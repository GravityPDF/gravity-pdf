<?php

/**
 * The Dismisal Button
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

<form method="post">
	<input type="hidden" name="gfpdf_action" value="gfpdf_<?php echo $args['type']; ?>"/>
	<input type="hidden" name="gfpdf_action_<?php echo $args['type']; ?>" value="<?php echo wp_create_nonce( 'gfpdf_action_' . $args['type'] ); ?>"/>

	<p>
		<button class="button button-primary"><?php echo $args['button_text']; ?></button>

		<?php if ( $args['dismissal'] === 'enabled' ): ?>
			<input class="button" type="submit" value="<?php esc_attr_e( 'Dismiss Notice', 'gravity-forms-pdf-extended' ); ?>" name="gfpdf-dismiss-notice"/>
		<?php endif; ?>
	</p>

</form>
