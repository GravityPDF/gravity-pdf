<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF_Vendor\Mpdf\Fonts\FontRegistry;
use GFPDF_Vendor\Mpdf\Language\LanguageToFontRegistry;
use GFPDF_Vendor\Mpdf\Ucdn;
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
 * Builds everything mPDF and the Font Manager need to know about fonts
 *
 * Both come from one read of the font tables, so what renders and what the UI lists cannot drift. Nothing here
 * touches the filesystem, and nothing on the render path constructs `Font_Sources` — its filter runs third-party
 * code a render must not depend on. `Catalog_Repository` is held for the install statuses alone, which only the
 * admin surfaces ask for; no method mPDF reaches touches it.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Registry {

	/**
	 * The bundled font keys, which no catalogue entry may claim
	 *
	 * @since 7.0
	 */
	public const BUNDLED_FONT = 'gfpdf-arimo';

	/**
	 * Substitution-only: never offered as a document font
	 *
	 * @since 7.0
	 */
	public const BUNDLED_SYMBOLS = 'gfpdf-dejavu-symbols';

	/**
	 * The bundled font's four faces, which are also what a missing file is substituted with
	 *
	 * @since 7.0
	 */
	public const BUNDLED_FACES = [
		'R'  => 'Arimo-Regular.ttf',
		'B'  => 'Arimo-Bold.ttf',
		'I'  => 'Arimo-Italic.ttf',
		'BI' => 'Arimo-BoldItalic.ttf',
	];

	/**
	 * The bundled map, before the installed rows overlay it
	 *
	 * Just Latin. This row is what sends Latin text inside a `zh` or `ar` document to Arimo rather than the pack
	 * font's Latin glyphs. Latin-*language* rows are deliberately absent — an explicit `lang` attribute would flip
	 * words out of the chosen font mid-paragraph — as are Greek, Cyrillic, Hebrew, Armenian and Georgian, which
	 * mPDF happily scavenges per glyph from `backupSubsFont`.
	 *
	 * @since 7.0
	 */
	public const DEFAULT_LANGUAGE_MAP = [ 'und-latn' => self::BUNDLED_FONT ];

	/**
	 * Language or script tag => the document script it implies
	 *
	 * Anything not listed is Latin. Replaces mPDF's `mode`, which is a composite parser with a constructor font
	 * lookup nothing may rely on.
	 *
	 * @since 7.0
	 */
	public const LANGUAGE_TO_SCRIPT = [
		'ja' => Ucdn::SCRIPT_HAN,
		'zh' => Ucdn::SCRIPT_HAN,
		'ko' => Ucdn::SCRIPT_HANGUL,
		'ar' => Ucdn::SCRIPT_ARABIC,
		'fa' => Ucdn::SCRIPT_ARABIC,
		'ur' => Ucdn::SCRIPT_ARABIC,
		'ps' => Ucdn::SCRIPT_ARABIC,
		'sd' => Ucdn::SCRIPT_ARABIC,
		'hi' => Ucdn::SCRIPT_DEVANAGARI,
		'mr' => Ucdn::SCRIPT_DEVANAGARI,
		'ne' => Ucdn::SCRIPT_DEVANAGARI,
		'th' => Ucdn::SCRIPT_THAI,
		'he' => Ucdn::SCRIPT_HEBREW,
	];

	/**
	 * A live entry whose phase has not moved for this long is worth re-dispatching
	 *
	 * Two of the poller's slowest intervals (§4.6 Store): long enough that a batch which simply has not been picked
	 * up yet is never nudged, short enough that a dead loopback costs one poll rather than the five-minute
	 * healthcheck.
	 *
	 * @since 7.0
	 */
	public const NUDGE_AFTER = 60;

	/**
	 * ...and this long is stuck: the UI stops polling and offers Retry
	 *
	 * @since 7.0
	 */
	public const STUCK_AFTER = 15 * MINUTE_IN_SECONDS;

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
	 * @var Helper_Abstract_Options
	 * @since 7.0
	 */
	protected $options;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @var string Absolute path to the plugin's bundled fonts directory
	 * @since 7.0
	 */
	protected $bundled_dir;

	/**
	 * @var array{stamp: string, package: Package}|null The installed layer this request has already built
	 * @since 7.0
	 */
	protected $memo;

	public function __construct(
		Font_Repository $repository,
		Catalog_Repository $catalog,
		Helper_Abstract_Options $options,
		LoggerInterface $log,
		string $bundled_dir
	) {
		$this->repository  = $repository;
		$this->catalog     = $catalog;
		$this->options     = $options;
		$this->log         = $log;
		$this->bundled_dir = trailingslashit( $bundled_dir );
	}

	/**
	 * Whether a trigger may install fonts on its own
	 *
	 * The one conversion point for `auto_install_fonts`. It is stored `'Yes'`/`'No'` because a stored `false` would
	 * be deleted by `update_option()` and resurrected by `get_option()`'s fallback, so the string form exists at the
	 * storage boundary and nowhere else — every consumer deals in `true`/`false`. The constant locks the setting
	 * (the UI disables it); the filter is the add-on hook and defers to a stored choice.
	 *
	 * @since 7.0
	 */
	public function auto_install_enabled(): bool {
		if ( defined( 'GPDF_AUTO_INSTALL_FONTS' ) ) {
			return (bool) GPDF_AUTO_INSTALL_FONTS;
		}

		return (bool) apply_filters( 'gfpdf_auto_install_fonts', $this->options->get_option( 'auto_install_fonts', 'Yes' ) === 'Yes' );
	}

	/**
	 * Every mPDF config key that decides which font a run of text gets
	 *
	 * Kept here rather than inline in the caller because each key is load-bearing rather than a preference: the
	 * registry decides how fonts resolve, so anything constructing mPDF takes this set whole and merges its own
	 * per-document keys on top.
	 *
	 * @param LanguageToFontRegistry $language_to_font Handed in so the caller can keep adding to it after mPDF is built
	 *
	 * @since 7.0
	 */
	public function mpdf_font_config( LanguageToFontRegistry $language_to_font ): array {
		return [
			'fontRegistry'     => $this->build_font_registry(),
			/* Left empty: each package layer appends its own directory, bundled first */
			'fontDir'          => [],
			'fontdata'         => apply_filters( 'mpdf_font_data', [] ),
			'languageToFont'   => $language_to_font,

			/*
			 * A 7.0 requirement rather than a preference: mPDF never substitutes Arabic or Indic glyphs, so a lang
			 * tag on the run is the only route to a font for those scripts, and this is what supplies the tag.
			 */
			'autoScriptToLang' => true,
			/* No Latin-language rows in the map, so nothing flips words out of the chosen font mid-paragraph */
			'autoVietnamese'   => false,
			/* Arimo carries no legacy kern table, so GPOS kerning is its only route to it */
			'useKerning'       => true,
			'autoLangToFont'   => true,
			'useSubstitutions' => true,
		];
	}

	/**
	 * The registry handed to mPDF as `fontRegistry`
	 *
	 * Installed is added first and bundled second, because `add()` prepends and mPDF reads the result in that
	 * order: the bundled layer is read first, so its keys and its `backupSubsFont` entries sort ahead of the rows'.
	 *
	 * @since 7.0
	 */
	public function build_font_registry(): FontRegistry {
		return new FontRegistry( [ $this->installed_package(), $this->bundled_package() ] );
	}

	/**
	 * The fonts that ship inside the plugin
	 *
	 * A constant, not rows: they never toggle, never update and cannot be removed.
	 *
	 * @since 7.0
	 */
	public function bundled_package(): Package {
		return new Package(
			'gfpdf-bundled',
			untrailingslashit( $this->bundled_dir ),
			[
				/* useOTL is load-bearing: the faces carry no legacy kern table, so GPOS is their only route to kerning */
				static::BUNDLED_FONT    => static::BUNDLED_FACES + [
					'useOTL'     => 0xFF,
					'useKashida' => 0,
				],
				static::BUNDLED_SYMBOLS => [
					'R'          => 'DejaVuSansSymbols.ttf',
					'useOTL'     => 0,
					'useKashida' => 0,
				],
			],
			[ static::BUNDLED_FONT, static::BUNDLED_SYMBOLS ],
			$this->generic_families(),
			$this->bundled_aliases()
		);
	}

	/**
	 * The `fontFileFinder` mPDF resolves out of the container, in place of the one it would build itself
	 *
	 * @param object|null $inner A finder already in the container, which the returned one wraps rather than replaces
	 *
	 * @since 7.0
	 */
	public function font_file_finder( $inner = null ): Font_File_Finder {
		return new Font_File_Finder( $this->repository, static::BUNDLED_FACES, $this->log, $inner );
	}

	/**
	 * Every font row, whatever installed it
	 *
	 * Faces flagged `missing` are left out, so no fallback list ever names a file mPDF cannot load. Neither this
	 * nor the bundled layer touches the disk.
	 *
	 * @since 7.0
	 */
	public function installed_package(): Package {
		$last_changed = $this->repository->get_last_changed();

		/*
		 * One begin_pdf() reaches this through build_font_registry(), get_default_font() and the language map, and
		 * a single PDF reaches it again through set_watermark_font() and the template styles. Building it once a
		 * request matters most to queue and bulk jobs, which render many PDFs before the stamp can change.
		 */
		if ( $this->memo !== null && $this->memo['stamp'] === $last_changed ) {
			return $this->memo['package'];
		}

		$rows = $this->rows();

		$fonts        = [];
		$backup_subs  = [];
		$bmp          = [];
		$substitution = [];
		$dictionaries = [];

		foreach ( $rows as $font_key => $row ) {
			$faces = [];

			foreach ( Font_Repository::FACE_ROLES as $role ) {
				if ( isset( $row['files'][ $role ] ) && (int) $row['files'][ $role ]['missing'] === 0 ) {
					$faces[ $role ] = $row['files'][ $role ]['path'];
				}
			}

			/* A partially installed entry is a normal state, but a row with no readable face registers nothing */
			if ( $faces === [] ) {
				continue;
			}

			$faces['useOTL']     = (int) $row['use_otl'];
			$faces['useKashida'] = (int) $row['use_kashida'];

			$meta = $row['meta'];

			if ( ! empty( $meta['sip_ext'] ) ) {
				$faces['sip-ext'] = (string) $meta['sip_ext'];
			}

			$fonts[ $font_key ] = $faces;

			if ( ! empty( $meta['backup_subs'] ) ) {
				$backup_subs[] = $font_key;
			}

			if ( ! empty( $meta['bmp'] ) ) {
				$bmp[] = $font_key;
			}

			foreach ( (array) ( $meta['family_substitution'] ?? [] ) as $family ) {
				$substitution[ $family ][] = $font_key;
			}

			foreach ( $row['files'] as $role => $file ) {
				if ( strpos( (string) $role, 'dict_' ) === 0 && (int) $file['missing'] === 0 ) {
					$dictionaries[ substr( (string) $role, 5 ) ] = $this->repository->get_font_dir() . $file['path'];
				}
			}
		}

		$package = new Package(
			'gfpdf-installed',
			untrailingslashit( $this->repository->get_font_dir() ),
			$fonts,
			$backup_subs,
			$substitution,
			[],
			$bmp,
			$dictionaries
		);

		$this->memo = [
			'stamp'   => $last_changed,
			'package' => $package,
		];

		return $package;
	}

	/**
	 * The one effective language map, as mPDF consumes it
	 *
	 * @since 7.0
	 */
	public function language_to_font( array $adobe_cjk = [] ): Language_To_Font {
		return new Language_To_Font( $this->effective_language_map( $adobe_cjk ) );
	}

	/**
	 * mPDF's four Adobe CJK families, by the tags a detector emits for them
	 *
	 * The last-resort route for a CJK document whose pack has not landed: mPDF writes a non-embedded
	 * `CIDFontType0` and the reader's own viewer supplies the glyphs, which beats a page of boxes in an emailed
	 * PDF. Traditional Chinese is `big5` and everything else Han is `gb`, matching the pack split — Simplified is
	 * where a Han run with no better signal belongs.
	 *
	 * @since 7.0
	 */
	public const ADOBE_CJK_MAP = [
		'zh'       => 'gb',
		'zh-cn'    => 'gb',
		'und-hans' => 'gb',
		'zh-hk'    => 'big5',
		'zh-tw'    => 'big5',
		'und-hant' => 'big5',
		'ja'       => 'sjis',
		'ko'       => 'uhc',
		'und-hang' => 'uhc',
	];

	/**
	 * The Adobe CJK rows for the tags a render could not resolve, if this document may have them
	 *
	 * Refused outright for PDF/A and PDF/X, where `AddCJKFont()` throws: those render with the bundled faces and
	 * the miss already recorded on the entry's catalogue row. The format is read here rather than by the caller so
	 * that deciding which families cover which tags stays in one place.
	 *
	 * @param string[] $scripts  What the render could not resolve
	 * @param array    $settings The PDF's settings
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	public function adobe_cjk_overlay( array $scripts, array $settings = [] ): array {
		if ( strtolower( (string) ( $settings['format'] ?? 'standard' ) ) !== 'standard' ) {
			return [];
		}

		return array_intersect_key( static::ADOBE_CJK_MAP, array_flip( array_map( 'strtolower', $scripts ) ) );
	}

	/**
	 * The bundled map overlaid by the installed rows
	 *
	 * Installed beats bundled for the same code; between installed entries the first row in precedence order wins.
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	public function default_language_map(): array {
		$installed = [];

		foreach ( $this->rows_by_precedence() as $font_key => $row ) {
			foreach ( (array) ( $row['meta']['languages'] ?? [] ) as $code ) {
				$code = strtolower( (string) $code );

				if ( $code !== '' && ! isset( $installed[ $code ] ) ) {
					$installed[ $code ] = $font_key;
				}
			}
		}

		return array_merge( static::DEFAULT_LANGUAGE_MAP, $installed );
	}

	/**
	 * The rows ordered `[ legacy, generic, position, font_key ]`: every language-specific pack ahead of every
	 * generic one, both ahead of everything a 6.x upgrade adopted off disk, each tier in the catalogue's order, and
	 * the key as a stable tiebreak
	 *
	 * The legacy tier is last because an adopted font is what the site had, not what it asked for: installing the
	 * Korean pack moves `ko` off its own UnBatang by the ordinary rule, with nothing to uninstall first.
	 *
	 * Not the order the rows arrive in. `Font_Repository::all()` reads them by the font table's auto-increment
	 * `id`, which is the order this site happened to install them, so two sites holding the same two packs would
	 * otherwise resolve a code both claim — `he` is claimed by `west-asian` and by `popular-sans` — differently.
	 *
	 * @return array<string, array>
	 *
	 * @since 7.0
	 */
	protected function rows_by_precedence(): array {
		$rows = $this->rows();

		/* `$rows` is captured before the sort, so the comparison always reads the unsorted copy */
		uksort(
			$rows,
			static function ( $a, $b ) use ( $rows ) {
				return static::precedence( $rows[ $a ], $a ) <=> static::precedence( $rows[ $b ], $b );
			}
		);

		return $rows;
	}

	/**
	 * One row's sort key, read from the `meta` its source wrote (`Font_Sources::coverage_meta()`, or
	 * `Legacy_Font_Adopter` for the legacy tier)
	 *
	 * @return array{0: int, 1: int, 2: int, 3: string}
	 *
	 * @since 7.0
	 */
	protected static function precedence( array $row, string $font_key ): array {
		$meta = (array) ( $row['meta'] ?? [] );

		return [
			empty( $meta['legacy'] ) ? 0 : 1,
			empty( $meta['generic'] ) ? 0 : 1,
			(int) ( $meta['position'] ?? 0 ),
			$font_key,
		];
	}

	/**
	 * The default map with the Adobe CJK fallback and then the user's overrides applied
	 *
	 * `*` removes a code so the document font stands. An override naming a font that is not registered is dropped
	 * rather than obeyed, or a since-deleted font would send the run into mPDF's substitution instead of the map.
	 *
	 * The fallback sits between the two because it is a stand-in, not a preference: an installed pack has already
	 * been asked for by the time a caller passes one (the render only passes codes the map could not answer), and
	 * an admin's own override still wins over a stand-in.
	 *
	 * @param array<string, string> $adobe_cjk Per request, from `adobe_cjk_overlay()`
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	public function effective_language_map( array $adobe_cjk = [] ): array {
		$map       = array_merge( $this->default_language_map(), $adobe_cjk );
		$overrides = $this->options->get_option( 'font_language_overrides', [] );

		if ( ! is_array( $overrides ) ) {
			return $map;
		}

		$registered = $this->registered_keys();

		foreach ( $overrides as $code => $font_key ) {
			$code = strtolower( (string) $code );

			if ( $font_key === '*' ) {
				unset( $map[ $code ] );
				continue;
			}

			if ( isset( $registered[ $font_key ] ) ) {
				$map[ $code ] = (string) $font_key;
			}
		}

		return $map;
	}

	/**
	 * The font a PDF renders in
	 *
	 * Validated against what is actually registered, never derived from a list position: a saved font that is no
	 * longer installed falls back rather than rendering as whatever happened to register first.
	 *
	 * @param array $settings The PDF's settings
	 *
	 * @since 7.0
	 */
	public function get_default_font( array $settings = [] ): string {
		$registered = $this->registered_keys();

		foreach ( [ $settings['font'] ?? '', $this->options->get_option( 'default_font', '' ) ] as $candidate ) {
			$candidate = (string) $candidate;

			if ( $candidate === '' ) {
				continue;
			}

			if ( isset( $registered[ $candidate ] ) ) {
				return $candidate;
			}

			$this->log->warning( 'The selected font is not installed, falling back to the bundled font', [ 'font' => $candidate ] );
		}

		/* Nothing was ever chosen: let the language map answer for the document's language before giving up */
		$mapped = $this->language_to_font()->getLanguageOptions( $this->get_default_language(), false );

		return $mapped !== '' && isset( $registered[ $mapped ] ) ? $mapped : static::BUNDLED_FONT;
	}

	/**
	 * The site-wide document language, from the setting or the WordPress locale
	 *
	 * @since 7.0
	 */
	public function get_default_language(): string {
		$saved = (string) $this->options->get_option( 'default_pdf_language', '' );

		return $saved !== '' ? $saved : Language_To_Font::locale_to_language( get_locale() );
	}

	/**
	 * The language a given PDF renders in
	 *
	 * @since 7.0
	 */
	public function get_document_language( array $settings = [] ): string {
		$per_pdf = (string) ( $settings['pdf_language'] ?? '' );

		return $per_pdf !== '' ? $per_pdf : $this->get_default_language();
	}

	/**
	 * The document's base script, as `baseScript`
	 *
	 * Runs in this script are never tagged and inherit the document language, so the chosen font stands and OTL
	 * `locl` picks that language's forms in fonts that carry them.
	 *
	 * @since 7.0
	 */
	public function get_document_script( array $settings = [] ): int {
		$override = (string) $this->options->get_option( 'document_script', '' );

		if ( $override !== '' && defined( Ucdn::class . '::' . $override ) ) {
			return (int) constant( Ucdn::class . '::' . $override );
		}

		$language = strtolower( $this->get_document_language( $settings ) );
		$primary  = explode( '-', $language )[0];

		return static::LANGUAGE_TO_SCRIPT[ $primary ] ?? Ucdn::SCRIPT_LATIN;
	}

	/**
	 * Every font key a PDF may name, grouped for the settings dropdown and the Font Manager
	 *
	 * @return array<string, array<string, string>>
	 *
	 * @since 7.0
	 */
	public function get_grouped_fonts(): array {
		$groups = [
			esc_html__( 'Bundled Fonts', 'gravity-pdf' ) => [ static::BUNDLED_FONT => 'Arimo' ],
		];

		$user_defined = esc_html__( 'User-Defined Fonts', 'gravity-pdf' );

		foreach ( $this->rows() as $font_key => $row ) {
			$label = $row['coverage'] === 1 ? esc_html__( 'Language Packs', 'gravity-pdf' ) : $user_defined;

			$groups[ $label ][ $font_key ] = (string) $row['label'];
		}

		return array_filter( $groups );
	}

	/**
	 * The install status of every entry the Font Manager can show progress for
	 *
	 * One object per `{source}/{entry}` that has font rows or a phase — installed entries, entries mid-install, and
	 * entries a failure left behind. Font rows and catalog rows only: no route, poll or health check may make this
	 * fetch anything.
	 *
	 * The queue is a parameter rather than a constructor dependency because it depends on this class in turn (for
	 * the auto-install gate), and a container cannot build a cycle.
	 *
	 * @return array<string, array>
	 *
	 * @since 7.0
	 */
	public function get_install_statuses( Install_Queue $queue, bool $nudge = false ): array {
		$entries = $this->entry_rows();
		$catalog = $this->catalog->status_rows( array_keys( $entries ) );

		$ids = array_keys( $entries + $catalog );
		sort( $ids );

		$running  = $queue->is_running();
		$stalled  = false;
		$statuses = [];

		foreach ( $ids as $id ) {
			$row             = $catalog[ $id ] ?? null;
			$statuses[ $id ] = $this->status_object( $entries[ $id ] ?? [], $row, $running );
			$stalled         = $stalled || $this->stalled_for( $row, static::NUDGE_AFTER );
		}

		/*
		 * Before the poller gives up on it: a batch nothing has picked up is usually one dispatch away from moving.
		 * Asked for rather than done, because the same statuses are read by the System Report, which promises not
		 * to change the site it is describing.
		 */
		if ( $nudge && ! $running && $stalled ) {
			$queue->nudge();
		}

		return $statuses;
	}

	/**
	 * One entry's status object
	 *
	 * @param string $id `{source}/{entry}`
	 *
	 * @return array
	 *
	 * @since 7.0
	 */
	public function get_install_status( string $id, Install_Queue $queue ): array {
		$statuses = $this->get_install_statuses( $queue );

		return $statuses[ $id ] ?? $this->status_object( [], null, false );
	}

	/**
	 * The font rows of every entry that has them, keyed by `{source}/{entry}`
	 *
	 * Rows a site has hidden are kept: the toggle is visibility, not installation (§4.11).
	 *
	 * @return array<string, array[]>
	 *
	 * @since 7.0
	 */
	protected function entry_rows(): array {
		$entries = [];

		foreach ( $this->rows() as $row ) {
			$entry = (string) ( $row['entry'] ?? '' );

			if ( $entry === '' ) {
				continue;
			}

			$entries[ $row['source'] . '/' . $entry ][] = $row;
		}

		return $entries;
	}

	/**
	 * Compose one status object from the rows behind it
	 *
	 * @param array[]    $rows    The entry's font rows
	 * @param array|null $catalog The entry's catalog row, when it still has one
	 * @param bool       $running Whether the install queue is processing a batch right now
	 *
	 * @return array
	 *
	 * @since 7.0
	 */
	protected function status_object( array $rows, ?array $catalog, bool $running ): array {
		$paths = Font_Repository::paths_for_rows( $rows );

		$phase  = $catalog === null ? '' : (string) $catalog['phase'];
		$status = [
			'phase'            => $phase === '' ? null : $phase,
			'files_done'       => count( $paths ),
			'installed'        => $rows !== [],
			'update_available' => false,
		];

		foreach ( [ 'error', 'retry_after' ] as $field ) {
			if ( $catalog !== null && (string) $catalog[ $field ] !== '' ) {
				$status[ $field ] = (string) $catalog[ $field ];
			}
		}

		if ( ! $running && $this->stalled_for( $catalog, static::STUCK_AFTER ) ) {
			$status['stuck'] = true;
		}

		$update = $this->pending_update( $rows, $catalog );

		if ( $update !== null ) {
			$status['update_available'] = true;
			$status['update']           = $update;
		}

		return $status;
	}

	/**
	 * What the Updates screen shows for this entry, or null when it is current
	 *
	 * A column comparison, never a fetch: the sync writes the catalogue's version and the installer copies the
	 * version it installed onto every row, so a difference between them is the whole test.
	 *
	 * @return array|null
	 *
	 * @since 7.0
	 */
	protected function pending_update( array $rows, ?array $catalog ): ?array {
		$version = $catalog === null ? '' : (string) $catalog['version'];

		if ( $rows === [] || $version === '' ) {
			return null;
		}

		$installed = null;

		foreach ( $rows as $row ) {
			if ( (string) ( $row['version'] ?? '' ) !== $version ) {
				$installed = (string) ( $row['version'] ?? '' );
				break;
			}
		}

		if ( $installed === null ) {
			return null;
		}

		return [
			'installed_version' => $installed,
			'version'           => $version,
			'notes'             => $catalog['notes'],
			'released'          => $catalog['released'],
			'files'             => (int) $catalog['files'],
			'size'              => (int) $catalog['size'],
		];
	}

	/**
	 * Whether an entry has been in a live phase, unchanged, for longer than `$seconds`
	 *
	 * `phase_since` is written in UTC by every phase transition, so this never reads the site's timezone.
	 *
	 * @since 7.0
	 */
	protected function stalled_for( ?array $catalog, int $seconds ): bool {
		if ( $catalog === null || ! in_array( (string) $catalog['phase'], Catalog_Repository::LIVE_PHASES, true ) ) {
			return false;
		}

		$since = $catalog['phase_since'];

		return $since !== null && (int) strtotime( $since . ' UTC' ) < time() - $seconds;
	}

	/**
	 * Whether a font key would resolve to something at render time
	 *
	 * The public half of `registered_keys()`, for the health check that asks about a saved setting rather than
	 * about a row: a font whose only face is flagged missing is not registered, however present its row is.
	 *
	 * @since 7.0
	 */
	public function is_registered( string $font_key ): bool {
		return isset( $this->registered_keys()[ $font_key ] );
	}

	/**
	 * Every key mPDF will have registered, bundled included
	 *
	 * @return array<string, true>
	 *
	 * @since 7.0
	 */
	protected function registered_keys(): array {
		$keys = [
			static::BUNDLED_FONT    => true,
			static::BUNDLED_SYMBOLS => true,
		];

		/* Read from the rows rather than the built layer: the fallback arrays it also assembles are not needed here */
		foreach ( $this->rows() as $font_key => $row ) {
			foreach ( Font_Repository::FACE_ROLES as $role ) {
				if ( isset( $row['files'][ $role ] ) && (int) $row['files'][ $role ]['missing'] === 0 ) {
					$keys[ $font_key ] = true;
					break;
				}
			}
		}

		return $keys;
	}

	/**
	 * The rows, read once per request
	 *
	 * @since 7.0
	 */
	protected function rows(): array {
		return $this->repository->all();
	}

	/**
	 * The canonical generic-family lists
	 *
	 * mPDF walks these for the first *available* key, so `sans` is always Arimo, and `serif` / `mono` become a real
	 * serif or monospace as soon as a pack carrying one is installed. Rows and config append after these; nothing
	 * prepends.
	 *
	 * @since 7.0
	 */
	protected function generic_families(): array {
		return [
			'sans_fonts'  => [
				static::BUNDLED_FONT,
				'sans',
				'sans-serif',
				'cursive',
				'fantasy',
				'dejavusanscondensed',
				'dejavusans',
				'freesans',
				'xbriyaz',
				'garuda',
				'arial',
				'helvetica',
				'liberationsans',
			],
			'serif_fonts' => [
				'serif',
				'tinos',
				'times',
				'timesnewroman',
				'dejavuserifcondensed',
				'dejavuserif',
				'freeserif',
				static::BUNDLED_FONT,
			],
			'mono_fonts'  => [
				'mono',
				'monospace',
				'cousine',
				'courier',
				'couriernew',
				'dejavusansmono',
				'freemono',
				'ocrb',
				static::BUNDLED_FONT,
			],
		];
	}

	/**
	 * `arial` / `helvetica` resolve to the bundled font, plus whatever names the rows claim for themselves
	 *
	 * Arimo is metric-compatible with Arial, so templates written against those families keep their layout. The
	 * rows' own `meta.aliases` are 6.x's `fonttrans` entries, carried by the upgrade with the fonts they name
	 * (`ocr-b` → `ocrb`, `damase` → `mph2bdamase`), so a template written against either still resolves.
	 *
	 * Every alias defers to a real font: an upload or import may claim any of these keys, and then it wins.
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	protected function bundled_aliases(): array {
		$rows    = $this->rows();
		$aliases = [
			'arial'     => static::BUNDLED_FONT,
			'helvetica' => static::BUNDLED_FONT,
		];

		foreach ( $rows as $font_key => $row ) {
			foreach ( (array) ( $row['meta']['aliases'] ?? [] ) as $alias ) {
				$aliases[ strtolower( (string) $alias ) ] = $font_key;
			}
		}

		return array_diff_key( $aliases, $rows );
	}
}
