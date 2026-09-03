<?php

/**
 * The Deprecated Features Notice
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.17.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

?>

<div style="font-size:15px; line-height: 25px" role="alert" aria-live="polite">

	<strong>
		<?php esc_html_e( 'This site uses deprecated Gravity PDF functionality:', 'gravity-pdf' ); ?>
	</strong>

	<?php /* Bullets are set per-item: Gravity Forms resets `ul li` to none, and wp_kses_post() drops the `list-style` shorthand */ ?>
	<ul style="margin: 0.5em 0 0.5em 2em">
		<?php foreach ( $args['features'] as $feature ) : ?>
			<li style="list-style-type: disc">
				<?php echo esc_html( $feature['notice'] ); ?>
				<a href="<?php echo esc_url( $feature['url'] ); ?>"><?php esc_html_e( 'Learn how to upgrade', 'gravity-pdf' ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php esc_html_e( 'Dismissing this notice hides the items above. Anything found later is reported again.', 'gravity-pdf' ); ?>
</div>
