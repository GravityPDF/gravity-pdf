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

| <a href="<?php echo ( $args['view'] === 'download' ) ? esc_url( $args['pdf']['download'] ) : esc_url( $args['pdf']['view'] ); ?>" <?php echo ( $args['view'] !== 'download' ) ? 'target="_blank"' : ''; ?>>
	<?php echo ( $args['view'] === 'download' ) ? esc_html__( 'Download PDF', 'gravity-forms-pdf-extended' ) : esc_html__( 'View PDF', 'gravity-forms-pdf-extended' ); ?>
</a>
