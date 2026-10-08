<?php

namespace GFPDF\Model;

use Exception;
use GFPDF\Exceptions\GravityPdfShortcodeEntryIdException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConditionalLogicFailedException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConfigNotFoundException;
use GFPDF\Exceptions\GravityPdfShortcodePdfInactiveException;
use GFPDF\Helper\Helper_Abstract_Pdf_Shortcode;
use GFPDF\Helper\Helper_Trait_Removed_Methods;
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

	use Helper_Trait_Removed_Methods;

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
				'token'   => '',
			],
			$attributes,
			static::SHORTCODE
		);

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_gravityforms_shortcode_attributes/ for more information about this filter */
		$attributes = apply_filters( 'gfpdf_gravityforms_shortcode_attributes', $attributes );

		try {
			$original_entry_id   = $attributes['entry'];
			$attributes['entry'] = $this->get_entry_id_if_empty( $original_entry_id );

			/* Do PDF validation */
			$settings = $this->get_pdf_config( $attributes['entry'], $attributes['id'] );

			$pdf               = GPDFAPI::get_mvc_class( 'Model_PDF' );
			$download          = $attributes['type'] === 'download';
			$print             = ! empty( $attributes['print'] );
			$raw               = ! empty( $attributes['raw'] );
			$attributes['url'] = $pdf->get_pdf_url( $attributes['id'], $attributes['entry'], $download, $print );

			/* Sign the URL to allow direct access to the PDF until it expires (only for an entry ID set on the shortcode) */
			if ( ! empty( $attributes['signed'] ) && ! empty( $original_entry_id ) && $this->can_sign_shortcode( $attributes, $settings ) ) {
				$attributes['url'] = $this->url_signer->sign( $attributes['url'], $attributes['expires'] );
			}

			$this->log->notice( 'Generating Shortcode Markup', [ 'attr' => array_diff_key( $attributes, [ 'token' => '' ] ) ] );

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
	 * Whether the shortcode's entry can be signed
	 *
	 * @param array $attributes
	 * @param array $settings   The PDF settings
	 *
	 * @return bool
	 *
	 * @since 6.17.3
	 */
	private function can_sign_shortcode( $attributes, $settings ) {
		if ( $this->get_trust()->is_valid_shortcode_signing_token( $attributes['token'], static::SHORTCODE, $attributes['id'], $attributes['entry'] ) ) {
			return true;
		}

		/* Inside a PDF, only the PDF's own entry signs without a signature */
		if ( $this->get_trust()->is_rendering_pdf() ) {
			return (int) $attributes['entry'] === $this->get_trust()->get_rendering_entry_id();
		}

		return $this->can_sign_url( $attributes['entry'], $settings );
	}

	/**
	 * Without a valid `token`, only sign an entry the shortcode's author can view
	 *
	 * @param int   $entry_id
	 * @param array $settings The PDF settings
	 *
	 * @return bool
	 *
	 * @since 6.17.3
	 */
	private function can_sign_url( $entry_id, $settings ) {
		/* In post content the author is the post's author, not whoever is viewing it */
		$post    = doing_filter( 'the_content' ) ? get_post() : null;
		$user_id = (int) apply_filters( 'gfpdf_shortcode_signing_user_id', $post ? $post->post_author : get_current_user_id(), $entry_id, $settings );

		/** @var Model_PDF $model_pdf */
		$model_pdf = GPDFAPI::get_mvc_class( 'Model_PDF' );

		/* Capabilities don't need the entry, so only load it to check ownership */
		return $model_pdf->can_user_view_pdf_with_capabilities( $user_id ) || $model_pdf->can_user_view_entry( $this->gform->get_entry( $entry_id ), $settings, $user_id );
	}

	/**
	 * @return Model_Signed_Url_Trust
	 *
	 * @since 6.17.3
	 */
	private function get_trust() {
		return GPDFAPI::get_mvc_class( 'Model_Signed_Url_Trust' );
	}
}
