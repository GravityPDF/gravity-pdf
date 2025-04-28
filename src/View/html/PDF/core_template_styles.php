<?php

/**
 * The styles needed to display our core PDF styles like header, footer, font and colour
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */

use GFPDF\Statics\Kses;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var    $settings array
 * @var    $form     array
 * @var    $entry    array
 * @global $gfpdf
 */

$font        = $settings['font'] ?? 'DejavuSansCondensed';
$font_colour = $settings['font_colour'] ?? '#333';
$font_size   = $settings['font_size'] ?? '9';

$header       = $settings['header'] ?? '';
$footer       = $settings['footer'] ?? '';
$first_header = $settings['first_header'] ?? '';
$first_footer = $settings['first_footer'] ?? '';

$background_color      = $settings['background_color'] ?? '#FFF';
$background_image      = $gfpdf->gform->process_tags( $settings['background_image'] ?? '', $form, $entry );
$background_image_path = ! empty( $background_image ) ? $gfpdf->misc->convert_url_to_path( $background_image ) : false;

$contrast                  = $gfpdf->misc->get_background_and_border_contrast( $background_color );
$contrast_background_color = $contrast['background'];
$contrast_border_color     = $contrast['border'];

/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_include_list_styles/ for more details about this filter */
$include_list_styles = apply_filters( 'gfpdf_include_list_styles', true, $settings );

/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_include_product_styles/ for more details about this filter */
$include_product_styles = apply_filters( 'gfpdf_include_product_styles', true, $settings );

?>

<style>
	@page {
		margin: 10mm;

	<?php if ( ! empty( $header ) ) : ?>
		header: html_TemplateHeader;
		margin-header: 5mm;
	<?php endif; ?>

	<?php if ( ! empty( $footer ) ) : ?>
		footer: html_TemplateFooter;
		margin-footer: 5mm;
	<?php endif; ?>

	<?php if ( ! empty( $background_color ) ) : ?>
		background-color: <?php echo esc_html( $background_color ); ?>;
	<?php endif; ?>

	<?php if ( ! empty( $background_image ) ) : ?>
		background-image: url(<?php echo $background_image_path !== false ? esc_attr( $background_image_path ) : esc_url_raw( $background_image ); ?>) no-repeat 0 0;
		background-image-resize: 4;
	<?php endif; ?>
	}

	@page :first {
	<?php if ( ! empty( $first_header ) ) : ?>
		header: html_TemplateFirstHeader;
		margin-header: 5mm;
	<?php endif; ?>

	<?php if ( ! empty( $first_footer ) ) : ?>
		footer: html_TemplateFirstFooter;
		margin-footer: 5mm;
	<?php endif; ?>
	}

	body, th, td, li, a {
		color: <?php echo esc_html( $font_colour ); ?>;
		font-size: <?php echo esc_html( $font_size ); ?>pt;
		font-family: <?php echo esc_html( $font ); ?>, sans-serif;
	}

	.header-footer-img {
		width: auto !important;
		max-height: 25mm;
	}

	/* List Field Styles */
	<?php if ( $include_list_styles ) : ?>
	.gfield_list {
		border-collapse: collapse;
		border: 1px solid #000;
		border-color: <?php echo esc_html( $contrast_border_color ); ?>;
		margin: 2px 0 6px;
		padding: 0;
		width: 100%;
	}

	.gfield_list th {
		text-align: left;
		background-color: <?php echo esc_html( $contrast_background_color ); ?>;
		border: 1px solid #000;
		border-color: <?php echo esc_html( $contrast_border_color ); ?>;
		font-weight: bold;
		padding: 6px 10px;
	}

	.gfield_list td {
		padding: 6px 10px;
		border: 1px solid #000;
		border-color: <?php echo esc_html( $contrast_border_color ); ?>;
	}

	<?php endif; ?>

	/* Product Field Styles */
	<?php if ( $include_product_styles ) : ?>
	table.entry-products th {
		background-color: <?php echo esc_html( $contrast_background_color ); ?>;
		border-bottom: 1px solid #000;
		border-right: 1px solid #000;
		border-bottom-color: <?php echo esc_html( $contrast_border_color ); ?>;
		border-right-color: <?php echo esc_html( $contrast_border_color ); ?>;
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
		border: 1px solid #000;
		border-color: <?php echo esc_html( $contrast_border_color ); ?>;
		margin: 5px 0 3px;
	}

	table.entry-products td {
		border-bottom: 1px solid #000;
		border-right: 1px solid #000;
		border-bottom-color: <?php echo esc_html( $contrast_border_color ); ?>;
		border-right-color: <?php echo esc_html( $contrast_border_color ); ?>;
		padding: 7px 7px 8px;
		vertical-align: top;
	}

	table.entry-products td.emptycell {
		background-color: <?php echo esc_html( $contrast_background_color ); ?>;
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

	/* Consent Field */
	.consent-text {
		font-size: 85%;
	}

	.consent-text a,
	.consent-text li,
	.consent-text td,
	.consent-text th {
		font-size: 100%;
	}

	.consent-tick {
		font-size: 150%;
	}

	/* Repeater */
	.gfpdf-repeater,
	.gfpdf-form {
		margin-bottom: 1.5%;
	}

	.repeater-container {
		margin: 1% 0;
		padding-left: 2%;
		border-left: 1px solid #000;
	}

	/* Chained Select */
	.gfpdf-chainedselect td:nth-child(1) {
		width: 30%;
	}

	/* Rich Text Paragraph */
	.gfpdf-textarea .value p {
	  margin-top: 0;
	}

	/* Quiz image */
	.gf-quiz-img {
	  width: 20px;
	}
</style>

<?php if ( ! empty( $first_header ) ) : ?>
	<htmlpageheader name="TemplateFirstHeader">
		<div id="first_header">
			<?php Kses::output( $first_header ); ?>
		</div>
	</htmlpageheader>
<?php endif; ?>

<?php if ( ! empty( $header ) ) : ?>
	<htmlpageheader name="TemplateHeader">
		<div id="header">
			<?php Kses::output( $header ); ?>
		</div>
	</htmlpageheader>
<?php endif; ?>

<?php if ( ! empty( $first_footer ) ) : ?>
	<htmlpagefooter name="TemplateFirstFooter">
		<div id="first_footer">
			<?php Kses::output( $first_footer ); ?>
		</div>
	</htmlpagefooter>
<?php endif; ?>

<?php if ( ! empty( $footer ) ) : ?>
	<htmlpagefooter name="TemplateFooter">
		<div class="footer">
			<?php Kses::output( $footer ); ?>
		</div>
	</htmlpagefooter>
<?php endif; ?>

<?php
/* See https://docs.gravitypdf.com/v6/developers/actions/gfpdf_core_template for more details about this hook */
do_action( 'gfpdf_core_template', $form, $entry, $settings );
do_action( 'gfpdf_core_template_' . $form['id'], $form, $entry, $settings );
?>
