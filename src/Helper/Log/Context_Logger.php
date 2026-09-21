<?php

namespace GFPDF\Helper\Log;

use GFPDF_Vendor\Psr\Log\AbstractLogger;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

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
 * Passes every record to another logger with extra context added, such as the PDF mPDF is generating
 *
 * @since 6.17.1
 */
class Context_Logger extends AbstractLogger {

	/**
	 * @var LoggerInterface
	 *
	 * @since 6.17.1
	 */
	protected $logger;

	/**
	 * @var array
	 *
	 * @since 6.17.1
	 */
	protected $context;

	/**
	 * @param LoggerInterface $logger  The logger records are passed to
	 * @param array           $context Added to each record's context, without replacing a key the record already has
	 *
	 * @since 6.17.1
	 */
	public function __construct( LoggerInterface $logger, array $context ) {
		$this->logger  = $logger;
		$this->context = $context;
	}

	/**
	 * Untyped $message and a void return keep this compatible with psr/log v1, v2 and v3
	 *
	 * @since 6.17.1
	 */
	public function log( $level, $message, array $context = [] ): void {
		$this->logger->log( $level, $message, $context + $this->context );
	}
}
