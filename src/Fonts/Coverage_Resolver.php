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
 * Reads the coverage rows' `always` / `languages` / `font_keys` columns and nothing else — no entry document, no
 * fetch, no `Font_Sources`, whose filter runs third-party code the render path must not depend on. Naming an
 * entry's files is the queue's job, after the auto-install gate.
 *
 * It decides *what*, never *whether*: the gate and the dedup both live in `Install_Queue`.
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
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	public function __construct( Catalog_Repository $catalog, Registry $registry ) {
		$this->catalog  = $catalog;
		$this->registry = $registry;
	}

	/**
	 * The entries a site's languages call for: the site language and every installed core translation
	 *
	 * WordPress locales are underscored and cased (`zh_TW`); the catalogue speaks mPDF's tags (`zh-tw`).
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
	 * already registered, which is the key being asked for.
	 *
	 * @return array[] One `Install_Queue::enqueue_once()` request per entry
	 *
	 * @since 7.0
	 */
	public function for_settings( array $pdf ): array {
		return $this->requests(
			array_merge(
				$this->rows_for_font( (string) ( $pdf['font'] ?? '' ) ),
				$this->rows_for_languages( [ $this->registry->get_document_language( $pdf ) ] )
			)
		);
	}

	/**
	 * The coverage entries claiming any of these language tags
	 *
	 * Most specific rung first, and every row claiming a rung comes back before the next rung is tried: `zh-tw`
	 * names Traditional Chinese where bare `zh` names Simplified. The ladder is `Language_To_Font`'s, the same one
	 * the render walks.
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

		foreach ( array_unique( $languages ) as $language ) {
			foreach ( Language_To_Font::candidates( $language ) as $tag ) {
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
	 * @param array[] $rows
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function requests( array $rows ): array {
		$requests = [];

		foreach ( array_merge( $rows, $this->always_rows() ) as $row ) {
			if ( $row['phase'] === 'removed' ) {
				continue;
			}

			$id              = $row['source'] . '/' . $row['entry'];
			$requests[ $id ] = [ 'entry' => $id ];
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
