<?php

/**
 * Uninstaller Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

?>

<!-- Prevent Firefox auto-filling fields on refresh. @see https://stackoverflow.com/a/44504822/1614565 -->
<form name="gfpdf-settings-form-<?= rand() ?>" method="post" class="gform_settings_form">
	<?php wp_nonce_field( 'gfpdf-uninstall-plugin', 'gfpdf-uninstall-plugin' ); ?>
	<input type="hidden" name="gfpdf_uninstall" value="1" />

	<?= $args['content'] ?>
</form>
