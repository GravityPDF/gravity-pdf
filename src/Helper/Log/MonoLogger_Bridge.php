<?php

namespace GFPDF\Helper\Log;

use GFPDF_Vendor\Monolog\Logger as MonoLogger;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Our scoped Monolog, also implementing the global \Psr\Log\LoggerInterface add-ons type-hint
 *
 * A subclass because PHP fatals when a class implements both an interface and its alias.
 *
 * @since 6.18.0
 */
class MonoLogger_Bridge extends MonoLogger implements \Psr\Log\LoggerInterface {
}
