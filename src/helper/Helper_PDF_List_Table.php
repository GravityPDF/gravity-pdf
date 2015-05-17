<?php

namespace GFPDF\Helper;
use WP_List_Table;
use GFCommon;

/**
 * WP_List_Table Helper Controller 
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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

/**
 * A simple abstract class controlers can extent to share similar variables
 * @since 4.0
 */
class Helper_PDF_List_Table extends WP_List_Table {

	public $form;

	function __construct( $form ) {

		$this->form = $form;

		$this->_column_headers = array(
			array(
				'cb'      => '',
				'name'    => __( 'Name', 'gravityforms' ),
				'template' => __( 'Template', 'gravityforms' ),
				'notifications' => __( 'Notifications', 'gravityforms' )
			),
			array(),
			array(),
		);

		parent::__construct();
	}

	function prepare_items() {
		$this->items = (isset($this->form['gfpdf_form_settings'])) ? $this->form['gfpdf_form_settings'] : array();
	}

	function display() {
		$singular = rgar( $this->_args, 'singular' );
		?>

		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
			</tfoot>

			<tbody id="the-list"<?php if ( $singular ) {
				echo " class='list:$singular'";
			} ?>>

				<?php $this->display_rows_or_placeholder(); ?>

			</tbody>
		</table>

	<?php
	}

	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr id="gfpdf-' . $item['id'] . '" ' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	function column_default( $item, $column ) {
		echo rgar( $item, $column );
	}

	function column_cb( $item ) {
		if ( rgar( $item, 'isDefault' ) ) {
			return;
		}
		$is_active = isset( $item['isActive'] ) ? $item['isActive'] : true;
		?>
		<img src="<?php echo GFCommon::get_base_url() ?>/images/active<?php echo intval( $is_active ) ?>.png" style="cursor: pointer;margin:-5px 0 0 8px;" alt="<?php $is_active ? __( 'Active', 'gravityforms' ) : __( 'Inactive', 'gravityforms' ); ?>" title="<?php echo $is_active ? __( 'Active', 'gravityforms' ) : __( 'Inactive', 'gravityforms' ); ?>" onclick="ToggleActive(this, '<?php echo $item['id'] ?>'); " />
	<?php
	}

	function column_name( $item ) {
		$edit_url = add_query_arg( array( 'pid' => $item['id'] ) );
		$actions  = apply_filters(
			'gform_notification_actions', array(
				'edit'      => '<a title="' . __( 'Edit this PDF', 'pdfextended' ) . '" href="' . $edit_url . '">' . __( 'Edit', 'pdfextended' ) . '</a>',
				'duplicate' => '<a title="' . __( 'Duplicate this PDF', 'pdfextended' ) . '" onclick="javascript: DuplicateNotification(\'' . $item['id'] . '\');" style="cursor:pointer;">' . __( 'Duplicate', 'gravityforms' ) . '</a>',
				'delete'    => '<a title="' . __( 'Delete this PDF', 'pdfextended' ) . '" class="submitdelete" onclick="javascript: if(confirm(\'' . __( 'WARNING: You are about to delete this notification.', 'gravityforms' ) . __( "\'Cancel\' to stop, \'OK\' to delete.", 'gravityforms' ) . '\')){ DeleteNotification(\'' . $item['id'] . '\'); }" style="cursor:pointer;">' . __( 'Delete', 'gravityforms' ) . '</a>'
			)
		);

		if ( isset( $item['isDefault'] ) && $item['isDefault'] ) {
			unset( $actions['delete'] );
		}

		?>

		<a href="<?php echo $edit_url; ?>"><strong><?php echo rgar( $item, 'name' ); ?></strong></a>
		<div class="row-actions">

			<?php
			if ( is_array( $actions ) && ! empty( $actions ) ) {
				$keys     = array_keys( $actions );
				$last_key = array_pop( $keys );
				foreach ( $actions as $key => $html ) {
					$divider = $key == $last_key ? '' : ' | ';
					?>
					<span class="<?php echo $key; ?>">
                        <?php echo $html . $divider; ?>
                    </span>
				<?php
				}
			}
			?>

		</div>

	<?php
	}

	function no_items() {
		printf( __( "This form doesn't have any PDFs. Let's go %screate one%s.", 'gravityforms' ), "<a href='" . add_query_arg( array( 'pid' => 0 ) ) . "'>", '</a>' );
	}
}