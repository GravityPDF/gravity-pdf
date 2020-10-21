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

$width       = isset( $args['width'] ) ? $args['width'] : 'full';
$width_class = 'gform-settings-panel--' . $width;

$collapsible       = ! empty( $args['collapsible'] );
$collapsible_class = '';

if ( $collapsible ) {
	$collapsible_name = 'gform_settings_section_collapsed_' . $args['id'];

	/* Force open the collapsible section if user had it open during submission */
	if ( isset( $_POST['gfpdf_settings'] ) ) {
		$args['collapsible-open'] = empty( $_POST[ $collapsible_name ] );
	}

	$collapsible_class  = 'gform-settings-panel--collapsible';
	$collapsible_class .= empty( $args['collapsible-open'] ) ? ' gform-settings-panel--collapsed' : '';
}

?>

<fieldset id="gfpdf-fieldset-<?= esc_attr( $args['id'] ) ?>" class="gform-settings-panel gform-settings-panel--with-title <?= esc_attr( $width_class ) ?> <?= $collapsible_class ?>">
		<?php if ( $collapsible ): ?>
			<legend class="gform-settings-panel__title gform-settings-panel__title--header">
				<label class="gform-settings-panel__title" id="<?= esc_attr( $collapsible_name ) ?>-description" for="<?= esc_attr( $collapsible_name ) ?>"><?= esc_html( $args['title'] ) ?></label>

				<?php if ( ! empty( $args['tooltip'] ) ): ?>
					<?= $args['tooltip'] ?>
				<?php endif; ?>
			</legend>

			<span class="gform-settings-panel__collapsible-control">
				<input type="checkbox" class="gform-settings-panel__collapsible-toggle-checkbox" name="<?= esc_attr( $collapsible_name ) ?>" id="<?= esc_attr( $collapsible_name ) ?>" aria-labelledby="<?=$collapsible_name?>-description" value="1"
					   onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )"
					   onkeydown="if(event.keyCode==13){ event.preventDefault(); this.click(); }"
					<?php checked( empty( $args['collapsible-open'] ) ); ?>>
				<label class="gform-settings-panel__collapsible-toggle" for="<?= esc_attr( $collapsible_name ) ?>"><span class="screen-reader-text"><?= sprintf( __( 'Toggle %s Section', 'gravity-forms-pdf-extended' ), esc_html( $args['title'] ) ) ?></span></label>
			</span>
		<?php else : ?>
			<legend class="gform-settings-panel__title gform-settings-panel__title--header">
				<?= esc_html( $args['title'] ) ?>

				<?php if ( ! empty( $args['tooltip'] ) ): ?>
					<?= $args['tooltip'] ?>
				<?php endif; ?>
			</legend>
		<?php endif; ?>

	<div class="gform-settings-panel__content <?= isset( $args['content_class'] ) ? esc_attr( $args['content_class'] ) : '' ?>">
		<?php if ( ! empty( $args['desc'] ) ): ?>
			<div class="gform-settings-description gform-settings-panel--full"><?= wp_kses_post( $args['desc'] ) ?></div>
		<?php endif; ?>

		<?= $args['content'] ?>
	</div>
</fieldset>
