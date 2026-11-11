<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\AccessesPrivateState;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Reading options from many sites' tables in chunked `UNION ALL` queries
 *
 * @group model
 * @group ms-required
 */
class Test_Model_Site_Options extends TestCase {

	use AccessesPrivateState;

	private const NAMES = [ 'gfpdf_test_a', 'gfpdf_test_b' ];

	/**
	 * @var TestHandler
	 */
	private $log;

	/**
	 * @var string[] The site option queries run
	 */
	private $queries = [];

	public function set_up(): void {
		parent::set_up();

		$this->skipWithoutMultisite();
		$this->log = new TestHandler();
		add_filter( 'query', [ $this, 'capture_query' ] );
	}

	public function tear_down(): void {
		remove_filter( 'query', [ $this, 'capture_query' ] );

		/* A subsite leaves these behind */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
	}

	/**
	 * @param mixed $query
	 *
	 * @return mixed
	 */
	public function capture_query( $query ) {
		if ( is_string( $query ) && strpos( $query, ' AS site_id' ) !== false && strpos( $query, "'gfpdf_test_a'" ) !== false ) {
			$this->queries[] = $query;
		}

		return $query;
	}

	private function reader( int $chunk_size = 100 ): Model_Site_Options {
		$reader = new Model_Site_Options( new Logger( 'test', [ $this->log ] ) );
		$this->set_private( $reader, 'chunk_size', $chunk_size );

		return $reader;
	}

	/**
	 * @return int[] Sites with both test options, one with an array value
	 */
	private function sites( int $count ): array {
		$sites = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$site_id = self::factory()->blog->create();
			update_blog_option( $site_id, 'gfpdf_test_a', "a-$site_id" );
			update_blog_option( $site_id, 'gfpdf_test_b', [ 'site' => $site_id ] );
			$sites[] = $site_id;
		}

		return $sites;
	}

	private function expected( int $site_id ): array {
		return [
			'gfpdf_test_a' => "a-$site_id",
			'gfpdf_test_b' => serialize( [ 'site' => $site_id ] ), //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		];
	}

	/**
	 * @return array<int, string[]> The level and message of each log record
	 */
	private function log_messages(): array {
		return array_map(
			static function ( $record ) {
				return [ $record['level_name'], $record['message'] ];
			},
			$this->log->getRecords()
		);
	}

	public function test_each_chunk_of_sites_is_read_in_one_query() {
		$sites = array_merge( [ get_main_site_id() ], $this->sites( 4 ) );
		update_blog_option( get_main_site_id(), 'gfpdf_test_a', 'main' );
		delete_blog_option( $sites[2], 'gfpdf_test_b' );

		$options = $this->reader( 2 )->get_options( $sites, self::NAMES );

		$this->assertSame( $sites, array_keys( $options ) );
		$this->assertSame( [ 'gfpdf_test_a' => 'main' ], $options[ $sites[0] ] );
		$this->assertSame( [ 'gfpdf_test_a' => "a-{$sites[2]}" ], $options[ $sites[2] ] );
		foreach ( [ 1, 3, 4 ] as $i ) {
			$this->assertSame( $this->expected( $sites[ $i ] ), $options[ $sites[ $i ] ] );
		}

		$this->assertCount( 3, $this->queries );
		$this->assertSame( [ 1, 1, 0 ], array_map( 'substr_count', $this->queries, array_fill( 0, 3, 'UNION ALL' ) ) );
		$this->assertSame( [], $this->log_messages() );
	}

	public function test_chunks_read_the_same_as_one_site_at_a_time() {
		$sites   = $this->sites( 5 );
		$sites[] = 999999;
		delete_blog_option( $sites[1], 'gfpdf_test_a' );

		[ $per_site, $chunked, $one ] = $this->without_db_errors(
			function () use ( $sites ) {
				return [
					$this->reader( 1 )->get_options( $sites, self::NAMES ),
					$this->reader( 2 )->get_options( $sites, self::NAMES ),
					$this->reader()->get_options( $sites, self::NAMES ),
				];
			}
		);

		$this->assertSame( $per_site, $chunked );
		$this->assertSame( $per_site, $one );
		$this->assertSame( [], $per_site[999999] );
	}

	public function test_a_dropped_table_has_no_options() {
		$sites = $this->sites( 5 );
		$this->drop_site_options_table( $sites[1] );

		$options = $this->without_db_errors(
			function () use ( $sites ) {
				return $this->reader( 3 )->get_options( $sites, self::NAMES );
			}
		);

		$this->assertSame( [], $options[ $sites[1] ] );
		foreach ( [ 0, 2, 3, 4 ] as $i ) {
			$this->assertSame( $this->expected( $sites[ $i ] ), $options[ $sites[ $i ] ] );
		}

		/* The failed chunk is read again a site at a time; the chunk after it isn't */
		$this->assertCount( 5, $this->queries );
		$this->assertSame( [ 2, 0, 0, 0, 1 ], array_map( 'substr_count', $this->queries, array_fill( 0, 5, 'UNION ALL' ) ) );
		$this->assertSame(
			[
				[ 'NOTICE', 'Sites\' options could not be read together, so each is read on its own' ],
				[ 'WARNING', 'A site has no options table, so it has no options' ],
			],
			$this->log_messages()
		);
		$this->assertSame( $sites[1], $this->log->getRecords()[1]['context']['site_id'] );
	}

	public function test_a_database_error_stops_the_reads() {
		$sites   = $this->sites( 5 );
		$options = $this->with_failing_site_option_reads(
			function () use ( $sites ) {
				return $this->reader( 3 )->get_options( $sites, self::NAMES );
			}
		);

		$this->assertSame( array_fill_keys( $sites, null ), $options );

		/* The first chunk and its first site; nothing after */
		$this->assertCount( 2, $this->queries );
		$this->assertSame(
			[
				[ 'NOTICE', 'Sites\' options could not be read together, so each is read on its own' ],
				[ 'ERROR', 'Stopped reading sites\' options after a database error' ],
			],
			$this->log_messages()
		);
	}

	public function test_primed_sites_are_read_without_another_query() {
		$sites  = $this->sites( 3 );
		$reader = $this->reader();

		$reader->prime( [ $sites[0], $sites[1] ], self::NAMES );
		$this->assertCount( 1, $this->queries );

		/* A subset of the names primed, then a site that wasn't primed beside one that was */
		$this->assertSame( [ $sites[0] => [ 'gfpdf_test_a' => "a-{$sites[0]}" ] ], $reader->get_options( [ $sites[0] ], [ 'gfpdf_test_a' ] ) );
		$this->assertCount( 1, $this->queries );
		$this->assertSame( $this->expected( $sites[2] ), $reader->get_options( [ $sites[1], $sites[2] ], self::NAMES )[ $sites[2] ] );
		$this->assertCount( 2, $this->queries );
		$this->assertStringNotContainsString( "{$sites[1]} AS site_id", $this->queries[1] );

		$reader->prime( [], [] );
		$reader->get_options( [ $sites[0] ], self::NAMES );
		$this->assertCount( 3, $this->queries );
	}

	public function test_sites_are_read_a_chunk_at_a_time() {
		$sites = $this->sites( 4 );

		$each = $this->reader( 2 )->each_site_options( $sites, self::NAMES );

		$this->assertSame( $sites[0], $each->key() );
		$this->assertSame( $this->expected( $sites[0] ), $each->current() );
		$this->assertCount( 1, $this->queries );
	}

	public function test_tables_with_different_character_sets_are_read_together() {
		global $wpdb;

		$sites = $this->sites( 3 );
		$other = $wpdb->collate === 'utf8mb4_general_ci' ? 'utf8mb4_unicode_ci' : 'utf8mb4_general_ci';
		foreach ( [ $sites[1] => 'CHARSET=latin1', $sites[2] => "CHARSET=utf8mb4 COLLATE=$other" ] as $site_id => $charset ) {
			$table = $wpdb->get_blog_prefix( $site_id ) . 'options';
			$this->drop_site_options_table( $site_id );
			/* Made a temporary table by the test suite */
			$wpdb->query( "CREATE TABLE $table ( option_id bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, option_name varchar(191) NOT NULL UNIQUE, option_value longtext NOT NULL, autoload varchar(20) NOT NULL DEFAULT 'auto' ) $charset" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->insert(
				$table,
				[
					'option_name'  => 'gfpdf_test_a',
					'option_value' => 'Café',
				]
			);
		}

		$options = $this->reader()->get_options( $sites, self::NAMES );

		$this->assertSame( $this->expected( $sites[0] ), $options[ $sites[0] ] );
		$this->assertSame( [ 'gfpdf_test_a' => 'Café' ], $options[ $sites[1] ] );
		$this->assertSame( [ 'gfpdf_test_a' => 'Café' ], $options[ $sites[2] ] );
		$this->assertCount( 1, $this->queries );
		$this->assertSame( [], $this->log_messages() );
		$this->assertSame( $this->reader( 1 )->get_options( $sites, self::NAMES ), $options );
	}

	public function test_nothing_to_read_runs_no_query() {
		$this->assertSame( [], $this->reader()->get_options( [], self::NAMES ) );
		$this->assertSame( [ 5 => [] ], $this->reader()->get_options( [ '5', 5 ], [] ) );
		$this->assertSame( [], $this->queries );
	}
}
