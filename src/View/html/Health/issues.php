<?php

/**
 * One health check's issues, as an admin notice
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

?>

<div style="font-size:15px; line-height: 25px" role="alert" aria-live="polite">

	<?php /* Bullets are set per-item: Gravity Forms resets `ul li` to none, and wp_kses_post() drops the `list-style` shorthand */ ?>
	<ul style="margin: 0 0 0.5em 2em">
		<?php foreach ( $args['issues'] as $issue ) : ?>
			<li style="list-style-type: disc">
				<strong><?php echo esc_html( $issue['summary'] ); ?></strong>
				<?php echo esc_html( $issue['consequence'] ); ?>

				<?php if ( $issue['label'] !== '' && $issue['url'] !== '' ) : ?>
					<a href="<?php echo esc_url( $issue['url'] ); ?>"><?php echo esc_html( $issue['label'] ); ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php esc_html_e( 'Dismissing this notice hides the items above. Anything found later is reported again.', 'gravity-pdf' ); ?>
</div>
