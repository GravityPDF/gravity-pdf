<?php

/**
 * Navigation Settings View
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

<h2 class="nav-tab-wrapper">
	<?php foreach ( $args['tabs'] as $tab ): ?>
		<a data-id="<?php echo esc_attr( $tab['id'] ); ?>" class="nav-tab <?php echo ( $args['selected'] === $tab['id'] ) ? 'nav-tab-active' : ''; ?>" href="<?php echo $args['data']->settings_url . '&amp;tab=' . $tab['id']; ?>"><?php echo $tab['name']; ?></a>
	<?php endforeach; ?>
</h2>
