<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

?>

<div id="<?php echo esc_attr( $args['id'] ); ?>" class="<?php echo esc_attr( $args['class'] ); ?>">
	<?php if ( ! empty( $args['title'] ) ): ?>
		<div class='gform-settings-panel__title'>
			<?php echo esc_html( $args['title'] ); ?>

			<?php if ( ! empty( $args['tooltip'] ) ): ?>
				<?php echo wp_kses_post( $args['tooltip'] ); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php call_user_func( $args['callback'], $args['callback_args'] ); ?>
</div>
