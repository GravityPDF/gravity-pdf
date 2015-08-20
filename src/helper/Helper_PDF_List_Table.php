<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Options;

use WP_List_Table;


/**
 * WP_List_Table Helper Controller
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	/**
	 * Our Gravity Form array
	 * @var Array
	 * @since 4.0
	 */
	public $form;

	/**
	 * Holds abstracted functions related to the forms plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $form_plugin;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 * @var Object
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Setup our class with appropriate data (columns, form
	 * @param  array $form A Gravity Form meta data array
	 * @since 4.0
	 */
	public function __construct( $form, Helper_Abstract_Form $form_plugin, Helper_Misc $misc, Helper_Options $options ) {

		/* Assign our internal variables */
		$this->form        = $form;
		$this->form_plugin = $form_plugin;
		$this->misc        = $misc;
		$this->options     = $options;

		/* Cache column header internally so we don't have to work with the global get_column_headers() function */
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			array(),
		);

		parent::__construct();
	}

	/**
	 * Return the columns that should be used in the list table
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
				'cb'            => '',
				'name'          => __( 'Name', 'gravitypdf' ),
				'shortcode'     => __( 'Download Shortcode', 'gravitypdf' ),
				'template'      => __( 'Template', 'gravitypdf' ),
				'notifications' => __( 'Notifications', 'gravitypdf' ),
		);

		$columns = apply_filters( 'gfpdf_pdf_list_columns', $columns );

		return $columns;
	}

	/**
	 * Get the name of the default primary column.
	 * @return string Name of the default primary column, in this case, 'name'
	 * @since 4.0
	 */
	protected function get_default_primary_column_name() {
		return 'name';
	}

	/**
	 * Prepares the list of items for displaying.
	 * @since 4.0
	 */
	public function prepare_items() {
		$this->items = ( isset( $this->form['gfpdf_form_settings'] ) ) ? $this->form['gfpdf_form_settings'] : array();
	}

	/**
	 * Display our table
	 * @since 4.0
	 */
	public function display() {

		$singular = rgar( $this->_args, 'singular' );
		?>

		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
				<tr>
					<?php $this->print_column_headers(); ?>
				</tr>
			</thead>

			<tbody id="the-list" <?php if ( $singular ) { echo " class='list:$singular'"; } ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tfoot>
				<tr>
					<?php $this->print_column_headers( false ); ?>
				</tr>
			</tfoot>

		</table>

		<?php
	}

	/**
	 * Output the single table row
	 * @param  array $item The table row being processed
	 * @since 4.0
	 */
	public function single_row( $item ) {
		static $row_class = '';

		$row_class = ( $row_class == '' ? 'class="alternate"' : $row_class );

		echo '<tr id="gfpdf-' . $item['id'] . '" ' . $row_class . '>';
		echo $this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Default column handler
	 * Used when not custom column public function exists
	 * @param  array $item The table row being processed
	 * @since 4.0
	 */
	public function column_default( $item, $column ) {
		echo rgar( $item, $column );
	}

	/**
	 * Custom public function for displaying the 'cb' column
	 * Used to handle active / inactive PDFs
	 * @param  array $item The table row being processed
	 * @since 4.0
	 */
	public function column_cb( $item ) {

		$is_active   = isset( $item['active'] ) ? $item['active'] : true;
		$form_id     = rgget( 'id' );
		$state_nonce = wp_create_nonce( "gfpdf_state_nonce_{$form_id}_{$item['id']}" );
		?>

		<img data-id="<?php echo $item['id'] ?>" data-nonce="<?php echo $state_nonce; ?>" data-fid="<?php echo $form_id; ?>" src="<?php echo $this->form_plugin->get_plugin_url() ?>/images/active<?php echo intval( $is_active ) ?>.png" style="cursor: pointer;margin:-1px 0 0 8px;" alt="<?php $is_active ? __( 'Active', 'gravitypdf' ) : __( 'Inactive', 'gravitypdf' ); ?>" title="<?php echo $is_active ? __( 'Active', 'gravitypdf' ) : __( 'Inactive', 'gravitypdf' ); ?>"/>
		
		<?php
	}

	/**
	 * Custom public function for displaying the 'notifications' column
	 * Display comma separated list of active notifications, otherwise display 'None'
	 * @param  array $item The table row being processed
	 * @since 4.0
	 */
	public function column_notifications( $item ) {
		if ( ! isset($item['notification']) || sizeof( $item['notification'] ) == 0 ) {
			_e( 'None', 'gravitypdf' );
			return;
		}

		echo implode( ', ', $item['notification'] );
	}

	/**
	 * Translates the template raw name to a user-friendly name
	 * @param  array $item The table row being processed
	 * @since 4.0
	 */
	public function column_shortcode( $item ) {
		$shortcode = '[gravitypdf id="'. $item['id'] . '" text="' . __( 'Download PDF', 'gravitypdf' ) . '"]';
		echo '<input type="text" class="gravitypdf_shortcode" value="'. esc_html( $shortcode ) .'" readonly="readonly" onfocus="jQuery(this).select();" onclick="jQuery(this).select();" />';
	}

	/**
	 * Translates the template raw name to a user-friendly name
	 * @param  array $item The table row being processed
	 * @since 4.0
	 */
	public function column_template( $item ) {

		$template = $this->options->get_template_information( $item['template'] );

		if ( is_array( $template ) && isset($template['template']) ) {
			echo "<strong>{$template['group']} – </strong> {$template['template']}";
		} else {
			echo "<strong>{$template['group']}</strong> – " . $this->misc->human_readable( rgar( $item, 'template' ) );
		}
	}

	/**
	 * Add column actions to allow edit, duplication and deletion
	 * @param  array $item The table row being processed
	 * @since 4.0
	 */
	public function column_name( $item ) {
		$edit_url        = add_query_arg( array( 'pid' => $item['id'] ) );
		$form_id         = rgget( 'id' );
		$duplicate_nonce = wp_create_nonce( "gfpdf_duplicate_nonce_{$form_id}_{$item['id']}" );
		$delete_nonce    = wp_create_nonce( "gfpdf_delete_nonce_{$form_id}_{$item['id']}" );

		$actions  = apply_filters(
			'gfpdf_pdf_actions', array(
				'edit'      => '<a title="' . __( 'Edit this PDF', 'gravitypdf' ) . '" href="' . $edit_url . '">' . __( 'Edit', 'gravitypdf' ) . '</a>',
				'duplicate' => '<a title="' . __( 'Duplicate this PDF', 'gravitypdf' ) . '" data-id="' . $item['id'] . '" class="submitduplicate" data-nonce="'. $duplicate_nonce .'"  data-fid="'. $form_id . '">' . __( 'Duplicate', 'gravitypdf' ) . '</a>',
				'delete'    => '<a title="' . __( 'Delete this PDF', 'gravitypdf' ) . '" class="submitdelete" data-id="' . $item['id'] . '" data-nonce="'. $delete_nonce .'" data-fid="'. $form_id .'">' . __( 'Delete', 'gravitypdf' ) . '</a>',
			)
		);

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

	/**
	 * Copy to display when no PDF configuration options exist
	 * @since 4.0
	 */
	public function no_items() {
		printf( __( "This form doesn't have any PDFs. Let's go %screate one%s.", 'gravitypdf' ), "<a href='" . add_query_arg( array( 'pid' => 0 ) ) . "'>", '</a>' );
	}
}
