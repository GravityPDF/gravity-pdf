<?php

/**
 * The "View PDF" link for the entry list page
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
	This file is part of Gravity PDF.

	Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

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

?>

<?php do_action( 'gfpdf_entry_detail_pre_pdf_title_markup', $args['pdfs'] ); ?>

<strong><?php esc_html_e( 'PDFs', 'gravity-forms-pdf-extended' ); ?></strong><br />

<?php do_action( 'gfpdf_entry_detail_post_pdf_title_markup', $args['pdfs'] ); ?>

<?php foreach ( $args['pdfs'] as $pdf ): ?>
	<?php do_action( 'gfpdf_entry_detail_pre_container_markup', $pdf, $args['pdfs'] ); ?>

	<div class="gfpdf_detailed_pdf_container">
		<?php do_action( 'gfpdf_entry_detail_pre_pdf_name_markup', $pdf, $args['pdfs'] ); ?>
		<span><?php echo esc_html( $pdf['name'] ); ?></span>
		<?php do_action( 'gfpdf_entry_detail_post_pdf_name_markup', $pdf, $args['pdfs'] ); ?>

		<div>
			<?php do_action( 'gfpdf_entry_detail_pre_pdf_links_markup', $pdf, $args['pdfs'] ); ?>
			<a href="<?php echo esc_url( $pdf['view'] ); ?>" target="_blank" class="button"><?php esc_html_e( 'View', 'gravity-forms-pdf-extended' ); ?></a>
			<a href="<?php echo esc_url( $pdf['download'] ); ?>" class="button"><?php esc_html_e( 'Download', 'gravity-forms-pdf-extended' ); ?></a>
			<?php do_action( 'gfpdf_entry_detail_post_pdf_links_markup', $pdf, $args['pdfs'] ); ?>
		</div>
	</div>

	<?php do_action( 'gfpdf_entry_detail_post_container_markup', $pdf, $args['pdfs'] ); ?>
<?php endforeach; ?>

<?php do_action( 'gfpdf_entry_detail_post_markup', $pdf, $args['pdfs'] ); ?>
