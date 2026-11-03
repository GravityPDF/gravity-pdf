<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Statics\Git_Blob;
use GFPDF_Vendor\Mpdf\Config\FontVariables;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use GPDFAPI;
use Throwable;

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
 * The fonts Gravity PDF provides: the bundled Arimo and DejaVu symbol faces, and mPDF's core fonts
 *
 * A source has `families` (mPDF font data, plus an optional `name` and `listed`), and optionally a bundled `dir`, a
 * `manifest` of its downloadable files, a font list `group` and `legacy` (git blob SHA-1 => size of other copies of
 * its files, which adopt_records() accepts).
 *
 * @since 6.19.0
 */
class Model_Font_Sources extends Helper_Abstract_Model {

	/**
	 * The bundled DejaVu Sans subset that draws the symbols Arimo lacks
	 *
	 * @since 6.19.0
	 */
	public const SYMBOLS_FONT = 'gfpdf-dejavu-symbols';

	/**
	 * The bundled Arimo, the default font for new installs and the last fallback
	 *
	 * @since 6.19.0
	 */
	public const ARIMO = 'arimo';

	/**
	 * @var Helper_Data
	 * @since 6.19.0
	 */
	protected $data;

	/**
	 * @var Model_Custom_Fonts
	 * @since 6.19.0
	 */
	protected $custom_fonts;

	/**
	 * @var LoggerInterface
	 * @since 6.19.0
	 */
	protected $log;

	/**
	 * @var array<string, array>|null The sources, once get_sources() builds them
	 * @since 6.19.0
	 */
	protected $sources;

	/**
	 * @var array|null mPDF's FontVariables defaults, once built
	 * @since 6.19.0
	 */
	protected $mpdf_defaults;

	/**
	 * Every source's downloadable files by name, once get_manifest() reads them
	 *
	 * @var array<string, array{url: string, size: int, sha: string}>|null
	 * @since 6.19.0
	 */
	protected $manifest;

	/**
	 * @since 6.19.0
	 */
	public function __construct( Helper_Data $data, Model_Custom_Fonts $custom_fonts, LoggerInterface $log ) {
		$this->data         = $data;
		$this->custom_fonts = $custom_fonts;
		$this->log          = $log;
	}

	/**
	 * The font sources, keyed by ID (`bundled`, `core`), built once
	 *
	 * @return array<string, array>
	 *
	 * @since 6.19.0
	 */
	public function get_sources(): array {
		if ( $this->sources === null ) {
			$this->sources = [
				'bundled' => $this->get_bundled_source(),
				'core'    => $this->get_core_source(),
			];
		}

		return $this->sources;
	}

	/**
	 * Whether a font source provides the mPDF font key
	 *
	 * @since 6.19.0
	 */
	public function is_family_key( string $key ): bool {
		foreach ( $this->get_sources() as $source ) {
			if ( isset( $source['families'][ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Start mPDF in the PDF's font, else the default font, else Arimo, rather than mPDF's `serif` (DejaVu Serif
	 * Condensed), which a site without the core fonts can't load
	 *
	 * @param mixed $config   mPDF config
	 * @param mixed $form
	 * @param mixed $entry
	 * @param mixed $settings The PDF's settings
	 *
	 * @since 6.19.0
	 */
	public function set_default_font( $config, $form = [], $entry = [], $settings = [] ): array {
		$config   = is_array( $config ) ? $config : [];
		$fontdata = is_array( $config['fontdata'] ?? null ) ? $config['fontdata'] : [];
		$pdf_font = is_array( $settings ) ? ( $settings['font'] ?? '' ) : '';

		$config['default_font'] = $this->get_default_font( [ $pdf_font ], $fontdata );

		return $config;
	}

	/**
	 * The first registered font given, else the default font, else Arimo
	 *
	 * @param mixed[]    $fonts    Font keys in order of preference, e.g. the PDF's font
	 * @param array|null $fontdata mPDF font data, or null for the registered fonts
	 *
	 * @since 6.19.0
	 */
	public function get_default_font( array $fonts = [], ?array $fontdata = null ): string {
		$fontdata = $fontdata ?? apply_filters( 'mpdf_font_data', $this->get_mpdf_defaults()['fontdata'] );
		$fontdata = is_array( $fontdata ) ? $fontdata : [];
		$fonts[]  = GPDFAPI::get_options_class()->get_option( 'default_font', '' );

		foreach ( $fonts as $font ) {
			$font = is_string( $font ) ? strtolower( $font ) : '';
			if ( $font !== '' && $this->mpdf_can_resolve( $font, $fontdata ) ) {
				return $font;
			}
		}

		return self::ARIMO;
	}

	/**
	 * Whether mPDF finds a font for the name as SetFont() does: its alias, then the font, then another of its kind
	 * (e.g. `arial` becomes the first registered sans-serif font)
	 *
	 * @see \GFPDF_Vendor\Mpdf\Mpdf::SetFont() Check this still mirrors it when mPDF is upgraded
	 *
	 * @since 6.19.0
	 */
	protected function mpdf_can_resolve( string $font, array $fontdata ): bool {
		$defaults = $this->get_mpdf_defaults();
		$font     = $defaults['fonttrans'][ $font ] ?? $font;

		if ( $this->is_registered( $font, $fontdata ) ) {
			return true;
		}

		foreach ( [ 'sans_fonts', 'serif_fonts', 'mono_fonts' ] as $kind ) {
			if ( ! in_array( $font, $defaults[ $kind ], true ) ) {
				continue;
			}

			foreach ( $defaults[ $kind ] as $similar ) {
				if ( $this->is_registered( $similar, $fontdata ) ) {
					return true;
				}
			}

			return false;
		}

		return false;
	}

	/**
	 * @since 6.19.0
	 */
	protected function get_mpdf_defaults(): array {
		if ( $this->mpdf_defaults === null ) {
			$this->mpdf_defaults = ( new FontVariables() )->getDefaults();
		}

		return $this->mpdf_defaults;
	}

	/**
	 * Whether mPDF's font data has a Regular file for the key
	 *
	 * @since 6.19.0
	 */
	protected function is_registered( string $font, array $fontdata ): bool {
		return is_string( $fontdata[ $font ]['R'] ?? null );
	}

	/**
	 * The bundled Arimo Regular path
	 *
	 * @since 6.19.0
	 */
	public function get_fallback_file(): string {
		$bundled = $this->get_sources()['bundled'];

		return $bundled['dir'] . $bundled['families'][ self::ARIMO ]['R'];
	}

	/**
	 * Downloadable files across all sources, keyed by file name, read only when a file is needed
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_manifest(): array {
		if ( $this->manifest === null ) {
			$this->manifest = [];
			foreach ( $this->get_sources() as $source ) {
				$this->manifest += $this->read_manifest( $source );
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
	 * Where a file goes in the fonts folder
	 *
	 * @since 6.19.0
	 */
	public function get_font_path( string $file ): string {
		return $this->data->template_font_location . $file;
	}

	/**
	 * Register each source's families with mPDF, keeping any font already registered
	 *
	 * @param mixed $fonts mPDF font data
	 *
	 * @since 6.19.0
	 */
	public function register_font_data( $fonts ): array {
		$fonts = is_array( $fonts ) ? $fonts : [];

		foreach ( $this->get_sources() as $source ) {
			foreach ( $source['families'] as $key => $family ) {
				if ( ! isset( $fonts[ $key ] ) ) {
					unset( $family['name'], $family['listed'] );
					$fonts[ $key ] = $family;
				}
			}
		}

		return $fonts;
	}

	/**
	 * Add each source's `dir` to mPDF's `fontDir`, turning a single folder string into a list
	 *
	 * @param mixed $config mPDF config
	 *
	 * @since 6.19.0
	 */
	public function register_font_directories( $config ): array {
		$config = is_array( $config ) ? $config : [];
		$dirs   = $config['fontDir'] ?? [];

		/* mPDF takes one folder as a string */
		if ( ! is_array( $dirs ) ) {
			$dirs = is_string( $dirs ) && $dirs !== '' ? [ $dirs ] : [];
		}

		$config['fontDir'] = $dirs;

		foreach ( $this->get_sources() as $source ) {
			if ( ! empty( $source['dir'] ) && ! in_array( $source['dir'], $config['fontDir'], true ) ) {
				$config['fontDir'][] = $source['dir'];
			}
		}

		return $config;
	}

	/**
	 * Prepend each source's listed families to its font list group, skipping any a custom font overrides
	 *
	 * @param mixed $fonts The font list, grouped by label
	 *
	 * @since 6.19.0
	 */
	public function add_font_list_groups( $fonts ): array {
		$fonts        = is_array( $fonts ) ? $fonts : [];
		$custom_fonts = $this->custom_fonts->get_custom_fonts();

		foreach ( $this->get_sources() as $source ) {
			if ( empty( $source['group'] ) ) {
				continue;
			}

			$list = [];
			foreach ( $source['families'] as $key => $family ) {
				if ( ( $family['listed'] ?? true ) && ! isset( $custom_fonts[ $key ] ) ) {
					$list[ $key ] = $family['name'] ?? $key;
				}
			}

			$group                     = $fonts[ $source['group'] ] ?? [];
			$fonts[ $source['group'] ] = $list + ( is_array( $group ) ? $group : [] );
		}

		return $fonts;
	}

	/**
	 * Remove the custom font records a source now serves (keyed by one of its families, every file a known copy).
	 * Edited records stay and keep overriding.
	 *
	 * @param string $source_id A get_sources() key, e.g. `bundled`
	 *
	 * @return string[] The IDs of the records removed
	 *
	 * @since 6.19.0
	 */
	public function adopt_records( string $source_id ): array {
		$source = $this->get_sources()[ $source_id ] ?? null;
		if ( $source === null ) {
			return [];
		}

		$known   = $this->get_known_files( $source );
		$records = array_intersect_key( $this->custom_fonts->get_custom_fonts(), $source['families'] );

		/* A source with no known copies can't vouch for any file */
		if ( count( $known ) === 0 || count( $records ) === 0 ) {
			return [];
		}

		$adopted = [];

		foreach ( $records as $id => $font ) {
			$id = (string) $id;
			if ( ! $this->has_only_known_files( $font, $known ) ) {
				continue;
			}

			if ( $this->remove_record( $id ) ) {
				$adopted[] = $id;
			}
		}

		if ( count( $adopted ) > 0 ) {
			$this->log->notice(
				'Custom fonts replaced by a font source',
				[
					'source' => $source_id,
					'fonts'  => $adopted,
				]
			);
		}

		return $adopted;
	}

	/**
	 * Remove a custom font record, and outside Multisite its files too: another site's record may use the shared fonts
	 * folder. Logs and returns false on failure
	 *
	 * @since 6.19.0
	 */
	protected function remove_record( string $id ): bool {
		$removed = false;
		$error   = '';

		try {
			if ( ! is_multisite() ) {
				$removed = GPDFAPI::delete_pdf_font( $id ) === true;
			} elseif ( $this->custom_fonts->delete_font( $id ) ) {
				FlushCache::flush( $id );
				$removed = true;
			}
		} catch ( Throwable $e ) {
			$error = $e->getMessage();
		}

		if ( ! $removed ) {
			$this->log->error(
				'Could not remove a custom font record replaced by a font source',
				[
					'font'      => $id,
					'exception' => $error,
				]
			);

			return false;
		}

		return true;
	}

	/**
	 * The Arimo and DejaVu symbol faces in fonts/, plus the hashes of the Arimo copies premium templates install
	 *
	 * @since 6.19.0
	 */
	protected function get_bundled_source(): array {
		return [
			'dir'      => PDF_PLUGIN_DIR . 'fonts/',
			'group'    => esc_html__( 'Unicode', 'gravity-pdf' ),
			'families' => [
				self::ARIMO        => [
					'name'   => 'Arimo',
					'R'      => 'gfpdf-Arimo-Regular.ttf',
					'B'      => 'gfpdf-Arimo-Bold.ttf',
					'I'      => 'gfpdf-Arimo-Italic.ttf',
					'BI'     => 'gfpdf-Arimo-BoldItalic.ttf',
					'useOTL' => 0xFF,
				],
				self::SYMBOLS_FONT => [
					'R'      => 'gfpdf-DejaVuSansSymbols.ttf',
					'useOTL' => 0,
					'listed' => false,
				],
			],
			/* The Arimo the Formium, Cellulose and Colossus templates install. Only upgrade routines read it, so a new
			   hash needs its own routine */
			'legacy'   => [
				'9be443c7df4832028061c8fcd2282ca61e45b27c' => 436876, /* Arimo-Regular.ttf */
				'da89a631d64d19458de1fd8f993a03d7a8397a00' => 439272, /* Arimo-Bold.ttf */
				'6a8837cb0a16ddfbfa32a35be1f0d1f9a36384d3' => 441484, /* Arimo-Italic.ttf */
				'6dc45e79e19a2589ed7608c62186c7461859be34' => 435744, /* Arimo-BoldItalic.ttf */
			],
		];
	}

	/**
	 * mPDF's core fonts, which live in the fonts folder
	 *
	 * @since 6.19.0
	 */
	protected function get_core_source(): array {
		return [
			'families' => $this->get_mpdf_defaults()['fontdata'],
			'manifest' => PDF_PLUGIN_DIR . 'fonts/core-fonts.php',
		];
	}

	/**
	 * A source's manifest, keyed by file name, skipping malformed entries
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	protected function read_manifest( array $source ): array {
		$path = $source['manifest'] ?? '';
		if ( ! is_string( $path ) || $path === '' ) {
			return [];
		}

		try {
			$entries = is_readable( $path ) ? include $path : null;
		} catch ( Throwable $e ) {
			$entries = null;
		}

		if ( ! is_array( $entries ) ) {
			$this->log->error( 'Font manifest is missing or invalid', [ 'manifest' => $path ] );

			return [];
		}

		$manifest = array_filter( $entries, [ $this, 'is_manifest_entry' ], ARRAY_FILTER_USE_BOTH );

		if ( count( $manifest ) !== count( $entries ) ) {
			$this->log->warning(
				'Font manifest entries skipped as invalid',
				[
					'manifest' => $path,
					'skipped'  => count( $entries ) - count( $manifest ),
				]
			);
		}

		return $manifest;
	}

	/**
	 * A plain file name with an https:// URL, and a git blob SHA-1 with a positive size
	 *
	 * @param mixed      $entry
	 * @param int|string $file
	 *
	 * @since 6.19.0
	 */
	protected function is_manifest_entry( $entry, $file ): bool {
		return is_string( $file ) && $file !== '' && basename( $file ) === $file
			&& is_array( $entry )
			&& is_string( $entry['url'] ?? null ) && strpos( $entry['url'], 'https://' ) === 0
			&& is_string( $entry['sha'] ?? null ) && preg_match( '/^[0-9a-f]{40}$/', $entry['sha'] ) === 1
			&& is_int( $entry['size'] ?? null ) && $entry['size'] > 0;
	}

	/**
	 * A source's manifest and `legacy` git blob SHA-1s, keyed by file size
	 *
	 * @return array<int, string[]>
	 *
	 * @since 6.19.0
	 */
	protected function get_known_files( array $source ): array {
		$known = [];
		foreach ( $this->read_manifest( $source ) as $file ) {
			$known[ $file['size'] ][] = $file['sha'];
		}

		foreach ( $source['legacy'] ?? [] as $sha => $size ) {
			$known[ $size ][] = $sha;
		}

		return $known;
	}

	/**
	 * Whether every file of a custom font record is a known copy, checking size before hash
	 *
	 * @param array $font A custom font record
	 *
	 * @since 6.19.0
	 */
	protected function has_only_known_files( array $font, array $known ): bool {
		$files = array_filter( [ $font['regular'] ?? '', $font['italics'] ?? '', $font['bold'] ?? '', $font['bolditalics'] ?? '' ] );
		if ( count( $files ) === 0 ) {
			return false;
		}

		foreach ( $files as $file ) {
			if ( ! is_string( $file ) ) {
				return false;
			}

			/* mPDF finds a font by its file name in the fonts folder, so check the copy there */
			$path = $this->get_font_path( basename( $file ) );
			if ( ! is_file( $path ) || ! is_readable( $path ) ) {
				return false;
			}

			$size = (int) filesize( $path );
			if ( ! isset( $known[ $size ] ) || ! in_array( Git_Blob::sha1( $path, $size ), $known[ $size ], true ) ) {
				return false;
			}
		}

		return true;
	}
}
