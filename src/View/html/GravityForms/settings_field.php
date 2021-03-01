<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

?>

<div id="<?= esc_attr( $args['id'] ) ?>" class="<?= esc_attr( $args['class'] ) ?>">
	<?php if ( ! empty( $args['title'] ) ): ?>
		<div class='gform-settings-panel__title'>
			<?= esc_html( $args['title'] ) ?>

			<?php if ( ! empty( $args['tooltip'] ) ): ?>
				<?= $args['tooltip'] ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php call_user_func( $args['callback'], $args['callback_args'] ); ?>
</div>
