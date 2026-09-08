<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;
use WP_Error;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Catalog_Sync
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Catalog_Sync extends TestCase {

	use HasCatalogRows;
	use MocksHttpRequests;

	/**
	 * @var string base64 ed25519 public key
	 */
	public $public_key;

	/**
	 * @var string Raw ed25519 secret key
	 */
	private $secret_key;

	/**
	 * @var string
	 */
	public $root;

	/**
	 * @var string The source index the last publish() built, so a test can corrupt it without changing its length
	 */
	public $last_index = '';

	public function set_up(): void {
		parent::set_up();

		$pair = sodium_crypto_sign_keypair();

		$this->public_key = base64_encode( sodium_crypto_sign_publickey( $pair ) );
		$this->secret_key = sodium_crypto_sign_secretkey( $pair );
		$this->root       = trailingslashit( GPDF_FONTS_URL );

		$this->reset_sync_state();
	}

	public function tear_down(): void {
		$this->unmock_http();
		$this->reset_sync_state();

		parent::tear_down();
	}

	protected function reset_sync_state(): void {
		$this->drop_catalog_rows();

		delete_site_option( Catalog_Sync::OPTION );
		delete_site_option( Catalog_Sync::GENERATED_OPTION );
		( new Font_Lock() )->release( Catalog_Sync::LOCK );
	}

	protected function sync( ?array $trust_keys = null, string $seed_file = '' ): Catalog_Sync {
		global $gfpdf;

		return new Catalog_Sync(
			$gfpdf->get_font_repository()->get_schema(),
			$this->catalog_repository(),
			$gfpdf->get_font_sources(),
			new Font_Downloader( GPDFAPI::get_log_class() ),
			new Font_Lock(),
			GPDFAPI::get_log_class(),
			new Catalog_Font_Adopter( $gfpdf->get_font_repository(), $this->catalog_repository(), GPDFAPI::get_log_class() ),
			$trust_keys === null ? [ $this->public_key ] : $trust_keys,
			$seed_file
		);
	}

	/**
	 * One valid coverage entry
	 *
	 * Not `entry()`: `HasGfpdfFixtures` has one with a different signature, and overriding it is a class-load fatal
	 * PHPUnit reports as a bare exit 255.
	 */
	protected function pack_entry( string $id, array $overrides = [] ): array {
		return array_merge(
			[
				'id'       => $id,
				'label'    => ucfirst( $id ),
				'version'  => 'fonts-v1.0.0',
				'coverage' => true,
				'license'  => 'OFL-1.1',
				'size'     => 1024,
				'files'    => 1,
				'always'   => false,
				'scripts'  => [ 'und-Zsye' ],
				'entry'    => [
					'fonts' => [ $id . 'font' => [ 'R' => 'A.ttf' ] ],
					'files' => [
						'A.ttf' => [
							'sha256'      => str_repeat( 'a', 64 ),
							'size'        => 1024,
							'remote_path' => 'fonts-v1.0.0/A.ttf',
						],
					],
				],
			],
			$overrides
		);
	}

	/**
	 * Publish a signed root plus the source index it names, and mock both
	 *
	 * @param array $entries The `packs` index entries
	 */
	protected function publish( array $entries, array $root_overrides = [], array $mock_overrides = [] ): string {
		$index            = (string) wp_json_encode( [ 'schema' => 1, 'entries' => $entries ] );
		$index_hash       = hash( 'sha256', $index );
		$this->last_index = $index;

		$root = (string) wp_json_encode(
			array_merge(
				[
					'schema'    => 1,
					'generated' => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'sources'   => [
						'packs' => [
							'sha256' => $index_hash,
							'size'   => strlen( $index ),
						],
					],
				],
				$root_overrides
			)
		);

		$this->mock_http(
			array_merge(
				[
					'index.json.sig' => base64_encode( sodium_crypto_sign_detached( Catalog_Sync::SIGNATURE_CONTEXT . $root, $this->secret_key ) ),
					'index.json'     => $root,
					'sources/packs-' => $index,
				],
				$mock_overrides
			)
		);

		return $index_hash;
	}

	public function test_a_signed_root_populates_the_catalog() {
		$hash = $this->publish( [ $this->pack_entry( 'emoji' ), $this->pack_entry( 'dejavu' ) ] );

		$this->assertTrue( $this->sync()->run() );

		$result = $this->catalog_repository()->search( 'packs' );

		$this->assertSame( 2, $result['total'] );
		$this->assertSame( [ 'emoji', 'dejavu' ], wp_list_pluck( $result['entries'], 'entry' ) );

		$record = $this->sync()->get_record( 'packs' );

		$this->assertSame( $hash, $record['index_sha256'] );
		$this->assertGreaterThan( 0, $record['synced'] );
		$this->assertSame( '', $record['last_error'] );
	}

	public function test_index_order_becomes_the_position_column() {
		$this->publish( [ $this->pack_entry( 'zulu' ), $this->pack_entry( 'alpha' ) ] );
		$this->sync()->run();

		/* The index is curated most-to-least common, so its order must survive rather than sorting by label */
		$this->assertSame( [ 'zulu', 'alpha' ], wp_list_pluck( $this->catalog_repository()->search( 'packs' )['entries'], 'entry' ) );
	}

	public function test_an_unsigned_build_fails_closed() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );

		/* An empty GPDF_TRUST_KEYS must refuse to sync, never fall back to origin trust */
		$this->sync( [] )->run();

		$this->assertSame( 0, $this->catalog_repository()->search( 'packs' )['total'] );
		$this->assertStringContainsString( 'signing keys', $this->sync()->get_record( 'packs' )['last_error'] );
	}

	public function test_a_root_signed_by_another_key_is_refused() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );

		$other = base64_encode( sodium_crypto_sign_publickey( sodium_crypto_sign_keypair() ) );

		$this->sync( [ $other ] )->run();

		$this->assertSame( 0, $this->catalog_repository()->search( 'packs' )['total'] );
	}

	public function test_a_tampered_root_is_refused() {
		$hash = hash( 'sha256', 'x' );

		/* Signature made over the honest root, body swapped for one pointing somewhere else */
		$honest = (string) wp_json_encode( [ 'schema' => 1, 'generated' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'sources' => [] ] );
		$forged = (string) wp_json_encode( [ 'schema' => 1, 'generated' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'sources' => [ 'packs' => [ 'sha256' => $hash ] ] ] );

		$this->mock_http(
			[
				'index.json.sig' => base64_encode( sodium_crypto_sign_detached( Catalog_Sync::SIGNATURE_CONTEXT . $honest, $this->secret_key ) ),
				'index.json'     => $forged,
			]
		);

		$this->sync()->run();

		$this->assertSame( 0, $this->catalog_repository()->search( 'packs' )['total'] );
	}

	public function test_a_signature_over_another_context_does_not_verify() {
		$root = (string) wp_json_encode( [ 'schema' => 1, 'generated' => gmdate( 'Y-m-d\TH:i:s\Z' ), 'sources' => [] ] );

		/* The same key signing a different purpose must never verify here */
		$this->mock_http(
			[
				'index.json.sig' => base64_encode( sodium_crypto_sign_detached( "gravitypdf/templates/v1\n" . $root, $this->secret_key ) ),
				'index.json'     => $root,
			]
		);

		$this->sync()->run();

		$this->assertStringContainsString( 'not signed by a trusted key', $this->sync()->get_record( 'packs' )['last_error'] );
	}

	public function test_a_replayed_root_is_refused() {
		$this->publish( [ $this->pack_entry( 'emoji' ), $this->pack_entry( 'dejavu' ) ] );
		$this->sync()->run();

		$this->assertSame( 2, $this->catalog_repository()->search( 'packs' )['total'] );

		/* An old signed root pointing at a since-replaced font must not roll the catalogue back */
		$this->publish( [ $this->pack_entry( 'emoji' ) ], [ 'generated' => gmdate( 'Y-m-d\TH:i:s\Z', time() - YEAR_IN_SECONDS ) ] );
		$this->sync()->run();

		$this->assertSame( 2, $this->catalog_repository()->search( 'packs' )['total'] );
		$this->assertStringContainsString( 'older than one this site has already accepted', $this->sync()->get_record( 'packs' )['last_error'] );
	}

	public function test_a_root_from_the_future_is_refused() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ], [ 'generated' => gmdate( 'Y-m-d\TH:i:s\Z', time() + ( 30 * DAY_IN_SECONDS ) ) ] );

		$this->sync()->run();

		$this->assertSame( 0, $this->catalog_repository()->search( 'packs' )['total'] );
		$this->assertStringContainsString( 'further ahead', $this->sync()->get_record( 'packs' )['last_error'] );
	}

	public function test_ordinary_clock_skew_is_tolerated() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ], [ 'generated' => gmdate( 'Y-m-d\TH:i:s\Z', time() + HOUR_IN_SECONDS ) ] );

		$this->sync()->run();

		$this->assertSame( 1, $this->catalog_repository()->search( 'packs' )['total'] );
	}

	public function test_an_unchanged_root_makes_no_further_request() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );
		$this->sync()->run();

		$this->publish( [ $this->pack_entry( 'emoji' ) ] );
		$this->sync()->run();

		/* Root plus signature only: the source index hash matched what the record already held */
		$this->assertSame( [], array_filter( $this->requested_urls(), static function ( $url ) {
			return strpos( $url, 'sources/packs-' ) !== false;
		} ) );
	}

	public function test_a_changed_index_replaces_rows_and_prunes_dropped_entries() {
		$this->publish( [ $this->pack_entry( 'emoji' ), $this->pack_entry( 'dejavu' ) ] );
		$this->sync()->run();

		$this->publish( [ $this->pack_entry( 'emoji', [ 'label' => 'Emoji v2' ] ) ] );
		$this->sync()->run();

		$result = $this->catalog_repository()->search( 'packs' );

		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 'Emoji v2', $result['entries'][0]['label'] );
	}

	public function test_a_sync_leaves_an_in_flight_install_its_phase() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );
		$this->sync()->run();

		$this->catalog_repository()->set_status( 'packs', 'emoji', [ 'phase' => 'installing' ] );

		$this->publish( [ $this->pack_entry( 'emoji', [ 'label' => 'Emoji v2' ] ) ] );
		$this->sync()->run();

		$row = $this->catalog_repository()->entry( 'packs', 'emoji' );

		$this->assertSame( 'Emoji v2', $row['label'] );
		$this->assertSame( 'installing', $row['phase'] );
	}

	public function test_one_invalid_entry_refuses_the_whole_index() {
		$bad = $this->pack_entry( 'evil' );

		$bad['entry']['fonts'] = [ '../escape' => [ 'R' => 'A.ttf' ] ];

		$this->publish( [ $this->pack_entry( 'emoji' ), $bad ] );
		$this->sync()->run();

		/* Not "the good ones landed": bad data must never reach the table at all */
		$this->assertSame( 0, $this->catalog_repository()->search( 'packs' )['total'] );
		$this->assertStringContainsString( 'evil', $this->sync()->get_record( 'packs' )['last_error'] );
	}

	public function test_an_index_whose_hash_does_not_match_the_root_is_refused() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );

		/* Same length, one byte different: the size check passes, so this is the hash chain alone being tested */
		$corrupt              = $this->last_index;
		$corrupt[ strlen( $corrupt ) - 2 ] = 'X';

		$this->publish( [ $this->pack_entry( 'emoji' ) ], [], [ 'sources/packs-' => $corrupt ] );
		$this->sync()->run();

		$this->assertSame( 0, $this->catalog_repository()->search( 'packs' )['total'] );
		$this->assertStringContainsString( 'hash', $this->sync()->get_record( 'packs' )['last_error'] );
	}

	public function test_an_index_of_the_wrong_length_is_refused_before_it_is_hashed() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ], [], [ 'sources/packs-' => '{"schema":1,"entries":[]}' ] );

		$this->sync()->run();

		$this->assertSame( 0, $this->catalog_repository()->search( 'packs' )['total'] );
		$this->assertStringContainsString( 'bytes', $this->sync()->get_record( 'packs' )['last_error'] );
	}

	public function test_a_transport_failure_leaves_every_row_in_place() {
		$this->publish( [ $this->pack_entry( 'emoji' ), $this->pack_entry( 'dejavu' ) ] );
		$this->sync()->run();

		$this->mock_http( [ 'index.json' => new WP_Error( 'http_request_failed', 'Connection timed out' ) ] );
		$this->sync()->run();

		$this->assertSame( 2, $this->catalog_repository()->search( 'packs' )['total'] );

		$record = $this->sync()->get_record( 'packs' );

		$this->assertStringContainsString( 'Connection timed out', $record['last_error'] );
		$this->assertGreaterThan( 0, $record['last_attempt'] );
	}

	public function test_a_source_the_root_does_not_list_keeps_its_rows() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );
		$this->sync()->run();

		$this->publish( [], [ 'sources' => [] ] );
		$this->sync()->run();

		$this->assertSame( 1, $this->catalog_repository()->search( 'packs' )['total'] );
	}

	public function test_a_second_runner_exits_without_touching_the_catalog() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );

		( new Font_Lock() )->acquire( Catalog_Sync::LOCK, Catalog_Sync::LOCK_TTL );

		$this->assertFalse( $this->sync()->run() );
		$this->assertSame( [], $this->requested_urls() );
	}

	public function test_is_due_respects_the_interval_and_the_backoff() {
		$sync = $this->sync();

		$this->assertTrue( $sync->is_due(), 'a never-synced source is due' );

		$this->publish( [ $this->pack_entry( 'emoji' ) ] );
		$sync->run();

		$this->assertFalse( $this->sync()->is_due(), 'a freshly synced source is not due' );

		update_site_option(
			Catalog_Sync::OPTION,
			[
				'packs' => [
					'synced'       => time() - ( 60 * DAY_IN_SECONDS ),
					'last_attempt' => time() - HOUR_IN_SECONDS,
				],
			]
		);

		$this->assertFalse( $this->sync()->is_due(), 'a recent failed attempt holds the backoff' );

		update_site_option(
			Catalog_Sync::OPTION,
			[
				'packs' => [
					'synced'       => time() - ( 60 * DAY_IN_SECONDS ),
					'last_attempt' => time() - ( 12 * HOUR_IN_SECONDS ),
				],
			]
		);

		$this->assertTrue( $this->sync()->is_due(), 'past the backoff it is due again' );
	}

	public function test_is_stale_is_the_one_threshold() {
		$this->assertTrue( Catalog_Sync::is_stale( [ 'synced' => 0 ] ) );
		$this->assertTrue( Catalog_Sync::is_stale( [ 'synced' => time() - ( 50 * DAY_IN_SECONDS ) ] ) );
		$this->assertFalse( Catalog_Sync::is_stale( [ 'synced' => time() - ( 10 * DAY_IN_SECONDS ) ] ) );
	}

	public function test_request_reports_up_to_date_without_scheduling() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );
		$this->sync()->run();

		$this->publish( [ $this->pack_entry( 'emoji' ) ] );

		$result = $this->sync()->request();

		$this->assertTrue( $result['up_to_date'] );
		$this->assertFalse( $result['scheduled'] );
		$this->assertFalse( (bool) wp_next_scheduled( Catalog_Sync::EVENT ) );
	}

	public function test_request_schedules_one_event_when_the_root_changed() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );

		$result = $this->sync()->request();

		$this->assertFalse( $result['up_to_date'] );
		$this->assertTrue( $result['scheduled'] );
		$this->assertNotFalse( wp_next_scheduled( Catalog_Sync::EVENT ) );

		/* A second Refresh must not queue a second sync */
		$first = wp_next_scheduled( Catalog_Sync::EVENT );
		$this->sync()->request();

		$this->assertSame( $first, wp_next_scheduled( Catalog_Sync::EVENT ) );

		wp_clear_scheduled_hook( Catalog_Sync::EVENT );
	}

	public function test_request_surfaces_a_root_failure() {
		$this->mock_http( [ 'index.json' => new WP_Error( 'http_request_failed', 'Connection timed out' ) ] );

		$result = $this->sync()->request();

		$this->assertFalse( $result['scheduled'] );
		$this->assertStringContainsString( 'Connection timed out', $result['error'] );
	}

	public function test_the_seed_fills_an_empty_catalog_without_claiming_freshness() {
		$seed = wp_tempnam( 'packs-seed' );
		file_put_contents( $seed, (string) wp_json_encode( [ 'schema' => 1, 'entries' => [ $this->pack_entry( 'emoji' ) ] ] ) );

		$this->assertTrue( $this->sync( null, $seed )->seed() );

		$this->assertSame( 1, $this->catalog_repository()->search( 'packs' )['total'] );

		$record = $this->sync()->get_record( 'packs' );

		$this->assertTrue( $record['seeded'] );
		$this->assertSame( 0, $record['synced'], 'a seed must not report a sync that never happened' );
		$this->assertTrue( Catalog_Sync::is_stale( $record ) );

		unlink( $seed );
	}

	public function test_the_seed_does_not_overwrite_a_real_sync() {
		$this->publish( [ $this->pack_entry( 'emoji' ), $this->pack_entry( 'dejavu' ) ] );
		$this->sync()->run();

		$seed = wp_tempnam( 'packs-seed' );
		file_put_contents( $seed, (string) wp_json_encode( [ 'schema' => 1, 'entries' => [ $this->pack_entry( 'emoji' ) ] ] ) );

		$this->assertFalse( $this->sync( null, $seed )->seed() );
		$this->assertSame( 2, $this->catalog_repository()->search( 'packs' )['total'] );

		unlink( $seed );
	}

	public function test_a_missing_seed_file_is_not_an_error() {
		$this->assertFalse( $this->sync( null, '/does/not/exist.json' )->seed() );
	}

	public function test_the_root_request_carries_no_query_args() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );
		$this->sync()->run();

		foreach ( $this->requested_urls() as $url ) {
			$this->assertStringNotContainsString( '?', $url, 'a licence key must never reach the fonts host' );
		}
	}

	public function test_inlined_entries_are_stored_and_font_keys_mirrored() {
		$this->publish( [ $this->pack_entry( 'emoji' ) ] );
		$this->sync()->run();

		$row = $this->catalog_repository()->entry( 'packs', 'emoji' );

		$this->assertSame( [ 'R' => 'A.ttf' ], $row['data']['fonts']['emojifont'] );
		$this->assertSame( 'emojifont', $row['font_keys'] );
	}

	public function test_a_pointed_at_entry_stores_its_hash_and_no_json() {
		$entry = $this->pack_entry( 'lato' );
		unset( $entry['entry'] );
		$entry['entry_sha256'] = str_repeat( 'c', 64 );

		$this->publish( [ $entry ] );
		$this->sync()->run();

		$row = $this->catalog_repository()->entry( 'packs', 'lato' );

		$this->assertSame( str_repeat( 'c', 64 ), $row['entry_sha256'] );
		$this->assertNull( $row['data'] );
	}

	public function test_array_index_fields_become_csv_columns() {
		$this->publish(
			[
				$this->pack_entry(
					'dejavu',
					[
						'scripts'   => [ 'und-Latn', 'und-Grek' ],
						'languages' => [ 'en', 'fr' ],
						'subsets'   => [ 'latin', 'latin-ext' ],
					]
				),
			]
		);
		$this->sync()->run();

		$row = $this->catalog_repository()->entry( 'packs', 'dejavu' );

		$this->assertSame( 'und-Latn,und-Grek', $row['scripts'] );
		$this->assertSame( 'en,fr', $row['languages'] );
		$this->assertSame( 'latin,latin-ext', $row['subsets'] );
	}
}
