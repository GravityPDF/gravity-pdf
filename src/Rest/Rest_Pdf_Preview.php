<?php

namespace GFPDF\Rest;

use GFPDF\Model\Model_PDF;
use WP_REST_Server;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 6.12
 */
class Rest_Pdf_Preview extends Rest_Form_Settings {

	/**
	 * @var string[]
	 * @since 6.12
	 */
	public static $endpoints = [
		'pdf-settings-preview' => self::API_BASE . '/(?P<form>[\d]+)/preview',
	];

	/**
	 * Registers the routes for this endpoint
	 *
	 * @return void
	 * @since 6.12.0
	 */
	public function register_routes() {

		register_rest_route(
			static::NAMESPACE,
			static::$endpoints['pdf-settings-preview'],
			[
				'args'   => [
					'form'  => [
						'description'       => __( 'The unique identifier for the Gravity Forms form.', 'gravity-forms-pdf-extended' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'check_form_is_valid' ],
					],

					'entry' => [
						'description'       => __( 'The unique identifier for the Gravity Forms entry.', 'gravity-forms-pdf-extended' ),
						'type'              => 'integer',
						'required'          => false,
						'validate_callback' => [ $this, 'check_entry_is_valid' ],
					],
				],

				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],

				'schema' => [ $this, 'get_public_item_schema' ],
			],
			true
		);
	}

	/**
	 * Take the current PDF settings and generate a PDF Preview
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|null
	 *
	 * @since 6.12
	 */
	public function create_item( $request ) {
		$form  = $this->gform->get_form( $request->get_param( 'form' ) );
		$entry = $this->get_entry( $request, $form );

		/* Prepare request for previewing */
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'password', '' );

		$pdf_settings       = $this->prepare_item_for_database( $request );
		$pdf_settings['id'] = uniqid( 'review' );

		/* Generate the PDF */
		/** @var Model_PDF $pdf_model */
		$pdf_model = \GPDFAPI::get_pdf_class( 'model' );

		$pdf_path = $pdf_model->generate_and_save_pdf( $entry, $pdf_settings );
		if ( is_wp_error( $pdf_path ) ) {
			return $pdf_path;
		}

		/* Sends the PDF to browser, or return WP_Error */
		return $pdf_model->send_pdf_to_browser( $pdf_path );
	}

	/**
	 * Get a Gravity Forms Entry object
	 *
	 * @param \WP_REST_Request $request
	 * @param array $form
	 *
	 * @return array|\WP_Error
	 */
	protected function get_entry( $request, $form ) {
		/* user requested a specific entry to preview */
		if ( $request->get_param( 'entry' ) ) {
			return $this->gform->get_entry( $request->get_param( 'entry' ) );
		}

		/* try to get the last form submission */
		$latest_entry = \GFAPI::get_entries(
			$form['id'],
			[ 'status' => 'active' ],
			null,
			[ 'page_size' => 1 ]
		);

		if ( ! is_wp_error( $latest_entry ) && isset( $latest_entry[0] ) ) {
			return $latest_entry[0];
		}

		/* fallback to a blank entry */

		return \GFFormsModel::create_lead( $form );
	}
}
