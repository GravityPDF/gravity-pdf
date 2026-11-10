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

	<?php echo wp_kses_post( \GFPDF\View\View_Actions::get_code_list( $args['items'] ) ); ?>

	<?php echo wp_kses_post( $args['footer'] ); ?>
</div>
