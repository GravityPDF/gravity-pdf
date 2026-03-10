<?php

namespace GFPDF\Helper;

use Exception;
use GFPDF\Helper\Mpdf\Request;
use GFPDF_Vendor\Mpdf\Config\FontVariables;
use GFPDF\Helper\Mpdf\Mpdf;
use GFPDF_Vendor\Mpdf\MpdfException;
use GFPDF_Vendor\Mpdf\Utils\UtfString;
use GFPDF_Vendor\Mpdf\Container\SimpleContainer;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 4.0
 */
class Helper_PDF {

	/**
	 * Holds our PDF Object
	 *
	 * @var Mpdf
	 *
	 * @since 4.0
	 */
	protected $mpdf;

	/**
	 * Holds our Gravity Form array
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $form;

	/**
	 * Holds our Gravity Form Entry Details
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $entry;

	/**
	 * Holds our PDF Settings
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $settings;

	/**
	 * Controls how the PDF should be output.
	 * Whether to display it in the browser, force a download, or save it to disk
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $output = 'DISPLAY';

	/**
	 * Holds the predetermined paper size
	 *
	 * @var string|array
	 *
	 * @since 4.0
	 */
	protected $paper_size;

	/**
	 * Holds our paper orientation in mPDF flavour
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $orientation;

	/**
	 * Holds the full path to the PHP template to load
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $template_path;

	/**
	 * Holds the PDF filename that should be used
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $filename = 'document.pdf';

	/**
	 * Holds the path the PDF should be saved to
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $path;

	/**
	 * Whether to force the print dialog when the PDF is opened
	 *
	 * @var boolean
	 *
	 * @since 4.0
	 */
	protected $print = false;

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
	 *
	 * @since 4.0
	 */
	protected $templates;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 5.0
	 */
	protected $log;

	/**
	 * Initialise our class
	 *
	 * @param array                $entry    The Gravity Form Entry to be processed
	 * @param array                $settings The Gravity PDF Settings Array
	 *
	 * @param Helper_Abstract_Form $gform
	 * @param Helper_Data          $data
	 * @param Helper_Misc          $misc
	 * @param Helper_Templates     $templates
	 * @param LoggerInterface      $log
	 *
	 * @since 4.0
	 */
	public function __construct( $entry, $settings, Helper_Abstract_Form $gform, Helper_Data $data, Helper_Misc $misc, Helper_Templates $templates, LoggerInterface $log ) {

		/* Assign our internal variables */
		$this->entry     = $entry;
		$this->settings  = $settings;
		$this->gform     = $gform;
		$this->data      = $data;
		$this->misc      = $misc;
		$this->templates = $templates;
		$this->log       = $log;
		$this->form      = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, 'initialize_pdf_class' );

		$this->set_path();
	}

	/**
	 * A public method to start our PDF creation process
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function init() {
		do_action( 'gfpdf_pre_pdf_generation_initilise', $this->mpdf, $this->form, $this->entry, $this->settings, $this );

		$this->set_paper();
		$this->begin_pdf();
		$this->set_creator();
		$this->set_text_direction();
		$this->set_pdf_format();
		$this->set_pdf_security();
		$this->set_display_mode();

		/*
		 * Allow $mpdf object class to be modified after it is fully initialised
		 *
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_mpdf_post_init_class/ for more details about this filter
		 */
		$this->mpdf = apply_filters( 'gfpdf_mpdf_post_init_class', $this->mpdf, $this->form, $this->entry, $this->settings, $this );
	}

	/**
	 * Render the HTML to our PDF
	 *
	 * @param array  $args Any arguments that should be passed to the PDF template
	 * @param string $html By pass the template  file and pass in a HTML string directly to the engine. Optional.
	 *
	 * @return void
	 *
	 * @throws MpdfException
	 * @throws Exception
	 * @since 4.0
	 */
	public function render_html( $args = [], $html = '' ) {

		/* Because this class can load any content we'll only set up our template if no HTML is passed */
		if ( empty( $html ) ) {
			$this->set_template();
		}

		$form = $this->form;

		/* Allow this method to be short circuited */
		if ( apply_filters( 'gfpdf_skip_pdf_html_render', false, $args, $this ) ) {
			do_action( 'gfpdf_skipped_html_render', $args, $this );

			return;
		}

		/* Load in our PHP template */
		if ( empty( $html ) ) {
			$html = $this->load_html( $args );
		}

		/* Apply our filters */
		$html = apply_filters( 'gfpdfe_pdf_template', $html, $form['id'], $this->entry['id'], $args['settings'] ); /* Backwards compat */
		$html = apply_filters( 'gfpdfe_pdf_template_' . $form['id'], $html, $this->entry['id'], $args['settings'] ); /* Backwards compat */

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_html_output/ for more details about these filters */
		$html = apply_filters( 'gfpdf_pdf_html_output', $html, $form, $this->entry, $args['settings'], $this );
		$html = apply_filters( 'gfpdf_pdf_html_output_' . $form['id'], $html, $this->gform, $this->entry, $args['settings'], $this );

		/* Check if we should output the HTML to the browser, for debugging */
		$this->maybe_display_raw_html( $html );

		/* Write the HTML to mPDF */
		$this->mpdf->WriteHTML( $html );
	}

	/**
	 * Create the PDF
	 *
	 * @return string
	 *
	 * @throws MpdfException
	 * @since 4.0
	 */
	public function generate() {

		/* Process any final settings before outputting */
		$this->show_print_dialog();
		$this->set_metadata();

		$form = $this->form;

		/*
		 * Allow $mpdf object class to be modified
		 *
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_mpdf_class/ for more details about this filter
		 */
		$this->mpdf = apply_filters( 'gfpdf_mpdf_class', $this->mpdf, $form, $this->entry, $this->settings, $this );

		/* deprecated backwards compatibility filters */
		$this->mpdf = apply_filters( 'gfpdfe_mpdf_class_pre_render', $this->mpdf, $this->entry['form_id'], $this->entry['id'], $this->settings, '', $this->get_filename() );
		$this->mpdf = apply_filters( 'gfpdfe_pre_render_pdf', $this->mpdf, $this->entry['form_id'], $this->entry['id'], $this->settings, '', $this->get_filename() );
		$this->mpdf = apply_filters( 'gfpdfe_mpdf_class', $this->mpdf, $this->entry['form_id'], $this->entry['id'], $this->settings, '', $this->get_filename() );

		do_action( 'gfpdf_pre_pdf_generation_output', $this->mpdf, $form, $this->entry, $this->settings, $this );

		switch ( $this->output ) {
			case 'DISPLAY':
				$this->pre_stream_actions();
				$this->mpdf->Output( $this->filename, 'I' );
				exit;

			case 'DOWNLOAD':
				$this->pre_stream_actions();
				$this->mpdf->Output( $this->filename, 'D' );
				exit;

			case 'SAVE':
				return $this->mpdf->Output( '', 'S' );
		}

		return false;
	}

	/**
	 * Save the PDF to our tmp directory
	 *
	 * @param string $raw_pdf_string The generated PDF to be saved
	 *
	 * @return string|boolean The full path to the file or false if failed
	 *
	 * @throws Exception
	 *
	 * @since  4.0
	 */
	public function save_pdf( $raw_pdf_string ) {

		/* create our path */
		if ( ! is_dir( $this->path ) ) {
			if ( ! wp_mkdir_p( $this->path ) ) {
				throw new Exception( sprintf( 'Could not create directory: %s', esc_html( $this->path ) ) );
			}
		}

		/* save our PDF */
		if ( ! file_put_contents( $this->path . $this->filename, $raw_pdf_string ) ) {
			throw new Exception( sprintf( 'Could not save PDF: %s', esc_html( $this->path . $this->filename ) ) );
		}

		return $this->path . $this->filename;
	}

	/**
	 * Get the correct path to the PHP template we should load into mPDF
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function set_template() {

		$template = ( isset( $this->settings['template'] ) ) ? $this->settings['template'] : '';

		/* Allow a user to change the current template if they have the appropriate capabilities */
		if ( rgget( 'template' ) && is_user_logged_in() && $this->gform->has_capability( 'gravityforms_edit_forms' ) ) {
			$template = rgget( 'template' );

			/*
			 * Handle legacy v3 URL structure and strip .php from the end of the template
			 */

			/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
			if ( isset( $_GET['gf_pdf'] ) && isset( $_GET['fid'] ) && isset( $_GET['lid'] ) ) {
				$template = substr( $template, 0, -4 );
			}

			$template = sanitize_html_class( $template );
		}

		$this->template_path = $this->templates->get_template_path_by_id( $template );

		/* Check if there are version requirements */
		$template_info = $this->templates->get_template_info_by_path( $this->template_path );
		if ( ! $this->templates->is_template_compatible( $template_info['required_pdf_version'] ) ) {
			throw new Exception( sprintf( esc_html__( 'The PDF Template %1$s requires Gravity PDF version %2$s. Upgrade to the latest version.', 'gravity-pdf' ), '<em>' . esc_html( $template ) . '</em>', '<em>' . esc_html( $template_info['required_pdf_version'] ) . '</em>' ) );
		}
	}

	/**
	 * Gets the current directory template files are being included from.
	 * This is set in the set_template() method
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function get_template_path() {
		return $this->template_path;
	}

	/**
	 * Public endpoint to allow users to control how the generated PDF will be displayed
	 *
	 * @param string $type Only display, download or save options are valid
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function set_output_type( $type ) {
		$valid = [ 'DISPLAY', 'DOWNLOAD', 'SAVE' ];

		if ( ! in_array( strtoupper( $type ), $valid, true ) ) {
			throw new Exception( sprintf( 'Display type not valid. Use %s', esc_html( implode( ', ', $valid ) ) ) );
		}

		$this->output = strtoupper( $type );
	}


	/**
	 * Get the current PDF output type as per the set_output_type() method.
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function get_output_type() {
		return $this->output;
	}

	/**
	 * Set the PDF meta data, including title, author, creator and subject
	 *
	 * @since 4.0
	 */
	protected function set_metadata() {
		$this->mpdf->SetTitle( UtfString::strcode2utf( wp_strip_all_tags( $this->get_filename() ) ) );
		$this->mpdf->SetAuthor( UtfString::strcode2utf( wp_strip_all_tags( get_bloginfo( 'name' ) ) ) );
	}

	/**
	 * Public Method to mark the PDF document creator
	 *
	 * @param string $text The PDF Creator
	 *
	 * @since 4.0
	 */
	public function set_creator( $text = '' ) {
		if ( empty( $text ) ) {
			$this->mpdf->SetCreator( 'Gravity PDF, gravitypdf.com' );
		} else {
			$this->mpdf->SetCreator( $text );
		}
	}

	/**
	 * Public Method to set how the PDF should be displayed when first open
	 *
	 * @param mixed  $mode   A string or integer setting the zoom mode
	 * @param string $layout The PDF layout format
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function set_display_mode( $mode = 'fullpage', $layout = 'continuous' ) {

		$valid_mode   = [ 'fullpage', 'fullwidth', 'real', 'default' ];
		$valid_layout = [ 'single', 'continuous', 'two', 'twoleft', 'tworight', 'default' ];

		/* check the mode */
		if ( ! in_array( strtolower( $mode ), $valid_mode, true ) ) {
			/* determine if the mode is an integer */
			if ( ! is_int( $mode ) || $mode <= 10 ) {
				throw new Exception( sprintf( 'Mode must be an number value more than 10 or one of these types: %s', esc_html( implode( ', ', $valid_mode ) ) ) );
			}
		}

		/* check the layout */
		if ( ! in_array( strtolower( $layout ), $valid_layout, true ) ) {
			throw new Exception( sprintf( 'Layout must be one of these types: %s', esc_html( implode( ', ', $valid_layout ) ) ) );
		}

		$this->mpdf->SetDisplayMode( $mode, $layout );
	}


	/**
	 * Public Method to allow the print dialog to be display when PDF is opened
	 *
	 * @param boolean $show_print_dialog Whether the PDF should open the print dialog every time the PDF is opened
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	public function set_print_dialog( $show_print_dialog = true ) {
		if ( ! is_bool( $show_print_dialog ) ) {
			throw new Exception( 'Only boolean values true and false can been passed to setPrintDialog().' );
		}

		$this->print = $show_print_dialog;
	}

	/**
	 * Generic PDF JS Setter function
	 *
	 * @param string $js The PDF Javascript to execute
	 *
	 * @since 4.0
	 */
	public function set_JS( $js ) {
		$this->mpdf->SetJS( $js );
	}

	/**
	 *
	 * Get the current Gravity Form Entry
	 *
	 * @return array
	 * @since 4.0
	 */
	public function get_entry() {
		return $this->entry;
	}

	/**
	 * Get the current PDF Settings
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get the current PDF Name
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function get_filename() {
		return $this->filename;
	}

	/**
	 * Generate the PDF filename used
	 *
	 * @param string $filename The PDF filename you want to use
	 *
	 * @since 4.0
	 */
	public function set_filename( $filename ) {
		$this->filename = $this->misc->get_file_with_extension( $filename, '.pdf' );
	}

	/**
	 * Get the current PDF path
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Sets the path the PDF should be saved to
	 *
	 * @param string $path
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function set_path( $path = '' ) {

		if ( empty( $path ) ) {
			/* build our PDF path location */
			$path = $this->data->template_tmp_location . $this->entry['form_id'] . $this->entry['id'] . $this->settings['id'] . '/';
		} elseif ( substr( $path, -1 ) !== '/' ) {
			/* ensure the path ends with a forward slash */
			$path .= '/';
		}

		$this->path = $path;
	}

	/**
	 * Gets the absolute path to the PDF
	 *
	 * Works with our legacy Tier 2 add-on without adding a filter because we have stuck with the same naming convention
	 *
	 *
	 * @return string The full path and filename of the PDF
	 *
	 * @since 4.0
	 */
	public function get_full_pdf_path() {
		return $this->get_path() . $this->get_filename();
	}

	/**
	 * Initialise our mPDF object
	 *
	 * @return void
	 *
	 * @throws MpdfException
	 * @since 4.0
	 */
	protected function begin_pdf() {
		$default_font_config = ( new FontVariables() )->getDefaults();

		$this->mpdf = new Mpdf(
			apply_filters(
				'gfpdf_mpdf_class_config',
				[
					'fontDir'                => [ $this->data->template_font_location ],
					'fontdata'               => apply_filters( 'mpdf_font_data', $default_font_config['fontdata'] ),
					'tempDir'                => $this->data->mpdf_tmp_location,

					'allow_output_buffering' => true,
					'autoLangToFont'         => true,
					'useSubstitutions'       => true,
					'ignore_invalid_utf8'    => true,
					'setAutoTopMargin'       => 'stretch',
					'setAutoBottomMargin'    => 'stretch',
					'enableImports'          => true,
					'use_kwt'                => true,
					'keepColumns'            => true,
					'biDirectional'          => true,
					'showWatermarkText'      => true,
					'showWatermarkImage'     => true,

					'format'                 => $this->paper_size,
					'orientation'            => $this->orientation,

					'img_dpi'                => isset( $this->settings['image_dpi'] ) ? (int) $this->settings['image_dpi'] : 96,

					'exposeVersion'          => false,
				],
				$this->form,
				$this->entry,
				$this->settings,
				$this
			),
			new SimpleContainer(
				apply_filters(
					'gfpdf_mpdf_class_container',
					[
						'httpClient' => new Request( WP_DEBUG && WP_DEBUG_DISPLAY ),
					],
					$this->form,
					$this->entry,
					$this->settings,
					$this
				)
			)
		);

		$this->mpdf->setLogger( $this->log );

		/**
		 * Allow $mpdf object class to be modified
		 * Note: in some circumstances using WriteHTML() during this filter will break headers/footers
		 *
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_mpdf_init_class/ for more details about this filter
		 */
		$this->mpdf = apply_filters( 'gfpdf_mpdf_init_class', $this->mpdf, $this->form, $this->entry, $this->settings, $this );
	}

	/**
	 * @return Mpdf
	 */
	public function get_pdf_class() {
		return $this->mpdf;
	}

	/**
	 * Set up the paper size and orientation
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	protected function set_paper() {

		/* Get the paper size from the settings */
		$paper_size = ( isset( $this->settings['pdf_size'] ) ) ? strtoupper( $this->settings['pdf_size'] ) : 'A4';

		$valid_paper_size = [
			'4A0',
			'2A0',
			'A0',
			'A1',
			'A2',
			'A3',
			'A4',
			'A5',
			'A6',
			'A7',
			'A8',
			'A9',
			'A10',
			'B0',
			'B1',
			'B2',
			'B3',
			'B4',
			'B5',
			'B6',
			'B7',
			'B8',
			'B9',
			'B10',
			'C0',
			'C1',
			'C2',
			'C3',
			'C4',
			'C5',
			'C6',
			'C7',
			'C8',
			'C9',
			'C10',
			'RA0',
			'RA1',
			'RA2',
			'RA3',
			'RA4',
			'SRA0',
			'SRA1',
			'SRA2',
			'SRA3',
			'SRA4',
			'LETTER',
			'LEGAL',
			'LEDGER',
			'TABLOID',
			'EXECUTIVE',
			'FOILIO',
			'B',
			'A',
			'DEMY',
			'ROYAL',
			'CUSTOM',
		];

		if ( ! in_array( $paper_size, $valid_paper_size, true ) ) {
			throw new Exception( sprintf( 'Paper size not valid. Use %s', esc_html( implode( ', ', $valid_paper_size ) ) ) );
		}

		/* set our paper size and orientation based on user selection */
		if ( $paper_size === 'CUSTOM' ) {
			$this->set_custom_paper_size();
			$this->set_orientation( true );
		} else {
			$this->set_paper_size( $paper_size );
			$this->set_orientation();
		}
	}

	/**
	 * Set our paper size using pre-defined values
	 *
	 * @param string $size The paper size to be set
	 *
	 * @since 4.0
	 */
	protected function set_paper_size( $size ) {
		$this->paper_size = $size;
	}

	/**
	 * Set our custom paper size which will be a 2-key array signifying the
	 * width and height of the paper stock
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	protected function set_custom_paper_size() {
		$custom_paper_size = ( isset( $this->settings['custom_pdf_size'] ) ) ? $this->settings['custom_pdf_size'] : [];

		if ( count( $custom_paper_size ) !== 3 ) {
			throw new Exception( 'Custom paper size not valid. Array should contain three keys: width, height and unit type' );
		}

		$this->paper_size = $this->get_paper_size( $custom_paper_size );
	}

	/**
	 * Ensure the custom paper size has the correct values
	 *
	 * @param array $size
	 *
	 * @return array
	 *
	 * @since  4.0
	 */
	protected function get_paper_size( $size ) {
		$size[0] = ( $size[2] === 'inches' ) ? (float) $size[0] * 25.4 : (float) $size[0];
		$size[1] = ( $size[2] === 'inches' ) ? (float) $size[1] * 25.4 : (float) $size[1];

		/* tidy up custom paper size array */
		unset( $size[2] );

		return $size;
	}

	/**
	 * Set the page orientation based on the paper size selected
	 *
	 * @param boolean $custom Whether a predefined paper size was used, or a custom size
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function set_orientation( $custom = false ) {

		$orientation = ( isset( $this->settings['orientation'] ) ) ? strtolower( $this->settings['orientation'] ) : 'portrait';

		/**
		 * If using a custom paper size (with an array) we'll pass in the L or P. If standard paper size the -L attribute needs to be added to the $paper_size argument.
		 *
		 * @todo Update mPDF to be more consistent when setting portrait and landscape documentation
		 */
		if ( $custom ) {
			$this->orientation = ( $orientation === 'landscape' ) ? 'L' : 'P';
		} else {
			$this->orientation = ( $orientation === 'landscape' ) ? '-L' : '';
			$this->paper_size .= $this->orientation;
		}
	}

	/**
	 * Load our PHP template file and return the buffered HTML
	 *
	 * @param array $args Any arguments that should be passed to the PDF template file
	 *
	 * @return string The buffered HTML to pass into mPDF
	 *
	 * @since 4.0
	 */
	protected function load_html( $args = [] ) {
		/*
		 * for backwards compatibility extract the $args variable
		 */
		/* phpcs:ignore WordPress.PHP.DontExtract.extract_extract */
		extract( $args, EXTR_SKIP ); /* skip any arguments that would clash - i.e filename, args, output, path, this */

		ob_start();
		include $this->template_path;

		return ob_get_clean();
	}


	/**
	 * Allow site admins to view the RAW HTML if needed
	 *
	 * @param string $html The HTML that should be output to the browser
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function maybe_display_raw_html( $html ) {

		$options = \GPDFAPI::get_options_class();

		/* Disregard if PDF is being saved */
		if ( $this->output === 'SAVE' ) {
			return;
		}

		/* Disregard if `?html` URL parameter doesn't exist */
		if ( ! rgget( 'html' ) ) {
			return;
		}

		/* Disregard if PDF Debug Mode off AND the environment is production */
		if ( $options->get_option( 'debug_mode', 'No' ) === 'No' && ( ! function_exists( 'wp_get_environment_type' ) || wp_get_environment_type() === 'production' ) ) {
			return;
		}

		/* Check if user has permission to view info */
		if ( ! $this->gform->has_capability( 'gravityforms_edit_forms' ) ) {
			return;
		}

		/* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
		echo apply_filters( 'gfpdf_pre_html_browser_output', $html, $this->settings, $this->entry, $this->gform, $this );
		exit;
	}

	/**
	 * Prompt the print dialog box
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function show_print_dialog() {
		if ( $this->print ) {
			$this->mpdf->setJS( 'this.print();' );
		}
	}

	/**
	 * Sets the image DPI in the PDF
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function set_image_dpi() {
		_doing_it_wrong( __METHOD__, esc_html__( 'This method has been removed because mPDF no longer supports setting the image DPI after the class is initialised.', 'gravity-pdf' ), '5.2' );
	}

	/**
	 * Sets the text direction in the PDF (RTL support)
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function set_text_direction() {
		$rtl = ( isset( $this->settings['rtl'] ) ) ? $this->settings['rtl'] : 'No';

		if ( strtolower( $rtl ) === 'yes' ) {
			$this->mpdf->SetDirectionality( 'rtl' );
		}
	}

	/**
	 * Set the correct PDF Format
	 * Normal, PDF/A-1b or PDF/X-1a
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function set_pdf_format() {
		switch ( strtolower( $this->settings['format'] ) ) {
			case 'pdfa1b':
				$this->mpdf->PDFA     = true;
				$this->mpdf->PDFAauto = true;
				break;

			case 'pdfx1a':
				$this->mpdf->PDFX     = true;
				$this->mpdf->PDFXauto = true;
				break;
		}
	}

	/**
	 * Add PDF Security, if able
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	protected function set_pdf_security() {
		/* Security settings cannot be applied to pdfa1b or pdfx1a formats */
		if ( strtolower( $this->settings['format'] ) === 'standard' && strtolower( $this->settings['security'] ) === 'yes' ) {

			$password        = ( isset( $this->settings['password'] ) ) ? wp_specialchars_decode( $this->gform->process_tags( $this->settings['password'], $this->form, $this->entry ), ENT_QUOTES ) : '';
			$privileges      = ( isset( $this->settings['privileges'] ) ) ? $this->settings['privileges'] : [];
			$master_password = ( isset( $this->settings['master_password'] ) ) ? wp_specialchars_decode( $this->gform->process_tags( $this->settings['master_password'], $this->form, $this->entry ), ENT_QUOTES ) : '';

			/* GitHub Issue #662 - Fix issue with possibility of blank master password being set */
			if ( strlen( $master_password ) === 0 ) {
				$master_password = null;
			}

			$this->mpdf->SetProtection( $privileges, $password, $master_password, 128 );
		}
	}


	/**
	 * Actions to run before a PDF is streamed to the browser
	 *
	 * @since 6.13.5
	 */
	protected function pre_stream_actions() {
		/* Close any open buffers to prevent anything from modifying the binary PDF stream */
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		/* Define the PDF as not cacheable, for plugins that support it  */
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		if ( ! headers_sent() ) {
			send_nosniff_header();
		}
	}
}
