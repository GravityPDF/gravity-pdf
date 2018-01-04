<?php

/**
 * The Review Plugin Notice
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

<div style="font-size:15px; line-height: 25px">

	<strong><?php esc_html_e( "Hey, we just noticed you've generated your 100th PDF using Gravity PDF!", 'gravity-forms-pdf-extended' ); ?></strong>

	<br>

	<?php printf( esc_html__( "If you love how much time you've saved using Gravity PDF then do us a big favor and %sgive it a five-star rating on WordPress.org%s.", 'gravity-forms-pdf-extended' ), '<a href="https://wordpress.org/support/view/plugin-reviews/gravity-forms-pdf-extended">', '</a>' ); ?>

	<br>

	<?php printf( esc_html__( '%sOr let your Twitter follows know how good it is%s (or anyone else for that matter).', 'gravity-forms-pdf-extended' ), '<a href="https://goo.gl/07NhJQ">', '</a>' ); ?>
</div>
