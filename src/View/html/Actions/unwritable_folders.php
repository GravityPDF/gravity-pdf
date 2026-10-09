<?php

/**
 * The Unwritable Folders Notice
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.18.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

?>

<div style="font-size:15px; line-height: 25px" role="alert" aria-live="polite">

	<strong>
		<?php esc_html_e( 'Gravity PDF cannot create or write to these directories. Contact your web hosting provider to fix the issue.', 'gravity-pdf' ); ?>
	</strong>

	<?php /* Bullets are set per-item: Gravity Forms resets `ul li` to none, and wp_kses_post() drops the `list-style` shorthand */ ?>
	<ul style="margin: 0.5em 0 0.5em 2em">
		<?php foreach ( $args['folders'] as $folder ) : ?>
			<li style="list-style-type: disc"><code><?php echo esc_html( $folder ); ?></code></li>
		<?php endforeach; ?>
	</ul>

	<?php esc_html_e( 'Dismissing this notice hides the directories above. Any found later are reported again.', 'gravity-pdf' ); ?>
</div>
