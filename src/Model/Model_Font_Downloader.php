<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Render_Health;
use GFPDF\Helper\Mpdf\On_Demand_Font_File_Finder;
use GFPDF\Statics\Git_Blob;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use WP_Error;
use WP_Site;

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
 * Downloads the font sources' files into the fonts folder, verifying each against its manifest
 *
 * @since 6.19.0
 */
class Model_Font_Downloader extends Helper_Abstract_Model {

	/**
	 * A font row's style fields, as keys to intersect a row with
	 *
	 * @since 6.19.0
	 */
	protected const FONT_STYLE_KEYS = [
		'regular'     => true,
		'italics'     => true,
		'bold'        => true,
		'bolditalics' => true,
	];


	/**
	 * Failed files awaiting their one retry, and those that gave up, which the admin notice lists. An option of the
	 * main network, as every site shares the fonts folder
	 *
	 * @since 6.19.0
	 */
	const FAILURES = 'gfpdf_core_font_failures';

	/**
	 * Prefix of the per-file site transient holding when a download last failed, which skips the network until it
	 * expires
	 *
	 * @since 6.19.0
	 */
	const BACK_OFF_KEY = 'gfpdf_core_font_backoff_';

	/**
	 * @since 6.19.0
	 */
	const BACK_OFF = 15 * MINUTE_IN_SECONDS;

	/**
	 * Upper bound of the random delay before the retry
	 *
	 * @since 6.19.0
	 */
	const RETRY_JITTER = 5 * MINUTE_IN_SECONDS;

	/**
	 * Cron event that downloads the required fonts and runs the retries that are due
	 *
	 * @since 6.19.0
	 */
	const SYNC_EVENT = 'gfpdf_core_font_sync';

	/**
	 * The files a paused sync run left for the next one
	 *
	 * @since 6.19.0
	 */
	const SYNC_REMAINING = 'gfpdf_core_font_sync_remaining';

	/**
	 * Seconds a sync run keeps starting downloads
	 *
	 * @since 6.19.0
	 */
	const SYNC_BUDGET = 20;

	/**
	 * Main network option holding the 6.19 cleanup's progress while it runs, then true once it's done
	 *
	 * @since 6.19.0
	 */
	const CLEANUP = 'gfpdf_core_font_cleanup';

	/**
	 * The default font of a site upgraded from before 6.19 with none saved
	 *
	 * @since 6.19.0
	 */
	const PRE_6_19_DEFAULT_FONT = 'dejavusanscondensed';

	/**
	 * Cron event that reads every site's fonts a batch at a time, then cleans the shared fonts folder
	 *
	 * @since 6.19.0
	 */
	const CLEANUP_EVENT = 'gfpdf_core_font_cleanup_run';

	/**
	 * Core fonts prune() removes even when needed, as the installer's copies are outdated (mpdf/mpdf#1440, GravityPDF/mpdf#881)
	 *
	 * @since 6.19.0
	 */
	const OUTDATED = [ 'Garuda.ttf', 'Garuda-Bold.ttf', 'Garuda-Oblique.ttf', 'Garuda-BoldOblique.ttf', 'Sun-ExtA.ttf', 'Sun-ExtB.ttf' ];

	/**
	 * Large files a PDF needed beyond the required set (e.g. Sun-ExtA on an English site), which the sync fetches and the
	 * cleanup keeps
	 *
	 * @since 6.19.0
	 */
	const WANTED = 'gfpdf_font_wanted';

	/**
	 * @var Model_Font_Sources
	 * @since 6.19.0
	 */
	protected $font_sources;

	/**
	 * @var Helper_Data
	 * @since 6.19.0
	 */
	protected $data;

	/**
	 * @var Helper_Abstract_Options
	 * @since 6.19.0
	 */
	protected $options;

	/**
	 * @var Helper_Misc
	 * @since 6.19.0
	 */
	protected $misc;

	/**
	 * @var LoggerInterface
	 * @since 6.19.0
	 */
	protected $log;

	/**
	 * When each failed file's retry is due (else 0), once the retry also failed when it gave up (else 0), and whether
	 * an admin dismissed the notice listing it. `conflict` is the size and modified time of another font's file at its
	 * name when it failed, else ''.
	 *
	 * @var array<string, array{retry_at: int, gave_up: int, dismissed: bool, conflict: string}>|null
	 * @since 6.19.0
	 */
	protected $failures;

	/**
	 * @var Model_Required_Fonts
	 * @since 6.19.0
	 */
	protected $required_fonts;

	/**
	 * @var int|float
	 * @since 6.19.0
	 */
	protected $sync_budget = self::SYNC_BUDGET;

	/**
	 * @var int
	 * @since 6.19.0
	 */
	protected $retry_jitter = self::RETRY_JITTER;

	/**
	 * Set while the sync runs, which schedules its own next run once it's done
	 *
	 * @var bool
	 * @since 6.19.0
	 */
	protected $syncing = false;

	/**
	 * Build the downloader from the font sources, which list the files it may fetch, and the required fonts it syncs
	 *
	 * @since 6.19.0
	 */
	public function __construct( Model_Font_Sources $font_sources, Helper_Data $data, Helper_Abstract_Options $options, Helper_Misc $misc, LoggerInterface $log, Model_Required_Fonts $required_fonts ) {
		$this->font_sources   = $font_sources;
		$this->data           = $data;
		$this->options        = $options;
		$this->misc           = $misc;
		$this->log            = $log;
		$this->required_fonts = $required_fonts;
	}

	/**
	 * An mPDF font finder that downloads missing fonts
	 *
	 * @param array $config The filtered mPDF config
	 *
	 * @since 6.19.0
	 */
	public function get_font_file_finder( array $config ): On_Demand_Font_File_Finder {
		$fontdata     = is_array( $config['fontdata'] ?? null ) ? $config['fontdata'] : [];
		$default_font = is_string( $config['default_font'] ?? null ) ? strtolower( $config['default_font'] ) : '';
		$default_file = $fontdata[ $default_font ]['R'] ?? '';
		$font_dirs    = array_values( array_filter( (array) ( $config['fontDir'] ?? [] ), 'is_string' ) );

		/* Debug Mode only, not Debug::is_enabled(): a staging site should still render with a fallback */
		$fallback = $this->options->get_option( 'debug_mode', 'No' ) !== 'Yes';

		return new On_Demand_Font_File_Finder( $font_dirs, $this, is_string( $default_file ) ? $default_file : '', $fallback );
	}

	/**
	 * @since 6.19.0
	 */
	public function get_own_copy( string $file ): string {
		return $this->font_sources->get_own_copy( $file );
	}

	/**
	 * The bundled Arimo Regular path, or '' (logged) if it's missing
	 *
	 * @since 6.19.0
	 */
	public function get_fallback_file(): string {
		$file = $this->font_sources->get_fallback_file();
		if ( ! is_file( $file ) ) {
			$this->log->error( 'The bundled fallback font is missing; reinstall Gravity PDF', [ 'file' => $file ] );

			return '';
		}

		return $file;
	}

	/**
	 * @since 6.19.0
	 */
	public function is_core_font_file( string $file ): bool {
		return $this->font_sources->is_core_font_file( $file );
	}

	/**
	 * @since 6.19.0
	 */
	public function get_manifest_file( string $name ): string {
		return $this->font_sources->get_manifest_file( $name );
	}

	/**
	 * Whether fonts may be downloaded, e.g. not on hosts that block outbound requests: the constant, else the filter
	 *
	 * @since 6.19.0
	 */
	protected function is_auto_install_enabled( string $file ): bool {
		$locked = $this->get_auto_install_constant();
		if ( $locked !== null ) {
			return $locked;
		}

		/**
		 * Filter whether a font may be downloaded. The GPDF_AUTO_INSTALL_FONTS constant overrides it.
		 *
		 * @param bool   $download Whether to download the file
		 * @param string $file     The file name
		 *
		 * @since 6.19.0
		 */
		return (bool) apply_filters( 'gfpdf_auto_install_fonts', true, $file );
	}

	/**
	 * GPDF_AUTO_INSTALL_FONTS as a bool, or null when it isn't defined
	 *
	 * @since 6.19.0
	 */
	protected function get_auto_install_constant(): ?bool {
		return defined( 'GPDF_AUTO_INSTALL_FONTS' ) ? (bool) constant( 'GPDF_AUTO_INSTALL_FONTS' ) : null;
	}

	/**
	 * Whether the manifest's files may be downloaded, whichever are missing, and what turned them off
	 *
	 * @since 6.19.0
	 */
	public function get_auto_install_status(): string {
		if ( $this->get_auto_install_constant() === false ) {
			return 'Off (GPDF_AUTO_INSTALL_FONTS constant)';
		}

		if ( ! $this->is_auto_install_overridden() ) {
			return 'On';
		}

		$files   = array_keys( $this->font_sources->get_manifest() );
		$blocked = array_filter(
			$files,
			function ( $file ) {
				return ! $this->is_auto_install_enabled( (string) $file );
			}
		);

		if ( $blocked === [] ) {
			return 'On';
		}

		return sprintf( '%s (gfpdf_auto_install_fonts filter)', count( $blocked ) === count( $files ) ? 'Off' : 'Partly off' );
	}

	/**
	 * What stops the missing required files downloading, or null
	 *
	 * @param string[] $missing The missing required files
	 *
	 * @since 6.19.0
	 */
	public function get_auto_install_blocker( array $missing ): ?string {
		if ( $this->can_download_all( $missing ) ) {
			return null;
		}

		return $this->get_auto_install_constant() === false ? 'GPDF_AUTO_INSTALL_FONTS constant' : 'gfpdf_auto_install_fonts filter';
	}

	/**
	 * Whether the constant turned downloads off or the filter may have, so a file can be blocked
	 *
	 * @since 6.19.0
	 */
	public function is_auto_install_overridden(): bool {
		$locked = $this->get_auto_install_constant();

		return $locked === null ? has_filter( 'gfpdf_auto_install_fonts' ) : ! $locked;
	}

	/**
	 * Whether a Font Manager record of any site uses the file, so it isn't the core font's
	 *
	 * @since 6.19.0
	 */
	public function is_record_file( string $file ): bool {
		return $this->font_sources->is_record_file( $file );
	}

	/**
	 * Whether a path is in the fonts folder, as opposed to a bundled font's or one a filter added
	 *
	 * @since 6.19.0
	 */
	public function is_in_fonts_folder( string $path ): bool {
		return untrailingslashit( dirname( $path ) ) === untrailingslashit( $this->data->template_font_location );
	}

	/**
	 * Download a file on the manifest into the fonts folder, or under its hashed name if another font's file has its name
	 *
	 * @param string $file   The manifest file name
	 * @param bool   $verify Hash a file of a known size too, to find another font's of the same size
	 *
	 * @return bool Whether the font's own copy is on disk now
	 *
	 * @since 6.19.0
	 */
	public function download( string $file, bool $verify = false ): bool {
		$manifest = $this->font_sources->get_manifest();

		if ( ! isset( $manifest[ $file ] ) ) {
			$this->log->error( 'Font not on the approved list', [ 'name' => $file ] );

			return false;
		}

		$font_path = $this->font_sources->get_plain_path( $file );
		$own_path  = $this->font_sources->get_own_copy( $file );
		$own_copy  = $own_path !== '';

		/* A file at the name of a known size can still be another font's, which only its SHA-1 tells */
		if ( $own_copy && $verify && $own_path === $font_path && $this->is_auto_install_enabled( $file ) ) {
			$own_copy = ! $this->is_unknown_build( $file );
		} elseif ( $own_copy && $this->is_failed_conflict( $file, $own_path ) ) {
			$own_copy = false;
		}

		/* Copied in by hand or saved by another request, so any failure on record is stale */
		if ( $own_copy ) {
			$this->clear_failure( $file );

			return true;
		}

		if ( ! $this->is_auto_install_enabled( $file ) ) {
			return false;
		}

		if ( $this->is_backing_off( $file ) ) {
			return false;
		}

		$conflict = is_file( $font_path );
		$target   = $conflict ? $this->font_sources->get_plain_path( $this->font_sources->get_hashed_name( $file ) ) : $font_path;
		if ( ! $this->save( $file, $target, $manifest[ $file ] ) ) {
			$this->record_failure( $file );

			return false;
		}

		$this->clear_failure( $file );

		if ( $conflict ) {
			$this->log->notice( 'Font saved under a new name as another font has its name', [ 'name' => $file ] );
			$this->remove_older_hashed_copies( $file, basename( $target ) );

			/* The metrics and PDFs cached from the file that was there are stale */
			FlushCache::flush();
		}

		return true;
	}

	/**
	 * Delete the copies a font's older releases left under other hashed names, if the name matches the copy's own SHA-1
	 *
	 * @param string $file The manifest file name
	 * @param string $keep The hashed name to keep
	 *
	 * @since 6.19.0
	 */
	protected function remove_older_hashed_copies( string $file, string $keep ): void {
		$dir = $this->data->template_font_location;

		foreach ( scandir( $dir ) ?: [] as $name ) {
			$parsed = $this->font_sources->parse_hashed_name( $name );
			if ( $name === $keep || $parsed === null || $parsed['file'] !== $file ) {
				continue;
			}

			if ( strpos( $this->hash_blob( $dir . $name, (int) filesize( $dir . $name ) ), $parsed['sha'] ) === 0 ) {
				wp_delete_file_from_directory( $dir . $name, $dir );
				$this->log->notice( 'Font copy for an older release removed', [ 'name' => $name ] );
			}
		}
	}

	/**
	 * Whether the file at a manifest name has a SHA-1 no known build has, so is another font's. A file that can't be read,
	 * or one with no other name to take, is left alone.
	 *
	 * @since 6.19.0
	 */
	protected function is_unknown_build( string $file ): bool {
		if ( $this->font_sources->get_hashed_name( $file ) === '' ) {
			return false;
		}

		$path = $this->font_sources->get_plain_path( $file );
		$size = (int) filesize( $path );
		$sha  = $this->hash_blob( $path, $size );

		return $sha !== '' && ! in_array( $sha, $this->font_sources->get_known_builds( $file )[ $size ] ?? [], true );
	}

	/**
	 * Whether the font's own copy is on disk, as is_installed() but without the file a failed download found was another
	 * font's (see is_failed_conflict())
	 *
	 * @since 6.19.0
	 */
	protected function has_own_copy( string $file ): bool {
		$own_path = $this->font_sources->get_own_copy( $file );

		return $own_path !== '' && ! $this->is_failed_conflict( $file, $own_path );
	}

	/**
	 * Whether the own copy get_own_copy() found by size is the other font's file a failed download found at the name,
	 * unchanged since. Saves hashing it again on a render.
	 *
	 * @since 6.19.0
	 */
	protected function is_failed_conflict( string $file, string $own_path ): bool {
		$conflict = $this->get_failures()[ $file ]['conflict'] ?? '';

		/* A hashed copy is the font's own whatever is at the name */
		return $conflict !== '' && $own_path === $this->font_sources->get_plain_path( $file ) && $conflict === $this->get_file_stamp( $own_path );
	}

	/**
	 * A file's size and modified time, or '' if it isn't on disk
	 *
	 * @since 6.19.0
	 */
	protected function get_file_stamp( string $path ): string {
		return is_file( $path ) ? filesize( $path ) . ':' . filemtime( $path ) : '';
	}

	/**
	 * Hash a font on disk, separate so tests can count the calls
	 *
	 * @since 6.19.0
	 */
	protected function hash_blob( string $path, int $size ): string {
		return Git_Blob::sha1( $path, $size );
	}

	/**
	 * Delete the unmodified files (fonts and licences) the Core Font installer downloaded that aren't kept, and the
	 * outdated ones that are, queuing a sync to fetch those again. A file whose download is turned off stays, as it
	 * couldn't come back. Only files of the installer's size are hashed.
	 *
	 * @param string[]|null $keep  File names to keep, else the fonts the site's PDFs need
	 * @param int           $since Also keep files written after this time, as a download since then may be needed
	 *
	 * @return string[] The files removed
	 *
	 * @since 6.19.0
	 */
	public function prune( ?array $keep = null, int $since = 0 ): array {
		$manifest = $this->font_sources->get_legacy_manifest( 'core' );

		/* Without the manifest nothing is known to be unmodified */
		if ( count( $manifest ) === 0 ) {
			$this->log->error( 'Core Font cleanup skipped as the font manifest could not be read' );

			return [];
		}

		$needed  = $keep ?? $this->with_wanted( $this->required_fonts->get_required_font_files() );
		$keep    = array_flip( array_diff( $needed, self::OUTDATED ) );
		$removed = [];
		$bytes   = 0;
		$offline = [];

		foreach ( $manifest as $file => $entry ) {
			$file = (string) $file;
			if ( isset( $keep[ $file ] ) ) {
				continue;
			}

			/* The file at the legacy name, never a hashed copy, which isn't the installer's */
			$path = $this->font_sources->get_plain_path( $file );
			clearstatcache( true, $path );
			if ( ! is_file( $path ) || filesize( $path ) !== $entry['size'] || ( $since > 0 && filemtime( $path ) > $since ) ) {
				continue;
			}

			if ( ! $this->is_auto_install_enabled( $file ) ) {
				$offline[] = $file;
				continue;
			}

			if ( ! $this->is_unmodified( $path, $entry ) ) {
				continue;
			}

			if ( ! wp_delete_file_from_directory( $path, $this->data->template_font_location ) ) {
				$this->log->warning( 'Core Font could not be removed', [ 'name' => $file ] );
				continue;
			}

			$removed[] = $file;
			$bytes    += $entry['size'];
		}

		if ( count( $offline ) > 0 ) {
			$this->log->notice( 'Core Font cleanup kept the fonts that downloads are turned off for', [ 'files' => $offline ] );
		}

		$this->log->notice(
			'Core Font cleanup complete',
			[
				'removed' => count( $removed ),
				'bytes'   => $bytes,
			]
		);

		if ( array_intersect( $removed, self::OUTDATED, $needed ) !== [] ) {
			$this->schedule_sync();
		}

		return $removed;
	}

	/**
	 * Every font the Font Manager lists: custom and listed bundled fonts by name, then core fonts, on disk or available to
	 * install, in the font dropdown's order.
	 * `can_delete` is whether this user may delete the font when it's unused; get_font_usage() says what's in use.
	 *
	 * @return array<int, array{id: string, font_name: string, type: string, regular: string, italics: string, bold: string, bolditalics: string, can_delete: bool, size_label: string, file_sizes: array<string, int>, base_url?: string, auto_install?: bool, can_install?: bool, languages?: string[], sample?: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_installed_fonts(): array {
		$custom = $this->font_sources->get_custom_fonts();
		$labels = $this->get_font_labels();
		$fonts  = [];

		foreach ( $custom as $font ) {
			$fonts[] = $this->get_custom_font_row( $font );
		}

		foreach ( $this->font_sources->get_sources() as $source ) {
			if ( empty( $source['manifest'] ) ) {
				foreach ( $this->font_sources->get_listed_families( $source ) as $key => $family ) {
					$fonts[] = $this->get_font_row( (string) $key, $labels[ $key ] ?? (string) $key, 'bundled', $family, false, $source['dir'] ) + [ 'base_url' => PDF_PLUGIN_URL . 'fonts/' ];
				}
			}
		}

		usort(
			$fonts,
			static function ( array $a, array $b ): int {
				return strcasecmp( $a['font_name'], $b['font_name'] );
			}
		);

		/* The labels are in the dropdown's order, and a family without one follows them */
		$families = array_diff_key( $this->get_core_families(), $custom );
		foreach ( array_replace( array_intersect_key( $labels, $families ), $families ) as $key => $family ) {
			$row = $this->get_core_font_row( (string) $key, $family, $labels );
			if ( $row !== null ) {
				$fonts[] = $row;
			}
		}

		return $fonts;
	}

	/**
	 * Why each font in use can't be deleted, as phrases for the Font Manager. Can need every form read, so the list
	 * loads without it. The PDFs themselves load when a font is opened, see get_pdfs_using_font().
	 *
	 * @return array<string, array{reasons: string[], pdfs_label: string}> By font key
	 *
	 * @since 6.19.0
	 */
	public function get_font_usage(): array {
		$custom  = $this->font_sources->get_custom_fonts();
		$locales = $this->font_sources->get_language_font_locales();
		$usage   = [];

		/* Other sites only lock core fonts, which only a super admin can delete */
		foreach ( $this->required_fonts->get_font_reasons( is_multisite() && $this->can_change_core_fonts() ) as $key => $reason ) {
			/* A site's custom fonts are its own, so other sites don't count */
			if ( isset( $custom[ $key ] ) ) {
				$reason['sites']   = 0;
				$reason['pending'] = false;
			}

			$in_use = $this->get_in_use( $reason, $locales[ $key ] ?? [] );
			if ( count( $in_use ) > 0 || $reason['pdfs'] > 0 ) {
				$usage[ $key ] = [
					'reasons'    => $in_use,
					/* translators: %d: the number of PDFs */
					'pdfs_label' => $reason['pdfs'] > 0 ? sprintf( _n( 'Used by %d PDF', 'Used by %d PDFs', $reason['pdfs'], 'gravity-pdf' ), $reason['pdfs'] ) : '',
				];
			}
		}

		return $usage;
	}

	/**
	 * The PDFs of this site using a font
	 *
	 * @return array<int, array{form: string, name: string, url: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_pdfs_using_font( string $key ): array {
		return $this->required_fonts->get_pdfs_using_font( $key );
	}

	/**
	 * Every site shares the fonts folder, so only a super admin deletes core fonts on Multisite
	 *
	 * @since 6.19.0
	 */
	public function can_change_core_fonts(): bool {
		return ! is_multisite() || is_super_admin();
	}

	/**
	 * A saved custom font as the Font Manager lists it
	 *
	 * @param array $font A custom font record
	 *
	 * @return array The record with its `type`, `can_delete`, `size_label` and `file_sizes`
	 *
	 * @since 6.19.0
	 */
	public function get_custom_font_row( array $font ): array {
		$files = array_intersect_key( $font, self::FONT_STYLE_KEYS );

		return array_merge(
			$font,
			[
				'type'       => 'custom',
				'can_delete' => true,
			],
			$this->get_size_fields( $files, $this->data->template_font_location )
		);
	}

	/**
	 * A source's font in the shape of a custom font record
	 *
	 * @param array  $files File names by style (R, B, I, BI)
	 * @param string $dir   The folder holding the files
	 *
	 * @return array{id: string, font_name: string, type: string, regular: string, italics: string, bold: string, bolditalics: string, can_delete: bool, size_label: string, file_sizes: array<string, int>}
	 *
	 * @since 6.19.0
	 */
	protected function get_font_row( string $key, string $label, string $type, array $files, bool $can_delete, string $dir ): array {
		$styles = [
			'regular'     => $files['R'] ?? '',
			'italics'     => $files['I'] ?? '',
			'bold'        => $files['B'] ?? '',
			'bolditalics' => $files['BI'] ?? '',
		];

		return [
			'id'        => $key,
			'font_name' => $label,
			'type'      => $type,
		] + $styles + [
			'can_delete' => $can_delete,
		] + $this->get_size_fields( $styles, $dir );
	}

	/**
	 * A core family's Font Manager row, `available` when none of it is on disk
	 *
	 * @param array{files: array<string, string>, paths: array<string, string>, bytes: int} $family
	 * @param array<string, string>                                                         $labels Font list labels by key
	 *
	 * @return array|null Null for a family with nothing on disk that can't or shouldn't be installed
	 *
	 * @since 6.19.0
	 */
	protected function get_core_font_row( string $key, array $family, array $labels ): ?array {
		/* Named as on disk, so the sizes and the preview are the font's own copy */
		$on_disk = [];
		foreach ( $family['files'] as $style => $file ) {
			if ( isset( $family['paths'][ $file ] ) ) {
				$on_disk[ $style ] = basename( $family['paths'][ $file ] );
			}
		}

		$can_download = $this->can_download_all( $family['files'] );
		$installed    = count( $on_disk ) > 0;

		/* A family without a label can't be chosen for a PDF (a fallback or barcode font), so isn't offered */
		if ( ! $installed && ( ! $can_download || ! isset( $labels[ $key ] ) ) ) {
			return null;
		}

		$can_change = $this->can_change_core_fonts();
		$row        = $this->get_font_row( $key, $labels[ $key ] ?? $key, $installed ? 'core' : 'available', $installed ? $on_disk : $family['files'], $installed && $can_change, $this->data->template_font_location );

		if ( ! $installed ) {
			$manifest = $this->font_sources->get_manifest();

			$row['size_label'] = (string) size_format( $family['bytes'], 1 );
			$row['file_sizes'] = array_map(
				static function ( string $file ) use ( $manifest ): int {
					return (int) $manifest[ $file ]['size'];
				},
				array_filter( array_intersect_key( $row, self::FONT_STYLE_KEYS ) )
			);

			/* Nothing on this site to preview yet, so the browser reads it from where it would be downloaded */
			$row['base_url'] = dirname( $manifest[ reset( $family['files'] ) ]['url'] ) . '/';
		}

		$languages = $this->font_sources->get_font_languages()[ $key ] ?? [];

		return $row + [
			'auto_install' => $can_download,
			'can_install'  => $can_change && $can_download && count( $on_disk ) < count( $family['files'] ),
			'languages'    => $languages['languages'] ?? [],
			'sample'       => $languages['sample'] ?? '',
		];
	}

	/**
	 * A core family's row as it stands now its files have changed
	 *
	 * @since 6.19.0
	 */
	protected function get_current_core_font_row( string $key ): ?array {
		return $this->get_core_font_row( $key, $this->get_core_families()[ $key ], $this->get_font_labels() );
	}

	/**
	 * A font's sizes, found by name in the folder as stored custom font paths can predate a move
	 *
	 * @param string[] $files File names or paths by style, '' for a style it doesn't have
	 *
	 * @return array{size_label: string, file_sizes: array<string, int>} `file_sizes` lets the preview ask before loading a large file
	 *
	 * @since 6.19.0
	 */
	protected function get_size_fields( array $files, string $dir ): array {
		$sizes = [];
		foreach ( array_filter( $files ) as $style => $file ) {
			$path = $dir . basename( $file );
			if ( is_file( $path ) ) {
				$sizes[ $style ] = (int) filesize( $path );
			}
		}

		return [
			'size_label' => count( $sizes ) > 0 ? (string) size_format( array_sum( $sizes ), 1 ) : '',
			'file_sizes' => $sizes,
		];
	}

	/**
	 * Delete an unused downloaded family's own copies (see Model_Font_Sources::get_own_copy()), keeping the licence files
	 * and any other font's file at a family file's name (a file of a known size is hashed to tell). A file at the name
	 * another site's custom font uses is kept too, as every site shares the fonts folder.
	 *
	 * @return array{id: string, removed: string[], bytes: int, font: array|null}|WP_Error `font` is its row now, if it can
	 *                                                                                     be installed again. 404 when
	 *                                                                                     nothing is on disk, 409 when in use
	 *
	 * @since 6.19.0
	 */
	public function delete_downloaded_font( string $key ) {
		$family = $this->get_core_families()[ $key ] ?? null;

		/* Before the sync saves the font under its hashed name, another font's file of a known size passes for its own */
		foreach ( $family['paths'] ?? [] as $file => $path ) {
			if ( $path === $this->font_sources->get_plain_path( $file ) && $this->is_unknown_build( $file ) ) {
				unset( $family['paths'][ $file ] );
				$this->log->notice( 'Kept another font\'s file at a font file\'s name', [ 'name' => $file ] );
			}
		}

		if ( $family === null || count( $family['paths'] ) === 0 ) {
			return new WP_Error( 'font_not_downloaded', __( 'The font is not downloaded.', 'gravity-pdf' ), [ 'status' => 404 ] );
		}

		$files = array_values( array_unique( $family['files'] ) );
		if ( $this->required_fonts->is_font_in_use( $key, is_multisite() ) ) {
			return new WP_Error( 'font_in_use', __( 'The font is in use, so it would be downloaded again.', 'gravity-pdf' ), [ 'status' => 409 ] );
		}

		$removed = [];
		$bytes   = 0;
		$shared  = $this->font_sources->get_files_used_by_other_sites();
		$shared  = $shared === null ? null : array_flip( $shared );
		if ( $shared === null ) {
			$this->log->warning( 'Kept the font\'s files at their plain names, as another site\'s fonts could not be read', [ 'font' => $key ] );
		}
		foreach ( $files as $file ) {
			$this->clear_failure( $file );
			$this->delete_back_off( $file );

			$path = $family['paths'][ $file ] ?? null;
			if ( $path === null ) {
				continue;
			}

			/* Another site's file can be at the name (any, if a site couldn't be read); a hashed copy is the core font's */
			if ( ( $shared === null || isset( $shared[ $file ] ) ) && $path === $this->font_sources->get_plain_path( $file ) ) {
				if ( $shared !== null ) {
					$this->log->notice( 'Kept a font file another site\'s custom font uses', [ 'name' => $file ] );
				}

				continue;
			}

			$size = (int) filesize( $path );
			if ( ! wp_delete_file_from_directory( $path, $this->data->template_font_location ) ) {
				$this->log->warning( 'Downloaded font file could not be removed', [ 'name' => $file ] );
				continue;
			}

			$removed[] = $file;
			$bytes    += $size;
		}

		FlushCache::flush( $key );

		$this->log->notice(
			'Downloaded font deleted',
			[
				'font'  => $key,
				'files' => $removed,
				'bytes' => $bytes,
			]
		);

		return [
			'id'      => $key,
			'removed' => $removed,
			'bytes'   => $bytes,
			'font'    => $this->get_current_core_font_row( $key ),
		];
	}

	/**
	 * Download a core family's missing files, ignoring any back-off as someone asked for it
	 *
	 * @return array|WP_Error The font's row. 404 for a family not on the manifest or replaced by a custom font, 409 with
	 *                        downloads turned off, 502 when a file couldn't be downloaded
	 *
	 * @since 6.19.0
	 */
	public function install_font( string $key ) {
		$family = $this->get_core_families()[ $key ] ?? null;
		if ( $family === null || isset( $this->font_sources->get_custom_fonts()[ $key ] ) ) {
			return new WP_Error( 'font_not_available', __( 'The font is not available to install.', 'gravity-pdf' ), [ 'status' => 404 ] );
		}

		if ( ! $this->can_download_all( $family['files'] ) ) {
			return new WP_Error( 'font_downloads_off', __( 'Font downloads are turned off.', 'gravity-pdf' ), [ 'status' => 409 ] );
		}

		$failed = [];
		foreach ( array_unique( $family['files'] ) as $file ) {
			$this->delete_back_off( $file );

			if ( ! $this->download( $file ) ) {
				$failed[] = $file;
			}
		}

		if ( count( $failed ) > 0 ) {
			$this->log->error(
				'Font could not be installed',
				[
					'font'  => $key,
					'files' => $failed,
				]
			);

			return new WP_Error( 'font_download_failed', __( 'The font could not be downloaded. Please try again.', 'gravity-pdf' ), [ 'status' => 502 ] );
		}

		$this->log->notice( 'Font installed', [ 'font' => $key ] );

		return $this->get_current_core_font_row( $key );
	}

	/**
	 * The downloadable sources' families: their files on the manifest by style (R, B, I, BI), the paths of their own
	 * copies on disk (see Model_Font_Sources::get_own_copy()) and the bytes of them all
	 *
	 * @return array<string, array{files: array<string, string>, paths: array<string, string>, bytes: int}>
	 *
	 * @since 6.19.0
	 */
	protected function get_core_families(): array {
		$families = [];

		foreach ( $this->font_sources->get_sources() as $id => $source ) {
			if ( empty( $source['manifest'] ) ) {
				continue;
			}

			$manifest = $this->font_sources->get_source_manifest( (string) $id );
			foreach ( $source['families'] ?? [] as $key => $family ) {
				$key = (string) $key;
				if ( ! empty( $families[ $key ]['paths'] ) || ! is_array( $family ) ) {
					continue;
				}

				$files = [];
				foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
					$file = $family[ $style ] ?? null;
					if ( is_string( $file ) && isset( $manifest[ $file ] ) && $this->font_sources->is_core_font_file( $file ) ) {
						$files[ $style ] = $file;
					}
				}

				$paths = [];
				$bytes = 0;
				foreach ( $files as $file ) {
					$bytes += (int) $manifest[ $file ]['size'];
					$path   = $this->font_sources->get_own_copy( $file );
					if ( $path !== '' ) {
						$paths[ $file ] = $path;
					}
				}

				if ( count( $files ) > 0 ) {
					$families[ $key ] = [
						'files' => $files,
						'paths' => $paths,
						'bytes' => $bytes,
					];
				}
			}
		}

		return $families;
	}

	/**
	 * Font list labels by key
	 *
	 * @return array<string, string>
	 *
	 * @since 6.19.0
	 */
	protected function get_font_labels(): array {
		$labels = [];
		foreach ( (array) $this->options->get_installed_fonts() as $group ) {
			foreach ( is_array( $group ) ? $group : [] as $key => $label ) {
				if ( is_string( $label ) && ! isset( $labels[ $key ] ) ) {
					$labels[ (string) $key ] = $label;
				}
			}
		}

		return $labels;
	}

	/**
	 * A font's get_font_reasons() entry as phrases for the Font Manager, other than its PDFs, which are linked to
	 *
	 * @param array    $reasons A get_font_reasons() entry
	 * @param string[] $locales The locales it's a language font for
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_in_use( array $reasons, array $locales = [] ): array {
		$phrases = [];

		if ( $reasons['default'] ) {
			$phrases[] = __( 'Default font', 'gravity-pdf' );
		}

		if ( $reasons['language'] ) {
			$names = array_map( [ $this, 'get_language_name' ], $locales );
			/* translators: %s: the site's languages, e.g. ไทย */
			$phrases[] = count( $names ) > 0 ? sprintf( __( 'Site language (%s)', 'gravity-pdf' ), implode( ', ', $names ) ) : __( 'Site language', 'gravity-pdf' );
		}

		if ( $reasons['source'] ) {
			$phrases[] = __( 'Required by an add-on', 'gravity-pdf' );
		}

		if ( $reasons['sites'] > 0 ) {
			/* translators: %d: the number of sites */
			$phrases[] = sprintf( _n( 'Used on %d other site', 'Used on %d other sites', $reasons['sites'], 'gravity-pdf' ), $reasons['sites'] );
		}

		if ( $reasons['pending'] ) {
			$phrases[] = __( 'Still checking the other sites', 'gravity-pdf' );
		}

		return $phrases;
	}

	/**
	 * A locale's native name when WordPress has the translations list cached, else the locale
	 *
	 * @since 6.19.0
	 */
	protected function get_language_name( string $locale ): string {
		$translations = get_site_transient( 'available_translations' );
		$name         = is_array( $translations ) ? ( $translations[ $locale ]['native_name'] ?? '' ) : '';

		return is_string( $name ) && $name !== '' ? $name : $locale;
	}

	/**
	 * Whether every file would download again if deleted
	 *
	 * @param string[] $files
	 *
	 * @since 6.19.0
	 */
	protected function can_download_all( array $files ): bool {
		foreach ( $files as $file ) {
			if ( ! $this->is_auto_install_enabled( $file ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array{url: string, size: int, sha: string} $entry
	 *
	 * @since 6.19.0
	 */
	protected function is_unmodified( string $path, array $entry ): bool {
		$hash = Git_Blob::sha1( $path, $entry['size'] );

		return $hash !== '' && hash_equals( $entry['sha'], $hash );
	}

	/**
	 * Files whose retry also failed, mapped to when they gave up
	 *
	 * @return array<string, int>
	 *
	 * @since 6.19.0
	 */
	public function get_failed_files(): array {
		$failed = [];
		foreach ( $this->get_failures() as $file => $failure ) {
			if ( $failure['gave_up'] > 0 ) {
				$failed[ $file ] = $failure['gave_up'];
			}
		}

		return $failed;
	}

	/**
	 * Failed files no admin has dismissed the notice for
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_undismissed_failed_files(): array {
		$undismissed = array_filter(
			$this->get_failures(),
			static function ( array $failure ): bool {
				return $failure['gave_up'] > 0 && ! $failure['dismissed'];
			}
		);

		return array_map( 'strval', array_keys( $undismissed ) );
	}

	/**
	 * Dismiss the notice for every failed file, on every site. A file that downloads, then fails again, is listed anew
	 *
	 * @since 6.19.0
	 */
	public function dismiss_failed_files(): void {
		$failures = $this->get_failures();
		foreach ( $failures as $file => $failure ) {
			if ( $failure['gave_up'] > 0 ) {
				$failures[ $file ]['dismissed'] = true;
			}
		}

		$this->save_failures( $failures );
	}

	/**
	 * Forget the failures read this request
	 *
	 * @since 6.19.0
	 */
	public function flush(): void {
		$this->failures = null;
	}

	/**
	 * The transient key of a file's back-off
	 *
	 * @since 6.19.0
	 */
	public static function back_off_key( string $file ): string {
		return self::BACK_OFF_KEY . md5( $file );
	}

	/**
	 * The files given and the wanted ones
	 *
	 * @param string[] $files
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function with_wanted( array $files ): array {
		return array_unique( array_merge( $files, $this->get_wanted_files() ) );
	}

	/**
	 * Files a PDF needed beyond the required set
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_wanted_files(): array {
		$wanted = get_network_option( get_main_network_id(), self::WANTED, [] );

		return is_array( $wanted ) ? array_values( array_filter( $wanted, 'is_string' ) ) : [];
	}

	/**
	 * For a render: download() the file, unless it's a large one that isn't on disk, which is left to the background sync
	 * so the render never waits on it and isn't cached without it
	 *
	 * @return bool Whether the font's own copy is on disk now
	 *
	 * @since 6.19.0
	 */
	public function download_or_queue( string $file ): bool {
		if ( ! $this->font_sources->is_large_file( $file ) || $this->has_own_copy( $file ) ) {
			return $this->download( $file );
		}

		if ( $this->queue( $file ) ) {
			Helper_Render_Health::mark();
		}

		return false;
	}

	/**
	 * Whether a render without the file has it on the way: a large one, unless downloads are off for it or it gave up
	 *
	 * @since 6.19.0
	 */
	public function is_queued( string $file ): bool {
		return $this->font_sources->is_large_file( $file ) && $this->can_queue( $file );
	}

	/**
	 * Add a file to the wanted list and queue the sync for it, rather than download it now, unless it's awaiting its retry
	 *
	 * @return bool Whether it's still to come
	 *
	 * @since 6.19.0
	 */
	public function queue( string $file ): bool {
		if ( ! $this->can_queue( $file ) ) {
			return false;
		}

		$wanted = $this->get_wanted_files();
		$is_new = ! in_array( $file, $wanted, true );

		if ( $is_new ) {
			$this->log->notice( 'Font queued for the background sync as a PDF needs it', [ 'name' => $file ] );
			$wanted[] = $file;
			update_network_option( get_main_network_id(), self::WANTED, $wanted );
		}

		/* A failure already scheduled its retry */
		if ( ! isset( $this->get_failures()[ $file ] ) && ( $is_new || wp_next_scheduled( self::SYNC_EVENT ) === false ) ) {
			$this->schedule_sync();
		}

		return true;
	}

	/**
	 * @since 6.19.0
	 */
	protected function can_queue( string $file ): bool {
		return ! isset( $this->get_failed_files()[ $file ] ) && $this->is_auto_install_enabled( $file );
	}

	/**
	 * Queue a single background sync
	 *
	 * @since 6.19.0
	 */
	public function schedule_sync(): void {
		/* The trigger may have changed the required set */
		delete_transient( self::SYNC_REMAINING );

		$this->schedule_by( time() );
	}

	/**
	 * Run the retries that are due, then download the missing required files, rescheduling when the time budget runs
	 * out or a retry is still to come
	 *
	 * @since 6.19.0
	 */
	public function sync(): void {
		$due     = $this->get_retries()[0];
		$missing = array_diff( $this->get_sync_files(), $due );
		delete_transient( self::SYNC_REMAINING );

		/* The back-off would skip a file that failed minutes ago */
		array_map( [ $this, 'delete_back_off' ], $due );

		$this->syncing = true;
		$left          = $this->within_budget(
			array_merge( $due, $missing ),
			function ( $file ) {
				/* Verified here, off the render path, to find another font's file of a known size */
				$this->download( (string) $file, true );
			}
		);
		$this->syncing = false;

		/* Includes the retries this run's first failures added */
		[ $still_due, $next ] = $this->get_retries();

		if ( $left !== [] ) {
			$this->log->notice( 'Font sync paused until its next run', [ 'remaining' => count( $left ) ] );
			set_transient( self::SYNC_REMAINING, array_values( array_diff( $left, $due ) ), DAY_IN_SECONDS );
		}

		if ( $left !== [] || $still_due !== [] || ! $this->backfill_site_fonts() ) {
			$next = time();
		}

		if ( $next > 0 ) {
			$this->schedule_by( $next );
		}
	}

	/**
	 * Queue a sync to record a site that couldn't be read, an hour on so a lasting failure can't loop
	 *
	 * @since 6.19.0
	 */
	public function schedule_site_fonts_retry(): void {
		$this->schedule_by( time() + HOUR_IN_SECONDS );
	}

	/**
	 * Record the fonts, and the custom font files, of the sites with no record yet, until the time budget runs out
	 *
	 * @return bool Whether every site was reached. One whose forms can't be read waits for a later sync.
	 *
	 * @since 6.19.0
	 */
	protected function backfill_site_fonts(): bool {
		$read_ahead = [ $this->required_fonts, 'prime_site_options' ];
		$left       = $this->within_budget(
			$this->required_fonts->get_sites_without_fonts(),
			function ( $site_id ) {
				$this->required_fonts->write_site_fonts( (int) $site_id );
			},
			$read_ahead
		);

		$custom_fonts = \GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' );
		if ( $left === [] && $custom_fonts instanceof Model_Custom_Fonts ) {
			$left = $this->within_budget( $custom_fonts->get_sites_without_records(), [ $custom_fonts, 'record_missing_site_fonts' ], $read_ahead );
		}

		return $left === [];
	}

	/**
	 * Queue the 6.19 cleanup of the shared fonts folder, once per install
	 *
	 * @since 6.19.0
	 */
	public function schedule_cleanup(): void {
		if ( get_network_option( get_main_network_id(), self::CLEANUP ) !== false ) {
			return;
		}

		update_network_option(
			get_main_network_id(),
			self::CLEANUP,
			[
				'started' => time(),
				'sites'   => $this->required_fonts->get_site_ids(),
				'keys'    => [],
			]
		);

		$this->schedule_by( time(), self::CLEANUP_EVENT );
	}

	/**
	 * Read the fonts of the sites left until the time budget runs out, then clean the fonts folder once all are read
	 *
	 * @since 6.19.0
	 */
	public function cleanup(): void {
		$state = get_network_option( get_main_network_id(), self::CLEANUP );
		if ( ! is_array( $state ) ) {
			return;
		}

		if ( ! is_array( $state['sites'] ?? null ) || ! is_array( $state['keys'] ?? null ) || ! is_array( $state['files'] ?? [] ) || ! is_int( $state['started'] ?? null ) ) {
			$this->log->warning( 'Core font cleanup skipped: its stored progress is malformed' );

			return;
		}

		$keys   = array_fill_keys( $state['keys'], true );
		$files  = $state['files'] ?? [];
		$failed = [];
		$left   = $this->within_budget(
			$state['sites'],
			function ( $site_id ) use ( &$keys, &$files, &$failed ) {
				/* A site deleted since needs no fonts */
				if ( is_multisite() && ! ( get_site( (int) $site_id ) instanceof WP_Site ) ) {
					return;
				}

				/* Sites yet to upgrade will save it as their default when they do */
				$site_keys = $this->required_fonts->get_site_font_keys( (int) $site_id, self::PRE_6_19_DEFAULT_FONT );
				if ( $site_keys === null ) {
					$failed[] = $site_id;

					return;
				}

				$keys += array_fill_keys( $site_keys, true );
				$files = array_merge( $files, $this->get_site_custom_font_files( (int) $site_id, $site_keys ) );
			},
			[ $this->required_fonts, 'prime_site_options' ]
		);
		$keys   = array_map( 'strval', array_keys( $keys ) );
		$files  = array_values( array_unique( $files ) );

		/* Nothing is pruned until every site is read, so one that couldn't be read is tried again an hour on */
		if ( $left !== [] || $failed !== [] ) {
			$state['sites'] = array_merge( $left, $failed );
			$state['keys']  = $keys;
			$state['files'] = $files;
			update_network_option( get_main_network_id(), self::CLEANUP, $state );
			$this->schedule_by( $left !== [] ? time() : time() + HOUR_IN_SECONDS, self::CLEANUP_EVENT );

			return;
		}

		$this->prune( $this->with_wanted( array_merge( $this->required_fonts->get_install_font_files( $keys ), $files ) ), $state['started'] );
		update_network_option( get_main_network_id(), self::CLEANUP, true );
	}

	/**
	 * The files of a site's custom fonts that it uses, as the cron site's font list only has its own. An unused one's
	 * files aren't kept: prune() only removes unmodified core files, which download again by name when needed.
	 *
	 * @param string[] $keys The font keys the site uses
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_site_custom_font_files( int $site_id, array $keys ): array {
		if ( ! is_multisite() ) {
			return [];
		}

		$fonts = array_change_key_case( $this->font_sources->get_site_font_files( $site_id ) );

		return array_merge( [], ...array_values( array_intersect_key( $fonts, array_flip( $keys ) ) ) );
	}

	/**
	 * Have the event run by the time given: queue it, or move one queued later forward. Never two, never later.
	 *
	 * @since 6.19.0
	 */
	protected function schedule_by( int $at, string $event = self::SYNC_EVENT ): void {
		$next = wp_next_scheduled( $event );
		if ( $next !== false && $next <= $at ) {
			return;
		}

		if ( $next !== false ) {
			wp_unschedule_event( $next, $event );
		}

		if ( wp_schedule_single_event( $at, $event ) !== true ) {
			$this->log->warning( 'Font event could not be scheduled', [ 'event' => $event ] );
		}
	}

	/**
	 * Run the callback on each item until the time budget runs out
	 *
	 * @param mixed[]       $items
	 * @param callable|null $read_ahead Given each chunk of items before they're reached, then [] once the loop ends
	 *
	 * @return mixed[] The items it didn't reach
	 *
	 * @since 6.19.0
	 */
	protected function within_budget( array $items, callable $each, ?callable $read_ahead = null ): array {
		$items    = array_values( $items );
		$deadline = microtime( true ) + $this->sync_budget;

		try {
			foreach ( $items as $i => $item ) {
				/* At least one per run, so slow hosts make progress */
				if ( $i > 0 && microtime( true ) >= $deadline ) {
					return array_slice( $items, $i );
				}

				if ( $read_ahead !== null && $i % Model_Site_Options::CHUNK_SIZE === 0 ) {
					$read_ahead( array_slice( $items, $i, Model_Site_Options::CHUNK_SIZE ) );
				}

				$each( $item );
			}
		} finally {
			if ( $read_ahead !== null ) {
				$read_ahead( [] );
			}
		}

		return [];
	}

	/**
	 * The files a paused run left, else the required files, less those that gave up and those with a copy under their
	 * hashed name. A file already on disk is in the list so sync() can check it's the manifest's.
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_sync_files(): array {
		$remaining = get_transient( self::SYNC_REMAINING );
		$files     = is_array( $remaining ) ? array_filter( $remaining, 'is_string' ) : $this->with_wanted( $this->required_fonts->get_required_font_files() );
		$gave_up   = $this->get_failed_files();

		$missing   = [];
		$installed = [];
		foreach ( $files as $file ) {
			if ( isset( $gave_up[ $file ] ) ) {
				continue;
			}

			/* A hashed copy was checked when it was saved */
			$path = $this->font_sources->get_own_copy( $file );
			if ( $path === '' ) {
				$missing[] = $file;
			} elseif ( $path === $this->font_sources->get_plain_path( $file ) ) {
				$installed[] = $file;
			}
		}

		/* Those needing a download go first, so hashing the rest can't push one into a paused run */
		return array_merge( $missing, $installed );
	}

	/**
	 * The files whose one retry is due, and when the next one still to come is (or 0)
	 *
	 * @return array{0: string[], 1: int}
	 *
	 * @since 6.19.0
	 */
	protected function get_retries(): array {
		$due  = [];
		$next = 0;

		foreach ( $this->get_failures() as $file => $failure ) {
			$file = (string) $file;
			if ( $failure['gave_up'] > 0 ) {
				continue;
			}

			if ( $failure['retry_at'] > time() ) {
				$next = $next === 0 ? $failure['retry_at'] : min( $next, $failure['retry_at'] );
			} else {
				$due[] = $file;
			}
		}

		return [ $due, $next ];
	}

	/**
	 * Stream to a tmp file, verify it against the manifest, then move it into place
	 *
	 * @param string                                     $file
	 * @param string                                     $font_path
	 * @param array{url: string, size: int, sha: string} $entry
	 *
	 * @since 6.19.0
	 */
	protected function save( string $file, string $font_path, array $entry ): bool {
		foreach ( [ $this->data->template_font_location, $this->data->template_tmp_location ] as $dir ) {
			if ( ! $this->misc->create_folder( $dir ) ) {
				return false;
			}

			if ( ! wp_is_writable( $dir ) ) {
				$this->log->error( 'Font folder is not writable', [ 'dir' => $dir ] );

				return false;
			}
		}

		/* A front-end PDF request hasn't loaded wp_tempnam() */
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$download_path = wp_tempnam( $file, $this->data->template_tmp_location );
		if ( $download_path === '' || ! is_file( $download_path ) ) {
			$this->log->error( 'Font temporary file could not be created', [ 'name' => $file ] );

			return false;
		}

		$res = wp_remote_get(
			$entry['url'],
			[
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $download_path,
			]
		);

		if ( is_wp_error( $res ) ) {
			return $this->fail(
				$download_path,
				'Font Download Failed',
				[
					'name'             => $file,
					'WP_Error_Message' => $res->get_error_message(),
					'WP_Error_Code'    => $res->get_error_code(),
				]
			);
		}

		$res_code = wp_remote_retrieve_response_code( $res );
		if ( $res_code !== 200 ) {
			return $this->fail(
				$download_path,
				'Font API Response Failed',
				[
					'name'          => $file,
					'response_code' => $res_code,
				]
			);
		}

		if ( ! Git_Blob::matches( $download_path, $entry['size'], $entry['sha'] ) ) {
			return $this->fail(
				$download_path,
				'Font Verification Failed',
				[
					'name' => $file,
					'size' => $entry['size'],
					'sha'  => $entry['sha'],
				]
			);
		}

		if ( ! $this->move( $download_path, $font_path ) ) {
			/* The right font is there anyway, as when another request saved it first */
			if ( Git_Blob::matches( $font_path, $entry['size'], $entry['sha'] ) ) {
				wp_delete_file( $download_path );
				$this->log->notice( 'Font already in place after a failed move', [ 'name' => $file ] );

				return true;
			}

			/* A move across filesystems copies, so a failure can leave a partial font behind */
			if ( is_file( $font_path ) ) {
				wp_delete_file( $font_path );
			}

			return $this->fail( $download_path, 'Font Move Failed', [ 'name' => $file ] );
		}

		$this->log->notice( 'Font Downloaded', [ 'name' => $file ] );

		return true;
	}

	/**
	 * @since 6.19.0
	 */
	protected function move( string $from, string $to ): bool {
		return @rename( $from, $to ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
	}

	/**
	 * @since 6.19.0
	 */
	protected function fail( string $download_path, string $message, array $context ): bool {
		wp_delete_file( $download_path );
		$this->log->error( $message, $context );

		return false;
	}

	/**
	 * @since 6.19.0
	 */
	protected function is_backing_off( string $file ): bool {
		/* The transient expires with the back-off, so any value means the file failed recently */
		if ( get_site_transient( self::back_off_key( $file ) ) === false ) {
			return false;
		}

		$this->log->warning( 'Font download skipped after a recent failure', [ 'name' => $file ] );

		return true;
	}

	/**
	 * Back off, then: the first failure schedules one retry; the retry's failure gives up and raises the admin notice
	 *
	 * @since 6.19.0
	 */
	protected function record_failure( string $file ): void {
		set_site_transient( self::back_off_key( $file ), time(), self::BACK_OFF );

		/* A failed download leaves the font's name as it was, so a file there is another font's */
		$conflict = $this->get_file_stamp( $this->font_sources->get_plain_path( $file ) );

		$failures = $this->get_failures();
		$failure  = $failures[ $file ] ?? null;

		if ( $failure === null ) {
			$failures[ $file ] = [
				'retry_at'  => time() + wp_rand( 0, $this->retry_jitter ),
				'gave_up'   => 0,
				'dismissed' => false,
				'conflict'  => $conflict,
			];

			$this->save_failures( $failures );

			/* The sync schedules once, when it's done */
			if ( ! $this->syncing ) {
				$this->schedule_by( $failures[ $file ]['retry_at'] );
			}

			return;
		}

		if ( $failure['gave_up'] === 0 ) {
			/* The first give-up, which the notice lists */
			$failures[ $file ] = [
				'retry_at'  => 0,
				'gave_up'   => time(),
				'dismissed' => false,
				'conflict'  => $conflict,
			];

			$this->save_failures( $failures );
			$this->log->error( 'Font download failed again, and won\'t be retried automatically', [ 'name' => $file ] );
		}
	}

	/**
	 * Forget a file's failure and back-off, as after it downloads or turns up on disk
	 *
	 * @since 6.19.0
	 */
	protected function clear_failure( string $file ): void {
		$failures = $this->get_failures();
		if ( isset( $failures[ $file ] ) ) {
			$this->delete_back_off( $file );
			unset( $failures[ $file ] );
			$this->save_failures( $failures );
		}
	}

	/**
	 * Let a file be downloaded again before its back-off expires
	 *
	 * @since 6.19.0
	 */
	protected function delete_back_off( string $file ): void {
		delete_site_transient( self::back_off_key( $file ) );
	}

	/**
	 * @param array<string, array{retry_at: int, gave_up: int, dismissed: bool, conflict: string}> $failures
	 *
	 * @since 6.19.0
	 */
	protected function save_failures( array $failures ): void {
		$this->failures = $failures;

		update_network_option( get_main_network_id(), self::FAILURES, $failures );
	}

	/**
	 * Failures by file, read once per request, when any give-up whose file is now on disk is cleared from the record
	 *
	 * @return array<string, array{retry_at: int, gave_up: int, dismissed: bool, conflict: string}>
	 *
	 * @since 6.19.0
	 */
	protected function get_failures(): array {
		if ( $this->failures === null ) {
			$this->failures = [];

			$stored = get_network_option( get_main_network_id(), self::FAILURES, [] );
			foreach ( is_array( $stored ) ? $stored : [] as $file => $failure ) {
				$retry_at = is_array( $failure ) && is_int( $failure['retry_at'] ?? null ) ? $failure['retry_at'] : 0;
				$gave_up  = is_array( $failure ) && is_int( $failure['gave_up'] ?? null ) ? $failure['gave_up'] : 0;

				/* An entry with neither is waiting on nothing */
				if ( is_string( $file ) && ( $retry_at > 0 || $gave_up > 0 ) ) {
					$this->failures[ $file ] = [
						'retry_at'  => $gave_up > 0 ? 0 : $retry_at,
						'gave_up'   => $gave_up,
						'dismissed' => ( $failure['dismissed'] ?? false ) === true,
						'conflict'  => is_string( $failure['conflict'] ?? null ) ? $failure['conflict'] : '',
					];
				}
			}

			$this->clear_installed_give_ups();
		}

		return $this->failures;
	}

	/**
	 * Forget the give-ups of files now on disk, as when copied in by hand, so the notice and reports stop listing them
	 *
	 * @since 6.19.0
	 */
	protected function clear_installed_give_ups(): void {
		foreach ( $this->failures as $file => $failure ) {
			if ( $failure['gave_up'] > 0 && $this->has_own_copy( $file ) ) {
				$this->clear_failure( $file );
			}
		}
	}
}
