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
class Model_Font_Downloader extends Helper_Abstract_Model {

	/**
	 * Prefix of the per-file transient holding when a download last failed, which skips the network until it expires
	 *
	 * @since 6.19.0
	 */
	const BACK_OFF_KEY = 'gfpdf_core_font_backoff_';

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
	public function get_font_path( string $file ): string {
		return $this->font_sources->get_font_path( $file );
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
	 * Download a file on the manifest into the fonts folder, skipped if it's there unless forced
	 *
	 * @param string $file  A file name on the manifest
	 * @param bool   $force Replace a copy already in the fonts folder
	 *
	 * @since 6.19.0
	 */
	public function download( string $file, bool $force = false ): bool {
		$manifest = $this->font_sources->get_manifest();

		if ( ! isset( $manifest[ $file ] ) ) {
			$this->log->error( 'Font not on the approved list', [ 'name' => $file ] );

			return false;
		}

		$font_path = $this->get_font_path( $file );
		if ( ! $force && is_file( $font_path ) ) {
			return true;
		}

		if ( ! $this->is_auto_install_enabled( $file ) ) {
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
	 * The transient key of a file's back-off
	 *
	 * @since 6.19.0
	 */
	public static function back_off_key( string $file ): string {
		return self::BACK_OFF_KEY . md5( $file );
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
			/* A move across filesystems copies, so a failure can leave a partial font behind */
			if ( is_file( $font_path ) && ! Git_Blob::matches( $font_path, $entry['size'], $entry['sha'] ) ) {
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
	 * Back off the file for 15 minutes, network-wide like the fonts folder
	 *
	 * @since 6.19.0
	 */
	protected function record_failure( string $file ): void {
		set_site_transient( self::back_off_key( $file ), time(), self::BACK_OFF );
	}
}
