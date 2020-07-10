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

<div id="gform-form-toolbar">
	<div id="gfpdf-global-settings-navigation" class="gform-settings__wrapper" style="padding: 0">
		<div></div><!-- Grid override -->
		<ul id="gform-form-toolbar__menu">
			<?php foreach ( $args['tabs'] as $tab ): ?>
				<li><a data-id="<?php echo esc_attr( $tab['id'] ); ?>" class="<?= $args['selected'] === $tab['id'] ? 'gf_toolbar_active' : ''; ?>" href="<?php echo $args['data']->settings_url . '&amp;tab=' . $tab['id']; ?>"><?= esc_html( $tab['name'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>