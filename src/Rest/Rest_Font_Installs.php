<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Catalog_Repository;
use GFPDF\Fonts\Font_Installer;
use GFPDF\Fonts\Font_Repository;
use GFPDF\Fonts\Font_Sources;
use GFPDF\Fonts\Install_Queue;
use GFPDF\Fonts\Registry;
use GFPDF\Helper\Helper_Abstract_Form;
use WP_Error;
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
 * Install a catalogue entry, and report what every install is doing
 *
 * The counterpart to `Rest_Font_Sources`, which browses the catalogue and deliberately carries no installed state.
 * Everything here is about the font tables: what is installed, what is being installed, and what a POST asks the
 * background queue to install next. Nothing on these routes fetches a font — an install is queued, never performed
 * in-request, so a pack of seventeen files cannot time out an admin screen.
 *
 * Source-generic like its counterpart: a coverage entry (a language pack) and a display entry (a catalogue family)
 * differ only in whether the caller may name the rows, and that is a column on the entry.
 *
 * @since 7.0
 */
class Rest_Font_Installs extends Rest_Font_Entry_Base {

	/**
	 * How many keys one display entry may be installed under
	 *
	 * Rows, not disk: two installs of a family share every file they resolve to the same variant for, so the cap
	 * bounds the font dropdown rather than storage.
	 *
	 * @since 7.0
	 */
	public const MAX_INSTALLS = 10;

	/**
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var Font_Installer
	 * @since 7.0
	 */
	protected $installer;

	/**
	 * @var Install_Queue
	 * @since 7.0
	 */
	protected $queue;

	public function __construct(
		Registry $registry,
		Catalog_Repository $catalog,
		Font_Repository $repository,
		Font_Installer $installer,
		Install_Queue $queue,
		Font_Sources $sources,
		Helper_Abstract_Form $gform
	) {
		$this->registry   = $registry;
		$this->catalog    = $catalog;
		$this->repository = $repository;
		$this->installer  = $installer;
		$this->queue      = $queue;
		$this->sources    = $sources;
		$this->gform      = $gform;
	}

	/**
	 * @since 7.0
	 */
	public function register_routes() {
		register_rest_route(
			static::NAMESPACE,
			'/fonts/status',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_status' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
				],
			]
		);

		register_rest_route(
			static::NAMESPACE,
			'/fonts/updates',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'install_updates' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
				],
			]
		);

		register_rest_route(
			static::NAMESPACE,
			static::ENTRY_ROUTE,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'install_entry' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => [
						'label'    => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'variants' => [
							'type'    => 'object',
							'default' => [],
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_entry' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
				],
			]
		);
	}

	/**
	 * What every entry with rows or a phase is doing
	 *
	 * The one route the Font Manager polls, and the only one carrying installed state. Cast to an object so an
	 * empty result is `{}` rather than `[]`: the store merges it into a map either way.
	 *
	 * @return WP_REST_Response
	 *
	 * @since 7.0
	 */
	public function get_status( $request ) {
		return rest_ensure_response( (object) $this->registry->get_install_statuses( $this->queue ) );
	}

	/**
	 * Install a catalogue entry, or update the installs it already has
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 7.0
	 */
	public function install_entry( $request ) {
		$row = $this->entry_row( $this->catalog, (string) $request['source'], (string) $request['entry'] );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$installs = $this->requested_installs( $row, (string) $request['label'], (array) $request['variants'] );

		if ( is_wp_error( $installs ) ) {
			return $installs;
		}

		$queued = $this->queue_entry( $row, $installs );

		if ( is_wp_error( $queued ) ) {
			return $queued;
		}

		$statuses = $this->registry->get_install_statuses( $this->queue );
		$id       = $this->entry_id( $row );

		/*
		 * The claim was lost. For a language pack that is the outcome the caller wanted — something else is already
		 * installing it — so the status says so and the poller takes over. A family is different: the install in
		 * flight may be a different one, under a different key, and would silently swallow this request's own.
		 */
		if ( ! $queued && ! $this->is_coverage( $row ) && in_array( (string) $statuses[ $id ]['phase'], Catalog_Repository::LIVE_PHASES, true ) ) {
			return new WP_Error(
				'font_install_in_progress',
				__( 'This font is already being installed. Try again once it has finished.', 'gravity-pdf' ),
				[ 'status' => 409 ]
			);
		}

		return new WP_REST_Response( $this->statuses_for( $statuses, [ $id ] ), 202 );
	}

	/**
	 * Remove an entry's fonts, and answer with what it looks like afterwards
	 *
	 * Never a 404 for "not installed": the entry route is about the catalogue entry, and removing one that has no
	 * rows is how an admin declines a language pack the always rule would otherwise keep reinstalling. The only
	 * refusal is a file of it being downloaded at that moment, which clears itself.
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 7.0
	 */
	public function delete_entry( $request ) {
		$row = $this->entry_row( $this->catalog, (string) $request['source'], (string) $request['entry'] );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$id      = $this->entry_id( $row );
		$removed = $this->installer->remove( $id );

		if ( is_wp_error( $removed ) ) {
			$removed->add_data( [ 'status' => 409 ], $removed->get_error_code() );

			return $removed;
		}

		return new WP_REST_Response( (object) [ $id => $this->registry->get_install_status( $id, $this->queue ) ], 200 );
	}

	/**
	 * Install every entry the catalogue has moved past
	 *
	 * "Update all". Each entry is the ordinary upsert of every install it already has, through the same
	 * `queue_entry()` the entry route uses, so there is genuinely no second update path. One unreachable entry
	 * does not fail the batch: the rest still update, and its row keeps the backoff the installer gave it.
	 *
	 * @return WP_REST_Response
	 *
	 * @since 7.0
	 */
	public function install_updates( $request ) {
		$queued = [];

		foreach ( $this->registry->get_install_statuses( $this->queue ) as $id => $status ) {
			if ( empty( $status['update_available'] ) ) {
				continue;
			}

			[ $source, $entry ] = Font_Sources::split( (string) $id );

			$row = $this->catalog->entry( $source, $entry );

			if ( $row === null ) {
				continue;
			}

			if ( $this->queue_entry( $row, $this->installs_on_record( $row ) ) === true ) {
				$queued[] = (string) $id;
			}
		}

		return new WP_REST_Response(
			$this->statuses_for( $this->registry->get_install_statuses( $this->queue ), $queued ),
			202
		);
	}

	/**
	 * Hand a set of installs to the queue
	 *
	 * @param array[] $installs
	 *
	 * @return bool|WP_Error Whether anything was queued, or why the entry could not be read
	 *
	 * @since 7.0
	 */
	protected function queue_entry( array $row, array $installs ) {
		$id    = $this->entry_id( $row );
		$files = $this->installer->files_for_installs( $id, $installs );

		if ( is_wp_error( $files ) ) {
			$files->add_data( [ 'status' => 502 ], $files->get_error_code() );

			return $files;
		}

		$requests = [];

		foreach ( $installs as $index => $install ) {
			$requests[] = [
				'install'    => $install,
				'background' => $files[ $index ],
			];
		}

		return $this->queue->enqueue_once(
			[
				'entry'    => $id,
				'installs' => $requests,

				/* An install and an update both mean "these files, whatever is already on disk" */
				'force'    => true,
			],
			true
		);
	}

	/**
	 * The install payloads a request asks for
	 *
	 * Three cases. A coverage entry is always its own rows — the fallback maps in `meta` name them, so the keys
	 * and labels are the entry's, not the caller's. A body naming a label or variants is one install under a key
	 * the installer derives from that label. An empty body on an entry that already has rows is the update: every
	 * install it holds, each with the variants it was installed with, which is what makes a re-POST idempotent.
	 *
	 * @return array[]|WP_Error
	 *
	 * @since 7.0
	 */
	protected function requested_installs( array $row, string $label, array $variants ) {
		$label  = trim( $label );
		$chosen = $label !== '' || $variants !== [];

		if ( $this->is_coverage( $row ) ) {
			if ( $chosen ) {
				return new WP_Error(
					'font_entry_owns_its_fonts',
					__( 'A language pack names its own fonts, so it cannot be installed under a chosen name or set of styles.', 'gravity-pdf' ),
					[ 'status' => 400 ]
				);
			}

			return [ [] ];
		}

		$error = $this->check_variants( $row, $variants );

		if ( $error !== null ) {
			return $error;
		}

		if ( ! $chosen ) {
			return $this->installs_on_record( $row );
		}

		$rows    = $this->repository->rows_for_entry( (string) $row['source'], (string) $row['entry'] );
		$install = $variants === [] ? [] : [ 'variants' => $variants ];

		if ( $label === '' ) {
			return [ $install ];
		}

		$error = $this->check_install_limit( $rows, $label );

		if ( $error !== null ) {
			return $error;
		}

		$install['label'] = $label;

		return [ $install ];
	}

	/**
	 * Every install an entry already holds, as the payloads that would rewrite them
	 *
	 * The update path, and the whole of what an empty body means. It cannot fail — the payloads are read back off
	 * rows the installer itself wrote — which is why "Update all" can reach it without repeating any of the
	 * validation the entry route does on a body.
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function installs_on_record( array $row ): array {
		if ( $this->is_coverage( $row ) ) {
			return [ [] ];
		}

		$rows = $this->repository->rows_for_entry( (string) $row['source'], (string) $row['entry'] );

		return $rows === [] ? [ [] ] : array_values( array_map( [ $this, 'install_for_row' ], $rows ) );
	}

	/**
	 * One existing row, as the install that would rewrite it
	 *
	 * `label` is the identity the installer matches on, so this is idempotent by construction: it finds the row it
	 * came from. The variants come off the file rows because that is where the choice was recorded.
	 *
	 * @since 7.0
	 */
	protected function install_for_row( array $font ): array {
		$variants = [];

		foreach ( (array) $font['files'] as $role => $file ) {
			if ( in_array( (string) $role, Font_Repository::FACE_ROLES, true ) && (string) $file['variant'] !== '' ) {
				$variants[ (string) $role ] = (string) $file['variant'];
			}
		}

		$install = [ 'label' => (string) $font['label'] ];

		if ( $variants !== [] ) {
			$install['variants'] = $variants;
		}

		return $install;
	}

	/**
	 * Every variant a body names has to be a role the installer fills and a style the entry publishes
	 *
	 * @since 7.0
	 */
	protected function check_variants( array $row, array $variants ): ?WP_Error {
		if ( $variants === [] ) {
			return null;
		}

		$styles = array_filter( array_map( 'trim', explode( ',', (string) ( $row['styles'] ?? '' ) ) ) );

		foreach ( $variants as $role => $variant ) {
			if ( in_array( (string) $role, Font_Repository::FACE_ROLES, true ) && in_array( (string) $variant, $styles, true ) ) {
				continue;
			}

			return new WP_Error(
				'font_variant_unknown',
				sprintf(
					/* translators: %s: the style the request asked for */
					__( 'This font does not publish a %s style.', 'gravity-pdf' ),
					(string) $variant
				),
				[ 'status' => 400 ]
			);
		}

		return null;
	}

	/**
	 * Refuse a name that would take a family past `MAX_INSTALLS` rows
	 *
	 * Only a new name counts: re-POSTing a label the entry already holds is an update of that row, and an entry
	 * sitting on the cap must still be updatable. This is the only path that can breach it — every other install
	 * came from a row that already exists.
	 *
	 * @param array[] $rows The entry's existing font rows
	 *
	 * @since 7.0
	 */
	protected function check_install_limit( array $rows, string $label ): ?WP_Error {
		if ( count( $rows ) < static::MAX_INSTALLS || in_array( $label, array_column( $rows, 'label' ), true ) ) {
			return null;
		}

		return new WP_Error(
			'font_entry_install_limit',
			sprintf(
				/* translators: %d: the maximum number of installs */
				__( 'A font can be installed under at most %d names. Remove one before adding another.', 'gravity-pdf' ),
				static::MAX_INSTALLS
			),
			[ 'status' => 400 ]
		);
	}

	/**
	 * The status objects for the entries named, in the shape `GET /fonts/status` returns
	 *
	 * A POST answers with the same map the poller reads, so the store merges the response instead of unpacking it.
	 * The map is passed in rather than fetched: it is an uncached scan, and a route that has already built one has
	 * no reason to build a second.
	 *
	 * @param string[] $ids
	 *
	 * @since 7.0
	 */
	protected function statuses_for( array $statuses, array $ids ): object {
		return (object) array_intersect_key( $statuses, array_flip( $ids ) );
	}

	/**
	 * @since 7.0
	 */
	protected function entry_id( array $row ): string {
		return $row['source'] . '/' . $row['entry'];
	}

	/**
	 * @since 7.0
	 */
	protected function is_coverage( array $row ): bool {
		return (int) $row['coverage'] === 1;
	}
}
