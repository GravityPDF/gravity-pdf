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

<span class="gf_form_toolbar_settings gf_form_action_has_submenu gfpdf_form_action_has_submenu">
   | <a href="#" title="View PDFs" onclick="return false" class=""><?php echo ( $args['view'] == 'download' ) ? esc_html__( 'Download PDFs', 'gravity-forms-pdf-extended' ) : esc_html__( 'View PDFs', 'gravity-forms-pdf-extended' ); ?></a>

    <div class="gf_submenu gfpdf_submenu">
	    <ul>
		    <?php foreach ( $args['pdfs'] as $pdf ): ?>
			    <li>
				    <a href="<?php echo ( $args['view'] == 'download' ) ? esc_url( $pdf['download'] ) : esc_url( $pdf['view'] ); ?>" <?php echo ( $args['view'] != 'download' ) ? 'target="_blank"' : '' ?>><?php echo esc_html( $pdf['name'] ); ?></a>
			    </li>
		    <?php endforeach; ?>
	    </ul>
    </div>
</span>
