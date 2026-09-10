<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * Which catalogue entries a site wants installed
 *
 * Reads the coverage rows' `always` / `languages` / `font_keys` columns and the font rows already installed — no
 * entry document, no fetch, no `Font_Sources`, whose filter runs third-party code the render path must not depend
 * on. Naming an entry's files is the queue's job, after the auto-install gate.
 *
 * It decides *what*, never *whether*: the gate and the dedup both live in `Install_Queue`.
 *
 * **The legacy rule.** A tag a font adopted from a 6.x install already answers is not a gap, so it is subtracted
 * from every answer here and no trigger downloads a pack to redraw a script the site has rendered since 6.x. Only
 * legacy rows count this way: a *generic* pack answering `he` must not stop `west-asian` installing, or the
 * specific pack could never arrive to outrank it. Delete the legacy font and the tag is a gap again on the next
 * render; every other route to the pack — the Font Manager, an explicit `POST`, a language override — is untouched.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Coverage_Resolver {

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	public function __construct( Catalog_Repository $catalog, Font_Repository $repository, Registry $registry ) {
		$this->catalog    = $catalog;
		$this->repository = $repository;
		$this->registry   = $registry;
	}

	/**
	 * The entries a site's languages call for: the site language and every installed core translation
	 *
	 * The whole set rather than the active one, because a PDF renders in whatever language its settings name and
	 * an admin can switch the site's at any time — installing only the current one would leave the next render
	 * short. WordPress locales are underscored and cased (`zh_TW`); the catalogue speaks mPDF's tags (`zh-tw`).
	 *
	 * @return array[] One `Install_Queue::enqueue_once()` request per entry
	 *
	 * @since 7.0
	 */
	public function for_site_languages(): array {
		return $this->for_locales( array_unique( array_merge( [ get_locale() ], get_available_languages() ) ) );
	}

	/**
	 * The entries a given list of WordPress locales calls for
	 *
	 * @param string[] $wp_locales
	 *
	 * @return array[] One `Install_Queue::enqueue_once()` request per entry
	 *
	 * @since 7.0
	 */
	public function for_locales( array $wp_locales ): array {
		return $this->requests(
			$this->rows_for_languages( array_map( [ Language_To_Font::class, 'locale_to_language' ], $wp_locales ) )
		);
	}

	/**
	 * The entries one PDF's settings call for: the font the admin picked, and the language it renders in
	 *
	 * The language goes through `Registry`, so a PDF naming none still asks for the site's — `default_pdf_language`
	 * has no trigger of its own. The font is read raw, since `get_default_font()` would filter it against the fonts
	 * already registered, which is the key being asked for — but only when it is a string: the settings array
	 * reaches this straight off the save, before anything has validated its shape.
	 *
	 * @return array[] One `Install_Queue::enqueue_once()` request per entry
	 *
	 * @since 7.0
	 */
	public function for_settings( array $pdf ): array {
		return $this->requests(
			array_merge(
				$this->rows_for_font( is_string( $pdf['font'] ?? null ) ? $pdf['font'] : '' ),
				$this->rows_for_languages( [ $this->registry->get_document_language( $pdf ) ] )
			)
		);
	}

	/**
	 * The entries a rendered document's scripts call for
	 *
	 * Each request carries the tags that matched it, which is what makes trigger 3 a selector rather than a second
	 * code path: the queue reads the reason and takes those scripts' Regular faces inline, and everything a
	 * request does *not* explain — the always rule's own entries included — means all of it, in the background.
	 *
	 * @param string[] $scripts `Script_Detector::detect()`'s answer
	 *
	 * @return array[] One `Install_Queue::enqueue_once()` request per entry
	 *
	 * @since 7.0
	 */
	public function for_scripts( array $scripts ): array {
		$matched = [];
		$reasons = [];
		$scripts = array_values( array_diff( $scripts, array_keys( $this->legacy_map() ) ) );

		foreach ( $this->catalog->coverage_entries() as $row ) {
			$claimed = array_values( array_intersect( $this->csv( $row['scripts'] ?? null ), $scripts ) );

			if ( $claimed === [] ) {
				continue;
			}

			$matched[] = $row;

			$reasons[ $row['source'] . '/' . $row['entry'] ] = $claimed;
		}

		return $this->requests( $matched, $reasons );
	}

	/**
	 * Every script tag the catalogue claims and this site has no font for
	 *
	 * `Script_Detector`'s shortcut: with nothing left to install there is nothing worth reading a document for, so
	 * a fully provisioned site skips the code-point walk entirely. An entry counts as installed once every key its
	 * `font_keys` names has a row — a key short of that still has files to fetch, so its scripts stay on the list.
	 *
	 * @return array<string, true> Keyed for lookup
	 *
	 * @since 7.0
	 */
	public function uninstalled_scripts(): array {
		$installed = $this->repository->all();
		$legacy    = $this->legacy_map();
		$scripts   = [];

		foreach ( $this->catalog->coverage_entries() as $row ) {
			$keys = $this->csv( $row['font_keys'] ?? null );

			if ( $row['phase'] === 'removed' || array_diff( $keys, array_keys( $installed ) ) === [] ) {
				continue;
			}

			foreach ( $this->csv( $row['scripts'] ?? null ) as $tag ) {
				if ( ! isset( $legacy[ $tag ] ) ) {
					$scripts[ $tag ] = true;
				}
			}
		}

		return $scripts;
	}

	/**
	 * The coverage entries a 6.x font is currently standing in for, with the tags and fonts that do it
	 *
	 * The legacy rule seen from the other side, for `Missing_Coverage_Check`: a tag one of these entries claims
	 * never reaches `missing_scripts`, because it resolved, so the only way to offer the pack is to ask which
	 * entries the adopted fonts are answering for. Entries with a row of their own are already installed and are
	 * not on offer.
	 *
	 * @return array[] Coverage rows, each with `legacy_tags` and `legacy_fonts`
	 *
	 * @since 7.0
	 */
	public function legacy_covered_entries(): array {
		$legacy = $this->legacy_map();

		if ( $legacy === [] ) {
			return [];
		}

		$installed = array_keys( $this->repository->all() );
		$covered   = [];

		foreach ( $this->catalog->coverage_entries() as $row ) {
			$tags = array_values( array_intersect( $this->csv( $row['languages'] ?? null ), array_keys( $legacy ) ) );

			if ( $tags === [] || $row['phase'] === 'removed' ) {
				continue;
			}

			if ( array_intersect( $this->csv( $row['font_keys'] ?? null ), $installed ) !== [] ) {
				continue;
			}

			$row['legacy_tags']  = $tags;
			$row['legacy_fonts'] = array_values( array_unique( array_intersect_key( $legacy, array_flip( $tags ) ) ) );

			$covered[] = $row;
		}

		return $covered;
	}

	/**
	 * Every language tag a font adopted from a 6.x install answers, and the font that answers it
	 *
	 * The legacy rule's one input, read from the same `meta.languages` the registry routes with, so the tags this
	 * treats as covered are exactly the ones a render resolves.
	 *
	 * @return array<string, string> Tag => font key
	 *
	 * @since 7.0
	 */
	protected function legacy_map(): array {
		$tags = [];

		foreach ( $this->repository->all() as $font_key => $row ) {
			$meta = (array) ( $row['meta'] ?? [] );

			if ( empty( $meta['legacy'] ) ) {
				continue;
			}

			foreach ( (array) ( $meta['languages'] ?? [] ) as $tag ) {
				$tags[ strtolower( (string) $tag ) ] = $font_key;
			}
		}

		return $tags;
	}

	/**
	 * The coverage entries claiming any of these language tags
	 *
	 * Most specific rung first, and every row claiming a rung comes back before the next rung is tried: `zh-tw`
	 * names Traditional Chinese where bare `zh` names Simplified. The ladder is `Language_To_Font`'s, the same one
	 * the render walks — including the legacy rule, which ends the walk when an adopted 6.x font answers the rung.
	 *
	 * @param string[] $languages
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function rows_for_languages( array $languages ): array {
		$by_tag = [];

		foreach ( $this->catalog->coverage_entries() as $row ) {
			foreach ( $this->csv( $row['languages'] ?? null ) as $tag ) {
				$by_tag[ $tag ][] = $row;
			}
		}

		$matched = [];
		$legacy  = $this->legacy_map();

		foreach ( array_unique( $languages ) as $language ) {
			/* The same ladder the render walks, so whichever rung answers first decides here too */
			foreach ( Language_To_Font::candidates( $language ) as $tag ) {
				if ( isset( $legacy[ $tag ] ) ) {
					break;
				}

				if ( isset( $by_tag[ $tag ] ) ) {
					$matched = array_merge( $matched, $by_tag[ $tag ] );
					break;
				}
			}
		}

		return $matched;
	}

	/**
	 * The coverage entries installing a font key
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function rows_for_font( string $font ): array {
		return array_filter(
			$this->catalog->coverage_entries(),
			function ( array $row ) use ( $font ): bool {
				return in_array( $font, $this->csv( $row['font_keys'] ?? null ), true );
			}
		);
	}

	/**
	 * Turn matched rows into install requests, with **the always rule** folded in: every answer also carries each
	 * `always` entry, so `emoji` is installed by default rather than pinned
	 *
	 * A `removed` phase drops any entry, always or not — `claim()` refuses one anyway, so this only spares the
	 * queue an entry document it would read and discard.
	 *
	 * @param array[]                $rows
	 * @param array<string, string[]> $reasons The scripts that matched an entry, where a caller has them
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function requests( array $rows, array $reasons = [] ): array {
		$requests = [];

		foreach ( array_merge( $rows, $this->always_rows() ) as $row ) {
			if ( $row['phase'] === 'removed' ) {
				continue;
			}

			$id              = $row['source'] . '/' . $row['entry'];
			$requests[ $id ] = [ 'entry' => $id ];

			if ( isset( $reasons[ $id ] ) ) {
				$requests[ $id ]['scripts'] = $reasons[ $id ];
			}
		}

		return array_values( $requests );
	}

	/**
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function always_rows(): array {
		return array_filter(
			$this->catalog->coverage_entries(),
			static function ( array $row ): bool {
				return $row['always'] === 1;
			}
		);
	}

	/**
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function csv( ?string $value ): array {
		$value = (string) $value;

		return $value === '' ? [] : array_filter( explode( ',', strtolower( $value ) ) );
	}
}
