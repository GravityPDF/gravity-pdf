<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use Exception;
use GFPDF\Exceptions\GravityPdfDatabaseUpdateException;
use GFPDF\Exceptions\GravityPdfFontNotFoundException;
use GFPDF\Exceptions\GravityPdfIdException;
use GFPDF\Exceptions\GravityPdfModelNotUpdatedException;
use GFPDF\Fonts\FlushCache;
use GFPDF\Fonts\Font_Repository;
use GFPDF\Fonts\SupportsOtl;
use GFPDF\Fonts\TtfFontValidation;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Model\Model_Custom_Fonts;
use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF_Vendor\GravityPdf\Upload\Validation\Extension;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use WP_Error;
use WP_REST_Request;
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
 * Upload, edit and delete a font of the site's own
 *
 * A font row, not a catalogue entry: `custom` and `imported` rows have no source and nothing to install, so this
 * addresses them by their mPDF key alone. It shares `Rest_Font_Base` with the catalogue routes for the capability
 * and nothing else.
 *
 * @package GFPDF\Rest
 *
 * @since 6.0
 */
class Rest_Custom_Fonts extends Rest_Font_Base {

	/**
	 * @var Model_Custom_Fonts
	 * @since 6.0
	 */
	protected $model;

	/**
	 * @var LoggerInterface
	 * @since 6.0
	 */
	protected $log;

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
	protected $font_keys = [ 'regular', 'italics', 'bold', 'bolditalics' ];

	public function __construct( Model_Custom_Fonts $model, LoggerInterface $log, Helper_Abstract_Form $gform, string $font_dir_path, string $filesystem = 'GFPDF_Vendor\\GravityPdf\\Upload\\Storage\\FileSystem', string $file = 'GFPDF_Vendor\\GravityPdf\\Upload\\File' ) {
		$this->model         = $model;
		$this->log           = $log;
		$this->gform         = $gform;
		$this->font_dir_path = $font_dir_path;

		$this->filesystem = $filesystem;
		$this->file       = $file;
	}

	/**
	 * The `{id}` route, with the literal `/fonts/…` words it must not swallow excluded up front
	 *
	 * Built from `Font_Repository`'s two constants rather than spelled here: the charset is the font-key charset,
	 * `_` included — an imported `Open_Sans.ttf` keys as `open_sans`, and a route narrower than the key rule is how
	 * those rows became unaddressable in the first place. WordPress anchors the whole pattern, so excluding the bare
	 * word is enough; a longer path can never reach a one-segment route.
	 *
	 * @since 7.0
	 */
	public static function id_route(): string {
		return sprintf(
			'/fonts/(?!(?:%s)$)(?P<id>[%s]+)',
			implode( '|', Font_Repository::RESERVED_ROUTE_KEYS ),
			Font_Repository::KEY_CHARS
		);
	}

	/**
	 * @deprecated 7.0 Renamed `register_routes()`, which is what `WP_REST_Controller` calls it
	 *
	 * @since 6.0
	 */
	public function register_endpoints(): void {
		$this->register_routes();
	}

	/**
	 * Register the Font CRUD REST API endpoints
	 *
	 * @since 7.0
	 */
	public function register_routes() {
		register_rest_route(
			static::NAMESPACE,
			'/fonts/',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_all_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],

				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
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
			static::NAMESPACE,
			static::id_route(),
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
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => [
						'label'       => [
							'description'       => __( 'The font label used for the object', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => [ $this->model, 'check_font_name_valid' ],
						],

						'regular'     => [
							'description'       => __( 'The path to the `regular` font file. Pass empty value if it should be deleted', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => [ $this, 'check_empty_string' ],
						],

						'italics'     => [
							'description'       => __( 'The path to the `italics` font file. Pass empty value if it should be deleted', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => [ $this, 'check_empty_string' ],
						],

						'bold'        => [
							'description'       => __( 'The path to the `bold` font file. Pass empty value if it should be deleted', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => [ $this, 'check_empty_string' ],
						],

						'bolditalics' => [
							'description'       => __( 'The path to the `bolditalics` font file. Pass empty value if it should be deleted', 'gravity-pdf' ),
							'type'              => 'string',
							'validate_callback' => [ $this, 'check_empty_string' ],
						],
					],
				],

				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
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
	 * Our `Create` CRUD for custom fonts
	 *
	 * @return array|WP_Error
	 *
	 * @since 6.0
	 */
	public function add_item( WP_REST_Request $request ) {
		try {
			$label = $request->get_param( 'label' );
			$id    = $this->model->get_unique_id( $this->model->get_font_short_name( $label ) );

			/* Handle uploads */
			$files = $this->get_uploaded_font_files( $request );

			/* Ensure the regular font file has been uploaded (required field) */
			if ( ! isset( $files['regular'] ) ) {
				throw new UploadException( wp_json_encode( [ 'regular' => __( 'The Regular font is required', 'gravity-pdf' ) ] ) );
			}

			$files = $this->move_fonts_to_font_dir( $files );

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

			FlushCache::flush();

			return $font;
		} catch ( UploadException $e ) {
			$message = $e->getMessage()[0] === '{' ? json_decode( $e->getMessage(), true ) : $e->getMessage();
			return new WP_Error( 'font_validation_error', $message, [ 'status' => 400 ] );
		} catch ( GravityPdfFontNotFoundException $e ) {
			$message = $e->getMessage()[0] === '{' ? json_decode( $e->getMessage(), true ) : $e->getMessage();
			return new WP_Error( 'font_file_gone_missing', $message, [ 'status' => 500 ] );
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
	 * Our `Update` CRUD for custom fonts
	 *
	 * This endpoint acts like a PATCH request, and data that isn't passed won't be updated
	 *
	 * @param WP_REST_Request $request Untyped: `WP_REST_Controller` declares it so, and narrowing is a fatal
	 *
	 * @return array|WP_Error
	 *
	 * @since 6.0
	 */
	public function update_item( $request ) {
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

			/*
			 * Clear any font faces the request asked to remove. The files go with the rows in `update_font()`:
			 * unlinking here would run before the row is gone, and the shared delete path skips a path a
			 * surviving row still records.
			 */
			$params = $request->get_body_params();
			foreach ( $this->font_keys as $font_id ) {
				if ( ! isset( $params[ $font_id ] ) || empty( $font[ $font_id ] ) ) {
					continue;
				}

				$font[ $font_id ] = '';
			}

			/*
			 * Handle newly-uploaded files
			 * If we are to replace an existing font we will need to delete it first and then update the $font key
			 */
			$files = $this->get_uploaded_font_files( $request );
			if ( count( $files ) > 0 ) {
				$files = $this->move_fonts_to_font_dir( $files );
				foreach ( $files as $font_id => $file ) {
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
			if ( $this->model->get_font_by_id( $font['id'] ) !== $font && ! $this->model->update_font( $font ) ) {
				throw new GravityPdfDatabaseUpdateException();
			}

			FlushCache::flush();

			return $font;
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
		}
	}

	/**
	 * The capability check, plus the multisite one for unlinking this row's files
	 *
	 * Here rather than in `delete_item()` so that `GPDFAPI::delete_pdf_font()` keeps working the way
	 * `add_pdf_font()` does: the programmatic API calls the handler directly and answers to no user.
	 *
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function delete_item_permissions_check( $request ) {
		$allowed = parent::delete_item_permissions_check( $request );

		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$font = $this->model->get_font( (string) $request['id'] );

		/* No row means no file to unlink, so there is nothing to permit — the handler answers for the id itself */
		if ( $font === [] ) {
			return true;
		}

		return $this->refuse_file_delete( $font ) ?? true;
	}

	/**
	 * @param WP_REST_Request $request Untyped, as `update_item()` above
	 *
	 * @return void|WP_Error
	 *
	 * @since 6.0
	 */
	public function delete_item( $request ) {
		try {
			$id = $request->get_param( 'id' );
			if ( ! $this->model->matches_custom_font_id( $id ) ) {
				throw new GravityPdfIdException();
			}

			/* Removes the rows, then the files no surviving row records */
			if ( ! $this->model->delete_font( $id ) ) {
				throw new GravityPdfDatabaseUpdateException();
			}

			FlushCache::flush();

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
		$storage = new $this->filesystem( $this->font_dir_path );
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

			/* Give file a unique name, if already exists */
			while ( is_file( $this->font_dir_path . $file->getNameWithExtension() ) ) {
				$file->setName( $file->getName() . substr( (string) time(), -5 ) );
				$files[ $id ]['name'] = $file->getNameWithExtension();
			}

			/* Do validation and move to the font directory */
			try {
				$file->upload();
			} catch ( UploadException $e ) {
				$errors[ $id ] = __( 'The upload is not a valid TTF file', 'gravity-pdf' );
			}
		}

		if ( count( $errors ) > 0 ) {
			throw new UploadException( wp_json_encode( $errors ) );
		}

		return $files;
	}

	/**
	 * A validation callback for the REST API
	 *
	 * @since 6.0
	 */
	public function check_empty_string( string $input ): bool {
		return empty( $input );
	}

	/**
	 * Logs the refusal, which the catalogue routes carry no logger to do
	 *
	 * @return true|WP_Error
	 *
	 * @since 6.0
	 */
	public function get_items_permissions_check( $request ) {
		$allowed = parent::get_items_permissions_check( $request );

		if ( is_wp_error( $allowed ) ) {
			$this->log->warning( 'Permission denied: user does not have "gravityforms_edit_forms" capabilities' );
		}

		return $allowed;
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
