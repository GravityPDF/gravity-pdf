<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Fonts\Font_Installer;
use GFPDF\Fonts\Font_Lock;
use GFPDF\Fonts\Font_Source;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontFixtures;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * A font source nobody here wrote code for
 *
 * The claim §4.3 makes about sources is that a new one is a record, not a feature: "a new built-in is one line in
 * `Font_Sources` plus the pipeline half that publishes it". This is that claim under test. An add-on registers
 * `new Font_Source( id, label, root_url, [], description, public_key )` through `gfpdf_font_sources` and its whole
 * lifecycle — sync, list, install with chosen variants, update, remove — runs through the routes every other
 * source uses, against code that never names it.
 *
 * Its root is signed with **its own** key rather than the build's, which is the half of the claim that makes the
 * first half safe: trust is per record, so registering a source is not a way to borrow ours.
 *
 * It is also the rehearsal for `google` (§5 Phase 5), which is the same shape — a slim index whose entries are
 * fetched one file at a time, display families rather than coverage packs, styles chosen per install.
 *
 * @group api
 * @group rest
 * @group fonts
 */
class Test_Rest_Font_Source_Extensibility extends Test_Rest {

	use HasCatalogRows;
	use HasFontFixtures;
	use HasFontRows;
	use MocksHttpRequests;

	/**
	 * @var string The source id an add-on picked; nothing in `src/` knows it
	 */
	const SOURCE = 'acme';

	/**
	 * @var string Its own root, on its own host
	 */
	const ROOT = 'https://fonts.example.test/v1/';

	/**
	 * @var string base64 ed25519 public key, the add-on's own
	 */
	public $public_key = '';

	/**
	 * @var string
	 */
	private $secret_key = '';

	/**
	 * @var callable|null
	 */
	private $register;

	/**
	 * @var string The font bytes every file of the fixture family resolves to
	 */
	public $body = '';

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$pair = sodium_crypto_sign_keypair();

		$this->public_key = base64_encode( sodium_crypto_sign_publickey( $pair ) );
		$this->secret_key = sodium_crypto_sign_secretkey( $pair );
		$this->body       = $this->font_bytes();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();
		delete_site_option( Catalog_Sync::OPTION );

		$this->register();

		wp_set_current_user( self::$admin_id );
	}

	public function tear_down(): void {
		global $gfpdf;

		if ( $this->register !== null ) {
			remove_filter( 'gfpdf_font_sources', $this->register );
		}

		$gfpdf->get_font_sources()->flush();

		wp_unschedule_hook( Catalog_Sync::EVENT );

		$this->unmock_http();
		$this->drop_catalog_rows();
		$this->remove_font_rows();
		GPDFAPI::get_misc_class()->rmdir( $this->font_dir() . self::SOURCE );
		delete_site_option( Catalog_Sync::OPTION );

		parent::tear_down();
	}

	/**
	 * Register the add-on's record the way an add-on would, and drop the memo taken before it existed
	 */
	protected function register(): void {
		global $gfpdf;

		$record = new Font_Source(
			self::SOURCE,
			'Acme Fonts',
			self::ROOT,
			[],
			'A library of display faces, served by Acme.',
			$this->public_key
		);

		$this->register = static function ( $sources ) use ( $record ) {
			$sources[] = $record;

			return $sources;
		};

		add_filter( 'gfpdf_font_sources', $this->register );

		$gfpdf->get_font_sources()->flush();
	}

	/**
	 * The entry document a slim index points at rather than inlines
	 *
	 * Three styles across three files, so an install can choose which weight fills which mPDF role — the shape a
	 * display source has and a language pack does not.
	 */
	protected function entry_document(): array {
		$file = [
			'sha256' => hash( 'sha256', $this->body ),
			'size'   => strlen( $this->body ),
		];

		return [
			'fonts'    => [ 'acmesans' => [ 'R' => 'AcmeSans-400.ttf', 'B' => 'AcmeSans-700.ttf', 'useOTL' => 255 ] ],
			'variants' => [
				'400' => 'AcmeSans-400.ttf',
				'700' => 'AcmeSans-700.ttf',
				'900' => 'AcmeSans-900.ttf',
			],
			'files'    => [
				'AcmeSans-400.ttf' => $file + [ 'remote_path' => 'acme-v1/AcmeSans-400.ttf' ],
				'AcmeSans-700.ttf' => $file + [ 'remote_path' => 'acme-v1/AcmeSans-700.ttf' ],
				'AcmeSans-900.ttf' => $file + [ 'remote_path' => 'acme-v1/AcmeSans-900.ttf' ],
			],
		];
	}

	/**
	 * One index row, slim: the entry is named by hash and fetched at install, never carried in the listing
	 */
	protected function index_entry( string $version = 'acme-v1.0.0' ): array {
		return [
			'id'           => 'acme-sans',
			'label'        => 'Acme Sans',
			'version'      => $version,
			'coverage'     => false,
			'license'      => 'OFL-1.1',
			'size'         => 3 * strlen( $this->body ),
			'files'        => 3,
			'category'     => 'sans-serif',
			'subsets'      => [ 'latin', 'latin-ext' ],
			'styles'       => [ '400', '700', '900' ],
			'entry_sha256' => hash( 'sha256', (string) wp_json_encode( $this->entry_document() ) ),
		];
	}

	/**
	 * Publish the add-on's signed root, its source index, its entry file and its font files
	 *
	 * Our own root answers with a connection failure throughout, which is deliberate: one source's origin being
	 * unreachable must not stop another's from syncing, and nothing here may quietly fall back to our host.
	 */
	protected function publish_acme( array $entries, array $root_overrides = [] ): void {
		$index      = (string) wp_json_encode( [ 'schema' => 1, 'entries' => $entries ] );
		$entry_json = (string) wp_json_encode( $this->entry_document() );

		$root = (string) wp_json_encode(
			array_merge(
				[
					'schema'    => 1,
					'generated' => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'sources'   => [
						self::SOURCE => [
							'sha256' => hash( 'sha256', $index ),
							'size'   => strlen( $index ),
						],
					],
				],
				$root_overrides
			)
		);

		$this->mock_http(
			[
				self::ROOT . 'index.json.sig'          => base64_encode( sodium_crypto_sign_detached( Catalog_Sync::SIGNATURE_CONTEXT . $root, $this->secret_key ) ),
				self::ROOT . 'index.json'              => $root,
				self::ROOT . 'sources/' . self::SOURCE => $index,
				self::ROOT . 'entries/' . self::SOURCE => $entry_json,
				self::ROOT . 'files/'                  => $this->body,
				'fonts.gravitypdf.com'                 => new \WP_Error( 'http_request_failed', 'Connection refused' ),
			]
		);
	}

	/**
	 * What the Font Manager's Refresh sets in motion
	 *
	 * The route compares the roots and schedules; the scheduled event is what syncs, and that is the half these
	 * cases are about. `test_refresh_...` below covers the route itself.
	 */
	protected function sync(): void {
		global $gfpdf;

		$gfpdf->get_catalog_sync()->run();

		$this->catalog_repository()->flush();
	}

	/**
	 * A fresh installer for each case
	 *
	 * The container's is a singleton and memoises fetched entry documents by hash for the life of the request.
	 * These cases share both the object and the hash, so one install would answer the next from the memo and the
	 * fetch this file is partly about would never happen twice.
	 */
	protected function installer(): Font_Installer {
		global $gfpdf;

		return new Font_Installer(
			$gfpdf->get_font_repository(),
			$gfpdf->get_catalog_repository(),
			$gfpdf->get_font_downloader(),
			$gfpdf->get_font_cache_warmer(),
			new Font_Lock(),
			GPDFAPI::get_log_class()
		);
	}

	protected function row(): ?array {
		return $this->catalog_repository()->entry( self::SOURCE, 'acme-sans' );
	}

	/**
	 * Every URL this test's HTTP mock was asked for, in order
	 *
	 * @return string[]
	 */
	protected function requested(): array {
		return wp_list_pluck( $this->http_requests, 'url' );
	}

	public function test_a_registered_source_syncs_from_its_own_root_under_its_own_key() {
		$this->publish_acme( [ $this->index_entry() ] );

		$this->sync();

		$row = $this->row();

		$this->assertNotNull( $row );
		$this->assertSame( 'Acme Sans', $row['label'] );
		$this->assertSame( 0, $row['coverage'] );
		$this->assertSame( 'sans-serif', $row['category'] );

		/* Slim: the listing names the entry by hash and carries no copy of it */
		$this->assertNull( $row['data'] );
		$this->assertSame( $this->index_entry()['entry_sha256'], $row['entry_sha256'] );
	}

	public function test_our_own_root_failing_does_not_stop_it() {
		$this->publish_acme( [ $this->index_entry() ] );

		$this->sync();

		$record = (array) get_site_option( Catalog_Sync::OPTION );

		$this->assertNotSame( '', (string) $record['packs']['last_error'] );
		$this->assertSame( '', (string) $record[ self::SOURCE ]['last_error'] );
		$this->assertGreaterThan( 0, (int) $record[ self::SOURCE ]['synced'] );
	}

	public function test_a_root_signed_with_the_wrong_key_is_refused() {
		$this->publish_acme( [ $this->index_entry() ] );

		/* Ours, not theirs: a record's key is the only thing that verifies its root */
		$this->secret_key = sodium_crypto_sign_secretkey( sodium_crypto_sign_keypair() );

		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$this->assertNull( $this->row() );
	}

	public function test_it_is_listed_and_searched_through_the_same_routes() {
		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$sources = wp_list_pluck( $this->get( '/fonts/sources' )->get_data(), 'label', 'id' );

		$this->assertSame( 'Acme Fonts', $sources[ self::SOURCE ] );

		$listing = $this->get( '/fonts/sources/' . self::SOURCE )->get_data();

		$this->assertSame( 1, $listing['total'] );
		$this->assertSame( 'acme-sans', $listing['entries'][0]['entry'] );

		/* Its own name, not run through the packs translation map */
		$this->assertSame( 'Acme Sans', $listing['entries'][0]['label'] );

		/* The filter vocabulary is the catalogue's, whoever published it */
		$filters = wp_list_pluck( $this->get( '/fonts/sources' )->get_data(), 'filters', 'id' );

		$this->assertSame( 'Latin Extended', wp_list_pluck( $filters[ self::SOURCE ]['subsets'], 'label', 'id' )['latin-ext'] );
		$this->assertSame( 'Sans-serif', wp_list_pluck( $filters[ self::SOURCE ]['category'], 'label', 'id' )['sans-serif'] );
	}

	public function test_its_files_are_addressed_from_its_own_root_not_ours() {
		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$this->installer()->install( self::SOURCE . '/acme-sans' );

		$fetched = array_values( array_filter( $this->requested(), static function ( string $url ): bool {
			return strpos( $url, '/files/' ) !== false;
		} ) );

		$this->assertNotSame( [], $fetched );

		foreach ( $fetched as $url ) {
			/* The root comes from the registered record, never from anything the index says */
			$this->assertStringStartsWith( self::ROOT . 'files/acme-v1/', $url );
		}
	}

	public function test_installing_fetches_the_entry_file_and_writes_the_chosen_variants() {
		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$this->installer()->install(
			self::SOURCE . '/acme-sans',
			[],
			[
				'label'    => 'Acme Display',
				'variants' => [ 'B' => '900' ],
			]
		);

		$font = $this->font_repository()->get( 'acmedisplay' );

		$this->assertNotNull( $font );
		$this->assertSame( self::SOURCE, $font['source'] );
		$this->assertSame( 'acme-sans', $font['entry'] );
		$this->assertSame( 0, $font['coverage'] );

		/* A display row never joins the fallback chain, whatever source it came from */
		$this->assertSame( [], $font['meta'] );

		/* The regular face is the entry's own; Bold is the 900 the caller asked for */
		$this->assertSame( self::SOURCE . '/acme-sans/AcmeSans-400.ttf', $font['files']['R']['path'] );
		$this->assertSame( self::SOURCE . '/acme-sans/AcmeSans-900.ttf', $font['files']['B']['path'] );
		$this->assertSame( '900', $font['files']['B']['variant'] );

		$this->assertFileExists( $this->font_dir() . self::SOURCE . '/acme-sans/AcmeSans-900.ttf' );

		/* The one entry-file fetch, keyed by the hash the index committed to */
		$this->assertContains(
			self::ROOT . 'entries/' . self::SOURCE . '/acme-sans-' . $this->index_entry()['entry_sha256'] . '.json',
			$this->requested()
		);
	}

	public function test_nothing_about_it_is_ever_asked_of_our_origin() {
		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$this->installer()->install( self::SOURCE . '/acme-sans' );

		foreach ( $this->http_requests as $request ) {
			if ( strpos( $request['url'], self::ROOT ) !== 0 ) {
				continue;
			}

			/* A third party's origin learns nothing about this site: no licence, no site URL, no query at all */
			$this->assertNull( wp_parse_url( $request['url'], PHP_URL_QUERY ) );
			$this->assertArrayNotHasKey( 'license', (array) ( $request['args']['body'] ?? [] ) );
		}
	}

	public function test_an_update_is_offered_and_taken_through_the_same_routes() {
		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$this->installer()->install( self::SOURCE . '/acme-sans' );

		/* The add-on publishes a new version of the same entry */
		$this->publish_acme( [ $this->index_entry( 'acme-v1.1.0' ) ] );
		$this->sync();

		$status = ( (array) $this->get( '/fonts/status' )->get_data() )[ self::SOURCE . '/acme-sans' ];

		$this->assertSame( 'acme-v1.1.0', $status['update']['version'] );

		$this->assertSame( 202, $this->post( '/fonts/updates' )->get_status() );
	}

	public function test_removing_it_takes_its_rows_and_its_files() {
		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$this->installer()->install( self::SOURCE . '/acme-sans' );

		$this->assertNotNull( $this->font_repository()->get( 'acme-sans' ) );

		$this->assertSame( 200, $this->delete( '/fonts/sources/' . self::SOURCE . '/acme-sans' )->get_status() );

		$this->assertNull( $this->font_repository()->get( 'acme-sans' ) );
		$this->assertFileDoesNotExist( $this->font_dir() . self::SOURCE . '/acme-sans/AcmeSans-400.ttf' );
	}

	/**
	 * Refresh asks every root before it answers
	 *
	 * With two origins registered, returning on the first failure would let an outage at ours hide the add-on's
	 * updates and skip scheduling the sync that would have taken them (fixed 2026-09-11).
	 */
	public function test_refresh_schedules_the_reachable_source_while_ours_is_down() {
		$this->publish_acme( [ $this->index_entry() ] );

		$this->assertSame( 202, $this->post( '/fonts/sources/sync' )->get_status() );
		$this->assertNotFalse( wp_next_scheduled( Catalog_Sync::EVENT ) );

		/* And the failure is still on our record, which is where the listing and the health check read it */
		$this->assertNotSame( '', (string) ( (array) get_site_option( Catalog_Sync::OPTION ) )['packs']['last_error'] );
	}

	public function test_refresh_reports_the_failure_when_there_is_nothing_to_schedule() {
		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$response = $this->post( '/fonts/sources/sync' );

		/* Their root is unchanged and ours is unreachable: nothing to do, and a reason worth saying */
		$this->assertSame( 502, $response->get_status() );
		$this->assertSame( 'font_source_unavailable', $response->get_data()['code'] );
	}

	public function test_an_entry_it_stops_publishing_is_pruned() {
		$this->publish_acme( [ $this->index_entry() ] );
		$this->sync();

		$this->publish_acme( [] );
		$this->sync();

		$this->assertNull( $this->row() );
	}
}
