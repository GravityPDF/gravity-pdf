<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Replaces the render-time glob 6.x ran on every PDF
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Loose_Font_Importer extends TestCase {

	use HasFontRows;

	/**
	 * @var Loose_Font_Importer
	 */
	public $importer;

	/**
	 * @var Font_Repository
	 */
	public $repository;

	/**
	 * @var string
	 */
	public $font_dir;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$this->repository = $this->font_repository();
		$this->font_dir   = $this->font_dir();
		$this->importer   = new Loose_Font_Importer(
			$this->repository,
			new SupportsOtl( $this->font_dir ),
			$gfpdf->log,
			$this->font_dir
		);
	}

	public function tear_down(): void {
		$this->remove_font_rows();
		$this->remove_font_files();

		GPDFAPI::get_options_class()->update_option( 'custom_fonts', [] );

		parent::tear_down();
	}

	/**
	 * Drop a real, parseable font into the fonts directory under a given name
	 */
	protected function drop( string $filename, string $source = 'DejaVuSans.ttf' ): string {
		return $this->drop_font_fixture( $filename, $source );
	}

	public function test_a_file_the_core_font_installer_wrote_is_left_to_the_adopter() {
		/* Byte-identical to the manifest entry, so it is adopted under its 6.x key rather than keyed by filename */
		$this->drop( 'DejaVuSansCondensed.ttf', 'DejaVuSansCondensed.ttf' );

		$this->assertNotContains( 'DejaVuSansCondensed.ttf', $this->importer->candidates() );
		$this->assertSame( 0, $this->importer->run() );
	}

	public function test_a_loose_font_becomes_a_row_under_its_legacy_key() {
		$this->drop( 'Roboto-Regular.ttf' );

		$this->assertSame( 1, $this->importer->run() );

		/* The key 6.x derived, so a template's font-family keeps resolving */
		$row = $this->repository->get( 'roboto-regular' );

		$this->assertNotNull( $row );
		$this->assertSame( 'Roboto Regular', $row['label'] );
		$this->assertSame( 'imported', $row['source'] );
		$this->assertSame( 'Roboto-Regular.ttf', $row['files']['R']['path'] );
		$this->assertGreaterThan( 0, $row['files']['R']['size'] );
	}

	public function test_an_underscore_key_is_valid_as_is() {
		$this->drop( 'Open_Sans.ttf' );

		$this->importer->run();

		$this->assertNotNull( $this->repository->get( 'open_sans' ) );
	}

	public function test_a_stem_outside_the_key_charset_collapses_to_hyphens() {
		$this->drop( 'Foo.Bar.ttf' );

		$this->importer->run();

		$this->assertNotNull( $this->repository->get( 'foo-bar' ) );
	}

	public function test_an_unreadable_file_is_skipped_rather_than_fatal() {
		file_put_contents( $this->font_dir . 'calibri.ttf', '' );
		$this->drop( 'Good-Font.ttf' );

		/* In 6.x a zero-byte file fatally broke the first render that selected it */
		$this->assertSame( 1, $this->importer->run() );

		$this->assertNull( $this->repository->get( 'calibri' ) );
		$this->assertNotNull( $this->repository->get( 'good-font' ) );
	}

	public function test_a_file_a_row_already_records_is_not_imported_again() {
		$this->drop( 'Claimed.ttf' );

		$this->repository->insert(
			[
				'font_key' => 'claimed',
				'label'    => 'Claimed',
				'source'   => 'packs',
				'files'    => [ 'R' => [ 'path' => 'Claimed.ttf', 'size' => 3 ] ],
			]
		);

		$this->assertSame( 0, $this->importer->run() );
		$this->assertCount( 1, $this->repository->all() );
	}

	public function test_a_file_named_by_the_6x_snapshot_belongs_to_the_migration() {
		$this->drop( 'Migrating.ttf' );

		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				'migrating' => [
					'id'        => 'migrating',
					'font_name' => 'Migrating',
					'regular'   => $this->font_dir . 'Migrating.ttf',
				],
			]
		);

		$this->assertSame( 0, $this->importer->run() );
		$this->assertNull( $this->repository->get( 'migrating' ) );
	}

	public function test_a_second_pass_imports_nothing() {
		$this->drop( 'Once.ttf' );

		$this->assertSame( 1, $this->importer->run() );
		$this->assertSame( 0, $this->importer->run() );
		$this->assertCount( 1, $this->repository->all() );
	}

	public function test_a_key_collision_is_suffixed_and_both_spellings_survive() {
		$this->drop( 'Taken.ttf' );

		$this->repository->insert(
			[
				'font_key' => 'taken',
				'label'    => 'Taken',
				'source'   => 'custom',
				'files'    => [ 'R' => [ 'path' => 'something-else.ttf', 'size' => 3 ] ],
			]
		);

		$this->assertSame( 1, $this->importer->run() );

		$keys = array_keys( $this->repository->all() );

		$this->assertContains( 'taken', $keys );

		$suffixed = array_values( array_diff( $keys, [ 'taken' ] ) )[0];
		$this->assertStringStartsWith( 'taken-', $suffixed );
		$this->assertSame( 'Taken.ttf', $this->repository->get( $suffixed )['files']['R']['path'] );
	}

	public function test_otf_files_are_ignored_as_they_always_were() {
		copy( PDF_PLUGIN_DIR . 'tools/phpunit/data/fonts/DejaVuSans.ttf', $this->font_dir . 'Ignored.otf' );

		$this->assertSame( 0, $this->importer->run() );
	}

	public function test_an_empty_directory_imports_nothing() {
		$this->assertSame( 0, $this->importer->run() );
		$this->assertSame( [], $this->repository->all() );
	}

	public function test_the_imported_font_renders() {
		$this->drop( 'Renderable.ttf' );
		$this->importer->run();

		$registry = GPDFAPI::get_font_registry();

		$this->assertArrayHasKey( 'renderable', $registry->installed_package()->getFonts() );
	}
}
