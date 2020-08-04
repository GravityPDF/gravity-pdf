<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Fonts\SupportsOtl;
use GFPDF\Helper\Fonts\TtfFontValidation;
use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Model\Model_Custom_Fonts;
use GFPDF_Vendor\Upload\Exception\UploadException;
use GFPDF_Vendor\Upload\File;
use GFPDF_Vendor\Upload\Storage\FileSystem;
use GFPDF_Vendor\Upload\Validation\Extension;
use Psr\Log\LoggerInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Controller_Custom_Fonts
 *
 * @package GFPDF\Controller
 *
 * @since   6.0
 */
class Controller_Custom_Fonts extends Helper_Abstract_Controller {

	/**
	 * @var LoggerInterface
	 * @since 6.0
	 */
	protected $log;

	/**
	 * @var Helper_Abstract_Form
	 * @since 6.0
	 */
	protected $gform;

	/**
	 * @var Helper_Abstract_Options
	 */
	protected $options;

	/**
	 * @var string
	 */
	protected $font_dir_path;

	/**
	 * @var string[]
	 */
	protected $font_keys = [ 'regular', 'italics', 'bold', 'bolditalics' ];

	public function __construct( Model_Custom_Fonts $model, LoggerInterface $log, Helper_Abstract_Form $gform, Helper_Abstract_Options $options, $font_dir_path ) {
		$this->model         = $model;
		$this->log           = $log;
		$this->gform         = $gform;
		$this->options       = $options;
		$this->font_dir_path = $font_dir_path;
	}

	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	public function register_endpoints() {
		register_rest_route(
			'gravity-pdf/v1',
			'/fonts/',
			[
				[
					'methods'              => WP_REST_Server::READABLE,
					'callback'             => [ $this, 'get_all_items' ],
					/*'permission_callback' => \Closure::fromCallable( [ $this, 'check_permissions' ] ),*/
					'permissions_callback' => '__return_true',
				],

				[
					'methods'              => WP_REST_Server::CREATABLE,
					'callback'             => [ $this, 'add_item' ],
					/*'permission_callback' => \Closure::fromCallable( [ $this, 'check_permissions' ] ),*/
					'permissions_callback' => '__return_true',
					'args'                 => [
						'label' => [
							'description'       => __( 'The font label used for the object', 'gravity-forms-pdf-extended' ),
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => [ $this->model, 'check_font_name_valid' ],
						],
					],
				],
			]
		);

		register_rest_route(
			'gravity-pdf/v1',
			'/fonts/(?P<id>[a-z0-9]+)',
			[
				'args' => [
					'id' => [
						'description'       => __( 'Unique identifier for the object.', 'default' ),
						'type'              => 'string',
						'validate_callback' => [ $this->model, 'check_font_id_valid' ],
						'required'          => true,
					],
				],

				[
					'methods'              => WP_REST_Server::CREATABLE,
					'callback'             => [ $this, 'update_item' ],
					/*'permission_callback' => \Closure::fromCallable( [ $this, 'check_permissions' ] ),*/
					'permissions_callback' => '__return_true',
					'args'                 => [
						'label' => [
							'description'       => __( 'The font label used for the object', 'gravity-forms-pdf-extended' ),
							'type'              => 'string',
							'validate_callback' => [ $this->model, 'check_font_name_valid' ],
						],

						'regular' => [
							'description'       => __( 'The path to the regular font file. Pass empty value if it should be deleted', 'gravity-forms-pdf-extended' ),
							'type'              => 'string',
							'validate_callback' => \Closure::fromCallable( [ $this, 'check_empty_string' ] ),
						],

						'italics' => [
							'description'       => __( 'The path to the italics font file. Pass empty value if it should be deleted', 'gravity-forms-pdf-extended' ),
							'type'              => 'string',
							'validate_callback' => \Closure::fromCallable( [ $this, 'check_empty_string' ] ),
						],

						'bold' => [
							'description'       => __( 'The path to the bold font file. Pass empty value if it should be deleted', 'gravity-forms-pdf-extended' ),
							'type'              => 'string',
							'validate_callback' => \Closure::fromCallable( [ $this, 'check_empty_string' ] ),
						],

						'bolditalics' => [
							'description'       => __( 'The path to the bolditalics font file. Pass empty value if it should be deleted', 'gravity-forms-pdf-extended' ),
							'type'              => 'string',
							'validate_callback' => \Closure::fromCallable( [ $this, 'check_empty_string' ] ),
						],
					],
				],

				[
					'methods'              => WP_REST_Server::DELETABLE,
					'callback'             => [ $this, 'delete_item' ],
					/*'permission_callback' => Closure::fromCallable( [ $this, 'check_permissions' ] ),*/
					'permissions_callback' => '__return_true',
					'validate_callback'    => [ $this->model, 'check_font_id_valid' ],
				],
			]
		);
	}


	public function get_all_items(): array {
		return $this->model->get_custom_fonts();
	}

	public function add_item( WP_REST_Request $request ) {
		try {
			$label = $request->get_param( 'label' );
			$id    = $this->model->get_unique_id( $this->options->get_font_short_name( $label ) );

			/* Handle uploads */
			$files = $this->get_uploaded_font_files( $request );
			$files = $this->move_fonts_to_font_dir( $files );

			/* Determine if font files support OTF data and auto register */
			$supports_otl = $this->does_fonts_support_otl( $files );

			/* Update database */
			$font = [
				'font_name'   => $label,
				'shortname'   => $id,
				'id'          => $id,
				'useOTL'      => $supports_otl ? 0xFF : 0x00,
				'useKashida'  => $supports_otl ? 75 : 0,
				'regular'     => $this->get_absolute_font_path( $files['regular']['name'] ),
				'italics'     => $this->get_absolute_font_path( $files['italics']['name'] ?? '' ),
				'bold'        => $this->get_absolute_font_path( $files['bold']['name'] ?? '' ),
				'bolditalics' => $this->get_absolute_font_path( $files['bolditalics']['name'] ?? '' ),
			];

			if ( ! $this->model->add_font( $font ) ) {
				throw new \Exception();
			}

			/* Flush mPDF cache */
			FlushCache::flush();

			return $font;
		} catch ( \Exception $e ) {
			return new WP_Error( 'something', '', [ 'status' => 500 ] );
		}
	}

	public function update_item( WP_REST_Request $request ) {
		try {
			$id = $request->get_param( 'id' );
			if ( ! $this->model->has_custom_font_id( $id ) ) {
				throw new \Exception();
				/* @TODO */
			}

			$font = $this->model->get_font_by_id( $id );

			/*
			 * Compare params to font key.
			 * Any that are different will be considered "deleted"
			 * Will need to delete file from disk and then update $font key
			 */
			if ( $label = $request->get_param( 'label' ) ) {
				$font['font_name'] = $label;
			}

			/* Delete any font files needed */
			$params = $request->get_body_params();
			foreach ( $params as $font_id => $val ) {
				if ( ! isset( $font[ $font_id ] ) ) {
					continue;
				}

				$this->delete_font_file( basename( $font[ $font_id ] ) );
				$font[ $font_id ] = '';
			}

			/*
			 * Handle newly-uploaded files
			 * If we are to replace an existing font we will need to delete it first
			 * Then update the $font key
			 */
			$files = $this->get_uploaded_font_files( $request );
			$files = $this->move_fonts_to_font_dir( $files );
			foreach ( $files as $font_id => $file ) {
				$font[ $font_id ] = $this->get_absolute_font_path( $file['name'] );
			}

			/*
			 * Run all fonts through the OTL check again
			 */
			$files = [];
			foreach ( $this->font_keys as $font_id ) {
				if ( empty( $font[ $font_id ] ) ) {
					continue;
				}

				$files[] = [
					'name' => basename( $font[ $font_id ] ),
				];
			}

			$supports_otl       = $this->does_fonts_support_otl( $files );
			$font['useOTL']     = $supports_otl ? 0xFF : 0x00;
			$font['useKashida'] = $supports_otl ? 75 : 0;

			/*
			 * Insert into database
			 */
			if ( ! $this->model->update_font( $font ) ) {
				throw new \Exception();
			}

			/*
			 * Flush cache
			 */
			FlushCache::flush();
		} catch ( \Exception $e ) {
			return new WP_Error( 'something', '', [ 'status' => 500 ] );
		}
	}

	public function delete_item( WP_REST_Request $request ) {
		try {
			$id = $request->get_param( 'id' );
			if ( ! $this->model->has_custom_font_id( $id ) ) {
				throw new \Exception();
				/* @TODO */
			}

			/* Delete TTF files from disk */
			$font = $this->model->get_font_by_id( $id );
			foreach ( $this->font_keys as $font_id ) {
				if ( empty( $font[ $font_id ] ) ) {
					continue;
				}

				$this->delete_font_file( basename( $font[ $font_id ] ) );
			}

			/* Update DB */
			$this->model->delete_font( $id );

			/* Flush mPDF cache */
			FlushCache::flush();

		} catch ( \Exception $e ) {
			return new WP_Error( 'something', '', [ 'status' => 500 ] );
		}
	}

	public function get_absolute_font_path( $name ) {
		return ! empty( $name ) ? $this->font_dir_path . $name : '';
	}

	protected function get_uploaded_font_files( WP_REST_Request $request ): array {
		return array_filter( $request->get_file_params(), function( $id ) {
			return in_array( $id, $this->font_keys, true );
		}, ARRAY_FILTER_USE_KEY );
	}

	/* @TODO - migrate to Helper class */
	protected function move_fonts_to_font_dir( $files ) {
		$storage = new FileSystem( $this->font_dir_path );

		/* Ensure the regular font file has been uploaded (required field) */
		if ( ! isset( $files['regular'] ) ) {
			throw new UploadException( 'The Regular font is required' );
		}

		foreach ( $files as $id => $file ) {
			$file = new File( $id, $storage );

			/* Add validation checks */
			$file->addValidations( [
				new Extension( 'ttf' ),
				new TtfFontValidation(),
			] );

			/* Give file a unique name, if already exists */
			while ( is_file( $this->font_dir_path . $file->getNameWithExtension() ) ) {
				$file->setName( $file->getName() . substr( (string) time(), -5 ) );
				$files[ $id ]['name'] = $file->getNameWithExtension();
			}

			/* Do validation and move to the font directory */
			$file->upload();
		}

		return $files;
	}

	/* @TODO - migrate to Helper class */
	protected function delete_font_file( $file ) {
		if ( is_file( $this->font_dir_path . $file ) && ! unlink( $this->font_dir_path . $file ) ) {
			return false;
		}

		return true;
	}

	protected function check_empty_string( string $input ): bool {
		return empty( $input );
	}

	protected function check_permissions(): bool {
		$capabilities = $this->gform->has_capability( 'gravityforms_view_entries' );
		if ( ! $capabilities ) {
			$this->log->warning( 'Permission denied: user does not have "gravityforms_view_entries" capabilities' );
		}

		return $capabilities;
	}

	protected function does_fonts_support_otl( array $files ): bool {
		$data         = \GPDFAPI::get_data_class();
		$supports_otl = true;
		$otl          = new SupportsOtl( $this->font_dir_path, $data->mpdf_tmp_location );

		foreach ( $files as $file ) {
			if ( ! isset( $file['name'] ) || ! is_file( $this->font_dir_path . $file['name'] ) ) {
				throw new \Exception();
			}

			if ( ! $otl->supports_otl( $file['name'] ) ) {
				$supports_otl = false;
				break;
			}
		}

		return $supports_otl;
	}

}