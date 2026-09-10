<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Log;

use GFPDF_Vendor\Monolog\Handler\AbstractProcessingHandler;
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
 * The last few errors, kept whether or not anyone turned logging on
 *
 * Monolog is the verbose tier and it is off by default — a site that has just failed is exactly the site with no
 * log of why. This keeps the last ten errors in an option instead, so the System Report a support ticket carries
 * has them without anyone having had to predict the failure.
 *
 * Errors only. Anything below that is noise at this size, and the ring would be full of it before the one line
 * that mattered arrived.
 *
 * @package GFPDF\Helper\Log
 *
 * @since 7.0
 */
class Option_Ring_Handler extends AbstractProcessingHandler {

	/**
	 * Not autoloaded: it is read on the System Report and nowhere else
	 *
	 * @since 7.0
	 */
	public const OPTION = 'gfpdf_last_errors';

	/**
	 * @since 7.0
	 */
	public const SIZE = 10;

	/**
	 * Long enough to identify the failure, short enough that ten of them cannot bloat an option
	 *
	 * @since 7.0
	 */
	public const MAX_MESSAGE = 500;

	/**
	 * @var array[] This request's errors, written once at the end of it
	 * @since 7.0
	 */
	protected $pending = [];

	/**
	 * Errors only by default, where Monolog's own default is everything
	 *
	 * @since 7.0
	 */
	/* phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- the default level is the point */
	public function __construct( int $level = MonoLogger::ERROR, bool $bubble = true ) {
		parent::__construct( $level, $bubble );
	}

	/**
	 * @since 7.0
	 */
	public static function get_errors(): array {
		$errors = get_option( static::OPTION, [] );

		return is_array( $errors ) ? $errors : [];
	}

	/**
	 * @since 7.0
	 */
	public static function clear(): void {
		delete_option( static::OPTION );
	}

	/**
	 * @param array $record
	 *
	 * @since 7.0
	 */
	protected function write( array $record ): void {
		/*
		 * Buffered rather than written here: a batch that fails against an unreachable origin logs one error per
		 * file, and the ring only ever keeps the last ten of them anyway. One write per request, at the end of it.
		 */
		if ( $this->pending === [] ) {
			add_action( 'shutdown', [ $this, 'persist' ], 100 );
		}

		$this->pending[] = [
			'time'    => gmdate( 'Y-m-d H:i:s' ),
			'channel' => (string) ( $record['channel'] ?? '' ),
			'level'   => (string) ( $record['level_name'] ?? '' ),
			'message' => $this->truncate( (string) ( $record['message'] ?? '' ) ),
			'context' => $this->context( (array) ( $record['context'] ?? [] ) ),
		];

		/* Bounds the buffer, not the ring: `persist()` slices what it writes, this keeps a long-running batch's
		   thousandth failure from being held in memory alongside 999 the ring will never keep */
		$this->pending = array_slice( $this->pending, -static::SIZE );
	}

	/**
	 * Fold this request's errors into the ring
	 *
	 * @since 7.0
	 */
	public function persist(): void {
		if ( $this->pending === [] ) {
			return;
		}

		/* The eleventh error evicts the first */
		update_option( static::OPTION, array_slice( array_merge( static::get_errors(), $this->pending ), -static::SIZE ), false );

		$this->pending = [];
	}

	/**
	 * The context as identifiers, one level deep
	 *
	 * A caller can put anything in a context, including an object that holds an entire mPDF instance. What belongs
	 * in an option a support ticket carries is what identifies the failure, so anything that is not a scalar is
	 * named by its type and left behind.
	 *
	 * @since 7.0
	 */
	protected function context( array $context ): string {
		$flat = [];

		foreach ( $context as $key => $value ) {
			$flat[ $key ] = ( is_scalar( $value ) || $value === null ) ? $value : '(' . gettype( $value ) . ')';
		}

		return $this->truncate( (string) wp_json_encode( $flat, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}

	/**
	 * @since 7.0
	 */
	protected function truncate( string $value ): string {
		return strlen( $value ) > static::MAX_MESSAGE ? substr( $value, 0, static::MAX_MESSAGE ) . '…' : $value;
	}
}
