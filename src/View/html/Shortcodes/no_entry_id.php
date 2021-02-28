<?php

/**
 * No Entry ID for Shortcode
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<pre class="gravitypdf-error">
	<?php esc_html_e( 'No Gravity Form entry ID passed to Gravity PDF. Ensure you pass the entry ID via the confirmation url query string – using either "entry" or "lid" as the query string name – or by passing an ID directly to the shortcode.', 'gravity-forms-pdf-extended' ); ?>
	<small><?php esc_html_e( '(Admin Only Message)', 'gravity-forms-pdf-extended' ); ?></small>
</pre>
