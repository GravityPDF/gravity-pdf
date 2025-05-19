<?php

/**
 * The Dismissal Button
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

?>

<form method="post">
	<input type="hidden" name="gfpdf_action" value="gfpdf_<?php echo esc_attr( $args['type'] ); ?>" />
	<input type="hidden" name="gfpdf_action_<?php echo esc_attr( $args['type'] ); ?>" value="<?php echo esc_attr( wp_create_nonce( 'gfpdf_action_' . $args['type'] ) ); ?>" />

	<p>
		<button class="button"><?php echo esc_html( $args['button_text'] ); ?></button>

		<?php if ( $args['dismissal'] === 'enabled' ): ?>
			<input class="button primary" type="submit" value="<?php esc_attr_e( 'Dismiss Notice', 'gravity-pdf' ); ?>" name="gfpdf-dismiss-notice" />
		<?php endif; ?>
	</p>

</form>
