<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Catalog_Repository;
use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Fonts\Font_Sources;
use GFPDF\Helper\Helper_Abstract_Form;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

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
 * Browse the font catalogue
 *
 * Source-generic from day one: a source is a path segment, so a new one — an add-on's, a self-hosted library —
 * appears here with no route change. Nothing in this class names a vendor.
 *
 * These routes answer from the cached catalog rows and the sync's option record. They never fetch, and they never
 * carry installed state: what a site has installed is the font rows, read through `GET /fonts/status`, so a
 * catalogue that is stale, seeded or empty cannot change what the Font Manager says is installed.
 *
 * @since 7.0
 */
class Rest_Font_Sources extends Rest_Font_Entry_Base {

	/**
	 * Fixed rather than a parameter: the browser is a grid of cards, not an export
	 *
	 * @since 7.0
	 */
	public const PER_PAGE = 50;

	/**
	 * The catalogue fields a route emits, named rather than subtracted from the row
	 *
	 * Projecting the row would make the response contract a function of the table: `list_columns()` carries the
	 * six status columns, so "the row minus `entry_json`" ships install progress on a route that documents itself
	 * as never carrying it, and every column added for a storage reason joins the payload silently.
	 *
	 * @since 7.0
	 */
	public const FIELDS = [
		'source',
		'entry',
		'label',
		'version',
		'notes',
		'released',
		'coverage',
		'position',
		'license',
		'size',
		'files',
		'category',
		'subsets',
		'preview_text',
		'styles',
		'always',
		'scripts',
		'languages',
	];

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Catalog_Sync
	 * @since 7.0
	 */
	protected $sync;

	public function __construct( Catalog_Repository $catalog, Catalog_Sync $sync, Font_Sources $sources, Helper_Abstract_Form $gform ) {
		$this->catalog = $catalog;
		$this->sync    = $sync;
		$this->sources = $sources;
		$this->gform   = $gform;
	}

	/**
	 * @since 7.0
	 */
	public function register_routes() {
		/*
		 * `sync` is a reserved source id, so the literal route can never be shadowed by `{source}`. Registered
		 * first anyway, since only same-method routes can collide and both of these are not.
		 */
		register_rest_route(
			static::NAMESPACE,
			static::API_BASE . '/sync',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'sync_sources' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
				],
			]
		);

		register_rest_route(
			static::NAMESPACE,
			static::API_BASE,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		register_rest_route(
			static::NAMESPACE,
			static::API_BASE . '/(?P<source>[a-z0-9-]+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_source' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => [
						's'        => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'category' => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'subset'   => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'coverage' => [
							'type'    => 'boolean',
							'default' => null,
						],
						'page'     => [
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						],
					],
				],
			]
		);

		register_rest_route(
			static::NAMESPACE,
			static::ENTRY_ROUTE,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_entry' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);
	}

	/**
	 * Every registered source, with its counts, filter vocabulary and sync state
	 *
	 * Built from the records, the option record and the cached summaries — never a fetch, so this stays cheap
	 * enough for the Font Manager to open with.
	 *
	 * @return WP_REST_Response
	 *
	 * @since 7.0
	 */
	public function get_items( $request ) {
		$items   = [];
		$records = $this->sync->get_records();

		foreach ( $this->sources->all() as $id => $source ) {
			$summary = $this->catalog->summary( $id );
			$record  = array_merge( Catalog_Sync::default_record(), $records[ $id ] ?? [] );

			$items[] = [
				'id'           => $id,
				'label'        => $source->get_label(),
				'description'  => $source->get_description(),
				'total'        => $summary['total'],
				'coverage'     => $summary['coverage'],
				'filters'      => [
					'category' => $this->label_filters( $summary['filters']['category'] ),
					'subsets'  => $this->label_filters( $summary['filters']['subsets'] ),
				],
				/* null rather than 0, so "never synced" is not a date in 1970 */
				'synced'       => (int) $record['synced'] > 0 ? (int) $record['synced'] : null,
				'stale'        => Catalog_Sync::is_stale( $record ),
				'seeded'       => (bool) $record['seeded'],
				'last_attempt' => (int) $record['last_attempt'] > 0 ? (int) $record['last_attempt'] : null,
				'last_error'   => (string) $record['last_error'],
			];
		}

		return rest_ensure_response( $items );
	}

	/**
	 * One page of a source's catalogue
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 7.0
	 */
	public function get_source( $request ) {
		$id = (string) $request['source'];

		$error = $this->check_source( $id );
		if ( $error !== null ) {
			return $error;
		}

		$result = $this->catalog->search(
			$id,
			[
				's'        => (string) $request['s'],
				'category' => (string) $request['category'],
				'subset'   => (string) $request['subset'],
				'coverage' => $request['coverage'],
				'page'     => (int) $request['page'],
				'per_page' => static::PER_PAGE,
			]
		);

		$record = $this->sync->get_record( $id );

		return rest_ensure_response(
			[
				'entries' => array_map( [ $this, 'prepare_row' ], $result['entries'] ),
				'total'   => $result['total'],
				'pages'   => $result['pages'],
				'synced'  => (int) $record['synced'] > 0 ? (int) $record['synced'] : null,
			]
		);
	}

	/**
	 * One catalogue entry
	 *
	 * Index fields only: no route ever fetches an entry file, and an inlined entry's contents are the installer's
	 * business rather than the browser's.
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 7.0
	 */
	public function get_entry( $request ) {
		$row = $this->entry_row( $this->catalog, (string) $request['source'], (string) $request['entry'] );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$fonts = (array) ( $row['data']['fonts'] ?? [] );

		$prepared                 = $this->prepare_row( $row );
		$prepared['preview_urls'] = $this->catalog->preview_urls( $row, $fonts );

		return rest_ensure_response( $prepared );
	}

	/**
	 * The Font Manager's Refresh
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 7.0
	 */
	public function sync_sources( $request ) {
		$result = $this->sync->request();

		if ( isset( $result['error'] ) ) {
			return new WP_Error(
				'font_source_unavailable',
				$result['error'],
				[ 'status' => 502 ]
			);
		}

		if ( $result['up_to_date'] ) {
			return rest_ensure_response( [ 'up_to_date' => true ] );
		}

		return new WP_REST_Response( [ 'up_to_date' => false ], 202 );
	}

	/**
	 * A catalog row as a route emits it
	 *
	 * `preview_url` is the first key's regular face, which is all a card shows; the detail route sends the full set.
	 *
	 * @since 7.0
	 */
	protected function prepare_row( array $row ): array {
		$previews = $this->catalog->preview_urls( $row );
		$first    = reset( $previews );

		$prepared = [];
		foreach ( static::FIELDS as $field ) {
			$prepared[ $field ] = $row[ $field ] ?? null;
		}

		$prepared['label']       = Font_Sources::translate_entry( (string) $row['source'], (string) $row['entry'], 'label', (string) $row['label'] );
		$prepared['preview_url'] = is_array( $first ) ? ( $first['R'] ?? null ) : null;

		return $prepared;
	}

	/**
	 * Pair each filter id with a label the browser can show
	 *
	 * An id outside the map is title-cased rather than dropped, so a source using vocabulary we have not translated
	 * still gets a usable control.
	 *
	 * @param array<string, int> $counts
	 *
	 * @return array<int, array{id: string, label: string, count: int}>
	 *
	 * @since 7.0
	 */
	protected function label_filters( array $counts ): array {
		$labels = $this->get_filter_labels();

		$filters = [];
		foreach ( $counts as $id => $count ) {
			$filters[] = [
				'id'    => (string) $id,
				'label' => $labels[ $id ] ?? ucwords( str_replace( '-', ' ', (string) $id ) ),
				'count' => (int) $count,
			];
		}

		return $filters;
	}

	/**
	 * The browser's dropdown vocabulary
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	protected function get_filter_labels(): array {
		static $labels = null;

		if ( $labels !== null ) {
			return $labels;
		}

		$labels = [
			/* Categories */
			'sans-serif'          => __( 'Sans-serif', 'gravity-pdf' ),
			'serif'               => __( 'Serif', 'gravity-pdf' ),
			'display'             => __( 'Display', 'gravity-pdf' ),
			'handwriting'         => __( 'Handwriting', 'gravity-pdf' ),
			'monospace'           => __( 'Monospace', 'gravity-pdf' ),

			/* Subsets */
			'latin'               => __( 'Latin', 'gravity-pdf' ),
			'latin-ext'           => __( 'Latin Extended', 'gravity-pdf' ),
			'greek'               => __( 'Greek', 'gravity-pdf' ),
			'greek-ext'           => __( 'Greek Extended', 'gravity-pdf' ),
			'cyrillic'            => __( 'Cyrillic', 'gravity-pdf' ),
			'cyrillic-ext'        => __( 'Cyrillic Extended', 'gravity-pdf' ),
			'arabic'              => __( 'Arabic', 'gravity-pdf' ),
			'hebrew'              => __( 'Hebrew', 'gravity-pdf' ),
			'devanagari'          => __( 'Devanagari', 'gravity-pdf' ),
			'bengali'             => __( 'Bengali', 'gravity-pdf' ),
			'tamil'               => __( 'Tamil', 'gravity-pdf' ),
			'thai'                => __( 'Thai', 'gravity-pdf' ),
			'vietnamese'          => __( 'Vietnamese', 'gravity-pdf' ),
			'korean'              => __( 'Korean', 'gravity-pdf' ),
			'japanese'            => __( 'Japanese', 'gravity-pdf' ),
			'chinese-simplified'  => __( 'Chinese (Simplified)', 'gravity-pdf' ),
			'chinese-traditional' => __( 'Chinese (Traditional)', 'gravity-pdf' ),
			'emoji'               => __( 'Emoji', 'gravity-pdf' ),
			'math'                => __( 'Math', 'gravity-pdf' ),
			'symbols'             => __( 'Symbols', 'gravity-pdf' ),
		];

		return $labels;
	}
}
