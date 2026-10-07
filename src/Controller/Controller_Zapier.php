<?php

namespace GFPDF\Controller;

use GFPDF\Model\Model_Shortcodes;

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
 * Class Controller_Zapier
 *
 * @package GFPDF\Controller
 */
class Controller_Zapier {

	/**
	 * @var Model_Shortcodes
	 *
	 * @since 6.17.3
	 */
	protected $shortcodes;

	/**
	 * @param Model_Shortcodes $shortcodes
	 *
	 * @since 6.17.3
	 */
	public function __construct( Model_Shortcodes $shortcodes ) {
		$this->shortcodes = $shortcodes;
	}

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

		$urls = [
			' PDF URL'                  => '',
			' PDF URL - SIGNED 1 WEEK'  => ' signed="1" expires="+1 week"',
			' PDF URL - SIGNED 1 MONTH' => ' signed="1" expires="+1 month"',
			' PDF URL - SIGNED 1 YEAR'  => ' signed="1" expires="+1 year"',
		];

		foreach ( $pdfs as $pdf ) {
			foreach ( $urls as $label => $signed ) {
				$shortcode = sprintf( '[gravitypdf id="%2$s" entry="%1$d" raw="1"%3$s]', $entry['id'], $pdf['id'], $signed );

				$body[ $zapier->get_body_key( $body, $pdf['name'] . $label ) ] = $this->shortcodes->do_trusted_shortcode( $shortcode, $entry['id'] );
			}
		}

		return $body;
	}
}
