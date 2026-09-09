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
}
