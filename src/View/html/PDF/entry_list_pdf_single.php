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

?>

|
<a href="<?= ( $args['view'] === 'download' ) ? esc_url( $args['pdf']['download'] ) : esc_url( $args['pdf']['view'] ); ?>" <?= ( $args['view'] !== 'download' ) ? 'target="_blank"' : ''; ?>>
	<?= ( $args['view'] === 'download' ) ? esc_html__( 'Download PDF', 'gravity-forms-pdf-extended' ) : esc_html__( 'View PDF', 'gravity-forms-pdf-extended' ); ?>
</a>
