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
 * Names a pack's rows from the fonts themselves, where the entry did not name them
 *
 * A pack published before the index carried names labels every row by its mPDF key — `notosansbengali`,
 * `daibannasil` — which is what 61 of 66 rows were showing in the Default Font select (§11 D20). The name is in
 * the bytes either way, in the `name` table every one of those files has.
 *
 * Coverage rows only, and cosmetic: a display row is identified by its label, so renaming one would make the next
 * re-install miss it, and mPDF keys everything on `font_key` regardless.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Namer implements Font_Population_Pass {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var FontFaceAnalysis
	 * @since 7.0
	 */
	protected $analysis;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	public function __construct( Font_Repository $repository, FontFaceAnalysis $analysis, LoggerInterface $log ) {
		$this->repository = $repository;
		$this->analysis   = $analysis;
		$this->log        = $log;
	}

	/**
	 * What one row should be called, given the face it renders with
	 *
	 * For the caller holding the file before it writes the row: `Catalog_Font_Adopter` inserts each row once, so
	 * naming there costs the parse and nothing else.
	 *
	 * @param string $regular  The regular face, relative to the fonts directory
	 * @param string $fallback What the row is called if the file has no family name to give
	 *
	 * @since 7.0
	 */
	public function name_for( string $regular, string $fallback ): string {
		$face   = $regular === '' ? null : $this->analysis->analyse( $regular );
		$family = $face === null ? '' : Font_Sources::display_name( $face['family'] );

		return $family !== '' ? $family : $fallback;
	}

	/**
	 * Name every row of one entry the entry did not name itself
	 *
	 * For the caller whose rows are already written. The install upserts one as each file lands and `font_row()`
	 * rewrites `label` every time, so a name written before the last file is overwritten by it — hence at
	 * completion, never per file. An entry update reverts the labels until this runs again, which self-heals.
	 *
	 * The parse is a second one, after `Font_Cache_Warmer::warm()`, and the warm cannot answer it: mPDF's metrics
	 * store nameID 4 with its spaces stripped (`NotoSansSC-Regular`), and a row wants nameID 1.
	 *
	 * @param array $entry The decoded `entry` object: a name it publishes outranks the file's and is on the row
	 *                     already, so this leaves it alone
	 *
	 * @since 7.0
	 */
	public function name_entry( string $source, string $entry_id, array $entry ): void {
		$rows = [];

		foreach ( $this->repository->rows_for_entry( $source, $entry_id ) as $font_key => $row ) {
			/* A display entry's row is identified by its label, and an admin may have chosen that label */
			if ( ! Install_Requests::is_coverage( $row ) ) {
				continue;
			}

			if ( Font_Sources::published_name( $entry, (string) $font_key ) === '' ) {
				$rows[] = $row;
			}
		}

		$this->name_rows( $rows, Font_Sources::join( $source, $entry_id ) );
	}

	/**
	 * Name the rows a site installed before any of this existed
	 *
	 * The one-shot half. It runs where the other population passes do, which means it needs what they need: a
	 * `Font_Schema::VERSION` a site does not hold yet. A fresh install and a 6.x upgrade both reach the pass loop
	 * with no coverage rows to rename — those arrive later, from a catalogue install that names its own — so the
	 * population this exists for is the site that installed packs on an earlier 7.0, and the bump is what reaches
	 * it. Pre-release, that costs one dbDelta and the three idempotent passes ahead of this one.
	 *
	 * Scoped to rows still labelled by their key: a coverage row showing anything else was named by its entry or
	 * by the single-font rung, and neither is this pass's to overwrite.
	 *
	 * @return int How many rows were renamed
	 *
	 * @since 7.0
	 */
	public function run(): int {
		$rows = [];

		foreach ( $this->repository->all() as $font_key => $row ) {
			if ( Install_Requests::is_coverage( $row ) && (string) $row['label'] === (string) $font_key ) {
				$rows[] = $row;
			}
		}

		return $this->name_rows( $rows, 'backfill' );
	}

	/**
	 * Read each row's regular face and write back the names that changed, in one go
	 *
	 * One write for the set: `update()` flushes the row cache per call, and sixty flushes would have every read in
	 * between re-running both `SELECT *`s.
	 *
	 * @param array[] $rows
	 *
	 * @since 7.0
	 */
	protected function name_rows( array $rows, string $context ): int {
		$labels = [];

		foreach ( $rows as $row ) {
			$label = $this->name_for( (string) ( $row['files']['R']['path'] ?? '' ), (string) $row['label'] );

			if ( $label !== (string) $row['label'] ) {
				$labels[ (int) $row['id'] ] = $label;
			}
		}

		if ( $labels === [] ) {
			return 0;
		}

		$named = $this->repository->update_labels( $labels );

		$this->log->notice(
			'Named font rows from the fonts themselves',
			[
				'entry' => $context,
				'named' => $named,
			]
		);

		return $named;
	}
}
