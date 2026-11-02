<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use Exception;
use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Statics\Git_Blob;
use GFPDF_Vendor\Mpdf\Config\FontVariables;
use GFPDF_Vendor\Mpdf\Language\LanguageToFont;
use GFPDF_Vendor\Mpdf\Language\LanguageToFontInterface;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use GPDFAPI;

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
 * The fonts Gravity PDF provides: the bundled Arimo faces and mPDF's core fonts
 *
 * A source has a `version` and `families` (mPDF font data, plus an optional `name` and `listed`), and optionally a
 * bundled `dir`, a `manifest`, a font list `group` and `legacy` (git blob SHA-1 => size of other copies).
 *
 * @since 6.19.0
 */
class Model_Font_Sources extends Helper_Abstract_Model {

	/**
	 * Each source's version at the last check, so a new or updated source adopts its fonts once
	 *
	 * @since 6.19.0
	 */
	const OPTION = 'gfpdf_font_sources';

	/**
	 * The bundled DejaVu Sans subset that draws the symbols Arimo lacks
	 *
	 * @since 6.19.0
	 */
	const SYMBOLS_FONT = 'gfpdf-dejavu-symbols';

	/**
	 * The bundled Arimo, the default font for new installs and the last fallback
	 *
	 * @since 6.19.0
	 */
	const ARIMO = 'arimo';

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
	 * @var array|null
	 * @since 6.19.0
	 */
	protected $sources;

	/**
	 * @var array|null
	 * @since 6.19.0
	 */
	protected $font_data;

	/**
	 * @var string[]|null
	 * @since 6.19.0
	 */
	protected $language_font_keys;

	/**
	 * @since 6.19.0
	 */
	public function __construct( Helper_Data $data, Model_Custom_Fonts $custom_fonts, LoggerInterface $log ) {
		$this->data         = $data;
		$this->custom_fonts = $custom_fonts;
		$this->log          = $log;
	}

	/**
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
	 * The bundled Arimo Regular path
	 *
	 * @since 6.19.0
	 */
	public function get_fallback_file(): string {
		$bundled = $this->get_sources()['bundled'];

		return $bundled['dir'] . $bundled['families'][ self::ARIMO ]['R'];
	}

	/**
	 * The first registered font given, else the default font, else Arimo (never mPDF's serif)
	 *
	 * @param mixed[]    $fonts    Font keys in order of preference, e.g. the PDF's font
	 * @param array|null $fontdata mPDF font data, or null for the registered fonts
	 *
	 * @since 6.19.0
	 */
	public function get_default_font( array $fonts = [], ?array $fontdata = null ): string {
		$fontdata = $fontdata ?? $this->get_font_data();
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
	 * @since 6.19.0
	 */
	protected function mpdf_can_resolve( string $font, array $fontdata ): bool {
		$defaults = ( new FontVariables() )->getDefaults();
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
	protected function is_registered( string $font, array $fontdata ): bool {
		return is_string( $fontdata[ $font ]['R'] ?? null );
	}

	/**
	 * mPDF's font data with every registered font, read once per request
	 *
	 * @since 6.19.0
	 */
	public function get_font_data(): array {
		if ( $this->font_data === null ) {
			$fontdata        = apply_filters( 'mpdf_font_data', ( new FontVariables() )->getDefaults()['fontdata'] );
			$this->font_data = is_array( $fontdata ) ? $fontdata : [];
		}

		return $this->font_data;
	}

	/**
	 * Forget the font data and language fonts read this request
	 *
	 * @since 6.19.0
	 */
	public function flush(): void {
		$this->font_data          = null;
		$this->language_font_keys = null;
	}

	/**
	 * The mPDF font keys for the installed languages and the locale
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_language_font_keys(): array {
		if ( $this->language_font_keys === null ) {
			$mapper  = $this->get_language_mapper();
			$locales = array_filter( array_merge( (array) get_available_languages(), [ get_locale() ] ), 'is_string' );
			$keys    = [];

			foreach ( array_unique( $locales ) as $locale ) {
				/* Adobe CJK fonts are off in Gravity PDF's config */
				$options = $mapper->getLanguageOptions( str_replace( '_', '-', $locale ), false );
				$key     = is_array( $options ) && is_string( $options[1] ?? null ) ? $options[1] : '';
				if ( $key !== '' ) {
					$keys[] = $key;
				}
			}

			$this->language_font_keys = array_values( array_unique( $keys ) );
		}

		return $this->language_font_keys;
	}

	/**
	 * mPDF's backupSubsFont. Sun-ExtA is added only once it's on disk, as mPDF's own list would download 22.9 MB for one emoji.
	 *
	 * @param array|null $fontdata mPDF font data, or null for the registered fonts
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_substitution_fonts( ?array $fontdata = null ): array {
		$fontdata = $fontdata ?? $this->get_font_data();
		$fonts    = array_merge( [ self::SYMBOLS_FONT, 'dejavusanscondensed', 'freesans' ], $this->get_language_font_keys() );

		$sun_exta = $fontdata['sun-exta']['R'] ?? '';
		if ( is_string( $sun_exta ) && $sun_exta !== '' && is_file( $this->data->template_font_location . $sun_exta ) ) {
			$fonts[] = 'sun-exta';
		}

		$fonts = array_filter(
			array_unique( $fonts ),
			function ( $key ) use ( $fontdata ) {
				return isset( $fontdata[ $key ] );
			}
		);

		return array_values( $fonts );
	}

	/**
	 * A source's manifest, keyed by file name
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	public function read_manifest( string $path ): array {
		if ( $path === '' ) {
			return [];
		}

		$entries = is_readable( $path ) ? json_decode( (string) file_get_contents( $path ), true ) : null; /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents */
		if ( ! is_array( $entries ) ) {
			$this->log->error( 'Font manifest is missing or invalid', [ 'manifest' => $path ] );

			return [];
		}

		$manifest = [];
		foreach ( $entries as $entry ) {
			if ( $this->is_manifest_entry( $entry ) ) {
				$manifest[ $entry['name'] ] = [
					'url'  => $entry['download_url'],
					'size' => $entry['size'],
					'sha'  => $entry['sha'],
				];
			}
		}

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
	 * A plain file name, an https:// URL, and a git blob SHA-1 with a positive size
	 *
	 * @param mixed $entry
	 *
	 * @since 6.19.0
	 */
	protected function is_manifest_entry( $entry ): bool {
		return is_array( $entry )
			&& is_string( $entry['name'] ?? null ) && $entry['name'] !== '' && basename( $entry['name'] ) === $entry['name']
			&& is_string( $entry['download_url'] ?? null ) && strpos( $entry['download_url'], 'https://' ) === 0
			&& $this->is_blob_entry( $entry['sha'] ?? null, $entry['size'] ?? null );
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
	 * Adopt the custom font records of each new or updated source
	 *
	 * @since 6.19.0
	 */
	public function maybe_adopt_changed_sources(): void {
		$versions = array_map(
			function ( $source ) {
				return (string) $source['version'];
			},
			$this->get_sources()
		);

		$stored = get_option( self::OPTION, [] );
		$stored = is_array( $stored ) ? $stored : [];
		ksort( $stored );
		ksort( $versions );
		if ( $stored === $versions ) {
			return;
		}

		foreach ( $versions as $id => $version ) {
			if ( ( $stored[ $id ] ?? null ) !== $version ) {
				$this->adopt_records( $id );
			}
		}

		update_option( self::OPTION, $versions );

		/**
		 * Fires once a font source is added or changes version, after its custom font records are adopted
		 *
		 * @since 6.19.0
		 */
		do_action( 'gfpdf_font_sources_changed' );
	}

	/**
	 * Remove the custom font records a source now serves (keyed by one of its families, every file a known copy).
	 * Edited records stay and keep overriding.
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

		$records = array_intersect_key( $this->custom_fonts->get_custom_fonts(), $source['families'] );
		if ( count( $records ) === 0 ) {
			return [];
		}

		$known   = $this->get_known_files( $source );
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
	 * On Multisite another site's record may still use the files in the shared fonts folder, so keep them
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
		} catch ( Exception $e ) {
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
	 * @since 6.19.0
	 */
	protected function get_language_mapper(): LanguageToFontInterface {
		return new LanguageToFont();
	}

	/**
	 * @since 6.19.0
	 */
	protected function get_bundled_source(): array {
		return [
			'version'  => '1.0.0',
			'dir'      => PDF_PLUGIN_DIR . 'fonts/',
			'group'    => esc_html__( 'Unicode', 'gravity-pdf' ),
			'families' => [
				self::ARIMO        => [
					'name'   => 'Arimo',
					'R'      => 'GravityPDF-Arimo-Regular.ttf',
					'B'      => 'GravityPDF-Arimo-Bold.ttf',
					'I'      => 'GravityPDF-Arimo-Italic.ttf',
					'BI'     => 'GravityPDF-Arimo-BoldItalic.ttf',
					'useOTL' => 0xFF,
				],
				self::SYMBOLS_FONT => [
					'R'      => 'GravityPDF-DejaVuSansSymbols.ttf',
					'useOTL' => 0,
					'listed' => false,
				],
			],
			/* The Arimo copies premium templates install */
			'legacy'   => [
				'9be443c7df4832028061c8fcd2282ca61e45b27c' => 436876,
				'da89a631d64d19458de1fd8f993a03d7a8397a00' => 439272,
				'6a8837cb0a16ddfbfa32a35be1f0d1f9a36384d3' => 441484,
				'6dc45e79e19a2589ed7608c62186c7461859be34' => 435744,
			],
		];
	}

	/**
	 * @since 6.19.0
	 */
	protected function get_core_source(): array {
		return [
			'version'  => '1.0.0',
			'families' => ( new FontVariables() )->getDefaults()['fontdata'],
			'manifest' => PDF_PLUGIN_DIR . 'build/payload/core-fonts.json',
		];
	}

	/**
	 * A source's known git blob SHA-1s, from its manifest and `legacy`, keyed by file size
	 *
	 * @return array<int, string[]>
	 *
	 * @since 6.19.0
	 */
	protected function get_known_files( array $source ): array {
		$known = [];

		foreach ( $this->read_manifest( (string) ( $source['manifest'] ?? '' ) ) as $file ) {
			$known[ $file['size'] ][] = $file['sha'];
		}

		$legacy = is_array( $source['legacy'] ?? null ) ? $source['legacy'] : [];
		foreach ( $legacy as $sha => $size ) {
			if ( $this->is_blob_entry( $sha, $size ) ) {
				$known[ $size ][] = $sha;
			}
		}

		return $known;
	}

	/**
	 * @param mixed $sha
	 * @param mixed $size
	 *
	 * @since 6.19.0
	 */
	protected function is_blob_entry( $sha, $size ): bool {
		return is_string( $sha ) && preg_match( '/^[0-9a-f]{40}$/', $sha ) && is_int( $size ) && $size > 0;
	}

	/**
	 * Whether every file of a custom font record is a known copy, checking size before hash
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
			$path = $this->data->template_font_location . basename( $file );
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
