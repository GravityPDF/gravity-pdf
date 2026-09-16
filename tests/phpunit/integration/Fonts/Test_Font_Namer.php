<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Font_Namer
 *
 * The rows a pack installed before anything published their names (§11 D20), and what the pass leaves alone.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Namer extends TestCase {

	use HasFontRows;

	public function tear_down(): void {
		$this->remove_font_rows();

		parent::tear_down();
	}

	protected function namer(): Font_Namer {
		global $gfpdf;

		return $gfpdf->get_font_namer();
	}

	/**
	 * A row backed by a real face, since the name is read out of the bytes: Arimo says "Arimo" whatever the row
	 * is called, and it is already in the repository
	 */
	protected function pack_row( string $font_key, array $overrides = [], string $face = 'Arimo-Regular.ttf' ): void {
		$path = 'test-' . $font_key . '.ttf';

		copy( PDF_PLUGIN_DIR . 'fonts/' . $face, $this->font_dir() . $path );

		$this->font_repository()->insert(
			array_merge(
				[
					'font_key' => $font_key,
					'label'    => $font_key,
					'source'   => 'packs',
					'entry'    => 'indic',
					'coverage' => 1,
					'files'    => [
						'R' => [
							'path' => $path,
							'size' => (int) filesize( $this->font_dir() . $path ),
						],
					],
				],
				$overrides
			)
		);
	}

	protected function label( string $font_key ): string {
		return (string) $this->font_repository()->get( $font_key )['label'];
	}

	public function test_the_backfill_names_a_row_still_labelled_by_its_key() {
		$this->pack_row( 'notosansbengali' );

		$this->assertSame( 1, $this->namer()->run() );
		$this->assertSame( 'Arimo', $this->label( 'notosansbengali' ) );
	}

	/* Nothing left to do second time round, which is what makes the pass safe to leave in the sequence */
	public function test_the_backfill_is_idempotent() {
		$this->pack_row( 'notosansbengali' );

		$this->assertSame( 1, $this->namer()->run() );
		$this->assertSame( 0, $this->namer()->run() );
	}

	/**
	 * The old derivation could only produce the key for a pack of more than one font, so a row showing anything
	 * else was named by its entry or by the single-font rung
	 */
	public function test_the_backfill_leaves_a_row_that_already_has_a_name() {
		$this->pack_row( 'notoemoji', [ 'label' => 'Emoji' ] );

		$this->assertSame( 0, $this->namer()->run() );
		$this->assertSame( 'Emoji', $this->label( 'notoemoji' ) );
	}

	/* A display row is identified by its label, so renaming one would make the next re-install miss it */
	public function test_the_backfill_leaves_a_row_that_is_not_a_packs() {
		$this->pack_row( 'brandsans', [ 'source' => 'custom', 'entry' => null, 'coverage' => 0 ] );

		$this->assertSame( 0, $this->namer()->run() );
		$this->assertSame( 'brandsans', $this->label( 'brandsans' ) );
	}

	public function test_a_name_the_entry_publishes_is_left_where_it_is() {
		$this->pack_row( 'notosansbengali', [ 'label' => 'Noto Sans Bengali' ] );

		$this->namer()->name_entry( 'packs', 'indic', [ 'names' => [ 'notosansbengali' => 'Noto Sans Bengali' ] ] );

		$this->assertSame( 'Noto Sans Bengali', $this->label( 'notosansbengali' ) );
	}

	/* The installer's path: the rows are written, and `font_row()` has just put the key back on each */
	public function test_an_entry_that_published_nothing_is_named_from_its_files() {
		$this->pack_row( 'notosansbengali' );

		$this->namer()->name_entry( 'packs', 'indic', [] );

		$this->assertSame( 'Arimo', $this->label( 'notosansbengali' ) );
	}

	/**
	 * Nothing is wrong with such a row — it renders as well under its key as under a name — and a face that will
	 * not parse is `Font_Cache_Warmer`'s to refuse, one call earlier
	 */
	public function test_a_face_that_will_not_parse_leaves_its_row_alone() {
		$this->install_entry_row( 'notosansbengali', 'indic' );

		$this->font_repository()->update(
			(int) $this->font_repository()->get( 'notosansbengali' )['id'],
			[ 'label' => 'notosansbengali' ]
		);

		$this->assertSame( 0, $this->namer()->run() );
		$this->assertSame( 'notosansbengali', $this->label( 'notosansbengali' ) );
	}
}
