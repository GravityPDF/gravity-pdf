<?php

/*
 * Template Name: Rubix
 * Version: 1.1
 * Description: Rubix uses stylish containers to create an aesthetically pleasing design.
 * Author: Gravity PDF
 * Group: Core
 * License: GPLv2
 * Required PDF Version: 4.0-alpha
 */

/* Prevent direct access to the template */
if ( ! class_exists('GFForms')) {
    return;
}

/*
 * All Gravity PDF 4.x templates have access to the following variables:
 *
 * $form (The current Gravity Form array)
 * $entry (The raw entry data)
 * $form_data (The processed entry data stored in an array)
 * $settings (the current PDF configuration)
 * $fields (an array of Gravity Form fields which can be accessed with their ID number)
 * $config (The initialised template config class – eg. /config/rubix.php)
 * $gfpdf (the main Gravity PDF object containing all our helper classes)
 * $args (contains an array of all variables - the ones being described right now - passed to the template)
 */

/*
 * Load up our template-specific appearance settings
 */
$container_background_color = ( ! empty( $settings['rubix_container_background_colour'] ) ) ? $settings['rubix_container_background_colour'] : '#eeeeee';

/* Create a constrasting border colour for our tables */
$misc     = GPDFAPI::get_misc_class();
$contrast = $misc->get_background_and_border_contrast( $container_background_color );

?>

<!-- Include styles needed for the PDF -->
<style>

    /* Handle Gravity Forms CSS Ready Classes */
    .row-separator {
        clear: both;
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
        width: 32.3%;
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

    /*
     * Headings
     */
    h3 {
        margin: 1.5mm 0 0.5mm;
        padding: 0;
    }

    /*
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

    /*
     * Survey Style Support
     */
    .gsurvey-likert-choice-label {
        padding: 4px;
    }

    .gsurvey-likert-choice, .gsurvey-likert-choice-label {
        text-align: center;
    }

    /*
     * Terms of Service (Gravity Perks) Support
     */
    .terms-of-service-agreement {
        padding-top: 2px;
        font-weight: bold;
    }

    .terms-of-service-tick {
        font-size: 150%;
    }

    /*
     * List Support
     */
    ul, ol {
        margin: 0;
        padding-left: 1mm;
        padding-right: 1mm;
    }

    li {
        margin: 0;
        padding: 0;
        list-style-position: inside;
    }

    /*
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

    /*
     * Independant Template Styles
     */
    #form_title {
        text-align: center;
        text-transform: uppercase;
        font-size: 22px;
    }

    .row-separator {
        margin-bottom: 15px;
    }

    .gfpdf-page, .product-field-title {
        margin-bottom: -13px;
        margin-left: 10px;
    }

    .gfpdf-field .inner-container {
        background: <?php echo $container_background_color; ?>;
        border-radius: 10px;
    }

    .gfpdf-field .label {
        padding: 5px 10px 0;
    }

    .gfpdf-field .value {
        padding: 0 10px 5px;
    }

    .gfpdf-products .inner-container,
    div.gfpdf-html .value {
        padding: 5px 10px;
    }

    div.gfpdf-section-description .inner-container {
        background: none;
        border-radius: 0;
    }

    .gfpdf-section-title {
        padding-left: 10px;
    }

    .gfpdf-section-title h3 {
        margin-top: 0;
        padding-top: 0;
    }

    .gfield_list th,
    table.entry-products th, table.entry-products td.emptycell {
        background-color: <?php echo $contrast['background']; ?>;
    }

    .gfield_list th, .gfield_list td,
    table.entry-products th, table.entry-products td {
        border: 1px solid <?php echo $contrast['border']; ?>;
    }

</style>

<!-- Output our HTML markup -->
<?php

/*
 * Load our core-specific styles from our PDF settings which will be passed to the PDF template $config array
 */
$show_form_title      = ( ! empty( $settings['show_form_title'] ) && $settings['show_form_title'] == 'Yes' ) ?              true : false;
$show_page_names      = ( ! empty( $settings['show_page_names'] ) && $settings['show_page_names'] == 'Yes' ) ?              true : false;
$show_html            = ( ! empty( $settings['show_html'] ) && $settings['show_html'] == 'Yes' ) ?                          true : false;
$show_section_content = ( ! empty( $settings['show_section_content'] ) && $settings['show_section_content'] == 'Yes' ) ?    true : false;
$enable_conditional   = ( ! empty( $settings['enable_conditional'] ) && $settings['enable_conditional'] == 'Yes' ) ?        true : false;
$show_empty           = ( ! empty( $settings['show_empty'] ) && $settings['show_empty'] == 'Yes' ) ?                        true : false;

/**
 * Set up our configuration array to control what is and is not shown in the generated PDF
 *
 * @var array
 */
$html_config = array(
    'settings' => $settings,
    'meta'     => array(
        'echo'                     => true, /* whether to output the HTML or return it */
        'exclude'                  => true, /* whether we should exclude fields with a CSS value of 'exclude'. Default to true */
        'empty'                    => $show_empty, /* whether to show empty fields or not. Default is false */
        'conditional'              => $enable_conditional, /* whether we should skip fields hidden with conditional logic. Default to true. */
        'show_title'               => $show_form_title, /* whether we should show the form title. Default to true */
        'section_content'          => $show_section_content, /* whether we should include a section breaks content. Default to false */
        'page_names'               => $show_page_names, /* whether we should show the form's page names. Default to false */
        'html_field'               => $show_html, /* whether we should show the form's html fields. Default to false */
        'individual_products'      => false, /* Whether to show individual fields in the entry. Default to false - they are grouped together at the end of the form */
        'enable_css_ready_classes' => true, /* Whether to enable or disable Gravity Forms CSS Ready Class support in your PDF */
    ),
);

/*
 * Generate our HTML markup
 *
 * You can access Gravity PDFs common functions and classes through our API wrapper class "GPDFAPI"
 */
$pdf = GPDFAPI::get_pdf_class();
$pdf->process_html_structure($entry, GPDFAPI::get_pdf_class('model'), $html_config);
