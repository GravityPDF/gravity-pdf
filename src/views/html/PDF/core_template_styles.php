<?php

/**
 * The styles needed to display our core PDF styles like header, footer, font and colour
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if (! defined('ABSPATH')) {
    exit;
}

$font                 = ( ! empty( $settings['font'] ) ) 			? $settings['font'] : 'DejavuSansCondensed';
$font_colour          = ( ! empty( $settings['font_colour'] ) ) 	? $settings['font_colour'] : '#333';
$font_size            = ( ! empty( $settings['font_size'] ) ) 		? $settings['font_size'] : '9';

$header               = ( ! empty( $settings['header'] ) ) 			? $settings['header'] : '';
$footer               = ( ! empty( $settings['footer'] ) ) 			? $settings['footer'] : '';
$first_header         = ( ! empty( $settings['first_header'] ) ) 	? $settings['first_header'] : '';
$first_footer         = ( ! empty( $settings['first_footer'] ) ) 	? $settings['first_footer'] : '';

$background_img       = ( ! empty( $settings['background'] ) )  	? $settings['background'] : '';

?>


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

	body, table th, table td, ul li, ol li {
        color: <?php echo $font_colour; ?>;
        font-size: <?php echo $font_size; ?>pt;
        font-family: <?php echo $font; ?>, sans-serif;
    }

    .header-footer-img {
        width: auto !important;
        max-height: 25mm;
    }

</style>

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

<htmlpagefooter name="TemplateFirstFooter">
    <div id="first_footer">
        <?php echo $first_footer; ?>
    </div>
</htmlpagefooter>

<htmlpagefooter name="TemplateFooter">
    <div class="footer">
        <?php echo $footer; ?>
    </div>
</htmlpagefooter>
