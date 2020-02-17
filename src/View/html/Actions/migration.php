<?php

/**
 * The Review Plugin Notice
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

<div style="font-size:15px; line-height: 25px">

	<strong><?php esc_html_e( 'Gravity PDF needs to migrate your configuration.', 'gravity-forms-pdf-extended' ); ?></strong>

	<br>

	<?php esc_html_e( 'The migration process will import your old configuration file into the database.', 'gravity-forms-pdf-extended' ); ?>

</div>
