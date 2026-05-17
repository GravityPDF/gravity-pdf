<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

$for = $args['callback_args']['type'] !== 'rich_editor' ?
	'gfpdf_settings[' . $args['callback_args']['id'] . ']' :
	'gfpdf_settings_' . $args['callback_args']['id'];

/* Group complex fields together into a fieldset */
?>

<?php if ( ! in_array( $args['callback_args']['type'], [ 'radio', 'multicheck', 'conditional_logic', 'paper_size' ], true ) ): ?>
	<div id="<?php echo esc_attr( $args['id'] ); ?>" class="<?php echo esc_attr( $args['class'] ); ?>">
		<?php if ( ! empty( $args['title'] ) ): ?>
			<div class='gform-settings-panel__title'>
				<label for="<?php echo esc_attr( $for ); ?>">
					<?php echo esc_html( $args['title'] ); ?>
				</label>

				<?php if ( ! empty( $args['tooltip'] ) ): ?>
					<?php echo wp_kses_post( $args['tooltip'] ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php call_user_func( $args['callback'], $args['callback_args'] ); ?>
	</div>
<?php else: ?>
	<fieldset id="<?php echo esc_attr( $args['id'] ); ?>" class="<?php echo esc_attr( $args['class'] ); ?>">
		<?php if ( ! empty( $args['title'] ) ): ?>
			<div class='gform-settings-panel__title'>
				<legend>
					<?php echo esc_html( $args['title'] ); ?>
				</legend>

				<?php if ( ! empty( $args['tooltip'] ) ): ?>
					<?php echo wp_kses_post( $args['tooltip'] ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php call_user_func( $args['callback'], $args['callback_args'] ); ?>
	</fieldset>
<?php endif; ?>
