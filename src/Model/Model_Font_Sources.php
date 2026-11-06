<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Statics\Git_Blob;
use GFPDF_Vendor\Mpdf\Config\FontVariables;
use GFPDF_Vendor\Mpdf\Language\LanguageToFont;
use GFPDF_Vendor\Mpdf\Language\LanguageToFontInterface;
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
 * The fonts Gravity PDF provides (bundled Arimo and DejaVu symbols, mPDF's core fonts) and those `gfpdf_font_sources` adds
 *
 * A source has `families` (mPDF font data, plus an optional `name` and `listed`), and optionally a bundled `dir`, a
 * `manifest` of its downloadable files, a font list `group`, and `legacy` (git blob SHA-1 => size) or a
 * `legacy_manifest` of other copies of its files, which adopt_records() accepts. A `required` source's files join the
 * background sync.
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
	 * Core fonts mPDF substitutes from, each downloaded only when a PDF has the characters it covers that the fonts before
	 * it lack: DejaVu Sans Condensed (Armenian, Georgian, symbols), FreeSans (Syriac, Cherokee), then Thai, Khmer, Myanmar,
	 * Sinhala and Ethiopic, 0.1–0.6 MB each
	 *
	 * @since 6.19.0
	 */
	public const SUBSTITUTION_CORE_FONTS = [ 'dejavusanscondensed', 'freesans', 'garuda', 'khmeros', 'padaukbook', 'kaputaunicode', 'abyssinicasil' ];

	/**
	 * The site transient holding the installed language packs' font keys, which every site shares
	 *
	 * @since 6.19.0
	 */
	public const LANGUAGE_FONT_KEYS = 'gfpdf_language_font_keys';

	/**
	 * The option holding each added source's version, as of the last admin request
	 *
	 * @since 6.19.0
	 */
	public const SOURCE_VERSIONS = 'gfpdf_font_sources';

	/**
	 * Source IDs a plugin can't add or replace
	 *
	 * @since 6.19.0
	 */
	public const RESERVED_SOURCES = [ 'bundled', 'core' ];

	/**
	 * The keys an added source may set
	 *
	 * @since 6.19.0
	 */
	protected const ADDED_SOURCE_KEYS = [ 'families', 'manifest', 'group', 'required', 'legacy', 'version' ];

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
	 * @var array|null mPDF's font data with every registered font, once get_font_data() filters it
	 * @since 6.19.0
	 */
	protected $font_data;

	/**
	 * @var string[]|null The installed languages' mPDF font keys, once get_language_font_keys() maps them
	 * @since 6.19.0
	 */
	protected $language_font_keys;

	/**
	 * @var array<string, string[]>|null Locales by font key, once get_language_font_locales() maps them
	 * @since 6.19.0
	 */
	protected $language_font_locales;

	/**
	 * Every source's downloadable files by name, once get_manifest() reads them
	 *
	 * @var array<string, array{url: string, size: int, sha: string}>|null
	 * @since 6.19.0
	 */
	protected $manifest;

	/**
	 * Each source's manifest by ID, once get_source_manifest() reads it
	 *
	 * @var array<string, array<string, array{url: string, size: int, sha: string}>>
	 * @since 6.19.0
	 */
	protected $manifests = [];

	/**
	 * Each source's legacy manifest by ID, once get_legacy_manifest() reads it
	 *
	 * @var array<string, array<string, array{url: string, size: int, sha: string}>>
	 * @since 6.19.0
	 */
	protected $legacy_manifests = [];

	/**
	 * Core font files a Font Manager record of the site also uses (see get_record_collisions()), by blog ID
	 *
	 * @var array<int, array<string, string>>
	 * @since 6.19.0
	 */
	protected $record_collisions = [];

	/**
	 * @var array<string, true>|null The file names the downloadable sources' families use, once get_family_files() reads them
	 * @since 6.19.0
	 */
	protected $family_files;

	/**
	 * @since 6.19.0
	 */
	public function __construct( Helper_Data $data, Model_Custom_Fonts $custom_fonts, LoggerInterface $log ) {
		$this->data         = $data;
		$this->custom_fonts = $custom_fonts;
		$this->log          = $log;
	}

	/**
	 * The font sources, keyed by ID (`bundled`, `core`, then those plugins add), built once
	 *
	 * @return array<string, array>
	 *
	 * @since 6.19.0
	 */
	public function get_sources(): array {
		if ( $this->sources === null ) {
			$core = [
				'bundled' => $this->get_bundled_source(),
				'core'    => $this->get_core_source(),
			];

			/* A filter callback asking for the sources gets the core ones, rather than recursing */
			$this->sources = $core;

			/**
			 * Add font sources, whose files download from their manifest when needed
			 *
			 * @param array<string, array> $sources The sources keyed by ID; `bundled` and `core` are reserved
			 *
			 * @since 6.19.0
			 */
			$sources = apply_filters( 'gfpdf_font_sources', $core );

			/* Only valid sources reach rename_colliding_files(), which reads their manifests */
			$this->sources = $this->rename_colliding_files( $core + $this->get_added_sources( $sources, $core ) );

			/* A callback may have cached what was built from the core sources alone */
			$this->manifest          = null;
			$this->family_files      = null;
			$this->record_collisions = [];
		}

		return $this->sources;
	}

	/**
	 * The valid sources a plugin added, logging and dropping the rest
	 *
	 * @param mixed $sources What the filter returned
	 *
	 * @return array<string, array>
	 *
	 * @since 6.19.0
	 */
	protected function get_added_sources( $sources, array $core ): array {
		$added = [];

		if ( ! is_array( $sources ) ) {
			return $added;
		}

		foreach ( $sources as $id => $source ) {
			if ( isset( $core[ $id ] ) ) {
				if ( $source !== $core[ $id ] ) {
					$this->log->error( 'Font source dropped: its ID is reserved', [ 'source' => $id ] );
				}

				continue;
			}

			$error = $this->get_source_error( $id, $source );
			if ( $error !== '' ) {
				$this->log->error( 'Font source dropped: ' . $error, [ 'source' => (string) $id ] );

				continue;
			}

			$source             = array_intersect_key( $source, array_flip( self::ADDED_SOURCE_KEYS ) );
			$source['required'] = ! empty( $source['required'] );
			$source['version']  = (string) ( $source['version'] ?? '' );

			$added[ $id ] = $source;
		}

		return $added;
	}

	/**
	 * Why an added source is invalid, or '' if it isn't; its manifest's entries are checked when first read
	 *
	 * @param mixed $id
	 * @param mixed $source
	 *
	 * @since 6.19.0
	 */
	protected function get_source_error( $id, $source ): string {
		if ( ! is_string( $id ) || $id === '' || sanitize_key( $id ) !== $id ) {
			return 'its ID is not a lowercase key';
		}

		if ( ! is_array( $source ) ) {
			return 'it is not an array';
		}

		if ( ! is_array( $source['families'] ?? null ) || count( $source['families'] ) === 0 ) {
			return 'it has no families';
		}

		foreach ( $source['families'] as $key => $family ) {
			if ( ! $this->is_family( $key, $family ) ) {
				return 'a family is invalid';
			}
		}

		if ( ! is_string( $source['manifest'] ?? null ) || ! is_readable( $source['manifest'] ) ) {
			return 'its manifest is missing';
		}

		if ( isset( $source['group'] ) && ( ! is_string( $source['group'] ) || $source['group'] === '' ) ) {
			return 'its group is not a label';
		}

		if ( isset( $source['version'] ) && ! is_scalar( $source['version'] ) ) {
			return 'its version is not a string';
		}

		if ( isset( $source['legacy'] ) && ! $this->is_legacy( $source['legacy'] ) ) {
			return 'its legacy hashes are invalid';
		}

		return '';
	}

	/**
	 * A lowercase font key with a Regular file, every R/B/I/BI file a plain file name
	 *
	 * @param mixed $key
	 * @param mixed $family
	 *
	 * @since 6.19.0
	 */
	protected function is_family( $key, $family ): bool {
		if ( ! is_string( $key ) || $key === '' || strtolower( $key ) !== $key || ! is_array( $family ) || ! isset( $family['R'] ) ) {
			return false;
		}

		foreach ( $this->get_family_files( $family ) as $file ) {
			if ( ! $this->is_file_name( $file ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A family's R/B/I/BI values
	 *
	 * @return mixed[]
	 *
	 * @since 6.19.0
	 */
	protected function get_family_files( array $family ): array {
		return array_values( array_intersect_key( $family, array_flip( [ 'R', 'B', 'I', 'BI' ] ) ) );
	}

	/**
	 * A file name with no path
	 *
	 * @param mixed $file
	 *
	 * @since 6.19.0
	 */
	protected function is_file_name( $file ): bool {
		return is_string( $file ) && $file !== '' && basename( $file ) === $file;
	}

	/**
	 * A git blob SHA-1 and a positive size
	 *
	 * @param mixed $sha
	 * @param mixed $size
	 *
	 * @since 6.19.0
	 */
	protected function is_blob( $sha, $size ): bool {
		return is_string( $sha ) && preg_match( '/^[0-9a-f]{40}$/', $sha ) === 1 && is_int( $size ) && $size > 0;
	}

	/**
	 * Whether every legacy entry is a git blob SHA-1 => a positive size
	 *
	 * @param mixed $legacy
	 *
	 * @since 6.19.0
	 */
	protected function is_legacy( $legacy ): bool {
		if ( ! is_array( $legacy ) ) {
			return false;
		}

		foreach ( $legacy as $sha => $size ) {
			if ( ! $this->is_blob( $sha, $size ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Adopt the records of added sources that are new or re-versioned, then fire `gfpdf_font_sources_changed`
	 *
	 * @since 6.19.0
	 */
	public function check_added_sources(): void {
		/* No default: the first check stores [], so later reads hit the autoloaded options */
		$stored = get_option( self::SOURCE_VERSIONS );
		if ( $stored === [] && ! has_filter( 'gfpdf_font_sources' ) ) {
			return;
		}

		$versions = is_array( $stored ) ? $stored : [];
		$current  = [];
		$changed  = [];

		foreach ( array_diff_key( $this->get_sources(), array_flip( self::RESERVED_SOURCES ) ) as $id => $source ) {
			$id = (string) $id;
			if ( ( $versions[ $id ] ?? null ) !== $source['version'] ) {
				/* Unrecorded, so it's checked again once its manifest is fixed */
				if ( count( $this->get_source_manifest( $id ) ) === 0 ) {
					continue;
				}

				$this->adopt_records( $id );
				$changed[] = $id;
			}

			$current[ $id ] = $source['version'];
		}

		if ( $current !== $stored ) {
			update_option( self::SOURCE_VERSIONS, $current, true );
		}

		if ( count( $changed ) > 0 ) {
			/**
			 * Fires when font sources were added or re-versioned, after their custom font records were adopted
			 *
			 * @param string[] $changed The source IDs
			 *
			 * @since 6.19.0
			 */
			do_action( 'gfpdf_font_sources_changed', $changed );
		}
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
	 * Condensed), which a site without the core fonts can't load, and substitute missing glyphs from Arimo first
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

		$config['default_font']   = $this->get_default_font( [ $pdf_font ], $fontdata );
		$config['backupSubsFont'] = $this->get_substitution_fonts( $fontdata, $config['default_font'] );
		$config['backupSIPFont']  = $this->get_sip_font( $fontdata );

		/* Sun-ExtA names Sun-ExtB as its own fallback for those characters */
		foreach ( $fontdata as $key => $family ) {
			$sip_ext = is_array( $family ) ? ( $family['sip-ext'] ?? '' ) : '';
			if ( is_string( $sip_ext ) && $sip_ext !== '' && ! $this->is_on_disk( $sip_ext, $fontdata ) ) {
				unset( $config['fontdata'][ $key ]['sip-ext'] );
			}
		}

		return $config;
	}

	/**
	 * mPDF's backupSIPFont: Sun-ExtB once it's on disk, as mPDF would download 17.6 MB for one rare CJK Extension B
	 * character, else the bundled symbols. With none, mPDF stops substituting the rest of a run at that character, e.g.
	 * 字 after 𠀀; the first render after a font cache flush still does (GravityPDF/mpdf#828).
	 *
	 * @since 6.19.0
	 */
	protected function get_sip_font( array $fontdata ): string {
		if ( $this->is_on_disk( 'sun-extb', $fontdata ) ) {
			return 'sun-extb';
		}

		return $this->is_registered( self::SYMBOLS_FONT, $fontdata ) ? self::SYMBOLS_FONT : '';
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
	 * mPDF's font data with every registered font, filtered once per request
	 *
	 * @return array<string, array> mPDF font data keyed by font
	 *
	 * @since 6.19.0
	 */
	public function get_font_data(): array {
		if ( $this->font_data === null ) {
			$fontdata        = apply_filters( 'mpdf_font_data', $this->get_mpdf_defaults()['fontdata'] );
			$this->font_data = is_array( $fontdata ) ? $fontdata : [];
		}

		return $this->font_data;
	}

	/**
	 * Forget the font data and language fonts, e.g. once a language pack installs
	 *
	 * @since 6.19.0
	 */
	public function flush(): void {
		$this->font_data             = null;
		$this->language_font_keys    = null;
		$this->language_font_locales = null;
		$this->record_collisions     = [];
		delete_site_transient( self::LANGUAGE_FONT_KEYS );
	}

	/**
	 * The mPDF font keys for the installed languages (cached for a day, network-wide) and this site's locale
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_language_font_keys(): array {
		if ( $this->language_font_keys === null ) {
			$keys = get_site_transient( self::LANGUAGE_FONT_KEYS );
			if ( ! is_array( $keys ) ) {
				$keys = array_keys( $this->map_language_font_locales( (array) get_available_languages() ) );
				set_site_transient( self::LANGUAGE_FONT_KEYS, $keys, DAY_IN_SECONDS );
			}

			/* Per site, and may be set without its language pack */
			$locale_keys = array_keys( $this->map_language_font_locales( [ get_locale() ] ) );

			$this->language_font_keys = array_values( array_unique( array_merge( array_filter( $keys, 'is_string' ), $locale_keys ) ) );
		}

		return $this->language_font_keys;
	}

	/**
	 * The installed languages and the locale mapped to their mPDF font keys, once per request
	 *
	 * @return array<string, string[]> Locales by font key
	 *
	 * @since 6.19.0
	 */
	public function get_language_font_locales(): array {
		if ( $this->language_font_locales !== null ) {
			return $this->language_font_locales;
		}

		$this->language_font_locales = $this->map_language_font_locales(
			array_merge( (array) get_available_languages(), [ get_locale() ] )
		);

		return $this->language_font_locales;
	}

	/**
	 * Map locales to the mPDF font keys they need, skipping those the default font covers
	 *
	 * @param mixed[] $locales
	 *
	 * @return array<string, string[]> Locales by font key
	 *
	 * @since 6.19.0
	 */
	public function map_language_font_locales( array $locales ): array {
		$mapper = $this->get_language_mapper();
		$keys   = [];

		foreach ( array_unique( array_filter( $locales, 'is_string' ) ) as $language ) {
			/* Adobe CJK fonts are off in Gravity PDF's config */
			$options = $mapper->getLanguageOptions( str_replace( '_', '-', $language ), false );
			$key     = is_array( $options ) && is_string( $options[1] ?? null ) ? $options[1] : '';
			if ( $key !== '' ) {
				$keys[ $key ][] = $language;
			}
		}

		return $keys;
	}

	/**
	 * mPDF's backupSubsFont, Arimo first. mPDF reads which characters a font has from its ranges file and downloads only a
	 * font that has them, but Sun-ExtA is listed only once it's on disk, as it would download 22.9 MB mid-render.
	 *
	 * @param array|null $fontdata mPDF font data, or null for the registered fonts
	 * @param string     $font     The PDF's font, left out as it can't stand in for its own missing glyphs
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_substitution_fonts( ?array $fontdata = null, string $font = '' ): array {
		$fontdata = $fontdata ?? $this->get_font_data();

		$fonts = array_merge(
			[ self::ARIMO, self::SYMBOLS_FONT ],
			self::SUBSTITUTION_CORE_FONTS,
			$this->get_language_font_keys(),
			$this->is_on_disk( 'sun-exta', $fontdata ) ? [ 'sun-exta' ] : []
		);

		$fonts = array_filter(
			array_unique( $fonts ),
			function ( $key ) use ( $fontdata, $font ) {
				return $key !== $font && $this->is_registered( $key, $fontdata );
			}
		);

		return array_values( $fonts );
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
	 * Whether mPDF's font data has a Regular file for the key, and it's in the fonts folder
	 *
	 * @since 6.19.0
	 */
	protected function is_on_disk( string $font, array $fontdata ): bool {
		return $this->is_registered( $font, $fontdata ) && $this->is_downloaded( $fontdata[ $font ]['R'] );
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
	 * Downloadable files across all sources, keyed by file name, read only when a file is needed. A name two sources
	 * use for different files is unique here: the later source's has the hashed name (see rename_colliding_files()).
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_manifest(): array {
		/* Building the sources resets what was cached from them, so it comes first */
		$sources = $this->get_sources();

		if ( $this->manifest === null ) {
			$this->manifest = [];
			foreach ( array_keys( $sources ) as $id ) {
				$this->manifest += $this->get_unaliased_manifest( (string) $id );
			}
		}

		/* A record can be added at any time, so the hashed names are added to a copy */
		$manifest = $this->manifest;
		foreach ( $this->get_record_collisions() as $file => $hashed ) {
			if ( isset( $manifest[ $file ] ) ) {
				$manifest[ $hashed ] = $manifest[ $file ];
			}
		}

		return $manifest;
	}

	/**
	 * A source's older copies of its files, keyed by file name, read once
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_legacy_manifest( string $id ): array {
		if ( ! isset( $this->legacy_manifests[ $id ] ) ) {
			$this->legacy_manifests[ $id ] = $this->read_manifest_file( $this->get_sources()[ $id ]['legacy_manifest'] ?? '' );
		}

		return $this->legacy_manifests[ $id ];
	}

	/**
	 * A source's downloadable files, keyed by file name, read once
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_source_manifest( string $id ): array {
		$manifest = $this->get_unaliased_manifest( $id );

		foreach ( $this->get_record_collisions() as $file => $hashed ) {
			if ( isset( $manifest[ $file ] ) ) {
				$manifest[ $hashed ] = $manifest[ $file ];
			}
		}

		return $manifest;
	}

	/**
	 * A source's manifest as read, without the hashed names of files a Font Manager record uses
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	protected function get_unaliased_manifest( string $id ): array {
		/* Building the sources reads the manifests of those that share a file name with another */
		$sources = $this->get_sources();

		if ( ! isset( $this->manifests[ $id ] ) ) {
			$this->manifests[ $id ] = $this->read_source_manifest( $id, $sources[ $id ] ?? [] );
		}

		return $this->manifests[ $id ];
	}

	/**
	 * The core font files a Font Manager record of this site also uses, mapped to the hashed name their families take, so
	 * the record keeps the file at the name. A record whose file is a known build of the core font (an adopted copy) isn't
	 * one. Reads the manifest only when a record's file has the name of a family's, and never hashes.
	 *
	 * @return array<string, string>
	 *
	 * @since 6.19.0
	 */
	public function get_record_collisions(): array {
		$this->get_sources();

		$blog = get_current_blog_id();
		if ( isset( $this->record_collisions[ $blog ] ) ) {
			return $this->record_collisions[ $blog ];
		}

		/* Guards the manifest reads below */
		$this->record_collisions[ $blog ] = [];

		$names = $this->get_record_file_names();
		if ( count( $names ) === 0 ) {
			return [];
		}

		$manifest = [];
		foreach ( array_keys( $this->get_sources() ) as $id ) {
			$manifest += $this->get_unaliased_manifest( (string) $id );
		}

		foreach ( $names as $file ) {
			$path = $this->get_plain_path( $file );
			if ( ! isset( $manifest[ $file ] ) || ! is_file( $path ) ) {
				continue;
			}

			if ( ! isset( $this->collect_builds( $file, $manifest[ $file ] )[ (int) filesize( $path ) ] ) ) {
				$this->record_collisions[ $blog ][ $file ] = $this->build_hashed_name( $file, $manifest[ $file ]['sha'] );
			}
		}

		return $this->record_collisions[ $blog ];
	}

	/**
	 * Whether a Font Manager record of this site uses the file, which then isn't the core font's
	 *
	 * @since 6.19.0
	 */
	public function is_record_file( string $file ): bool {
		return isset( $this->get_record_collisions()[ $file ] );
	}

	/**
	 * The files of this site's Font Manager records that a downloadable family uses too, by name
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_record_file_names(): array {
		$records = $this->custom_fonts->get_custom_fonts();
		if ( count( $records ) === 0 ) {
			return [];
		}

		$sources = $this->get_sources();

		if ( $this->family_files === null ) {
			$this->family_files = [];
			foreach ( $sources as $source ) {
				foreach ( empty( $source['manifest'] ) ? [] : $source['families'] as $family ) {
					foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
						if ( is_array( $family ) && is_string( $family[ $style ] ?? null ) ) {
							$this->family_files[ $family[ $style ] ] = true;
						}
					}
				}
			}
		}

		$names = [];
		foreach ( $records as $record ) {
			foreach ( [ 'regular', 'italics', 'bold', 'bolditalics' ] as $style ) {
				$file = is_string( $record[ $style ] ?? null ) ? basename( $record[ $style ] ) : '';
				if ( isset( $this->family_files[ $file ] ) ) {
					$names[ $file ] = $file;
				}
			}
		}

		return array_values( $names );
	}

	/**
	 * Whether every family's files are on the manifest
	 *
	 * @since 6.19.0
	 */
	protected function has_family_files( array $families, array $manifest ): bool {
		foreach ( $families as $family ) {
			if ( count( array_diff( $this->get_family_files( $family ), array_keys( $manifest ) ) ) > 0 ) {
				return false;
			}
		}

		return true;
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
	 * Where a manifest file goes in the fonts folder, under its own name
	 *
	 * @since 6.19.0
	 */
	public function get_plain_path( string $file ): string {
		return $this->data->template_font_location . $file;
	}

	/**
	 * The path mPDF should load for a manifest file: its copy under the hashed name if there is one, else the file at
	 * its own name, which may be missing. Stat calls only, no hashing.
	 *
	 * @since 6.19.0
	 */
	public function get_font_path( string $file ): string {
		return $this->has_hashed_copy( $file ) ? $this->get_plain_path( $this->get_hashed_name( $file ) ) : $this->get_plain_path( $file );
	}

	/**
	 * The name a manifest file takes when another file holds its own: `{stem}-{first 8 of its SHA-1}{ext}`, e.g.
	 * Garuda-0abddd35.ttf. Worked out from the manifest alone, so nothing stores it. '' for a file not on the manifest.
	 *
	 * @since 6.19.0
	 */
	public function get_hashed_name( string $file ): string {
		$entry = $this->get_manifest()[ $file ] ?? null;
		if ( $entry === null ) {
			return '';
		}

		/* A name the merge already gave a hash has no second one */
		$tag = '-' . substr( $entry['sha'], 0, 8 );
		if ( substr( pathinfo( $file, PATHINFO_FILENAME ), -strlen( $tag ) ) === $tag ) {
			return $file;
		}

		return $this->build_hashed_name( $file, $entry['sha'] );
	}

	/**
	 * @since 6.19.0
	 */
	protected function build_hashed_name( string $file, string $sha ): string {
		$info = pathinfo( $file );

		return $info['filename'] . '-' . substr( $sha, 0, 8 ) . ( isset( $info['extension'] ) ? '.' . $info['extension'] : '' );
	}

	/**
	 * Whether a file name is a manifest file's hashed name
	 *
	 * @since 6.19.0
	 */
	public function is_hashed_copy( string $name ): bool {
		if ( preg_match( '/^(.+)-([0-9a-f]{8})(\.[^.]+)$/i', $name, $match ) !== 1 ) {
			return false;
		}

		$entry = $this->get_manifest()[ $match[1] . $match[3] ] ?? null;

		return $entry !== null && strtolower( $match[2] ) === substr( $entry['sha'], 0, 8 );
	}

	/**
	 * Whether the file's copy under its hashed name is in the fonts folder
	 *
	 * @since 6.19.0
	 */
	public function has_hashed_copy( string $file ): bool {
		$hashed = $this->get_hashed_name( $file );

		return $hashed !== '' && $hashed !== $file && is_file( $this->get_plain_path( $hashed ) );
	}

	/**
	 * Whether a file is in the fonts folder at its name, which a render checks without reading the manifest
	 *
	 * @since 6.19.0
	 */
	public function is_downloaded( string $file ): bool {
		return is_file( $this->get_plain_path( $file ) );
	}

	/**
	 * Whether the file's own copy is in the fonts folder: its hashed copy, or the file at its name unless that is
	 * another font's by size (see is_size_conflict())
	 *
	 * @since 6.19.0
	 */
	public function is_installed( string $file ): bool {
		return $this->has_hashed_copy( $file ) || ( is_file( $this->get_plain_path( $file ) ) && ! $this->is_size_conflict( $file ) );
	}

	/**
	 * Whether the file at a manifest name is another font's going by its size alone, which is a stat call: no build
	 * the sources know of the file has that size. A file of a known size can still be another font's; only hashing
	 * shows that, so it's left to the background sync.
	 *
	 * @since 6.19.0
	 */
	public function is_size_conflict( string $file ): bool {
		$hashed = $this->get_hashed_name( $file );
		$path   = $this->get_plain_path( $file );

		/* With no other name to take, the file is the one that is used */
		if ( $hashed === '' || $hashed === $file ) {
			return false;
		}

		clearstatcache( true, $path );

		return is_file( $path ) && ! isset( $this->get_known_builds( $file )[ (int) filesize( $path ) ] );
	}

	/**
	 * The builds of a manifest file the sources know (the manifest's, the legacy manifests' and the sources' `legacy`
	 * hashes) as git blob SHA-1s by size
	 *
	 * @return array<int, string[]>
	 *
	 * @since 6.19.0
	 */
	public function get_known_builds( string $file ): array {
		return $this->collect_builds( $file, $this->get_manifest()[ $file ] ?? null );
	}

	/**
	 * @param array{url: string, size: int, sha: string}|null $entry The file's manifest entry
	 *
	 * @return array<int, string[]>
	 *
	 * @since 6.19.0
	 */
	protected function collect_builds( string $file, ?array $entry ): array {
		$builds = [];
		if ( $entry !== null ) {
			$builds[ $entry['size'] ][] = $entry['sha'];
		}

		foreach ( $this->get_sources() as $id => $source ) {
			$old = $this->get_legacy_manifest( (string) $id )[ $file ] ?? null;
			if ( $old !== null ) {
				$builds[ $old['size'] ][] = $old['sha'];
			}

			foreach ( empty( $source['manifest'] ) ? [] : (array) ( $source['legacy'] ?? [] ) as $sha => $size ) {
				$builds[ (int) $size ][] = (string) $sha;
			}
		}

		return $builds;
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

		$collisions = $this->get_record_collisions();

		foreach ( $this->get_sources() as $source ) {
			foreach ( $source['families'] as $key => $family ) {
				if ( ! isset( $fonts[ $key ] ) ) {
					unset( $family['name'], $family['listed'] );
					$fonts[ $key ] = $family;
				}

				/* mPDF's own data already has the core families */
				if ( is_array( $fonts[ $key ] ) && ! empty( $source['manifest'] ) ) {
					$fonts[ $key ] = $this->use_hashed_names( $fonts[ $key ], $collisions );
				}
			}
		}

		return $fonts;
	}

	/**
	 * A family with the files a Font Manager record uses renamed to their hashed names
	 *
	 * @param array<string, string> $collisions get_record_collisions()
	 *
	 * @since 6.19.0
	 */
	protected function use_hashed_names( array $family, array $collisions ): array {
		foreach ( count( $collisions ) === 0 ? [] : [ 'R', 'B', 'I', 'BI' ] as $style ) {
			if ( is_string( $family[ $style ] ?? null ) && isset( $collisions[ $family[ $style ] ] ) ) {
				$family[ $style ] = $collisions[ $family[ $style ] ];
			}
		}

		return $family;
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

		$known   = $this->get_known_files( $source, $this->get_source_manifest( $source_id ), $this->get_legacy_manifest( $source_id ) );
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
	 * mPDF's locale-to-font mapper, a seam for tests
	 *
	 * @since 6.19.0
	 */
	protected function get_language_mapper(): LanguageToFontInterface {
		return new LanguageToFont();
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
			'families'        => $this->get_mpdf_defaults()['fontdata'],
			'manifest'        => PDF_PLUGIN_DIR . 'fonts/core-fonts.php',
			/* The files the removed Core Font installer downloaded, before the core fonts were dehinted */
			'legacy_manifest' => PDF_PLUGIN_DIR . 'fonts/core-fonts-v1.0.0.php',
		];
	}

	/**
	 * Give a file name two sources use for different files (another SHA-1) its hashed name in the later source's manifest
	 * and families, so everything downstream sees a unique name. Identical files share one. The manifests are read here
	 * only when two sources have one; otherwise they're read lazily.
	 *
	 * @param array<string, array> $sources
	 *
	 * @return array<string, array>
	 *
	 * @since 6.19.0
	 */
	protected function rename_colliding_files( array $sources ): array {
		$downloadable = [];
		foreach ( $sources as $id => $source ) {
			if ( ! empty( $source['manifest'] ) ) {
				$downloadable[] = $id;
			}
		}

		if ( count( $downloadable ) < 2 ) {
			return $sources;
		}

		$taken = [];
		foreach ( $downloadable as $id ) {
			$renames  = [];
			$resolved = [];

			foreach ( $this->read_source_manifest( (string) $id, $sources[ $id ] ) as $file => $entry ) {
				$file = (string) $file;
				$name = $file;

				if ( isset( $taken[ $file ] ) && $taken[ $file ] !== $entry['sha'] ) {
					$name             = $this->build_hashed_name( $file, $entry['sha'] );
					$renames[ $file ] = $name;
					$this->log->notice(
						'Font file name used by two sources for different files',
						[
							'source' => $id,
							'name'   => $file,
							'as'     => $name,
						]
					);
				}

				$taken[ $name ]    = $taken[ $name ] ?? $entry['sha'];
				$resolved[ $name ] = $entry;
			}

			$this->manifests[ $id ] = $resolved;

			foreach ( $sources[ $id ]['families'] ?? [] as $key => $family ) {
				foreach ( $renames === [] || ! is_array( $family ) ? [] : [ 'R', 'B', 'I', 'BI' ] as $style ) {
					if ( is_string( $family[ $style ] ?? null ) && isset( $renames[ $family[ $style ] ] ) ) {
						$sources[ $id ]['families'][ $key ][ $style ] = $renames[ $family[ $style ] ];
					}
				}
			}
		}

		return $sources;
	}

	/**
	 * A source's manifest, keyed by file name. An added source's is all or nothing, so a bad build never half-installs: it
	 * is rejected whole if any entry is malformed or a family file is not on it.
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	protected function read_source_manifest( string $id, array $source ): array {
		$strict   = $source !== [] && ! in_array( $id, self::RESERVED_SOURCES, true );
		$manifest = $this->read_manifest_file( $source['manifest'] ?? '', $strict );

		if ( $strict && count( $manifest ) > 0 && ! $this->has_family_files( $source['families'], $manifest ) ) {
			$this->log->error( 'Font manifest rejected: a family file is not on it', [ 'manifest' => $source['manifest'] ] );

			return [];
		}

		return $manifest;
	}

	/**
	 * A manifest file's entries, keyed by file name, skipping malformed ones
	 *
	 * @param mixed $path
	 * @param bool  $strict Read none if any entry is malformed
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	protected function read_manifest_file( $path, bool $strict = false ): array {
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

		if ( $strict && $manifest !== $entries ) {
			$this->log->error( 'Font manifest rejected: an entry is invalid', [ 'manifest' => $path ] );

			return [];
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
	 * A plain file name with an https:// URL, and a git blob SHA-1 with a positive size
	 *
	 * @param mixed      $entry
	 * @param int|string $file
	 *
	 * @since 6.19.0
	 */
	protected function is_manifest_entry( $entry, $file ): bool {
		return $this->is_file_name( $file )
			&& is_array( $entry )
			&& is_string( $entry['url'] ?? null ) && strpos( $entry['url'], 'https://' ) === 0
			&& $this->is_blob( $entry['sha'] ?? null, $entry['size'] ?? null );
	}

	/**
	 * A source's manifest, legacy manifest and `legacy` git blob SHA-1s, keyed by file size
	 *
	 * @return array<int, string[]>
	 *
	 * @since 6.19.0
	 */
	protected function get_known_files( array $source, array $source_manifest, array $legacy_manifest ): array {
		$known = [];
		foreach ( [ $source_manifest, $legacy_manifest ] as $manifest ) {
			foreach ( $manifest as $file ) {
				$known[ $file['size'] ][] = $file['sha'];
			}
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

			/* mPDF finds a font by its file name in the fonts folder, so check the copy there, not a hashed one */
			$path = $this->get_plain_path( basename( $file ) );
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
