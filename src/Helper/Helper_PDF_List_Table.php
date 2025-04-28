<?php

namespace GFPDF\Helper;

use WP_List_Table;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
			'name'          => esc_html__( 'Label', 'gravity-pdf' ),
			'template'      => esc_html__( 'Template', 'gravity-pdf' ),
			'notifications' => esc_html__( 'Notifications', 'gravity-pdf' ),
			'shortcode'     => esc_html__( 'Shortcode', 'gravity-pdf' ) . gform_tooltip( 'pdf_shortcode', 'gfpdf-tooltip', true ),
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
		$this->items = $this->form['gfpdf_form_settings'] ?? [];
	}

	/**
	 * Display our table
	 *
	 * @since 4.0
	 */
	public function display() {

		$singular = rgar( $this->_args, 'singular' );
		?>

		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>" cellspacing="0" aria-label="<?php echo esc_attr__( 'PDF List', 'gravity-pdf' ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-list"
				<?php
				if ( $singular ) {
					echo ' class="list:' . esc_attr( $singular ) . '"';
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
		echo '<tr id="gfpdf-' . esc_attr( $item['id'] ) . '">';
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
			echo wp_kses_post( $item[ $column ] ?? '' );
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

		$is_active   = $item['active'] ?? true;
		$form_id     = (int) rgget( 'id' );
		$state_nonce = wp_create_nonce( "gfpdf_state_nonce_{$form_id}_{$item['id']}" );

		if ( $is_active ) {
			$class = 'gform-status--active';
			$text  = __( 'Active', 'gravity-pdf' );
		} else {
			$class = 'gform-status--inactive';
			$text  = __( 'Inactive', 'gravity-pdf' );
		}

		$gf_less_than_288 = version_compare( \GFCommon::$version, '2.8.8', '<' );

		?>

		<button type="button" class="gform-status-indicator <?php echo ! $gf_less_than_288 ? 'gform-status-indicator--size-sm gform-status-indicator--theme-cosmos' : ''; ?> <?php echo esc_attr( $class ); ?>" data-id="<?php echo esc_attr( $item['id'] ); ?>" data-nonce="<?php echo esc_attr( $state_nonce ); ?>" data-fid="<?php echo esc_attr( $form_id ); ?>" data-status="<?php echo $is_active ? 'active' : 'inactive'; ?>">
			<?php if ( $gf_less_than_288 ): ?>
				<svg viewBox="0 0 6 6" xmlns="http://www.w3.org/2000/svg"><circle cx="3" cy="2" r="1" stroke-width="2"/></svg>
			<?php endif; ?>

			<span class="gform-status-indicator-status <?php echo ! $gf_less_than_288 ? 'gform-typography--weight-medium gform-typography--size-text-xs' : ''; ?>"><?php echo esc_html( $text ); ?></span>
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
			printf( '<span>%s</span>', esc_html__( 'None', 'gravity-pdf' ) );

			return;
		}

		/* Convert our IDs to names */
		$notification_names = [];
		foreach ( $this->form['notifications'] as $notification ) {
			if ( in_array( $notification['id'], $item['notification'], true ) ) {
				$notification_names[] = $notification['name'];
			}
		}

		printf( '<span>%1$s</span>', esc_html( implode( ', ', $notification_names ) ) );
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

		/* Remove quotes so it doesn't cause encoding issues */
		$name   = str_replace( '"', '', $item['name'] );
		$pdf_id = $item['id'];

		/* Prepare our shortcode sample */
		$shortcode = sprintf(
			'[gravitypdf name="%1$s" id="%2$s" text="%3$s"]',
			$name,
			esc_attr( $pdf_id ),
			__( 'Download PDF', 'gravity-pdf' )
		);

		$aria_label = sprintf( __( 'Copy the %s PDF shortcode to the clipboard', 'gravity-pdf' ), $item['name'] );

		ob_start();
		/* If the current GF version is 2.6 or higher, use the new updated UI for the shortcode button or else use the pre GF 2.5 version. */
		if ( version_compare( '2.6-rc-1', \GFCommon::$version, '<=' ) ):
			?>
			<button type="button"
					class="gform-button gform-button--size-r gform-button--white gform-button--icon-leading gform-embed-form__shortcode-trigger btn-shortcode"
					data-clipboard-text="<?php echo esc_attr( $shortcode ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"
					role="dialog" aria-live="polite">
				<i class="gform-button__icon gform-icon gform-icon--copy"></i>
				<span class="gform-embed-form__shortcode-copy-label"
					  aria-hidden="false"><?php echo esc_attr__( 'Copy', 'gravity-pdf' ); ?></span>
				<span class="gform-embed-form__shortcode-copy-copied" aria-hidden="true">
				<i class="gform-embed-form__shortcode-copy-icon gform-icon gform-icon--circle-check-alt"></i>
				<?php echo esc_attr__( 'Copied', 'gravity-pdf' ); ?>
			</span>
			</button>
		<?php else : ?>
			<button data-selected-text="<?php echo esc_attr__( 'Shortcode copied!', 'gravity-pdf' ); ?>"
					type="button" class="gform-status-indicator btn-shortcode gf_2_5 "
					data-clipboard-text="<?php echo esc_attr( $shortcode ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"
					role="status" aria-live="polite">
				<?php echo esc_html__( 'Copy Shortcode', 'gravity-pdf' ); ?>
			</button>
		<?php endif; ?>
		<div class="gpdf-fallback-input">
			<input type="text" id="<?php echo esc_attr( $pdf_id ); ?>" value="<?php echo esc_attr( $shortcode ); ?>"
				   aria-label="<?php echo esc_attr( $aria_label ); ?>" />
		</div>

		<?php
		ob_end_flush();

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
			<span aria-label="<?php echo esc_attr( $template_group . ' ' . $template_name ); ?>">
				<strong><?php echo esc_html( $template_group ); ?></strong> <?php echo esc_html( $template_name ); ?>
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
			'edit'      => '<a title="' . esc_attr__( 'Edit this PDF', 'gravity-pdf' ) . '" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'gravity-pdf' ) . '</a>',
			'duplicate' => '<a title="' . esc_attr__( 'Duplicate this PDF', 'gravity-pdf' ) . '" data-id="' . esc_attr( $item['id'] ) . '" class="submitduplicate" data-nonce="' . esc_attr( $duplicate_nonce ) . '"  data-fid="' . esc_attr( $form_id ) . '" href="#">' . esc_html__( 'Duplicate', 'gravity-pdf' ) . '</a>',
			'delete'    => '<a title="' . esc_attr__( 'Delete this PDF', 'gravity-pdf' ) . '" class="submitdelete" data-id="' . esc_attr( $item['id'] ) . '" data-nonce="' . esc_attr( $delete_nonce ) . '" data-fid="' . esc_attr( $form_id ) . '" href="#">' . esc_html__( 'Delete', 'gravity-pdf' ) . '</a>',
		];

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_actions/ for more details about this filter */
		$actions = apply_filters( 'gfpdf_pdf_actions', $actions, $item );

		?>

		<a href="<?php echo esc_url( $edit_url ); ?>" aria-label="<?php echo esc_attr( sprintf( __( '%s PDF', 'gravity-pdf' ), $pdf_name ) ); ?>"><strong><?php echo esc_html( $pdf_name ); ?></strong></a>
		<div class="row-actions">

			<?php
			if ( is_array( $actions ) && ! empty( $actions ) ) {
				$keys     = array_keys( $actions );
				$last_key = array_pop( $keys );
				foreach ( $actions as $key => $html ) {
					$divider = $key === $last_key ? '' : ' | ';
					?>
					<span class="<?php echo esc_attr( $key ); ?>" aria-labelledby="pdf_<?php echo esc_attr( $form_id ); ?>">
						<?php echo wp_kses_post( $html . $divider ); ?>
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
		printf( esc_html__( "This form doesn't have any PDFs. Let's go %1\$screate one%2\$s.", 'gravity-pdf' ), "<a href='" . esc_url( add_query_arg( [ 'pid' => 0 ] ) ) . "'>", '</a>' );
		echo '</label>';
	}
}
