<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF_Vendor\Psr\Log\LoggerInterface;
use WP_Error;

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
 * Turns a catalog entry into font rows and files on disk
 *
 * The one install path, whatever the source: language packs, catalogue families, the background queue and the
 * render-time single-file fetch all arrive here. Nothing branches on the source id — an entry is an entry, and the
 * only thing that varies is whether it carries coverage maps.
 *
 * Installed state is the font tables and nothing else (§4.3 Stored state). Install *progress* is the status columns
 * on the catalog row, which this writes through `Catalog_Repository::set_status()` one row at a time, so the
 * background installer and a request-time trigger never read-modify-write a shared record.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Installer {

	/**
	 * How long a failed entry is left alone before the hourly retry re-enqueues it
	 *
	 * @since 7.0
	 */
	public const RETRY_AFTER = 6 * HOUR_IN_SECONDS;

	/**
	 * What the backoff becomes once an entry has failed more than once
	 *
	 * A host with no egress at all fails every entry every time; without an escalation it would re-batch four
	 * times a day forever.
	 *
	 * @since 7.0
	 */
	public const RETRY_AFTER_MAX = 24 * HOUR_IN_SECONDS;

	/**
	 * The widest the retry is scattered by, either side of the backoff
	 *
	 * @since 7.0
	 */
	public const RETRY_JITTER = HOUR_IN_SECONDS;

	/**
	 * Covers one file's recheck → fetch → rename → upsert, which `gfpdf_font_download_timeout` already bounds
	 *
	 * @since 7.0
	 */
	public const LOCK_TTL = 60;

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Font_Downloader
	 * @since 7.0
	 */
	protected $downloader;

	/**
	 * @var Font_Lock
	 * @since 7.0
	 */
	protected $lock;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @var array<string, array> Entry files fetched this request, keyed by the hash of their contents
	 * @since 7.0
	 */
	protected $fetched = [];

	public function __construct(
		Font_Repository $repository,
		Catalog_Repository $catalog,
		Font_Downloader $downloader,
		Font_Lock $lock,
		LoggerInterface $log
	) {
		$this->repository = $repository;
		$this->catalog    = $catalog;
		$this->downloader = $downloader;
		$this->lock       = $lock;
		$this->log        = $log;
	}

	/**
	 * Install one entry, or the named files of it
	 *
	 * @param string $id        `{source}/{entry}`
	 * @param array  $filenames The files to fetch; empty means every file the entry's roles resolve to
	 * @param array  $install   The row being written — `{ font_key?, label?, variants? }`, already validated by the
	 *                          route. Empty is the entry's default row
	 *
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function install( string $id, array $filenames = [], array $install = [] ) {
		[ $source, $entry ] = $this->split( $id );

		$resolved = $this->resolve( $source, $entry );
		if ( is_wp_error( $resolved ) ) {
			return $this->fail( $source, $entry, $resolved );
		}

		$targets = $this->targets( $resolved['data'], $install );

		if ( $filenames !== [] ) {
			$targets = array_intersect_key( $targets, array_flip( $filenames ) );
		}

		if ( $targets === [] ) {
			return $this->fail( $source, $entry, new WP_Error( 'font_file_unknown', sprintf( 'No file of %s matches the request', $id ) ) );
		}

		foreach ( array_keys( $targets ) as $name ) {
			$result = $this->install_locked( $resolved, (string) $name, $install );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Every filename an install of this entry resolves to
	 *
	 * What a caller with only an entry id — the hourly retry, a route — needs to enqueue per-file work, without
	 * teaching it how `variants` and `fonts` resolve to files.
	 *
	 * @return string[]|WP_Error
	 *
	 * @since 7.0
	 */
	public function files_for( string $id, array $install = [] ) {
		[ $source, $entry ] = $this->split( $id );

		$resolved = $this->resolve( $source, $entry );

		if ( is_wp_error( $resolved ) ) {
			return $resolved;
		}

		return array_map( 'strval', array_keys( $this->targets( $resolved['data'], $install ) ) );
	}

	/**
	 * Install one file of one entry, rows included
	 *
	 * The queue's unit of work. It carries only `{ source, entry, name, install? }` and everything else —
	 * URL, hash, size, remote path — is re-resolved from the catalog row here, at execution: that keeps "every URL
	 * is built from a registered root" true at fetch time, lets a root change take effect immediately, makes a
	 * poisoned batch row inert and lets a re-synced entry self-heal.
	 *
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function install_file( string $source, string $entry, string $name, array $install = [] ) {
		$resolved = $this->resolve( $source, $entry );

		if ( is_wp_error( $resolved ) ) {
			return $this->fail( $source, $entry, $resolved );
		}

		return $this->install_locked( $resolved, $name, $install );
	}

	/**
	 * Place and record one already-resolved file, alone in its entry
	 *
	 * Split from `install_file()` so `install()` resolves the entry once for the whole batch instead of once per
	 * file — a source that points at its entry files rather than inlining them, which is the reason the pointer
	 * form exists, otherwise pays a fresh HTTPS round trip for each. The resolve is deliberately *not* cached
	 * beyond one call: this object is a container singleton, so a memo would still be serving the old entry after
	 * a sync updated the row.
	 *
	 * The lock stays per file, sized for one file's recheck → fetch → rename → upsert, rather than being held
	 * across a whole pack's downloads and expiring mid-install.
	 *
	 * Phase is written here rather than around the loop, because this — not `install()` — is what the queue runs:
	 * a background install would otherwise never report `installing` and never clear an earlier `failed`. Marking
	 * each file also keeps `phase_since` moving, which is what stops `Catalog_Repository::claim()`'s staleness arm
	 * re-claiming a pack that is genuinely still downloading.
	 *
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	protected function install_locked( array $resolved, string $name, array $install ) {
		$source = (string) $resolved['row']['source'];
		$entry  = (string) $resolved['row']['entry'];
		$lock   = sprintf( 'entry_%s_%s', $source, $entry );

		if ( ! $this->lock->acquire( $lock, static::LOCK_TTL ) ) {
			return new WP_Error( 'font_install_in_progress', sprintf( 'Another request is installing %s/%s', $source, $entry ) );
		}

		try {
			$this->catalog->set_status( $source, $entry, [ 'phase' => 'installing' ] );

			$targets = $this->targets( $resolved['data'], $install );

			if ( ! isset( $targets[ $name ] ) ) {
				return $this->fail( $source, $entry, new WP_Error( 'font_file_unknown', sprintf( '%s is not a file %s/%s installs', $name, $source, $entry ) ) );
			}

			$file = (array) $resolved['data']['files'][ $name ];
			$path = $this->place( $source, $entry, $name, $file );

			if ( is_wp_error( $path ) ) {
				return $this->fail( $source, $entry, $path );
			}

			$this->write_rows( $resolved, $install, $targets[ $name ], $path, $file );

			if ( $this->install_complete( $resolved, $install, array_keys( $targets ) ) ) {
				$this->catalog->set_status(
					$source,
					$entry,
					[
						'phase'           => null,
						'error'           => null,
						'retry_after'     => null,
						'missing_scripts' => null,
						'missing_since'   => null,
					]
				);
			}

			return true;
		} finally {
			$this->lock->release( $lock );
		}
	}

	/**
	 * Whether every file this install wanted now has a row
	 *
	 * The phase belongs to the entry but the work arrives one file at a time, so the last file to land is the one
	 * that clears it — and which file that is depends on the order the queue happened to run them in.
	 *
	 * @param string[] $names Every filename this install resolves to
	 *
	 * @since 7.0
	 */
	protected function install_complete( array $resolved, array $install, array $names ): bool {
		$source  = (string) $resolved['row']['source'];
		$entry   = (string) $resolved['row']['entry'];
		$claimed = $this->repository->claimed_filenames();

		foreach ( $names as $name ) {
			if ( ! isset( $claimed[ Font_Sources::install_path( $source, $entry, (string) $name ) ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The catalog row plus its decoded entry object
	 *
	 * The entry is the row's `entry_json` when the index inlined it, else the one ~1 KB fetch of the entry file the
	 * row's `entry_sha256` names — this is the only code that fetches an entry file. **Either way it is validated
	 * before a filename or a key is used**: the signed root proves provenance, not content safety, and a
	 * third-party source never went through our pipeline at all.
	 *
	 * @return array{row: array, data: array}|WP_Error
	 *
	 * @since 7.0
	 */
	protected function resolve( string $source, string $entry ) {
		$row = $this->catalog->entry( $source, $entry );

		if ( $row === null ) {
			return new WP_Error( 'font_entry_unknown', sprintf( '%s/%s is not in the catalogue', $source, $entry ) );
		}

		$data = $row['data'];

		if ( ! is_array( $data ) ) {
			$data = $this->fetch_entry( $source, $row );

			if ( is_wp_error( $data ) ) {
				return $data;
			}
		}

		$invalid = Font_Sources::validate_entry( $data );

		if ( $invalid !== null ) {
			return new WP_Error( 'font_invalid_entry', sprintf( 'Entry %s/%s: %s', $source, $entry, $invalid ) );
		}

		return [
			'row'  => $row,
			'data' => $data,
		];
	}

	/**
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	protected function fetch_entry( string $source, array $row ) {
		$sha256 = (string) ( $row['entry_sha256'] ?? '' );

		if ( $sha256 === '' ) {
			return new WP_Error( 'font_entry_unknown', sprintf( '%s/%s carries neither an entry nor a hash to fetch one by', $source, $row['entry'] ) );
		}

		/*
		 * Keyed by the hash, not by the entry id, which is what makes this safe to hold for the whole request:
		 * a sync that changes an entry changes its hash and therefore the key, so a memo can never answer with a
		 * superseded entry. Without it a five-file family queued as five items pays five round trips for one
		 * unchanged ~1 KB document.
		 */
		if ( isset( $this->fetched[ $sha256 ] ) ) {
			return $this->fetched[ $sha256 ];
		}

		$url = $this->catalog->entry_url( $source, $row );

		if ( $url === null ) {
			return new WP_Error( 'font_source_unknown', sprintf( '%s is not a registered source', $source ) );
		}

		$body = $this->downloader->fetch(
			$url,
			[
				'sha256'       => $sha256,
				'request_args' => $this->catalog->request_args( $source ),
			]
		);

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'font_invalid_entry', sprintf( 'The entry file for %s/%s is not readable', $source, $row['entry'] ) );
		}

		$this->fetched[ $sha256 ] = $data;

		return $data;
	}

	/**
	 * Filename → the font keys and roles that file fills
	 *
	 * A role listed in `$install['variants']` resolves through the entry's `variants` map; every other role
	 * resolves through `fonts`. Roles that land on the same file share it on disk, which is why the map is keyed by
	 * filename rather than by role — nothing is downloaded twice.
	 *
	 * @return array<string, array<string, array<string, string>>> `{ filename: { font_key: { role: variant|'' } } }`
	 *
	 * @since 7.0
	 */
	protected function targets( array $data, array $install ): array {
		$variants = (array) ( $data['variants'] ?? [] );
		$chosen   = (array) ( $install['variants'] ?? [] );
		$files    = (array) ( $data['files'] ?? [] );
		$targets  = [];

		foreach ( (array) ( $data['fonts'] ?? [] ) as $font_key => $roles ) {
			foreach ( (array) $roles as $role => $filename ) {
				if ( in_array( $role, Font_Sources::NON_ROLE_KEYS, true ) ) {
					continue;
				}

				$variant = (string) ( $chosen[ $role ] ?? '' );
				$name    = $variant !== '' ? (string) ( $variants[ $variant ] ?? '' ) : (string) $filename;

				if ( $name === '' || ! isset( $files[ $name ] ) ) {
					continue;
				}

				$targets[ $name ][ (string) $font_key ][ (string) $role ] = $variant;
			}
		}

		return $targets;
	}

	/**
	 * Get one file onto disk at `{source}/{entry}/{name}` and hand back its path relative to the fonts directory
	 *
	 * Source installs are namespaced, so a download can never overwrite a user upload or an import: those stay flat
	 * in the fonts-dir root, and a directory cannot clash with a `.ttf`.
	 *
	 * @return string|WP_Error
	 *
	 * @since 7.0
	 */
	protected function place( string $source, string $entry, string $name, array $file ) {
		$relative = Font_Sources::install_path( $source, $entry, $name );
		$absolute = $this->repository->get_font_dir() . $relative;
		$sha256   = (string) ( $file['sha256'] ?? '' );
		$size     = (int) ( $file['size'] ?? 0 );

		if ( $this->already_installed( $relative, $absolute, $sha256, $size ) ) {
			return $relative;
		}

		$url = $this->catalog->url_for( $source, $file );

		if ( $url === null ) {
			return new WP_Error( 'font_source_unknown', sprintf( 'No URL could be built for %s', $relative ) );
		}

		$part = $this->downloader->download(
			$url,
			[
				'sha256'       => $sha256,
				'size'         => $size,
				'name'         => $name,
				'request_args' => $this->catalog->request_args( $source ),
			]
		);

		if ( is_wp_error( $part ) ) {
			return $part;
		}

		if ( ! wp_mkdir_p( dirname( $absolute ) ) ) {
			return new WP_Error( 'font_dir_unwritable', sprintf( 'The directory for %s could not be created', $relative ) );
		}

		/* Same filesystem as `.tmp/`, so this is atomic: a reader sees the old file or the new one, never a partial */
		/* phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- WP_Filesystem::move() copies and unlinks, which is exactly the non-atomic behaviour this avoids */
		if ( ! rename( $part, $absolute ) ) {
			return new WP_Error( 'font_rename_failed', sprintf( '%s could not be moved into place', $relative ) );
		}

		return $relative;
	}

	/**
	 * Whether the download can be skipped altogether
	 *
	 * Two cases, cheapest first. A file row already recording this path and hash, with the right size on disk, is
	 * the ordinary one: rows of an entry share files, so a second install of a variant the first already holds
	 * downloads nothing. Failing that, a file on disk that hashes correctly is a crash between a previous
	 * `rename()` and its row upsert — the hash is worth paying there because the alternative is re-fetching 17 MB.
	 *
	 * @since 7.0
	 */
	protected function already_installed( string $relative, string $absolute, string $sha256, int $size ): bool {
		if ( $sha256 === '' || ! is_file( $absolute ) || (int) filesize( $absolute ) !== $size ) {
			return false;
		}

		if ( $this->repository->recorded_hash( $relative ) === $sha256 ) {
			return true;
		}

		return hash_equals( $sha256, (string) hash_file( 'sha256', $absolute ) );
	}

	/**
	 * Upsert the font row for each key this file serves, then its file row for each role
	 *
	 * One write, no branch on the source. An entry update rewrites `meta`, `version` and the OTL flags in the same
	 * upsert as the file rows it replaces.
	 *
	 * @param array $targets `{ font_key: { role: variant } }` for this one file
	 *
	 * @since 7.0
	 */
	protected function write_rows( array $resolved, array $install, array $targets, string $path, array $file ): void {
		foreach ( $targets as $font_key => $roles ) {
			$font = $this->upsert_font( $resolved, $install, (string) $font_key );

			if ( $font['id'] === 0 ) {
				continue;
			}

			$replaced_a_file = false;

			foreach ( $roles as $role => $variant ) {
				$replaced = $this->repository->path_for_role( $font['id'], (string) $role );

				$this->repository->insert_file(
					$font['id'],
					(string) $role,
					[
						'path'    => $path,
						'sha256'  => (string) ( $file['sha256'] ?? '' ),
						'size'    => (int) ( $file['size'] ?? 0 ),
						'variant' => $variant !== '' ? $variant : null,
						'missing' => 0,
					]
				);

				if ( $replaced === null || $replaced === $path ) {
					continue;
				}

				/* The file this role no longer references, gone unless another row still records it */
				$this->repository->delete_file( $replaced );

				$replaced_a_file = true;
			}

			if ( $replaced_a_file ) {
				/*
				 * A variants swap can put a different file of the same size behind a role, and mPDF's size +
				 * `useOTL` comparison cannot see that. Only this branch needs it: an ordinary install has no cache
				 * yet, and an update whose bytes changed almost always changed length too. Once per key rather
				 * than per role, because the four faces of a key share one call.
				 */
				FlushCache::flush_font( $font['key'] );
			}
		}
	}

	/**
	 * The font row for one key of one entry, created or brought up to date
	 *
	 * @return array{id: int, key: string} The row id — 0 when it could not be written — and the key it holds
	 *
	 * @since 7.0
	 */
	protected function upsert_font( array $resolved, array $install, string $font_key ): array {
		$row  = $resolved['row'];
		$data = $resolved['data'];

		$coverage = (int) $row['coverage'] === 1;

		/* A bundled key is not a pack's to take: the pack's own row would shadow a font that ships with the plugin */
		if ( $coverage && $this->repository->is_key_reserved( $font_key ) ) {
			$this->log->warning(
				'Refusing to install a coverage font under a reserved key',
				[
					'entry' => $row['source'] . '/' . $row['entry'],
					'key'   => $font_key,
				]
			);

			return [
				'id'  => 0,
				'key' => '',
			];
		}

		$font  = Font_Sources::font_row( $row, $data, $font_key );
		$label = (string) ( $install['label'] ?? '' );

		if ( $label !== '' ) {
			$font['label'] = $label;
		}

		/*
		 * A coverage entry's rows *are* its `fonts` keys — the fallback maps in `meta` reference them by name, so
		 * they are not the caller's to choose, and the key is the identity. A display entry may be installed under
		 * more than one key, each its own row, so there the identity is the label and the key follows from it.
		 */
		$existing = $coverage ? $this->repository->get( $font_key ) : $this->existing_install( $row, $font['label'] );

		if ( ! $coverage ) {
			$font['font_key'] = $existing !== null ? (string) $existing['font_key'] : $this->new_key( $row, $install );
		}

		if ( $existing !== null ) {
			$this->repository->update( (int) $existing['id'], $font );

			return [
				'id'  => (int) $existing['id'],
				'key' => (string) $font['font_key'],
			];
		}

		return [
			'id'  => $this->repository->insert( $font ),
			'key' => (string) $font['font_key'],
		];
	}

	/**
	 * This install's existing row, matched on `(source, entry, label)`
	 *
	 * The key cannot be the identity of a display entry's row — a re-install would find its own key taken and
	 * suffix a duplicate every time. The label is what the caller actually chose, which makes a re-POST an update
	 * (§4.5 Variants), and it is the label the row was written with, so the two derivations cannot drift.
	 *
	 * @since 7.0
	 */
	protected function existing_install( array $row, string $label ): ?array {
		foreach ( $this->repository->all() as $font ) {
			if ( (string) $font['source'] === (string) $row['source']
				&& (string) ( $font['entry'] ?? '' ) === (string) $row['entry']
				&& (string) $font['label'] === $label
			) {
				return $font;
			}
		}

		return null;
	}

	/**
	 * The key a first install of a display entry takes
	 *
	 * The entry id by default — stable, matches the catalogue and survives a label edit — or the route's key,
	 * derived from the label by the custom-font rule. A key held by anything else is suffixed rather than refused,
	 * so an install never fails on a name (§4.5 Variants).
	 *
	 * @since 7.0
	 */
	protected function new_key( array $row, array $install ): string {
		$key = (string) ( $install['font_key'] ?? '' );

		return $this->repository->unique_key( $key !== '' ? $key : (string) $row['entry'] );
	}

	/**
	 * Record a failure on the entry's catalog row and hand the error back
	 *
	 * `retry_after` is what keeps a broken entry from being re-claimed on every trigger; the hourly retry picks it
	 * up once the backoff has passed.
	 *
	 * @since 7.0
	 */
	protected function fail( string $source, string $entry, WP_Error $error ): WP_Error {
		$this->log->error(
			'Font install failed',
			[
				'entry' => $source . '/' . $entry,
				'code'  => $error->get_error_code(),
				'error' => $error->get_error_message(),
			]
		);

		$this->catalog->set_status(
			$source,
			$entry,
			[
				'phase'       => 'failed',
				'error'       => $error->get_error_code(),
				'retry_after' => $this->retry_at( $source, $entry ),
			]
		);

		return $error;
	}

	/**
	 * When this entry may be re-claimed after a failure
	 *
	 * A row that still carries a `retry_after` has failed before — `claim()` leaves it in place precisely so this
	 * can tell the two apart without a counter column — and escalates. The jitter is what keeps a fleet of sites
	 * that failed together during an origin outage from retrying in lock-step and re-creating the outage.
	 *
	 * @since 7.0
	 */
	protected function retry_at( string $source, string $entry ): string {
		$row      = $this->catalog->entry( $source, $entry );
		$repeated = $row !== null && ( $row['retry_after'] ?? null ) !== null;
		$backoff  = $repeated ? static::RETRY_AFTER_MAX : static::RETRY_AFTER;

		return gmdate( 'Y-m-d H:i:s', time() + $backoff + wp_rand( 0, static::RETRY_JITTER ) );
	}

	/**
	 * @return array{0: string, 1: string}
	 *
	 * @since 7.0
	 */
	protected function split( string $id ): array {
		$parts = explode( '/', $id, 2 );

		return [ $parts[0], $parts[1] ?? '' ];
	}
}
