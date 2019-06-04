<?php

/**
 * The Review Plugin Notice
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div style="font-size:15px; line-height: 25px">

	<strong><?php esc_html_e( "Hey, we just noticed you've generated your 100th PDF using Gravity PDF!", 'gravity-forms-pdf-extended' ); ?></strong>

	<br>

	<?php printf( esc_html__( "If you love how much time you've saved using Gravity PDF then do us a big favor and %1\$sreview it on WordPress.org%2\$s.", 'gravity-forms-pdf-extended' ), '<a href="https://wordpress.org/support/view/plugin-reviews/gravity-forms-pdf-extended">', '</a>' ); ?>

	<br>

	<?php printf( esc_html__( '%1$sOr let your Twitter follows know how good it is%2$s (or anyone else for that matter).', 'gravity-forms-pdf-extended' ), '<a href="https://goo.gl/07NhJQ">', '</a>' ); ?>
</div>
