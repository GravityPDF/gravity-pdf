<?php

namespace GFPDF\Templates\Config;

use GFPDF\Helper\Helper_Abstract_Config_Settings;
use GFPDF\Helper\Helper_Interface_Config;

/**
 * Blank Slate configuration file
 *
 * This configuration file can be overridden by being placed in the PDF_EXTENDED_TEMPLATES/config/ folder
 *
 * If running a multisite that would be the PDF_EXTENDED_TEMPLATES/:id/config/ folder, where :id is the subsite ID number
 */

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
class Blank_Slate extends Helper_Abstract_Config_Settings implements Helper_Interface_Config {

	/**
	 * Return the templates configuration structure which control what extra fields will be shown in the "Template" tab when configuring a form's PDF.
	 *
	 * The fields key is based on our \GFPDF\Helper\Helper_Abstract_Options Settings API
	 *
	 * See the Helper_Options_Fields::register_settings() method for the exact fields that can be passed in
	 *
	 * @return array The array, split into core components and custom fields
	 * @since 4.0
	 */
	public function configuration() {

		return [

			/* Enable core fields */
			'core' => [
				'show_form_title'      => true,
				'show_page_names'      => true,
				'show_html'            => true,
				'show_section_content' => true,
				'enable_conditional'   => true,
				'show_empty'           => true,
				'header'               => true,
				'first_header'         => true,
				'footer'               => true,
				'first_footer'         => true,
				'background_color'     => true,
				'background_image'     => true,
			],

		];
	}
}
