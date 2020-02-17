<?php

/**
 * The [gravitypdf] shortcode output
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

<a href="<?php echo $args['url']; ?>" class="<?php echo $args['class']; ?> <?php echo $args['classes']; ?>"
					<?php
					if ( 'view' === $args['type'] ):
						?>
	target="_blank"<?php endif; ?> rel="nofollow"><?php echo $args['text']; ?></a>
