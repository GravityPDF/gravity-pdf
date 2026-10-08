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
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
		_deprecated_function( __METHOD__, '5.2', 'Model_Shortcodes::process()' );

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

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_gravityforms_shortcode_attributes/ for more information about this filter */
		$attributes = apply_filters( 'gfpdf_gravityforms_shortcode_attributes', $attributes );

		try {
			$original_entry_id   = $attributes['entry'];
			$attributes['entry'] = $this->get_entry_id_if_empty( $original_entry_id );

			/* Use up the entry's trust now, so a shortcode that fails validation can't leave it for a later one */
			$trusted = ! empty( $original_entry_id ) && $this->consume_trusted_entry( $attributes['entry'] );

			/* Do PDF validation */
			$settings = $this->get_pdf_config( $attributes['entry'], $attributes['id'] );

			$pdf               = GPDFAPI::get_mvc_class( 'Model_PDF' );
			$download          = $attributes['type'] === 'download';
			$print             = ! empty( $attributes['print'] );
			$raw               = ! empty( $attributes['raw'] );
			$attributes['url'] = $pdf->get_pdf_url( $attributes['id'], $attributes['entry'], $download, $print );

			/* Sign the URL to allow direct access to the PDF until it expires (only for an entry ID set on the shortcode) */
			if ( ! empty( $attributes['signed'] ) && ! empty( $original_entry_id ) && ( $trusted || $this->can_sign_url( $attributes['entry'], $settings ) ) ) {
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

	/**
	 * Only sign an untrusted entry the shortcode's author can view
	 *
	 * @param int   $entry_id
	 * @param array $settings The PDF settings
	 *
	 * @return bool
	 *
	 * @since 6.17.3
	 */
	protected function can_sign_url( $entry_id, $settings ) {
		/* In post content the author is the post's author, not whoever is viewing it */
		$post    = doing_filter( 'the_content' ) ? get_post() : null;
		$user_id = (int) apply_filters( 'gfpdf_shortcode_signing_user_id', $post ? $post->post_author : get_current_user_id(), $entry_id, $settings );

		/** @var Model_PDF $model_pdf */
		$model_pdf = GPDFAPI::get_mvc_class( 'Model_PDF' );

		/* Capabilities don't need the entry, so only load it to check ownership */
		return $model_pdf->can_user_view_pdf_with_capabilities( $user_id ) || $model_pdf->can_user_view_entry( $this->gform->get_entry( $entry_id ), $settings, $user_id );
	}
}
