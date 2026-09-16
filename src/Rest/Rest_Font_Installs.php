<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Catalog_Repository;
use GFPDF\Fonts\Font_Package_Importer;
use GFPDF\Fonts\Install_Requests;
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
 * background queue to install next. Nothing on these routes reaches the network — an install is queued, never
 * performed in-request, so a pack of seventeen files cannot time out an admin screen. The offline import is no
 * exception: it writes files an upload already carried, and then queues the same install.
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
	 * @var Install_Requests
	 * @since 7.0
	 */
	protected $requests;

	/**
	 * @var Install_Queue
	 * @since 7.0
	 */
	protected $queue;

	/**
	 * @var Font_Package_Importer
	 * @since 7.0
	 */
	protected $importer;

	public function __construct(
		Registry $registry,
		Catalog_Repository $catalog,
		Font_Repository $repository,
		Install_Requests $requests,
		Install_Queue $queue,
		Font_Sources $sources,
		Font_Package_Importer $importer,
		Helper_Abstract_Form $gform
	) {
		$this->registry   = $registry;
		$this->catalog    = $catalog;
		$this->repository = $repository;
		$this->requests   = $requests;
		$this->queue      = $queue;
		$this->sources    = $sources;
		$this->importer   = $importer;
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
			'/fonts/import',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'import_package' ],
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
		/* The poller, and the only caller that should re-dispatch a batch nothing has picked up */
		return rest_ensure_response( (object) $this->registry->get_install_statuses( $this->queue, true ) );
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

		return $this->queued( $row, $installs );
	}

	/**
	 * Install a catalogue entry from an offline package instead of from the store
	 *
	 * The import writes the files and nothing else; the install that follows is the one every other trigger runs,
	 * so it answers in the same status map the Font Manager already polls. Every file is on disk and verified by
	 * the time the queue reaches it, so the background pass downloads nothing — which is the point, on a site that
	 * has an archive precisely because it cannot download.
	 *
	 * An entry the site already holds is an update: `on_record()` rewrites the installs it has, exactly as a
	 * re-POST of the entry route would.
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 7.0
	 */
	public function import_package( $request ) {
		$archive = $this->uploaded_archive( $request );

		if ( is_wp_error( $archive ) ) {
			return $archive;
		}

		$row = $this->importer->import( $archive );

		if ( is_wp_error( $row ) ) {
			return $row;
		}

		return $this->queued( $row, $this->requests->on_record( $row ) );
	}

	/**
	 * The uploaded zip's path on this server, or why there is nothing to import
	 *
	 * Read where PHP left it rather than moved somewhere first: the importer only ever reads it, and every byte it
	 * writes is checked against the catalogue, so a second copy of an archive that may be 6 MB would buy nothing.
	 *
	 * @return string|WP_Error
	 *
	 * @since 7.0
	 */
	protected function uploaded_archive( $request ) {
		$file  = (array) ( $request->get_file_params()['file'] ?? [] );
		$error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );

		/*
		 * The host's own cap, and the one failure with advice attached: nine of the seventeen packs are over 2 MB
		 * and the largest is 6 MB, so a low `upload_max_filesize` is the likeliest reason an import never starts.
		 */
		if ( $error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE ) {
			return new WP_Error(
				'font_package_too_large',
				esc_html__( 'The font package is larger than this server accepts as an upload. Ask your host to raise the upload limit, or copy the font files into the fonts directory over FTP instead.', 'gravity-pdf' ),
				[ 'status' => 413 ]
			);
		}

		if ( $error !== UPLOAD_ERR_OK || ! is_file( (string) ( $file['tmp_name'] ?? '' ) ) ) {
			return new WP_Error(
				'font_package_missing',
				esc_html__( 'No font package was uploaded.', 'gravity-pdf' ),
				[ 'status' => 400 ]
			);
		}

		return (string) $file['tmp_name'];
	}

	/**
	 * Hand a set of installs to the queue and answer with what the entry is now doing
	 *
	 * Shared by the entry route and the import because the two differ only in where the bytes came from: past this
	 * point an import is an install of an entry whose files happen to be on disk already.
	 *
	 * @param array[] $installs
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 7.0
	 */
	protected function queued( array $row, array $installs ) {
		$queued = $this->requests->queue( $row, $installs );

		if ( is_wp_error( $queued ) ) {
			return $queued;
		}

		$statuses = $this->registry->get_install_statuses( $this->queue );
		$id       = Install_Requests::entry_id( $row );

		/*
		 * The claim was lost. For a language pack that is the outcome the caller wanted — something else is already
		 * installing it — so the status says so and the poller takes over. A family is different: the install in
		 * flight may be a different one, under a different key, and would silently swallow this request's own.
		 */
		if ( ! $queued && ! Install_Requests::is_coverage( $row ) && in_array( (string) $statuses[ $id ]['phase'], Catalog_Repository::LIVE_PHASES, true ) ) {
			return new WP_Error(
				'font_install_in_progress',
				__( 'This font is already being installed. Try again once it has finished.', 'gravity-pdf' ),
				[ 'status' => 409 ]
			);
		}

		return new WP_REST_Response( $this->statuses_for( $statuses, [ $id ] ), 202 );
	}

	/**
	 * The capability check, plus the multisite one for unlinking a pack's files
	 *
	 * A coverage entry is never owned by one site, so there is no row to look at: on multisite its files are
	 * always the network's to remove.
	 *
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function delete_item_permissions_check( $request ) {
		$allowed = parent::delete_item_permissions_check( $request );

		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		return $this->refuse_file_delete() ?? true;
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

		$id      = Install_Requests::entry_id( $row );
		$removed = $this->requests->remove( $id );

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
	 * `Install_Requests::queue()` the entry route uses, so there is genuinely no second update path. One unreachable entry
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

			if ( $this->requests->queue( $row, $this->requests->on_record( $row ) ) === true ) {
				$queued[] = (string) $id;
			}
		}

		return new WP_REST_Response(
			$this->statuses_for( $this->registry->get_install_statuses( $this->queue ), $queued ),
			202
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

		if ( Install_Requests::is_coverage( $row ) ) {
			if ( $chosen ) {
				return new WP_Error(
					'font_entry_owns_its_fonts',
					__( 'A language pack names its own fonts, so it cannot be installed under a chosen name or set of styles.', 'gravity-pdf' ),
					[ 'status' => 400 ]
				);
			}

			return [ [] ];
		}

		$error = $this->requests->check_variants( $row, $variants );

		if ( $error !== null ) {
			return $error;
		}

		if ( ! $chosen ) {
			return $this->requests->on_record( $row );
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
}
