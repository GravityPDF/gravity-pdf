<?php

/**
 * Template Name: Zadani
 * Version: 1.0
 * Description: A minimalist business-style template that will generate a well-spaced document great for printing.
 * Author: Gravity PDF
 * Group: Core
 * License: GPLv2
 * Required PDF Version: 4.0
 */

/* Prevent direct access to the template */
if ( ! class_exists( 'GFForms' ) ) {
	return;
}

/**
 * All Gravity PDF 4.x templates have access to the following variables:
 *
 * $form (The current Gravity Form array)
 * $entry (The raw entry data)
 * $lead (alias of $entry)
 * $form_data (The processed entry data stored in an array)
 * $settings (the current PDF configuration)
 * $args (contains an array of all variables - the ones being described right now - passed to the template)
 *
 * The following variables are avaliable for backwards compatibility purposes:
 *
 * $form_id (the current form ID)
 * $lead_ids (an array of the selected entries)
 * $lead_id (the current entry ID)
 *
 * To see the variable structure add "var_dump($variable); exit;" to your PDF template and view in your browser
 */

global $gfpdf;

/**
 * Load up our template-specific appearance settings
 */
$value_border_colour  = ( ! empty( $settings['border_colour']) ) 														? $settings['border_colour'] : '#CCCCCC';
$show_form_title      = ( ! empty( $settings['show_form_title'] ) && $settings['show_form_title'] == 'Yes' ) 			? true : false;
$show_page_names      = ( ! empty( $settings['show_page_names'] ) && $settings['show_page_names'] == 'Yes' ) 			? true : false;
$show_html            = ( ! empty( $settings['show_html'] ) && $settings['show_html'] == 'Yes' ) 						? true : false;
$show_section_content = ( ! empty( $settings['show_section_content'] ) && $settings['show_section_content'] == 'Yes' ) 	? true : false;
$show_hidden          = ( ! empty( $settings['show_hidden'] ) && $settings['show_hidden'] == 'Yes' ) 					? true : false;
$show_empty           = ( ! empty( $settings['show_empty'] ) && $settings['show_empty'] == 'Yes' ) 						? true : false;
$background_img       = ( ! empty( $settings['background'] ) )															? $settings['background'] : '';
$first_header         = ( ! empty( $settings['first_header'] ) ) 														? $gfpdf->misc->fix_header_footer( $settings['first_header'] ) : '';
$header               = ( ! empty( $settings['header'] ) ) 																? $gfpdf->misc->fix_header_footer( $settings['header'] ) : '';
$footer               = ( ! empty( $settings['footer'] ) ) 																? $gfpdf->misc->fix_header_footer( $settings['footer'] ) : '';
$first_footer         = ( ! empty( $settings['first_footer'] ) ) 														? $gfpdf->misc->fix_header_footer( $settings['first_footer'] ) : '';
$font_colour          = ( ! empty( $settings['font_colour'] ) ) 														? $settings['font_colour'] : '#333';
$font                 = ( ! empty( $settings['font'] ) ) 		        												? $settings['font'] : 'DejavuSansCondensed';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title>Gravity PDF</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
        @page {
            margin: 10mm;

			<?php if ( ! empty($header) ) : ?>
                header: html_TemplateHeader;
                margin-header: 5mm;
			<?php endif; ?>
            
			<?php if ( ! empty($footer) ) : ?>
                footer: html_TemplateFooter;
                margin-footer: 5mm;
			<?php endif; ?>

			<?php if ( ! empty($background_img) ) : ?>
				background-image: url(<?php echo $background_img; ?>) no-repeat 0 0;
                background-image-resize: 4;
			<?php endif; ?>
        }

        @page :first {
			<?php if ( ! empty($first_header) ) : ?>
                header: html_TemplateFirstHeader;
                margin-header: 5mm;
			<?php endif; ?>

			<?php if ( ! empty($first_footer) ) : ?>
                footer: html_TemplateFirstFooter;
                margin-footer: 5mm;
			<?php endif; ?>
        }

        body {
			font-family: <?php echo $font; ?>, sans-serif;
            font-size: 9pt;
        }

        body, th , td , ul li, ol li {
			color: <?php echo $font_colour; ?> !important;
        }

        /* Handle Gravity Forms CSS Ready Classes */
        .row-separator {
            clear: both;
            padding: 1.25mm 0;
        }

        .gf_left_half,
        .gf_left_third, .gf_middle_third,
        .gf_list_2col li, .gf_list_3col li, .gf_list_4col li, .gf_list_5col li {
            float: left;
        }

        .gf_right_half,
        .gf_right_third {
            float: right;
        }

        .gf_left_half, .gf_right_half,
        .gf_list_2col li {
            width: 49%;
        }

        .gf_left_third, .gf_middle_third, .gf_right_third,
        .gf_list_3col li {
            width: 32.33%;
        }

        .gf_list_4col li {
            width: 24%;
        }

        .gf_list_5col li {
            width: 19%;
        }

        .gf_left_half, .gf_right_half {
            padding-right: 1%;
        }

        .gf_left_third, .gf_middle_third, .gf_right_third {
            padding-right: 1.505%;
        }

        .gf_right_half, .gf_right_third {
            padding-right: 0;
        }

        /* Don't double float the list items if already floated (mPDF does not support this ) */
        .gf_left_half li, .gf_right_half li,
        .gf_left_third li, .gf_middle_third li, .gf_right_third li {
            width: 100% !important;
            float: none !important;
        }

        /**
         * Headings
         */
        h3 {
            margin: 1.5mm 0 0.5mm;
            padding: 0;
        }

        /**
         * Quiz Style Support
         */
        .gquiz-field {
            color: #666;
        }

        .gquiz-correct-choice {
            font-weight: bold;
            color: black;
        }

        .gf-quiz-img {
            padding-left: 5px !important;
            vertical-align: middle;
        }

        /**
         * Survey Style Support
         */
        .gsurvey-likert-choice-label {
            padding: 4px;
        }

        .gsurvey-likert-choice {
            text-align: center;
        }

        /**
         * Table Support
         */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            font-size: 9pt;
        }

        /**
         * List Support
         */
        ul, ol {
            margin: 0;
            padding-left: 1mm;
        }

        li {
            margin: 0;
            padding: 0 0.5mm 0 0;
            list-style-position: inside;
        }

        /**
         * Header / Footer
         */
        .alignleft {
            float: left;
        }

        .alignright {
            float: right;
        }

        .aligncenter {
            text-align: center;
        }

        p.alignleft {
            text-align: left;
            float: none;
        }

        p.alignright {
            text-align: right;
            float: none;
        }

        .header-footer-img {
            width: auto !important;
            max-height: 25mm;
        }

        /**
         * Independant Template Styles
         */
        .gfpdf-field .label {
            text-transform: uppercase;
            font-size: 8pt;
        }

        .gfpdf-field .value {
			border: 1px solid <?php echo $value_border_colour; ?>;
            padding: 1.5mm 2mm;
        }
        
    </style>

</head>
    <body>
        <htmlpageheader name="TemplateFirstHeader">
            <div id="first_header">
            	<?php echo $first_header; ?>
            </div>
        </htmlpageheader>

        <htmlpageheader name="TemplateHeader">
            <div id="header">
            	<?php echo $header; ?>
            </div>
        </htmlpageheader>

        <htmlpageheader name="TemplateFirstFooter">
            <div id="first_footer">
            	<?php echo $first_footer; ?>
            </div>
        </htmlpageheader>
        
        <htmlpagefooter name="TemplateFooter">
            <div class="footer">
        		<?php echo $footer; ?>
            </div>
        </htmlpagefooter>

		<?php

			/**
			 * Set up our configuration array to control what is and is not generated
			 * @var array
			 */
			$config = array(
				'settings' 	=> $settings,
				'meta' 		=> array(
					'echo'                => true, /* whether to output the HTML or return it */
					'exclude'             => true, /* whether we should exclude fields with a CSS value of 'exclude'. Default to true */
					'empty'               => $show_empty, /* whether to show empty fields or not. Default is false */
					'hidden'              => $show_hidden, /* whether we should skip fields hidden with conditional logic. Default to true. */
					'show_title'          => $show_form_title, /* whether we should show the form title. Default to true */
					'section_content'     => $show_section_content, /* whether we should include a section breaks content. Default to false */
					'page_names'          => $show_page_names, /* whether we should show the form's page names. Default to false */
					'html_field'          => $show_html, /* whether we should show the form's html fields. Default to false */
					'individual_products' => false, /* Whether to show individual fields in the entry. Default to false - they are grouped together at the end of the form */
				),
			);

			/**
			 * Generate our HTML markup
			 *
			 * Gravity PDF uses PHP Namespaces to structure its codebase.
			 *
			 * To keep it simplier for users the PDF template files are in PHP's global namespace, which means you have easy access to all PHP and WordPress' core classes.
			 *
			 * You can access Gravity PDFs common functions and classes through our API wrapper class "GPDFAPI", or use the full namespaces to references classes (GFPDF\View\View_PDF or GFPDF\Model\Model_PDF)
			 */
			$pdf = GPDFAPI::get_pdf_class();
			$pdf->process_html_structure( $entry, GPDFAPI::get_pdf_class( 'model' ), $config );

		?>
    </body>
</html>
