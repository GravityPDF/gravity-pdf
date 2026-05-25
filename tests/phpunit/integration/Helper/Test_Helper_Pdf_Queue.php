<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   pdf-queue
 */
class Test_Helper_Pdf_Queue extends TestCase {

	private Helper_Pdf_Queue $queue;

	public function set_up(): void {
		parent::set_up();

		global $gfpdf;

		$this->queue = new Helper_Pdf_Queue( $gfpdf->log );
	}

	public function test_push_to_queue_appends_item(): void {
		$item = [
			'id'   => 'entry-1',
			'func' => 'strlen',
			'args' => [ 'hello' ],
		];

		$this->queue->push_to_queue( $item );

		$data = $this->queue->get_data();
		$this->assertNotEmpty( $data );
		$this->assertContains( $item, $data );
	}

	public function test_push_to_queue_accumulates_multiple_items(): void {
		$a = [ 'id' => 'a', 'func' => 'strlen', 'args' => [ 'a' ] ];
		$b = [ 'id' => 'b', 'func' => 'strlen', 'args' => [ 'b' ] ];

		$this->queue->push_to_queue( $a );
		$this->queue->push_to_queue( $b );

		$data = $this->queue->get_data();
		$this->assertCount( 2, $data );
	}

	public function test_task_calls_callback_and_returns_false_when_queue_empty(): void {
		$called = false;

		$callbacks = [
			[
				'id'   => 'test-task',
				'func' => static function () use ( &$called ): void {
					$called = true;
				},
				'args' => [],
			],
		];

		$result = $this->queue->task( $callbacks );

		$this->assertTrue( $called );
		$this->assertFalse( $result );
	}

	public function test_task_returns_remaining_callbacks_when_queue_not_empty(): void {
		$callbacks = [
			[
				'id'   => 'first',
				'func' => 'strlen',
				'args' => [ 'a' ],
			],
			[
				'id'   => 'second',
				'func' => 'strlen',
				'args' => [ 'b' ],
			],
		];

		$remaining = $this->queue->task( $callbacks );

		$this->assertIsArray( $remaining );
		$this->assertCount( 1, $remaining );
		$this->assertSame( 'second', $remaining[0]['id'] );
	}

	public function test_task_returns_false_for_invalid_item(): void {
		$callbacks = [ [ 'broken' => true ] ];

		$result = $this->queue->task( $callbacks );

		$this->assertFalse( $result );
	}

	public function test_task_requeues_item_on_exception(): void {
		$callbacks = [
			[
				'id'   => 'throws',
				'func' => static function (): void {
					throw new \RuntimeException( 'simulated failure' );
				},
				'args' => [],
			],
		];

		$remaining = $this->queue->task( $callbacks );

		$this->assertIsArray( $remaining );
		$this->assertCount( 1, $remaining );
		$this->assertSame( 1, $remaining[0]['retry'] );
	}
}
