<?php

/**
 * The "View PDF" link for the entry list page
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

<span class="gf_form_toolbar_settings gf_form_action_has_submenu gfpdf_form_action_has_submenu">
   | <a href="#" title="View PDFs" onclick="return false" class=""><?php echo ( $args['view'] === 'download' ) ? esc_html__( 'Download PDFs', 'gravity-forms-pdf-extended' ) : esc_html__( 'View PDFs', 'gravity-forms-pdf-extended' ); ?></a>

	<div class="gf_submenu gfpdf_submenu">
		<ul>
			<?php foreach ( $args['pdfs'] as $pdf ): ?>
				<li>
					<a href="<?php echo ( $args['view'] === 'download' ) ? esc_url( $pdf['download'] ) : esc_url( $pdf['view'] ); ?>" <?php echo ( $args['view'] !== 'download' ) ? 'target="_blank"' : ''; ?>><?php echo esc_html( $pdf['name'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</span>
