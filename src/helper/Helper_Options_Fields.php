<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Helper_Options; /* not needed, but helps define usage */
use GFPDF\Helper\Helper_Interface_Filters;

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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class to set up the settings api fields
 *
 * @since 4.0
 */
class Helper_Options_Fields extends Helper_Options implements Helper_Interface_Filters {

	public function add_filters() {

		/* Conditionally enable specific fields */
		add_filter( 'gfpdf_form_settings_advanced', array( $this, 'get_public_access_field' ) );
		add_filter( 'gfpdf_form_settings_advanced', array( $this, 'get_advanced_template_field' ) );

		parent::add_filters();
	}

	/**
	 * Retrieve the array of registered fields
	 *
	 * @since 4.0
	 * @return array
	 */
	public function get_registered_fields() {

		/**
		 * Gravity PDF settings
		 * Filters are provided for each settings section to allow extensions and other plugins to add their own option
		 * which will be processed by our settings API
		 */
		$gfpdf_settings = array(

			/** General Settings */
			'general' => apply_filters( 'gfpdf_settings_general',
				array(
					'default_pdf_size' => array(
						'id'         => 'default_pdf_size',
						'name'       => __( 'Default Paper Size', 'gravitypdf' ),
						'desc'       => __( 'Set the default paper size used when generating PDFs.', 'gravitypdf' ),
						'type'       => 'select',
						'options'    => $this->get_paper_size(),
						'inputClass' => 'large',
						'chosen'     => true,
						'class'      => 'gfpdf_paper_size',
					),

					'default_custom_pdf_size' => array(
						'id'       => 'default_custom_pdf_size',
						'name'     => __( 'Custom Paper Size', 'gravitypdf' ),
						'desc'     => __( 'Control the exact paper size. Can be set in millimeters or inches.', 'gravitypdf' ),
						'type'     => 'paper_size',
						'size'     => 'small',
						'chosen'   => true,
						'required' => true,
						'class'    => 'gfpdf-hidden gfpdf_paper_size_other',
					),

					'default_template' => array(
						'id'         => 'default_template',
						'name'       => __( 'Default Template', 'gravitypdf' ),
						'desc'       => sprintf( __( 'Choose an existing template or purchased more %sfrom our theme shop%s. You can also %sbuild your own%s or %shire us%s to create a custom solution.', 'gravitypdf' ), '<a href="#">', '</a>', '<a href="#">', '</a>', '<a href="#">', '</a>' ),
						'type'       => 'select',
						'options'    => $this->get_templates(),
						'inputClass' => 'large',
						'chosen'     => true,
						'tooltip'    => '<h6>' . __( 'Templates', 'gravitypdf' ) . '</h6>' . sprintf( __( 'Gravity PDF comes with %sfive completely-free and highly customisable designs%s to choose. You can also purchase additional templates from our theme shop, hire us to integrate existing PDFs or, with a bit of technical know-how, build your own.', 'gravitypdf' ), '<strong>', '</strong>' ),
					),

					'default_font_type' => array(
						'id'         => 'default_font_type',
						'name'       => __( 'Default Font Type', 'gravitypdf' ),
						'desc'       => sprintf( __( 'Set the default font type used in PDFs. Choose an existing font or %sinstall your own%s.', 'gravitypdf' ), '<a href="'. $this->data->settings_url .'&tab=tools#manage_fonts">', '</a>' ),
						'type'       => 'select',
						'options'    => $this->get_installed_fonts(),
						'inputClass' => 'large',
						'chosen'     => true,
						'tooltip'    => '<h6>' . __( 'Fonts', 'gravitypdf' ) . '</h6>' . __( 'Gravity PDF comes bundled with fonts for most languages world-wide. Want to use a specific font type? Use the font installer (found in the Tools tab).', 'gravitypdf' ),
					),

					'default_font_size' => array(
						'id'      => 'default_font_size',
						'name'    => __( 'Default Font Size', 'gravitypdf' ),
						'desc'    => __( 'Set the default font size used in PDFs.', 'gravitypdf' ),
						'desc2'   => 'pt',
						'type'    => 'number',
						'size'    => 'small',
						'std'     => 12,
					),

					'default_font_colour' => array(
						'id'      => 'default_font_colour',
						'name'    => __( 'Default Font Colour', 'gravitypdf' ),
						'type'    => 'color',
						'std'     => '#000000',
						'desc'    => __( 'Set the default font colour used in PDFs.', 'gravitypdf' ),
					),

					'default_rtl' => array(
						'id'      => 'default_rtl',
						'name'    => __( 'Reverse Text (RTL)', 'gravitypdf' ),
						'desc'    => __( 'Script like Arabic and Hebrew are written right to left.', 'gravitypdf' ),
						'type'    => 'radio',
						'options' => array(
							'Yes'     => __( 'Yes', 'gravitypdf' ),
							'No'      => __( 'No', 'gravitypdf' ),
						),
						'std'     => __( 'No', 'gravitypdf' ),
						'tooltip'    => '<h6>' . __( 'Reverse Text (RTL)', 'gravitypdf' ) . '</h6>' . __( "Enable RTL if you are writing in Arabic, Hebrew, Syriac, N'ko, Thaana, Tifinar or Urdu.", 'gravitypdf' ),
					),

					'default_action' => array(
						'id'      => 'default_action',
						'name'    => __( 'Entry View', 'gravitypdf' ),
						'desc'    => sprintf( __( 'Select the default action used when accessing a PDF from the %sGravity Forms entries list%s page.', 'gravitypdf' ), '<a href="'. admin_url( 'admin.php?page=gf_entries' ) . '">', '</a>' ),
						'type'    => 'radio',
						'options' => array(
							'View'     => __( 'View', 'gravitypdf' ),
							'Download' => __( 'Download', 'gravitypdf' ),
						),
						'std'     => 'View',
						'tooltip'    => '<h6>' . __( 'Entry View', 'gravitypdf' ) . '</h6>' . __( 'Choose to view the PDF in your web browser or download the document to your computer.', 'gravitypdf' ),
					),

					'update_screen_action' => array(
						'id'      => 'update_screen_action',
						'name'    => __( "Show What's New?", 'gravitypdf' ),
						'desc'    => "When updating to a new release we'll redirect you to our What's New page.",
						'type'    => 'radio',
						'options' => array(
							'Enable'     => __( 'Enable', 'gravitypdf' ),
							'Disable' => __( 'Disable', 'gravitypdf' ),
						),
						'std'     => 'Enable',
						'tooltip'    => '<h6>' . __( "Show What's New Page", 'gravitypdf' ) . '</h6>' . __( "When upgrading Gravity PDF we'll automatically redirect you to our What's New page so you can see the changes. Bug fix and security releases are excluded (4.0.x).", 'gravitypdf' ),
					),
				)
			),

			'general_security' => apply_filters( 'gfpdf_settings_general_security',
				array(
					'admin_capabilities' => array(
						'id'          => 'admin_capabilities',
						'name'        => __( 'User Restriction', 'gravitypdf' ),
						'desc'        => __( 'Restrict PDF access to users with any of these capabilities. The Administrator Role always has full access.', 'gravitypdf' ),
						'type'        => 'select',
						'options'     => $this->get_capabilities(),
						'std'         => 'gravityforms_view_entries',
						'inputClass'  => 'large',
						'chosen'      => true,
						'multiple'    => true,
						'required'    => true,
						'placeholder' => __( 'Select Capability', 'gravitypdf' ),
						'tooltip'     => '<h6>' . __( 'User Restriction', 'gravitypdf' ) . '</h6>' . __( "Only logged in users with any selected capability can view generated PDFs they don't have ownership of. Ownership refers to an end user who completed the original Gravity Form entry.", 'gravitypdf' ),
					),

					'limit_to_admin' => array(
						'id'      => 'limit_to_admin',
						'name'    => __( 'Restrict Owner', 'gravitypdf' ),
						'desc'    => __( 'When enabled, the original entry owner will NOT be able to view the PDFs.', 'gravitypdf' ),
						'type'    => 'radio',
						'options' => array(
							'Yes'     => __( 'Yes', 'gravitypdf' ),
							'No'      => __( 'No', 'gravitypdf' ),
						),
						'std'     => __( 'No', 'gravitypdf' ),
						'tooltip' => '<h6>' . __( 'Restrict Owner', 'gravitypdf' ) . '</h6>' . __( 'Enable this setting if your PDFs should not be viewable by the end user.', 'gravitypdf' ),
					),

					'logged_out_timeout' => array(
						'id'      => 'logged_out_timeout',
						'name'    => __( 'Logged Out Timeout', 'gravitypdf' ),
						'desc'    => __( 'Limit how long a <em>logged out</em> users has direct access to the PDF after completing the form. Set to 0 to disable time limit (not recommended).', 'gravitypdf' ),
						'desc2'   => __( 'minutes', 'gravitypdf' ),
						'type'    => 'number',
						'size'    => 'small',
						'std'     => 20,
						'tooltip' => '<h6>' . __( 'Logged Out Timeout', 'gravitypdf' ) . '</h6>' . __( 'Logged out users can view PDFs when their IP matches the one assigned to the Gravity Form entry. Because IP addresses can change, a time-based restriction also applies.', 'gravitypdf' ),
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
						'name'    => __( 'Setup Custom Templates', 'gravitypdf' ),
						'desc'    => sprintf( __( 'Setup environment for building custom templates. %sSee docs to get started%s.', 'gravitypdf' ), '<a href="#">', '</a>' ),
						'type'    => 'button',
						'std'     => __( 'Run Setup', 'gravitypdf' ),
						'options' => 'copy',
						'tooltip' => '<h6>' . __( 'Setup Custom Templates', 'gravitypdf' ) . '</h6>' . __( 'The setup will create a environment in your uploads directory so you can freely create custom PDF templates without the risk of overriding your modifications when the plugin updates.', 'gravitypdf' ),
					),

					'manage_fonts' => array(
						'id'      => 'manage_fonts',
						'name'    => __( 'Fonts', 'gravitypdf' ),
						'desc'    => __( 'Add, update or remove custom fonts.', 'gravitypdf' ),
						'type'    => 'button',
						'std'     => __( 'Manage Fonts', 'gravitypdf' ),
						'options' => 'install_fonts',
						'tooltip' => '<h6>' . __( 'Install Fonts', 'gravitypdf' ) . '</h6>' . sprintf( __( 'Custom fonts can be installed so you can use them in your PDFs. Only %s.ttf%s and %s.otf%s font files are supported.', 'gravitypdf' ), '<code>', '</code>', '<code>', '</code>', '<code>', '</code>' ),
					),
				)
			),

			/* Form (PDF) Settings */
			'form_settings' => apply_filters('gfpdf_form_settings',
				array(

					'name' => array(
						'id'       => 'name',
						'name'     => __( 'Name', 'gravitypdf' ),
						'type'     => 'text',
						'required' => true,
						'tooltip'  => '<h6>' . __( 'PDF Name', 'gravitypdf' ) . '</h6>' . sprintf( __( 'Make it easy to distinguish between multiple PDFs by giving it an easy-to-remember name (for internal use). Use the %sFilename%s field below to set the actual PDF name.', 'gravitypdf' ), '<em>', '</em>' ),
					),

					'template' => array(
						'id'         => 'template',
						'name'       => __( 'Template', 'gravitypdf' ),
						'desc'       => sprintf( __( 'Choose an existing template or purchased more %sfrom our theme shop%s. You can also %sbuild your own%s or %shire us%s to create a custom solution.', 'gravitypdf' ), '<a href="#">', '</a>', '<a href="#">', '</a>', '<a href="#">', '</a>' ),
						'type'       => 'select',
						'options'    => $this->get_templates(),
						'std'     	 => $this->get_option( 'default_template', 'core-simple' ),
						'inputClass' => 'large',
						'chosen'     => true,
						'tooltip'    => '<h6>' . __( 'Templates', 'gravitypdf' ) . '</h6>' . sprintf( __( 'Gravity PDF comes with %sfive completely-free and highly customisable designs%s to choose. You can also purchase additional templates from our theme shop, hire us to integrate existing PDFs or, with a bit of technical know-how, build your own.', 'gravitypdf' ), '<strong>', '</strong>' ),
					),

					'notification' => array(
						'id'                 => 'notification',
						'name'               => __( 'Notifications', 'gravitypdf' ),
						'desc'               => __( 'Automatically attach PDF to the selected notifications.', 'gravitypdf' ),
						'type'               => 'select',
						'options'            => array(),
						'inputClass'         => 'large',
						'chosen'             => true,
						'multiple'           => true,
						'placeholder'        => __( 'Choose a Notification', 'gravitypdf' ),
						'tooltip'  => '<h6>' . __( 'Notifications', 'gravitypdf' ) . '</h6>' . __( 'Automatically generate and attach the PDF to your selected notifications. Conditional Logic for both the PDF and the Notification applies.', 'gravitypdf' ),
					),

					'filename' => array(
						'id'         => 'filename',
						'name'       => __( 'Filename', 'gravitypdf' ),
						'type'       => 'text',
						'desc'       => 'The name used when saving a PDF. Mergetags are allowed.',
						'tooltip'    => '<h6>' . __( 'Filename', 'gravitypdf' ) . '</h6>' . sprintf( __( 'Set an appropriate filename for the generated PDF. You should exclude the .pdf extension from the name. The following are invalid characters and will be converted to an underscore (_): %s', 'gravitypdf' ), '<code>/ \ " * ? | : < ></code>' ),
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
						'required'   => true,
					),

					'conditional' => array(
						'id'         => 'conditional',
						'name'       => __( 'Conditional Logic', 'gravitypdf' ),
						'type'       => 'conditional_logic',
						'desc'       => __( 'Enable conditional logic', 'gravitypdf' ),
						'class'      => 'conditional_logic',
						'inputClass' => 'conditional_logic_listener',
						'tooltip'    => '<h6>' . __( 'Conditional Logic', 'gravitypdf' ) . '</h6>' . __( 'Create rules to dynamically enable or disable PDFs. This includes attaching to notifications and viewing from your admin area.', 'gravitypdf' ),
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
						'name'    => __( 'Paper Size', 'gravitypdf' ),
						'desc'    => __( 'Set the paper size used when generating PDFs.', 'gravitypdf' ),
						'type'    => 'select',
						'options' => $this->get_paper_size(),
						'std'     => $this->get_option( 'default_pdf_size', 'A4' ),
						'inputClass'   => 'large',
						'class' => 'gfpdf_paper_size',
						'chosen'  => true,
					),

					'custom_pdf_size' => array(
						'id'      => 'custom_pdf_size',
						'name'    => __( 'Custom Paper Size', 'gravitypdf' ),
						'desc'    => __( 'Control the exact paper size. Can be set in millimeters or inches.', 'gravitypdf' ),
						'type'    => 'paper_size',
						'size'    => 'small',
						'chosen'  => true,
						'required' => true,
						'class'   => 'gfpdf-hidden gfpdf_paper_size_other',
						'std'     => $this->get_option( 'default_custom_pdf_size' ),
					),

					'orientation' => array(
						'id'      => 'orientation',
						'name'    => __( 'Orientation', 'gravitypdf' ),
						'type'    => 'select',
						'options' => array(
							'portrait' => __( 'Portrait', 'gravitypdf' ),
							'landscape' => __( 'Landscape', 'gravitypdf' ),
						),
						'inputClass'   => 'large',
						'chosen'  => true,
					),

					'font' => array(
						'id'      => 'font',
						'name'    => __( 'Font', 'gravitypdf' ),
						'type'    => 'select',
						'options' => $this->get_installed_fonts(),
						'std'     => $this->get_option( 'default_font_type' ),
						'desc'    => sprintf( __( 'Set the font type used in PDFs. Choose an existing font or %sinstall your own%s.', 'gravitypdf' ), '<a href="'. $this->data->settings_url .'&tab=tools#manage_fonts">', '</a>' ),
						'inputClass'   => 'large',
						'chosen'  => true,
						'tooltip'    => '<h6>' . __( 'Fonts', 'gravitypdf' ) . '</h6>' . __( 'Gravity PDF comes bundled with fonts for most languages world-wide. Want to use a specific font type? Use the font installer (found in the Forms -> Settings -> Tools tab).', 'gravitypdf' ),
					),

					'font_size' => array(
						'id'      => 'font_size',
						'name'    => __( 'Font Size', 'gravitypdf' ),
						'desc'    => __( 'Set the font size to use in the PDF.', 'gravitypdf' ),
						'desc2'   => 'pt',
						'type'    => 'number',
						'size'    => 'small',
						'std'     => $this->get_option( 'default_font_size', 12 ),
					),

					'font_colour' => array(
						'id'      => 'font_colour',
						'name'    => __( 'Font Colour', 'gravitypdf' ),
						'type'    => 'color',
						'std'     => $this->get_option( 'default_font_colour', '#000000' ),
						'desc'    => __( 'Set the font colour used in the PDF.', 'gravitypdf' ),
					),

					'rtl' => array(
						'id'    => 'rtl',
						'name'    => __( 'Reverse Text (RTL)', 'gravitypdf' ),
						'desc'  => __( 'Script like Arabic and Hebrew are written right to left.', 'gravitypdf' ),
						'type'  => 'radio',
						'options' => array(
							'Yes' => __( 'Yes', 'gravitypdf' ),
							'No'  => __( 'No', 'gravitypdf' ),
						),
						'std'   => $this->get_option( 'default_rtl', 'No' ),
						'tooltip'    => '<h6>' . __( 'Reverse Text (RTL)', 'gravitypdf' ) . '</h6>' . __( "Enable RTL if you are writing in Arabic, Hebrew, Syriac, N'ko, Thaana, Tifinar or Urdu.", 'gravitypdf' ),
					),

				)
			),

			/**
			 * Form (PDF) Settings Custom Appearance
			 * This filter allows templates to add custom options for use specific to that template
			 * Gravity PDF autoloads a PHP template file if it exists and loads it up with this filter
			 * @todo  add link to documentation on doing this
			 */
			'form_settings_custom_appearance' => apply_filters('gfpdf_form_settings_custom_appearance',
				array()
			),

			/* Form (PDF) Settings Advanced */
			'form_settings_advanced' => apply_filters('gfpdf_form_settings_advanced',
				array(
					'format' => array(
						'id'    => 'format',
						'name'  => __( 'Format', 'gravitypdf' ),
						'desc'  => __( 'Generate a PDF in the selected format.', 'gravitypdf' ),
						'type'  => 'radio',
						'options' => array(
							'Standard' => 'Standard',
							'PDFA1B'  => 'PDF/A-1b',
							'PDFX1A'  => 'PDF/X-1a',
						),
						'std'   => 'Standard',
						'tooltip' => '<h6>' . __( 'PDF Format', 'gravitypdf' ) . '</h6>' . sprintf( __( "Generate a document adhearing to the appropriate PDF standard. When not in %sStandard%s mode, watermarks, alpha-transparent PNGs and security options can NOT be used.", 'gravitypdf' ), '<em>', '</em>' ),
					),

					'security' => array(
						'id'      => 'security',
						'name'    => __( 'Enable PDF Security', 'gravitypdf' ),
						'desc'    => __( 'Password protect generated PDFs, or restrict user capabilities.', 'gravitypdf' ),
						'type'    => 'radio',
						'options' => array(
							'Yes' => __( 'Yes', 'gravitypdf' ),
							'No'  => __( 'No', 'gravitypdf' ),
						),
						'std'     => __( 'No', 'gravitypdf' ),
					),

					'password' => array(
						'id'    => 'password',
						'name'  => __( 'Password', 'gravitypdf' ),
						'type'  => 'text',
						'desc'  => 'Password protect the PDFs, or leave blank to disable password protection.',
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
					),

					'privileges' => array(
						'id'      => 'privileges',
						'name'    => __( 'Privileges', 'gravitypdf' ),
						'desc'    => 'Restrict end user capabilities by removing privileges.',
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
						'tooltip'     => '<h6>' . __( 'Privileges', 'gravitypdf' ) . '</h6>' . __( 'You can prevent the end user completing certain actions to the PDF – such as copying text, printing, adding annotations or extracting pages.', 'gravitypdf' ),
						'multiple'    => true,
						'placeholder' => __( 'Select End User PDF Privileges', 'gravitypdf' ),
					),

					'image_dpi' => array(
						'id'    => 'image_dpi',
						'name'  => __( 'Image DPI', 'gravitypdf' ),
						'type'  => 'number',
						'size'  => 'small',
						'std'   => 96,
						'tooltip' => '<h6>' . __( 'Image DPI', 'gravitypdf' ) . '</h6>' . __( 'Control the image DPI (dots per inch) in PDFs. Set to 300 when professionally printing document.', 'gravitypdf' ),
					),

					'save' => array(
						'id'    => 'save',
						'name'  => __( 'Always Save PDF?', 'gravitypdf' ),
						'desc'  => __( 'Force a PDF to be saved to disk when a new entry is created.', 'gravitypdf' ),
						'type'  => 'radio',
						'options' => array(
							'Yes' => __( 'Yes', 'gravitypdf' ),
							'No'  => __( 'No', 'gravitypdf' ),
						),
						'std'   => __( 'No', 'gravitypdf' ),
						'tooltip' => '<h6>' . __( 'Save PDF', 'gravitypdf' ) . '</h6>' . __( "By default, PDFs are not automatically saved to disk. Enable this option to force the PDF to be generated and saved. Useful when using the 'gfpdf_post_pdf_save' hook to copy the PDF to an alternate location.", 'gravitypdf' ),
					),

				)
			),
		);

		return apply_filters( 'gfpdf_registered_fields', $gfpdf_settings );
	}

	/**
	 * Enable public access field if the user has enabled it with a filter
	 * @param  Array $settings The 'form_settings_advanced' array
	 * @return Array
	 * @since 4.0
	 */
	public function get_public_access_field( $settings ) {

		$enabled = apply_filters( 'gfpdf_enable_public_access_field', false );

		if( $enabled !== true ) {
			return $settings;
		}

		$settings['public_access'] = array(
						'id'    => 'public_access',
						'name'  => __( 'Enable Public Acccess?', 'gravitypdf' ),
						'desc'  => sprintf( __( 'Allow ANYONE to access the PDFs. %sWarning: This disables all security protocols.%s', 'gravitypdf' ), '<strong>', '</strong>' ),
						'type'  => 'radio',
						'options' => array(
							'Yes' => __( 'Yes', 'gravitypdf' ),
							'No'  => __( 'No', 'gravitypdf' ),
						),
						'std'   => __( 'No', 'gravitypdf' ),
		);

		return $settings;
	}

	/**
	 * Enable advanced templating field if the user has enabled it with a filter, or our premium plugin has been installed
	 * @param  Array $settings The 'form_settings_advanced' array
	 * @return Array
	 * @since 4.0
	 * @todo Currently not sure if this method is actually needed for our v4.x release. We will need the access key during our import process for backwards compatibility
	 */
	public function get_advanced_template_field( $settings ) {

		$enabled = apply_filters( 'gfpdf_enable_advanced_template_field', false );

		if( $enabled !== true && ! class_exists( 'gfpdfe_business_plus' ) ) {
			return $settings;
		}

		$settings['advanced_template'] = array(
						'id'    => 'advanced_template',
						'name'  => __( 'Enable Advanced Templating?', 'gravitypdf' ),
						'desc'  => sprintf( __( 'By enabling, a PDF template will no longer be treated as HTML. %sUse wisely.%s', 'gravitypdf' ), '<strong>', '</strong>' ),
						'type'  => 'radio',
						'options' => array(
							'Yes' => __( 'Yes', 'gravitypdf' ),
							'No'  => __( 'No', 'gravitypdf' ),
						),
						'std'   => __( 'No', 'gravitypdf' ),
		);

		return $settings;
	}

	/**
	 * Return the optional template-specific form title field
	 * @return Array
	 * @since 4.0
	 */
	public function get_form_title_display_field() {
		return apply_filters( 'gfpdf_form_title_display_setting', array(
			'id'    => 'show_form_title',
			'name'  => __( 'Show Form Title?', 'gravitypdf' ),
			'desc'  => __( 'Display the form title at the beginning of the PDF.', 'gravitypdf' ),
			'type'  => 'radio',
			'options' => array(
				'Yes' => __( 'Yes', 'gravitypdf' ),
				'No'  => __( 'No', 'gravitypdf' ),
			),
			'std'   => __( 'No', 'gravitypdf' ),
		) );
	}

	/**
	 * Return the optional template-specific page names field
	 * @return Array
	 * @since 4.0
	 */
	public function get_page_names_display_field() {
		return apply_filters( 'gfpdf_page_names_display_setting', array(
			'id'    => 'show_page_names',
			'name'  => __( 'Show Page Names?', 'gravitypdf' ),
			'desc'  => __( 'Display form page names on the PDF when enabled (only works when using page break field).', 'gravitypdf' ),
			'type'  => 'radio',
			'options' => array(
				'Yes' => __( 'Yes', 'gravitypdf' ),
				'No'  => __( 'No', 'gravitypdf' ),
			),
			'std'   => __( 'No', 'gravitypdf' ),
		) );
	}

	/**
	 * Return the optional template-specific HTML field
	 * @return Array
	 * @since 4.0
	 */
	public function get_html_display_field() {
		return apply_filters( 'gfpdf_html_display_setting', array(
			'id'    => 'show_html',
			'name'  => __( 'Show HTML Fields?', 'gravitypdf' ),
			'desc'  => __( 'Display HTML fields in the PDF.', 'gravitypdf' ),
			'type'  => 'radio',
			'options' => array(
				'Yes' => __( 'Yes', 'gravitypdf' ),
				'No'  => __( 'No', 'gravitypdf' ),
			),
			'std'   => __( 'No', 'gravitypdf' ),
		) );
	}

	/**
	 * Return the optional template-specific section content field
	 * @return Array
	 * @since 4.0
	 */
	public function get_section_content_display_field() {
		return apply_filters( 'gfpdf_section_content_display_setting', array(
			'id'    => 'show_section_content',
			'name'  => __( 'Show Section Break Description?', 'gravitypdf' ),
			'desc'  => __( 'Display Section Break field description in the PDF.', 'gravitypdf' ),
			'type'  => 'radio',
			'options' => array(
				'Yes' => __( 'Yes', 'gravitypdf' ),
				'No'  => __( 'No', 'gravitypdf' ),
			),
			'std'   => __( 'No', 'gravitypdf' ),
		) );
	}

	/**
	 * Return the optional template-specific hidden field
	 * @return Array
	 * @since 4.0
	 */
	public function get_hidden_display_field() {
		return apply_filters( 'gfpdf_hidden_display_setting', array(
			'id'    => 'show_hidden',
			'name'  => __( 'Show Hidden Fields?', 'gravitypdf' ),
			'desc'  => __( 'Display Hidden fields in the PDF.', 'gravitypdf' ),
			'type'  => 'radio',
			'options' => array(
				'Yes' => __( 'Yes', 'gravitypdf' ),
				'No'  => __( 'No', 'gravitypdf' ),
			),
			'std'   => __( 'No', 'gravitypdf' ),
		) );
	}

	/**
	 * Return the optional template-specific empty field
	 * @return Array
	 * @since 4.0
	 */
	public function get_empty_display_field() {
		return apply_filters( 'gfpdf_empty_display_setting', array(
			'id'    => 'show_empty',
			'name'  => __( 'Show Empty Fields?', 'gravitypdf' ),
			'desc'  => __( 'Display Empy fields in the PDF.', 'gravitypdf' ),
			'type'  => 'radio',
			'options' => array(
				'Yes' => __( 'Yes', 'gravitypdf' ),
				'No'  => __( 'No', 'gravitypdf' ),
			),
			'std'   => __( 'No', 'gravitypdf' ),
		) );
	}

	/**
	 * Return the optional template-specific header field
	 * @return Array
	 * @since 4.0
	 */
	public function get_header_field() {
		return apply_filters( 'gfpdf_header_field_setting', array(
			'id'         => 'header',
			'name'       => __( 'Header', 'gravitypdf' ),
			'type'       => 'rich_editor',
			'size'       => 8,
			'desc'       => sprintf( __( 'The header is included at the top of each page. For best results, keep the formatting simple.', 'gravitypdf' ), '<em>', '</em>', '<em>', '</em>' ),
			'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			'tooltip'    => '<h6>' . __( 'Header', 'gravitypdf' ) . '</h6>' . sprintf( __( 'For the best image quality, ensure you insert images at %sFull Size%s. Left and right image alignment work as expected, but to center align you need to wrap the image in a %s tag.', 'gravitypdf' ), '<em>', '</em>', esc_html( '<div class="centeralign">...</div>' ) ),
		) );
	}

	/**
	 * Return the optional template-specific first page header field
	 * @return Array
	 * @since 4.0
	 */
	public function get_first_page_header_field() {
		return apply_filters( 'gfpdf_first_page_header_field_setting', array(
			'id'         => 'first_header',
			'name'       => __( 'First Page Header', 'gravitypdf' ),
			'type'       => 'rich_editor',
			'size'       => 8,
			'desc'       => __( 'Override the header on the first page of the PDF.', 'gravitypdf' ),
			'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			'toggle'     => __( 'Use different header on first page of PDF?', 'gravitypdf' ),
		) );
	}

	/**
	 * Return the optional template-specific footer field
	 * @return Array
	 * @since 4.0
	 */
	public function get_footer_field() {
		return apply_filters( 'gfpdf_footer_field_setting', array(
			'id'         => 'footer',
			'name'       => __( 'Footer', 'gravitypdf' ),
			'type'       => 'rich_editor',
			'size'       => 8,
			'desc'       => sprintf( __( 'The footer is included at the bottom of every page. For simple columns %stry this HTML table snippet%s.', 'gravitypdf' ), '<a href="https://gist.github.com/blueliquiddesigns/e6179a96cd97ef0a8457">', '</a>' ),
			'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			'tooltip'    => '<h6>' . __( 'Footer', 'gravitypdf' ) . '</h6>' . sprintf( __( 'For simple text footers try use the left, center and right alignment buttons in the editor. You can also use the special %s{PAGENO}%s and %s{nbpg}%s tags to display page numbering.', 'gravitypdf' ), '<em>', '</em>', '<em>', '</em>' ),
		) );
	}

	/**
	 * Return the optional template-specific first page footer field
	 * @return Array
	 * @since 4.0
	 */
	public function get_first_page_footer_field() {
		return apply_filters( 'gfpdf_first_page_footer_field_setting', array(
			'id'         => 'first_footer',
			'name'       => __( 'First Page Footer', 'gravitypdf' ),
			'type'       => 'rich_editor',
			'size'       => 8,
			'desc'       => __( 'Override the footer on the first page of the PDF.', 'gravitypdf' ),
			'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			'toggle'     => __( 'Use different footer on first page of PDF?', 'gravitypdf' ),
		) );
	}

	/**
	 * Return the optional template-specific background image field
	 * @return Array
	 * @since 4.0
	 */
	public function get_background_field() {
		return apply_filters( 'gfpdf_background_field_setting', array(
			'id'      => 'background',
			'name'    => __( 'Background Image', 'gravitypdf' ),
			'type'    => 'upload',
			'desc'    => __( 'The background image is included on all pages. For optimal results, use an image the same dimensions as the paper size.', 'gravitypdf' ),
			'tooltip' => '<h6>' . __( 'Background Image', 'gravitypdf' ) . '</h6>' . __( 'For the best results, use a JPG or non-interlaced 8-Bit PNG that has the same dimensions as the paper size.', 'gravitypdf' ),
		) );
	}
}
