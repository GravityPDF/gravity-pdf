<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * The registered font sources, and the entry schema every source shares
 *
 * The built-in records pass through `gfpdf_font_sources` so an add-on or a self-hosted library can register its own
 * root; its rows land in the same catalog table. Nothing here queries: per-source sync state is the option record
 * `Catalog_Sync` writes, and counts come from the table.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Sources {

	/**
	 * Ids no source may take: the first two are `gravitypdf_font.source` values for rows no source owns, the third
	 * is the sync route's own path segment
	 *
	 * @since 7.0
	 */
	public const RESERVED_IDS = [ 'custom', 'imported', 'sync' ];

	/**
	 * Filenames are a single path segment with a known extension; `remote_path` and `preview` are these joined by `/`
	 *
	 * @since 7.0
	 */
	public const FILENAME_PATTERN = '/^[A-Za-z0-9._\-]+$/';

	/**
	 * @since 7.0
	 */
	public const FILE_EXTENSIONS = [ 'ttf', 'otf', 'txt', 'dat', 'woff2' ];

	/**
	 * Font keys and variant ids, the one charset that may carry an underscore
	 *
	 * @since 7.0
	 */
	public const KEY_PATTERN = '/^[a-z0-9_\-]+$/';

	/**
	 * Keys inside a `fonts` entry that name something other than a face role
	 *
	 * @since 7.0
	 */
	public const NON_ROLE_KEYS = [ 'useOTL', 'useKashida', 'sip-ext' ];

	/**
	 * The pack labels, translated
	 *
	 * Keyed `{source}/{entry}/{field}`. A pack the server adds before the plugin ships its translation falls back to
	 * the index string, so this map never has to be complete. Entry descriptions are pipeline-generated and
	 * translated as they are published, hence labels only for now.
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	public static function get_translations(): array {
		static $translations = null;

		if ( $translations !== null ) {
			return $translations;
		}

		$translations = [
			'packs/emoji/label'               => __( 'Emoji', 'gravity-pdf' ),
			'packs/serif-mono/label'          => __( 'Serif & Monospace', 'gravity-pdf' ),
			'packs/popular-sans/label'        => __( 'Popular Sans Serif', 'gravity-pdf' ),
			'packs/popular-serif/label'       => __( 'Popular Serif', 'gravity-pdf' ),
			'packs/popular-mono/label'        => __( 'Popular Monospace', 'gravity-pdf' ),
			'packs/popular-cursive/label'     => __( 'Popular Cursive', 'gravity-pdf' ),
			'packs/dejavu/label'              => __( 'Extended Latin', 'gravity-pdf' ),
			'packs/chinese-simplified/label'  => __( 'Chinese (Simplified)', 'gravity-pdf' ),
			'packs/japanese/label'            => __( 'Japanese', 'gravity-pdf' ),
			'packs/chinese-traditional/label' => __( 'Chinese (Traditional)', 'gravity-pdf' ),
			'packs/cjk-ext-b/label'           => __( 'CJK Extension B', 'gravity-pdf' ),
			'packs/indic/label'               => __( 'Indic scripts', 'gravity-pdf' ),
			'packs/arabic/label'              => __( 'Arabic', 'gravity-pdf' ),
			'packs/korean/label'              => __( 'Korean', 'gravity-pdf' ),
			'packs/african/label'             => __( 'African scripts', 'gravity-pdf' ),
			'packs/hebrew-syriac/label'       => __( 'Hebrew & Syriac', 'gravity-pdf' ),
			'packs/southeast-asian/label'     => __( 'Southeast Asian', 'gravity-pdf' ),
			'packs/americas/label'            => __( 'Americas', 'gravity-pdf' ),
			'packs/ancient-scripts/label'     => __( 'Ancient scripts', 'gravity-pdf' ),
			'packs/barcode/label'             => __( 'Barcode', 'gravity-pdf' ),
		];

		return $translations;
	}

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @var Font_Source[]|null Keyed by source id; built once per request
	 * @since 7.0
	 */
	protected $sources;

	public function __construct( LoggerInterface $log ) {
		$this->log = $log;
	}

	/**
	 * Every registered source, keyed by id
	 *
	 * Core records go in first, so a filter cannot displace one by re-using its id.
	 *
	 * @return Font_Source[]
	 *
	 * @since 7.0
	 */
	public function all(): array {
		if ( $this->sources !== null ) {
			return $this->sources;
		}

		$sources = [];
		foreach ( $this->get_built_in() as $source ) {
			$sources[ $source->get_id() ] = $source;
		}

		/**
		 * Register a font source of your own
		 *
		 * @param Font_Source[] $sources
		 *
		 * @since 7.0
		 */
		$filtered = apply_filters( 'gfpdf_font_sources', array_values( $sources ) );

		foreach ( is_array( $filtered ) ? $filtered : [] as $source ) {
			if ( ! $source instanceof Font_Source ) {
				continue;
			}

			$id = $source->get_id();

			if ( isset( $sources[ $id ] ) ) {
				/* Core's own records land here on every call and are not worth logging */
				if ( $sources[ $id ] !== $source ) {
					$this->log->warning( 'Ignoring a font source whose id is already registered', [ 'source' => $id ] );
				}

				continue;
			}

			if ( in_array( $id, static::RESERVED_IDS, true ) || preg_match( Font_Source::ID_PATTERN, $id ) !== 1 ) {
				$this->log->warning( 'Ignoring a font source with a reserved or malformed id', [ 'source' => $id ] );

				continue;
			}

			$sources[ $id ] = $source;
		}

		$this->sources = $sources;

		return $this->sources;
	}

	/**
	 * @since 7.0
	 */
	public function get( string $id ): ?Font_Source {
		return $this->all()[ $id ] ?? null;
	}

	/**
	 * The records the plugin ships
	 *
	 * Both built-ins share the fonts root and send no query args: nothing on that host can read them — there is no
	 * Worker, and R2 cannot — so args would only fragment the CDN cache per site.
	 *
	 * @return Font_Source[]
	 *
	 * @since 7.0
	 */
	protected function get_built_in(): array {
		return [
			new Font_Source(
				'packs',
				__( 'Language packs', 'gravity-pdf' ),
				static::get_root_url(),
				[],
				__( 'Font packs maintained by Gravity PDF, each covering the scripts of one language or region. Files are downloaded from fonts.gravitypdf.com and verified against the signed index before they are installed.', 'gravity-pdf' )
			),
		];
	}

	/**
	 * The fonts root every built-in record points at
	 *
	 * @since 7.0
	 */
	public static function get_root_url(): string {
		/**
		 * Point font downloads at a mirror of the fonts root
		 *
		 * @param string $url
		 *
		 * @since 7.0
		 */
		return (string) apply_filters( 'gfpdf_font_download_base_url', GPDF_FONTS_URL );
	}

	/**
	 * The UI string for one field of one catalog entry
	 *
	 * Applied at the presentation points only, never in `Catalog_Sync` or the columns: the stored catalog stays
	 * byte-identical to the index. Other sources' values pass through — a Google family name is a proper noun.
	 *
	 * @since 7.0
	 */
	public static function translate_entry( string $source, string $entry, string $field, string $value ): string {
		return static::get_translations()[ "{$source}/{$entry}/{$field}" ] ?? $value;
	}

	/**
	 * Where a source's installed files live, relative to the fonts directory
	 *
	 * Source installs are namespaced, so a download can never overwrite a user upload or an import: those stay flat
	 * in the fonts-dir root, and a directory cannot clash with a `.ttf`. `Font_Installer` writes here and
	 * `Catalog_Font_Adopter` looks here, so the layout is stated once — a divergence would not error, adoption
	 * would just silently stop finding installed files.
	 *
	 * @since 7.0
	 */
	public static function install_dir( string $source, string $entry ): string {
		return $source . '/' . $entry . '/';
	}

	/**
	 * @since 7.0
	 */
	public static function install_path( string $source, string $entry, string $filename ): string {
		return static::install_dir( $source, $entry ) . $filename;
	}

	/**
	 * The font row one key of one entry describes, files aside
	 *
	 * The other half of the `coverage_meta()` contract: the two writers of source-installed rows —
	 * `Catalog_Font_Adopter` for files already on disk and `Font_Installer` for files it downloads — have to agree
	 * on every column, not just `meta`, or the same pack means different things depending on how it arrived.
	 * The adopter adds `files`; the installer overlays `font_key` and `label` for a display entry, where the caller
	 * chooses both.
	 *
	 * @param array $row   The catalog row
	 * @param array $entry The decoded `entry` object
	 *
	 * @since 7.0
	 */
	public static function font_row( array $row, array $entry, string $font_key ): array {
		$fonts    = (array) ( $entry['fonts'] ?? [] );
		$roles    = (array) ( $fonts[ $font_key ] ?? [] );
		$coverage = (int) ( $row['coverage'] ?? 0 ) === 1;

		return [
			'font_key'    => $font_key,
			/* A multi-font pack labels each row by its key: one shared label across four CJK fonts helps nobody */
			'label'       => count( $fonts ) === 1 ? (string) $row['label'] : $font_key,
			'source'      => (string) $row['source'],
			'entry'       => (string) $row['entry'],
			'coverage'    => (int) $coverage,
			'meta'        => $coverage ? static::coverage_meta( $font_key, $entry, $roles ) : [],
			'version'     => $row['version'] ?? null,
			'use_otl'     => (int) ( $roles['useOTL'] ?? 0 ),
			'use_kashida' => (int) ( $roles['useKashida'] ?? 0 ),
		];
	}

	/**
	 * One font key's share of its entry's coverage maps
	 *
	 * Copied onto the font row so the registry builds every mPDF fallback array from the rows alone, without
	 * opening a source or decoding an entry on the load path. Shared by the two writers of coverage rows —
	 * `Catalog_Font_Adopter` for files already on disk and `Font_Installer` for files it downloads — which must
	 * agree exactly, or the same pack would mean different things depending on how it arrived.
	 *
	 * @param array $entry The decoded `entry` object
	 * @param array $roles That font key's role map, for the `sip-ext` supplement
	 *
	 * @since 7.0
	 */
	public static function coverage_meta( string $font_key, array $entry, array $roles ): array {
		$languages = [];
		foreach ( (array) ( $entry['language_to_font'] ?? [] ) as $code => $target ) {
			if ( $target === $font_key ) {
				$languages[] = (string) $code;
			}
		}

		$families = [];
		foreach ( (array) ( $entry['family_substitution'] ?? [] ) as $family => $keys ) {
			if ( in_array( $font_key, (array) $keys, true ) ) {
				$families[] = (string) $family;
			}
		}

		$meta = [
			'backup_subs'         => in_array( $font_key, (array) ( $entry['backup_subs_fonts'] ?? [] ), true ),
			'bmp'                 => in_array( $font_key, (array) ( $entry['bmp_fonts'] ?? [] ), true ),
			'family_substitution' => $families,
			'languages'           => $languages,
		];

		if ( isset( $roles['sip-ext'] ) && is_string( $roles['sip-ext'] ) ) {
			$meta['sip_ext'] = $roles['sip-ext'];
		}

		return $meta;
	}

	/**
	 * Whether one index entry is safe to store and install from
	 *
	 * Runs at both boundaries — mirroring an index in `Catalog_Sync` and decoding any `entry_json` in
	 * `Font_Installer` — because entry files are fetched lazily and a third-party source bypasses our pipeline: the
	 * signed root proves provenance, not content safety.
	 *
	 * @param array $entry The decoded `entry` object, not the index row around it
	 *
	 * @return string|null The reason it was rejected, or null when it is valid
	 *
	 * @since 7.0
	 */
	public static function validate_entry( array $entry ): ?string {
		$files = $entry['files'] ?? [];

		if ( ! is_array( $files ) || count( $files ) === 0 ) {
			return 'the entry lists no files';
		}

		foreach ( $files as $filename => $file ) {
			if ( ! is_string( $filename ) || ! static::is_valid_filename( $filename ) ) {
				return sprintf( 'file name "%s" is not a plain filename with a known extension', (string) $filename );
			}

			if ( ! is_array( $file ) || ! isset( $file['remote_path'] ) || ! is_string( $file['remote_path'] ) ) {
				return sprintf( 'file "%s" has no remote_path', $filename );
			}

			if ( ! static::is_valid_relative_path( $file['remote_path'] ) ) {
				return sprintf( 'remote_path "%s" escapes the files directory', $file['remote_path'] );
			}
		}

		if ( isset( $entry['preview'] ) && ( ! is_string( $entry['preview'] ) || ! static::is_valid_relative_path( $entry['preview'] ) ) ) {
			return 'preview is not a relative path below the files directory';
		}

		$error = static::validate_fonts( $entry['fonts'] ?? [], $files );
		if ( $error !== null ) {
			return $error;
		}

		foreach ( (array) ( $entry['variants'] ?? [] ) as $variant => $filename ) {
			/*
			 * Cast, don't `is_string()`: the pipeline's variant ids are weights (`400`, `700`) and PHP turns a
			 * numeric JSON object key into an integer, so an `is_string()` gate would reject every upright weight
			 * the `google` source publishes while passing every italic (`400i`).
			 */
			$variant = (string) $variant;

			if ( preg_match( static::KEY_PATTERN, $variant ) !== 1 ) {
				return sprintf( 'variant id "%s" is not a valid key', $variant );
			}

			if ( ! is_string( $filename ) || ! isset( $files[ $filename ] ) ) {
				return sprintf( 'variant "%s" names a file the entry does not list', $variant );
			}
		}

		/* Coverage maps point at font keys, which mPDF joins straight into its font cache path */
		foreach ( (array) ( $entry['language_to_font'] ?? [] ) as $key ) {
			if ( ! is_string( $key ) || preg_match( static::KEY_PATTERN, $key ) !== 1 ) {
				return sprintf( 'language_to_font names an invalid font key "%s"', (string) $key );
			}
		}

		return null;
	}

	/**
	 * The `fonts` map: mPDF key → role → filename, with useOTL / useKashida / sip-ext beside the roles
	 *
	 * @param array $files The entry's own file list, so a role cannot name a file that will never be downloaded
	 *
	 * @since 7.0
	 */
	protected static function validate_fonts( array $fonts, array $files ): ?string {
		if ( count( $fonts ) === 0 ) {
			return 'the entry registers no fonts';
		}

		foreach ( $fonts as $font_key => $roles ) {
			/* Cast for the same reason as the variant ids above: an all-digit key arrives as an integer */
			$font_key = (string) $font_key;

			if ( preg_match( static::KEY_PATTERN, $font_key ) !== 1 ) {
				return sprintf( 'font key "%s" is not a valid key', $font_key );
			}

			if ( ! is_array( $roles ) ) {
				return sprintf( 'font "%s" has no roles', $font_key );
			}

			foreach ( $roles as $role => $value ) {
				/* `sip-ext` names a font key, not a file, and may point outside this entry */
				if ( $role === 'sip-ext' ) {
					if ( ! is_string( $value ) || preg_match( static::KEY_PATTERN, $value ) !== 1 ) {
						return sprintf( 'font "%s" has an invalid sip-ext target', $font_key );
					}

					continue;
				}

				if ( in_array( $role, static::NON_ROLE_KEYS, true ) ) {
					continue;
				}

				if ( ! is_string( $value ) || ! isset( $files[ $value ] ) ) {
					return sprintf( 'font "%s" role "%s" names a file the entry does not list', $font_key, (string) $role );
				}
			}
		}

		return null;
	}

	/**
	 * @since 7.0
	 */
	protected static function is_valid_filename( string $filename ): bool {
		if ( $filename !== basename( $filename ) || strpos( $filename, '.' ) === 0 ) {
			return false;
		}

		if ( preg_match( static::FILENAME_PATTERN, $filename ) !== 1 ) {
			return false;
		}

		return in_array( strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ), static::FILE_EXTENSIONS, true );
	}

	/**
	 * A `/`-separated path below the root's files directory: every segment a valid filename, no `..`, no leading `/`
	 *
	 * @since 7.0
	 */
	protected static function is_valid_relative_path( string $path ): bool {
		if ( $path === '' || strpos( $path, '/' ) === 0 ) {
			return false;
		}

		$segments = explode( '/', $path );
		$last     = array_pop( $segments );

		/* The leading-dot ban is what rules out a `..` segment, so traversal never needs its own check */
		foreach ( $segments as $segment ) {
			if ( strpos( $segment, '.' ) === 0 || preg_match( static::FILENAME_PATTERN, $segment ) !== 1 ) {
				return false;
			}
		}

		return static::is_valid_filename( $last );
	}
}
