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

$is_download = $args['view'] === 'download';

?>

|
<a  href="<?php echo $is_download ? esc_url( $args['pdf']['download'] ) : esc_url( $args['pdf']['view'] ); ?>"
	class="<?php echo esc_attr( $args['pdf']['class'] ); ?>"
	data-type="<?php echo esc_attr( $args['view'] ); ?>"
	<?php echo $is_download ? '' : 'target="_blank"'; ?>
>
	<?php echo $is_download ? esc_html__( 'Download PDF', 'gravity-pdf' ) : esc_html__( 'View PDF', 'gravity-pdf' ); ?>
</a>
