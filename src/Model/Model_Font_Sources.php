<?php

declare( strict_types=1 );

namespace GFPDF\Model;

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
 * The fonts Gravity PDF provides: the bundled Arimo and DejaVu symbol faces, and mPDF's core fonts
 *
 * A source has `families` (mPDF font data, plus an optional `name` and `listed`), and optionally a bundled `dir`, a
 * `manifest` of its downloadable files, a font list `group`, and `legacy` (git blob SHA-1 => size) or a
 * `legacy_manifest` of other copies of its files, which adopt_records() accepts.
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
	 * A downloadable file above this size is never fetched mid-render, only by the background sync
	 *
	 * @since 6.19.0
	 */
	public const LARGE_FILE_SIZE = 5 * MB_IN_BYTES;

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
	 * @var array<string, array{languages: string[], sample?: string}>|null What each core font supports, once read
	 * @since 6.19.0
	 */
	protected $font_languages;

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
	 * Core font files a Font Manager record also uses (see get_record_collisions()), by the blog ID they're read on
	 *
	 * @var array<int, array<string, string>>
	 * @since 6.19.0
	 */
	protected $record_collisions = [];

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
			$this->sources = $this->rename_colliding_files(
				[
					'bundled' => $this->get_bundled_source(),
					'core'    => $this->get_core_source(),
				]
			);
		}

		return $this->sources;
	}

	/**
	 * The custom fonts by ID
	 *
	 * @return array<string, array>
	 *
	 * @since 6.19.0
	 */
	public function get_custom_fonts(): array {
		return $this->custom_fonts->get_custom_fonts();
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
	 * What each core font supports, by font key
	 *
	 * @return array<string, array{languages: string[], sample?: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_font_languages(): array {
		if ( $this->font_languages === null ) {
			$this->font_languages = ( new Model_Font_Languages() )->get_font_languages();
		}

		return $this->font_languages;
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
	 * mPDF's backupSubsFont, Arimo first. mPDF reads which characters a font has from its ranges file and loads only a font
	 * that has them; the finder queues a large one (Sun-ExtA) for the background sync rather than download it mid-render.
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
			[ 'sun-exta' ]
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
		if ( $this->manifest === null ) {
			$this->manifest = [];
			foreach ( array_keys( $this->get_sources() ) as $id ) {
				$this->manifest += $this->get_source_manifest( (string) $id );
			}
		}

		return $this->manifest;
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
		/* Building the sources reads the manifests of those that share a file name with another */
		$sources = $this->get_sources();

		if ( ! isset( $this->manifests[ $id ] ) ) {
			$this->manifests[ $id ] = $this->read_manifest( $sources[ $id ] ?? [] );
		}

		return $this->manifests[ $id ];
	}

	/**
	 * The core font files a Font Manager record of any site also uses, mapped to the hashed name their families take.
	 * A record whose file is a known build of the core font (an adopted copy) isn't one. Never hashes.
	 *
	 * @return array<string, string>
	 *
	 * @since 6.19.0
	 */
	public function get_record_collisions(): array {
		$blog = get_current_blog_id();
		if ( isset( $this->record_collisions[ $blog ] ) ) {
			return $this->record_collisions[ $blog ];
		}

		$collisions = [];
		$names      = $this->get_record_file_names();
		$manifest   = count( $names ) > 0 ? $this->get_manifest() : [];

		foreach ( $names as $file ) {
			$path = $this->get_plain_path( $file );
			if ( ! isset( $manifest[ $file ] ) || ! is_file( $path ) ) {
				continue;
			}

			if ( ! isset( $this->get_known_builds( $file )[ (int) filesize( $path ) ] ) ) {
				$collisions[ $file ] = $this->build_hashed_name( $file, $manifest[ $file ]['sha'] );
			}
		}

		$this->record_collisions[ $blog ] = $collisions;

		return $collisions;
	}

	/**
	 * Whether a Font Manager record of any site uses the file, which then isn't the core font's
	 *
	 * @since 6.19.0
	 */
	public function is_record_file( string $file ): bool {
		return isset( $this->get_record_collisions()[ $file ] );
	}

	/**
	 * The files the other sites' Font Manager records use. Every site shares the fonts folder.
	 *
	 * @return string[]|null Null when a site's fonts couldn't be read, so any file could be its
	 *
	 * @since 6.19.0
	 */
	public function get_files_used_by_other_sites(): ?array {
		return $this->custom_fonts->get_files_used_by_other_sites();
	}

	/**
	 * A site's Font Manager record files by font ID
	 *
	 * @return array<string, string[]>
	 *
	 * @since 6.19.0
	 */
	public function get_site_font_files( int $site_id ): array {
		return $this->custom_fonts->get_site_font_files( $site_id );
	}

	/**
	 * The files of Font Manager records that a downloadable family uses too, by name. Every site shares the fonts
	 * folder, so other sites' records count. A site not recorded yet would cost a query per PDF, so it's left to the
	 * sync, and core font deletes check it themselves.
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_record_file_names(): array {
		$files = $this->custom_fonts->get_network_font_files();
		if ( count( $files ) === 0 ) {
			return [];
		}

		$family_files = [];
		foreach ( $this->get_sources() as $source ) {
			foreach ( empty( $source['manifest'] ) ? [] : $source['families'] as $family ) {
				foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
					if ( is_array( $family ) && is_string( $family[ $style ] ?? null ) ) {
						$family_files[ $family[ $style ] ] = true;
					}
				}
			}
		}

		return array_map( 'strval', array_keys( array_intersect_key( $files, $family_files ) ) );
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
	 * Whether a downloadable file is above LARGE_FILE_SIZE
	 *
	 * @since 6.19.0
	 */
	public function is_large_file( string $file ): bool {
		return ( $this->get_manifest()[ $file ]['size'] ?? 0 ) > self::LARGE_FILE_SIZE;
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
	 * The name a manifest file takes when another file holds its own: `{stem}-{first 8 of its SHA-1}{ext}`, e.g.
	 * Garuda-0abddd35.ttf. '' when it can't take one: it isn't on the manifest, or rename_colliding_files() already
	 * hashed its name.
	 *
	 * @since 6.19.0
	 */
	public function get_hashed_name( string $file ): string {
		$entry = $this->get_manifest()[ $file ] ?? null;
		if ( $entry === null ) {
			return '';
		}

		$tag = '-' . substr( $entry['sha'], 0, 8 );
		if ( substr( pathinfo( $file, PATHINFO_FILENAME ), -strlen( $tag ) ) === $tag ) {
			return '';
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
	 * The manifest file name and SHA-1 prefix a `{stem}-{sha}{ext}` name holds
	 *
	 * @return array{file: string, sha: string}|null Null for any other name
	 *
	 * @since 6.19.0
	 */
	public function parse_hashed_name( string $name ): ?array {
		if ( preg_match( '/^(.+)-([0-9a-f]{8})(\.[^.]+)$/i', $name, $match ) !== 1 ) {
			return null;
		}

		return [
			'file' => $match[1] . $match[3],
			'sha'  => strtolower( $match[2] ),
		];
	}

	/**
	 * Whether a file name is one a manifest file could take as its hashed name, in any case and for any release's SHA-1
	 *
	 * @since 6.19.0
	 */
	public function is_reserved_name( string $name ): bool {
		$parsed = $this->parse_hashed_name( $name );

		return $parsed !== null && isset( array_change_key_case( $this->get_manifest() )[ strtolower( $parsed['file'] ) ] );
	}

	/**
	 * Whether the file's copy under its hashed name is in the fonts folder, going by its size
	 *
	 * @since 6.19.0
	 */
	protected function has_hashed_copy( string $file ): bool {
		$hashed = $this->get_hashed_name( $file );
		if ( $hashed === '' ) {
			return false;
		}

		$path = $this->get_plain_path( $hashed );

		return is_file( $path ) && (int) filesize( $path ) === (int) $this->get_manifest()[ $file ]['size'];
	}

	/**
	 * The path of a manifest file's own copy in the fonts folder: its hashed copy, else the file at its name unless that
	 * is another font's by size (see is_size_conflict()). '' when neither is. Stat calls only, no hashing.
	 *
	 * @since 6.19.0
	 */
	public function get_own_copy( string $file ): string {
		if ( $this->has_hashed_copy( $file ) ) {
			return $this->get_plain_path( $this->get_hashed_name( $file ) );
		}

		$path = $this->get_plain_path( $file );

		return is_file( $path ) && ! $this->is_size_conflict( $file ) ? $path : '';
	}

	/**
	 * The manifest file a font data file name stands for: the name itself, or the file whose hashed name it is, which
	 * register_font_data() gives a family sharing a file name with a Font Manager record. '' for any other name.
	 *
	 * @since 6.19.0
	 */
	public function get_manifest_file( string $name ): string {
		if ( isset( $this->get_manifest()[ $name ] ) ) {
			return $name;
		}

		$parsed = $this->parse_hashed_name( $name );
		$entry  = $parsed !== null ? $this->get_manifest()[ $parsed['file'] ] ?? null : null;

		return $entry !== null && $parsed['sha'] === substr( $entry['sha'], 0, 8 ) ? $parsed['file'] : '';
	}

	/**
	 * Whether the file at a manifest name is another font's going by its size alone: no build the sources know has it
	 *
	 * @since 6.19.0
	 */
	protected function is_size_conflict( string $file ): bool {
		$path = $this->get_plain_path( $file );

		/* With no other name to take, the file is the one that is used */
		if ( $this->get_hashed_name( $file ) === '' || ! is_file( $path ) ) {
			return false;
		}

		$size = (int) filesize( $path );

		/* The usual case needs no legacy manifest */
		return $size !== (int) $this->get_manifest()[ $file ]['size'] && ! isset( $this->get_known_builds( $file )[ $size ] );
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
		$entry  = $this->get_manifest()[ $file ] ?? null;
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
	 * A family with the given files renamed to their hashed names
	 *
	 * @param array<string, string> $collisions Hashed names by file name
	 *
	 * @since 6.19.0
	 */
	protected function use_hashed_names( array $family, array $collisions ): array {
		foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
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
		$fonts = is_array( $fonts ) ? $fonts : [];

		foreach ( $this->get_sources() as $source ) {
			if ( empty( $source['group'] ) ) {
				continue;
			}

			$list = [];
			foreach ( $this->get_listed_families( $source ) as $key => $family ) {
				$list[ $key ] = $family['name'] ?? $key;
			}

			$group                     = $fonts[ $source['group'] ] ?? [];
			$fonts[ $source['group'] ] = $list + ( is_array( $group ) ? $group : [] );
		}

		return $fonts;
	}

	/**
	 * A source's families that users can choose: not hidden, and not replaced by a custom font of the same key
	 *
	 * @param array $source A get_sources() value
	 *
	 * @return array<string, array>
	 *
	 * @since 6.19.0
	 */
	public function get_listed_families( array $source ): array {
		$custom_fonts = $this->custom_fonts->get_custom_fonts();

		return array_filter(
			$source['families'] ?? [],
			static function ( $family, $key ) use ( $custom_fonts ): bool {
				return is_array( $family ) && ( $family['listed'] ?? true ) && ! isset( $custom_fonts[ $key ] );
			},
			ARRAY_FILTER_USE_BOTH
		);
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

		$known   = $this->get_known_files( $source, $this->get_legacy_manifest( $source_id ) );
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
	 * Remove a custom font record and the files no other site uses. Logs and returns false on failure
	 *
	 * @since 6.19.0
	 */
	protected function remove_record( string $id ): bool {
		$error = '';

		try {
			if ( GPDFAPI::delete_pdf_font( $id ) === true ) {
				return true;
			}
		} catch ( Throwable $e ) {
			$error = $e->getMessage();
		}

		$this->log->error(
			'Could not remove a custom font record replaced by a font source',
			[
				'font'      => $id,
				'exception' => $error,
			]
		);

		return false;
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
	 * Give a file name two sources use for different files its hashed name in the later source's manifest and families
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

			foreach ( $this->read_manifest( $sources[ $id ] ) as $file => $entry ) {
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

			foreach ( array_filter( $sources[ $id ]['families'] ?? [], 'is_array' ) as $key => $family ) {
				$sources[ $id ]['families'][ $key ] = $this->use_hashed_names( $family, $renames );
			}
		}

		return $sources;
	}

	/**
	 * A source's manifest, keyed by file name, skipping malformed entries
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	protected function read_manifest( array $source ): array {
		return $this->read_manifest_file( $source['manifest'] ?? '' );
	}

	/**
	 * A manifest file's entries, keyed by file name, skipping malformed ones
	 *
	 * @param mixed $path
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	protected function read_manifest_file( $path ): array {
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
	 * A source's manifest, legacy manifest and `legacy` git blob SHA-1s, keyed by file size
	 *
	 * @return array<int, string[]>
	 *
	 * @since 6.19.0
	 */
	protected function get_known_files( array $source, array $legacy_manifest ): array {
		$known = [];
		foreach ( [ $this->read_manifest( $source ), $legacy_manifest ] as $manifest ) {
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
