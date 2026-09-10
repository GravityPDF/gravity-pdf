<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\QueuesFontInstalls;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Install_Status
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Install_Status extends TestCase {

	use HasCatalogRows;
	use HasFontRows;
	use QueuesFontInstalls;

	/**
	 * @var Registry
	 */
	public $registry;

	/**
	 * @var Install_Queue
	 */
	public $queue;

	public function set_up(): void {
		parent::set_up();

		global $gfpdf;

		$this->registry = $gfpdf->get_font_registry();
		$this->queue    = $this->install_queue();

		$this->font_repository()->ensure_ready();
		$this->drop_catalog_rows();
		$this->block_dispatch();
	}

	public function tear_down(): void {
		$this->reset_queue();
		$this->remove_font_rows();
		$this->drop_catalog_rows();
		$this->font_repository()->flush();

		parent::tear_down();
	}

	/**
	 * The poller's read, which is the one that re-dispatches: the System Report's does not
	 */
	protected function statuses(): array {
		return $this->registry->get_install_statuses( $this->queue, true );
	}

	/**
	 * Move an entry into a phase that started `$ago` seconds back
	 */
	protected function set_phase( string $entry, string $phase, int $ago = 0, array $extra = [] ): void {
		$this->catalog_repository()->set_status(
			'packs',
			$entry,
			array_merge(
				[
					'phase'       => $phase,
					'phase_since' => gmdate( 'Y-m-d H:i:s', time() - $ago ),
				],
				$extra
			)
		);
	}

	public function test_an_entry_with_rows_reports_installed_and_its_file_count() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->install_entry_row( 'noto1', 'emoji', [], [ 'R', 'B' ] );

		$status = $this->statuses()['packs/emoji'] ?? [];

		$this->assertTrue( $status['installed'] );
		$this->assertSame( 2, $status['files_done'] );
		$this->assertNull( $status['phase'] );
		$this->assertFalse( $status['update_available'] );
		$this->assertArrayNotHasKey( 'stuck', $status );
	}

	public function test_a_file_two_rows_of_one_entry_share_is_counted_once() {
		$this->insert_catalog_row( 'packs', 'cjk', [ 'coverage' => 1 ] );

		$path = 'test-shared.ttf';
		file_put_contents( $this->font_dir() . $path, 'ttf' );

		foreach ( [ 'notosc', 'nototc' ] as $font_key ) {
			$this->font_repository()->insert(
				[
					'font_key' => $font_key,
					'label'    => $font_key,
					'source'   => 'packs',
					'entry'    => 'cjk',
					'coverage' => 1,
					'version'  => 'fonts-v1.0.0',
					'files'    => [ 'R' => [ 'path' => $path, 'size' => 3 ] ],
				]
			);
		}

		/* `files_done` is progress against the entry's file count, and the entry downloads that file once */
		$this->assertSame( 1, $this->statuses()['packs/cjk']['files_done'] );
	}

	public function test_an_entry_with_a_phase_and_no_rows_still_reports() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase( 'emoji', 'queued' );

		$status = $this->statuses()['packs/emoji'] ?? [];

		$this->assertSame( 'queued', $status['phase'] );
		$this->assertFalse( $status['installed'] );
		$this->assertSame( 0, $status['files_done'] );
	}

	public function test_an_entry_mid_install_is_reported_alongside_an_installed_one() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->insert_catalog_row( 'packs', 'cjk', [ 'coverage' => 1 ] );
		$this->install_entry_row( 'noto1', 'emoji' );
		$this->set_phase( 'cjk', 'installing' );

		/* The entry being installed has no rows to name it, so only the phase arm of the query can find it */
		$statuses = $this->statuses();

		$this->assertSame( [ 'packs/cjk', 'packs/emoji' ], array_keys( $statuses ) );
		$this->assertSame( 'installing', $statuses['packs/cjk']['phase'] );
		$this->assertFalse( $statuses['packs/cjk']['installed'] );
	}

	public function test_rows_that_belong_to_no_entry_are_never_listed() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->install_entry_row( 'noto1', 'emoji' );
		$this->install_font_row( 'myupload' );

		/* An upload has no `entry`, so there is no install to report progress on */
		$this->assertSame( [ 'packs/emoji' ], array_keys( $this->statuses() ) );
	}

	public function test_a_failed_entry_carries_its_error_and_backoff() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase(
			'emoji',
			'failed',
			0,
			[
				'error'       => 'The font could not be downloaded',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ),
			]
		);

		$status = $this->statuses()['packs/emoji'];

		$this->assertSame( 'failed', $status['phase'] );
		$this->assertSame( 'The font could not be downloaded', $status['error'] );
		$this->assertNotEmpty( $status['retry_after'] );
		$this->assertArrayNotHasKey( 'stuck', $status );
	}

	public function test_a_live_entry_that_has_not_moved_for_fifteen_minutes_is_stuck() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase( 'emoji', 'installing', Registry::STUCK_AFTER + 60 );

		$this->assertTrue( $this->statuses()['packs/emoji']['stuck'] );
	}

	public function test_a_live_entry_that_has_just_moved_is_not_stuck() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase( 'emoji', 'installing', 30 );

		$this->assertArrayNotHasKey( 'stuck', $this->statuses()['packs/emoji'] );
	}

	public function test_a_batch_being_processed_is_never_stuck() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase( 'emoji', 'installing', Registry::STUCK_AFTER + 60 );

		$this->lock_queue();

		/* An install that is slow is not an install that is dead */
		$this->assertArrayNotHasKey( 'stuck', $this->statuses()['packs/emoji'] );
	}

	public function test_a_stalled_batch_is_re_dispatched_by_the_poll() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase( 'emoji', 'queued', Registry::NUDGE_AFTER + 10 );

		$this->queue->push_to_queue(
			[
				'source' => 'packs',
				'entry'  => 'emoji',
				'name'   => 'Noto.ttf',
			]
		)->save();

		$this->statuses();

		$this->assertSame( 1, $this->dispatches() );
	}

	/**
	 * The System Report reads the same statuses and must not re-dispatch anything
	 */
	public function test_a_status_read_that_did_not_ask_dispatches_nothing() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase( 'emoji', 'queued', Registry::NUDGE_AFTER + 10 );

		$this->queue->push_to_queue(
			[
				'source' => 'packs',
				'entry'  => 'emoji',
				'name'   => 'Noto.ttf',
			]
		)->save();

		$this->registry->get_install_statuses( $this->queue );

		$this->assertSame( 0, $this->dispatches() );
	}

	public function test_a_batch_that_has_only_just_been_queued_is_left_alone() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase( 'emoji', 'queued', 5 );

		$this->queue->push_to_queue(
			[
				'source' => 'packs',
				'entry'  => 'emoji',
				'name'   => 'Noto.ttf',
			]
		)->save();

		$this->statuses();

		$this->assertSame( 0, $this->dispatches() );
	}

	public function test_a_processing_batch_is_never_re_dispatched() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->set_phase( 'emoji', 'queued', Registry::NUDGE_AFTER + 10 );

		$this->queue->push_to_queue(
			[
				'source' => 'packs',
				'entry'  => 'emoji',
				'name'   => 'Noto.ttf',
			]
		)->save();

		$this->lock_queue();

		$this->statuses();

		$this->assertSame( 0, $this->dispatches() );
	}

	public function test_a_row_behind_the_catalogue_reports_an_update() {
		$this->insert_catalog_row(
			'packs',
			'emoji',
			[
				'coverage' => 1,
				'version'  => 'fonts-v2.0.0',
				'notes'    => 'Unicode 16 additions',
				'released' => '2026-08-01',
				'files'    => 4,
				'size'     => 2048,
			]
		);
		$this->install_entry_row( 'noto1', 'emoji', [ 'version' => 'fonts-v1.0.0' ] );

		$status = $this->statuses()['packs/emoji'];

		$this->assertTrue( $status['update_available'] );
		$this->assertSame(
			[
				'installed_version' => 'fonts-v1.0.0',
				'version'           => 'fonts-v2.0.0',
				'notes'             => 'Unicode 16 additions',
				'released'          => '2026-08-01',
				'files'             => 4,
				'size'              => 2048,
			],
			$status['update']
		);
	}

	public function test_a_row_on_the_catalogue_version_reports_no_update() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1, 'version' => 'fonts-v1.0.0' ] );
		$this->install_entry_row( 'noto1', 'emoji', [ 'version' => 'fonts-v1.0.0' ] );

		$status = $this->statuses()['packs/emoji'];

		$this->assertFalse( $status['update_available'] );
		$this->assertArrayNotHasKey( 'update', $status );
	}

	public function test_an_entry_with_no_rows_never_reports_an_update() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1, 'version' => 'fonts-v2.0.0' ] );
		$this->set_phase( 'emoji', 'queued' );

		/* Nothing is installed, so there is nothing to update — this is an install, and the card says so */
		$this->assertFalse( $this->statuses()['packs/emoji']['update_available'] );
	}

	public function test_an_entry_the_catalogue_has_dropped_reports_installed_without_an_update() {
		$this->install_entry_row( 'noto1', 'emoji', [ 'version' => 'fonts-v1.0.0' ] );

		$status = $this->statuses()['packs/emoji'];

		$this->assertTrue( $status['installed'] );
		$this->assertNull( $status['phase'] );
		$this->assertFalse( $status['update_available'] );
	}

	public function test_a_row_this_site_has_hidden_still_counts_as_installed() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$font_id = $this->install_entry_row( 'noto1', 'emoji' );

		$this->font_repository()->set_site_enabled( $font_id, get_current_blog_id(), false );

		/* The toggle hides a font from one site's dropdown; the files are still on disk and the entry is still installed */
		$status = $this->statuses()['packs/emoji'];

		$this->assertTrue( $status['installed'] );
		$this->assertSame( 1, $status['files_done'] );
	}

	public function test_one_entry_is_read_by_id() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->install_entry_row( 'noto1', 'emoji' );

		$this->assertTrue( $this->registry->get_install_status( 'packs/emoji', $this->queue )['installed'] );
	}

	public function test_the_queue_refuses_to_nudge_a_batch_it_is_already_processing() {
		$this->queue->push_to_queue(
			[
				'source' => 'packs',
				'entry'  => 'emoji',
				'name'   => 'Noto.ttf',
			]
		)->save();

		$this->lock_queue();

		$this->assertFalse( $this->queue->nudge() );
		$this->assertSame( 0, $this->dispatches() );
	}

	public function test_the_queue_refuses_to_nudge_when_there_is_nothing_queued() {
		$this->assertFalse( $this->queue->nudge() );
		$this->assertSame( 0, $this->dispatches() );
	}

	public function test_the_queue_nudges_an_outstanding_batch_nothing_is_processing() {
		$this->queue->push_to_queue(
			[
				'source' => 'packs',
				'entry'  => 'emoji',
				'name'   => 'Noto.ttf',
			]
		)->save();

		$this->queue->nudge();

		$this->assertSame( 1, $this->dispatches() );
	}

	public function test_an_unknown_id_reads_as_nothing_installed() {
		$status = $this->registry->get_install_status( 'packs/nope', $this->queue );

		$this->assertSame(
			[
				'phase'            => null,
				'files_done'       => 0,
				'installed'        => false,
				'update_available' => false,
			],
			$status
		);
	}
}
