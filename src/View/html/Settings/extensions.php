<?php

/**
 * Extensions Settings View
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
	<h3>
		<span>
			<i class="fa fa-cogs"></i>
			<?php esc_html_e( 'Extensions Settings', 'gravity-forms-pdf-extended' ); ?>
		</span>
	</h3>

	<form method="post" action="options.php">
		<?php settings_fields( 'gfpdf_settings' ); ?>

		<table id="pdf-extensions" class="form-table">
			<?php do_settings_fields( 'gfpdf_settings_extensions', 'gfpdf_settings_extensions' ); ?>
		</table>

		<?php
		if ( $args['edit_cap'] ) {
			submit_button();
		}
		?>
	</form>

	<?php
	/* @TODO */
	do_action( 'gfpdf_post_extensions_settings_page' );
	?>
</div>
