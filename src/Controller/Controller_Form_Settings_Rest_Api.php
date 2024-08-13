<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Options_Fields;
use GFPDF\Helper\Helper_Templates;
use GFPDF\Model\Model_Form_Settings;
use GFPDF\Statics\Kses;
use GPDFAPI;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
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
class Controller_Form_Settings_Rest_Api extends WP_REST_Controller {

	/**
	 * @var string
	 * @since 6.12
	 */
	public const NAMESPACE = Helper_Data::REST_API_BASENAME . 'v1';

	/**
	 * @var string
	 * @since 6.12
	 */
	public const API_BASE = '/form';

	/**
	 * @var string The current template being processed for this request
	 * @since 6.12
	 */
	protected $current_template = '';

	/**
	 * @var string A backup of the original PDF template for this request (if any)
	 * @since 6.12
	 */
	protected $original_request_template = '';

	/**
	 * @var string A backup of the original schema for this request (if any)
	 * @since 6.12
	 */
	protected $original_schema;

	/**
	 * @var Helper_Options_Fields
	 * @since 6.12
	 */
	protected $options;

	/**
	 * @var Helper_Form
	 * @since 6.12
	 */
	protected $gform;

	/**
	 * @var Helper_Misc
	 * @since 6.12
	 */
	protected $misc;

	/**
	 * @var Helper_Templates
	 * @since 6.12
	 */
	protected $templates;

	/**
	 * @param Helper_Options_Fields $options
	 * @param Helper_Form $gform
	 * @param Helper_Misc $misc
	 * @param Helper_Templates $templates
	 *
	 * @since 6.12
	 */
	public function __construct( $options, $gform, $misc, $templates ) {
		$this->options   = $options;
		$this->gform     = $gform;
		$this->misc      = $misc;
		$this->templates = $templates;
	}

	/**
	 * @return void
	 * @since 6.12
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the routes for the API
	 *
	 * @return void
	 * @since 6.12
	 */
	public function register_routes() {

		/* To make full use of schema validation, we need to correctly parse and save the template for the request */
		$this->maybe_assign_template_for_schema();

		/*
		 * Routes to list all form PDF settings, or create a new one
		 */
		register_rest_route(
			static::NAMESPACE,
			static::API_BASE . '/(?P<form>[\d]+)',
			[
				'args'   => [
					'form' => [
						'description'       => __( 'The unique identifier for the Gravity Form.', 'gravity-forms-pdf-extended' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'check_form_is_valid' ],
					],
				],

				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
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

		/*
		 * Routes to handle read/update/delete of individual form PDF settings
		 */
		register_rest_route(
			static::NAMESPACE,
			static::API_BASE . '/(?P<form>[\d]+)/(?P<pdf>[a-fA-F0-9]{13})',
			[
				'args'   => [
					'form' => [
						'description'       => __( 'The unique identifier for the Gravity Form.', 'gravity-forms-pdf-extended' ),
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => [ $this, 'check_form_is_valid' ],
					],

					'pdf'  => [
						'description'       => __( 'The identifier for the PDF', 'gravity-forms-pdf-extended' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => function( $param, $request ) {
							$pdf = GPDFAPI::get_pdf( $request->get_param( 'form' ), $param );

							if ( ! is_wp_error( $pdf ) ) {
								return true;
							}

							$pdfs = GPDFAPI::get_form_pdfs( $request->get_param( 'form' ) );

							/* translators: 1: Parameter, 2: List of valid values. */
							return new WP_Error( 'rest_not_in_enum', wp_sprintf( __( '%1$s is not one of %2$l.', 'default' ), $param, ! is_wp_error( $pdfs ) ? array_keys( $pdfs ) : '' ) );
						},
					],
				],

				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'context' => $this->get_context_param( [ 'default' => 'edit' ] ),
					],
				],

				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				],

				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
				],

				'schema' => [ $this, 'get_public_item_schema' ],
			],
			true
		);
	}

	/**
	 * Check if the form is valid
	 *
	 * @param int $form_id
	 *
	 * @return true|WP_Error
	 *
	 * @since 6.12.0
	 */
	public function check_form_is_valid( $form_id ) {
		if ( is_array( $this->gform->get_form( $form_id ) ) ) {
			return true;
		}

		return new WP_Error(
			'rest_invalid_param',
			sprintf( __( 'Invalid form ID %d provided.', 'gravity-forms-pdf-extended' ), $form_id ),
			[ 'status' => 400 ]
		);
	}

	/**
	 * A helper to re-register the endpoints with a new template schema
	 * This allows for correct validation of requests, and default values
	 *
	 * @param string $template The name of the template
	 *
	 * @return void
	 *
	 * @internal Only use this if manually running REST API requests `new WP_REST_Request()` with a different template each time.
	 * External API requests automatically detect the correct template each request
	 *
	 * @since    6.12.0
	 */
	public function reregister_routes( $template ) {
		$this->set_template( $template );
		$this->register_routes();
	}

	/**
	 * Permissions check for getting all form PDFs
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
	 *
	 * @since 6.12.0
	 */
	public function get_items_permissions_check( $request ) {
		return $this->get_item_permissions_check( $request );
	}

	/**
	 * Retrieves all PDFs for a form
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @since 6.12.0
	 */
	public function get_items( $request ) {
		$pdfs = \GPDFAPI::get_form_pdfs( $request->get_param( 'form' ) );

		if ( is_wp_error( $pdfs ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Form does not exist.', 'gravity-forms-pdf-extended' ),
				[ 'status' => 400 ]
			);
		}

		$output = [];
		foreach ( $pdfs as $pdf ) {
			$this->set_template( $pdf['template'] );
			$request->set_param( 'pdf', $pdf['id'] );

			$output[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $pdf, $request ) );

			$this->restore_template_to_original();
		}

		return rest_ensure_response( $output );
	}

	/**
	 * Checks if a given request has access to read a PDF.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access for the item, otherwise WP_Error object.
	 *
	 * @since 6.12.0
	 */
	public function get_item_permissions_check( $request ) {
		if ( $this->gform->has_capability( 'gravityforms_view_settings' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you do not have access to this endpoint.', 'gravity-forms-pdf-extended' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Retrieves a single PDF
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @since 6.12.0
	 */
	public function get_item( $request ) {
		$pdf = \GPDFAPI::get_pdf( $request->get_param( 'form' ), $request->get_param( 'pdf' ) );
		if ( is_wp_error( $pdf ) ) {
			return $pdf;
		}

		$this->set_template( $pdf['template'] );

		$response = rest_ensure_response( $this->prepare_item_for_response( $pdf, $request ) );

		$this->restore_template_to_original();

		return $response;
	}

	/**
	 * Checks if a given request has access to create PDFs
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 *
	 * @since 6.12.0
	 */
	public function create_item_permissions_check( $request ) {
		if ( $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden_context',
			__( 'Sorry, you do not have access to this endpoint.', 'gravity-forms-pdf-extended' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * Creates a single PDF for a form.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @since 6.12.0
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['pdf'] ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Cannot create existing PDF.', 'gravity-forms-pdf-extended' ),
				[ 'status' => 400 ]
			);
		}

		$form_id = $request['form'];

		$this->set_template( $request['template'] ?? '' );

		$pdf_id = \GPDFAPI::add_pdf( $form_id, $this->prepare_item_for_database( $request ) );
		if ( $pdf_id === false ) {
			return new WP_Error(
				'gfpdf_form_settings_rest_pdf_creation_error',
				__( 'Cannot create PDF.', 'gravity-forms-pdf-extended' ),
				[ 'status' => 500 ]
			);
		}

		$pdf = \GPDFAPI::get_pdf( $form_id, $pdf_id );
		if ( is_wp_error( $pdf ) ) {
			/*
			 * The PDF was successfully created in the last step and should be retrievable.
			 * That it could not be retrieved suggests a database problem, and why the 500
			 * server error.
			 */
			$pdf->add_data( [ 'status' => 500 ] );

			return $pdf;
		}

		do_action( 'gfpdf_form_settings_rest_insert_pdf', $pdf, $request, true );

		$this->update_additional_fields_for_object( $pdf, $request );
		$request->set_param( 'context', 'edit' );

		do_action( 'gfpdf_form_settings_rest_after_insert_pdf', $pdf, $request, true );

		$response = $this->prepare_item_for_response( $pdf, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '%s/%s/%d/%s', static::NAMESPACE, static::API_BASE, $form_id, $pdf_id ) ) );

		$this->restore_template_to_original();

		return $response;
	}

	/**
	 * Checks if a given request has access to update a PDF.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 *
	 * @since 6.12.0
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Updates a single PDF.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @since 6.12.0
	 */
	public function update_item( $request ) {
		$form_id = $request['form'];
		$pdf_id  = $request['pdf'];
		$pdf     = \GPDFAPI::get_pdf( $form_id, $pdf_id );
		if ( is_wp_error( $pdf ) ) {
			$pdf->add_data( [ 'status' => 410 ] );

			return $pdf;
		}

		$id = $pdf['id'];

		$this->set_template( $pdf['template'] );

		$database_pdf = $this->prepare_item_for_database( $request );

		/* Ensure we're operating on the original PDF */
		$database_pdf['id'] = $id;

		$results = \GPDFAPI::update_pdf( $form_id, $pdf_id, $database_pdf );
		if ( ! $results ) {
			return new \WP_Error(
				'gfpdf_form_settings_rest_cannot_update_pdf',
				__( 'The PDF cannot be updated.', 'gravity-forms-pdf-extended' ),
				[ 'status' => 500 ]
			);
		}

		$pdf = \GPDFAPI::get_pdf( $form_id, $pdf_id );

		do_action( 'gfpdf_form_settings_rest_update_pdf', $pdf, $request, false );

		$this->update_additional_fields_for_object( $pdf, $request );
		$request->set_param( 'context', 'edit' );

		do_action( 'gfpdf_form_settings_rest_after_update_pdf', $pdf, $request, false );

		$response = $this->prepare_item_for_response( $pdf, $request );
		$response = rest_ensure_response( $response );

		$this->restore_template_to_original();

		return $response;
	}

	/**
	 * Checks if a given request has access delete a PDF
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 *
	 * @since 6.12.0
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Deletes a single PDF
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @since 6.12.0
	 */
	public function delete_item( $request ) {
		$pdf = \GPDFAPI::get_pdf( $request['form'], $request['pdf'] );
		if ( is_wp_error( $pdf ) ) {
			$pdf->add_data( [ 'status' => 410 ] );

			return $pdf;
		}

		$request->set_param( 'context', 'edit' );
		$this->set_template( $pdf['template'] );

		$previous = $this->prepare_item_for_response( $pdf, $request );

		$this->restore_template_to_original();

		$result = \GPDFAPI::delete_pdf( $request['form'], $request['pdf'] );
		if ( ! $result ) {
			return new \WP_Error(
				'gfpdf_form_settings_rest_cannot_delete_pdf',
				__( 'The PDF cannot be deleted.', 'gravity-forms-pdf-extended' ),
				[ 'status' => 500 ]
			);
		}

		$response = new \WP_REST_Response();
		$response->set_data(
			[
				'deleted'  => true,
				'previous' => $previous->get_data(),
			]
		);

		do_action( 'gfpdf_form_settings_rest_delete_pdf', $pdf, $response, $request );

		return $response;
	}

	/**
	 * Prepares a single PDF output for response.
	 *
	 * @param array            $item    PDF object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response Response object.
	 *
	 * @since 6.12.0
	 */
	public function prepare_item_for_response( $item, $request ) {
		/* Restores the more descriptive, specific name for use within this method (PHP 8 fix). */
		$pdf    = $item;
		$fields = $this->get_fields_for_response( $request );
		$schema = $this->get_item_schema();

		$data = [];

		/* Always include the PDF ID */
		$data['id'] = $pdf['id'];

		/* Dynamically process schema data */
		foreach ( $fields as $id ) {
			/* Skip if $id not found in schema */
			if ( ! isset( $schema['properties'][ $id ] ) ) {
				continue;
			}

			/* Skip if $id is not a requested field */
			if ( ! rest_is_field_included( $id, $fields ) ) {
				continue;
			}

			$property = $schema['properties'][ $id ];
			$value    = $pdf[ $id ] ?? $property['default'] ?? '';

			/* Handle arrays */
			if ( $this->has_property_type( 'array', $property['type'] ) ) {
				$data[ $id ] = is_array( $value ) ? array_values( $value ) : [];

				continue;
			}

			/* Convert integers/numbers into actual int/float values */
			if ( $this->has_property_type( [ 'integer', 'number' ], $property['type'] ) ) {
				$data[ $id ] = is_numeric( $value ) ? +$value : 0;

				continue;
			}

			/* Handle toggle switch */
			if ( $this->has_property_type( 'boolean', $property['type'] ) ) {
				$data[ $id ] = in_array( $value, [ 'Enable', 'Yes', '1', 1, true ], true );

				continue;
			}

			/* Let the field format its response */
			if ( isset( $property['arg_options']['get_callback'] ) ) {
				$value = call_user_func( $property['arg_options']['get_callback'], $item, $id, $request, $this->get_object_type() );
			}

			$data[ $id ] = $value;
		}

		/* Add form ID */
		if ( rest_is_field_included( 'form', $fields ) ) {
			$data['form'] = +$request->get_param( 'form' );
		}

		/* Fix up the order of data to match the schema */
		$reordered = [];
		foreach ( array_keys( $schema['properties'] ) as $property_id ) {
			if ( array_key_exists( $property_id, $data ) ) {
				$reordered[ $property_id ] = $data[ $property_id ];
				unset( $data[ $property_id ] );
			}
		}

		/* Add any extra values not found in the schema */
		$reordered = array_merge( $reordered, $data );

		$data = $reordered;

		$context = ! empty( $request['context'] ) ? $request['context'] : 'edit';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		$data = apply_filters( 'gfpdf_form_settings_rest_prepared_response', $data, $request );

		/* Wrap the data in a response object. */
		$response = rest_ensure_response( $data );

		if ( rest_is_field_included( '_links', $fields ) || rest_is_field_included( '_embedded', $fields ) ) {
			$response->add_links( $this->prepare_links( $request, $data ) );
		}

		return $response;
	}

	/**
	 * Prepares a single PDF for creation or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array PDF settings
	 *
	 * @since 6.12.0
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_pdf = [];

		/* Updating an existing PDF, save to $prepared_pdf */
		if ( isset( $request['pdf'] ) ) {
			$existing_pdf = \GPDFAPI::get_pdf( $request['form'], $request['pdf'] );
			if ( is_wp_error( $existing_pdf ) ) {
				$existing_pdf->add_data( [ 'status' => '410' ] );

				return $existing_pdf;
			}

			$prepared_pdf = $existing_pdf;
		}

		$schema = $this->get_item_schema();

		/* Sanitize fields */
		foreach ( $schema['properties'] as $id => $property ) {
			if ( ! empty( $property['readonly'] ) ) {
				continue;
			}

			/* skip over if updating a PDF and no data is being set */
			if ( isset( $request['pdf'] ) && ! array_key_exists( $id, $request->get_params() ) ) {
				continue;
			}

			$value = $request[ $id ] ?? '';

			/* Handle arrays */
			if ( $this->has_property_type( 'array', $property['type'] ) ) {
				/* if the value isn't an array, set to an empty one */
				if ( ! is_array( $value ) ) {
					$value = [];
				}

				/* if not an empty array, use the same keys as values */
				if ( ! empty( $value ) ) {
					$value = array_combine( $value, $value );
				}
			}

			/* Handle Toggle values */
			if ( $this->has_property_type( 'boolean', $property['type'] ) && ( $property['format'] ?? '' ) === 'yes_no' ) {
				$value = $value === true ? 'Yes' : '';
			}

			$prepared_pdf[ $id ] = $value;
		}

		/* Let fields prepare themselves for the database */
		foreach ( $schema['properties'] as $id => $property ) {
			if ( isset( $property['arg_options']['update_callback'] ) ) {
				$prepared_pdf[ $id ] = call_user_func( $property['arg_options']['update_callback'], $prepared_pdf, $id, $request, $this->get_object_type() );
			}
		}

		return apply_filters( 'gfpdf_form_settings_rest_pre_insert_pdf', $prepared_pdf, $request );
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @return array Collection parameters.
	 *
	 * @since 6.12.0
	 */
	public function get_collection_params() {
		return [
			'context' => $this->get_context_param( [ 'default' => 'edit' ] ),
		];
	}

	/**
	 * Retrieves the PDF schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 *
	 * @since 6.12.0
	 */
	public function get_item_schema() {
		/* returned cached schema + additional fields */
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = [
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'gravity-pdf',
			'description'          => __( 'Individual Form PDF Settings for Gravity PDF.', 'gravity-forms-pdf-extended' ),
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => [
				'id'     => [
					'description' => __( 'Unique identifier for the PDF.', 'gravity-forms-pdf-extended' ),
					'type'        => 'string',
					'context'     => [ 'edit' ],
					'readonly'    => true,
					'pattern'     => '[a-fA-F0-9]{13}',
				],

				'form'   => [
					'description' => __( 'The Gravity Forms ID the PDF is configured on.', 'gravity-forms-pdf-extended' ),
					'type'        => 'integer',
					'context'     => [ 'edit' ],
					'readonly'    => true,
				],

				'active' => [
					'description' => __( 'The current state of the PDF.', 'gravity-forms-pdf-extended' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => [ 'edit' ],
				],
			],
		];

		$all_gravitypdf_settings = $this->options->get_registered_fields();

		$schema['properties'] = array_merge(
			$schema['properties'],
			/* General-specific fields */
			$this->get_section_schema( $all_gravitypdf_settings['form_settings'] ?? [] ),
			/* Appearance-specific fields */
			$this->get_section_schema( $all_gravitypdf_settings['form_settings_appearance'] ?? [] ),
			/* Template-specific fields */
			$this->get_section_schema( $this->get_template_settings( $this->current_template ) ),
			/* Advanced/Security fields */
			$this->get_section_schema( $all_gravitypdf_settings['form_settings_advanced'] ?? [] ),
		);

		$this->schema = apply_filters( 'gfpdf_form_settings_rest_schema', $schema );

		return $this->add_additional_fields_schema( $this->schema );
	}

	/**
	 * Backup the current request's template/schema and set a new legacy template
	 *
	 * @param string $template
	 *
	 * @return void
	 *
	 * @since 6.12.0
	 */
	protected function set_template( string $template ) {
		if ( $template === $this->current_template ) {
			return;
		}

		$this->original_request_template = $this->current_template;
		$this->original_schema           = $this->schema;

		$this->current_template = $template;
		$this->schema           = null;
	}

	/**
	 * Restore the original template/schema for the current request
	 *
	 * @return void
	 *
	 * @since 6.12.0
	 */
	protected function restore_template_to_original() {
		if ( empty( $this->original_request_template ) && empty( $this->original_schema ) ) {
			return;
		}

		$this->current_template = $this->original_request_template;
		$this->schema           = $this->original_schema;
	}

	/**
	 * Set the requested template
	 *
	 * Generating the correct schema relies on knowing ahead of time what template (if any) is required.
	 * The template information could come in the form of a URL parameter, posted data, or JSON, which is
	 * why we need to create a new REST Request to correctly parse all the information.
	 *
	 * @return void
	 *
	 * @since 6.12.0
	 */
	protected function maybe_assign_template_for_schema() {
		if ( ! isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return;
		}

		$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );
		if ( strpos( $route, sprintf( '/%s', static::NAMESPACE . static::API_BASE ) ) !== 0 ) {
			return;
		}

		$this->assign_template_for_schema();
	}

	/**
	 * Save requested PDF template for use in schema validation
	 *
	 * @return Controller_Form_Settings_Rest_Api
	 *
	 * @since 6.12.0
	 */
	protected function assign_template_for_schema() {
		$server  = rest_get_server();
		$request = new \WP_REST_Request( 'POST', '', [ 'template' => [ 'type' => 'string' ] ] );
		$request->set_query_params( wp_unslash( $_GET ) ); /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
		$request->set_body_params( wp_unslash( $_POST ) ); /* phpcs:ignore WordPress.Security.NonceVerification.Missing */
		$request->set_headers( $server->get_headers( wp_unslash( $_SERVER ) ) );
		$request->set_body( \WP_REST_Server::get_raw_data() );
		$request->get_json_params();

		/* Get template from request */
		$template = $request->get_param( 'template' );

		/* Check if template is valid */
		$is_valid = rest_validate_value_from_schema( $template, $this->get_item_schema()['properties']['template'] );
		if ( is_wp_error( $is_valid ) ) {
			unset( $request );

			return $this;
		}

		$this->set_template( $template );

		unset( $request );

		return $this;
	}

	/**
	 * Get the schema for a specific Template PDF
	 *
	 * @param array $settings
	 *
	 * @return array
	 *
	 * @since 6.12.0
	 */
	protected function get_section_schema( $settings ) {
		$generic_description = __( 'Content for the specific property.', 'gravity-forms-pdf-extended' );

		$schema = [];
		foreach ( $settings as $id => $value ) {
			/* Skip fields that should be excluded */
			if ( isset( $value['show_in_rest'] ) && $value['show_in_rest'] === false ) {
				continue;
			}

			/* if schema defined with a different type set it now */
			if ( isset( $value['rest_type'] ) ) {
				$value['type'] = $value['rest_type'];
			}

			$default = $value['std'] ?? null;

			$schema[ $id ] = [
				'description' => ! empty( $value['desc'] ) ? wp_strip_all_tags( $value['desc'] ) : $generic_description,
				'type'        => 'string',
				'default'     => $default,
				'context'     => [ 'edit' ],
				'arg_options' => [
					'sanitize_callback' => function( $param, $request, $key ) {
						return is_array( $param ) ? array_map( 'sanitize_text_field', $param ) : sanitize_text_field( $param );
					},
				],
			];

			switch ( $value['type'] ) {
				case 'number':
					$schema[ $id ]['type'] = 'number';

					if ( isset( $value['min'] ) ) {
						$schema[ $id ]['minimum'] = $value['min'];
					}

					if ( isset( $value['max'] ) ) {
						$schema[ $id ]['maximum'] = $value['max'];
					}

					$schema[ $id ]['arg_options']['sanitize_callback'] = 'rest_sanitize_request_arg';
					break;

				case 'multicheck':
					$schema[ $id ]['type']                             = 'array';
					$schema[ $id ]['items']                            = [
						'type' => 'string',
						'enum' => $this->misc->flatten_array( $value['options'] ?? [] ),
					];
					$schema[ $id ]['arg_options']['sanitize_callback'] = 'rest_sanitize_request_arg';
					break;

				case 'radio':
				case 'select':
					$schema[ $id ]['enum']                             = $this->misc->flatten_array( $value['options'] ?? [] );
					$schema[ $id ]['arg_options']['sanitize_callback'] = 'rest_sanitize_request_arg';
					break;

				case 'textarea':
					$schema[ $id ]['format'] = 'textarea';
					break;

				case 'rich_editor':
					$schema[ $id ]['format']                           = 'rich_text';
					$schema[ $id ]['arg_options']['sanitize_callback'] = function( $param, $request, $key ) {
						return $this->sanitize_rich_text( $param );
					};
					break;

				case 'color':
					$schema[ $id ]['format']                           = 'hex-color';
					$schema[ $id ]['arg_options']['sanitize_callback'] = 'rest_sanitize_request_arg';
					break;

				case 'checkbox':
				case 'toggle':
					$schema[ $id ]['type']                             = 'boolean';
					$schema[ $id ]['format']                           = 'yes_no';
					$schema[ $id ]['default']                          = in_array( $default, [ 'Yes', '1', 'true', true ], true );
					$schema[ $id ]['arg_options']['sanitize_callback'] = 'rest_sanitize_request_arg';

					break;
			}

			/* Mark field as required */
			if ( isset( $value['required'] ) ) {
				$schema[ $id ]['required'] = $value['required'];
			}

			/* Merge any REST API-specific settings, if exists */
			if ( isset( $value['schema'] ) && is_array( $value['schema'] ) ) {
				$schema[ $id ] = array_replace_recursive( $schema[ $id ], $value['schema'] );

				/* don't merge any default array values */
				if ( isset( $value['schema']['default'] ) ) {
					$schema[ $id ]['default'] = $value['schema']['default'];
				}
			}
		}

		return $schema;
	}

	/**
	 * Get the current template's settings (if any)
	 *
	 * @param string $template
	 *
	 * @return array
	 *
	 * @since 6.12.0
	 */
	protected function get_template_settings( string $template ): array {
		$config = $this->templates->get_config_class( $template );

		/** @var Model_Form_Settings $model */
		$model = \GPDFAPI::get_mvc_class( 'Model_Form_Settings' );

		$settings = $model->setup_custom_appearance_settings( $config );

		return $settings;
	}

	/**
	 * @param \WP_REST_Request $request
	 * @param array            $data
	 *
	 * @return array[]
	 *
	 * @since 6.12.0
	 */
	protected function prepare_links( $request, $data ) {
		$links = [
			'self'       => [
				'href' => rest_url( sprintf( '%s/%d/%s', static::NAMESPACE . static::API_BASE, $data['form'], $data['id'] ) ),
			],
			'admin'      => [
				'href' => admin_url( sprintf( 'admin.php?page=gf_edit_forms&view=settings&subview=PDF&id=%d&pid=%s', $data['form'], $data['id'] ) ),
			],
			'collection' => [
				'href' => rest_url( sprintf( '%s/%d', static::NAMESPACE . static::API_BASE, $data['form'] ) ),
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
			'href'       => rest_url( sprintf( 'gf/v2/forms/%d', $data['form'] ) ),
			'embeddable' => true,
		];

		return $links;
	}

	/**
	 * Sanitize Rich Text fields
	 *
	 * So merge tags can be used inside HTML attributes (like src="{tag:20}"), but the rich editor content
	 * can still be sanitized, we'll replace the opening { tag with a valid protocol that isn't very common.
	 * Doing this ensures the merge tag does not get malformed during sanitizing.
	 *
	 * @param string $html
	 *
	 * @return string
	 *
	 * @since 6.12.0
	 */
	protected function sanitize_rich_text( $html ) {
		if ( strpos( $html, 'telnet://{' ) !== false ) {
			return Kses::parse( $html );
		}

		$pattern = '{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}';
		$html    = preg_replace( "/$pattern/mi", 'telnet://$0', $html );
		$html    = Kses::parse( $html );
		$html    = preg_replace( "/telnet:\/\/($pattern)/mi", '$1', $html );

		return $html;
	}

	/**
	 * Check if the schema item is of a specific type
	 *
	 * @param string|array $type
	 * @param string|array  $property
	 *
	 * @return bool
	 */
	protected function has_property_type( $type, $property ) {
		if ( ! is_array( $property ) ) {
			$property = [ $property ];
		}

		if ( ! is_array( $type ) ) {
			$type = [ $type ];
		}

		return count( array_intersect( $property, $type ) ) > 0;
	}
}
