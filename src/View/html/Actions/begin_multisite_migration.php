<?php

/**
 * The start of the multisite migration
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<script type="text/javascript">
	var gfpdf_migration_multisite_ids = <?php echo json_encode( $args['multisite_ids'] ); ?>;
</script>

<div class="wrap">

	<h1><?php esc_html_e( 'Gravity PDF Multisite Migration', 'gravity-forms-pdf-extended' ); ?></h1>

	<p><?php esc_html_e( 'Beginning Migration...', 'gravity-forms-pdf-extended' ); ?></p>

	<div id="gfpdf-multisite-migration-copy" data-nonce="<?php echo wp_create_nonce( 'gfpdf_multisite_migration' ); ?>">
		<!-- Container for our AJAX endpoint -->
	</div>

	<div id="gfpdf-multisite-migration-complete" style="display: none">
		<p><?php esc_html_e( 'Migration Complete.', 'gravity-forms-pdf-extended' ); ?></p>

		<p><a href="<?php echo $args['current_page_url']; ?>">Return to current page</a> | <a href="<?php echo $args['gf_forms_url']; ?>">View Gravity Forms</a></p>
	</div>
