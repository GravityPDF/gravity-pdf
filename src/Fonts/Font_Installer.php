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
	 * @var Font_Sources
	 * @since 7.0
	 */
	protected $sources;

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

	public function __construct(
		Font_Repository $repository,
		Catalog_Repository $catalog,
		Font_Sources $sources,
		Font_Downloader $downloader,
		Font_Lock $lock,
		LoggerInterface $log
	) {
		$this->repository = $repository;
		$this->catalog    = $catalog;
		$this->sources    = $sources;
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

		$this->catalog->set_status( $source, $entry, [ 'phase' => 'installing' ] );

		foreach ( array_keys( $targets ) as $name ) {
			$result = $this->install_file( $source, $entry, (string) $name, $install );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

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

		return true;
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
		$lock = sprintf( 'entry_%s_%s', $source, $entry );

		if ( ! $this->lock->acquire( $lock, static::LOCK_TTL ) ) {
			return new WP_Error( 'font_install_in_progress', sprintf( 'Another request is installing %s/%s', $source, $entry ) );
		}

		try {
			$resolved = $this->resolve( $source, $entry );
			if ( is_wp_error( $resolved ) ) {
				return $this->fail( $source, $entry, $resolved );
			}

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

			return true;
		} finally {
			$this->lock->release( $lock );
		}
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
		$record = $this->sources->get( $source );
		$sha256 = (string) ( $row['entry_sha256'] ?? '' );

		if ( $record === null ) {
			return new WP_Error( 'font_source_unknown', sprintf( '%s is not a registered source', $source ) );
		}

		if ( $sha256 === '' ) {
			return new WP_Error( 'font_entry_unknown', sprintf( '%s/%s carries neither an entry nor a hash to fetch one by', $source, $row['entry'] ) );
		}

		$body = $this->downloader->fetch(
			sprintf( '%sentries/%s/%s-%s.json', $record->get_root_url(), $source, $row['entry'], $sha256 ),
			[
				'sha256'       => $sha256,
				'request_args' => $record->get_request_args(),
			]
		);

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$data = json_decode( $body, true );

		return is_array( $data ) ? $data : new WP_Error( 'font_invalid_entry', sprintf( 'The entry file for %s/%s is not readable', $source, $row['entry'] ) );
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
		$relative = $source . '/' . $entry . '/' . $name;
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
				'sha256' => $sha256,
				'size'   => $size,
				'name'   => $name,
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

		if ( ( $this->installed_hashes()[ $relative ] ?? '' ) === $sha256 ) {
			return true;
		}

		return hash_equals( $sha256, (string) hash_file( 'sha256', $absolute ) );
	}

	/**
	 * Every path a file row records, with the hash it claims
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	protected function installed_hashes(): array {
		$hashes = [];

		foreach ( $this->repository->all() as $font ) {
			foreach ( $font['files'] as $file ) {
				$hashes[ (string) $file['path'] ] = (string) ( $file['sha256'] ?? '' );
			}
		}

		return $hashes;
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
			$font_id = $this->upsert_font( $resolved, $install, (string) $font_key );

			if ( $font_id === 0 ) {
				continue;
			}

			foreach ( $roles as $role => $variant ) {
				$replaced = $this->path_for_role( $font_id, (string) $role );

				$this->repository->insert_file(
					$font_id,
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

				/*
				 * A variants swap can put a different file of the same size behind a role, and mPDF's size +
				 * `useOTL` comparison cannot see that. Only this branch needs it: an ordinary install has no cache
				 * yet, and an update whose bytes changed almost always changed length too.
				 */
				FlushCache::flush_font( $this->key_for_id( $font_id ) );
			}
		}
	}

	/**
	 * The path a font row's role currently records, before this install replaces it
	 *
	 * @since 7.0
	 */
	protected function path_for_role( int $font_id, string $role ): ?string {
		foreach ( $this->repository->all() as $font ) {
			if ( (int) $font['id'] === $font_id ) {
				return isset( $font['files'][ $role ] ) ? (string) $font['files'][ $role ]['path'] : null;
			}
		}

		return null;
	}

	/**
	 * @since 7.0
	 */
	protected function key_for_id( int $font_id ): string {
		foreach ( $this->repository->all() as $key => $font ) {
			if ( (int) $font['id'] === $font_id ) {
				return (string) $key;
			}
		}

		return '';
	}

	/**
	 * The font row for one key of one entry, created or brought up to date
	 *
	 * @return int The row id, or 0 when it could not be written
	 *
	 * @since 7.0
	 */
	protected function upsert_font( array $resolved, array $install, string $font_key ): int {
		$row   = $resolved['row'];
		$data  = $resolved['data'];
		$roles = (array) ( $data['fonts'][ $font_key ] ?? [] );

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

			return 0;
		}

		$existing = $coverage ? $this->repository->get( $font_key ) : $this->existing_install( $row, $install );

		$font = [
			'font_key'    => $this->row_key( $row, $install, $font_key, $coverage, $existing ),
			'label'       => $this->row_label( $row, $data, $install, $font_key ),
			'source'      => (string) $row['source'],
			'entry'       => (string) $row['entry'],
			'coverage'    => (int) $row['coverage'],
			'meta'        => (int) $row['coverage'] === 1 ? Font_Sources::coverage_meta( $font_key, $data, $roles ) : [],
			'version'     => $row['version'] ?? null,
			'use_otl'     => (int) ( $roles['useOTL'] ?? 0 ),
			'use_kashida' => (int) ( $roles['useKashida'] ?? 0 ),
		];

		if ( $existing !== null ) {
			$this->repository->update( (int) $existing['id'], $font );

			return (int) $existing['id'];
		}

		return $this->repository->insert( $font );
	}

	/**
	 * The key this row takes
	 *
	 * A coverage entry's rows *are* its `fonts` keys — the fallback maps in `meta` reference them by name, so they
	 * are not the caller's to choose. A display entry keeps the key its existing row already has, or derives a new
	 * one.
	 *
	 * @since 7.0
	 */
	protected function row_key( array $row, array $install, string $font_key, bool $coverage, ?array $existing ): string {
		if ( $coverage ) {
			return $font_key;
		}

		return $existing !== null ? (string) $existing['font_key'] : $this->new_key( $row, $install );
	}

	/**
	 * This install's existing row, matched on `(source, entry, label)`
	 *
	 * A display entry may be installed under more than one key, each install its own row, so the key cannot be the
	 * identity — a re-install would find its own key taken and suffix a duplicate every time. The label is what the
	 * caller actually chose, which makes a re-POST an update (§4.5 Variants).
	 *
	 * @since 7.0
	 */
	protected function existing_install( array $row, array $install ): ?array {
		$label = (string) ( $install['label'] ?? '' );
		$label = $label !== '' ? $label : (string) $row['label'];

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
	 * @since 7.0
	 */
	protected function row_label( array $row, array $data, array $install, string $font_key ): string {
		$label = (string) ( $install['label'] ?? '' );

		if ( $label !== '' ) {
			return $label;
		}

		/* A multi-font pack labels each row by its key: one shared label across four CJK fonts helps nobody */
		return count( (array) ( $data['fonts'] ?? [] ) ) === 1 ? (string) $row['label'] : $font_key;
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
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() + static::RETRY_AFTER ),
			]
		);

		return $error;
	}

	/**
	 * @return array{0: string, 1: string}
	 *
	 * @since 7.0
	 */
	protected function split( string $id ): array {
		$parts = explode( '/', $id, 2 );

		return [ (string) ( $parts[0] ?? '' ), (string) ( $parts[1] ?? '' ) ];
	}
}
