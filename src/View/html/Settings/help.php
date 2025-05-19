<?php

/**
 * Help Settings View
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

GFCommon::display_admin_message();

?>

<div id="pdfextended-settings" class="gpdf-help">

	<?php do_action( 'gfpdf_settings_sub_menu' ); ?>

	<h2><?php esc_html_e( 'Get help with Gravity PDF', 'gravity-pdf' ); ?></h2>

	<p><?php esc_html_e( 'Search the documentation for an answer to your question. If you need further assistance, contact support and our team will be happy to help.', 'gravity-pdf' ); ?></p>

	<div id="gpdf-search"><!-- Placeholder --></div>

	<div id="gpdf-action-links">
		<a href="https://docs.gravitypdf.com/v6/users/five-minute-install/" class="button button-primary button-large"><?php esc_html_e( 'View Documentation', 'gravity-pdf' ); ?></a>
		<a href="https://gravitypdf.com/support/#contact-support" class="button button-primary button-large"><?php esc_html_e( 'Contact Support', 'gravity-pdf' ); ?></a>

		<p>
			<?php printf( esc_html__( 'Support hours are 9:00am-5:00pm Monday to Friday, %1$sSydney Australia time%2$s (public holidays excluded).', 'gravity-pdf' ), '<br><a href="http://www.timeanddate.com/worldclock/australia/sydney">', '</a>' ); ?>
		</p>
	</div>

	<?php
	/* See https://docs.gravitypdf.com/v6/developers/actions/gfpdf_post_help_settings_page for more details about this action */
	do_action( 'gfpdf_post_help_settings_page' );
	?>
</div><!-- close #pdfextended-settings -->
