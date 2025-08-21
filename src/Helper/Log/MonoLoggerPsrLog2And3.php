<?php

namespace GFPDF\Helper\Log;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PSR\Log v2/v3 Compatible Monolog Proxy class
 *
 * @since 6.14.0
 *
 * phpcs:disable Generic.PHP.Syntax.PHPSyntax
 */
class MonoLoggerPsrLog2And3 implements \Psr\Log\LoggerInterface {

	/**
	 * @var \GFPDF_Vendor\Monolog\Logger
	 */
	protected $monologger;

	public function __construct( $slug ) {
		$this->monologger = new \GFPDF_Vendor\Monolog\Logger( $slug );
	}

	public function __call( $method_name, $args ) {
		return call_user_func_array( [ $this->monologger, $method_name ], $args );
	}

	public function log( $level, string|\Stringable $message, array $context = [] ): void {
		$this->monologger->log( $level, $message, $context );
	}

	public function debug( string|\Stringable $message, array $context = [] ): void {
		$this->monologger->debug( $message, $context );
	}

	public function info( string|\Stringable $message, array $context = [] ): void {
		$this->monologger->info( $message, $context );
	}

	public function notice( string|\Stringable $message, array $context = [] ): void {
		$this->monologger->notice( $message, $context );
	}

	public function warning( string|\Stringable $message, array $context = [] ): void {
		$this->monologger->warning( $message, $context );
	}

	public function error( string|\Stringable $message, array $context = [] ): void {
		$this->monologger->error( $message, $context );
	}

	public function critical( string|\Stringable $message, array $context = [] ): void {
		$this->monologger->critical( $message, $context );
	}

	public function alert( string|\Stringable $message, array $context = [] ): void {
		$this->monologger->alert( $message, $context );
	}

	public function emergency( string|\Stringable $message, array $context = [] ): void {
		$this->monologger->emergency( $message, $context );
	}
}
