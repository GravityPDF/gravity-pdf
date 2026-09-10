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
 * What the deleted core font installer left on disk keeps rendering
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Legacy_Font_Adopter extends TestCase {

	use HasFontRows;

	/**
	 * @var Legacy_Font_Adopter
	 */
	public $adopter;

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
		$this->adopter    = new Legacy_Font_Adopter( $this->repository, $gfpdf->log );
	}

	public function tear_down(): void {
		$this->remove_font_rows();
		$this->remove_font_files();

		parent::tear_down();
	}

	/**
	 * Put an installer font in the fonts directory
	 *
	 * The four DejaVu fixtures this suite uses are byte-identical to the files the 6.x installer downloaded, which
	 * is what lets the frozen hashes be exercised rather than mocked.
	 */
	protected function drop( string $filename ): void {
		$this->drop_font_fixture( $filename );
	}

	public function test_every_face_of_a_family_becomes_one_row() {
		$this->drop( 'DejaVuSans.ttf' );
		$this->drop( 'DejaVuSans-Bold.ttf' );

		$this->assertSame( 1, $this->adopter->run() );

		/* The 6.x key, so a template saying `font-family: dejavusans` still resolves */
		$row = $this->repository->get( 'dejavusans' );

		$this->assertNotNull( $row );
		$this->assertSame( 'imported', $row['source'] );
		$this->assertSame( 'DejaVuSans.ttf', $row['files']['R']['path'] );
		$this->assertSame( 'DejaVuSans-Bold.ttf', $row['files']['B']['path'] );

		/* Absent from disk, so absent from the row — no face pointing at a file that isn't there */
		$this->assertArrayNotHasKey( 'I', $row['files'] );
		$this->assertArrayNotHasKey( 'BI', $row['files'] );
	}

	public function test_the_shaping_flags_come_across_with_the_font() {
		$this->drop( 'DejaVuSans.ttf' );

		$this->adopter->run();

		$row = $this->repository->get( 'dejavusans' );

		$this->assertSame( 0xFF, $row['use_otl'] );
		$this->assertSame( 75, $row['use_kashida'] );
	}

	public function test_a_family_with_no_regular_face_is_skipped() {
		$this->drop( 'DejaVuSans-Bold.ttf' );

		$this->assertSame( 0, $this->adopter->run() );
		$this->assertNull( $this->repository->get( 'dejavusans' ) );
	}

	public function test_a_file_that_is_not_what_the_installer_wrote_gets_no_row() {
		$this->drop( 'DejaVuSans.ttf' );

		/* Right name and size, wrong bytes: a replaced or half-downloaded file */
		$path = $this->font_dir . 'DejaVuSans.ttf';
		$body = (string) file_get_contents( $path );
		file_put_contents( $path, 'X' . substr( $body, 1 ) );

		$this->assertSame( 0, $this->adopter->run() );
		$this->assertNull( $this->repository->get( 'dejavusans' ) );
	}

	public function test_a_truncated_file_gets_no_row() {
		$this->drop( 'DejaVuSansCondensed.ttf' );

		$path = $this->font_dir . 'DejaVuSansCondensed.ttf';
		file_put_contents( $path, substr( (string) file_get_contents( $path ), 0, 1024 ) );

		$this->assertSame( 0, $this->adopter->run() );
		$this->assertNull( $this->repository->get( 'dejavusanscondensed' ) );
	}

	public function test_a_second_pass_adopts_nothing_twice() {
		$this->drop( 'DejaVuSerifCondensed.ttf' );

		$this->assertSame( 1, $this->adopter->run() );
		$this->assertSame( 0, $this->adopter->run() );
	}

	public function test_a_row_already_holding_the_key_is_left_alone() {
		$this->drop( 'DejaVuSans.ttf' );

		/* A custom font the user uploaded under the same name: their row is the one templates resolve */
		$this->install_font_row( 'dejavusans' );

		$this->assertSame( 0, $this->adopter->run() );
		$this->assertSame( 'custom', $this->repository->get( 'dejavusans' )['source'] );
	}

	public function test_the_adopted_row_carries_the_settings_that_reached_the_font() {
		$this->drop( 'DejaVuSansCondensed.ttf' );

		$this->adopter->run();

		$meta = $this->repository->get( 'dejavusanscondensed' )['meta'];

		$this->assertTrue( $meta['legacy'] );
		$this->assertTrue( $meta['backup_subs'] );
		$this->assertTrue( $meta['bmp'] );
		$this->assertContains( 'ru', $meta['languages'] );
		$this->assertContains( 'und-latn', $meta['languages'] );

		/* Only `sun-exta` declares one, and mPDF looks the name up, so nothing else may carry it */
		$this->assertArrayNotHasKey( 'sip_ext', $meta );
	}

	public function test_a_family_six_never_routed_is_adopted_without_a_language() {
		$this->drop( 'DejaVuSerifCondensed.ttf' );

		$this->adopter->run();

		$meta = $this->repository->get( 'dejavuserifcondensed' )['meta'];

		$this->assertTrue( $meta['legacy'] );
		$this->assertArrayNotHasKey( 'languages', $meta );
	}

	public function test_the_backup_substitution_fonts_are_sixes_own_three_in_order() {
		$keys = array_keys( array_filter( Legacy_Installer_Files::FAMILIES, static fn( array $f ): bool => ! empty( $f['backup_subs'] ) ) );

		/* mPDF walks them in this order, and they arrive in it because the map is keyed alphabetically */
		$this->assertSame( [ 'dejavusanscondensed', 'freesans', 'sun-exta' ], $keys );
	}

	public function test_a_sip_extension_only_ever_names_a_family_the_map_can_adopt() {
		foreach ( Legacy_Installer_Files::FAMILIES as $font_key => $family ) {
			if ( isset( $family['sip_ext'] ) ) {
				$this->assertArrayHasKey( $family['sip_ext'], Legacy_Installer_Files::FAMILIES, $font_key );
			}
		}
	}

	public function test_no_language_is_routed_to_two_families() {
		$seen = [];

		foreach ( Legacy_Installer_Files::FAMILIES as $font_key => $family ) {
			foreach ( $family['languages'] ?? [] as $code ) {
				$this->assertSame( strtolower( $code ), $code, $font_key );
				$this->assertArrayNotHasKey( $code, $seen, $code );

				$seen[ $code ] = $font_key;
			}
		}

		/* 6.x's whole map less `und-mtei`, whose font — `eeyekunicode` — the installer never shipped */
		$this->assertCount( 172, $seen );
		$this->assertArrayNotHasKey( 'und-mtei', $seen );
	}

	public function test_every_manifest_entry_names_a_regular_face() {
		foreach ( Legacy_Installer_Files::FAMILIES as $font_key => $family ) {
			$this->assertArrayHasKey( 'R', $family['faces'], $font_key );

			foreach ( $family['faces'] as $role => $face ) {
				$this->assertContains( $role, Font_Repository::FACE_ROLES, $font_key );
				$this->assertSame( 40, strlen( $face['blob'] ), $face['name'] );
				$this->assertGreaterThan( 0, $face['size'], $face['name'] );
			}
		}
	}
}
