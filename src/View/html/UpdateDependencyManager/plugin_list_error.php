<?php

/**
 * Plugin List Error Message
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.4
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

$message = $args['message'];
$plugin  = $args['plugin'];

$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin['slug'] . '&section=changelog&TB_iframe=true&width=600&height=800' );

?>

<tr class="plugin-update-tr active" id="<?= esc_attr( $plugin['slug'] ) ?>-update" data-slug="<?= esc_attr( $plugin['slug'] ) ?>" data-plugin="<?= esc_attr( $plugin['plugin'] ) ?>">
	<td colspan="4" class="plugin-update colspanchange">
		<div class="update-message notice inline notice-error notice-alt">
			<p>
				<?= $message ?>

				<a href="<?= esc_url( $details_url ) ?>"
				   class="thickbox open-plugin-details-modal"
				   aria-label="<?= esc_attr( sprintf( __( 'View %1$s version %2$s details', 'default' ), $plugin['Name'], $plugin['new_version'] ) ) ?>">
					<?= sprintf( esc_html__( 'View version %s details', 'default' ), $plugin['new_version'] ) ?>
				</a>
			</p>
		</div>
	</td>
</tr>
