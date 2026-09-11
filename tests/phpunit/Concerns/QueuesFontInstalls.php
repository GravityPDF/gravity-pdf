<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

use GFPDF\Fonts\Install_Queue;

/**
 * The install queue, and a way to see what is sitting in it.
 *
 * Three suites drive the same background process — the queue's own, the status composition's and the REST route's —
 * and all three need the batch store read back and the loopback kept shut.
 */
trait QueuesFontInstalls {

	/**
	 * @var int Dispatches attempted since `block_dispatch()`
	 */
	protected $dispatches = 0;

	/**
	 * The shared queue the plugin itself uses
	 */
	protected function install_queue(): Install_Queue {
		global $gfpdf;

		return $gfpdf->get_install_queue();
	}

	/**
	 * Stop every dispatch before it reaches the loopback
	 *
	 * A queued install is the assertion; actually running one is a different test. Returns nothing — call
	 * `dispatches()` for the count.
	 */
	protected function block_dispatch(): void {
		$this->dispatches = 0;

		add_filter(
			$this->install_queue()->get_identifier() . '_pre_dispatch',
			function () {
				++$this->dispatches;

				return true;
			}
		);
	}

	/**
	 * How many dispatches were attempted since `block_dispatch()`
	 */
	protected function dispatches(): int {
		return (int) $this->dispatches;
	}

	/**
	 * Put the queue back the way it was found
	 */
	protected function reset_queue(): void {
		$queue = $this->install_queue();

		remove_all_filters( $queue->get_identifier() . '_pre_dispatch' );
		delete_site_transient( $queue->get_identifier() . '_process_lock' );

		$queue->clear_queue();
	}

	/**
	 * Pretend a batch is being worked on right now
	 */
	protected function lock_queue(): void {
		set_site_transient( $this->install_queue()->get_identifier() . '_process_lock', microtime(), 60 );
	}

	/**
	 * Every item sitting in the batch store
	 *
	 * Not `get_data()`: GF's `save()` empties the buffer as it persists, so after a `flush()` the only place the
	 * work exists is the store.
	 */
	protected function queued(): array {
		$items = [];

		foreach ( $this->install_queue()->get_batches() as $batch ) {
			foreach ( (array) $batch->data as $item ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * The install payloads behind the queued items, in the order they were queued
	 *
	 * @return array[]
	 */
	protected function queued_installs(): array {
		return array_column( $this->queued(), 'install' );
	}
}
