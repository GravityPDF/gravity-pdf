<?php

/**
 * Uninstaller Settings View
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

<!-- Prevent Firefox auto-filling fields on refresh. @see https://stackoverflow.com/a/44504822/1614565 -->
<form name="gfpdf-settings-form-<?=rand() ?>" method="post" class="gform_settings_form">
	<?php wp_nonce_field( 'gfpdf-uninstall-plugin', 'gfpdf-uninstall-plugin' ); ?>
	<input type="hidden" name="gfpdf_uninstall" value="1"/>

	<?= $args['content'] ?>
</form>

<div id="uninstall-confirm" title="<?php esc_attr_e( 'Uninstall Gravity PDF', 'gravity-forms-pdf-extended' ); ?>" style="display: none;">
	<?php printf( esc_html__( "Warning! ALL Gravity PDF data, %1\$sincluding PDF configurations and ALL custom templates%2\$s will be deleted. This cannot be undone. Select 'Uninstall' to delete, 'Cancel' to stop.", 'gravity-forms-pdf-extended' ), '<strong>', '</strong>' ); ?>
</div>
