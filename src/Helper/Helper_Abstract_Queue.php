<?php

namespace GFPDF\Helper;

use GF_Background_Process;
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
 * What every Gravity PDF background process shares with Gravity Forms' own
 *
 * Extracted from `Helper_Pdf_Queue` when the font installer needed a second process. A subclass supplies `$action`
 * — which is what gives it its own cron event, process lock and batch store, so two of ours never contend — and
 * `task()`.
 *
 * @package GFPDF\Helper
 *
 * @since 7.0
 */
abstract class Helper_Abstract_Queue extends GF_Background_Process {

	/**
	 * Not autoloaded: read on the System Report and nowhere else
	 *
	 * @since 7.0
	 */
	public const DISPATCH_ERROR_OPTION = 'gfpdf_last_dispatch_error';

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 7.0
	 */
	protected $log;

	/**
	 * Restrict object instantiation when using unserialize.
	 *
	 * @since 7.0
	 *
	 * @var bool|array
	 */
	protected $allowed_batch_data_classes = false;

	/**
	 * @since 7.0
	 */
	public function __construct( LoggerInterface $log ) {
		parent::__construct();

		$this->log = $log;
	}

	/**
	 * Add a getter for the stored async data
	 *
	 * @return array
	 *
	 * @since 7.0
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Persist whatever this request pushed, and dispatch it once the response is out
	 *
	 * Returning early on empty is not just an optimisation: a trigger that pushed nothing would otherwise pay
	 * `dispatch_on_shutdown()`'s `is_queue_empty()` read on every request that merely *considered* queuing work.
	 *
	 * Dispatch is deferred to `shutdown` so the request that filled the queue is not the one that waits for it —
	 * on the render path that hook runs after the PDF has already been flushed to the reader.
	 *
	 * @since 7.0
	 */
	public function flush(): void {
		if ( empty( $this->data ) ) {
			return;
		}

		$this->save()->dispatch_on_shutdown();
	}

	/**
	 * Dispatch, and remember it when the loopback request does not come back
	 *
	 * Gravity Forms logs this at debug and returns the error, which on a site with logging off means a queue that
	 * never runs and nothing anywhere saying why. Basic Auth, `WP_HTTP_BLOCK_EXTERNAL` and a Docker DNS that does
	 * not resolve the site's own host all look like this.
	 *
	 * @return array|WP_Error|false
	 *
	 * @since 7.0
	 */
	public function dispatch() {
		$result = parent::dispatch();

		if ( is_wp_error( $result ) ) {
			$this->log->error(
				'Could not dispatch a background queue',
				[
					'queue' => $this->action,
					'error' => $result->get_error_message(),
				]
			);

			update_option( static::DISPATCH_ERROR_OPTION, $result->get_error_message(), false );

			return $result;
		}

		delete_option( static::DISPATCH_ERROR_OPTION );

		return $result;
	}

	/**
	 * The last loopback failure, for the System Report
	 *
	 * @since 7.0
	 */
	public static function get_dispatch_error(): string {
		return (string) get_option( static::DISPATCH_ERROR_OPTION, '' );
	}
}
