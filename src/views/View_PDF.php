<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Field_Container;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Helper\Helper_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;

use Psr\Log\LoggerInterface;

use GFPDF\Helper\Fields\Field_Products;

use GFFormsModel;
use GFCommon;
use GF_Field;

use mPDF;
use Exception;

/**
 * PDF View
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
 * View_PDF
 *
 * A general class for PDF display
 *
 * @since 4.0
 */
class View_PDF extends Helper_Abstract_View
{

	/**
	 * Set the view's name
	 * @var string
	 * @since 4.0
	 */
	protected $ViewType = 'PDF';

	/**
	 * Holds abstracted functions related to the forms plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $form;

	/**
	 * Holds our log class
	 * @var Object
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 * @var Object
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 * @var Object
	 * @since 4.0
	 */
	protected $plugin_data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * [__construct description]
	 * @param array $data [description]
	 */
	public function __construct( $data = array(), Helper_Abstract_Form $form, LoggerInterface $log, Helper_Options $options, Helper_Data $plugin_data, Helper_Misc $misc ) {
		$this->data = $data;

		/* Assign our internal variables */
		$this->form        = $form;
		$this->log         = $log;
		$this->options     = $options;
		$this->plugin_data = $plugin_data;
		$this->misc        = $misc;
	}

	/**
	 * Our PDF Generator
	 * @param  Array $entry    The Gravity Forms Entry to process
	 * @param  Array $settings The Gravity Form PDF Settings
	 * @return void
	 * @since 4.0
	 */
	public function generate_pdf( $entry, $settings ) {

		$controller = $this->getController();
		$model      = $controller->model;

		/**
		 * Load our arguments that should be accessed by our PDF template
		 * @var array
		 */
		$args = $this->misc->get_template_args( $entry, $settings );

		/**
		 * Show $form_data array if requested
		 */
		if ( isset( $_GET['data'] ) && $this->form->has_capability( 'gravityforms_view_settings' ) ) {
			echo '<pre>';
			print_r( $args['form_data'] );
			echo '</pre>';
			exit;
		}

		/* Enable Multicurrency support */
		$this->misc->maybe_add_multicurrency_support();

		/**
		 * Set out our PDF abstraction class
		 */
		$pdf = new Helper_PDF( $entry, $settings, $this->form, $this->plugin_data );
		$pdf->set_filename( $model->get_pdf_name( $settings, $entry ) );

		try {
			$pdf->init();
			
			/* set display type */
			if ( $settings['pdf_action'] == 'download' ) {
				$pdf->set_output_type( 'download' );
			}

			$pdf->render_html( $args );
			$this->options->increment_pdf_count();

			/* Generate PDF */
			$pdf->generate();

		} catch (Exception $e) {

			$this->log->addError( 'PDF Generation Error', array(
				'entry'     => $entry,
				'settings'  => $settings,
				'exception' => $e,
			) );

			if( $this->form->has_capability( 'gravityforms_view_entries' ) ) {
				wp_die( $e->getMessage() );
			}
			
			wp_die( __( 'There was a problem generating your PDF', 'gravitypdf' ) );
		}
	}


	/**
	 * Ensure a PHP extension is added to the end of the template name
	 * @param  String $name The PHP template
	 * @return String
	 * @since  4.0
	 */
	public function get_template_filename( $name ) {
		if ( substr( $name, -4 ) !== '.php' ) {
			$name = $name . '.php';
		}

		return $name;
	}

	/**
	 * Start the PDF HTML Generation Process
	 * @param  Array $entry  The Gravity Forms Entry Array
	 * @param  Array $config Any configuration data passed in
	 * @return String         The generated HTML
	 * @since 4.0
	 */
	public function process_html_structure( $entry, Helper_Abstract_Model $model, $config = array() ) {
		/* Determine whether we should output or return the results */
		$config['meta'] = (isset($config['meta'])) ? $config['meta'] : array();
		$echo           = (rgar( $config, 'echo' )) ? rgar( $config, 'echo' ) : true; /* whether to output or return the generated markup. Default is echo */

		if ( ! $echo ) {
			ob_start();
		}

		/* Generate the markup */
		?>

        <div id="container">
            <?php $this->generate_html_structure( $entry, $model, $config ); ?>
        </div>

        <?php

		if ( ! $echo ) {
			return ob_get_clean();
		}
	}

	/**
	 * Build our HTML structure
	 * @param  Array $entry  The Gravity Forms Entry Array
	 * @param  Array $config Any configuration data passed in
	 * @return String         The generated HTML
	 * @since 4.0
	 */
	public function generate_html_structure( $entry, Helper_Abstract_Model $model, $config = array() ) {
		
		/* Set up required variables */
		$form                           = $this->form->get_form( $entry['form_id'] );
		$products                       = new Field_Products( new GF_Field(), $entry, $this->form, $this->misc );
		$has_products                   = false;
		$page_number                    = 0;
		$container                      = new Helper_Field_Container();

		/* Allow the config to be changed through a filter */
		$config['meta']                 = (isset($config['meta'])) ? $config['meta'] : array();
		$config                         = apply_filters( 'gfpdf_pdf_configuration', $config, $entry, $form );

		/* Get the user configuration values */
		$skip_marked_fields             = (rgar( $config['meta'], 'exclude' )) ? rgar( $config['meta'], 'exclude' ) : true; /* whether we should exclude fields with a CSS value of 'exclude'. Default to true */
		$skip_hidden_fields             = (rgar( $config['meta'], 'hidden' )) ? rgar( $config['meta'], 'hidden' ) : true; /* whether we should skip fields hidden with conditional logic. Default to true. */
		$show_title                     = (rgar( $config['meta'], 'show_title' )) ? rgar( $config['meta'], 'show_title' ) : true; /* whether we should show the form title. Default to true */
		$show_page_names                = (rgar( $config['meta'], 'page_names' )) ? rgar( $config['meta'], 'page_names' ) : false; /* whether we should show the form's page names. Default to false */
		$show_html_fields               = (rgar( $config['meta'], 'html_field' )) ? rgar( $config['meta'], 'html_field' ) : false; /* whether we should show the form's html fields. Default to false */
		$show_individual_product_fields = (rgar( $config['meta'], 'individual_products' )) ? rgar( $config['meta'], 'individual_products' ) : false; /* Whether to show individual fields in the entry. Default to false - they are grouped together at the end of the form */

		/* Display the form title, if needed */
		$this->show_form_title( $show_title, $form );

		/* Loop through the fields and output or skip if needed */
		foreach ( $form['fields'] as $key => $field ) {

			/* Load our page name, if needed */
			if ( $show_page_names === true && $field->pageNumber !== $page_number ) {
				$this->display_page_name( $page_number, $form );
				$page_number++;
			}

			/* Skip any fields with the css class 'exclude', if needed */
			if ( $skip_marked_fields !== false && strpos( $field->cssClass, 'exclude' ) ) {
				continue;
			}

			/* Skip over any hidden fields (usually by conditional logic), if needed */
			if ( $skip_hidden_fields === true && GFFormsModel::is_field_hidden( $form, $field, array(), $entry ) ) {
				continue;
			}

			/* Skip over any product fields, if needed */
			if ( $show_individual_product_fields === false && GFCommon::is_product_field( $field->type ) ) {
				$has_products = true;
				continue;
			}

			/* Skip HTML fields, if needed */
			if ( $show_html_fields === false && $field->type == 'html' ) {
				continue;
			}

			/**
			 * Let's output our field
			 */
			$this->process_field( $field, $entry, $form, $config, $products, $container, $model );
		}

		/* correctly close / cleanup the HTML container if needed */
		$container->close();

		/* Output product table, if needed */
		if ( $has_products && ! $products->is_empty() ) {
			echo $products->html();
		}

	}

	/**
	 * Handle our field loader and display the generated HTML
	 * @param  GF_Field               $field    The field to process
	 * @param  Array                  $entry    The Gravity Form Entry
	 * @param  Array                  $form     The Gravity Form Field
	 * @param  Array                  $config   The user-passed configuration data
	 * @param  Object                 $products A Field_Products Object
	 * @param  Helper_Field_Container $container
	 * @return void
	 * @since 4.0
	 */
	public function process_field( GF_Field $field, $entry, $form, $config, Field_Products $products, Helper_Field_Container $container, Helper_Abstract_Model $model ) {
		
		/*
        * Set up our configuration variables
        */
		$config['meta']           = (isset($config['meta'])) ? $config['meta'] : array();
		$show_empty_fields        = (rgar( $config['meta'], 'empty' )) ? rgar( $config['meta'], 'empty' ) : false; /* whether to show empty fields or not. Default is false */
		$load_legacy_css          = (rgar( $config['meta'], 'legacy_css' )) ? rgar( $config['meta'], 'legacy_css' ) : false; /* whether we should add our legacy field class names (v3.x.x) to our fields. Default to false */
		$show_section_description = (rgar( $config['meta'], 'section_content' )) ? rgar( $config['meta'], 'section_content' ) : false; /* whether we should include a section breaks content. Default to false */

		$class = $model->get_field_class( $field, $form, $entry, $products );

		/* Try and display our HTML */
		try {
			/* Only load our HTML if the field is NOT empty, or the $empty config option is true */
			if ( ! $class->is_empty() || $show_empty_fields === true ) {
				/* Load our legacy CSS class names */
				if ( $load_legacy_css === true ) {
					$this->load_legacy_css( $field );
				}

				/**
				 * Add CSS Ready Class Float Support to mPDF
				 * Open a HTML container if needed
				 */
				$container->generate( $field );

				echo ($field->type !== 'section') ? $class->html() : $class->html( $show_section_description );
			} else {
				/**
				 * Close our CSS Ready Class Row, if open
				 */
				$container->close();
			}
		} catch (Exception $e) {
			$this->log->addError( 'PDF Generation Error', array(
				'field'     => $field,
				'entry'     => $entry,
				'config'    => $config,
				'form'      => $form,
				'exception' => $e,
			) );
		}
	}

	/**
	 * If enabled, we'll show the Gravity Form Title in the document
	 * @param  Boolean $show_title Whether or not to show the title
	 * @param  Array   $form       The Gravity Form array
	 * @return void
	 * @since 4.0
	 */
	public function show_form_title( $show_title, $form ) {
		/* Show the form title, if needed */
		if ( $show_title !== false ) : ob_start(); ?>
            <h3 id="form_title"><?php echo $form['title']?></h3>
        <?php endif;

		echo apply_filters( 'gfpdf_pdf_form_title_html', ob_get_clean(), $form );
	}


	/**
	 * Our default template used a number of legacy classes.
	 * To keep backwards compatible, we will manually assign when needed.
	 * @param  GF_Field $field The Gravity Form Fields
	 * @return void (classes are passed by reference)
	 * @since 4.0
	 */
	public function load_legacy_css( $field ) {
		static $counter = 1;

		/* Add odd / even rows */
		$field->cssClass = ($counter++ % 2) ? $field->cssClass . ' odd' : ' even';

		switch ( $field->type ) {
			case 'html':
				$field->cssClass = $field->cssClass . ' entry-view-html-value';
			break;

			case 'section':
				$field->cssClass = $field->cssClass . ' entry-view-section-break-content';
			break;

			default:
				$field->cssClass = $field->cssClass . ' entry-view-field-value';
			break;
		}
	}

	/**
	 * Output the current page name HTML
	 * @param  Integer $page  The current page number
	 * @param  Array   $form  The form array
	 * @return String           The page HTML output
	 */
	public function display_page_name( $page, $form ) {
		/* Only display the current page name if it has changed (and it exists) */
		if ( isset($form['pagination']['pages'][$page]) && strlen( trim( $form['pagination']['pages'][$page] ) ) > 0 ) {
			ob_start();
			?>
                <h3 id="field-<?php echo $field->id; ?>" class="gfpdf-<?php echo $field->inputType; ?> gfpdf-field <?php echo $field->cssClass; ?>">
                    <?php echo $form['pagination']['pages'][$page]; ?>
                </h3>
            <?php
			echo apply_filters( 'gfpdf_field_page_name_html', ob_get_clean(), $page, $field, $form );
		}
	}
}
