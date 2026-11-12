<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Model\Model_System_Report;
use GFPDF\Statics\Deprecation;
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
 * Controls the Gravity PDF Actions Display
 *
 * @since 4.0
 */
class View_Actions extends Helper_Abstract_View {
	/**
	 * The docs on installing the core fonts by hand
	 *
	 * @since 6.19.0
	 */
	public const CORE_FONTS_DOCS_URL = 'https://docs.gravitypdf.com/users/core-pdf-fonts';

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_type = 'Actions';

	/**
	 * Add our primary button and an opt-our dismissal button
	 *
	 * @param string $type        The action ID
	 * @param string $button_text The primary button text
	 * @param string $dismissal   Whether the dismissal button should be shown. Valid arguments are 'enabled' or 'disabled'
	 *
	 * @return string              The action_buttons HTML
	 *
	 * @since 4.0
	 */
	public function get_action_buttons( $type, $button_text, $dismissal = 'enabled' ) {

		return $this->load(
			'action_buttons',
			[
				'type'        => $type,
				'button_text' => $button_text,
				'dismissal'   => $dismissal,
			],
			false
		);
	}

	/**
	 * Load the notice for the deprecated functionality the site still uses
	 *
	 * One notice lists them all, each named the same way so the list scans. That is deliberately the registration's
	 * own sentence rather than the tailored copy the report writes per feature, which is written to be read under
	 * the row that titles it and not in a list where nothing else names the feature. The detections are left to the
	 * report the button links to, since a notice is read at a glance and the template files or form IDs behind a
	 * feature can run long.
	 *
	 * @param string[] $keys        The feature IDs from Deprecation::get_features()
	 * @param string   $type        The action ID
	 * @param string   $button_text The primary button text
	 *
	 * @return string The notice HTML
	 * @since 6.17.0
	 */
	public function deprecated_features( array $keys, string $type, string $button_text ): string {
		$features = [];

		foreach ( $keys as $key ) {
			$feature = Deprecation::get_feature( $key );

			$features[] = [
				'notice' => Deprecation::get_feature_notice( $feature ),
				'url'    => $feature['url'],
			];
		}

		$html = $this->load(
			'deprecated_features',
			[
				'features'    => $features,
				'unsupported' => Deprecation::has_unsupported_feature( $keys ),
			],
			false
		);

		$html .= $this->get_action_buttons( $type, $button_text );

		return $html;
	}

	/**
	 * Load the notice for the folders Gravity PDF can't write to
	 *
	 * @param string[] $folders     The folders' absolute paths
	 * @param string   $type        The action ID
	 * @param string   $button_text The primary button text
	 *
	 * @return string The notice HTML
	 * @since 6.18.0
	 */
	public function unwritable_folders( array $folders, string $type, string $button_text ): string {
		$misc = GPDFAPI::get_misc_class();

		$html = $this->load(
			'list_notice',
			[
				'heading' => __( 'Gravity PDF cannot create or write to these directories. Contact your web hosting provider to fix the issue.', 'gravity-pdf' ),
				'items'   => array_map( [ $misc, 'relative_path' ], $folders ),
				'footer'  => esc_html__( 'Dismissing this notice hides the directories above. Any found later are reported again.', 'gravity-pdf' ),
			],
			false
		);

		return $html . $this->get_action_buttons( $type, $button_text );
	}

	/**
	 * The notice listing the core fonts that couldn't be downloaded
	 *
	 * @param string[] $files       The font file names
	 * @param string   $type        The action ID
	 * @param string   $button_text The primary button text
	 *
	 * @since 6.19.0
	 */
	public function failed_core_fonts( array $files, string $type, string $button_text ): string {
		$html = $this->load(
			'list_notice',
			[
				'heading' => __( 'Gravity PDF could not download these fonts.', 'gravity-pdf' ) . ' ' . self::get_missing_core_fonts_warning(),
				'items'   => $files,
				'footer'  => sprintf(
					/* translators: 1: Opening <a> tag, 2: Closing </a> tag, 3: Opening <a> tag */
					esc_html__( 'You can %1$sinstall the fonts by hand%2$s, or %3$sview the system report%2$s. Dismissing this notice hides the fonts above. Any that fail later are reported again.', 'gravity-pdf' ),
					'<a href="' . self::CORE_FONTS_DOCS_URL . '">',
					'</a>',
					'<a href="' . esc_url( Model_System_Report::get_report_url( Model_System_Report::FONTS_FAILED_ANCHOR ) ) . '">'
				),
			],
			false
		);

		return $html . $this->get_action_buttons( $type, $button_text );
	}

	/**
	 * What missing core fonts mean for a PDF
	 *
	 * @since 6.19.0
	 */
	public static function get_missing_core_fonts_warning(): string {
		return __( 'Until the fonts are installed, PDFs that need them use a fallback font and some characters may not display.', 'gravity-pdf' );
	}

	/**
	 * A bulleted list of code items. Bullets are set per-item: Gravity Forms resets `ul li` to none, and
	 * wp_kses_post() drops the `list-style` shorthand
	 *
	 * @param string[] $items
	 *
	 * @since 6.19.0
	 */
	public static function get_code_list( array $items ): string {
		$html = '';
		foreach ( $items as $item ) {
			$html .= '<li style="list-style-type: disc"><code>' . esc_html( $item ) . '</code></li>';
		}

		return '<ul style="margin: 0.5em 0 0.5em 2em">' . $html . '</ul>';
	}
}
