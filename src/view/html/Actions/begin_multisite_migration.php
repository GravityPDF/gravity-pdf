<?php

/**
 * The start of the multisite migration
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

?>

<script type="text/javascript">
	var gfpdf_migration_multisite_ids = <?php echo json_encode( $args['multisite_ids'] ); ?>;
</script>

<div class="wrap">

	<h1><?php esc_html_e( 'Gravity PDF Multisite Migration', 'gravity-forms-pdf-extended' ); ?></h1>

	<p><?php esc_html_e( 'Beginning Migration...', 'gravity-forms-pdf-extended' ); ?></p>

	<div id="gfpdf-multisite-migration-copy" data-nonce="<?php echo wp_create_nonce( 'gfpdf_multisite_migration' ); ?>">
		<!-- Container for our AJAX endpoint -->
	</div>

	<div id="gfpdf-multisite-migration-complete" style="display: none">
		<p><?php esc_html_e( 'Migration Complete.', 'gravity-forms-pdf-extended' ); ?></p>

		<p><a href="<?php echo $args['current_page_url']; ?>">Return to current page</a> | <a href="<?php echo $args['gf_forms_url']; ?>">View Gravity Forms</a></p>
	</div>
