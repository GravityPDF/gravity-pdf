<?php

namespace GFPDF\Helper;

use WP_List_Table;


/**
 * WP_List_Table Helper Controller
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

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
 *
 * @since 4.0
 */
class Helper_PDF_List_Table extends WP_List_Table {

	/**
	 * Our Gravity Form array
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $form;

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Setup our class with appropriate data
	 *
	 * @param array                                 $form The Gravity Forms object
	 * @param \GFPDF\Helper\Helper_Abstract_Form    $gform Our abstracted Gravity Forms API
	 * @param \GFPDF\Helper\Helper_Misc             $misc
	 * @param \GFPDF\Helper\Helper_Abstract_Options $options
	 *
	 * @since    4.0
	 */
	public function __construct( $form, Helper_Abstract_Form $gform, Helper_Misc $misc, Helper_Abstract_Options $options ) {

		/* Assign our internal variables */
		$this->form    = $form;
		$this->gform   = $gform;
		$this->misc    = $misc;
		$this->options = $options;

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
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
			'cb'            => '',
			'name'          => __( 'Name', 'gravity-forms-pdf-extended' ),
			'shortcode'     => __( 'Download Shortcode', 'gravity-forms-pdf-extended' ),
			'template'      => __( 'Template', 'gravity-forms-pdf-extended' ),
			'notifications' => __( 'Notifications', 'gravity-forms-pdf-extended' ),
		);

		/* See https://gravitypdf.com/documentation/v4/gfpdf_pdf_list_columns/ for more details about this filter */
		$columns = apply_filters( 'gfpdf_pdf_list_columns', $columns );

		return $columns;
	}

	/**
	 * Get the name of the default primary column.
	 *
	 * @return string Name of the default primary column, in this case, 'name'
	 *
	 * @since 4.0
	 */
	protected function get_default_primary_column_name() {
		return 'name';
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 4.0
	 */
	public function prepare_items() {
		$this->items = ( isset( $this->form['gfpdf_form_settings'] ) ) ? $this->form['gfpdf_form_settings'] : array();
	}

	/**
	 * Display our table
	 *
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
	 *
	 * @param  object $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function single_row( $item ) {
		static $row_class = '';

		$row_class = ( $row_class == '' ? 'class="alternate"' : $row_class );

		echo '<tr id="gfpdf-' . $item['id'] . '" ' . $row_class . '>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Default column handler
	 *
	 * Used when no public method exists for the column being processed
	 * For developers who want to include additional columns using the `gfpdf_pdf_list_columns` filter
	 * there's also an action you can tap into to output the correct column information
	 *
	 * @param  array $item The table row being processed
	 *
	 * @param string $column
	 *
	 * @since 4.0
	 */
	public function column_default( $item, $column ) {

		$action = 'gfpdf_pdf_list_column_' . $column;

		if( has_action( $action ) ) {
			/* See https://gravitypdf.com/documentation/v4/gfpdf_pdf_list_column_id/ for more details about this action */
			do_action( $action, $item );
		} else {
			echo rgar( $item, $column );
		}
	}

	/**
	 * Custom public function for displaying the 'cb' column
	 * Used to handle active / inactive PDFs
	 *
	 * @param  array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_cb( $item ) {

		$is_active   = isset( $item['active'] ) ? $item['active'] : true;
		$form_id     = rgget( 'id' );
		$state_nonce = wp_create_nonce( "gfpdf_state_nonce_{$form_id}_{$item['id']}" );
		?>

		<img data-id="<?php echo $item['id'] ?>" data-nonce="<?php echo $state_nonce; ?>"
		     data-fid="<?php echo $form_id; ?>"
		     src="<?php echo $this->gform->get_plugin_url() ?>/images/active<?php echo intval( $is_active ) ?>.png"
		     style="cursor: pointer;margin:-1px 0 0 8px;"
		     alt="<?php $is_active ? __( 'Active', 'gravity-forms-pdf-extended' ) : __( 'Inactive', 'gravity-forms-pdf-extended' ); ?>"
		     title="<?php echo $is_active ? __( 'Active', 'gravity-forms-pdf-extended' ) : __( 'Inactive', 'gravity-forms-pdf-extended' ); ?>"/>

		<?php
	}

	/**
	 * Custom public function for displaying the 'notifications' column
	 * Display comma separated list of active notifications, otherwise display 'None'
	 *
	 * @param  array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_notifications( $item ) {
		if ( ! isset( $item['notification'] ) || sizeof( $item['notification'] ) == 0 ) {
			_e( 'None', 'gravity-forms-pdf-extended' );

			return;
		}

		/* Convert our IDs to names */
		$notification_names = array();
		foreach ( $this->form['notifications'] as $notification ) {
			if ( in_array( $notification['id'], $item['notification'] ) ) {
				$notification_names[] = $notification['name'];
			}
		}

		echo implode( ', ', $notification_names );
	}

	/**
	 * Translates the template raw name to a user-friendly name
	 *
	 * @param  array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_shortcode( $item ) {

		/**
		 * While esc_attr() used below will ensure no display issues when copied the double quote will cause shortcode parse issues
		 * We'll prevent this by removing them before hand
		 */
		$name = str_replace( '"', '', $item['name'] );

		/* Prepare our shortcode sample */
		$shortcode = '[gravitypdf name="' . esc_attr( $name ) . '" id="' . esc_attr( $item['id'] ) . '" text="' . __( 'Download PDF', 'gravity-forms-pdf-extended' ) . '"]';

		/* Display in a readonly field */
		echo '<input type="text" class="gravitypdf_shortcode" value="' . esc_attr( $shortcode ) . '" readonly="readonly" onfocus="jQuery(this).select();" onclick="jQuery(this).select();" />';
	}

	/**
	 * Translates the template raw name to a user-friendly name
	 *
	 * @param  array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_template( $item ) {

		$template = $this->options->get_template_information( $item['template'] );

		if ( is_array( $template ) && isset( $template['template'] ) ) {
			echo "<strong>{$template['group']} – </strong> {$template['template']}";

			/* Add addendum if version is incompatible */
			if ( ! empty( $template['required_pdf_version'] ) && version_compare( $template['required_pdf_version'], PDF_EXTENDED_VERSION, '>' ) ) {
				echo ' (+ ' . _x( 'needs', 'Required', 'gravity-forms-pdf-extended' ) . ' v' . $template['required_pdf_version'] . ')';
			}

		} else {
			echo "<strong>{$template['group']}</strong> – " . $this->misc->human_readable( rgar( $item, 'template' ) );
		}
	}

	/**
	 * Add column actions to allow edit, duplication and deletion
	 *
	 * @param  array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_name( $item ) {
		$edit_url        = add_query_arg( array( 'pid' => $item['id'] ) );
		$form_id         = rgget( 'id' );
		$duplicate_nonce = wp_create_nonce( "gfpdf_duplicate_nonce_{$form_id}_{$item['id']}" );
		$delete_nonce    = wp_create_nonce( "gfpdf_delete_nonce_{$form_id}_{$item['id']}" );

		$actions = array(
			'edit'      => '<a title="' . __( 'Edit this PDF', 'gravity-forms-pdf-extended' ) . '" href="' . $edit_url . '">' . __( 'Edit', 'gravity-forms-pdf-extended' ) . '</a>',
			'duplicate' => '<a title="' . __( 'Duplicate this PDF', 'gravity-forms-pdf-extended' ) . '" data-id="' . $item['id'] . '" class="submitduplicate" data-nonce="' . $duplicate_nonce . '"  data-fid="' . $form_id . '">' . __( 'Duplicate', 'gravity-forms-pdf-extended' ) . '</a>',
			'delete'    => '<a title="' . __( 'Delete this PDF', 'gravity-forms-pdf-extended' ) . '" class="submitdelete" data-id="' . $item['id'] . '" data-nonce="' . $delete_nonce . '" data-fid="' . $form_id . '">' . __( 'Delete', 'gravity-forms-pdf-extended' ) . '</a>',
		);

		/* See https://gravitypdf.com/documentation/v4/gfpdf_pdf_actions/ for more details about this filter */
		$actions = apply_filters( 'gfpdf_pdf_actions', $actions, $item );

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
	 *
	 * @since 4.0
	 */
	public function no_items() {
		printf( __( "This form doesn't have any PDFs. Let's go %screate one%s.", 'gravity-forms-pdf-extended' ), "<a href='" . add_query_arg( array( 'pid' => 0 ) ) . "'>", '</a>' );
	}
}
