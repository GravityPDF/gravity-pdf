<?php

namespace GFPDF\Helper;

use GFPDF\Vendor\Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 4.3
 */
trait Helper_Trait_Logger {

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.3
	 */
	protected $logger;

	/**
	 * @param Logger $log
	 *
	 * @since 4.3
	 */
	public function set_logger( Logger $log ) {
		$this->logger = $log;
	}

	/**
	 * @return Logger
	 *
	 * @since 4.3
	 */
	public function get_logger() {
		return $this->logger;
	}
}
