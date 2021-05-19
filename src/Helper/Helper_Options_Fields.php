<?php

namespace GFPDF\Helper;

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
 * Class to set up the settings api fields
 *
 * @since 4.0
 */
class Helper_Options_Fields extends Helper_Abstract_Options implements Helper_Interface_Filters {

	/**
	 * Add our filters
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function add_filters() {

		/* Conditionally enable specific fields */
		add_filter( 'gfpdf_form_settings_advanced', [ $this, 'get_advanced_template_field' ] );
		add_filter( 'gfpdf_form_settings_advanced', [ $this, 'get_master_password_field' ] );

		parent::add_filters();
	}

	/**
	 * Retrieve the array of registered fields
	 *
	 * @return array
	 * @since 4.0
	 *
	 */
	public function get_registered_fields() {

		/**
		 * Gravity PDF settings
		 * Filters are provided for each settings section to allow extensions and other plugins to add their own option
		 * which will be processed by our settings API
		 */
		$gfpdf_settings = [

			/*
			 * General Settings
			 *
			 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_settings_general/ for more details about this filter
			 */
			'general_defaults'                => apply_filters(
				'gfpdf_settings_general_defaults',
				[
					'default_template'        => [
						'id'      => 'default_template',
						'name'    => esc_html__( 'Default Template', 'gravity-forms-pdf-extended' ),
						'desc'    => sprintf( esc_html__( 'Choose an existing template or purchased more %1$sfrom our template shop%2$s. You can also %3$sbuild your own%4$s or %5$shire us%6$s to create a custom solution.', 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/store/#templates">', '</a>', '<a href="https://docs.gravitypdf.com/v6/developers/start-customising">', '</a>', '<a href="https://gravitypdf.com/bespoke/">', '</a>' ),
						'type'    => 'select',
						'options' => $this->templates->get_all_templates_by_group(),
						'std'     => 'zadani',
						'tooltip' => '<h6>' . esc_html__( 'Templates', 'gravity-forms-pdf-extended' ) . '</h6>' . sprintf( esc_html__( 'Gravity PDF comes with %1$sfour completely-free and highly customizable designs%2$s. You can also purchase additional templates from our template shop, hire us to integrate existing PDFs or, with a bit of technical know-how, build your own.', 'gravity-forms-pdf-extended' ), '<strong>', '</strong>' ),
					],

					'default_font'            => [
						'id'      => 'default_font',
						'name'    => esc_html__( 'Default Font', 'gravity-forms-pdf-extended' ),
						'desc'    => esc_html__( 'Set the default font type used in PDFs. Choose an existing font or install your own.', 'gravity-forms-pdf-extended' ),
						'type'    => 'select',
						'options' => $this->get_installed_fonts(),
						'tooltip' => '<h6>' . esc_html__( 'Fonts', 'gravity-forms-pdf-extended' ) . '</h6>' . esc_html__( 'Gravity PDF comes bundled with fonts for most languages world-wide. Want to use a specific font type? Use the font installer (found in the Tools tab).', 'gravity-forms-pdf-extended' ),
						'class'   => 'gfpdf-font-manager',
					],

					'default_pdf_size'        => [
						'id'      => 'default_pdf_size',
						'name'    => esc_html__( 'Default Paper Size', 'gravity-forms-pdf-extended' ),
						'desc'    => esc_html__( 'Set the default paper size used when generating PDFs.', 'gravity-forms-pdf-extended' ),
						'type'    => 'select',
						'options' => $this->get_paper_size(),
						'class'   => 'gfpdf_paper_size',
					],

					'default_custom_pdf_size' => [
						'id'    => 'default_custom_pdf_size',
						'name'  => esc_html__( 'Custom Paper Size', 'gravity-forms-pdf-extended' ),
						'desc'  => esc_html__( 'Control the exact paper size. Can be set in millimeters or inches.', 'gravity-forms-pdf-extended' ),
						'type'  => 'paper_size',
						'class' => 'gfpdf-hidden gfpdf_paper_size_other',
					],

					'default_rtl'             => [
						'id'      => 'default_rtl',
						'name'    => esc_html__( 'Reverse Text (RTL)', 'gravity-forms-pdf-extended' ),
						'desc'    => esc_html__( 'Script like Arabic and Hebrew are written right to left.', 'gravity-forms-pdf-extended' ),
						'type'    => 'toggle',
						'std'     => '0',
						'tooltip' => '<h6>' . esc_html__( 'Reverse Text (RTL)', 'gravity-forms-pdf-extended' ) . '</h6>' . esc_html__( "Enable RTL if you are writing in Arabic, Hebrew, Syriac, N'ko, Thaana, Tifinar, Urdu or other RTL languages.", 'gravity-forms-pdf-extended' ),
					],

					'default_font_size'       => [
						'id'    => 'default_font_size',
						'name'  => esc_html__( 'Default Font Size', 'gravity-forms-pdf-extended' ),
						'desc'  => esc_html__( 'Set the default font size used in PDFs.', 'gravity-forms-pdf-extended' ),
						'desc2' => 'pt',
						'type'  => 'number',
						'size'  => 'small',
						'std'   => 10,
					],

					'default_font_colour'     => [
						'id'   => 'default_font_colour',
						'name' => esc_html__( 'Default Font Color', 'gravity-forms-pdf-extended' ),
						'type' => 'color',
						'std'  => '#000000',
						'desc' => esc_html__( 'Set the default font color used in PDFs.', 'gravity-forms-pdf-extended' ),
					],
				]
			),

			'general'                         => apply_filters(
				'gfpdf_settings_general',
				[
					'default_action'        => [
						'id'      => 'default_action',
						'name'    => esc_html__( 'Entry View', 'gravity-forms-pdf-extended' ),
						'desc'    => sprintf( esc_html__( 'Select the default action used when accessing a PDF from the %1$sGravity Forms entries list%2$s page.', 'gravity-forms-pdf-extended' ), '<a href="' . admin_url( 'admin.php?page=gf_entries' ) . '">', '</a>' ),
						'type'    => 'radio',
						'options' => [
							'View'     => esc_html__( 'View', 'gravity-forms-pdf-extended' ),
							'Download' => esc_html__( 'Download', 'gravity-forms-pdf-extended' ),
						],
						'std'     => 'View',
					],

					'background_processing' => [
						'id'   => 'background_processing',
						'name' => esc_html__( 'Background Processing', 'gravity-forms-pdf-extended' ),
						'desc' => sprintf( esc_html__( 'When enable, form submission and resending notifications with PDFs are handled in a background task. %1$sRequires Background tasks to be enabled%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/background-processing/">', '</a>' ),
						'type' => 'toggle',
						'std'  => '0',
					],

					'debug_mode'            => [
						'id'   => 'debug_mode',
						'name' => esc_html__( 'Debug Mode', 'gravity-forms-pdf-extended' ),
						'type' => 'toggle',
						'std'  => '0',
						'desc' => esc_html__( 'When enabled, debug information will be displayed on-screen for core features.', 'gravity-forms-pdf-extended' ),
					],
				]
			),

			/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_settings_general_security/ for more details about this filter */
			'general_security'                => apply_filters(
				'gfpdf_settings_general_security',
				[
					'logged_out_timeout'     => [
						'id'      => 'logged_out_timeout',
						'name'    => esc_html__( 'Logged Out Timeout', 'gravity-forms-pdf-extended' ),
						'desc'    => sprintf( esc_html__( 'Limit how long a %1$slogged out%2$s users has direct access to the PDF after completing the form. Set to 0 to disable time limit (not recommended).', 'gravity-forms-pdf-extended' ), '<em>', '</em>' ),
						'desc2'   => esc_html__( 'minutes', 'gravity-forms-pdf-extended' ),
						'type'    => 'number',
						'size'    => 'small',
						'std'     => 20,
						'tooltip' => '<h6>' . esc_html__( 'Logged Out Timeout', 'gravity-forms-pdf-extended' ) . '</h6>' . esc_html__( 'Logged out users can view PDFs when their IP matches the one assigned to the Gravity Form entry. Because IP addresses can change, a time-based restriction also applies.', 'gravity-forms-pdf-extended' ),
					],

					'default_restrict_owner' => [
						'id'      => 'default_restrict_owner',
						'name'    => esc_html__( 'Default Owner Restrictions', 'gravity-forms-pdf-extended' ),
						'desc'    => esc_html__( 'Set the default PDF owner permissions. When enabled, the original entry owner will NOT be able to view the PDFs (unless they have a User Restriction capability).', 'gravity-forms-pdf-extended' ),
						'type'    => 'toggle',
						'std'     => '0',
						'tooltip' => '<h6>' . esc_html__( 'Restrict Owner', 'gravity-forms-pdf-extended' ) . '</h6>' . esc_html__( 'Enable this setting if your PDFs should not be viewable by the end user. This can be set on a per-PDF basis.', 'gravity-forms-pdf-extended' ),
					],

					'admin_capabilities'     => [
						'id'      => 'admin_capabilities',
						'name'    => esc_html__( 'User Restriction', 'gravity-forms-pdf-extended' ),
						'class'   => 'gform-settings-panel--full col-1-3',
						'desc'    => esc_html__( 'Restrict PDF access to users with any of these capabilities. The Administrator Role always has full access.', 'gravity-forms-pdf-extended' ),
						'type'    => 'multicheck',
						'options' => $this->get_capabilities(),
						'std'     => [ 'gravityforms_view_entries' ],
						'tooltip' => '<h6>' . esc_html__( 'User Restriction', 'gravity-forms-pdf-extended' ) . '</h6>' . esc_html__( "Only logged in users with any selected capability can view generated PDFs they don't have ownership of. Ownership refers to an end user who completed the original Gravity Form entry.", 'gravity-forms-pdf-extended' ),
					],
				]
			),

			/* Extension Settings */
			'extensions'                      => apply_filters(
				'gfpdf_settings_extensions',
				[]
			),

			/* License Settings */
			'licenses'                        => apply_filters(
				'gfpdf_settings_licenses',
				[]
			),

			/*
			 * Tools Settings
			 *
			 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_settings_tools/ for more details about this filter
			 */
			'tools'                           => apply_filters(
				'gfpdf_settings_tools',
				[
					'install_core_fonts' => [
						'id'   => 'install_core_fonts',
						'name' => esc_html__( 'Install Core Fonts', 'gravity-forms-pdf-extended' ),
						'desc' => esc_html__( 'Automatically install the core fonts needed to generate PDF documents. This action only needs to be run once, as the fonts are preserved during plugin updates.', 'gravity-forms-pdf-extended' ),
						'type' => 'button',
						'std'  => __( 'Download Core Fonts', 'gravity-forms-pdf-extended' ),
					],

					'manage_fonts'       => [
						'id'   => 'manage_fonts',
						'name' => esc_html__( 'Fonts', 'gravity-forms-pdf-extended' ),
						'desc' => '<div class="gform-settings-description gform-kitchen-sink">' . sprintf( esc_html__( 'Install custom fonts for use in your PDF documents. Only %1$s.ttf%2$s font files are supported.', 'gravity-forms-pdf-extended' ), '<code>', '</code>' ) . '</div>',
						'type' => 'descriptive_text',
					],
				]
			),

			/*
			 * Form (PDF) Settings
			 *
			 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_settings/ for more details about this filter
			 */
			'form_settings'                   => apply_filters(
				'gfpdf_form_settings',
				[
					'name'             => [
						'id'       => 'name',
						'name'     => esc_html__( 'Label', 'gravity-forms-pdf-extended' ),
						'type'     => 'text',
						'required' => true,
						'desc'     => esc_html__( 'Add a descriptive label to help you  differentiate between multiple PDF settings.', 'gravity-forms-pdf-extended' ),
					],

					'template'         => [
						'id'         => 'template',
						'name'       => esc_html__( 'Template', 'gravity-forms-pdf-extended' ),
						'desc'       => sprintf( esc_html__( 'Templates control the overall look and feel of the PDFs, and additional templates can be %1$spurchased from the online store%4$s. If you want to digitize and automate your existing documents, %2$suse our Bespoke PDF service%4$s. Developers can also %3$sbuild their own templates%4$s.', 'gravity-forms-pdf-extended' ), '<a href="https://gravitypdf.com/store/#templates">', '<a href="https://gravitypdf.com/bespoke/">', '<a href="https://docs.gravitypdf.com/v6/developers/start-customising/">', '</a>' ),
						'type'       => 'select',
						'options'    => $this->templates->get_all_templates_by_group(),
						'std'        => $this->get_option( 'default_template', 'zadani' ),
						'inputClass' => 'large',
					],

					'notification'     => [
						'id'          => 'notification',
						'name'        => esc_html__( 'Notifications', 'gravity-forms-pdf-extended' ),
						'desc'        => sprintf( esc_html__( 'Send the PDF as an email attachment for the selected notification(s). %1$sPassword protect the PDF%3$s if security is a concern. Alternatively, %2$suse the [gravitypdf] shortcode%3$s directly in your Notification message.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/setup-pdf#password">', '<a href="https://docs.gravitypdf.com/v6/users/shortcodes-and-mergetags">', '</a>' ),
						'type'        => 'multicheck',
						'options'     => [],
						'placeholder' => esc_html__( 'Choose a Notification', 'gravity-forms-pdf-extended' ),
					],

					'filename'         => [
						'id'         => 'filename',
						'name'       => esc_html__( 'Filename', 'gravity-forms-pdf-extended' ),
						'type'       => 'text',
						'desc'       => sprintf( esc_html__( 'Set the filename for the generated PDF (excluding the .pdf extension). Mergetags are supported, and invalid characters %s are automatically converted to an underscore.', 'gravity-forms-pdf-extended' ), '<code>/ \ " * ? | : &lt; &gt;</code>' ),
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
						'required'   => true,
					],

					'conditional'      => [
						'id'         => 'conditional',
						'name'       => esc_html__( 'Conditional Logic', 'gravity-forms-pdf-extended' ),
						'type'       => 'conditional_logic',
						'desc'       => esc_html__( 'Enable conditional logic', 'gravity-forms-pdf-extended' ),
						'class'      => 'conditional_logic',
						'inputClass' => 'conditional_logic_listener',
						'desc2'      => esc_html__( 'Add rules to dynamically enable or disable the PDF. When disabled, PDFs do not show up in the admin area, cannot be viewed, and will not be attached to notifications.', 'gravity-forms-pdf-extended' ),
					],

					'conditionalLogic' => [
						'id'    => 'conditionalLogic',
						'type'  => 'hidden',
						'class' => 'gfpdf-hidden',
					],
				]
			),

			/*
			 * Form (PDF) Settings Appearance
			 *
			 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_settings_appearance/ for more details about this filter
			 */
			'form_settings_appearance'        => apply_filters(
				'gfpdf_form_settings_appearance',
				[
					'pdf_size'        => [
						'id'      => 'pdf_size',
						'name'    => esc_html__( 'Paper Size', 'gravity-forms-pdf-extended' ),
						'desc'    => esc_html__( 'Set the paper size used when generating PDFs.', 'gravity-forms-pdf-extended' ),
						'type'    => 'select',
						'options' => $this->get_paper_size(),
						'std'     => $this->get_option( 'default_pdf_size', 'A4' ),
						'class'   => 'gfpdf_paper_size',
					],

					'custom_pdf_size' => [
						'id'       => 'custom_pdf_size',
						'name'     => esc_html__( 'Custom Paper Size', 'gravity-forms-pdf-extended' ),
						'desc'     => esc_html__( 'Control the exact paper size. Can be set in millimeters or inches.', 'gravity-forms-pdf-extended' ),
						'type'     => 'paper_size',
						'size'     => 'small',
						'class'    => 'gfpdf-hidden gfpdf_paper_size_other',
						'std'      => $this->get_option( 'default_custom_pdf_size' ),
						'required' => true,
					],

					'orientation'     => [
						'id'         => 'orientation',
						'name'       => esc_html__( 'Paper Orientation', 'gravity-forms-pdf-extended' ),
						'type'       => 'select',
						'options'    => [
							'portrait'  => esc_html__( 'Portrait', 'gravity-forms-pdf-extended' ),
							'landscape' => esc_html__( 'Landscape', 'gravity-forms-pdf-extended' ),
						],
						'inputClass' => 'large',
					],

					'font'            => [
						'id'      => 'font',
						'name'    => esc_html__( 'Font', 'gravity-forms-pdf-extended' ),
						'type'    => 'select',
						'options' => $this->get_installed_fonts(),
						'std'     => $this->get_option( 'default_font' ),
						'desc'    => esc_html__( 'Set the primary font used in PDFs. You can also install your own.', 'gravity-forms-pdf-extended' ),
						'class'   => 'gfpdf_font_type gfpdf-font-manager',
					],

					'font_size'       => [
						'id'    => 'font_size',
						'name'  => esc_html__( 'Font Size', 'gravity-forms-pdf-extended' ),
						'desc'  => esc_html__( 'Set the font size to use in the PDF.', 'gravity-forms-pdf-extended' ),
						'desc2' => 'pt',
						'type'  => 'number',
						'size'  => 'small',
						'std'   => $this->get_option( 'default_font_size', 10 ),
						'class' => 'gfpdf_font_size',
					],

					'font_colour'     => [
						'id'    => 'font_colour',
						'name'  => esc_html__( 'Font Color', 'gravity-forms-pdf-extended' ),
						'type'  => 'color',
						'std'   => $this->get_option( 'default_font_colour', '#000000' ),
						'desc'  => esc_html__( 'Set the font color to use in the PDF.', 'gravity-forms-pdf-extended' ),
						'class' => 'gfpdf_font_colour',
					],

					'rtl'             => [
						'id'   => 'rtl',
						'name' => esc_html__( 'Reverse Text (RTL)', 'gravity-forms-pdf-extended' ),
						'desc' => esc_html__( 'Script like Arabic, Hebrew, Syriac (and many others) are written right to left.', 'gravity-forms-pdf-extended' ),
						'type' => 'toggle',
						'std'  => $this->get_option( 'default_rtl', '0' ),
					],

				]
			),

			/**
			 * Form (PDF) Settings Custom Appearance
			 * This filter allows templates to add custom options for use specific to that template
			 * Gravity PDF autoloads a PHP template file if it exists and loads it up with this filter
			 *
			 * See https://docs.gravitypdf.com/v6/developers/filters/developer-template-configuration-and-image/#template-configuration for more details
			 */
			'form_settings_custom_appearance' => apply_filters(
				'gfpdf_form_settings_custom_appearance',
				[]
			),

			/*
			 * Form (PDF) Settings Advanced
			 *
			 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_settings_advanced/ for more details about this filter
			 */
			'form_settings_advanced'          => apply_filters(
				'gfpdf_form_settings_advanced',
				[
					'format'          => [
						'id'      => 'format',
						'name'    => esc_html__( 'Format', 'gravity-forms-pdf-extended' ),
						'desc'    => esc_html__( 'Generate a document adhering to the selected PDF format. Watermarks, alpha-transparency, and PDF Security are automatically disabled when using PDF/A-1b or PDF/X-1a formats.', 'gravity-forms-pdf-extended' ),
						'type'    => 'radio',
						'options' => [
							'Standard' => 'Standard',
							'PDFA1B'   => 'PDF/A-1b',
							'PDFX1A'   => 'PDF/X-1a',
						],
						'std'     => 'Standard',
					],

					'security'        => [
						'id'   => 'security',
						'name' => esc_html__( 'Enable PDF Security', 'gravity-forms-pdf-extended' ),
						'desc' => esc_html__( 'Password protect generated PDFs, and/or restrict user capabilities.', 'gravity-forms-pdf-extended' ),
						'type' => 'toggle',
					],

					'password'        => [
						'id'         => 'password',
						'name'       => esc_html__( 'Password', 'gravity-forms-pdf-extended' ),
						'type'       => 'text',
						'desc'       => 'Password protect the PDF, or leave blank to disable. Mergetags are supported.',
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
					],

					'privileges'      => [
						'id'          => 'privileges',
						'name'        => esc_html__( 'Privileges', 'gravity-forms-pdf-extended' ),
						'desc'        => 'Deselect privileges to restrict end user capabilities in the PDF. Privileges are trivial to bypass and are only suitable to specify your intentions to the user (and not as a means of access control or security).',
						'type'        => 'multicheck',
						'options'     => $this->get_privilages(),
						'std'         => [
							'copy',
							'print',
							'print-highres',
							'modify',
							'annot-forms',
							'fill-forms',
							'extract',
							'assemble',
						],
						'placeholder' => esc_html__( 'Select End User PDF Privileges', 'gravity-forms-pdf-extended' ),
					],

					'master_password' => [
						'id'    => 'master_password',
						'type'  => 'hidden',
						'class' => 'gfpdf-hidden',
					],

					'image_dpi'       => [
						'id'   => 'image_dpi',
						'name' => esc_html__( 'Image DPI', 'gravity-forms-pdf-extended' ),
						'type' => 'number',
						'size' => 'small',
						'std'  => 96,
						'desc' => esc_html__( 'Control the image DPI (dots per inch) in PDFs. Set to 300 when professionally printing document.', 'gravity-forms-pdf-extended' ),
					],

					'save'            => [
						'id'    => 'save',
						'type'  => 'hidden',
						'class' => 'gfpdf-hidden',
					],

					'public_access'   => [
						'id'   => 'public_access',
						'name' => esc_html__( 'Enable Public Access', 'gravity-forms-pdf-extended' ),
						'type' => 'toggle',
						'desc' => sprintf( esc_html__( "When public access is on all security protocols are disabled and %3\$sanyone can view the PDF document for ALL your form's entries%4\$s. For better security, %1\$suse the signed PDF urls feature instead%2\$s.", 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/shortcodes-and-mergetags#before-you-get-started">', '</a>', '<strong>', '</strong>' ),
					],

					'restrict_owner'  => [
						'id'   => 'restrict_owner',
						'name' => esc_html__( 'Restrict Owner', 'gravity-forms-pdf-extended' ),
						'desc' => sprintf( esc_html__( 'When enabled, the original entry owner will NOT be able to view the PDFs. This setting is overridden %1$swhen using signed PDF urls%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravitypdf.com/v6/users/shortcodes-and-mergetags#before-you-get-started">', '</a>' ),
						'type' => 'toggle',
						'std'  => $this->get_option( 'default_restrict_owner', '0' ),
					],
				]
			),
		];

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_registered_fields/ for more details about this filter */

		return apply_filters( 'gfpdf_registered_fields', $gfpdf_settings );
	}

	/**
	 * Enable advanced templating field if the user has our legacy premium plugin installed
	 *
	 * Dev notice: We're going to rewrite and rename the Tier 2 premium add-on and utilise template headers to automatically handle
	 * advanced templates without the need for user intervention, which is why this method doesn't have a filter to manually
	 * enable it.
	 *
	 * @param array $settings The 'form_settings_advanced' array
	 *
	 * @return array
	 *
	 * @since 4.0
	 *
	 */
	public function get_advanced_template_field( $settings ) {

		if ( ! class_exists( 'gfpdfe_business_plus' ) ) {
			return $settings;
		}

		$settings['advanced_template'] = [
			'id'   => 'advanced_template',
			'name' => esc_html__( 'Enable Advanced Templating', 'gravity-forms-pdf-extended' ),
			'desc' => esc_html__( 'A legacy setting used that enables a template to be treated as PHP, with direct access to the PDF engine.', 'gravity-forms-pdf-extended' ),
			'type' => 'toggle',
		];

		return $settings;
	}

	/**
	 * Enable the Master Password field.
	 *
	 * This isn't enabled by default because it's very simple for end users to bypass if needed.
	 * If you need to prevent unauthorised access to the generated PDFs you should
	 * use the standard password instead as that will prevent the PDF being viewed by anyone without your password.
	 *
	 * @param array $settings The 'form_settings_advanced' array
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	public function get_master_password_field( $settings ) {

		/**
		 * Use the filter below to return 'true' which will enable the master password field
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_enable_master_password_field/ for usage
		 */
		if ( ! apply_filters( 'gfpdf_enable_master_password_field', false, $settings ) ) {
			return $settings;
		}

		$settings['master_password'] = [
			'id'         => 'master_password',
			'name'       => esc_html__( 'Master Password', 'gravity-forms-pdf-extended' ),
			'type'       => 'text',
			'desc'       => 'Set the PDF Owner Password which is used to prevent the PDF privileges being changed.',
			'inputClass' => 'merge-tag-support mt-hide_all_fields',
		];

		return $settings;
	}

	/**
	 * Return the optional template-specific form title field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_form_title_display_field() {
		return apply_filters(
			'gfpdf_form_title_display_setting',
			[
				'id'   => 'show_form_title',
				'name' => esc_html__( 'Show Form Title', 'gravity-forms-pdf-extended' ),
				'desc' => esc_html__( 'Display the form title at the beginning of the PDF.', 'gravity-forms-pdf-extended' ),
				'type' => 'toggle',
				'std'  => 'Yes',
			]
		);
	}

	/**
	 * Return the optional template-specific page names field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_page_names_display_field() {
		return apply_filters(
			'gfpdf_page_names_display_setting',
			[
				'id'   => 'show_page_names',
				'name' => esc_html__( 'Show Page Names', 'gravity-forms-pdf-extended' ),
				'desc' => sprintf( esc_html__( 'Display form page names on the PDF. Requires the use of the %1$sPage Break field%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://docs.gravityforms.com/page-break/">', '</a>' ),
				'type' => 'toggle',
			]
		);
	}

	/**
	 * Return the optional template-specific HTML field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_html_display_field() {
		return apply_filters(
			'gfpdf_html_display_setting',
			[
				'id'   => 'show_html',
				'name' => esc_html__( 'Show HTML Fields', 'gravity-forms-pdf-extended' ),
				'desc' => esc_html__( 'Display HTML fields in the PDF.', 'gravity-forms-pdf-extended' ),
				'type' => 'toggle',
			]
		);
	}

	/**
	 * Return the optional template-specific section content field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_section_content_display_field() {
		return apply_filters(
			'gfpdf_section_content_display_setting',
			[
				'id'   => 'show_section_content',
				'name' => esc_html__( 'Show Section Break Description', 'gravity-forms-pdf-extended' ),
				'desc' => esc_html__( 'Display the Section Break field description in the PDF.', 'gravity-forms-pdf-extended' ),
				'type' => 'toggle',
			]
		);
	}

	/**
	 * Return the optional template-specific hidden field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_conditional_display_field() {
		return apply_filters(
			'gfpdf_conditional_display_setting',
			[
				'id'   => 'enable_conditional',
				'name' => esc_html__( 'Enable Conditional Logic', 'gravity-forms-pdf-extended' ),
				'desc' => esc_html__( 'When enabled the PDF will adhere to the form field conditional logic and show/hide fields.', 'gravity-forms-pdf-extended' ),
				'type' => 'toggle',
				'std'  => 'Yes',
			]
		);
	}

	/**
	 * Return the optional template-specific empty field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_empty_display_field() {
		return apply_filters(
			'gfpdf_empty_display_setting',
			[
				'id'   => 'show_empty',
				'name' => esc_html__( 'Show Empty Fields', 'gravity-forms-pdf-extended' ),
				'desc' => esc_html__( 'Display Empty fields in the PDF.', 'gravity-forms-pdf-extended' ),
				'type' => 'toggle',
			]
		);
	}

	/**
	 * Return the optional template-specific header field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_header_field() {
		return apply_filters(
			'gfpdf_header_field_setting',
			[
				'id'         => 'header',
				'name'       => esc_html__( 'Header', 'gravity-forms-pdf-extended' ),
				'type'       => 'rich_editor',
				'size'       => 8,
				'desc'       => sprintf( esc_html__( 'The header is included at the top of each page. For simple columns %1$stry this HTML table snippet%2$s.', 'gravity-forms-pdf-extended' ), '<a href="https://gist.github.com/jakejackson1/997b5dedf0a5e665e8ef">', '</a>' ),
				'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			]
		);
	}

	/**
	 * Return the optional template-specific first page header field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_first_page_header_field() {
		return apply_filters(
			'gfpdf_first_page_header_field_setting',
			[
				'id'         => 'first_header',
				'name'       => esc_html__( 'First Page Header', 'gravity-forms-pdf-extended' ),
				'type'       => 'rich_editor',
				'size'       => 8,
				'desc'       => esc_html__( 'Override the header on the first page of the PDF.', 'gravity-forms-pdf-extended' ),
				'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
				'toggle'     => esc_html__( 'Use different header on first page of PDF?', 'gravity-forms-pdf-extended' ),
			]
		);
	}

	/**
	 * Return the optional template-specific footer field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_footer_field() {
		return apply_filters(
			'gfpdf_footer_field_setting',
			[
				'id'         => 'footer',
				'name'       => esc_html__( 'Footer', 'gravity-forms-pdf-extended' ),
				'type'       => 'rich_editor',
				'size'       => 8,
				'desc'       => sprintf( esc_html__( 'The footer is included at the bottom of every page. For simple text footers use the left, center and right alignment buttons in the editor. For simple columns %1$stry this HTML table snippet%2$s. Use the special %3$s{PAGENO}%4$s and %3$s{nbpg}%4$s tags to display page numbering. ', 'gravity-forms-pdf-extended' ), '<a href="https://gist.github.com/jakejackson1/e6179a96cd97ef0a8457">', '</a>', '<em>', '</em>' ),
				'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			]
		);
	}

	/**
	 * Return the optional template-specific first page footer field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_first_page_footer_field() {
		return apply_filters(
			'gfpdf_first_page_footer_field_setting',
			[
				'id'         => 'first_footer',
				'name'       => esc_html__( 'First Page Footer', 'gravity-forms-pdf-extended' ),
				'type'       => 'rich_editor',
				'size'       => 8,
				'desc'       => esc_html__( 'Override the footer on the first page of the PDF.', 'gravity-forms-pdf-extended' ),
				'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
				'toggle'     => esc_html__( 'Use different footer on first page of PDF?', 'gravity-forms-pdf-extended' ),
			]
		);
	}

	/**
	 * Return the optional template-specific background color field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_background_color_field() {
		return apply_filters(
			'gfpdf_background_color_field_setting',
			[
				'id'   => 'background_color',
				'name' => esc_html__( 'Background Color', 'gravity-forms-pdf-extended' ),
				'type' => 'color',
				'std'  => '#FFF',
				'desc' => esc_html__( 'Set the background color for all pages.', 'gravity-forms-pdf-extended' ),
			]
		);
	}

	/**
	 * Return the optional template-specific background image field
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_background_image_field() {
		return apply_filters(
			'gfpdf_background_image_field_setting',
			[
				'id'   => 'background_image',
				'name' => esc_html__( 'Background Image', 'gravity-forms-pdf-extended' ),
				'type' => 'upload',
				'desc' => esc_html__( 'The background image is included on all pages. For optimal results, use an image the same dimensions as the paper size and run it through an image optimization tool before upload.', 'gravity-forms-pdf-extended' ),
			]
		);
	}
}
