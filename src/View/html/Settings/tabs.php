<?php

/**
 * Navigation Settings View
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

<nav class="gform-settings-tabs__navigation" role="tablist">
	<?php foreach ( $args['tabs'] as $tab_nav_item ): ?>
		<a
				role="tab"
				aria-selected="<?php echo $args['selected'] === $tab_nav_item['id'] ? 'true' : 'false'; ?>"
				data-id="<?php echo esc_attr( $tab_nav_item['id'] ); ?>"
				class="<?php echo $args['selected'] === $tab_nav_item['id'] ? 'active' : ''; ?>"
				href="<?php echo esc_url( add_query_arg( [ 'tab' => $tab_nav_item['id'] ], $args['data']->settings_url ) ); ?>">
			<?php echo esc_html( $tab_nav_item['name'] ); ?>
		</a>
	<?php endforeach; ?>
</nav>
