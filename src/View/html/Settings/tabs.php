<?php

/**
 * Navigation Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

?>

<nav class="gform-settings-tabs__navigation" role="tablist">
	<?php foreach ( $args['tabs'] as $tab ): ?>
		<a
				role="tab"
				aria-selected="<?= $args['selected'] === $tab['id'] ? 'true' : 'false' ?>"
				data-id="<?= esc_attr( $tab['id'] ); ?>"
				class="<?= $args['selected'] === $tab['id'] ? 'active' : ''; ?>"
				href="<?= $args['data']->settings_url . '&amp;tab=' . esc_attr( $tab['id'] ); ?>">
			<?= esc_html( $tab['name'] ); ?>
		</a>
	<?php endforeach; ?>
</nav>
