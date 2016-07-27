<?php

namespace GFPDF\Templates\Config;

use GFPDF\Helper\Helper_Interface_Config;

/**
 * Focus Gravity configuration file
 *
 * This configuration file can be overridden by being placed in the PDF_EXTENDED_TEMPLATES/config/ folder
 *
 * If running a multisite that would be the PDF_EXTENDED_TEMPLATES/:id/config/ folder, where :id is the subsite ID number
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 *
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

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
class Focus_Gravity implements Helper_Interface_Config {

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

		return array(

			/* Enable core fields */
			'core'   => array(
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
			),

			/* Create custom fields to control the look and feel of a template */
			'fields' => array(
				'focusgravity_accent_colour' => array(
					'id'   => 'focusgravity_accent_colour',
					'name' => esc_html__( 'Accent Colour', 'gravity-forms-pdf-extended' ),
					'type' => 'color',
					'desc' => esc_html__( 'The accent colour is used for the page and section titles, as well as the border.', 'gravity-forms-pdf-extended' ),
					'std'  => '#e3e3e3',
				),

				'focusgravity_secondary_colour' => array(
					'id'   => 'focusgravity_secondary_colour',
					'name' => esc_html__( 'Secondary Colour', 'gravity-forms-pdf-extended' ),
					'type' => 'color',
					'desc' => esc_html__( 'The secondary colour is used with the field labels and for alternate rows.', 'gravity-forms-pdf-extended' ),
					'std'  => '#eaf2fa',
				),

				'focusgravity_label_format' => array(
					'id'      => 'focusgravity_label_format',
					'name'    => esc_html__( 'Format', 'gravity-forms-pdf-extended' ),
					'type'    => 'radio',
					'desc'    => esc_html__( 'Combine the field label and value or have a distinct label/value.', 'gravity-forms-pdf-extended' ),
					'options' => array(
						'combined_label' => esc_html__( 'Combined Label', 'gravity-forms-pdf-extended' ),
						'split_label'    => esc_html__( 'Split Label', 'gravity-forms-pdf-extended' ),
					),
					'std'     => 'combined_label',
				),
			),
		);
	}
}
