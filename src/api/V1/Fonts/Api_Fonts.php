<?php

namespace GFPDF\Api\V1\Fonts;

use GFPDF\Api\CallableApiResponse;

/**
 * @package     Gravity PDF Previewer
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF Previewer.

    Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 3 as published
    by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Class ApiFontsEndpoint
 *
 * @package GFPDF\Plugins\GravityPDF\API
 */
class Api_Fonts implements CallableApiResponse {

	/**
	 * Initialise our module
	 *
	 * @since 0.1
	 */
	public function init() {		
		$this->add_actions();
	}

	/**
	 * @since 0.1
	 */
	public function add_actions() {		
		add_action( 'rest_api_init', [ $this, 'register_endpoint' ] );			
	}

	/**
	 * Register our PDF save font endpoint
	 *
	 * @Internal Use this endpoint to save fonts
	 *
	 * @since 5.2
	 */
	public function register_endpoint() {
		register_rest_route(
			'gravity-pdf/v1', /* @TODO - pass `gravity-pdf` portion via __construct() */
			'/fonts/',
			[
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'response' ],

				'permission_callback' => function() {
					return current_user_can( 'read' );
				},
			]
		);
	}

	/**
	 * Register our PDF save font endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 *
	 * @since 5.2
	 */
	private function response( \WP_REST_Request $request ) {

		/* User / CORS validation */
		/* $this->misc->handle_ajax_authentication( 'Save Font', 'gravityforms_edit_settings', 'gfpdf_font_nonce' ); */

		/* Handle the validation and saving of the font */
		$payload = isset( $_POST['payload'] ) ? $_POST['payload'] : '';
		$results = $this->process_font( $payload );

		/* If we reached this point the results were successful so return the new object */
		$this->log->addNotice(
			'AJAX – Successfully Saved Font',
			[
				'results' => $results,
			]
		);

		echo json_encode( $results );
		wp_die();

		return new \WP_Error( 'some_error_code', 'Some error message', [ 'status' => 400 ] );
	}

	/**
	 * Validate user input and save as new font
	 *
	 * @param  array $font The four font fields to be processed
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	private function process_font( $font ) {

		/* remove any empty fields */
		$font = array_filter( $font );

		/* Check we have the required data */
		if ( ! isset( $font['font_name'] ) || ! isset( $font['regular'] ) ||
		     strlen( $font['font_name'] ) === 0 || strlen( $font['regular'] ) === 0
		) {

			$return = [
				'error' => esc_html__( 'Required fields have not been included.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->addWarning( 'Font Validation Failed', $return );

			echo json_encode( $return );

			/* Bad Request */
			wp_die( '', 400 );
		}

		/* Check we have a valid font name */
		$name = $font['font_name'];

		if ( ! $this->is_font_name_valid( $name ) ) {

			$return = [
				'error' => esc_html__( 'Font name is not valid. Only alphanumeric characters and spaces are accepted.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->addWarning( 'Font Validation Failed', $return );

			echo json_encode( $return );

			/* Bad Request */
			wp_die( '', 400 );
		}

		/* Check the font name is unique */
		$shortname = $this->options->get_font_short_name( $name );
		$id        = ( isset( $font['id'] ) ) ? $font['id'] : '';

		if ( ! $this->is_font_name_unique( $shortname, $id ) ) {

			$return = [
				'error' => esc_html__( 'A font with the same name already exists. Try a different name.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->addWarning( 'Font Validation Failed', $return );

			echo json_encode( $return );

			/* Bad Request */
			wp_die( '', 400 );
		}

		/* Move fonts to our Gravity PDF font folder */
		$installation = $this->install_fonts( $font );

		/* Check if any errors occured installing the fonts */
		if ( isset( $installation['errors'] ) ) {

			$return = [
				'error' => $installation,
			];

			$this->log->addWarning( 'Font Validation Failed', $return );

			echo json_encode( $return );

			/* Bad Request */
			wp_die( '', 400 );
		}

		/* If we got here the installation was successful so return the data */
		return $installation;
	}

}
