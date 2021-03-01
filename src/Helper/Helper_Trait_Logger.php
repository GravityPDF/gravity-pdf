<?php

namespace GFPDF\Helper;

use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
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
	 * @param LoggerInterface $log
	 *
	 * @since 4.3
	 */
	public function set_logger( LoggerInterface $log ) {
		$this->logger = $log;
	}

	/**
	 * @return LoggerInterface
	 *
	 * @since 4.3
	 */
	public function get_logger() {
		return $this->logger;
	}
}
