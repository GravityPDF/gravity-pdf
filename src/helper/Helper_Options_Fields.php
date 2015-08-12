<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Helper_Options; /* not needed, but helps define usage */

/**
 * Our Gravity PDF Options API Field Registration
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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

/* Exit if accessed directly */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Class to set up the settings api fields
 *
 * @since 4.0
 */
class Helper_Options_Fields extends Helper_Options {
	
	/**
	 * Retrieve the array of registered fields
	 *
	 * @since 4.0
	 * @return array
	*/
	public function get_registered_fields() {

		/**
		 * 'Whitelisted' Gravity PDF settings, filters are provided for each settings
		 * section to allow extensions and other plugins to add their own settings
		 */
		$gfpdf_settings = array(
			/** General Settings */
			'general' => apply_filters( 'gfpdf_settings_general',
				array(
					'default_pdf_size' => array(
						'id'         => 'default_pdf_size',
						'name'       => __('Default Paper Size', 'gravitypdf'),
						'desc'       => __('Set the default paper size used when generating PDFs.', 'gravitypdf'),
						'type'       => 'select',
						'options'    => $this->get_paper_size(),
						'inputClass' => 'large',
						'chosen'     => true,
						'class'      => 'gfpdf_paper_size',
					),

					'default_custom_pdf_size' => array(
						'id'       => 'default_custom_pdf_size',
						'name'     => __('Custom Paper Size', 'gravitypdf'),
						'desc'     => __('Control the exact paper size. Can be set in millimeters or inches.', 'gravitypdf'),
						'type'     => 'paper_size',
						'size'     => 'small',
						'chosen'   => true,
						'required' => true,
						'class'    => 'gfpdf-hidden gfpdf_paper_size_other',
					),

					'default_template' => array(
						'id'         => 'default_template',
						'name'       => __('Default Template', 'gravitypdf'),
						'desc'       => __('Set the default PDF template to use for all forms.', 'gravitypdf'),
						'type'       => 'select',
						'options'    => $this->get_templates(),
						'inputClass' => 'large',
						'chosen'     => true,
					),

					'default_font_type' => array(
						'id'         => 'default_font_type',
						'name'       => __('Default Font Type', 'gravitypdf'),
						'desc'       => __('Set the default font type used in the PDFs.', 'gravitypdf'),
						'type'       => 'select',
						'options'    => $this->get_installed_fonts(),
						'inputClass' => 'large',
						'chosen'     => true,
					),

					'default_font_colour' => array(
						'id'      => 'default_font_colour',
						'name'    => __('Default Font Colour', 'gravitypdf'),
						'type'    => 'color',
						'std'     => '#000000',
						'desc'    => __('Set the default font colour used in the PDFs.', 'gravitypdf'),
					),

					'default_rtl' => array(
						'id'      => 'default_rtl',
						'name'    => __('Reverse Text (RTL)', 'gravitypdf'),
						'desc'    => __('Languages like Arabic and Hebrew are written right to left.', 'gravitypdf'),
						'type'    => 'radio',
						'options' => array(
							'Yes'     => __('Yes', 'gravitypdf'),
							'No'      => __('No', 'gravitypdf')
						),
						'std'     => __('No', 'gravitypdf'),
					),

					'default_action' => array(
						'id'      => 'default_action',
						'name'    => __('Entry View', 'gravitypdf'),
						'desc'    => sprintf(__('Select the default action used when accessing a PDF from the %sGravity Forms entries list%s page.', 'gravitypdf'), '<a href="'. admin_url('admin.php?page=gf_entries') . '">', '</a>'),
						'type'    => 'radio',
						'options' => array(
							'View'     => __('View', 'gravitypdf'),
							'Download' => __('Download', 'gravitypdf'),
						),
						'std'     => 'View',
					),
				)
			),

			'general_security' => apply_filters( 'gfpdf_settings_general_security',
				array(
					'admin_capabilities' => array(
						'id'          => 'admin_capabilities',
						'name'        => __('User Restriction', 'gravitypdf'),
						'desc'        => __('Restrict PDF access to logged in users with any of these capabilities. The Administrator Role always has full access.', 'gravitypdf'),
						'type'        => 'select',
						'options'     => $this->get_capabilities(),
						'std'         => 'gravityforms_view_entries',
						'inputClass'  => 'large',
						'chosen'      => true,
						'multiple'    => true,
						'required'    => true,
						'placeholder' => __('Select Capability', 'gravitypdf'),
						'tooltip'     => '<h6>' . __('User Restriction', 'gravitypdf') . '</h6>' . __("Only logged in users with these capabilities can view generated PDFs they don't have ownership of. Ownership refers to the user who completed the original Gravity Form entry.", 'gravitypdf'),
					),

					'limit_to_admin' => array(
						'id'      => 'limit_to_admin',
						'name'    => __('Restrict Owner', 'gravitypdf'),
						'desc'    => __("When enabled, the original entry owner will NOT be able to view their PDF.", 'gravitypdf'),
						'type'    => 'radio',
						'options' => array(
							'Yes'     => __('Yes', 'gravitypdf'),
							'No'      => __('No', 'gravitypdf')
						),
						'std'     => __('No', 'gravitypdf'),
						'tooltip' => '<h6>' . __('Restrict Owner', 'gravitypdf') . '</h6>' . __("Enable this option if you only want logged in users with the above capability to view PDFs.", 'gravitypdf'),
					),

					'logged_out_timeout' => array(
						'id'      => 'logged_out_timeout',
						'name'    => __('Logged Out Timeout', 'gravitypdf'),
						'desc'    => __('Limit how long a <em>logged out</em> users has direct access to the PDF after completing the form. Set to 0 to disable time limit (not recommended).', 'gravitypdf'),
						'desc2'   => __('minutes', 'gravitypdf'),
						'type'    => 'number',
						'size'    => 'small',
						'std'     => 20,
						'tooltip' => '<h6>' . __('Logged Out Timeout', 'gravitypdf') . '</h6>' . __("By default, logged out users can view PDFs when their IP matches the IP assigned to the Gravity Form entry. But because IP addresses can change frequently a time-based restriction also applies.", 'gravitypdf'),
					),
				)
			),

			
			/** Extension Settings */
			'extensions' 	=> apply_filters('gfpdf_settings_extensions',
				array()
			),
			'licenses' 		=> apply_filters('gfpdf_settings_licenses',
				array()
			),

			'tools' 		=> apply_filters('gfpdf_settings_tools',
				array(
					'setup_templates' => array(
						'id'      => 'setup_templates',
						'name'    => __('Setup Custom Templates', 'gravitypdf'),
						'desc'    => sprintf(__("Ready to get down and dirty with custom PDF templates? %sSee docs to get started%s.", 'gravitypdf'), '<a href="#">', '</a>'),
						'type'    => 'button',
						'std'     => __('Run Setup', 'gravitypdf'),
						'options' => 'copy',
						'tooltip' => '<h6>' . __('Setup Custom Templates', 'gravitypdf') . '</h6>' . __('TODO... Write Copy', 'gravitypdf'),
					),

					'manage_fonts' => array(
						'id'      => 'manage_fonts',
						'name'    => __('Fonts', 'gravitypdf'),
						'desc'    => __("Add, update or remove custom fonts.", 'gravitypdf'),
						'type'    => 'button',
						'std'     => __('Manage Fonts', 'gravitypdf'),
						'options' => 'install_fonts',
						'tooltip' => '<h6>' . __('Install Fonts', 'gravitypdf') . '</h6>' . sprintf(__("Custom fonts can be installed and used in your PDFs. Currently only %s.ttf%s and %s.otf%s font files are supported. Once installed, fonts can be used in your custom PDF templates with a CSS %sfont-family%s declaration.", 'gravitypdf'), '<code>', '</code>', '<code>', '</code>', '<code>', '</code>'),
					),
				)
			),

			/* Form (PDF) Settings */
			'form_settings' => apply_filters('gfpdf_form_settings',
				array(

					'name' => array(
						'id'       => 'name',
						'name'     => __('Name', 'gravitypdf'),
						'type'     => 'text',
						'required' => true,
						'tooltip'  => '<h6>' . __('Name', 'gravitypdf') . '</h6>' . __('The internal PDF name, making it easy to distinguish between multiple PDFs.', 'gravitypdf'),
					),

					'template' => array(
						'id'         => 'template',
						'name'       => __('Template', 'gravitypdf'),
						'desc'       =>  sprintf(__('Choose an existing template or purchased more %sfrom our theme shop%s.%s You can also %sbuild your own%s or %shire us%s to create a custom solution.', 'gravitypdf'), '<a href="#">', '</a>', '<br>', '<a href="#">', '</a>', '<a href="#">', '</a>'),
						'type'       => 'select',
						'options'    => $this->get_templates(),
						'inputClass' => 'large',
						'chosen'     => true,
						'tooltip'    => '<h6>' . __('Templates', 'gravitypdf') . '</h6>' . sprintf( __('Gravity PDF comes with %sfive completely-free and highly customisable designs%s to choose. You can also purchase additional templates from our theme shop, hire us to integrate existing PDFs or, with a bit of technical know-how, build your own.', 'gravitypdf'), '<strong>', '</strong>' ),
					),

					'notification' => array(
						'id'                 => 'notification',
						'name'               => __('Notifications', 'gravitypdf'),
						'desc'               => __('Automatically attach PDF to the selected notifications.', 'gravitypdf'),
						'type'               => 'select',
						'options'            => array(),
						'inputClass'         => 'large',
						'chosen'             => true,
						'multiple'           => true,
						'placeholder'        => __('Choose a Notification', 'gravitypdf'),
						'tooltip'  => '<h6>' . __('Notifications', 'gravitypdf') . '</h6>' . __('Automatically generate and attach the PDF to your selected notifications. Conditional Logic for both the PDF and the Notification applies.', 'gravitypdf'),
					),

					'filename' => array(
						'id'         => 'filename',
						'name'       => __('Filename', 'gravitypdf'),
						'type'       => 'text',
						'desc'       => 'The name used when saving a PDF. Mergetags are allowed.',
						'tooltip'    => '<h6>' . __('Filename', 'gravitypdf') . '</h6>' . __('Set an appropriate filename for the generated PDF. You should exclude the .pdf extension from the name.', 'gravitypdf'),
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
						'required'   => true,
					),

					'conditional' => array(
						'id'         => 'conditional',
						'name'       => __('Conditional Logic', 'gravitypdf'),
						'type'       => 'conditional_logic',
						'desc'       => __('Enable conditional logic', 'gravitypdf'),
						'class'      => 'conditional_logic',
						'inputClass' => 'conditional_logic_listener',
						'tooltip'    => '<h6>' . __('Conditional Logic', 'gravitypdf') . '</h6>' . __('Create rules to dynamically enable or disable PDFs. This includes attaching to notifications and viewing.', 'gravitypdf'),
					),

					'conditionalLogic' => array(
						'id'      => 'conditionalLogic',
						'type'    => 'hidden',
						'class'   => 'gfpdf-hidden',
					),
				
				)
			),

			/* Form (PDF) Settings Appearance */
			'form_settings_appearance' => apply_filters('gfpdf_form_settings_appearance',
				array(
					'pdf_size' => array(
						'id'      => 'pdf_size',
						'name'    => __('Paper Size', 'gravitypdf'),
						'desc'    => __('Set the paper size used when generating PDFs.', 'gravitypdf'),
						'type'    => 'select',
						'options' => $this->get_paper_size(),
						'std'     => $this->get_option('default_pdf_size', 'A4'),
						'inputClass'   => 'large',
						'class' => 'gfpdf_paper_size',
						'chosen'  => true,
					),

					'custom_pdf_size' => array(
						'id'      => 'custom_pdf_size',
						'name'    => __('Custom Paper Size', 'gravitypdf'),
						'desc'    => __('Control the exact paper size. Can be set in millimeters or inches.', 'gravitypdf'),
						'type'    => 'paper_size',
						'size'    => 'small',
						'chosen'  => true,
						'required' => true,
						'class'   => 'gfpdf-hidden gfpdf_paper_size_other',
						'std'     => $this->get_option('default_custom_pdf_size'),
					),

					'orientation' => array(
						'id'      => 'orientation',
						'name'    => __('Orientation', 'gravitypdf'),
						'type'    => 'select',
						'options' => array(
							'portrait' => __('Portrait', 'gravitypdf'),
							'landscape' => __('Landscape', 'gravitypdf'),
						),
						'inputClass'   => 'large',
						'chosen'  => true,
					),

					'font' => array(
						'id'      => 'font',
						'name'    => __('Font', 'gravitypdf'),
						'type'    => 'select',
						'options' => $this->get_installed_fonts(),
						'std'     => $this->get_option('default_font_type'),
						'desc'    => __('Set the font type to use in the PDF.', 'gravitypdf'),
						'inputClass'   => 'large',
						'chosen'  => true,
					),

					'font_colour' => array(
						'id'      => 'font_colour',
						'name'    => __('Font Colour', 'gravitypdf'),
						'type'    => 'color',
						'std'     => $this->get_option('default_font_colour'),
						'desc'    => __('Set the font colour used in the PDF.', 'gravitypdf'),
					),

					'rtl' => array(
						'id'    => 'rtl',
						'name'    => __('Reverse Text (RTL)', 'gravitypdf'),
						'desc'  => __('Languages like Arabic and Hebrew are written right to left.', 'gravitypdf'),
						'type'  => 'radio',
						'options' => array(
							'Yes' => __('Yes', 'gravitypdf'),
							'No'  => __('No', 'gravitypdf')
						),
						'std'   => $this->get_option('default_rtl', 'No'),
					),
															
				)
			),

			/**
			 * Form (PDF) Settings Custom Appearance
			 * This filter allows templates to add custom options for use specific to that template
			 * Gravity PDF autoloads a PHP template file if it exists and loads it up with this filter
			 */
			'form_settings_custom_appearance' => apply_filters('gfpdf_form_settings_custom_appearance', array()),

			/* Form (PDF) Settings Advanced */
			'form_settings_advanced' => apply_filters('gfpdf_form_settings_advanced',
				array(
					'format' => array(
						'id'    => 'format',
						'name'  => __('Format', 'gravitypdf'),
						'desc'  => __('Generate a PDF in the selected format.', 'gravitypdf'),
						'type'  => 'radio',
						'options' => array(
							'Standard' => 'Standard',
							'PDFA1B'  => 'PDF/A-1b',
							'PDFX1A'  => 'PDF/X-1a',
						),
						'std'   => 'Standard',
						'tooltip' => '<h6>' . __('PDF Format', 'gravitypdf') . '</h6>' . __("Generate a document adhearing to the appropriate PDF standard. When not in 'Standard' mode, watermarks, alpha-transparent PNGs and security options can NOT be used.", 'gravitypdf'),
					),

					'security' => array(
						'id'      => 'security',
						'name'    => __('Enable PDF Security', 'gravitypdf'),
						'desc'    => __('Password protect generated PDFs, or restrict user capabilities.', 'gravitypdf'),
						'type'    => 'radio',
						'options' => array(
							'Yes' => __('Yes', 'gravitypdf'),
							'No'  => __('No', 'gravitypdf')
						),
						'std'     => __('No', 'gravitypdf'),
					),

					'password' => array(
						'id'    => 'password',
						'name'  => __('Password', 'gravitypdf'),
						'type'  => 'text',
						'desc'  => 'Set a password to view PDFs. Leave blank to disable password protection.',
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
					),

					'privileges' => array(
						'id'      => 'privileges',
						'name'    => __('Privileges', 'gravitypdf'),
						'desc'    => 'Restrict end-user capabilities.',
						'type'    => 'select',
						'options' => $this->get_privilages(),
						'std'     => array(
							'copy',
							'print',
							'print-highres',
							'modify',
							'annot-forms',
							'fill-forms',
							'extract',
							'assemble',
						),
						'inputClass'       => 'large',
						'chosen'      => true,
						'tooltip'     => '<h6>' . __('Privileges', 'gravitypdf') . '</h6>' . __("You can prevent the end-user completing certain actions to the PDF, such as copying text, printing, adding annotations or extracting pages.", 'gravitypdf'),
						'multiple'    => true,
						'placeholder' => __('Select PDF Privileges', 'gravitypdf'),
					),

					'image_dpi' => array(
						'id'    => 'image_dpi',
						'name'  => __('Image DPI', 'gravitypdf'),
						'type'  => 'number',
						'size'  => 'small',
						'std'   => 96,
						'tooltip' => '<h6>' . __('Image DPI', 'gravitypdf') . '</h6>' . __("Control the image DPI (dots per inch). Set to 300 when professionally printing.", 'gravitypdf'),
					),

					'save' => array(
						'id'    => 'save',
						'name'  => __('Always Save PDF?', 'gravitypdf'),
						'desc'  => __('Force a PDF to be saved to disk when a new entry is submitted.', 'gravitypdf'),
						'type'  => 'radio',
						'options' => array(
							'Yes' => __('Yes', 'gravitypdf'),
							'No'  => __('No', 'gravitypdf')
						),
						'std'   => __('No', 'gravitypdf'),
						'tooltip' => '<h6>' . __('Save PDF', 'gravitypdf') . '</h6>' . __("When notifications are disabled a PDF is not automatically saved to disk. Enable this option to force the PDF to be generated and saved. Useful when using the 'gfpdf_post_pdf_save' hook to copy / manipulate the PDF further.", 'gravitypdf'),
					),

				)
			),
		);

		return apply_filters( 'gfpdf_registered_settings', $gfpdf_settings );
	}
}