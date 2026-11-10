<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use Closure;
use Exception;
use GFPDF\Exceptions\GravityPdfDatabaseUpdateException;
use GFPDF\Exceptions\GravityPdfFontNotFoundException;
use GFPDF\Exceptions\GravityPdfIdException;
use GFPDF\Exceptions\GravityPdfModelNotUpdatedException;
use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Fonts\SupportsOtl;
use GFPDF\Helper\Fonts\TtfFontValidation;
use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_Custom_Fonts;
use GFPDF\Model\Model_Font_Downloader;
use GFPDF\Model\Model_Font_Sources;
use GFPDF\Model\Model_Required_Fonts;
use GFPDF\Statics\Upload_Size;
use GFPDF_Vendor\GravityPdf\Upload\ErrorCode;
use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF_Vendor\GravityPdf\Upload\File;
use GFPDF_Vendor\GravityPdf\Upload\Filename;
use GFPDF_Vendor\GravityPdf\Upload\Validation\Extension;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use GPDFAPI;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;
use WP_Site;

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
 * Class Controller_Custom_Fonts
 *
 * @package GFPDF\Controller
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
	 * @var string The absolute path to the Custom Fonts directory on the server
	 * @since 6.0
	 */
	protected $font_dir_path;

	/**
	 * @var string
	 * @since 6.0
	 */
	protected $filesystem;

	/**
	 * @var string
	 * @since 6.0
	 */
	protected $file;

	/**
	 * @var string[] List of the standard font keys used when saving settings
	 * @since 6.0
	 */
	protected $font_keys = Model_Custom_Fonts::FONT_STYLES;

	public function __construct( Model_Custom_Fonts $model, LoggerInterface $log, Helper_Abstract_Form $gform, string $font_dir_path, string $filesystem = 'GFPDF_Vendor\\GravityPdf\\Upload\\Storage\\FileSystem', string $file = 'GFPDF_Vendor\\GravityPdf\\Upload\\File' ) {
		$this->model         = $model;
		$this->log           = $log;
		$this->gform         = $gform;
		$this->font_dir_path = $font_dir_path;

		$this->filesystem = $filesystem;
		$this->file       = $file;
	}

	/**
	 * @since 6.0
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );

		add_action( 'add_option_gfpdf_settings', [ $this, 'record_site_fonts' ], 10, 2 );
		add_action( 'update_option_gfpdf_settings', [ $this, 'record_site_fonts' ], 10, 2 );

		foreach ( [ 'add_site_option', 'update_site_option', 'delete_site_option' ] as $hook ) {
			add_action( $hook, [ $this->model, 'maybe_flush_site_records' ] );
		}

		/* Ahead of WordPress dropping the site's tables */
		add_action( 'wp_uninitialize_site', [ $this, 'delete_site_fonts' ], 5 );
	}

	/**
	 * Record which files the site's fonts use, so other sites can leave them alone. An unchanged record isn't written.
	 *
	 * @param mixed $old      The old settings, or the option name
	 * @param mixed $settings The saved settings
	 *
	 * @since 6.19.0
	 */
	public function record_site_fonts( $old, $settings ): void {
		if ( is_array( $settings ) ) {
			$this->model->record_site_fonts( get_current_blog_id(), $settings['custom_fonts'] ?? [] );
		}
	}

	/**
	 * Delete a deleted site's font files no other site uses, and its record
	 *
	 * @param mixed $site
	 *
	 * @since 6.19.0
	 */
	public function delete_site_fonts( $site ): void {
		if ( ! $site instanceof WP_Site ) {
			return;
		}

		$site_id = (int) $site->id;
		$fonts   = $this->model->get_site_font_files( $site_id );

		$this->delete_unused_font_files( array_merge( [], ...array_values( $fonts ) ), $site_id );
		array_map( [ FlushCache::class, 'flush' ], array_map( 'strval', array_keys( $fonts ) ) );

		$this->model->delete_site_record( $site_id );
	}

	/**
	 * Register the Font CRUD REST API endpoints
	 *
	 * @since 6.0
	 */
	public function register_endpoints(): void {
		register_rest_route(
			Helper_Data::REST_API_BASENAME . 'v1',
			'/fonts/',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_scoped_items' ],
					'permission_callback' => Closure::fromCallable( [ $this, 'check_permissions' ] ),
					'args'                => [
						'scope' => [
							'description'       => __( 'Which to return: custom fonts, every font the Font Manager lists, or why fonts are in use', 'gravity-pdf' ),
							'type'              => 'string',
							'enum'              => [ 'custom', 'all', 'usage' ],
							'default'           => 'custom',
							'validate_callback' => 'rest_validate_request_arg',
						],
					],
				],

				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_item' ],
					'permission_callback' => Closure::fromCallable( [ $this, 'check_permissions' ] ),
					'args'                => [
						'label' => [
							'description'       => __( 'The font label used for the object', 'gravity-pdf' ),
							'type'              => 'string',
							'required'          => true,
							'validate_callback' => [ $this->model, 'check_font_name_valid' ],
						],
					],
				],
			]
		);

		register_rest_route(
			Helper_Data::REST_API_BASENAME . 'v1',
			'/fonts/(?P<id>[a-z0-9\-]+)',
			[
				'args' => [
					'id'    => [
						'description'       => __( 'Unique identifier for the object.', 'default' ),
						'type'              => 'string',
						'validate_callback' => [ $this->model, 'check_font_id_valid' ],
						'required'          => true,
					],
					'scope' => [
						'description'       => __( 'Whether the ID is a custom font or a downloadable core font. POST installs a core font.', 'gravity-pdf' ),
						'type'              => 'string',
						'enum'              => [ 'custom', 'core' ],
						'default'           => 'custom',
						'validate_callback' => 'rest_validate_request_arg',
					],
				],

				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item_usage' ],
					'permission_callback' => Closure::fromCallable( [ $this, 'check_permissions' ] ),
					'args'                => [
						'scope' => [
							'description'       => __( 'Return what uses the font', 'gravity-pdf' ),
							'type'              => 'string',
							'enum'              => [ 'usage' ],
							'required'          => true,
							'validate_callback' => 'rest_validate_request_arg',
						],
					],
				],

				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_or_install_item' ],
					'permission_callback' => Closure::fromCallable( [ $this, 'check_item_permissions' ] ),
					'args'                => [
						'label'       => [
							'description'       => __( 'The font label used for the object', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => [ $this->model, 'check_font_name_valid' ],
						],

						'regular'     => [
							'description'       => __( 'The path to the `regular` font file. Pass empty value if it should be deleted', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => Closure::fromCallable( [ $this, 'check_empty_string' ] ),
						],

						'italics'     => [
							'description'       => __( 'The path to the `italics` font file. Pass empty value if it should be deleted', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => Closure::fromCallable( [ $this, 'check_empty_string' ] ),
						],

						'bold'        => [
							'description'       => __( 'The path to the `bold` font file. Pass empty value if it should be deleted', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => Closure::fromCallable( [ $this, 'check_empty_string' ] ),
						],

						'bolditalics' => [
							'description'       => __( 'The path to the `bolditalics` font file. Pass empty value if it should be deleted', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => Closure::fromCallable( [ $this, 'check_empty_string' ] ),
						],
					],
				],

				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_scoped_item' ],
					'permission_callback' => Closure::fromCallable( [ $this, 'check_item_permissions' ] ),
				],
			]
		);
	}

	/**
	 * @return string[]
	 *
	 * @since 6.0
	 */
	public function get_font_keys(): array {
		return $this->font_keys;
	}

	/**
	 * Get a numerical array of all custom installed fonts
	 *
	 * @since 6.0
	 */
	public function get_all_items(): array {
		return array_values( $this->model->get_custom_fonts() );
	}

	/**
	 * The fonts route's `scope`. Usage is separate from the list as it can need every form read.
	 *
	 * @return array|object|WP_Error Usage is an object, so none in use is still a JSON map
	 *
	 * @since 6.19.0
	 */
	public function get_scoped_items( WP_REST_Request $request ) {
		$scope = $request->get_param( 'scope' );
		if ( $scope === 'custom' ) {
			return $this->get_all_items();
		}

		$downloader = $this->get_downloader();
		if ( ! $downloader instanceof Model_Font_Downloader ) {
			return $downloader;
		}

		return $scope === 'all' ? $downloader->get_installed_fonts() : (object) $downloader->get_font_usage();
	}

	/**
	 * The font route's GET: the PDFs using a font, for when it's opened
	 *
	 * @return array|WP_Error
	 *
	 * @since 6.19.0
	 */
	public function get_item_usage( WP_REST_Request $request ) {
		$downloader = $this->get_downloader();

		return $downloader instanceof Model_Font_Downloader ? [ 'pdfs' => $downloader->get_pdfs_using_font( (string) $request->get_param( 'id' ) ) ] : $downloader;
	}

	/**
	 * The font route's POST: an update, or with the `core` scope an install
	 *
	 * @return array|WP_Error
	 *
	 * @since 6.19.0
	 */
	public function update_or_install_item( WP_REST_Request $request ) {
		if ( $request->get_param( 'scope' ) !== 'core' ) {
			return $this->update_item( $request );
		}

		$downloader = $this->get_downloader();

		return $downloader instanceof Model_Font_Downloader ? $downloader->install_font( (string) $request->get_param( 'id' ) ) : $downloader;
	}

	/**
	 * Our `Create` CRUD for custom fonts
	 *
	 * @return array|WP_Error
	 *
	 * @since 6.0
	 */
	public function add_item( WP_REST_Request $request ) {
		$uncommitted_files = [];

		try {
			$label = $request->get_param( 'label' );
			$id    = $this->model->get_unique_id( $this->model->get_font_short_name( $label ) );

			/* Handle uploads */
			$files = $this->get_uploaded_font_files( $request );

			/* Ensure the regular font file has been uploaded (required field) */
			if ( ! isset( $files['regular'] ) ) {
				throw new UploadException( wp_json_encode( [ 'regular' => __( 'The Regular font is required', 'gravity-pdf' ) ] ) );
			}

			$files             = $this->move_fonts_to_font_dir( $files );
			$uncommitted_files = array_column( $files, 'name' );

			/* Determine if font files support OTF data and auto register */
			$supports_otl = $this->does_fonts_support_otl( $files );

			/* Update database */
			$font = [
				'font_name'   => $label,
				'id'          => $id,
				'useOTL'      => $supports_otl ? 0xFF : 0x00,
				'useKashida'  => $supports_otl ? 75 : 0,
				'regular'     => $this->get_absolute_font_path( $files['regular']['name'] ),
				'italics'     => $this->get_absolute_font_path( $files['italics']['name'] ?? '' ),
				'bold'        => $this->get_absolute_font_path( $files['bold']['name'] ?? '' ),
				'bolditalics' => $this->get_absolute_font_path( $files['bolditalics']['name'] ?? '' ),
			];

			if ( ! $this->model->add_font( $font ) ) {
				throw new GravityPdfDatabaseUpdateException();
			}

			$uncommitted_files = [];

			FlushCache::flush( $font['id'] );

			return $this->get_font_row( $font );
		} catch ( UploadException $e ) {
			$message = $e->getMessage()[0] === '{' ? json_decode( $e->getMessage(), true ) : $e->getMessage();
			return new WP_Error( 'font_validation_error', $message, [ 'status' => 400 ] );
		} catch ( GravityPdfFontNotFoundException $e ) {
			$message = $e->getMessage()[0] === '{' ? json_decode( $e->getMessage(), true ) : $e->getMessage();
			return new WP_Error( 'font_file_gone_missing', $message, [ 'status' => 500 ] );
		} catch ( GravityPdfDatabaseUpdateException $e ) {
			return new WP_Error( 'database_error', '', [ 'status' => 500 ] );
		} catch ( GravityPdfIdException $e ) {
			return new WP_Error( 'invalid_font_id', $e->getMessage(), [ 'status' => 500 ] );
		} catch ( Exception $e ) {
			return new WP_Error( 'unknown_error', $e->getMessage(), [ 'status' => 500 ] );
		} finally {
			if ( isset( $e ) ) {
				$this->log->error( $e->getMessage() );
			}

			/* Don't leave the uploaded files behind when the font isn't saved */
			array_map( [ $this, 'delete_font_file' ], $uncommitted_files );
		}
	}

	/**
	 * Our `Update` CRUD for custom fonts
	 *
	 * This endpoint acts like a PATCH request, and data that isn't passed won't be updated
	 *
	 * @return array|WP_Error
	 *
	 * @since 6.0
	 */
	public function update_item( WP_REST_Request $request ) {
		$uncommitted_files = [];

		try {
			$id = $request->get_param( 'id' );
			if ( ! $this->model->matches_custom_font_id( $id ) ) {
				throw new GravityPdfIdException();
			}

			$font = $this->model->get_font_by_id( $id );

			$label = $request->get_param( 'label' );
			if ( ! empty( $label ) ) {
				$font['font_name'] = $label;
			}

			/* Files the font stops using (styles removed or replaced), deleted only once the change is saved */
			$unused_files = [];

			$params = $request->get_body_params();
			foreach ( $this->font_keys as $font_id ) {
				if ( ! isset( $params[ $font_id ] ) || empty( $font[ $font_id ] ) ) {
					continue;
				}

				$unused_files[]   = basename( $font[ $font_id ] );
				$font[ $font_id ] = '';
			}

			$files = $this->get_uploaded_font_files( $request );
			if ( count( $files ) > 0 ) {
				$files             = $this->move_fonts_to_font_dir( $files );
				$uncommitted_files = array_column( $files, 'name' );

				foreach ( $files as $font_id => $file ) {
					if ( ! empty( $font[ $font_id ] ) ) {
						$unused_files[] = basename( $font[ $font_id ] );
					}

					$font[ $font_id ] = $this->get_absolute_font_path( $file['name'] );
				}
			}

			/*
			 * Run all fonts through the OTL check again
			 */
			$files = [];
			foreach ( $this->font_keys as $font_id ) {
				if ( empty( $font[ $font_id ] ) ) {
					continue;
				}

				$files[ $font_id ] = [
					'name' => basename( $font[ $font_id ] ),
				];
			}

			$supports_otl = $this->does_fonts_support_otl( $files );

			if ( $supports_otl ) {
				$useKashida = $request->get_param( 'useKashida' ) ?? 75;
				if ( $useKashida !== null ) {
					$useKashida = (int) $useKashida;
					if ( $useKashida < 0 || $useKashida > 100 ) {
						throw new \InvalidArgumentException( __( 'Kashida needs to be a value between 0-100', 'gravity-pdf' ) );
					}
				}

				$font['useOTL']     = 0xFF;
				$font['useKashida'] = $useKashida;
			} else {
				$font['useOTL']     = 0x00;
				$font['useKashida'] = 0;
			}

			/* Update database, if needed */
			if ( $this->model->get_custom_fonts()[ $font['id'] ] !== $font && ! $this->model->update_font( $font ) ) {
				throw new GravityPdfDatabaseUpdateException();
			}

			/* Not one an upload was just saved under, which happens when the old file had gone missing */
			$this->delete_unused_font_files( array_diff( $unused_files, $uncommitted_files ) );

			$uncommitted_files = [];

			FlushCache::flush( $font['id'] );

			return $this->get_font_row( $font );
		} catch ( UploadException $e ) {
			$message = $e->getMessage()[0] === '{' ? json_decode( $e->getMessage(), true ) : $e->getMessage();
			return new WP_Error( 'font_validation_error', $message, [ 'status' => 400 ] );
		} catch ( GravityPdfFontNotFoundException $e ) {
			$message = $e->getMessage()[0] === '{' ? json_decode( $e->getMessage(), true ) : $e->getMessage();
			return new WP_Error( 'font_file_gone_missing', $message, [ 'status' => 500 ] );
		} catch ( GravityPdfModelNotUpdatedException $e ) {
			return new WP_Error( 'no_changes_found', '', [ 'status' => 400 ] );
		} catch ( GravityPdfDatabaseUpdateException $e ) {
			return new WP_Error( 'database_error', '', [ 'status' => 500 ] );
		} catch ( GravityPdfIdException $e ) {
			return new WP_Error( 'invalid_font_id', $e->getMessage(), [ 'status' => 400 ] );
		} catch ( Exception $e ) {
			return new WP_Error( 'unknown_error', $e->getMessage(), [ 'status' => 500 ] );
		} finally {
			if ( isset( $e ) ) {
				$this->log->error( $e->getMessage() );
			}

			/* Don't leave the uploaded files behind when the font isn't saved */
			array_map( [ $this, 'delete_font_file' ], $uncommitted_files );
		}
	}

	/**
	 * A saved font as the Font Manager lists it
	 *
	 * @since 6.19.0
	 */
	protected function get_font_row( array $font ): array {
		$downloader = $this->get_downloader();

		return $downloader instanceof Model_Font_Downloader ? $downloader->get_custom_font_row( $font ) : $font;
	}

	/**
	 * The downloader, unless an add-on has unhooked it
	 *
	 * @return Model_Font_Downloader|WP_Error
	 *
	 * @since 6.19.0
	 */
	protected function get_downloader() {
		$downloader = GPDFAPI::get_mvc_class( 'Model_Font_Downloader' );

		return $downloader instanceof Model_Font_Downloader ? $downloader : new WP_Error( 'invalid_model', '', [ 'status' => 500 ] );
	}

	/**
	 * Changing a core font also needs the rights over the fonts folder a Multisite shares
	 *
	 * @since 6.19.0
	 */
	protected function check_item_permissions( WP_REST_Request $request ): bool {
		if ( ! $this->check_permissions() ) {
			return false;
		}

		if ( $request->get_param( 'scope' ) !== 'core' ) {
			return true;
		}

		$downloader = $this->get_downloader();

		return $downloader instanceof Model_Font_Downloader && $downloader->can_change_core_fonts();
	}

	/**
	 * The font route's DELETE: of a custom font, or with the `core` scope a core font
	 *
	 * @return array|void|WP_Error
	 *
	 * @since 6.19.0
	 */
	public function delete_scoped_item( WP_REST_Request $request ) {
		if ( $request->get_param( 'scope' ) !== 'core' ) {
			return $this->delete_unused_item( $request );
		}

		$downloader = $this->get_downloader();

		return $downloader instanceof Model_Font_Downloader ? $downloader->delete_downloaded_font( (string) $request->get_param( 'id' ) ) : $downloader;
	}

	/**
	 * Delete a custom font, refusing one in use. delete_item() doesn't, as the upgrade to 6.19 removes records whose fonts a
	 * source now serves, which stay in use.
	 *
	 * @return void|WP_Error
	 *
	 * @since 6.19.0
	 */
	public function delete_unused_item( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		$required_fonts = GPDFAPI::get_mvc_class( 'Model_Required_Fonts' );

		if ( $this->model->matches_custom_font_id( $id ) && $required_fonts instanceof Model_Required_Fonts && $required_fonts->is_font_in_use( $id ) ) {
			return new WP_Error( 'font_in_use', __( 'The font is in use, so it can\'t be deleted.', 'gravity-pdf' ), [ 'status' => 409 ] );
		}

		return $this->delete_item( $request );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return void|WP_Error
	 *
	 * @since 6.0
	 */
	public function delete_item( WP_REST_Request $request ) {
		try {
			$id = $request->get_param( 'id' );
			if ( ! $this->model->matches_custom_font_id( $id ) ) {
				throw new GravityPdfIdException();
			}

			/* Delete TTF files from disk */
			$font  = $this->model->get_font_by_id( $id );
			$files = [];
			foreach ( $this->font_keys as $font_id ) {
				if ( ! empty( $font[ $font_id ] ) ) {
					$files[] = basename( $font[ $font_id ] );
				}
			}

			$this->delete_unused_font_files( $files );

			/* Update DB */
			if ( ! $this->model->delete_font( $id ) ) {
				throw new GravityPdfDatabaseUpdateException();
			}

			FlushCache::flush( $id );

			return;

		} catch ( GravityPdfDatabaseUpdateException $e ) {
			return new WP_Error( 'database_error', '', [ 'status' => 500 ] );
		} catch ( GravityPdfIdException $e ) {
			return new WP_Error( 'invalid_font_id', $e->getMessage(), [ 'status' => 400 ] );
		} catch ( Exception $e ) {
			return new WP_Error( 'unknown_error', $e->getMessage(), [ 'status' => 500 ] );
		} finally {
			if ( isset( $e ) ) {
				$this->log->error( $e->getMessage() );
			}
		}
	}

	/**
	 * @since 6.0
	 */
	public function get_absolute_font_path( string $name ): string {
		return ! empty( $name ) ? $this->font_dir_path . $name : '';
	}

	/**
	 * Return any uploaded file details with a key matching the `font_keys`
	 *
	 * @since 6.0
	 */
	protected function get_uploaded_font_files( WP_REST_Request $request ): array {
		return array_filter(
			$request->get_file_params(),
			function ( $id ) {
				return in_array( $id, $this->font_keys, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Validate font files and move from tmp to custom font directory
	 *
	 * @param array $files Accepts array returned by self::get_uploaded_font_files()
	 *
	 * @return array New font file details (may include a renamed font file)
	 *
	 * @since 6.0
	 */
	protected function move_fonts_to_font_dir( array $files ): array {
		GPDFAPI::get_misc_class()->create_folder( $this->font_dir_path );

		$storage = new $this->filesystem( $this->font_dir_path );
		$uploads = [];
		$errors  = [];

		foreach ( $files as $id => $file ) {
			$file = new $this->file( $id, $storage );

			/* Add validation checks */
			$file->addValidations(
				[
					new Extension( 'ttf' ),
					new TtfFontValidation(),
				]
			);

			/* Check every style before saving any, so a rejected one leaves nothing behind */
			if ( ! $file->isValid() ) {
				$errors[ $id ] = $this->get_upload_error_message( $file );
			}

			$uploads[ $id ] = $file;
		}

		if ( count( $errors ) > 0 ) {
			throw new UploadException( wp_json_encode( $errors ) );
		}

		$saved = [];
		foreach ( $uploads as $id => $file ) {
			try {
				$this->upload_with_unique_name( $file );
			} catch ( UploadException $e ) {
				array_map( [ $this, 'delete_font_file' ], $saved );

				throw new UploadException( wp_json_encode( [ $id => $this->get_upload_error_message( $file, $e->getErrorCode() ) ] ) );
			}

			/* The upload library rewrites characters like dots and brackets, so keep the name it saved under */
			$files[ $id ]['name'] = basename( current( $file->getUploadedLocators() ) );
			$saved[]              = $files[ $id ]['name'];
		}

		return $files;
	}

	/**
	 * Save the font, adding a number to its name if the name is taken or is one a core font's copy could have
	 *
	 * The library claims the name as it writes, so a clash only shows up as its DESTINATION_EXISTS error
	 *
	 * @throws UploadException
	 *
	 * @since 6.18.0
	 */
	protected function upload_with_unique_name( File $file ): void {
		$name = $file->getName();

		for ( $attempt = 1; ; $attempt++ ) {
			$reserved = $this->is_reserved_name( $file );

			if ( ! $reserved ) {
				try {
					$file->upload();

					return;
				} catch ( UploadException $e ) {
					if ( $e->getErrorCode() !== ErrorCode::DESTINATION_EXISTS || $attempt >= 10 ) {
						throw $e;
					}
				}
			}

			/* Leaves room for the dot, extension and number, which the library would otherwise cut off the end */
			$base = mb_strcut( $name, 0, Filename::MAX_LENGTH - strlen( $file->getExtension() ) - 6, 'UTF-8' );

			/* Digits alone could complete a reserved name's hash */
			$file->setName( $base . ( $reserved ? '_' . wp_rand( 1000, 9999 ) : wp_rand( 10000, 99999 ) ) );
		}
	}

	/**
	 * Whether the name is one a core font's copy could have, which is used before the file at the font's own name
	 *
	 * @since 6.19.0
	 */
	protected function is_reserved_name( File $file ): bool {
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );

		return $sources instanceof Model_Font_Sources && $sources->is_reserved_name( $file->getNameWithExtension() );
	}

	/**
	 * Get a user-facing reason the font upload was refused
	 *
	 * @since 6.18.0
	 */
	protected function get_upload_error_message( File $file, string $error_code = ErrorCode::VALIDATION_FAILED ): string {
		$codes = array_column( $file->getErrorDetails(), 'code' );

		if ( array_intersect( $codes, [ ErrorCode::INI_SIZE, ErrorCode::FORM_SIZE ] ) ) {
			/* translators: %s: the maximum upload size, e.g. 8 MB */
			return sprintf( __( 'The font file is larger than the %s upload limit', 'gravity-pdf' ), size_format( Upload_Size::get_php_limit() ) );
		}

		$transfer_failures = [
			ErrorCode::PARTIAL,
			ErrorCode::NO_TMP_DIR,
			ErrorCode::CANT_WRITE,
			ErrorCode::EXTENSION_STOPPED,
			ErrorCode::UNKNOWN_TRANSFER_ERROR,
		];

		/* Storage failures aren't recorded as error details; they only reach us as the thrown code */
		if ( array_intersect( $codes, $transfer_failures ) || $error_code !== ErrorCode::VALIDATION_FAILED ) {
			return __( 'The font file could not be saved. Ask your web host to check the PHP upload settings', 'gravity-pdf' );
		}

		return __( 'The upload is not a valid TTF file', 'gravity-pdf' );
	}

	/**
	 * Delete font files, keeping any another site's fonts use (the fonts folder is network-wide)
	 *
	 * @param string[] $files   The filenames
	 * @param int      $site_id The site the files are deleted for, the current one by default
	 *
	 * @since 6.19.0
	 */
	protected function delete_unused_font_files( array $files, int $site_id = 0 ): void {
		if ( $files === [] ) {
			return;
		}

		$shared = $this->model->get_files_used_by_other_sites( $site_id );

		foreach ( $files as $file ) {
			if ( in_array( $file, $shared, true ) ) {
				$this->log->debug( 'Kept a font file another site uses', [ 'file' => $file ] );
				continue;
			}

			$this->delete_font_file( $file );
		}
	}

	/**
	 * @param string $file The filename of the font to be deleted
	 *
	 * @return bool
	 * @since 6.0
	 */
	protected function delete_font_file( string $file ): bool {
		if (
			is_file( $this->font_dir_path . $file ) &&
			! @unlink( $this->font_dir_path . $file ) /* phpcs:ignore */
		) {
			return false;
		}

		return true;
	}

	/**
	 * A validation callback for the REST API
	 *
	 * @since 6.0
	 */
	protected function check_empty_string( string $input ): bool {
		return empty( $input );
	}

	/**
	 * A permissions callback for the REST API endpoints
	 *
	 * @since 6.0
	 */
	protected function check_permissions(): bool {
		$capabilities = $this->gform->has_capability( 'gravityforms_edit_forms' );
		if ( ! $capabilities ) {
			$this->log->warning( 'Permission denied: user does not have "gravityforms_edit_forms" capabilities' );
		}

		return $capabilities;
	}

	/**
	 * Checks through all the font files for OTL support
	 *
	 * @param array $files Accepts array returned by self::get_uploaded_font_files()
	 *
	 * @return bool If supported for all fonts return true, false otherwise.
	 */
	protected function does_fonts_support_otl( array $files ): bool {
		$otl    = new SupportsOtl( $this->font_dir_path );
		$errors = [];

		$supports_otl = true;

		foreach ( $files as $id => $file ) {
			if ( ! isset( $file['name'] ) || ! is_file( $this->font_dir_path . $file['name'] ) ) {
				/* translators: %s: font filename */
				$errors[ $id ] = sprintf( __( 'Cannot find %s.', 'gravity-pdf' ), $file['name'] );
				continue;
			}

			if ( ! $otl->supports_otl( $file['name'] ) ) {
				$supports_otl = false;
				break;
			}
		}

		if ( count( $errors ) > 0 ) {
			throw new GravityPdfFontNotFoundException( wp_json_encode( $errors ) );
		}

		return $supports_otl;
	}
}
