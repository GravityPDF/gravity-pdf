<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Mpdf\On_Demand_Font_File_Finder;
use GFPDF\Statics\Git_Blob;
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
 * Downloads the font sources' files into the fonts folder, verifying each against its manifest
 *
 * @since 6.19.0
 */
class Model_Core_Font_Downloader extends Helper_Abstract_Model {

	/**
	 * Recently failed files (file => timestamp), which skip the network until the back-off ends
	 *
	 * @since 6.19.0
	 */
	const FAILURES = 'gfpdf_core_font_failures';

	/**
	 * @since 6.19.0
	 */
	const BACK_OFF = 15 * MINUTE_IN_SECONDS;

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
	 * @var array<string, array{url: string, size: int, sha: string}>|null
	 * @since 6.19.0
	 */
	protected $manifest;

	/**
	 * @var array<string, int>|null
	 * @since 6.19.0
	 */
	protected $failures;

	/**
	 * @since 6.19.0
	 */
	public function __construct( Model_Font_Sources $font_sources, Helper_Data $data, Helper_Abstract_Options $options, Helper_Misc $misc, LoggerInterface $log ) {
		$this->font_sources = $font_sources;
		$this->data         = $data;
		$this->options      = $options;
		$this->misc         = $misc;
		$this->log          = $log;
	}

	/**
	 * An mPDF font finder that downloads missing core fonts
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

		return new On_Demand_Font_File_Finder( $font_dirs, $this, is_string( $default_file ) ? $default_file : '' );
	}

	/**
	 * @since 6.19.0
	 */
	public function get_font_path( string $file ): string {
		return $this->data->template_font_location . $file;
	}

	/**
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
	public function is_debug_mode(): bool {
		return $this->options->get_option( 'debug_mode', 'No' ) === 'Yes';
	}

	/**
	 * Downloadable files across all sources, keyed by file name
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_manifest(): array {
		if ( $this->manifest === null ) {
			$this->manifest = [];
			foreach ( $this->font_sources->get_sources() as $source ) {
				$this->manifest += $this->font_sources->read_manifest( (string) ( $source['manifest'] ?? '' ) );
			}
		}

		return $this->manifest;
	}

	/**
	 * Whether a file is a font on the manifest, not one of its licence files
	 *
	 * @since 6.19.0
	 */
	public function is_core_font_file( string $file ): bool {
		return isset( $this->get_manifest()[ $file ] ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), [ 'ttf', 'otf' ], true );
	}

	/**
	 * Download a file on the manifest into the fonts folder, skipped if it's there unless forced
	 *
	 * @param bool $force Replace a copy already in the fonts folder
	 *
	 * @since 6.19.0
	 */
	public function download( string $file, bool $force = false ): bool {
		$font_path = $this->get_font_path( $file );
		$manifest  = $this->get_manifest();

		if ( ! isset( $manifest[ $file ] ) ) {
			$this->log->error( 'Core Font not on the approved list', [ 'name' => $file ] );

			return false;
		}

		if ( ! $force && is_file( $font_path ) ) {
			return true;
		}

		/**
		 * Filter whether a core font may be downloaded, e.g. on hosts that block outbound requests
		 *
		 * @param bool   $download Whether to download the file
		 * @param string $file     The file name
		 *
		 * @since 6.19.0
		 */
		if ( ! apply_filters( 'gfpdf_core_font_download', true, $file ) ) {
			return false;
		}

		if ( $this->is_backing_off( $file ) ) {
			return false;
		}

		if ( ! $this->save( $file, $font_path, $manifest[ $file ] ) ) {
			$this->record_failure( $file );

			return false;
		}

		return true;
	}

	/**
	 * Stream to a tmp file, verify it against the manifest, then move it into place
	 *
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
				$this->log->error( 'Core Font folder is not writable', [ 'dir' => $dir ] );

				return false;
			}
		}

		$download_path = wp_tempnam( $file, $this->data->template_tmp_location );
		if ( $download_path === '' || ! is_file( $download_path ) ) {
			$this->log->error( 'Core Font temporary file could not be created', [ 'name' => $file ] );

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
				'Core Font Download Failed',
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
				'Core Font API Response Failed',
				[
					'name'          => $file,
					'response_code' => $res_code,
				]
			);
		}

		if ( ! Git_Blob::matches( $download_path, $entry['size'], $entry['sha'] ) ) {
			return $this->fail(
				$download_path,
				'Core Font Verification Failed',
				[
					'name' => $file,
					'size' => $entry['size'],
					'sha'  => $entry['sha'],
				]
			);
		}

		if ( ! $this->move( $download_path, $font_path ) ) {
			/* A move across filesystems copies, so a failure can leave a partial font behind */
			if ( is_file( $font_path ) && ! Git_Blob::matches( $font_path, $entry['size'], $entry['sha'] ) ) {
				wp_delete_file( $font_path );
			}

			return $this->fail( $download_path, 'Core Font Move Failed', [ 'name' => $file ] );
		}

		$this->log->notice( 'Core Font Downloaded', [ 'name' => $file ] );

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
		if ( ! $this->is_recent( $this->get_failures()[ $file ] ?? 0 ) ) {
			return false;
		}

		$this->log->warning( 'Core Font download skipped after a recent failure', [ 'name' => $file ] );

		return true;
	}

	/**
	 * @since 6.19.0
	 */
	protected function record_failure( string $file ): void {
		$this->failures          = array_filter( $this->get_failures(), [ $this, 'is_recent' ] );
		$this->failures[ $file ] = time();

		set_transient( self::FAILURES, $this->failures, self::BACK_OFF );
	}

	/**
	 * @param mixed $failed_at
	 *
	 * @since 6.19.0
	 */
	protected function is_recent( $failed_at ): bool {
		return is_int( $failed_at ) && $failed_at > time() - self::BACK_OFF;
	}

	/**
	 * Recent failures, read once per request
	 *
	 * @return array<string, int>
	 *
	 * @since 6.19.0
	 */
	protected function get_failures(): array {
		if ( $this->failures === null ) {
			$failures       = get_transient( self::FAILURES );
			$this->failures = is_array( $failures ) ? $failures : [];
		}

		return $this->failures;
	}
}
