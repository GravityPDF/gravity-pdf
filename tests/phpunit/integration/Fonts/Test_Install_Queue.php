<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontFixtures;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Install_Queue
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Install_Queue extends TestCase {

	use HasCatalogRows;
	use HasFontFixtures;
	use MocksHttpRequests;

	/**
	 * @var Install_Queue
	 */
	public $queue;

	/**
	 * @var string
	 */
	public $font_dir;

	public function set_up(): void {
		parent::set_up();

		global $gfpdf;

		$this->queue    = $gfpdf->get_install_queue();
		$this->font_dir = $gfpdf->get_font_repository()->get_font_dir();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();
	}

	public function tear_down(): void {
		global $gfpdf;

		$this->unmock_http();
		$this->drop_catalog_rows();
		$this->queue->clear_queue();

		foreach ( [ 'packs', 'google', 'ghost' ] as $source ) {
			GPDFAPI::get_misc_class()->rmdir( $this->font_dir . $source );
		}

		$gfpdf->get_font_repository()->flush();

		parent::tear_down();
	}

	/**
	 * A coverage entry of `$count` files, each with the bytes its index describes
	 */
	protected function seed_pack( int $count = 1, string $entry = 'emoji' ): array {
		$fonts = [];
		$files = [];
		$names = [];

		for ( $i = 1; $i <= $count; $i++ ) {
			$name    = sprintf( 'Noto-%d.ttf', $i );
			$body    = $this->file_bytes( $i );
			$names[] = $name;

			$fonts[ 'noto' . $i ] = [ 'R' => $name ];
			$files[ $name ]       = [
				'sha256'      => hash( 'sha256', $body ),
				'size'        => strlen( $body ),
				'remote_path' => 'fonts-v1.0.0/' . $name,
			];
		}

		$this->insert_catalog_row(
			'packs',
			$entry,
			[
				'coverage'   => 1,
				'files'      => $count,
				'entry_json' => (string) wp_json_encode(
					[
						'fonts' => $fonts,
						'files' => $files,
					]
				),
			]
		);

		return $names;
	}

	/**
	 * A distinct real face per file, cycling: an install now ends by parsing what it downloaded
	 */
	protected function file_bytes( int $i ): string {
		$faces = [ 'DejaVuSansSymbols', 'Arimo-Regular', 'Arimo-Bold', 'Arimo-Italic', 'Arimo-BoldItalic' ];

		return $this->font_bytes( $faces[ ( $i - 1 ) % count( $faces ) ] );
	}

	protected function status( string $entry = 'emoji' ): array {
		return (array) $this->catalog_repository()->entry( 'packs', $entry );
	}

	protected function set_status( array $fields, string $entry = 'emoji' ): void {
		$this->catalog_repository()->set_status( 'packs', $entry, $fields );
	}

	/**
	 * Every item sitting in the batch store
	 *
	 * Not `get_data()`: GF's `save()` empties the buffer as it persists, so after a `flush()` the only place the
	 * work exists is the store.
	 */
	protected function queued(): array {
		$items = [];

		foreach ( $this->queue->get_batches() as $batch ) {
			foreach ( (array) $batch->data as $item ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	protected function installer(): Font_Installer {
		global $gfpdf;

		return $gfpdf->get_font_installer();
	}

	protected function request( array $names, string $entry = 'emoji' ): array {
		return [
			'entry'      => 'packs/' . $entry,
			'background' => $names,
		];
	}

	public function test_an_entry_is_queued_one_item_per_file_and_claimed() {
		$names = $this->seed_pack( 3 );

		$this->assertTrue( $this->queue->enqueue_once( $this->request( $names ) ) );

		$data = $this->queued();
		$this->assertCount( 3, $data );
		$this->assertSame( 'packs', $data[0]['source'] );
		$this->assertSame( 'emoji', $data[0]['entry'] );
		$this->assertSame( $names, array_column( $data, 'name' ) );

		/* The claim is what makes the push safe to repeat, so it has to have happened */
		$this->assertSame( 'queued', $this->status()['phase'] );
		$this->assertNotNull( $this->status()['phase_since'] );
	}

	public function test_a_second_enqueue_of_a_claimed_entry_pushes_nothing() {
		$names = $this->seed_pack( 2 );

		$this->assertTrue( $this->queue->enqueue_once( $this->request( $names ) ) );
		$this->assertFalse( $this->queue->enqueue_once( $this->request( $names ) ) );

		/* Two triggers on one request must leave each file queued exactly once */
		$this->assertCount( 2, $this->queued() );
	}

	public function test_files_that_already_have_a_row_are_never_queued() {
		$names = $this->seed_pack( 2 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );
		$this->installer()->install_file( 'packs', 'emoji', $names[0] );
		$this->unmock_http();

		/* A phase is left behind by that install; clear it so only the file-row check can drop anything */
		$this->set_status( [ 'phase' => null ] );

		$this->assertTrue( $this->queue->enqueue_once( $this->request( $names ) ) );
		$this->assertSame( [ $names[1] ], array_column( $this->queued(), 'name' ) );
	}

	public function test_an_entry_whose_files_all_have_rows_is_not_claimed() {
		$names = $this->seed_pack( 1 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );
		$this->installer()->install_file( 'packs', 'emoji', $names[0] );
		$this->unmock_http();

		$this->assertFalse( $this->queue->enqueue_once( $this->request( $names ) ) );
		$this->assertSame( [], $this->queued() );
	}

	public function test_a_failed_entry_inside_its_backoff_is_not_re_queued() {
		$names = $this->seed_pack( 1 );

		$this->set_status(
			[
				'phase'       => 'failed',
				'error'       => 'font_http_error',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ),
			]
		);

		$this->assertFalse( $this->queue->enqueue_once( $this->request( $names ) ) );
	}

	public function test_a_failed_entry_past_its_backoff_is_re_queued() {
		$names = $this->seed_pack( 1 );

		$this->set_status(
			[
				'phase'       => 'failed',
				'error'       => 'font_http_error',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			]
		);

		$this->assertTrue( $this->queue->enqueue_once( $this->request( $names ) ) );
		$this->assertSame( 'queued', $this->status()['phase'] );
	}

	public function test_a_manual_install_ignores_the_backoff() {
		$names = $this->seed_pack( 1 );

		$this->set_status(
			[
				'phase'       => 'failed',
				'error'       => 'font_http_error',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
			]
		);

		$this->assertFalse( $this->queue->enqueue_once( $this->request( $names ) ) );
		$this->assertTrue( $this->queue->enqueue_once( $this->request( $names ), true ) );
	}

	public function test_a_removed_entry_is_resurrected_only_by_a_manual_install() {
		$names = $this->seed_pack( 1 );

		$this->set_status( [ 'phase' => 'removed' ] );

		/* A pre-render trigger must never bring back a pack the admin deleted */
		$this->assertFalse( $this->queue->enqueue_once( $this->request( $names ) ) );
		$this->assertTrue( $this->queue->enqueue_once( $this->request( $names ), true ) );
	}

	public function test_a_stale_queued_row_is_re_claimed() {
		$names = $this->seed_pack( 1 );

		/* A batch whose process died leaves this behind; nothing else would ever pick the entry up again */
		$this->set_status(
			[
				'phase'       => 'queued',
				'phase_since' => gmdate( 'Y-m-d H:i:s', time() - Catalog_Repository::STALE_AFTER - MINUTE_IN_SECONDS ),
			]
		);

		$this->assertTrue( $this->queue->enqueue_once( $this->request( $names ) ) );
	}

	public function test_a_live_installing_row_is_not_re_claimed() {
		$names = $this->seed_pack( 1 );

		$this->set_status( [ 'phase' => 'installing' ] );

		$this->assertFalse( $this->queue->enqueue_once( $this->request( $names ) ) );
	}

	public function test_the_auto_install_gate_drops_background_work_but_never_a_manual_install() {
		$names = $this->seed_pack( 1 );

		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$this->assertFalse( $this->queue->enqueue_once( $this->request( $names ) ) );
		$this->assertTrue( $this->queue->enqueue_once( $this->request( $names ), true ) );

		remove_filter( 'gfpdf_auto_install_fonts', '__return_false' );
	}

	public function test_a_manual_install_under_its_own_key_queues_files_that_already_have_rows() {
		$names = $this->seed_pack( 1 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );
		$this->installer()->install_file( 'packs', 'emoji', $names[0] );
		$this->unmock_http();

		/*
		 * A further install of a display entry needs its own rows, and those are disjoint from the ones already
		 * there — dropping the file because *some* row records it would leave the new install with nothing to do.
		 */
		$request             = $this->request( $names );
		$request['install']  = [ 'label' => 'Second Copy' ];

		$this->assertTrue( $this->queue->enqueue_once( $request, true ) );
		$this->assertCount( 1, $this->queued() );
		$this->assertSame( [ 'label' => 'Second Copy' ], $this->queued()[0]['install'] );
	}

	public function test_running_the_queue_installs_the_file_and_clears_the_phase() {
		$names = $this->seed_pack( 1 );
		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );

		$this->queue->enqueue_once( $this->request( $names ) );
		$this->assertSame( 1, $this->queue->run_inline() );

		$this->assertFileExists( $this->font_dir . 'packs/emoji/Noto-1.ttf' );
		$this->assertNull( $this->status()['phase'] );
	}

	public function test_a_multi_file_entry_stays_installing_until_its_last_file_lands() {
		$names = $this->seed_pack( 2 );
		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );

		$installer = $this->installer();

		$installer->install_file( 'packs', 'emoji', $names[0] );
		$this->assertSame( 'installing', $this->status()['phase'] );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 2 ) ] );
		$installer->install_file( 'packs', 'emoji', $names[1] );
		$this->assertNull( $this->status()['phase'] );
	}

	public function test_a_queued_install_of_a_removed_entry_is_dropped_without_fetching() {
		$names = $this->seed_pack( 1 );
		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );

		$this->queue->enqueue_once( $this->request( $names ) );

		/* The admin deleted the pack between enqueue and run */
		$this->set_status( [ 'phase' => 'removed' ] );

		$this->queue->run_inline();

		$this->assertFileDoesNotExist( $this->font_dir . 'packs/emoji/Noto-1.ttf' );
		$this->assertSame( 'removed', $this->status()['phase'] );
	}

	public function test_a_queued_install_of_an_unregistered_source_is_dropped() {
		$this->insert_catalog_row(
			'ghost',
			'emoji',
			[
				'coverage'   => 1,
				'files'      => 1,
				'entry_json' => (string) wp_json_encode(
					[
						'fonts' => [ 'ghost' => [ 'R' => 'Ghost.ttf' ] ],
						'files' => [
							'Ghost.ttf' => [
								'sha256'      => hash( 'sha256', 'GHOST' ),
								'size'        => 5,
								'remote_path' => 'fonts-v1.0.0/Ghost.ttf',
							],
						],
					]
				),
			]
		);

		$this->mock_http( [ 'fonts.gravitypdf.com' => 'GHOST' ] );

		$this->assertTrue(
			$this->queue->enqueue_once(
				[
					'entry'      => 'ghost/emoji',
					'background' => [ 'Ghost.ttf' ],
				]
			)
		);

		$this->queue->run_inline();

		/* An add-on's source going away is the admin's own change, so the row is left alone rather than failed */
		$this->assertFileDoesNotExist( $this->font_dir . 'ghost/emoji/Ghost.ttf' );
		$this->assertNotSame( 'failed', (string) $this->catalog_repository()->entry( 'ghost', 'emoji' )['phase'] );
	}

	public function test_run_inline_drains_every_outstanding_batch() {
		$names = $this->seed_pack( 3 );
		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );

		$this->queue->enqueue_once( $this->request( $names ) );

		/* Nothing is left behind when the budget is never reached */
		$this->assertSame( 3, $this->queue->run_inline() );
		$this->assertSame( [], $this->queued() );
	}

	public function test_the_hourly_retry_re_queues_a_failed_entry_past_its_backoff() {
		$this->seed_pack( 2 );

		$this->set_status(
			[
				'phase'       => 'failed',
				'error'       => 'font_http_error',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			]
		);

		$this->assertSame( 1, $this->queue->maybe_retry() );

		/* It resolves the entry itself, so every outstanding file comes back */
		$this->assertCount( 2, $this->queued() );
		$this->assertSame( 'queued', $this->status()['phase'] );
	}

	public function test_the_hourly_retry_leaves_an_entry_inside_its_backoff_alone() {
		$this->seed_pack( 1 );

		$this->set_status(
			[
				'phase'       => 'failed',
				'error'       => 'font_http_error',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ),
			]
		);

		$this->assertSame( 0, $this->queue->maybe_retry() );
	}

	public function test_a_first_failure_backs_off_by_the_base_interval() {
		$names = $this->seed_pack( 1 );
		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->corrupt_font_bytes() ] );

		$this->installer()->install_file( 'packs', 'emoji', $names[0] );

		$row = $this->status();
		$this->assertSame( 'failed', $row['phase'] );
		$this->assertBackoffBetween( Font_Installer::RETRY_AFTER, $row['retry_after'] );
	}

	public function test_a_repeat_failure_escalates_the_backoff() {
		$names = $this->seed_pack( 1 );
		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->corrupt_font_bytes() ] );

		$this->installer()->install_file( 'packs', 'emoji', $names[0] );

		/*
		 * The row still carries the first failure's `retry_after` — `claim()` deliberately leaves it — which is
		 * how a repeat is told from a first without a counter column. A host with no egress at all stops
		 * re-batching four times a day.
		 */
		$this->installer()->install_file( 'packs', 'emoji', $names[0] );

		$this->assertBackoffBetween( Font_Installer::RETRY_AFTER_MAX, $this->status()['retry_after'] );
	}

	public function test_a_successful_install_clears_the_backoff() {
		$names = $this->seed_pack( 1 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->corrupt_font_bytes() ] );
		$this->installer()->install_file( 'packs', 'emoji', $names[0] );
		$this->assertNotNull( $this->status()['retry_after'] );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );
		$this->installer()->install_file( 'packs', 'emoji', $names[0] );

		/* Otherwise the next failure would open at 24 h because of one that has since been fixed */
		$this->assertNull( $this->status()['retry_after'] );
		$this->assertNull( $this->status()['error'] );
	}

	public function test_a_multi_file_entry_fetches_its_entry_file_once() {
		$entry = [
			'fonts' => [],
			'files' => [],
		];

		for ( $i = 1; $i <= 5; $i++ ) {
			$name = sprintf( 'Noto-%d.ttf', $i );
			$body = sprintf( 'FONT-BYTES-%d', $i );

			$entry['fonts'][ 'noto' . $i ] = [ 'R' => $name ];
			$entry['files'][ $name ]       = [
				'sha256'      => hash( 'sha256', $body ),
				'size'        => strlen( $body ),
				'remote_path' => 'fonts-v1.0.0/' . $name,
			];
		}

		$json = (string) wp_json_encode( $entry );

		/* The pointer form: the index names a hash, and the entry itself is a separate document */
		$this->insert_catalog_row(
			'packs',
			'emoji',
			[
				'coverage'     => 1,
				'files'        => 5,
				'entry_json'   => null,
				'entry_sha256' => hash( 'sha256', $json ),
			]
		);

		$this->mock_http( [ 'fonts.gravitypdf.com' => $json ] );

		$this->assertSame( 5, count( (array) $this->installer()->files_for( 'packs/emoji' ) ) );

		$entry_requests = array_filter(
			$this->requested_urls(),
			static function ( string $url ): bool {
				return strpos( $url, '/entries/' ) !== false;
			}
		);

		/* Five queued items must not mean five round trips for one unchanged ~1 KB document */
		$this->assertCount( 1, $entry_requests );

		$this->installer()->files_for( 'packs/emoji' );
		$this->assertCount( 1, array_filter( $this->requested_urls(), static function ( string $url ): bool {
			return strpos( $url, '/entries/' ) !== false;
		} ) );
	}

	/**
	 * A `retry_after` is the interval plus up to a full hour of jitter, so it is asserted as a window
	 */
	protected function assertBackoffBetween( int $interval, ?string $retry_after ): void {
		$this->assertNotNull( $retry_after );

		$at = (int) strtotime( $retry_after . ' UTC' );

		$this->assertGreaterThanOrEqual( time() + $interval - 5, $at );
		$this->assertLessThanOrEqual( time() + $interval + Font_Installer::RETRY_JITTER + 5, $at );
	}

	public function test_the_queue_has_its_own_identifier_and_attempt_cap() {
		$pdf_queue = new \GFPDF\Helper\Helper_Pdf_Queue( GPDFAPI::get_log_class() );

		/* Its own action is what gives it its own cron event, process lock and batch store */
		$this->assertNotSame( $pdf_queue->get_identifier(), $this->queue->get_identifier() );
		$this->assertStringContainsString( 'gravitypdf_fonts', $this->queue->get_identifier() );

		/* GF's default of one attempt is raised for this process alone */
		$this->assertSame( Install_Queue::MAX_ATTEMPTS, apply_filters( 'gform_max_async_task_attempts', 1, [], (object) [], $this->queue->get_identifier() ) );
		$this->assertSame( 1, apply_filters( 'gform_max_async_task_attempts', 1, [], (object) [], $pdf_queue->get_identifier() ) );
	}
}
