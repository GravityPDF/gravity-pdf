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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$font                       = ( ! empty( $settings['font'] ) ) ? $settings['font'] : 'DejavuSansCondensed';
$font_colour                = ( ! empty( $settings['font_colour'] ) ) ? $settings['font_colour'] : '#333';
$font_size                  = ( ! empty( $settings['font_size'] ) ) ? $settings['font_size'] : '9';

$header                     = ( ! empty( $settings['header'] ) ) ? $settings['header'] : '';
$footer                     = ( ! empty( $settings['footer'] ) ) ? $settings['footer'] : '';
$first_header               = ( ! empty( $settings['first_header'] ) ) ? $settings['first_header'] : '';
$first_footer               = ( ! empty( $settings['first_footer'] ) ) ? $settings['first_footer'] : '';

$background_color           = ( ! empty( $settings['background_color'] ) ) ? $settings['background_color'] : '#FFF';
$background_image           = ( ! empty( $settings['background_image'] ) ) ? $settings['background_image'] : '';

$background_color_contrast  = $gfpdf->misc->get_contrast( $background_color );
$contrast_value             = ( $background_color_contrast === '#FFF' ) ? 20 : -20;
$contrast_background_color  = $gfpdf->misc->change_brightness( $background_color, $contrast_value );
$border_contrast            = ( $background_color_contrast === '#FFF' ) ? 60 : -60;
$reverse_border_contrast    = ( $background_color_contrast === '#FFF' ) ? '#000' : '#FFF';
$contrast_border_color      = $gfpdf->misc->change_brightness( $reverse_border_contrast, $border_contrast );

$include_list_styles        = apply_filters( 'gfpdf_include_list_styles', true );
$include_product_styles     = apply_filters( 'gfpdf_include_product_styles', true );

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

	<?php if ( ! empty($background_color) ) : ?>
		background-color: <?php echo $background_color; ?>;
	<?php endif; ?>

	<?php if ( ! empty($background_image) ) : ?>
		background-image: url(<?php echo $background_image; ?>) no-repeat 0 0;
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

	body, table th, table td, ul li, ol li, a {
		color: <?php echo $font_colour; ?>;
		font-size: <?php echo $font_size; ?>pt;
		font-family: <?php echo $font; ?>, sans-serif;
	}

	.header-footer-img {
		width: auto !important;
		max-height: 25mm;
	}

	/* List Field Styles */
	<?php if ( $include_product_styles ) : ?>
		.gfield_list {
			border-collapse: collapse;
			border: 1px solid <?php echo $contrast_border_color; ?>;
			margin: 2px 0 6px;
			padding: 0;
			width: 100%;
		}

		.gfield_list th {
			text-align: left;
			background-color: <?php echo $contrast_background_color; ?>;
			border: 1px solid <?php echo $contrast_border_color; ?>;
			font-weight: bold;
			padding: 6px 10px;
		}

		.gfield_list td {
			padding: 6px 10px;
			border: 1px solid <?php echo $contrast_border_color; ?>;
		}
	<?php endif; ?>

	/* Product Field Styles */
	<?php if ( $include_product_styles ) : ?>
		table.entry-products th {
			background-color: <?php echo $contrast_background_color; ?>#F4F4F4;
			border-bottom: 1px solid <?php echo $contrast_border_color; ?>;
			border-right: 1px solid <?php echo $contrast_border_color; ?> !important;
		}

		table.entry-products td.textcenter, table.entry-products th.textcenter {
			text-align: center;
		}

		table.entry-products .entry-products-col2 {
			width: 10%;
		}

		table.entry-products .entry-products-col3 {
			width: 19%;
		}

		table.entry-products .entry-products-col4 {
			width: 19%;
		}

		table.entry-products {
			border: 1px solid <?php echo $contrast_border_color; ?>;
			margin: 5px 0 3px;
		}

		table.entry-products td {
			border-bottom: 1px solid <?php echo $contrast_border_color; ?>;
			border-right: 1px solid <?php echo $contrast_border_color; ?> !important;
			padding: 7px 7px 8px;
			vertical-align: top;
		}

		table.entry-products td.emptycell {
			background-color: <?php echo $contrast_background_color; ?>;
		}

		table.entry-products td.totals {
			font-size: 13px;
			font-weight: bold;
			padding-bottom: 8px;
			padding-top: 7px;
		}

		table.entry-products td.textright, table.entry-products th.textright {
			text-align: right;
		}
	<?php endif; ?>

</style>

<?php if ( ! empty($first_header) ) : ?>
	<htmlpageheader name="TemplateFirstHeader">
		<div id="first_header">
			<?php echo $first_header; ?>
		</div>
	</htmlpageheader>
<?php endif; ?>

<?php if ( ! empty($header) ) : ?>
	<htmlpageheader name="TemplateHeader">
		<div id="header">
			<?php echo $header; ?>
		</div>
	</htmlpageheader>
<?php endif; ?>

<?php if ( ! empty($first_footer) ) : ?>
	<htmlpagefooter name="TemplateFirstFooter">
		<div id="first_footer">
			<?php echo $first_footer; ?>
		</div>
	</htmlpagefooter>
<?php endif; ?>

<?php if ( ! empty($footer) ) : ?>
	<htmlpagefooter name="TemplateFooter">
		<div class="footer">
			<?php echo $footer; ?>
		</div>
	</htmlpagefooter>
<?php endif; ?>
