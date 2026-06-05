<?php

namespace GFPDF\Rest;

use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Url_Signer;
use GFPDF\Model\Model_PDF;
use GPDFAPI;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 7.0
 */
class Rest_Download_Pdf {

	/**
	 * @var string
	 * @since 7.0
	 */
	public const NAMESPACE = Helper_Data::REST_API_BASENAME . 'v1';

	/**
	 * @var string
	 * @since 7.0
	 */
	public const API_BASE = '/download';

	/**
	 * @var string[]
	 * @since 7.0
	 */
	public static $endpoints = [
		'download-pdf' => self::API_BASE . '/(?P<entry>[\d]+)/(?P<pdf>[a-fA-F0-9]{13})',
	];

	/**
	 * @var Helper_Form
	 * @since 7.0
	 */
	protected $gform;

	/**
	 * @var Helper_Interface_Url_Signer
	 * @since 7.0
	 */
	protected $url_signer;

	/**
	 * @param Helper_Form                 $gform
	 * @param Helper_Interface_Url_Signer $url_signer
	 *
	 * @since 7.0
	 */
	public function __construct( $gform, Helper_Interface_Url_Signer $url_signer ) {
		$this->gform      = $gform;
		$this->url_signer = $url_signer;
	}

	/**
	 * @return void
	 * @since 7.0
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the routes for the API
	 *
	 * @return void
	 * @since 7.0
	 */
	public function register_routes() {

		/*
		 * Route to generate and download a PDF for a specific entry
		 */
		register_rest_route(
			static::NAMESPACE,
			static::$endpoints['download-pdf'],
			[
				'args' => [
					'entry' => [
						'description'       => __( 'The unique identifier for the Gravity Forms entry.', 'gravity-forms-pdf-extended' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => function ( $param, $request ) {
							$entry = $this->gform->get_entry( $param );

							if ( ! is_wp_error( $entry ) ) {
								return true;
							}

							return new WP_Error(
								'rest_invalid_param',
								sprintf( __( 'Invalid entry ID %d provided.', 'gravity-forms-pdf-extended' ), $param ),
								[ 'status' => 400 ]
							);
						},
					],

					'pdf'   => [
						'description'       => __( 'The identifier for the PDF', 'gravity-forms-pdf-extended' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => function ( $param, $request ) {

							/* Get all active PDFs for the entry */
							$pdfs = GPDFAPI::get_entry_pdfs( $request->get_param( 'entry' ) );

							/* PDF ID is valid if in $pdfs array */
							if ( ! is_wp_error( $pdfs ) && isset( $pdfs[ $param ] ) ) {
								return true;
							}

							/* translators: 1: Parameter, 2: List of valid values. */

							return new WP_Error( 'rest_not_in_enum', wp_sprintf( __( '%1$s is not one of %2$l.', 'default' ), $param, ! is_wp_error( $pdfs ) ? array_keys( $pdfs ) : '""' ) );
						},
					],
				],

				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'type'      => [
							'description' => __( 'How to return the PDF: "base64" embeds the document in the response, "url" returns a signed proxy URL that streams it.', 'gravity-pdf' ),
							'type'        => 'string',
							'enum'        => [ 'base64', 'url' ],
							'default'     => 'base64',
						],

						'urlExpiry' => [
							'description' => __( 'How long a "url" type link remains valid, as a relative time string (e.g. "30 minutes", "2 hours"). Defaults to the global signed URL timeout.', 'gravity-pdf' ),
							'type'        => 'string',
							'required'    => false,
						],
					],
				],

				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'download_item' ],
					'permission_callback' => [ $this, 'download_item_permissions_check' ],
				],
			],
		);
	}

	/**
	 * Generate the PDF and return it inline as base64 data, or as a signed proxy URL
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 *
	 * @since 7.0
	 */
	public function get_item( $request ) {
		$path_to_pdf = $this->generate_pdf( $request );

		if ( is_wp_error( $path_to_pdf ) ) {
			return $path_to_pdf;
		}

		$data = [
			'filename' => wp_basename( $path_to_pdf ),
			'size'     => filesize( $path_to_pdf ),
		];

		if ( $request->get_param( 'type' ) === 'url' ) {
			$data['url'] = $this->get_signed_url( $request );
		} else {
			$data['data'] = base64_encode( file_get_contents( $path_to_pdf ) ); //phpcs:ignore
		}

		$response = new WP_REST_Response( $data );
		$response->add_links( $this->prepare_links( $request ) );

		return $response;
	}

	/**
	 * Validate the signed URL and stream the entry's PDF to the browser
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|void
	 *
	 * @since 7.0
	 */
	public function download_item( $request ) {
		$path_to_pdf = $this->generate_pdf( $request );

		if ( is_wp_error( $path_to_pdf ) ) {
			return $path_to_pdf;
		}

		/** @var Model_PDF $model_pdf */
		$model_pdf = \GPDFAPI::get_pdf_class( 'model' );

		return $model_pdf->send_pdf_to_browser( $path_to_pdf, 'download' );
	}

	/**
	 * Generate and save the entry's PDF, returning its path or a 500 error
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return string|WP_Error
	 *
	 * @since 7.0
	 */
	protected function generate_pdf( $request ) {
		$path_to_pdf = \GPDFAPI::create_pdf( $request->get_param( 'entry' ), $request->get_param( 'pdf' ) );

		if ( is_wp_error( $path_to_pdf ) ) {
			/*
			 * Both entry and PDF IDs have been validated by this point.
			 * If an error occurred it was in the PDF generator, and why the 500 server error.
			 */
			$path_to_pdf->add_data( [ 'status' => 500 ] );
		}

		return $path_to_pdf;
	}

	/**
	 * Build the download endpoint URL for an entry's PDF
	 *
	 * @param int    $entry_id
	 * @param string $pdf_id
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	protected function get_download_url( $entry_id, $pdf_id ) {
		return rest_url( sprintf( '%s/%d/%s', static::get_route_basepath(), $entry_id, $pdf_id ) );
	}

	/**
	 * Build a signed proxy URL pointing at this endpoint's GET route
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	protected function get_signed_url( $request ) {
		$url = $this->get_download_url( $request->get_param( 'entry' ), $request->get_param( 'pdf' ) );

		return $this->url_signer->sign( $url, (string) $request->get_param( 'urlExpiry' ) );
	}

	/**
	 * Add links for PDF
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function prepare_links( $request ) {

		$pdf_id   = $request->get_param( 'pdf' );
		$entry_id = $request->get_param( 'entry' );

		$entry   = $this->gform->get_entry( $entry_id );
		$form_id = $entry['form_id'];

		$links = [
			'self' => [
				'href' => $this->get_download_url( $entry_id, $pdf_id ),
			],

			'pdf'  => [
				'href'       => rest_url( sprintf( '%s/%d/%s', Rest_Form_Settings::get_route_basepath(), $form_id, $pdf_id ) ),
				'embeddable' => true,
			],
		];

		if ( ! class_exists( 'GFWebAPI' ) ) {
			return $links;
		}

		/* If GF REST API enabled, include direct links to form endpoint */
		$gfwebapi = \GFWebAPI::get_instance();
		if ( ! $gfwebapi->is_v2_enabled( $gfwebapi->get_plugin_settings() ) ) {
			return $links;
		}

		$links['form'] = [
			'href'       => rest_url( sprintf( 'gf/v2/forms/%d', $form_id ) ),
			'embeddable' => true,
		];

		if ( ! empty( $entry_id ) ) {
			$links['entry'] = [
				'href'       => rest_url( sprintf( 'gf/v2/entries/%d', $entry_id ) ),
				'embeddable' => true,
			];
		}

		return $links;
	}

	/**
	 * @return string
	 *
	 * @since 7.0
	 */
	public static function get_route_basepath() {
		return static::NAMESPACE . static::API_BASE;
	}

	/**
	 * Ensure current user is allowed to access PDF
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool
	 */
	public function get_item_permissions_check( $request ) {
		/* Check if the current user has appropriate capability to view document */
		/** @var Model_PDF $model_pdf */
		$model_pdf = \GPDFAPI::get_pdf_class( 'model' );
		if ( $model_pdf->can_user_view_pdf_with_capabilities() ) {
			return true;
		}

		/* check if the current user is the owner and owner access is not disabled in the PDF settings */
		$entry = $this->gform->get_entry( $request->get_param( 'entry' ) );
		$pdf   = \GPDFAPI::get_pdf( $entry['form_id'] ?? 0, $request->get_param( 'pdf' ) );

		$user_id             = get_current_user_id();
		$is_owner_restricted = $pdf['restrict_owner'] ?? 'No';
		if ( $user_id > 0 && $is_owner_restricted !== 'Yes' && (int) $entry['created_by'] === $user_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Authorise the GET route using the signed URL, which acts as a bearer token in place of a login
	 *
	 * @return bool|WP_Error
	 *
	 * @since 7.0
	 */
	public function download_item_permissions_check() {
		if ( $this->url_signer->verify_current_request() ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'The PDF link is invalid or has expired.', 'gravity-pdf' ),
			[ 'status' => 401 ]
		);
	}
}
