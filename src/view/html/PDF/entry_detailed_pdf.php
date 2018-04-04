<?php

/**
 * The "View PDF" link for the entry list page
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

?>

<strong><?php esc_html_e( 'PDFs', 'gravity-forms-pdf-extended' ); ?></strong><br/>

<?php foreach ( $args['pdfs'] as $pdf ): ?>
	<div class="gfpdf_detailed_pdf_container">
		<span><?php echo esc_html( $pdf['name'] ); ?></span>
		<div>
			<a href="<?php echo esc_url( $pdf['view'] ); ?>" target="_blank" class="button"><?php esc_html_e( 'View', 'gravity-forms-pdf-extended' ); ?></a>
			<a href="<?php echo esc_url( $pdf['download'] ); ?>" class="button"><?php esc_html_e( 'Download', 'gravity-forms-pdf-extended' ); ?></a>
		</div>
	</div>
<?php endforeach; ?>
