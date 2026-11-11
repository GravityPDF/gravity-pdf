<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF_Vendor\Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads options from many Multisite sites' tables at once, without switch_to_blog()
 *
 * @since 6.19.0
 */
class Model_Site_Options {

	/**
	 * How many sites one query reads. 100 was faster than 1,000 on networks of 1,000 and 10,000 sites.
	 *
	 * @since 6.19.0
	 */
	public const CHUNK_SIZE = 100;

	/**
	 * @var int
	 * @since 6.19.0
	 */
	protected $chunk_size = self::CHUNK_SIZE;

	/**
	 * @var LoggerInterface
	 * @since 6.19.0
	 */
	protected $log;

	/**
	 * @var array<int, array<string, string>|null> The options prime() read ahead, by site ID
	 * @since 6.19.0
	 */
	protected $primed = [];

	/**
	 * @var string[] The option names prime() read
	 * @since 6.19.0
	 */
	protected $primed_names = [];

	/**
	 * @since 6.19.0
	 */
	public function __construct( LoggerInterface $log ) {
		$this->log = $log;
	}

	/**
	 * Read the sites' options ahead in one query, for reads of those sites and options to use until the next prime().
	 * A loop working a site at a time reads its next chunk this way; prime( [], [] ) ends it.
	 *
	 * @param int[]    $site_ids
	 * @param string[] $names    The option names
	 *
	 * @since 6.19.0
	 */
	public function prime( array $site_ids, array $names ): void {
		/* Cleared first, so get_options() reads the database */
		$this->primed_names = [];
		$this->primed       = $this->get_options( $site_ids, $names );
		$this->primed_names = $names;
	}

	/**
	 * Read options from each site's options table, a chunk of sites per `UNION ALL` query. A chunk that fails, such as
	 * for a missing table, is read again one site at a time. A site with no options table has no options. A database
	 * error that isn't one site's missing table stops the reads, and the sites left fail.
	 *
	 * @param int[]    $site_ids
	 * @param string[] $names    The option names
	 *
	 * @return array<int, array<string, string>|null> By site ID: the raw values of the options it has, or null when
	 *                                                they couldn't be read
	 *
	 * @since 6.19.0
	 */
	public function get_options( array $site_ids, array $names ): array {
		return iterator_to_array( $this->each_site_options( $site_ids, $names ) );
	}

	/**
	 * get_options() a site at a time, holding one chunk's values at once
	 *
	 * @param int[]    $site_ids
	 * @param string[] $names    The option names
	 *
	 * @return \Generator<int, array<string, string>|null>
	 *
	 * @since 6.19.0
	 */
	public function each_site_options( array $site_ids, array $names ): \Generator {
		$site_ids = array_unique( array_map( 'intval', $site_ids ) );
		if ( $names === [] ) {
			yield from array_fill_keys( $site_ids, [] );

			return;
		}

		$wanted = array_flip( $names );
		$primed = array_diff( $names, $this->primed_names ) === []
			? array_intersect_key( $this->primed, array_flip( $site_ids ) )
			: [];
		foreach ( $primed as $site_id => $options ) {
			yield $site_id => $options === null ? null : array_intersect_key( $options, $wanted );
		}

		/* The sites prime() read ahead are answered from there */
		$site_ids = array_diff( $site_ids, array_keys( $primed ) );
		$select   = $this->get_select( $names );
		$stopped  = false;
		foreach ( array_chunk( $site_ids, $this->chunk_size ) as $chunk ) {
			if ( $stopped ) {
				yield from array_fill_keys( $chunk, null );
				continue;
			}

			/* One site's UNION would be the same query its retry runs */
			$rows = count( $chunk ) > 1 ? $this->read_chunk( $chunk, $select ) : null;
			yield from $rows ?? $this->read_each( $chunk, $select, $names, $stopped );
		}
	}

	/**
	 * @param int[] $site_ids
	 *
	 * @return array<int, array<string, string>>|null Null when the query failed
	 *
	 * @since 6.19.0
	 */
	protected function read_chunk( array $site_ids, string $select ): ?array {
		global $wpdb;

		$selects = array_map(
			function ( int $site_id ) use ( $select ): string {
				return $this->select_site( $select, $site_id );
			},
			$site_ids
		);

		/* Each site is read again on failure, and reports its own error then */
		$suppress = $wpdb->suppress_errors( true );
		$rows     = $wpdb->get_results( implode( ' UNION ALL ', $selects ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->suppress_errors( $suppress );

		if ( $wpdb->last_error !== '' ) {
			$this->log->notice(
				'Sites\' options could not be read together, so each is read on its own',
				[
					'sites' => count( $site_ids ),
					'error' => $wpdb->last_error,
				]
			);

			return null;
		}

		return $this->group_rows( $site_ids, (array) $rows );
	}

	/**
	 * @param int[]    $site_ids
	 * @param string[] $names    For the log
	 * @param bool     $stopped  Set when a database error stops the reads, failing the sites left unread
	 *
	 * @return array<int, array<string, string>|null>
	 *
	 * @since 6.19.0
	 */
	protected function read_each( array $site_ids, string $select, array $names, bool &$stopped ): array {
		global $wpdb;

		$options = [];
		foreach ( $site_ids as $site_id ) {
			$rows = $wpdb->get_results( $this->select_site( $select, $site_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

			if ( $wpdb->last_error !== '' ) {
				$context = [
					'site_id' => $site_id,
					'options' => $names,
					'error'   => $wpdb->last_error,
				];

				if ( ! $this->is_table_missing( $site_id ) ) {
					$stopped = true;
					$this->log->error( 'Stopped reading sites\' options after a database error', $context );
					break;
				}

				$this->log->warning( 'A site has no options table, so it has no options', $context );
				$options[ $site_id ] = [];
				continue;
			}

			$options[ $site_id ] = $this->group_rows( [ $site_id ], (array) $rows )[ $site_id ];
		}

		return $options + array_fill_keys( $site_ids, null );
	}

	/**
	 * Whether a site's options table is missing, rather than the database failing every site's read
	 *
	 * @since 6.19.0
	 */
	protected function is_table_missing( int $site_id ): bool {
		global $wpdb;

		$table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->get_blog_prefix( $site_id ) . 'options' ) ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $wpdb->last_error === '' && $table === null;
	}

	/**
	 * The query reading one site's options, with `{site_id}` and `{prefix}` to fill in. The values are converted to the
	 * connection's character set, as the server does for a one-site read, so tables with different collations can
	 * share a `UNION`.
	 *
	 * @param string[] $names
	 *
	 * @since 6.19.0
	 */
	protected function get_select( array $names ): string {
		global $wpdb;

		$charset      = is_string( $wpdb->charset ) && preg_match( '/^[a-z0-9_]+$/i', $wpdb->charset ) ? $wpdb->charset : 'utf8mb4';
		$placeholders = implode( ', ', array_fill( 0, count( $names ), '%s' ) );

		/* The character set comes from WordPress */
		return $wpdb->prepare( "SELECT {site_id} AS site_id, CONVERT( option_name USING {$charset} ) AS option_name, CONVERT( option_value USING {$charset} ) AS option_value FROM {prefix}options WHERE option_name IN ( {$placeholders} )", $names ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	}

	/**
	 * @since 6.19.0
	 */
	protected function select_site( string $select, int $site_id ): string {
		global $wpdb;

		return strtr(
			$select,
			[
				'{site_id}' => (string) $site_id,
				'{prefix}'  => $wpdb->get_blog_prefix( $site_id ),
			]
		);
	}

	/**
	 * @param int[]    $site_ids
	 * @param object[] $rows
	 *
	 * @return array<int, array<string, string>>
	 *
	 * @since 6.19.0
	 */
	protected function group_rows( array $site_ids, array $rows ): array {
		$options = array_fill_keys( $site_ids, [] );
		foreach ( $rows as $row ) {
			$options[ (int) $row->site_id ][ (string) $row->option_name ] = (string) $row->option_value;
		}

		return $options;
	}
}
