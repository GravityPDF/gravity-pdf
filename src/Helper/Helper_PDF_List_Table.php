<?php

namespace GFPDF\Helper;

use WP_List_Table;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A simple abstract class controllers can extent to share similar variables
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
	 * @var Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
	 *
	 * @since 4.0
	 */
	protected $templates;

	/**
	 * Setup our class with appropriate data
	 *
	 * @param array                $form  The Gravity Forms object
	 * @param Helper_Abstract_Form $gform Our abstracted Gravity Forms API
	 * @param Helper_Misc          $misc
	 * @param Helper_Templates     $templates
	 *
	 * @since    4.0
	 */
	public function __construct( $form, Helper_Abstract_Form $gform, Helper_Misc $misc, Helper_Templates $templates ) {

		/* Assign our internal variables */
		$this->form      = $form;
		$this->gform     = $gform;
		$this->misc      = $misc;
		$this->templates = $templates;

		/* Cache column header internally so we don't have to work with the global get_column_headers() function */
		$this->_column_headers = [ $this->get_columns(), [], [] ];

		parent::__construct();
	}

	/**
	 * Return the columns that should be used in the list table
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = [
			'cb'            => '',
			'name'          => esc_html__( 'Label', 'gravity-forms-pdf-extended' ),
			'template'      => esc_html__( 'Template', 'gravity-forms-pdf-extended' ),
			'notifications' => esc_html__( 'Notifications', 'gravity-forms-pdf-extended' ),
			'shortcode'     => esc_html__( 'Shortcode', 'gravity-forms-pdf-extended' ),
		];

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_list_columns/ for more details about this filter */
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
		$this->items = ( isset( $this->form['gfpdf_form_settings'] ) ) ? $this->form['gfpdf_form_settings'] : [];
	}

	/**
	 * Display our table
	 *
	 * @since 4.0
	 */
	public function display() {

		$singular = rgar( $this->_args, 'singular' );
		?>

		<table class="wp-list-table <?= implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0" aria-label="<?= esc_html__( 'PDF List', 'gravity-forms-pdf-extended' ) ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-list"
				<?php
				if ( $singular ) {
					echo " class='list:$singular'";
				}
				?>
			>
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
	 * @param object $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function single_row( $item ) {
		static $row_class = '';

		$row_class = ( $row_class === '' ? 'class="alternate"' : $row_class );

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
	 * @param array  $item The table row being processed
	 *
	 * @param string $column
	 *
	 * @since 4.0
	 */
	public function column_default( $item, $column ) {

		$action = 'gfpdf_pdf_list_column_' . $column;

		if ( has_action( $action ) ) {
			/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_list_column_id/ for more details about this action */
			do_action( $action, $item );
		} else {
			echo rgar( $item, $column );
		}
	}

	/**
	 * Custom public function for displaying the 'cb' column
	 * Used to handle active / inactive PDFs
	 *
	 * @param array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_cb( $item ) {

		$is_active   = isset( $item['active'] ) ? $item['active'] : true;
		$form_id     = (int) rgget( 'id' );
		$state_nonce = wp_create_nonce( "gfpdf_state_nonce_{$form_id}_{$item['id']}" );

		if ( $is_active ) {
			$class = 'gform-status--active';
			$text  = esc_html__( 'Active', 'gravity-forms-pdf-extended' );
		} else {
			$class = 'gform-status--inactive';
			$text  = esc_html__( 'Inactive', 'gravity-forms-pdf-extended' );
		}
		?>

		<button type="button" class="gform-status-indicator <?php echo esc_attr( $class ); ?>" data-id="<?= esc_attr( $item['id'] ) ?>" data-nonce="<?= esc_attr( $state_nonce ) ?>" data-fid="<?= esc_attr( $form_id ) ?>" data-status="<?= $is_active ? 'active' : 'inactive' ?>">
			<svg viewBox="0 0 6 6" xmlns="http://www.w3.org/2000/svg"><circle cx="3" cy="2" r="1" stroke-width="2"/></svg>
			<span class="gform-status-indicator-status"><?php echo esc_html( $text ); ?></span>
		</button>

		<?php
	}

	/**
	 * Custom public function for displaying the 'notifications' column
	 * Display comma separated list of active notifications, otherwise display 'None'
	 *
	 * @param array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_notifications( $item ) {
		if ( ! isset( $item['notification'] ) || count( $item['notification'] ) === 0 ) {
			printf( '<span>%s</span>', esc_html__( 'None', 'gravity-forms-pdf-extended' ) );

			return;
		}

		/* Convert our IDs to names */
		$notification_names = [];
		foreach ( $this->form['notifications'] as $notification ) {
			if ( in_array( $notification['id'], $item['notification'], true ) ) {
				$notification_names[] = $notification['name'];
			}
		}

		echo sprintf( '<span>%1$s</span>', implode( ', ', $notification_names ) );

	}

	/**
	 * Translates the template raw name to a user-friendly name
	 *
	 * @param array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_shortcode( $item ) {
		do_action( 'gfpdf_pre_pdf_list_shortcode_column', $item, $this );

		/*
		 * While esc_attr() used below will ensure no display issues when copied the double quote will cause shortcode parse issues
		 * We'll prevent this by removing them before hand
		 */
		$name   = str_replace( '"', '', $item['name'] );
		$pdf_id = esc_attr( $item['id'] );

		/* Prepare our shortcode sample */
		$shortcode = '[gravitypdf name="' . esc_attr( $name ) . '" id="' . esc_attr( $item['id'] ) . '" text="' . esc_attr__( 'Download PDF', 'gravity-forms-pdf-extended' ) . '"]';

		/* Set up hidden text for screen readers */
		echo '<p id="' . $pdf_id . '" class="screen-reader-text">' . sprintf( esc_html__( 'Copy Download %s Shortcode', 'gravity-forms-pdf-extended' ), esc_html( $item['name'] ) ) . '</p>';

		/* Display in a readonly field */
		echo '<input type="text" aria-labeledby="' . $pdf_id . '" class="gravitypdf_shortcode" value="' . esc_attr( $shortcode ) . '" readonly="readonly" onclick="jQuery(this).select();" />';

		do_action( 'gfpdf_post_pdf_list_shortcode_column', $item, $this );
	}

	/**
	 * Translates the template raw name to a user-friendly name
	 *
	 * @param array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_template( $item ) {

		$template = $this->templates->get_template_info_by_id( $item['template'] );

		if ( isset( $template['template'] ) ) {
			$template_group = $template['group'];
			$template_name  = $this->templates->maybe_add_template_compatibility_notice( $template['template'], $template['required_pdf_version'] );

			?>
			<span aria-label="<?= esc_attr( $template_group . ' ' . $template_name ) ?>">
				<strong><?= esc_html( $template_group ) ?></strong> <?= esc_html( $template_name ) ?>
			</span>
			<?php

		}
	}

	/**
	 * Add column actions to allow edit, duplication and deletion
	 *
	 * @param array $item The table row being processed
	 *
	 * @since 4.0
	 */
	public function column_name( $item ) {
		$edit_url        = add_query_arg( [ 'pid' => $item['id'] ] );
		$form_id         = (int) rgget( 'id' );
		$pdf_name        = $item['name'] ?? '';
		$duplicate_nonce = wp_create_nonce( "gfpdf_duplicate_nonce_{$form_id}_{$item['id']}" );
		$delete_nonce    = wp_create_nonce( "gfpdf_delete_nonce_{$form_id}_{$item['id']}" );

		$actions = [
			'edit'      => '<a title="' . esc_attr__( 'Edit this PDF', 'gravity-forms-pdf-extended' ) . '" href="' . $edit_url . '">' . esc_html__( 'Edit', 'gravity-forms-pdf-extended' ) . '</a>',
			'duplicate' => '<a title="' . esc_attr__( 'Duplicate this PDF', 'gravity-forms-pdf-extended' ) . '" data-id="' . esc_attr( $item['id'] ) . '" class="submitduplicate" data-nonce="' . $duplicate_nonce . '"  data-fid="' . $form_id . '" href="#">' . esc_html__( 'Duplicate', 'gravity-forms-pdf-extended' ) . '</a>',
			'delete'    => '<a title="' . esc_attr__( 'Delete this PDF', 'gravity-forms-pdf-extended' ) . '" class="submitdelete" data-id="' . esc_attr( $item['id'] ) . '" data-nonce="' . $delete_nonce . '" data-fid="' . $form_id . '" href="#">' . esc_html__( 'Delete', 'gravity-forms-pdf-extended' ) . '</a>',
		];

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_actions/ for more details about this filter */
		$actions = apply_filters( 'gfpdf_pdf_actions', $actions, $item );

		?>

		<a href="<?= $edit_url; ?>" aria-label="<?= esc_attr( $pdf_name ) . esc_attr__( 'PDF', 'gravity-forms-pdf-extended' ) ?>"><strong><?= esc_html( $pdf_name ) ?></strong></a>
		<div class="row-actions">

			<?php
			if ( is_array( $actions ) && ! empty( $actions ) ) {
				$keys     = array_keys( $actions );
				$last_key = array_pop( $keys );
				foreach ( $actions as $key => $html ) {
					$divider = $key === $last_key ? '' : ' | ';
					?>
					<span class="<?= $key; ?>" aria-labelledby="pdf_<?= $form_id ?>">
						<?= $html . $divider; ?>
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
		echo '<label>';
		printf( esc_html__( "This form doesn't have any PDFs. Let's go %1\$screate one%2\$s.", 'gravity-forms-pdf-extended' ), "<a href='" . add_query_arg( [ 'pid' => 0 ] ) . "'>", '</a>' );
		echo '</label>';

	}
}
