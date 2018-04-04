<?php

/**
 * The styles needed to display our core PDF styles like header, footer, font and colour
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

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

$font                       = ( ! empty( $settings['font'] ) ) ? $settings['font'] : 'DejavuSansCondensed';
$font_colour                = ( ! empty( $settings['font_colour'] ) ) ? $settings['font_colour'] : '#333';
$font_size                  = ( ! empty( $settings['font_size'] ) ) ? $settings['font_size'] : '9';

$header                     = ( ! empty( $settings['header'] ) ) ? $settings['header'] : '';
$footer                     = ( ! empty( $settings['footer'] ) ) ? $settings['footer'] : '';
$first_header               = ( ! empty( $settings['first_header'] ) ) ? $settings['first_header'] : '';
$first_footer               = ( ! empty( $settings['first_footer'] ) ) ? $settings['first_footer'] : '';

$background_color           = ( ! empty( $settings['background_color'] ) ) ? $settings['background_color'] : '#FFF';
$background_image           = ( ! empty( $settings['background_image'] ) ) ? $settings['background_image'] : '';

$contrast                   = $gfpdf->misc->get_background_and_border_contrast( $background_color );
$contrast_background_color  = $contrast['background'];
$contrast_border_color      = $contrast['border'];

/* See https://gravitypdf.com/documentation/v4/gfpdf_include_list_styles/ for more details about this filter */
$include_list_styles        = apply_filters( 'gfpdf_include_list_styles', true, $settings );

/* See https://gravitypdf.com/documentation/v4/gfpdf_include_product_styles/ for more details about this filter */
$include_product_styles     = apply_filters( 'gfpdf_include_product_styles', true, $settings );

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

	body, th, td, li, a {
		color: <?php echo $font_colour; ?>;
		font-size: <?php echo $font_size; ?>pt;
		font-family: <?php echo $font; ?>, sans-serif;
	}

	.header-footer-img {
		width: auto !important;
		max-height: 25mm;
	}

	/* List Field Styles */
	<?php if ( $include_list_styles ) : ?>
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
			background-color: <?php echo $contrast_background_color; ?>;
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
			font-weight: bold;
			padding-bottom: 8px;
			padding-top: 7px;
		}

        table.entry-products td.totals,
        table.entry-products .textright {
			text-align: right;
		}
	<?php endif; ?>

	/* Add Basic Table Support */
	table {
		width: 100%;
		border-collapse: collapse;
		overflow: wrap;
	}

	td, th {
		vertical-align: middle;
	}

    /* Page break */
    .pagebreak {
        page-break-before: always;
    }
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

<?php
/* See https://gravitypdf.com/documentation/v4/gfpdf_core_template/ for more details about this hook */
do_action( 'gfpdf_core_template' );
?>
