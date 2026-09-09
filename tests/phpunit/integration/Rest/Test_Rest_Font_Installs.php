<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Registry;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\QueuesFontInstalls;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group api
 * @group rest
 * @group fonts
 */
class Test_Rest_Font_Installs extends Test_Rest {

	use HasCatalogRows;
	use HasFontRows;
	use QueuesFontInstalls;

	/**
	 * @var \GFPDF\Fonts\Install_Queue
	 */
	public $queue;

	public function set_up(): void {
		parent::set_up();

		$this->queue = $this->install_queue();

		$this->font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		/* Nothing here should reach the network or a loopback: a route queues work, it never performs it */
		$this->block_dispatch();

		wp_set_current_user( self::$admin_id );
	}

	public function tear_down(): void {
		$this->reset_queue();
		$this->remove_font_rows();
		$this->drop_catalog_rows();
		$this->font_repository()->flush();

		parent::tear_down();
	}

	/**
	 * A coverage entry of one file, inlined so the installer never fetches an entry file
	 */
	protected function seed_pack( string $entry = 'emoji', array $overrides = [] ): void {
		$this->insert_catalog_row(
			'packs',
			$entry,
			array_merge(
				[
					'coverage'   => 1,
					'files'      => 1,
					'entry_json' => (string) wp_json_encode(
						[
							'fonts' => [ 'noto' . $entry => [ 'R' => 'Noto.ttf' ] ],
							'files' => [
								'Noto.ttf' => [
									'sha256'      => str_repeat( 'a', 64 ),
									'size'        => 1024,
									'remote_path' => 'fonts-v1.0.0/Noto.ttf',
								],
							],
						]
					),
				],
				$overrides
			)
		);
	}

	/**
	 * A display entry publishing four styles across two files
	 */
	protected function seed_family( string $entry = 'lato', array $overrides = [] ): void {
		$this->insert_catalog_row(
			'packs',
			$entry,
			array_merge(
				[
					'label'      => 'Lato',
					'coverage'   => 0,
					'files'      => 2,
					'styles'     => '300,400,700',
					'entry_json' => (string) wp_json_encode(
						[
							'fonts'    => [ $entry => [ 'R' => 'Lato-400.ttf', 'B' => 'Lato-700.ttf' ] ],
							'variants' => [
								'300' => 'Lato-300.ttf',
								'400' => 'Lato-400.ttf',
								'700' => 'Lato-700.ttf',
							],
							'files'    => [
								'Lato-300.ttf' => [ 'sha256' => str_repeat( 'c', 64 ), 'size' => 10, 'remote_path' => 'v/Lato-300.ttf' ],
								'Lato-400.ttf' => [ 'sha256' => str_repeat( 'a', 64 ), 'size' => 10, 'remote_path' => 'v/Lato-400.ttf' ],
								'Lato-700.ttf' => [ 'sha256' => str_repeat( 'b', 64 ), 'size' => 10, 'remote_path' => 'v/Lato-700.ttf' ],
							],
						]
					),
				],
				$overrides
			)
		);
	}

	/**
	 * Fill a family's install slots, so the next new name is one too many
	 */
	protected function seed_installs_to_cap(): void {
		for ( $i = 1; $i <= Rest_Font_Installs::MAX_INSTALLS; $i++ ) {
			$this->install_entry_row( 'lato' . $i, 'lato', [ 'label' => 'Lato ' . $i, 'coverage' => 0 ] );
		}
	}

	protected function status( string $entry = 'emoji' ): array {
		return (array) $this->catalog_repository()->entry( 'packs', $entry );
	}

	public function test_the_routes_are_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/gravity-pdf/v1/fonts/status', $routes );
		$this->assertArrayHasKey( '/gravity-pdf/v1/fonts/updates', $routes );

		$methods = wp_list_pluck( $routes[ '/gravity-pdf/v1' . Rest_Font_Base::ENTRY_ROUTE ], 'methods' );

		/* GET is `Rest_Font_Sources`, POST and DELETE are this class; one constant is what keeps them one resource */
		$this->assertSame( [ [ 'GET' => true ], [ 'POST' => true ], [ 'DELETE' => true ] ], array_values( $methods ) );
	}

	public function test_an_anonymous_request_is_refused() {
		wp_set_current_user( 0 );

		$this->assertSame( 401, $this->get( '/fonts/status' )->get_status() );
		$this->assertSame( 401, $this->post( '/fonts/updates' )->get_status() );
		$this->assertSame( 401, $this->post( '/fonts/sources/packs/emoji' )->get_status() );
	}

	public function test_an_editor_without_the_capability_is_refused() {
		wp_set_current_user( self::$editor_id );

		$this->assertSame( 403, $this->get( '/fonts/status' )->get_status() );
		$this->assertSame( 403, $this->post( '/fonts/sources/packs/emoji' )->get_status() );
		$this->assertSame( 403, $this->delete( '/fonts/sources/packs/emoji' )->get_status() );
	}

	public function test_the_status_route_reports_every_entry_with_rows_or_a_phase() {
		$this->seed_pack();
		$this->catalog_repository()->set_status( 'packs', 'emoji', [ 'phase' => 'queued' ] );

		$response = $this->get( '/fonts/status' );
		$data     = (array) $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'queued', $data['packs/emoji']['phase'] );
		$this->assertFalse( $data['packs/emoji']['installed'] );
	}

	public function test_the_status_route_answers_an_empty_site_with_an_object() {
		$response = $this->get( '/fonts/status' );

		$this->assertSame( 200, $response->get_status() );

		/* `[]` would reach the store as an array and break every keyed read it does */
		$this->assertSame( '{}', wp_json_encode( $response->get_data() ) );
	}

	public function test_an_unknown_source_is_refused() {
		$response = $this->post( '/fonts/sources/nope/emoji' );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'font_source_unknown', $response->get_data()['code'] );
	}

	public function test_an_unknown_entry_is_refused() {
		$response = $this->post( '/fonts/sources/packs/nope' );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'font_entry_unknown', $response->get_data()['code'] );
	}

	public function test_a_pack_is_queued_and_claimed() {
		$this->seed_pack();

		$response = $this->post( '/fonts/sources/packs/emoji' );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 'queued', $this->status()['phase'] );
		$this->assertSame( [ 'Noto.ttf' ], array_column( $this->queued(), 'name' ) );

		/* The response is the same map the poller reads, so the store merges it without unpacking */
		$data = (array) $response->get_data();
		$this->assertSame( 'queued', $data['packs/emoji']['phase'] );
	}

	public function test_a_manual_install_ignores_the_auto_install_setting() {
		$this->seed_pack();

		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );
		$response = $this->post( '/fonts/sources/packs/emoji' );
		remove_all_filters( 'gfpdf_auto_install_fonts' );

		/* The setting governs the triggers; asking for a font by hand is the admin overriding it */
		$this->assertSame( 202, $response->get_status() );
		$this->assertCount( 1, $this->queued() );
	}

	public function test_a_manual_install_ignores_a_backoff() {
		$this->seed_pack();
		$this->catalog_repository()->set_status(
			'packs',
			'emoji',
			[
				'phase'       => 'failed',
				'error'       => 'nope',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
			]
		);

		$this->assertSame( 202, $this->post( '/fonts/sources/packs/emoji' )->get_status() );
		$this->assertSame( 'queued', $this->status()['phase'] );
	}

	public function test_naming_a_language_pack_is_refused() {
		$this->seed_pack();

		$response = $this->post( '/fonts/sources/packs/emoji', [ 'label' => 'My Emoji' ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'font_entry_owns_its_fonts', $response->get_data()['code'] );
		$this->assertSame( [], $this->queued() );
	}

	public function test_choosing_styles_for_a_language_pack_is_refused() {
		$this->seed_pack();

		$response = $this->post( '/fonts/sources/packs/emoji', [ 'variants' => [ 'R' => '400' ] ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'font_entry_owns_its_fonts', $response->get_data()['code'] );
	}

	public function test_a_style_the_entry_does_not_publish_is_refused() {
		$this->seed_family();

		$response = $this->post( '/fonts/sources/packs/lato', [ 'variants' => [ 'R' => '900' ] ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'font_variant_unknown', $response->get_data()['code'] );
		$this->assertSame( [], $this->queued() );
	}

	public function test_a_role_the_installer_does_not_fill_is_refused() {
		$this->seed_family();

		$response = $this->post( '/fonts/sources/packs/lato', [ 'variants' => [ 'X' => '400' ] ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'font_variant_unknown', $response->get_data()['code'] );
	}

	public function test_a_named_install_carries_its_label_and_derived_key() {
		$this->seed_family();

		$response = $this->post(
			'/fonts/sources/packs/lato',
			[
				'label'    => 'Lato Light',
				'variants' => [ 'R' => '300' ],
			]
		);

		$this->assertSame( 202, $response->get_status() );

		$items = $this->queued();

		$this->assertSame( 'Lato Light', $items[0]['install']['label'] );

		/* The key is the installer's to derive, at the moment it writes the row, not the route's to precompute */
		$this->assertArrayNotHasKey( 'font_key', $items[0]['install'] );

		/* The chosen variant is what decides the file, so a light install fetches the light file */
		$this->assertSame( [ 'Lato-300.ttf', 'Lato-700.ttf' ], array_column( $items, 'name' ) );
	}

	public function test_an_unnamed_install_of_a_family_takes_the_entry_defaults() {
		$this->seed_family();

		$this->assertSame( 202, $this->post( '/fonts/sources/packs/lato' )->get_status() );

		$items = $this->queued();

		$this->assertSame( [ 'Lato-400.ttf', 'Lato-700.ttf' ], array_column( $items, 'name' ) );
		$this->assertArrayNotHasKey( 'install', $items[0] );
	}

	public function test_re_posting_an_installed_family_updates_every_install_it_holds() {
		$this->seed_family();

		$this->install_entry_row( 'lato', 'lato', [ 'label' => 'Lato', 'coverage' => 0 ] );

		$light = $this->install_entry_row( 'latolight', 'lato', [ 'label' => 'Lato Light', 'coverage' => 0 ] );
		$this->font_repository()->insert_file( $light, 'R', [ 'path' => 'packs/lato/Lato-300.ttf', 'size' => 10, 'variant' => '300' ] );

		$this->assertSame( 202, $this->post( '/fonts/sources/packs/lato' )->get_status() );

		$labels = array_unique( array_column( array_column( $this->queued(), 'install' ), 'label' ) );
		sort( $labels );

		/* One claim, both installs: an update that only refreshed the first row would leave the other stale */
		$this->assertSame( [ 'Lato', 'Lato Light' ], $labels );
	}

	public function test_a_manual_install_re_queues_files_that_already_have_rows() {
		$this->seed_pack();

		$this->install_entry_row( 'notoemoji', 'emoji', [ 'label' => 'Emoji' ] );

		$this->assertSame( 202, $this->post( '/fonts/sources/packs/emoji' )->get_status() );

		/* The update path: the file sits at the same path and only its contents changed, so it has to be re-fetched */
		$this->assertSame( [ 'Noto.ttf' ], array_column( $this->queued(), 'name' ) );
	}

	public function test_a_family_already_being_installed_refuses_a_second_install() {
		$this->seed_family();
		$this->catalog_repository()->set_status( 'packs', 'lato', [ 'phase' => 'installing' ] );

		$response = $this->post( '/fonts/sources/packs/lato', [ 'label' => 'Lato Light', 'variants' => [ 'R' => '300' ] ] );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'font_install_in_progress', $response->get_data()['code'] );
	}

	public function test_a_pack_already_being_installed_answers_with_its_progress() {
		$this->seed_pack();
		$this->catalog_repository()->set_status( 'packs', 'emoji', [ 'phase' => 'installing' ] );

		$response = $this->post( '/fonts/sources/packs/emoji' );

		/* Something else is already doing exactly what was asked, so this is the outcome, not a conflict */
		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 'installing', ( (array) $response->get_data() )['packs/emoji']['phase'] );
	}

	public function test_a_family_cannot_be_installed_under_more_than_ten_names() {
		$this->seed_family();

		$this->seed_installs_to_cap();

		$response = $this->post( '/fonts/sources/packs/lato', [ 'label' => 'Lato Eleven' ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'font_entry_install_limit', $response->get_data()['code'] );
	}

	public function test_a_family_on_the_cap_can_still_be_updated() {
		$this->seed_family();

		$this->seed_installs_to_cap();

		/* The cap bounds new rows; an entry that reached it must still be able to take a new version */
		$this->assertSame( 202, $this->post( '/fonts/sources/packs/lato', [ 'label' => 'Lato 1' ] )->get_status() );
	}

	public function test_update_all_queues_only_the_entries_behind_the_catalogue() {
		$this->seed_pack( 'emoji', [ 'version' => 'fonts-v2.0.0' ] );
		$this->seed_pack( 'dejavu', [ 'version' => 'fonts-v1.0.0' ] );

		foreach ( [ 'emoji', 'dejavu' ] as $entry ) {
			$this->install_entry_row( 'noto' . $entry, $entry );
		}

		$response = $this->post( '/fonts/updates' );
		$data     = (array) $response->get_data();

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( [ 'packs/emoji' ], array_keys( $data ) );
		$this->assertSame( [ 'emoji' ], array_unique( array_column( $this->queued(), 'entry' ) ) );
	}

	public function test_update_all_with_nothing_to_do_queues_nothing() {
		$this->seed_pack( 'emoji', [ 'version' => 'fonts-v1.0.0' ] );

		$this->install_entry_row( 'notoemoji', 'emoji', [ 'label' => 'Emoji' ] );

		$response = $this->post( '/fonts/updates' );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( '{}', wp_json_encode( $response->get_data() ) );
		$this->assertSame( [], $this->queued() );
	}

	public function test_a_stuck_entry_is_reported_as_stuck() {
		$this->seed_pack();
		$this->catalog_repository()->set_status(
			'packs',
			'emoji',
			[
				'phase'       => 'installing',
				'phase_since' => gmdate( 'Y-m-d H:i:s', time() - Registry::STUCK_AFTER - 60 ),
			]
		);

		$data = (array) $this->get( '/fonts/status' )->get_data();

		$this->assertTrue( $data['packs/emoji']['stuck'] );
	}

	public function test_deleting_an_entry_removes_its_fonts_and_answers_with_its_status() {
		$this->seed_pack();
		$this->install_entry_row( 'notoemoji', 'emoji' );

		$response = $this->delete( '/fonts/sources/packs/emoji' );
		$data     = (array) $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $data['packs/emoji']['installed'] );
		$this->assertNull( $this->font_repository()->get( 'notoemoji' ) );
	}

	public function test_deleting_a_queued_entry_leaves_the_tombstone_its_batch_reads() {
		$this->seed_pack();

		$this->assertSame( 202, $this->post( '/fonts/sources/packs/emoji' )->get_status() );
		$this->assertSame( 200, $this->delete( '/fonts/sources/packs/emoji' )->get_status() );

		/* The items are still in the batch; `removed` is what makes them drop instead of reinstalling */
		$this->assertSame( 'removed', $this->status()['phase'] );
		$this->assertCount( 1, $this->queued() );
	}

	public function test_deleting_an_entry_nothing_installed_is_not_a_404() {
		$this->seed_pack();

		/* Declining a language pack the always rule would keep reinstalling is the same call */
		$this->assertSame( 200, $this->delete( '/fonts/sources/packs/emoji' )->get_status() );
	}

	public function test_deleting_an_unknown_entry_is_refused() {
		$this->assertSame( 404, $this->delete( '/fonts/sources/nope/emoji' )->get_status() );
		$this->assertSame( 404, $this->delete( '/fonts/sources/packs/nope' )->get_status() );
	}
}
