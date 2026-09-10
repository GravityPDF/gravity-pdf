<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use WP_Error;

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
class Test_Rest_Font_Sources extends Test_Rest {

	use HasCatalogRows;
	use MocksHttpRequests;

	public function set_up(): void {
		parent::set_up();

		$this->drop_catalog_rows();
		delete_site_option( Catalog_Sync::OPTION );

		/* Most cases are an authorised browse; the anonymous ones sign back out */
		wp_set_current_user( self::$admin_id );
	}

	public function tear_down(): void {
		$this->unmock_http();
		$this->drop_catalog_rows();
		delete_site_option( Catalog_Sync::OPTION );

		parent::tear_down();
	}

	protected function seed_packs(): void {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'label' => 'Emoji', 'position' => 0, 'coverage' => 1, 'category' => 'symbols', 'subsets' => 'emoji', 'preview' => 'prev-a', 'font_keys' => 'notoemoji' ] );
		$this->insert_catalog_row( 'packs', 'dejavu', [ 'label' => 'Extended Latin', 'position' => 1, 'coverage' => 1, 'category' => 'sans-serif', 'subsets' => 'latin,latin-ext', 'font_keys' => 'dejavusans' ] );
		$this->insert_catalog_row( 'packs', 'lato', [ 'label' => 'Lato', 'position' => 2, 'coverage' => 0, 'category' => 'sans-serif', 'subsets' => 'latin' ] );
	}

	public function test_the_routes_are_registered() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/gravity-pdf/v1/fonts/sources', $routes );
		$this->assertArrayHasKey( '/gravity-pdf/v1/fonts/sources/sync', $routes );
		$this->assertArrayHasKey( '/gravity-pdf/v1/fonts/sources/(?P<source>[a-z0-9-]+)', $routes );
		$this->assertArrayHasKey( '/gravity-pdf/v1/fonts/sources/(?P<source>[a-z0-9-]+)/(?P<entry>[a-z0-9-]+)', $routes );
	}

	public function test_an_anonymous_request_is_refused() {
		wp_set_current_user( 0 );

		$this->assertSame( 401, $this->get( '/fonts/sources' )->get_status() );
		$this->assertSame( 401, $this->get( '/fonts/sources/packs' )->get_status() );
	}

	public function test_an_editor_without_the_capability_is_refused() {
		wp_set_current_user( self::$editor_id );

		$response = $this->get( '/fonts/sources' );

		$this->assertContains( $response->get_status(), [ 401, 403 ] );
	}

	public function test_the_sources_listing_reports_counts_and_never_synced() {
		$this->seed_packs();

		$data = $this->get( '/fonts/sources' )->get_data();

		$this->assertCount( 1, $data );
		$this->assertSame( 'packs', $data[0]['id'] );
		$this->assertSame( 'Language packs', $data[0]['label'] );
		$this->assertNotSame( '', $data[0]['description'] );
		$this->assertSame( 3, $data[0]['total'] );
		$this->assertSame( 2, $data[0]['coverage'] );

		/* null rather than 0, so the UI never renders a date in 1970 */
		$this->assertNull( $data[0]['synced'] );
		$this->assertTrue( $data[0]['stale'] );
	}

	public function test_the_sources_listing_carries_the_filter_vocabulary() {
		$this->seed_packs();

		$filters = $this->get( '/fonts/sources' )->get_data()[0]['filters'];

		$categories = wp_list_pluck( $filters['category'], 'label', 'id' );

		$this->assertSame( 'Sans-serif', $categories['sans-serif'] );
		$this->assertSame( 2, wp_list_pluck( $filters['category'], 'count', 'id' )['sans-serif'] );

		$subsets = wp_list_pluck( $filters['subsets'], 'label', 'id' );

		$this->assertSame( 'Latin Extended', $subsets['latin-ext'] );
	}

	public function test_an_untranslated_filter_id_is_title_cased_rather_than_dropped() {
		$this->insert_catalog_row( 'packs', 'odd', [ 'category' => 'made-up-thing' ] );

		$categories = wp_list_pluck( $this->get( '/fonts/sources' )->get_data()[0]['filters']['category'], 'label', 'id' );

		$this->assertSame( 'Made Up Thing', $categories['made-up-thing'] );
	}

	public function test_the_sources_listing_reflects_a_sync_record() {
		$this->seed_packs();

		$synced = time() - DAY_IN_SECONDS;

		update_site_option(
			Catalog_Sync::OPTION,
			[
				'packs' => [
					'synced'       => $synced,
					'last_attempt' => $synced,
					'last_error'   => 'Connection timed out',
				],
			]
		);

		$data = $this->get( '/fonts/sources' )->get_data()[0];

		$this->assertSame( $synced, $data['synced'] );
		$this->assertFalse( $data['stale'] );
		$this->assertSame( 'Connection timed out', $data['last_error'] );
	}

	public function test_a_source_search_returns_entries_in_index_order() {
		$this->seed_packs();

		$data = $this->get( '/fonts/sources/packs' )->get_data();

		$this->assertSame( 3, $data['total'] );
		$this->assertSame( 1, $data['pages'] );
		$this->assertNull( $data['synced'] );
		$this->assertSame( [ 'emoji', 'dejavu', 'lato' ], wp_list_pluck( $data['entries'], 'entry' ) );
	}

	public function test_the_coverage_param_is_the_language_packs_view() {
		$this->seed_packs();

		$data = $this->get( '/fonts/sources/packs', [ 'coverage' => 1 ] )->get_data();

		$this->assertSame( [ 'emoji', 'dejavu' ], wp_list_pluck( $data['entries'], 'entry' ) );
	}

	public function test_the_search_params_narrow_the_listing() {
		$this->seed_packs();

		$this->assertSame( [ 'dejavu' ], wp_list_pluck( $this->get( '/fonts/sources/packs', [ 's' => 'Latin' ] )->get_data()['entries'], 'entry' ) );
		$this->assertSame( [ 'emoji' ], wp_list_pluck( $this->get( '/fonts/sources/packs', [ 'category' => 'symbols' ] )->get_data()['entries'], 'entry' ) );

		/* FIND_IN_SET, so `latin` must not match `latin-ext` */
		$this->assertSame( [ 'dejavu' ], wp_list_pluck( $this->get( '/fonts/sources/packs', [ 'subset' => 'latin-ext' ] )->get_data()['entries'], 'entry' ) );
	}

	public function test_a_listing_never_carries_install_progress() {
		$this->insert_catalog_row(
			'packs',
			'emoji',
			[
				'phase'           => 'installing',
				'phase_since'     => '2026-09-09 00:00:00',
				'error'           => 'boom',
				'retry_after'     => '2026-09-09 01:00:00',
				'missing_scripts' => 'und-Zsye',
				'missing_since'   => '2026-09-09 00:00:00',
			]
		);

		$entry = $this->get( '/fonts/sources/packs' )->get_data()['entries'][0];

		/*
		 * Install progress belongs to `GET /fonts/status`. These are columns of the same row, so a response built
		 * by subtracting from the row rather than naming its fields ships them without anyone deciding to.
		 */
		foreach ( [ 'phase', 'phase_since', 'error', 'retry_after', 'missing_scripts', 'missing_since' ] as $column ) {
			$this->assertArrayNotHasKey( $column, $entry );
		}
	}

	public function test_a_listing_never_leaks_the_entry_object() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'entry_json' => '{"fonts":{"notoemoji":{"R":"A.ttf"}}}' ] );

		$entry = $this->get( '/fonts/sources/packs' )->get_data()['entries'][0];

		$this->assertArrayNotHasKey( 'entry_json', $entry );
		$this->assertArrayNotHasKey( 'data', $entry );
	}

	public function test_a_card_carries_one_preview_url() {
		$this->seed_packs();

		$entries = wp_list_pluck( $this->get( '/fonts/sources/packs' )->get_data()['entries'], 'preview_url', 'entry' );

		$this->assertSame( trailingslashit( GPDF_FONTS_URL ) . 'files/prev-a-notoemoji-R.woff2', $entries['emoji'] );

		/* A row with no preview says so rather than inventing a URL */
		$this->assertNull( $entries['dejavu'] );
	}

	public function test_an_unknown_source_is_a_404() {
		$response = $this->get( '/fonts/sources/nope' );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'font_source_unknown', $response->get_data()['code'] );
	}

	public function test_an_entry_returns_its_row_and_preview_urls() {
		$this->insert_catalog_row(
			'packs',
			'dejavu',
			[
				'label'      => 'Extended Latin',
				'preview'    => 'prev-b',
				'font_keys'  => 'dejavusans',
				'entry_json' => '{"fonts":{"dejavusans":{"R":"DejaVuSans.ttf","B":"DejaVuSans-Bold.ttf","useOTL":255}}}',
			]
		);

		$data = $this->get( '/fonts/sources/packs/dejavu' )->get_data();

		$this->assertSame( 'dejavu', $data['entry'] );
		$this->assertSame( 'Extended Latin', $data['label'] );

		/* The detail view gets every face, taken from the decoded entry; useOTL is not one */
		$this->assertSame( [ 'R', 'B' ], array_keys( $data['preview_urls']['dejavusans'] ) );

		/* Still no entry object on the wire */
		$this->assertArrayNotHasKey( 'entry_json', $data );
		$this->assertArrayNotHasKey( 'data', $data );
	}

	public function test_an_unknown_entry_is_a_404() {
		$response = $this->get( '/fonts/sources/packs/nope' );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'font_entry_unknown', $response->get_data()['code'] );
	}

	public function test_a_pack_label_is_translated_on_the_way_out() {
		/* The stored catalogue stays byte-identical to the index; translation happens at presentation */
		$this->insert_catalog_row( 'packs', 'west-asian', [ 'label' => 'West-Asian' ] );

		$this->assertSame( 'West Asian', $this->get( '/fonts/sources/packs' )->get_data()['entries'][0]['label'] );
	}

	public function test_sync_is_a_post_only_route() {
		$this->assertSame( 404, $this->get( '/fonts/sources/sync' )->get_status() );
	}

	public function test_a_root_failure_is_a_502_and_proves_sync_is_not_shadowed() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => new WP_Error( 'http_request_failed', 'Connection timed out' ) ] );

		$response = $this->post( '/fonts/sources/sync' );

		/*
		 * 502 rather than 404: `sync` is a reserved source id, so this is the literal route answering rather than
		 * `{source}` reporting `font_source_unknown`.
		 */
		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'font_source_unavailable', $response->get_data()['code'] );
		$this->assertStringContainsString( 'Connection timed out', $response->get_data()['message'] );
	}

	public function test_an_anonymous_sync_is_refused() {
		wp_set_current_user( 0 );

		$this->mock_http( [ 'fonts.gravitypdf.com' => '{}' ] );

		/* Refused before the sync is ever asked to run */
		$this->assertSame( 401, $this->post( '/fonts/sources/sync' )->get_status() );
		$this->assertSame( [], $this->requested_urls() );
	}
}
