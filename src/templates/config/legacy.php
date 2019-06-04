<?php

namespace GFPDF\Templates\Config;

use GFPDF\Helper\Helper_Interface_Config;

/**
 * Handles our v3 legacy templates configuration (default-template.php, default-template-two-rows.php and default-template-no-style.php)
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The configuration class name should be the same name as the PHP template file name with the following modifications:
 *     The file extension is omitted (.php)
 *     Any hyphens (-) should be replaced with underscores (_)
 *     The class name should be in sentence case (the first character of each word separated by a hyphen (-) or underscore (_) should be uppercase)
 *
 * For instance, a template called core-simple.php or core_simple.php would have a configuration class of "Core_Simple"
 *
 * This naming convention is very important, otherwise the software cannot correctly load the configuration
 *
 * @since 4.0
 */
class Legacy implements Helper_Interface_Config {

	/**
	 * Return the configuration structure.
	 *
	 * The fields key is based on our \GFPDF\Helper\Helper_Abstract_Options Settings API
	 * See the register_settings() method for the exact fields that can be passed in
	 *
	 * @return array The array, split into core components and custom fields
	 *
	 * @since 4.0
	 */
	public function configuration() {
		return [
			'core' => [
				'show_page_names'      => true,
				'show_html'            => true,
				'show_section_content' => true,
				'show_empty'           => true,
			],
		];
	}
}
