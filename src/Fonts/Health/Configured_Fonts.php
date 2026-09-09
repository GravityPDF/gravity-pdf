<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFAPI;
use GFPDF\Helper\Helper_Abstract_Options;

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
 * Which font keys this site has chosen, and where
 *
 * The expensive half of two health checks — a pass over every form — so it is one object they share rather than
 * two scans, and it is asked for only once something is actually wrong.
 *
 * @package GFPDF\Fonts\Health
 *
 * @since 7.0
 */
class Configured_Fonts {

	/**
	 * The PDF settings that name a font
	 *
	 * @since 7.0
	 */
	public const SETTINGS = [ 'font', 'watermark_font' ];

	/**
	 * @var Helper_Abstract_Options
	 * @since 7.0
	 */
	protected $options;

	/**
	 * @var array<string, string[]>|null
	 * @since 7.0
	 */
	protected $usage;

	public function __construct( Helper_Abstract_Options $options ) {
		$this->options = $options;
	}

	/**
	 * Font key => where it is chosen, in words an admin can act on
	 *
	 * Memoised for the life of the object, which is one health run: two checks asking the same question inside it
	 * must not cost two passes over the forms.
	 *
	 * @return array<string, string[]>
	 *
	 * @since 7.0
	 */
	public function by_key(): array {
		if ( $this->usage !== null ) {
			return $this->usage;
		}

		$this->usage = [];

		$default = (string) $this->options->get_option( 'default_font', '' );

		if ( $default !== '' ) {
			$this->usage[ $default ][] = __( 'The site-wide default font', 'gravity-pdf' );
		}

		foreach ( (array) GFAPI::get_forms() as $form ) {
			$pdfs = $this->options->get_form_pdfs( (int) $form['id'] );

			if ( is_wp_error( $pdfs ) ) {
				continue;
			}

			foreach ( (array) $pdfs as $pdf ) {
				$this->add_pdf( (array) $form, (array) $pdf );
			}
		}

		return $this->usage;
	}

	/**
	 * @since 7.0
	 */
	protected function add_pdf( array $form, array $pdf ): void {
		foreach ( static::SETTINGS as $setting ) {
			$key = $pdf[ $setting ] ?? '';

			if ( ! is_string( $key ) || $key === '' ) {
				continue;
			}

			$this->usage[ $key ][] = sprintf(
				/* translators: 1: form title, 2: PDF name */
				__( '%1$s → %2$s', 'gravity-pdf' ),
				(string) ( $form['title'] ?? '' ),
				(string) ( $pdf['name'] ?? '' )
			);
		}
	}
}
