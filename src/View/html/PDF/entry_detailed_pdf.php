<?php

/**
 * The "View PDF" link for the entry list page
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php do_action( 'gfpdf_entry_detail_pre_pdf_title_markup', $args['pdfs'] ); ?>

<?php do_action( 'gfpdf_entry_detail_post_pdf_title_markup', $args['pdfs'] ); ?>

<div id="gfpdf_detailed_pdf_wrapper">
<?php foreach ( $args['pdfs'] as $pdf ): ?>
	<?php do_action( 'gfpdf_entry_detail_pre_container_markup', $pdf, $args['pdfs'] ); ?>

	<div class="gfpdf_detailed_pdf_container">

		<?php do_action( 'gfpdf_entry_detail_pre_pdf_name_markup', $pdf, $args['pdfs'] ); ?>
		<div><?php echo esc_html( $pdf['name'] ); ?></div>
		<?php do_action( 'gfpdf_entry_detail_post_pdf_name_markup', $pdf, $args['pdfs'] ); ?>

		<div class="gfpdf_detailed_pdf_cta">
			<?php do_action( 'gfpdf_entry_detail_pre_pdf_links_markup', $pdf, $args['pdfs'] ); ?>
			<a href="<?php echo esc_url( $pdf['view'] ); ?>"
			   target="_blank"><?php esc_html_e( 'View', 'gravity-forms-pdf-extended' ); ?></a> |
			<a href="<?php echo esc_url( $pdf['download'] ); ?>"><?php esc_html_e( 'Download', 'gravity-forms-pdf-extended' ); ?></a>
			<?php do_action( 'gfpdf_entry_detail_post_pdf_links_markup', $pdf, $args['pdfs'] ); ?>
		</div>
	</div>
	<?php do_action( 'gfpdf_entry_detail_post_container_markup', $pdf, $args['pdfs'] ); ?>
<?php endforeach; ?>
</div>

<?php do_action( 'gfpdf_entry_detail_post_markup', $pdf, $args['pdfs'] ); ?>
