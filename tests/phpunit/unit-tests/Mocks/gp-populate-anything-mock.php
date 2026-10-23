<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'GP_Populate_Anything_Live_Merge_Tags' ) ) {
	class GP_Populate_Anything_Live_Merge_Tags {
		public static function get_instance() {
			return new self();
		}

		public function replace_live_merge_tags_static( $text, $form, $entry = null ) {
			return apply_filters( 'gfpdf_mock_gppa_replace_live_merge_tags', $text );
		}
	}
}
