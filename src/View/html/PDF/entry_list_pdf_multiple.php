<?php

/**
 * The "View PDF" link for the entry list page
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

$download         = $args['view'] === 'download';
$parent_link_text = $download ? __( 'Download PDFs', 'gravity-pdf' ) : __( 'View PDFs', 'gravity-pdf' );

?>

<div class="gfpdf_form_action_has_submenu">
	| <a href="#" aria-haspopup="true" aria-expanded="false"><?php echo esc_html( $parent_link_text ); ?></a>
	<div class="gform-form-toolbar__submenu">
		<div data-simplebar>
			<ul>
				<?php foreach ( $args['pdfs'] as $pdf ): ?>
					<li>
						<a href="<?php echo $download ? esc_url( $pdf['download'] ) : esc_url( $pdf['view'] ); ?>"
						   class="<?php echo esc_attr( $pdf['class'] ); ?>"
							<?php echo $download ? '' : 'target="_blank"'; ?>
						   data-label="<?php echo esc_attr( $pdf['name'] ); ?>"
						   data-type="<?php echo esc_attr( $args['view'] ); ?>"
						>
							<?php echo esc_html( $pdf['name'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
