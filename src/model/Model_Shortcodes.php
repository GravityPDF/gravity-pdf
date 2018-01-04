<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Misc;

use Psr\Log\LoggerInterface;

use GPDFAPI;
use GFCommon;
use GravityView_View;

/**
 * PDF Shortcode Model
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 *
 * Handles all the PDF Shortcode logic
 *
 * @since 4.0
 */
class Model_Shortcodes extends Helper_Abstract_Model {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var \Monolog\Logger|LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form|\GFPDF\Helper\Helper_Form              $gform   Our abstracted Gravity Forms helper functions
	 * @param \Monolog\Logger|LoggerInterface                                           $log     Our logger class
	 * @param \GFPDF\Helper\Helper_Abstract_Options|\GFPDF\Helper\Helper_Options_Fields $options Our options class which allows us to access any settings
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Abstract_Options $options, Helper_Misc $misc ) {

		/* Assign our internal variables */
		$this->gform   = $gform;
		$this->log     = $log;
		$this->options = $options;
		$this->misc    = $misc;
	}

	/**
	 * Generates a direct link to the PDF that should be generated
	 * If placed in a confirmation the appropriate entry will be displayed.
	 * A user also has the option to pass in an "entry" parameter to define the entry ID
	 *
	 * @param array $attributes The shortcode attributes specified
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function gravitypdf( $attributes ) {
		$controller = $this->getController();

		$global_settings                  = $this->options->get_settings();
		$shortcode_error_messages_enabled = ( isset( $global_settings['shortcode_debug_messages'] ) && $global_settings['shortcode_debug_messages'] === 'Yes' ) ? true : false;
		$has_view_permissions             = ( $shortcode_error_messages_enabled && $this->gform->has_capability( 'gravityforms_view_entries' ) );

		/* merge in any missing defaults */
		$attributes = shortcode_atts( [
			'id'      => '',
			'text'    => 'Download PDF',
			'type'    => 'download',
			'class'   => 'gravitypdf-download-link',
			'classes' => '',
			'entry'   => '',
			'print'   => '',
		], $attributes, 'gravitypdf' );

		/* See https://gravitypdf.com/documentation/v4/gfpdf_gravityforms_shortcode_attributes/ for more information about this filter */
		$attributes = apply_filters( 'gfpdf_gravityforms_shortcode_attributes', $attributes );

		/* Add Shortcake preview support */
		if ( defined( 'SHORTCODE_UI_DOING_PREVIEW' ) && SHORTCODE_UI_DOING_PREVIEW === true ) {
			$attributes['url'] = '#';

			return $controller->view->display_gravitypdf_shortcode( $attributes );
		}

		/* Check if we have an entry ID, otherwise check the GET and POST data */
		if ( empty( $attributes['entry'] ) ) {
			if ( isset( $_GET['lid'] ) || isset( $_GET['entry'] ) ) {
				$attributes['entry'] = ( isset( $_GET['lid'] ) ) ? (int) $_GET['lid'] : (int) $_GET['entry'];
			} else {

				/* Only display error to users with appropriate permissions */
				if ( $has_view_permissions ) {
					return $controller->view->no_entry_id();
				}

				return '';
			}
		}

		/* Check if we have a valid PDF configuration */
		$entry  = $this->gform->get_entry( $attributes['entry'] );
		$config = ( ! is_wp_error( $entry ) ) ? $this->options->get_pdf( $entry['form_id'], $attributes['id'] ) : $entry; /* if invalid entry a WP_Error will be thrown */

		if ( is_wp_error( $config ) ) {

			/* Only display error to users with appropriate permissions */
			if ( $has_view_permissions ) {
				return $controller->view->invalid_pdf_config();
			}

			return '';
		}

		/* Check if the PDF is enabled AND the conditional logic (if any) has been met */
		if ( $config['active'] !== true ) {
			/* Only display error to users with appropriate permissions */
			if ( $has_view_permissions ) {
				return $controller->view->pdf_not_active();
			}

			return '';
		}

		if ( isset( $config['conditionalLogic'] ) && ! $this->misc->evaluate_conditional_logic( $config['conditionalLogic'], $entry ) ) {
			/* Only display error to users with appropriate permissions */
			if ( $has_view_permissions ) {
				return $controller->view->conditional_logic_not_met();
			}

			return '';
		}

		/* Everything looks valid so let's get the URL */
		$pdf               = new Model_PDF( $this->gform, $this->log, $this->options, GPDFAPI::get_data_class(), GPDFAPI::get_misc_class(), GPDFAPI::get_notice_class(), GPDFAPI::get_templates_class() );
		$download          = ( $attributes['type'] == 'download' ) ? true : false;
		$print             = ( ! empty( $attributes['print'] ) ) ? true : false;
		$attributes['url'] = $pdf->get_pdf_url( $attributes['id'], $attributes['entry'], $download, $print );

		/* generate the markup and return */
		$this->log->addNotice( 'Generating Shortcode Markup', [
			'attr' => $attributes,
		] );

		return $controller->view->display_gravitypdf_shortcode( $attributes );
	}

	/**
	 * Update our Gravity Forms "Text" Confirmation Shortcode to include the current entry ID
	 *
	 * @param  string $confirmation The confirmation text
	 * @param  array  $form         The Gravity Form array
	 * @param  array  $entry        The Gravity Form entry information
	 *
	 * @return array               The confirmation text
	 *
	 * @since 4.0
	 */
	public function gravitypdf_confirmation( $confirmation, $form, $entry ) {

		/* check if confirmation is text-based */
		if ( ! is_array( $confirmation ) ) {
			$confirmation = $this->add_entry_id_to_shortcode( $confirmation, $entry['id'] );
		}

		return $confirmation;
	}

	/**
	 * Update our Gravity Forms Notification Shortcode to include the current entry ID
	 *
	 * @param  string $notification The confirmation text
	 * @param  array  $form         The Gravity Form array
	 * @param  array  $entry        The Gravity Form entry information
	 *
	 * @return array               The confirmation text
	 *
	 * @since 4.0
	 */
	public function gravitypdf_notification( $notification, $form, $entry ) {

		/* check if notification has a 'message' */
		if ( isset( $notification['message'] ) ) {
			$notification['message'] = $this->add_entry_id_to_shortcode( $notification['message'], $entry['id'] );
		}

		return $notification;
	}

	/**
	 * Add basic GravityView support and parse the Custom Content field for the [gravitypdf] shortcode
	 * This means users can copy and paste our sample shortcode without having to worry about an entry ID being passed.
	 *
	 * @param string $html
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function gravitypdf_gravityview_custom( $html ) {
		$gravityview_view = GravityView_View::getInstance();
		$entry            = $gravityview_view->getCurrentEntry();

		return $this->add_entry_id_to_shortcode( $html, $entry['id'] );
	}

	/**
	 * Check for the [gravitypdf] shortcode and add the entry ID to it
	 *
	 * @param $string   The text to search
	 * @param $entry_id The entry ID to add to our shortcode
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	private function add_entry_id_to_shortcode( $string, $entry_id ) {
		/* Check if our shortcode exists and add the entry ID if needed */
		$gravitypdf = $this->get_shortcode_information( 'gravitypdf', $string );

		if ( sizeof( $gravitypdf ) > 0 ) {
			foreach ( $gravitypdf as $shortcode ) {
				/* if the user hasn't explicitely defined an entry to display... */
				if ( ! isset( $shortcode['attr']['entry'] ) ) {
					/* get the new shortcode information */
					$new_shortcode = $this->add_shortcode_attr( $shortcode, 'entry', $entry_id );

					/* update our confirmation message */
					$string = str_replace( $shortcode['shortcode'], $new_shortcode['shortcode'], $string );
				}
			}
		}

		return $string;
	}

	/**
	 * Update a shortcode attributes
	 *
	 * @param array  $code  In individual shortcode array pulled in from the $this->get_shortcode_information() function
	 * @param string $attr  The attribute to add / replace
	 * @param string $value The new attribute value
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function add_shortcode_attr( $code, $attr, $value ) {

		/* if the attribute doesn't already exist... */
		if ( ! isset( $code['attr'][ $attr ] ) ) {

			$raw_attr = "{$code['attr_raw']} {$attr}=\"{$value}\"";

			/* if there are no attributes at all we'll need to fix our str replace */
			if ( 0 === strlen( $code['attr_raw'] ) ) {
				$pattern           = '^\[([a-zA-Z]+)';
				$code['shortcode'] = preg_replace( "/$pattern/s", "[$1 {$attr}=\"{$value}\"", $code['shortcode'] );
			} else {
				$code['shortcode'] = str_ireplace( $code['attr_raw'], $raw_attr, $code['shortcode'] );
			}

			$code['attr_raw'] = $raw_attr;

		} else { /* replace the current attribute */
			$pattern           = $attr . '="(.+?)"';
			$code['shortcode'] = preg_replace( "/$pattern/si", $attr . '="' . $value . '"', $code['shortcode'] );
			$code['attr_raw']  = preg_replace( "/$pattern/si", $attr . '="' . $value . '"', $code['attr_raw'] );
		}

		/* Update the actual attribute */
		$code['attr'][ $attr ] = $value;

		return $code;
	}

	/**
	 * Check if user is currently submitting a new confirmation redirect URL in the admin area,
	 * if so replace any shortcodes with a direct link to the PDF (as Gravity Forms correctly validates the URL)
	 *
	 * @param  array $form Gravity Form Array
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function gravitypdf_redirect_confirmation( $form ) {

		/* check if the confirmation is currently being saved */
		if ( isset( $_POST['form_confirmation_url'] ) ) {

			$this->log->addNotice( 'Begin Converting Shortcode to URL for Redirect Confirmation', [
				'form_id' => $form['id'],
				'post'    => $_POST,
			] );

			$url = stripslashes_deep( $_POST['form_confirmation_url'] );

			/* check if our shortcode exists and convert it to a URL */
			$gravitypdf = $this->get_shortcode_information( 'gravitypdf', $url );

			if ( sizeof( $gravitypdf ) > 0 ) {

				foreach ( $gravitypdf as $code ) {

					/* get the PDF Settings ID */
					$pid = ( isset( $code['attr']['id'] ) ) ? $code['attr']['id'] : '';

					if ( ! empty( $pid ) ) {

						/* generate the PDF URL */
						$pdf      = new Model_PDF( $this->gform, $this->log, $this->options, GPDFAPI::get_data_class(), GPDFAPI::get_misc_class(), GPDFAPI::get_notice_class(), GPDFAPI::get_templates_class() );
						$download = ( ! isset( $code['attr']['type'] ) || $code['attr']['type'] == 'download' ) ? true : false;
						$pdf_url  = $pdf->get_pdf_url( $pid, '{entry_id}', $download, false, false );

						/* override the confirmation URL submitted */
						$_POST['form_confirmation_url'] = str_replace( $code['shortcode'], $pdf_url, $url );
					}
				}
			}
		}

		/* it's a filter so return the $form array */

		return $form;
	}

	/**
	 * Search for any shortcodes in the text and return any matches
	 *
	 * @param  string $shortcode The shortcode to search for
	 * @param  string $text      The text to search in
	 *
	 * @return array             The shortcode information
	 *
	 * @since 4.0
	 */
	public function get_shortcode_information( $shortcode, $text ) {
		$shortcodes = [];

		if ( has_shortcode( $text, $shortcode ) ) {
			/* our shortcode exists so parse the shortcode data and return an easy-to-use array */
			preg_match_all( '/' . get_shortcode_regex( [ $shortcode ] ) . '/', $text, $matches, PREG_SET_ORDER );

			if ( empty( $matches ) ) {
				return $shortcodes;
			}

			foreach ( $matches as $item ) {
				if ( $shortcode === $item[2] ) {
					$attr = shortcode_parse_atts( $item[3] );

					$shortcodes[] = [
						'shortcode' => $item[0],
						'attr_raw'  => $item[3],
						'attr'      => ( is_array( $attr ) ) ? $attr : [],
					];
				}
			}
		}

		return $shortcodes;
	}
}
