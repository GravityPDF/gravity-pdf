<?php

/**
 * List of Form Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

/* assign list_items object to variable for easier access */
$list_items = $args['list_items'];

?>

<?php GFFormSettings::page_header( $args['title'] ); ?>

<div class="gform-settings__content">
	<div class="gform-settings-panel">
		<header class="gform-settings-panel__header">
			<h4 class="gform-settings-panel__title"><?php echo esc_html( $args['title'] ); ?></h4>
		</header>

		<div class="gform-settings-panel__content">
			<form id="gfpdf_list_form" method="post">
				<div class="tablenav top">
					<div class="alignleft actions bulkactions"></div>
					<div class="alignright">
						<a class="button" href="<?php echo esc_url( $args['add_new_url'] ); ?>" aria-label="<?php echo esc_attr__( 'Add new PDF', 'gravity-pdf' ); ?>"><?php esc_html_e( 'Add New', 'gravityforms' ); ?></a>
					</div>
					<br class="clear">
				</div>

				<?php $list_items->display(); ?>
			</form>
		</div>
	</div>
</div>

<?php GFFormSettings::page_footer(); ?>
