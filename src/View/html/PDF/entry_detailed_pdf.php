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

/** @var $args array */

?>

<?php do_action( 'gfpdf_entry_detail_pre_markup', $args['pdfs'] ); ?>
<ul>
	<?php foreach ( $args['pdfs'] as $pdf ): ?>
		<?php do_action( 'gfpdf_entry_detail_pre_container_markup', $pdf, $args['pdfs'] ); ?>

		<li class="gfpdf_detailed_pdf_container" >

			<?php do_action( 'gfpdf_entry_detail_pre_pdf_name_markup', $pdf, $args['pdfs'] ); ?>

			<div><?= esc_html( $pdf['name'] ); ?></div>

			<?php do_action( 'gfpdf_entry_detail_post_pdf_name_markup', $pdf, $args['pdfs'] ); ?>

			<div class="gfpdf_detailed_pdf_cta" aria-label="View or Download <?= $pdf['settings']['filename'] ?>.pdf ">
				<?php do_action( 'gfpdf_entry_detail_pre_pdf_links_markup', $pdf, $args['pdfs'] ); ?>

				<a href="<?= esc_url( $pdf['view'] ) ?>" target="_blank"><?= esc_html__( 'View', 'gravity-forms-pdf-extended' ) ?></a> |
				<a href="<?= esc_url( $pdf['download'] ) ?>"><?= esc_html__( 'Download', 'gravity-forms-pdf-extended' ); ?></a>

				<?php do_action( 'gfpdf_entry_detail_post_pdf_links_markup', $pdf, $args['pdfs'] ); ?>
			</div>
		</li>

		<?php do_action( 'gfpdf_entry_detail_post_container_markup', $pdf, $args['pdfs'] ); ?>

	<?php endforeach; ?>
</ul>
<?php do_action( 'gfpdf_entry_detail_post_markup', $args['pdfs'] ); ?>
