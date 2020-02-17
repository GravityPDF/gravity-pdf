<?php

/**
 * General Settings View
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

<?php $this->tabs(); ?>


<div id="pdfextended-settings">
	<h3>
		<span>
			<i class="fa fa-cog"></i>
			<?php esc_html_e( 'General Settings', 'gravity-forms-pdf-extended' ); ?>
		</span>
	</h3>

	<form method="post" action="options.php">
		<?php settings_fields( 'gfpdf_settings' ); ?>

		<table id="pdf-general" class="form-table">
			<?php do_settings_fields( 'gfpdf_settings_general', 'gfpdf_settings_general' ); ?>
		</table>

		<div id="gfpdf-advanced-options">
			<h3>
				<span>
					<i class="fa fa-lock"></i>
					<?php esc_html_e( 'Security Settings', 'gravity-forms-pdf-extended' ); ?>
				</span>
			</h3>

			<table id="pdf-general-security" class="form-table">
				<?php do_settings_fields( 'gfpdf_settings_general_security', 'gfpdf_settings_general_security' ); ?>
			</table>
		</div>

		<div class="gfpdf-advanced-options"><a href="#"><?php esc_html_e( 'Show Advanced Options...', 'gravity-forms-pdf-extended' ); ?></a></div>

		<?php
		if ( $args['edit_cap'] ) {
			submit_button();
		}
		?>

		<div class="extensions-upsell">
			<a href="https://gravitypdf.com/extension-shop/">
				<?php esc_html_e( 'Want more features? Take a look at our Extension Shop.', 'gravity-forms-pdf-extended' ); ?>
			</a>
		</div>
	</form>

	<?php
	/* See https://gravitypdf.com/documentation/v5/gfpdf_post_general_settings_page/ for more details about this action */
	do_action( 'gfpdf_post_general_settings_page' );
	?>
</div>
