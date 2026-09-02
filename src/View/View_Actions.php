<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Statics\Deprecation;

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
	 * Load our Core Font Installer
	 *
	 * @param string $type        The action ID
	 * @param string $button_text The primary button text
	 *
	 * @return string              The notice HTML
	 *
	 * @since 5.0
	 */
	public function core_font( $type, $button_text ) {

		$html  = $this->load( 'core_font', [], false );
		$html .= $this->get_action_buttons( $type, $button_text, 'disabled' );

		return $html;
	}

	/**
	 * Load the notice for the deprecated functionality the site still uses
	 *
	 * One notice lists them all, each named the same way so the list scans. That is deliberately the registration's
	 * own sentence rather than the tailored copy the report writes per feature: "These hooks will be removed"
	 * makes sense under a row titled Actions and Filters, and not in a list where nothing else names it. The
	 * detections are left to the report the button links to, since a notice is read at a glance and the template
	 * files or form IDs behind a feature can run long.
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

		$html  = $this->load( 'deprecated_features', [ 'features' => $features ], false );
		$html .= $this->get_action_buttons( $type, $button_text );

		return $html;
	}
}
