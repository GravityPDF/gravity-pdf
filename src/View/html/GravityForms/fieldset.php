<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$width       = isset( $args['width'] ) ? $args['width'] : 'full';
$width_class = 'gform-settings-panel--' . $width;

$collapsable       = ! empty( $args['collapsable'] );
$collapsable_class = $collapsable ? 'gform-settings-panel--collapsible gform-settings-panel--collapsed' : '';
$collapsable_name  = 'gform_settings_section_collapsed_' . $args['id'];

?>

<fieldset id="<?= esc_attr( $args['id'] ) ?>" class="gform-settings-panel <?= esc_attr( $width_class ) ?> <?= $collapsable_class ?>">
	<header class="gform-settings-panel__header">
		<?php if ( $collapsable ): ?>
			<legend class="gform-settings-panel__title">
				<label class="gform-settings-panel__title" for="<?= esc_attr( $collapsable_name ) ?>"><?= esc_html( $args['title'] ) ?></label>

				<?php if ( ! empty( $args['tooltip'] ) ): ?>
					<?= $args['tooltip'] ?>
				<?php endif; ?>
			</legend>

			<span class="gform-settings-panel__collapsible-control">
				<input type="checkbox" class="gform-settings-panel__collapsible-toggle-checkbox" name="<?= esc_attr( $collapsable_name ) ?>" id="<?= esc_attr( $collapsable_name ) ?>" value="1" onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )" checked="">
				<label class="gform-settings-panel__collapsible-toggle" for="<?= esc_attr( $collapsable_name ) ?>"><span class="screen-reader-text"><?= sprintf( __( 'Toggle %s Section', 'gravity-forms-pdf-extended' ), esc_html( $args['title'] ) ) ?></span></label>
			</span>
		<?php else: ?>
			<legend class="gform-settings-panel__title">
				<?= esc_html( $args['title'] ) ?>

				<?php if ( ! empty( $args['tooltip'] ) ): ?>
					<?= $args['tooltip'] ?>
				<?php endif; ?>
			</legend>
		<?php endif; ?>
	</header>

	<div class="gform-settings-panel__content <?= isset( $args['content_class'] ) ? esc_attr( $args['content_class'] ) : '' ?>">
		<?php if ( isset( $args['description'] ) ): ?>
			<div class="gform-settings-description gform-settings-panel--full"><?= wp_kses_post( $args['description'] ) ?></div>
		<?php endif; ?>

		<?= $args['content'] ?>
	</div>
</fieldset>