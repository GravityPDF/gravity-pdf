<?php

/**
 * The "View PDF" link for the entry list page
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

/** @var $args array */

$download         = $args['view'] === 'download';
$parent_link_text = $download ? esc_html__( 'Download PDFs', 'gravity-forms-pdf-extended' ) : esc_html__( 'View PDFs', 'gravity-forms-pdf-extended' );

?>

<div class="gfpdf_form_action_has_submenu">
	| <a href="#" aria-haspopup="true" aria-expanded="false"><?= $parent_link_text ?></a>
	<div class="gform-form-toolbar__submenu">
		<ul>
			<?php foreach ( $args['pdfs'] as $pdf ): ?>
				<li>
					<a href="<?= $download ? esc_url( $pdf['download'] ) : esc_url( $pdf['view'] ); ?>"
						<?= ! $download ? 'target="_blank"' : ''; ?>
					   data-label="<?= esc_attr( $pdf['name'] ) ?>"
					>
						<?= esc_html( $pdf['name'] ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
