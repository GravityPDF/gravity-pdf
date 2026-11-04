<?php

/**
 * A notice listing the items it's about, e.g. unwritable folders or fonts that failed to download
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array{heading: string, items: string[], footer: string} $args The footer may hold links */

?>

<div style="font-size:15px; line-height: 25px" role="alert" aria-live="polite">

	<strong>
		<?php echo esc_html( $args['heading'] ); ?>
	</strong>

	<?php /* Bullets are set per-item: Gravity Forms resets `ul li` to none, and wp_kses_post() drops the `list-style` shorthand */ ?>
	<ul style="margin: 0.5em 0 0.5em 2em">
		<?php foreach ( $args['items'] as $item ) : ?>
			<li style="list-style-type: disc"><code><?php echo esc_html( $item ); ?></code></li>
		<?php endforeach; ?>
	</ul>

	<?php echo wp_kses_post( $args['footer'] ); ?>
</div>
