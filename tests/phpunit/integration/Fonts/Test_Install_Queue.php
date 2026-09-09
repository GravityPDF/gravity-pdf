<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\HasFontFixtures;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use GFPDF\Tests\Concerns\QueuesFontInstalls;
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
	use HasFontRows;
	use HasFontFixtures;
	use MocksHttpRequests;
	use QueuesFontInstalls;

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

		$this->queue    = $this->install_queue();
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
	 * Write an index column directly, the way `HasCatalogRows` writes the row: the sync owns them in production
	 */
	protected function set_catalog( array $columns, string $entry = 'emoji' ): void {
		global $gfpdf, $wpdb;

		$wpdb->update(
			$gfpdf->get_font_repository()->get_schema()->get_catalog_table(),
			$columns,
			[
				'source' => 'packs',
				'entry'  => $entry,
			]
		);

		$this->catalog_repository()->flush();
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

	/**
	 * One entry whose files fill named roles at declared sizes: what trigger 3 selects on
	 *
	 * @param array<string, array{0: string, 1: int}> $roles role => [ filename, declared size ]
	 */
	protected function seed_roles( array $roles, string $entry = 'japanese' ): void {
		$fonts = [];
		$files = [];

		foreach ( $roles as $role => $file ) {
			$fonts['notosansjp'][ $role ] = $file[0];
			$files[ $file[0] ]            = [
				'sha256'      => hash( 'sha256', $file[0] ),
				'size'        => $file[1],
				'remote_path' => 'fonts-v1.0.0/' . $file[0],
			];
		}

		$this->insert_catalog_row(
			'packs',
			$entry,
			[
				'coverage'   => 1,
				'scripts'    => 'ja',
				'font_keys'  => 'notosansjp',
				'files'      => count( $files ),
				'entry_json' => (string) wp_json_encode(
					[
						'fonts' => $fonts,
						'files' => $files,
					]
				),
			]
		);
	}

	/**
	 * A render's request: the entry, and the scripts that made it ask
	 */
	protected function render_request( string $entry = 'japanese' ): array {
		return [
			'entry'   => 'packs/' . $entry,
			'scripts' => [ 'ja' ],
		];
	}

	public function test_a_render_takes_the_regular_face_now_and_queues_the_rest() {
		$this->seed_roles(
			[
				'R' => [ 'NotoSansJP-Regular.ttf', 1024 ],
				'B' => [ 'NotoSansJP-Bold.ttf', 2048 ],
				'I' => [ 'NotoSansJP-Italic.ttf', 4096 ],
			]
		);

		$inline = $this->queue->enqueue_for_render( [ $this->render_request() ] );

		$this->assertSame( [ 'NotoSansJP-Regular.ttf' ], array_column( $inline, 'name' ) );
		$this->assertSame( 1024, $inline[0]['size'] );
		$this->assertSame( 'packs', $inline[0]['source'] );
		$this->assertSame( 'japanese', $inline[0]['entry'] );

		/* Bold and italic can arrive after this PDF: mPDF draws both from the Regular face until they do */
		$this->assertSame(
			[ 'NotoSansJP-Bold.ttf', 'NotoSansJP-Italic.ttf' ],
			array_column( $this->queued(), 'name' )
		);
		$this->assertSame( 'queued', $this->status( 'japanese' )['phase'] );
	}

	/**
	 * Thai, Khmer and Lao have no spaces, so without the dictionary the first render has nowhere to break a line
	 */
	public function test_a_line_break_dictionary_is_taken_now_as_well() {
		$this->seed_roles(
			[
				'R'       => [ 'NotoSansThai-Regular.ttf', 1024 ],
				'B'       => [ 'NotoSansThai-Bold.ttf', 2048 ],
				'dict_th' => [ 'dict_th.txt', 512 ],
			]
		);

		$inline = $this->queue->enqueue_for_render( [ $this->render_request() ] );

		$this->assertSame( [ 'NotoSansThai-Regular.ttf', 'dict_th.txt' ], array_column( $inline, 'name' ) );
	}

	/**
	 * Every other trigger: no reason means all of it, in the background, which is what they have always meant
	 */
	public function test_a_request_with_no_scripts_queues_everything_and_takes_nothing_now() {
		$this->seed_roles(
			[
				'R' => [ 'NotoSansJP-Regular.ttf', 1024 ],
				'B' => [ 'NotoSansJP-Bold.ttf', 2048 ],
			]
		);

		$this->assertTrue( $this->queue->enqueue_once( [ 'entry' => 'packs/japanese' ] ) );
		$this->assertCount( 2, $this->queued() );
	}

	public function test_a_face_over_the_inline_cap_is_queued_instead_and_the_miss_recorded() {
		$this->seed_roles( [ 'R' => [ 'Sun-ExtB.ttf', Install_Queue::INLINE_CAP + 1 ] ], 'sip-ext' );

		$this->assertSame( [], $this->queue->enqueue_for_render( [ $this->render_request( 'sip-ext' ) ] ) );
		$this->assertSame( [ 'Sun-ExtB.ttf' ], array_column( $this->queued(), 'name' ) );

		$status = $this->status( 'sip-ext' );
		$this->assertSame( 'ja', $status['missing_scripts'] );
		$this->assertNotNull( $status['missing_since'] );
	}

	/**
	 * An entry that declares no size is an unknown, and a cap cannot be applied to one
	 */
	public function test_a_face_with_no_declared_size_is_queued_rather_than_fetched_now() {
		$this->seed_roles( [ 'R' => [ 'NotoSansJP-Regular.ttf', 0 ] ] );

		$this->assertSame( [], $this->queue->enqueue_for_render( [ $this->render_request() ] ) );
		$this->assertSame( [ 'NotoSansJP-Regular.ttf' ], array_column( $this->queued(), 'name' ) );
	}

	public function test_a_render_asks_for_nothing_while_auto_install_is_off() {
		$this->seed_roles( [ 'R' => [ 'NotoSansJP-Regular.ttf', 1024 ] ] );
		GPDFAPI::get_options_class()->update_option( 'auto_install_fonts', 'No' );

		$this->assertSame( [], $this->queue->enqueue_for_render( [ $this->render_request() ] ) );

		GPDFAPI::get_options_class()->update_option( 'auto_install_fonts', 'Yes' );
	}

	/**
	 * The second render of the same entry has an install in flight, and re-fetching its faces would double it
	 */
	public function test_a_second_render_while_the_install_is_in_flight_asks_for_nothing() {
		$this->seed_roles(
			[
				'R' => [ 'NotoSansJP-Regular.ttf', 1024 ],
				'B' => [ 'NotoSansJP-Bold.ttf', 2048 ],
			]
		);

		$this->assertCount( 1, $this->queue->enqueue_for_render( [ $this->render_request() ] ) );
		$this->assertSame( [], $this->queue->enqueue_for_render( [ $this->render_request() ] ) );
	}

	/**
	 * A pack whose Regular face landed but whose bold did not must not pay for the Regular again
	 */
	public function test_a_face_already_on_disk_is_not_fetched_again() {
		$this->seed_roles(
			[
				'R' => [ 'NotoSansJP-Regular.ttf', 1024 ],
				'B' => [ 'NotoSansJP-Bold.ttf', 2048 ],
			]
		);

		$this->install_entry_row(
			'notosansjp',
			'packs/japanese',
			[
				'files' => [
					'R' => [
						'path'   => 'packs/japanese/NotoSansJP-Regular.ttf',
						'size'   => 1024,
						'sha256' => hash( 'sha256', 'NotoSansJP-Regular.ttf' ),
					],
				],
			]
		);

		$this->assertSame( [], $this->queue->enqueue_for_render( [ $this->render_request() ] ) );
		$this->assertSame( [ 'NotoSansJP-Bold.ttf' ], array_column( $this->queued(), 'name' ) );
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

	public function test_a_request_naming_no_files_is_given_the_entrys_own() {
		$names = $this->seed_pack( 3 );

		$this->assertTrue( $this->queue->enqueue_once( [ 'entry' => 'packs/emoji' ] ) );
		$this->assertSame( $names, array_column( $this->queued(), 'name' ) );
	}

	/**
	 * An entry with no inlined document reaches the wire to name its own files, so it is what proves a read
	 */
	protected function seed_pointer_entry(): void {
		$this->insert_catalog_row(
			'packs',
			'pointed-at',
			[
				'coverage'     => 1,
				'files'        => 1,
				/* No `entry_json`, but a hash to fetch one by: everything a pointer source needs to reach the wire */
				'entry_sha256' => hash( 'sha256', 'never asked for' ),
			]
		);

		$this->mock_http( [ 'fonts.gravitypdf.com' => 'never asked for' ] );
	}

	public function test_an_entry_with_every_file_installed_is_never_read() {
		$this->seed_pointer_entry();
		$this->install_entry_row( 'pointed', 'pointed-at' );

		$this->assertFalse( $this->queue->enqueue_once( [ 'entry' => 'packs/pointed-at' ] ) );
		$this->assertSame( [], $this->requested_urls() );
		$this->assertSame( [], $this->queued() );
	}

	/**
	 * The install route names the files a chosen set of variants resolves to, which is rarely the entry's whole list
	 */
	public function test_a_caller_that_names_its_files_gets_exactly_those() {
		$names = $this->seed_pack( 3 );

		$this->assertTrue( $this->queue->enqueue_once( $this->request( [ $names[1] ] ) ) );
		$this->assertSame( [ $names[1] ], array_column( $this->queued(), 'name' ) );
	}

	public function test_an_entry_with_no_catalog_row_queues_nothing() {
		$this->assertFalse( $this->queue->enqueue_once( [ 'entry' => 'packs/nothing-here' ] ) );
		$this->assertSame( [], $this->queued() );
	}

	/**
	 * The gate comes first, so a site with auto-install off never pays to read an entry it will not install
	 */
	public function test_the_gate_refuses_before_the_entry_is_read() {
		$this->seed_pointer_entry();

		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );
		$queued = $this->queue->enqueue_once( [ 'entry' => 'packs/pointed-at' ] );
		remove_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$this->assertFalse( $queued );
		$this->assertSame( [], $this->requested_urls() );
	}

	/**
	 * `files` defaults to 0 and the index need not declare one, so a 0 must mean "ask", never "already done"
	 */
	public function test_an_entry_declaring_no_file_count_is_still_read() {
		$names = $this->seed_pack( 1 );
		$this->set_catalog( [ 'files' => 0 ] );

		$this->assertTrue( $this->queue->enqueue_once( [ 'entry' => 'packs/emoji' ] ) );
		$this->assertSame( $names, array_column( $this->queued(), 'name' ) );
	}

	public function test_the_hourly_retry_resolves_the_files_it_never_named() {
		$names = $this->seed_pack( 2 );

		$this->set_status(
			[
				'phase'       => 'failed',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			]
		);

		$this->assertSame( 1, $this->queue->maybe_retry() );
		$this->assertSame( $names, array_column( $this->queued(), 'name' ) );
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

	public function test_a_forcing_request_queues_files_that_already_have_rows() {
		$names = $this->seed_pack( 1 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );
		$this->installer()->install_file( 'packs', 'emoji', $names[0] );
		$this->unmock_http();

		/*
		 * What the install route asks for on both of its jobs: an update leaves the file at the same path and
		 * changes only its contents, and a further install of a display entry needs rows disjoint from the ones
		 * already there. Dropping the file because *some* row records it would leave both with nothing to do.
		 */
		$request            = $this->request( $names );
		$request['install'] = [ 'label' => 'Second Copy' ];
		$request['force']   = true;

		$this->assertTrue( $this->queue->enqueue_once( $request, true ) );
		$this->assertCount( 1, $this->queued() );
		$this->assertSame( [ 'label' => 'Second Copy' ], $this->queued()[0]['install'] );
	}

	public function test_an_install_payload_alone_no_longer_forces() {
		$names = $this->seed_pack( 1 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => $this->file_bytes( 1 ) ] );
		$this->installer()->install_file( 'packs', 'emoji', $names[0] );
		$this->unmock_http();
		$this->set_status( [ 'phase' => null ] );

		$request            = $this->request( $names );
		$request['install'] = [ 'label' => 'Second Copy' ];

		/* The drop is the trigger's default and `force` is the only thing that lifts it — one flag, one meaning */
		$this->assertFalse( $this->queue->enqueue_once( $request, true ) );
		$this->assertSame( [], $this->queued() );
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

	/**
	 * The pointer form: the index names a hash, and the entry itself is a separate document over HTTPS
	 */
	protected function seed_pointer_pack( int $count = 5 ): void {
		$entry = [
			'fonts' => [],
			'files' => [],
		];

		for ( $i = 1; $i <= $count; $i++ ) {
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

		$this->insert_catalog_row(
			'packs',
			'emoji',
			[
				'coverage'     => 1,
				'files'        => $count,
				'entry_json'   => null,
				'entry_sha256' => hash( 'sha256', $json ),
			]
		);

		$this->mock_http( [ 'fonts.gravitypdf.com' => $json ] );
	}

	/**
	 * @return string[] The entry-document requests made so far
	 */
	protected function entry_requests(): array {
		return array_values(
			array_filter(
				$this->requested_urls(),
				static function ( string $url ): bool {
					return strpos( $url, '/entries/' ) !== false;
				}
			)
		);
	}

	public function test_a_multi_file_entry_fetches_its_entry_file_once() {
		$this->seed_pointer_pack();

		$this->assertSame( 5, count( (array) $this->installer()->plan_for( 'packs/emoji' ) ) );

		/* Five queued items must not mean five round trips for one unchanged ~1 KB document */
		$this->assertCount( 1, $this->entry_requests() );

		$this->installer()->plan_for( 'packs/emoji' );
		$this->assertCount( 1, $this->entry_requests() );
	}

	/**
	 * A trigger fires repeatedly and trigger 3 fires on the render path, so an entry already being installed has
	 * to be refused before its document is read — which for a pointer source is an HTTPS round trip
	 */
	public function test_a_trigger_that_cannot_claim_never_reads_the_entry() {
		/* A file count of its own, so the entry hashes differently from the fixture the test above memoised */
		$this->seed_pointer_pack( 4 );

		$this->assertTrue( $this->queue->enqueue_once( [ 'entry' => 'packs/emoji' ] ) );
		$this->assertCount( 1, $this->entry_requests() );

		/* A second *request*, which is the case that matters: the first one's in-memory entry memo is gone with it */
		$this->assertFalse( $this->fresh_queue()->enqueue_once( [ 'entry' => 'packs/emoji' ] ) );
		$this->assertCount( 1, $this->entry_requests() );
	}

	/**
	 * The queue as the next request would build it, memos and all
	 */
	protected function fresh_queue(): Install_Queue {
		global $gfpdf;

		return new Install_Queue(
			$gfpdf->get_font_repository(),
			$this->catalog_repository(),
			new Font_Installer(
				$gfpdf->get_font_repository(),
				$this->catalog_repository(),
				$gfpdf->get_font_downloader(),
				$gfpdf->get_font_cache_warmer(),
				new Font_Lock(),
				$gfpdf->log
			),
			$gfpdf->get_font_registry(),
			$gfpdf->log
		);
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
