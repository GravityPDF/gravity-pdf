<?php

/* For backwards compatibility reasons this file will be in the global namespace */
use GFPDF\Router;
use GFPDF\Model\Model_PDF;
use GFPDF\View\View_PDF;
use GFPDF\Helper\Fields\Field_Products;
use GFPDF\Helper\Fields\Field_Likert;

/**
 * Depreciated Functionality / Classes
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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
 * Add's an easy depreciated class abstract fallback
 * @since 4.0
 */
abstract class GFPDF_Depreciated_Abstract
{
	/**
	 * Add user depreciation notice for missing methods
	 * @since  4.0
	 */
	public function __call( $name, $arguments ) {
		trigger_error( sprintf( __( '"%s" has been depreciated as of Gravity PDF 4.0', 'gravitypdf' ), $name ), E_USER_DEPRECATED );
	}

	/**
	 * Add user depreciation notice for missing methods
	 * @since  4.0
	 */
	public static function __callStatic( $name, $arguments ) {
		trigger_error( sprintf( __( '"%s" has been depreciated as of Gravity PDF 4.0', 'gravitypdf' ), $name ), E_USER_DEPRECATED );
	}
}

/**
 * Add backwards compatibility support for our main core class
 * @since 3.0
 */
class GFPDF_Core extends GFPDF_Depreciated_Abstract
{
	public function __construct() {
		global $gfpdf;

		$gfpdf = new Router();

		$gfpdf->init();
		$this->setup_constants();
	}

	public function setup_constants() {
		global $gfpdf;

		define( 'PDF_SAVE_LOCATION', $gfpdf->data->template_tmp_location );
		define( 'PDF_FONT_LOCATION', $gfpdf->data->template_font_location );
		define( 'PDF_TEMPLATE_LOCATION', $gfpdf->data->template_location );
		define( 'PDF_TEMPLATE_URL_LOCATION', $gfpdf->data->template_location );
	}
}

/**
 * Add backwards compatibility support for our PDF generator
 * @since 3.0
 */
class PDFRender extends GFPDF_Depreciated_Abstract
{
	/**
	 * Saves the PDF to disk
	 * @param String  $pdf
	 * @param String  $filename
	 * @param Integer $id
	 * @since 3.0
	 */
	public function savePDF( $pdf, $filename, $id ) {
		echo 'todo'; exit;
	}

	/**
	 * Handles backwards compatibility support for our Tier 2 add on
	 * @return void
	 * @since 4.0
	 */
	public function prepare_ids( $form_id, $lead_id, $template, $id, $output, $filename, $arguments, $args) {
		global $lead_ids;
		$lead_ids = $args['lead_ids'];

		return $form_id;
	}
}


/**
 * Add backwards compatibility support for our common class
 * @since 3.0
 */
class PDF_Common extends GFPDF_Depreciated_Abstract
{

	/**
	 * Takes over for setup_ids() but is now called much earlier in the process
	 * @return Boolean
	 * @since 4.0
	 */
	public static function get_ids() {
		global $form_id, $lead_id, $lead_ids;

		$form_id  = ($form_id) ? $form_id : absint( rgget( 'fid' ) );
		$lead_ids = ($lead_id) ? array( $lead_id ) : explode( ',', rgget( 'lid' ) );

		/* If form ID and lead ID hasn't been set stop the PDF from attempting to generate */
		if ( empty($form_id) || empty($lead_ids) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the base upload directory details
	 * @return Array
	 * @since 3.0
	 */
	public static function get_upload_dir() {
		global $gfpdf;

		return $gfpdf->misc->get_upload_details();
	}

	/**
	 * Convert merge tags to real Gravity Form values
	 * @param  String  $string
	 * @param  Integer $form_id
	 * @param  Integer $lead_id
	 * @return String
	 * @since 3.0
	 */
	public static function do_mergetags( $string, $form_id, $lead_id ) {
		global $gfpdf;

		return $gfpdf->misc->do_mergetags( $string, $gfpdf->form->get_form( $form_id ), $gfpdf->form->get_entry( $lead_id ) );
	}

	/**
	 * Allow users to view the $form_data array, if it exists
	 * @param String $form_data
	 * @since 4.0
	 */
	public static function view_data( $form_data ) {

		if ( isset( $_GET['data'] ) && $this->form->has_capability( 'gravityforms_view_settings' ) ) {
			print '<pre>';
			print_r( $form_data );
			print '</pre>';
			exit;
		}
	}

	/**
	 * Get $_POST key, or return nothing
	 * @param  String $name Key Name
	 * @return String
	 * @since 3.0
	 */
	public static function post( $name ) {
		if ( isset( $_POST[$name] ) ) {
			return $_POST[$name];
		}

		return '';
	}

	/**
	 * Get $_GET key, or return nothing
	 * @param  String $name Key Name
	 * @return String
	 * @since 3.0
	 */
	public static function get( $name ) {
		if ( isset( $_GET[$name] ) ) {
			return $_GET[$name];
		}

		return '';
	}

	/**
	 * Get the name of the PDF based on the Form and the submission
	 * @param  $form_id Integer
	 * @param  $lead_id Integer
	 * @return String
	 * @since 3.0
	 */
	public static function get_pdf_filename( $form_id, $lead_id ) {
		return "form-$form_id-entry-$lead_id.pdf";
	}

	/**
	 * Remove any characters that are invalid in filenames (mostly on Windows systems)
	 * @param  String $name The string / name to process
	 * @return String
	 * @since 4.0
	 */
	public static function remove_invalid_characters( $name ) {
		global $gfpdf;

		return $gfpdf->meta->strip_invalid_characters( $name );
	}
}

/**
 * Add depreciated functionality for generating our standard PDF HTML
 * @since 3.0
 */
class GFPDFEntryDetail extends GFPDF_Depreciated_Abstract {

	/**
	 * Generate our PDF HTML layout
	 * @param  Array   $form   The Gravity Form array
	 * @param  Array   $lead   The Gravity Form entry
	 * @param  boolean $allow_display_empty_fields
	 * @param  boolean $show_html
	 * @param  boolean $show_page_name
	 * @param  boolean $return
	 * @return void
	 * @since 3.0
	 */
	public static function lead_detail_grid( $form, $lead, $allow_display_empty_fields = false, $show_html = false, $show_page_name = false, $return = false ) {
			$config = array(
				'meta' => array(
					'empty'      => $allow_display_empty_fields,
					'echo'       => ! $return,
					'legacy_css' => true,
					'html_field' => $show_html,
					'page_names' => $show_page_name,
				),
			);

			self::do_lead_detail_grid( $form, $lead, $config );
	}

	/**
	 * Generate our PDF HTML layout
	 * @param  Array $form   The Gravity Form array
	 * @param  Array $lead   The Gravity Form entry
	 * @param  Array $config The PDF Configuration
	 * @return String        The generated HTML
	 * @since 3.7
	 */
	public static function do_lead_detail_grid( $form, $lead, $config = array() ) {
		global $gfpdf;

		/* Convert old config values to our new ones */
		if( ! isset( $config['meta'] ) ) {

			$convert = array(
				'empty_field' => 'empty',
				'return'      => 'echo',
			);

			foreach( $convert as $key => $val ) {
				if( isset( $config[ $key ] ) ) {
					$config[ $val ] = $config[ $key ];
					unset( $config[ $key ] );
				}
			}

			$config = array( 'meta' => $config );
		}

		/* Set up any legacy configuration options needed */
		$config['meta']['legacy_css'] = true;
		
		$model = new Model_PDF( $gfpdf->form, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices );
		$view  = new View_PDF( array(), $gfpdf->form, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc );
		$view->process_html_structure( $lead, $model, $config );
	}

	/**
	 * Get the $form_data array
	 * @param  Array $form   The Gravity Form array
	 * @param  Array $lead   The Gravity Form entry
	 * @return Array
	 * @since 3.0
	 */
	public static function lead_detail_grid_array( $form, $lead ) {
		global $gfpdf;

		$model = new Model_PDF( $gfpdf->form, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices );
		return $model->get_form_data( $lead );
	}

	/**
	 * Generate a standard Gravity Forms product table based on the form / entry data
	 * @param  Array $form   The Gravity Form array
	 * @param  Array $lead   The Gravity Form entry
	 * @return void
	 * @since 3.0
	 */
	public static function product_table( $form, $lead ) {
		global $gfpdf;

		$products = new Field_Products( new GF_Field(), $lead, $gfpdf->form, $gfpdf->misc );
		echo $products->html();
	}

	/**
	 * Public method for outputting likert (survey addon field)
	 * @param  Array $form   The Gravity Form array
	 * @param  Array $lead   The Gravity Form entry
	 * @param  Array $field_id The field ID to output
	 * @return void
	 * @since 3.0
	 */
	public static function get_likert( $form, $lead, $field_id ) {
		global $gfpdf;

		/* Find our field ID, if any */
		foreach ( $form['fields'] as $field ) {
			if ( $field->id == $field_id ) {

				/* Output our likert */
				$likert = new Field_Likert( $field, $lead, $gfpdf->form, $gfpdf->misc );
				echo $likert->html();
				return;
			}
		}
	}
}

class GFPDF_Core_Model extends GFPDF_Depreciated_Abstract { }
class GFPDF_Settings_Model extends GFPDF_Depreciated_Abstract { }
class GFPDF_Settings extends GFPDF_Depreciated_Abstract { }
