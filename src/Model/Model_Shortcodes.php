<?php

namespace GFPDF\Model;

use Exception;
use GFPDF\Exceptions\GravityPdfShortcodeEntryIdException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConditionalLogicFailedException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConfigNotFoundException;
use GFPDF\Exceptions\GravityPdfShortcodePdfInactiveException;
use GFPDF\Helper\Helper_Abstract_Pdf_Shortcode;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all the PDF Shortcode logic
 *
 * @since 4.0
 */
class Model_Shortcodes extends Helper_Abstract_Pdf_Shortcode {

	/**
	 * @since 5.2
	 */
	const SHORTCODE = 'gravitypdf';

	/**
	 * Generates a direct link to the PDF that should be generated
	 * If placed in a confirmation the appropriate entry will be displayed.
	 * A user also has the option to pass in an "entry" parameter to define the entry ID
	 *
	 * @param array $attributes The shortcode attributes specified
	 *
	 * @return string
	 *
	 * @since    4.0
	 *
	 * @internal Deprecated in 5.2. Use self::process()
	 */
	public function gravitypdf( $attributes ) {
		_doing_it_wrong( __METHOD__, __( 'This method has been superseded by self::process()', 'gravity-forms-pdf-extended' ), '5.2' );

		return $this->process( $attributes );
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
	public function process( $attributes ) {
		$controller = $this->getController();

		$shortcode_error_messages_enabled = $this->options->get_option( 'debug_mode', 'No' ) === 'Yes';
		$has_view_permissions             = $shortcode_error_messages_enabled && $this->gform->has_capability( 'gravityforms_view_entries' );

		/* merge in any missing defaults */
		$attributes = shortcode_atts(
			[
				'id'      => '',
				'text'    => 'Download PDF',
				'type'    => 'download',
				'signed'  => '',
				'expires' => '',
				'class'   => 'gravitypdf-download-link',
				'classes' => '',
				'entry'   => '',
				'print'   => '',
				'raw'     => '',
			],
			$attributes,
			static::SHORTCODE
		);

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_gravityforms_shortcode_attributes/ for more information about this filter */
		$attributes = apply_filters( 'gfpdf_gravityforms_shortcode_attributes', $attributes );

		try {
			$attributes['entry'] = $this->get_entry_id_if_empty( $attributes['entry'] );

			/* Do PDF validation */
			$this->get_pdf_config( $attributes['entry'], $attributes['id'] );

			$pdf               = GPDFAPI::get_mvc_class( 'Model_PDF' );
			$download          = $attributes['type'] === 'download';
			$print             = ! empty( $attributes['print'] );
			$raw               = ! empty( $attributes['raw'] );
			$attributes['url'] = $pdf->get_pdf_url( $attributes['id'], $attributes['entry'], $download, $print, ! $raw );

			/* Sign the URL to allow direct access to the PDF until it expires */
			if ( ! empty( $attributes['signed'] ) ) {
				$attributes['url'] = $this->url_signer->sign( $attributes['url'], $attributes['expires'] );
			}

			$this->log->notice( 'Generating Shortcode Markup', [ 'attr' => $attributes ] );

			if ( $raw ) {
				return $attributes['url'];
			}

			return $controller->view->display_gravitypdf_shortcode( $attributes );

		} catch ( GravityPdfShortcodeEntryIdException $e ) {
			return $has_view_permissions ? $controller->view->no_entry_id() : '';
		} catch ( GravityPdfShortcodePdfConfigNotFoundException $e ) {
			return $has_view_permissions ? $controller->view->invalid_pdf_config() : '';
		} catch ( GravityPdfShortcodePdfInactiveException $e ) {
			return $has_view_permissions ? $controller->view->pdf_not_active() : '';
		} catch ( GravityPdfShortcodePdfConditionalLogicFailedException $e ) {
			return $has_view_permissions ? $controller->view->conditional_logic_not_met() : '';
		} catch ( Exception $e ) {
			return $has_view_permissions ? $e->getMessage() : '';
		}
	}
}
