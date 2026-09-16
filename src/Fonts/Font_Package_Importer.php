<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF_Vendor\Psr\Log\LoggerInterface;
use WP_Error;
use ZipArchive;

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
 * Turns an offline font package into files on disk, ready for the ordinary install
 *
 * The offline half of `Font_Downloader`: a site that cannot reach `fonts.gravitypdf.com` carries the bytes in by
 * hand, and everything after they land is the install path every other trigger uses. What it writes is
 * indistinguishable from a download, deliberately — the files go where `Font_Sources::install_path()` says, so the
 * queue that follows finds them already installed and does nothing but write rows.
 *
 * **The archive is never believed about itself.** One string is taken from inside it, `package.path`, and only as
 * a key to find a catalogue row with; every byte is then checked against that row's entry. A crafted archive
 * claiming to be a pack this site has synced installs exactly nothing, because its files hash to something the
 * catalogue does not list.
 *
 * That is also the whole of why this is the *synced* path only. A site that has never synced has no row to check
 * against, and the answer there is the zip's detached signature over its `entry.json` — a §10 follow-up, whose
 * artefact ships from the first publish so the feature is a plugin release rather than a republish (§9.31).
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Package_Importer {

	/**
	 * The archive member carrying the entry object, at the zip root beside the bare filenames
	 *
	 * @since 7.0
	 */
	public const MANIFEST = 'entry.json';

	/**
	 * The most of `entry.json` that is ever read
	 *
	 * The largest the pipeline builds is a few KB. It is capped because this is the one member no catalogue row
	 * vouches for a size of, so it is the only place a crafted archive could ask for gigabytes of memory.
	 *
	 * @since 7.0
	 */
	public const MANIFEST_MAX_BYTES = 262144;

	/**
	 * How long an extraction holds the entry's lock
	 *
	 * Longer than `Font_Installer::LOCK_TTL`, which is sized for one file's fetch and rename: this covers a whole
	 * pack — up to 10 MB across thirty members, each inflated, hashed and moved — and a TTL that expires mid-pack
	 * would let a second writer into the entry the lock exists to keep to one.
	 *
	 * @since 7.0
	 */
	public const EXTRACT_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Font_Installer
	 * @since 7.0
	 */
	protected $installer;

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

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
		Catalog_Repository $catalog,
		Font_Installer $installer,
		Font_Repository $repository,
		Font_Downloader $downloader,
		Font_Lock $lock,
		LoggerInterface $log
	) {
		$this->catalog    = $catalog;
		$this->installer  = $installer;
		$this->repository = $repository;
		$this->downloader = $downloader;
		$this->lock       = $lock;
		$this->log        = $log;
	}

	/**
	 * Extract one package, and say which catalogue entry it turned out to be
	 *
	 * The row rather than a status, because the caller queues the install: what lands here is files, and rows are
	 * the queue's to write through the same installer a download goes through.
	 *
	 * @param string $archive Absolute path to the uploaded zip
	 *
	 * @return array|WP_Error The catalog row the files belong to
	 *
	 * @since 7.0
	 */
	public function import( string $archive ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error(
				'font_package_unsupported',
				esc_html__( 'This server has no zip support, so a font package cannot be opened here. Copy the font files into the fonts directory over FTP instead.', 'gravity-pdf' ),
				[ 'status' => 501 ]
			);
		}

		$zip = new ZipArchive();

		/* `CHECKCONS` is what the WordPress unzipper asks for too: a truncated upload fails here rather than half way through */
		if ( $zip->open( $archive, ZipArchive::CHECKCONS ) !== true ) {
			return $this->invalid( esc_html__( 'That file could not be read as a zip archive.', 'gravity-pdf' ) );
		}

		try {
			return $this->extract_package( $zip );
		} finally {
			$zip->close();
		}
	}

	/**
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	protected function extract_package( ZipArchive $zip ) {
		$manifest = (string) $zip->getFromName( static::MANIFEST, static::MANIFEST_MAX_BYTES );
		$declared = $this->declared_package( $manifest );

		if ( is_wp_error( $declared ) ) {
			return $declared;
		}

		$row = $this->catalog->entry_for_package( $declared );

		if ( $row === null ) {
			return new WP_Error(
				'font_entry_unknown',
				esc_html__( 'The font catalogue does not list this package. Sync the catalogue and try again — a site that has never synced cannot verify an import yet.', 'gravity-pdf' ),
				[ 'status' => 404 ]
			);
		}

		$offered = $this->offer_manifest( $row, $manifest );

		if ( $offered !== null ) {
			return $offered;
		}

		$files = $this->installer->entry_files( Install_Requests::entry_id( $row ) );

		if ( is_wp_error( $files ) ) {
			$files->add_data( [ 'status' => 502 ], $files->get_error_code() );

			return $files;
		}

		$wrong = $this->check_members( $zip, $files );

		if ( $wrong !== null ) {
			return $wrong;
		}

		$placed = $this->place_all( $zip, $row, $files );

		return is_wp_error( $placed ) ? $placed : $row;
	}

	/**
	 * Hand the embedded entry to the installer when the row is a pointer rather than an entry
	 *
	 * Without this the import reaches for the network on the one path that exists because there is none: a display
	 * entry's row carries `entry_sha256` and no document, so resolving it would fetch the entry file — five
	 * seconds to connect and fifteen to read, then a failure, on an airgapped host. The archive embeds that file
	 * byte for byte (§4.3 Hosting), and the hash on the row is exactly what decides whether it is the same one.
	 *
	 * A coverage entry is inlined and needs none of this; its archive's `entry.json` is ignored entirely, which is
	 * what "the archive is never believed about itself" means for the 17 packs — there is no published entry file
	 * for them to be compared against.
	 *
	 * @since 7.0
	 */
	protected function offer_manifest( array $row, string $manifest ): ?WP_Error {
		if ( is_array( $row['data'] ) ) {
			return null;
		}

		if ( $this->installer->offer_entry( (string) ( $row['entry_sha256'] ?? '' ), $manifest ) ) {
			return null;
		}

		return $this->invalid( esc_html__( 'This package describes a different version of the font than the catalogue lists. Sync the catalogue and try again.', 'gravity-pdf' ) );
	}

	/**
	 * The name of the archive the package says it is
	 *
	 * The only thing read out of the archive, and it decides nothing on its own: it names a catalogue row, and the
	 * row decides what may be written. `basename()` rather than a path check because this is about to be compared
	 * against a published object name, which is one segment by construction (§4.3 Hosting).
	 *
	 * @return string|WP_Error
	 *
	 * @since 7.0
	 */
	protected function declared_package( string $manifest ) {
		$entry = json_decode( $manifest, true );
		$path  = is_array( $entry ) ? (string) ( $entry['package']['path'] ?? '' ) : '';

		if ( $path === '' || $path !== basename( $path ) ) {
			return $this->invalid( esc_html__( 'This zip does not name a font package, so there is no way to tell which font it holds.', 'gravity-pdf' ) );
		}

		return $path;
	}

	/**
	 * Refuse an archive whose members are not exactly the files the entry lists
	 *
	 * Both directions, in one walk, before a byte is written — so "refused whole rather than half installed" holds
	 * for a package missing its last file as much as for one carrying an extra, instead of the miss being found
	 * after twenty-nine files have been inflated, hashed and renamed into place.
	 *
	 * The extra-member half is also the whole of the traversal rule: the entry's filenames are bare, validated
	 * segments (`Font_Sources::validate_entry()`), so a member named `../evil.ttf` or `sub/dir/Noto.ttf` is not one
	 * of them and never reaches an extraction.
	 *
	 * @param array $files The entry's `files` map
	 *
	 * @since 7.0
	 */
	protected function check_members( ZipArchive $zip, array $files ): ?WP_Error {
		$members = [];

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = (string) ( $zip->statIndex( $i )['name'] ?? '' );

			if ( $name !== static::MANIFEST && ! isset( $files[ $name ] ) ) {
				return $this->invalid(
					sprintf(
						/* translators: %s: a file name inside the uploaded zip */
						esc_html__( 'The package holds "%s", which is not a file this font publishes.', 'gravity-pdf' ),
						$name
					)
				);
			}

			$members[ $name ] = true;
		}

		$missing = array_diff_key( $files, $members );

		if ( $missing === [] ) {
			return null;
		}

		return $this->invalid(
			sprintf(
				/* translators: %s: a font file name */
				esc_html__( 'The package does not carry %s, which this font needs.', 'gravity-pdf' ),
				(string) array_key_first( $missing )
			)
		);
	}

	/**
	 * Every file the entry lists, extracted, verified and moved into the entry's install directory
	 *
	 * All or nothing is not attempted, and does not need to be: a file that lands is byte-for-byte what the
	 * catalogue lists, so a half-extracted package is a pack with files outstanding — the same state a download
	 * interrupted mid-pack leaves, and the same queue finishes it.
	 *
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	protected function place_all( ZipArchive $zip, array $row, array $files ) {
		$source = (string) $row['source'];
		$entry  = (string) $row['entry'];
		$lock   = $this->lock->acquire_entry( $source, $entry, static::EXTRACT_TTL );

		if ( is_wp_error( $lock ) ) {
			return $lock;
		}

		try {
			foreach ( $files as $name => $expected ) {
				$placed = $this->place( $zip, (string) $name, Font_Sources::install_path( $source, $entry, (string) $name ), (array) $expected );

				if ( is_wp_error( $placed ) ) {
					return $placed;
				}
			}
		} finally {
			$this->lock->release( $lock );
		}

		$this->log->notice(
			'Imported a font package',
			[
				'entry' => $source . '/' . $entry,
				'files' => count( $files ),
			]
		);

		return true;
	}

	/**
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	protected function place( ZipArchive $zip, string $name, string $destination, array $expected ) {
		$part = $this->downloader->part_path( $name );

		if ( is_wp_error( $part ) ) {
			return $part;
		}

		try {
			$error = $this->write_part( $zip, $name, $part, (int) ( $expected['size'] ?? 0 ) )
				?? $this->verify( $name, $part, $expected );

			return $error ?? $this->repository->move_into_place( $part, $destination );
		} finally {
			/* A no-op once the move has renamed it away, so this only bites on a part some step refused */
			$this->downloader->unlink_part( $part );
		}
	}

	/**
	 * Stream one member into its `.part`, capped at one byte past what the entry declares
	 *
	 * The cap is the decompression-bomb guard, and it is the size check below that turns it into an error rather
	 * than a truncated font: a member that inflates to more than the catalogue lists stops at the cap and then
	 * fails on length.
	 *
	 * @since 7.0
	 */
	protected function write_part( ZipArchive $zip, string $name, string $part, int $size ): ?WP_Error {
		$member = $zip->getStream( $name );

		/* `check_members()` has already found the name, so reaching this means the member itself will not open */
		if ( $member === false ) {
			return $this->invalid(
				sprintf(
					/* translators: %s: a font file name */
					esc_html__( '%s could not be read out of the package.', 'gravity-pdf' ),
					$name
				)
			);
		}

		/* phpcs:disable WordPress.WP.AlternativeFunctions -- WP_Filesystem has no streaming read, and this member may be 10 MB */
		$out = fopen( $part, 'wb' );

		if ( $out === false ) {
			fclose( $member );

			return new WP_Error( 'font_tmp_unwritable', sprintf( 'The temp file for %s could not be opened', $name ), [ 'status' => 500 ] );
		}

		stream_copy_to_stream( $member, $out, $size + 1 );

		fclose( $member );
		fclose( $out );
		/* phpcs:enable WordPress.WP.AlternativeFunctions */

		return null;
	}

	/**
	 * Is this byte-for-byte the file the entry lists?
	 *
	 * The downloader's check, not a second one: the rule is the subsystem's security boundary and there is no
	 * version of it that should be true of a download and false of an extraction. Only the entry's *silence* is
	 * read differently — `verify_file()` has nothing to check when a file declares no hash or size, which is the
	 * right reading for a source that publishes one, and the wrong one for an archive an admin was handed.
	 *
	 * @since 7.0
	 */
	protected function verify( string $name, string $part, array $expected ): ?WP_Error {
		if ( ! isset( $expected['sha256'], $expected['size'] ) ) {
			return $this->mismatched( $name );
		}

		return $this->downloader->verify_file( $name, $part, $expected ) === null ? null : $this->mismatched( $name );
	}

	/**
	 * One message for a wrong length and a wrong hash alike
	 *
	 * An admin can act on neither differently, and the distinction is exactly the detail an attacker probing a
	 * crafted archive would like back.
	 *
	 * @since 7.0
	 */
	protected function mismatched( string $name ): WP_Error {
		$this->log->warning( 'A font package member is not what the catalogue lists', [ 'file' => $name ] );

		return $this->invalid(
			sprintf(
				/* translators: %s: a font file name */
				esc_html__( '%s is not the file the catalogue lists under that name, so the package was not installed.', 'gravity-pdf' ),
				$name
			)
		);
	}

	/**
	 * One code for every way an archive is not the package it claims to be, because a client renders one message
	 *
	 * @since 7.0
	 */
	protected function invalid( string $message ): WP_Error {
		return new WP_Error( 'font_package_invalid', $message, [ 'status' => 400 ] );
	}
}
