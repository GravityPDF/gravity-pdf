<?php

/**
 * List of Form Settings View
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

/* assign list_items object to variable for easier access */
$list_items = $args['list_items'];

?>

<?php \GFFormSettings::page_header( $args['title'] ); ?>

<h3>
	<span>
		<i class="fa fa-file-o"></i>
		<?php echo $args['title']; ?>
		<a id="add-new-pdf" class="add-new-h2" href="<?php echo esc_url($args['add_new_url']); ?>"><?php esc_html_e( 'Add New', 'gravityforms' ) ?></a>
	</span>
</h3>

<form id="gfpdf_list_form" method="post">
	<?php $list_items->display(); ?>
</form>

<div id="delete-confirm" title="<?php esc_attr_e( 'Delete PDF?', 'gravity-forms-pdf-extended' ); ?>" style="display: none;">
	<?php esc_html_e( "Warning! You are about to delete this PDF. Select 'Delete' to delete, 'Cancel' to stop.", 'gravity-forms-pdf-extended' ); ?>
</div>


<?php \GFFormSettings::page_footer(); ?>
