<?php

/**
 * The [gravitypdf] shortcode output
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

<a
		href="<?php echo esc_url( $args['url'] ); ?>"
		class="<?php echo esc_attr( $args['class'] ); ?> <?php echo esc_attr( $args['classes'] ); ?>"
		<?php if ( 'view' === $args['type'] ): ?>
			target="_blank"
		<?php endif; ?>
		rel="nofollow">
	<?php echo esc_html( $args['text'] ); ?>
</a>
