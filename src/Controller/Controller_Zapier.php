<?php

namespace GFPDF\Controller;

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
 * Class Controller_Zapier
 *
 * @package GFPDF\Controller
 */
class Controller_Zapier {

	/**
	 * @since 6.3
	 */
	public function init(): void {
		add_filter( 'gform_zapier_request_body', [ $this, 'add_zapier_support' ], 10, 3 );
	}

	/**
	 * Add PDF URLs to the Zapier body array
	 *
	 * @param array $body  An associative array containing the request body that will be sent to Zapier.
	 * @param array $feed  The Feed Object currently being processed.
	 * @param array $entry The Entry Object currently being processed.
	 *
	 * @return array
	 *
	 * @since 6.3
	 */
	public function add_zapier_support( $body, $feed, $entry ) {
		$zapier = \GF_Zapier::get_instance();
		$pdfs   = \GPDFAPI::get_entry_pdfs( $entry['id'] ?? 0 );

		if ( is_wp_error( $pdfs ) ) {
			return $body;
		}

		foreach ( $pdfs as $pdf ) {
			$body[ $zapier->get_body_key( $body, $pdf['name'] . ' PDF URL' ) ]                  = do_shortcode( sprintf( '[gravitypdf id="%2$s" entry="%1$d" raw="1"]', $entry['id'], $pdf['id'] ) );
			$body[ $zapier->get_body_key( $body, $pdf['name'] . ' PDF URL - SIGNED 1 WEEK' ) ]  = do_shortcode( sprintf( '[gravitypdf id="%2$s" entry="%1$d" raw="1" signed="1" expires="+1 week"]', $entry['id'], $pdf['id'] ) );
			$body[ $zapier->get_body_key( $body, $pdf['name'] . ' PDF URL - SIGNED 1 MONTH' ) ] = do_shortcode( sprintf( '[gravitypdf id="%2$s" entry="%1$d" raw="1" signed="1" expires="+1 month"]', $entry['id'], $pdf['id'] ) );
			$body[ $zapier->get_body_key( $body, $pdf['name'] . ' PDF URL - SIGNED 1 YEAR' ) ]  = do_shortcode( sprintf( '[gravitypdf id="%2$s" entry="%1$d" raw="1" signed="1" expires="+1 year"]', $entry['id'], $pdf['id'] ) );
		}

		return $body;
	}
}
