<?php

/**
 * Tools Settings View
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

<div id="pdfextended-settings">

	<!-- Prevent Firefox auto-filling fields on refresh. @see https://stackoverflow.com/a/44504822/1614565 -->
	<form name="gfpdf-settings-form-<?= rand() ?>" method="post" class="gform_settings_form">

		<?php settings_fields( 'gfpdf_settings' ); ?>

		<?= $args['menu'] ?>
		<?= $args['content'] ?>
	</form>

	<?php
	/* See https://docs.gravitypdf.com/v6/developers/actions/gfpdf_post_tools_settings_page for more details about this action */
	do_action( 'gfpdf_post_tools_settings_page' );
	?>

</div>
