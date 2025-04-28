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

$width       = $args['width'] ?? 'full';
$width_class = 'gform-settings-panel--' . $width;

$collapsible       = ! empty( $args['collapsible'] );
$collapsible_class = '';

if ( $collapsible ) {
	$collapsible_name = 'gform_settings_section_collapsed_' . $args['id'];

	/* Force open the collapsible section if user had it open during submission */
	/* phpcs:disable WordPress.Security.NonceVerification.Missing */
	if ( isset( $_POST['gfpdf_settings'] ) ) {
		$args['collapsible-open'] = empty( $_POST[ $collapsible_name ] );
	}
	/* phpcs:enable */

	$collapsible_class  = 'gform-settings-panel--collapsible';
	$collapsible_class .= empty( $args['collapsible-open'] ) ? ' gform-settings-panel--collapsed' : '';
}

?>

<fieldset id="<?php echo esc_attr( 'gfpdf-fieldset-' . $args['id'] ); ?>"
		  class="gform-settings-panel gform-settings-panel--with-title <?php echo esc_attr( $width_class ); ?> <?php echo esc_attr( $collapsible_class ); ?>">

	<?php if ( $collapsible ): ?>
		<legend class="gform-settings-panel__title gform-settings-panel__title--header">
			<label class="gform-settings-panel__title"
				   id="<?php echo esc_attr( $collapsible_name ); ?>-description"
				   for="<?php echo esc_attr( $collapsible_name ); ?>">
				<?php echo esc_html( $args['title'] ); ?>
			</label>

			<?php if ( ! empty( $args['tooltip'] ) ): ?>
				<?php echo wp_kses_post( $args['tooltip'] ); ?>
			<?php endif; ?>
		</legend>

		<span class="gform-settings-panel__collapsible-control">
				<input type="checkbox"
					   class="gform-settings-panel__collapsible-toggle-checkbox"
					   name="<?php echo esc_attr( $collapsible_name ); ?>"
					   id="<?php echo esc_attr( $collapsible_name ); ?>"
					   aria-labelledby="<?php echo esc_attr( $collapsible_name ); ?>-description"
					   value="1"
					   onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )"
					   onkeydown="if(event.keyCode==13){ event.preventDefault(); this.click(); }"
					<?php checked( empty( $args['collapsible-open'] ) ); ?>
				/>

				<label class="gform-settings-panel__collapsible-toggle" for="<?php echo esc_attr( $collapsible_name ); ?>">
					<span class="screen-reader-text">
						<?php printf( esc_html__( 'Toggle %s Section', 'gravity-pdf' ), esc_html( $args['title'] ) ); ?>
					</span>
				</label>
			</span>
	<?php else : ?>
		<legend class="gform-settings-panel__title gform-settings-panel__title--header">
			<?php echo esc_html( $args['title'] ); ?>

			<?php if ( ! empty( $args['tooltip'] ) ): ?>
				<?php echo wp_kses_post( $args['tooltip'] ); ?>
			<?php endif; ?>
		</legend>
	<?php endif; ?>

	<div class="gform-settings-panel__content <?php echo esc_attr( $args['content_class'] ?? '' ); ?>">
		<?php if ( ! empty( $args['desc'] ) ): ?>
			<div class="gform-settings-description gform-settings-panel--full"><?php echo wp_kses_post( $args['desc'] ); ?></div>
		<?php endif; ?>

		<?php
		/** @since 6.4.0 */
		if ( isset( $args['callback'] ) ) {
			if ( ! is_array( $args['callback'] ) ) {
				$args['callback'] = [ $args['callback'] ];
			}

			array_map(
				static function ( $callback ) use ( $args ) {
					call_user_func_array( $callback, $args['callback_args'] ?? [] );
				},
				$args['callback']
			);
		}

		/** @deprecated 6.4.0 */
		if ( isset( $args['content'] ) ) {
			echo wp_kses_post( $args['content'] );
		}
		?>
	</div>
</fieldset>
