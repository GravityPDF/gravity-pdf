<?php

/**
 * The Failed Core Font Downloads Notice
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

/** @var array $args */

?>

<div style="font-size:15px; line-height: 25px" role="alert" aria-live="polite">

	<strong>
		<?php esc_html_e( 'Gravity PDF could not download these fonts. Until they are installed, PDFs use a fallback font in their place, so some characters may not display.', 'gravity-pdf' ); ?>
	</strong>

	<?php /* Per-item bullets: Gravity Forms resets "ul li" and wp_kses_post() drops the list-style shorthand */ ?>
	<ul style="margin: 0.5em 0 0.5em 2em">
		<?php foreach ( $args['files'] as $file ) : ?>
			<li style="list-style-type: disc"><code><?php echo esc_html( $file ); ?></code></li>
		<?php endforeach; ?>
	</ul>

	<?php
	/* translators: 1: Opening <a> tag, 2: Closing </a> tag */
	printf( esc_html__( 'You can %1$sinstall the fonts by hand%2$s. Dismissing this notice hides the fonts above. Any that fail later are reported again.', 'gravity-pdf' ), '<a href="https://docs.gravitypdf.com/users/core-pdf-fonts">', '</a>' );
	?>
</div>
