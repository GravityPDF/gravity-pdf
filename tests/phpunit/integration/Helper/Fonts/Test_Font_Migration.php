<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Font_Migration
 *
 * @package   GFPDF\Helper\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Migration extends TestCase {

	/**
	 * @var Font_Repository
	 */
	public $repository;

	/**
	 * @var Font_Migration
	 */
	public $migration;

	/**
	 * @var string
	 */
	public $font_dir;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$this->repository = $gfpdf->get_font_repository();
		$this->font_dir   = $this->repository->get_font_dir();
		$this->migration  = new Font_Migration( GPDFAPI::get_options_class(), GPDFAPI::get_log_class() );

		wp_mkdir_p( $this->font_dir );
	}

	public function tear_down(): void {
		foreach ( array_keys( $this->repository->all() ) as $font_key ) {
			$this->repository->delete( $font_key, false );
		}

		foreach ( glob( $this->font_dir . 'test-*.ttf' ) ?: [] as $file ) {
			unlink( $file );
		}

		GPDFAPI::get_options_class()->update_option( 'custom_fonts', [] );

		parent::tear_down();
	}

	protected function store_legacy_fonts( array $fonts ): void {
		GPDFAPI::get_options_class()->update_option( 'custom_fonts', $fonts );
	}

	protected function write_font_file( string $name ): string {
		file_put_contents( $this->font_dir . $name, 'ttf' );

		return $this->font_dir . $name;
	}

	public function test_a_6x_record_becomes_a_row_with_relative_paths() {
		$this->store_legacy_fonts(
			[
				'myfont' => [
					'id'         => 'myfont',
					'font_name'  => 'My Font',
					'regular'    => $this->write_font_file( 'test-myfont.ttf' ),
					'bold'       => $this->write_font_file( 'test-myfont-b.ttf' ),
					'useOTL'     => 255,
					'useKashida' => 75,
				],
			]
		);

		$this->assertSame( 1, $this->migration->from_option( $this->repository ) );

		$row = $this->repository->get( 'myfont' );

		$this->assertSame( 'My Font', $row['label'] );
		$this->assertSame( 'custom', $row['source'] );
		$this->assertSame( 255, $row['use_otl'] );
		$this->assertSame( 75, $row['use_kashida'] );

		/* 6.x stored absolute paths, which break on every site move */
		$this->assertSame( 'test-myfont.ttf', $row['files']['R']['path'] );
		$this->assertSame( 'test-myfont-b.ttf', $row['files']['B']['path'] );
		$this->assertSame( 3, $row['files']['R']['size'] );
		$this->assertSame( 0, $row['files']['R']['missing'] );
	}

	public function test_the_snapshot_is_never_consumed() {
		$this->store_legacy_fonts(
			[
				'keepme' => [
					'id'        => 'keepme',
					'font_name' => 'Keep Me',
					'regular'   => $this->write_font_file( 'test-keepme.ttf' ),
				],
			]
		);

		$before = GPDFAPI::get_options_class()->get_option( 'custom_fonts' );

		$this->migration->from_option( $this->repository );

		$this->assertSame( $before, GPDFAPI::get_options_class()->get_option( 'custom_fonts' ) );
	}

	public function test_a_second_pass_creates_nothing() {
		$this->store_legacy_fonts(
			[
				'twice' => [
					'id'        => 'twice',
					'font_name' => 'Twice',
					'regular'   => $this->write_font_file( 'test-twice.ttf' ),
				],
			]
		);

		$this->assertSame( 1, $this->migration->from_option( $this->repository ) );
		$this->assertSame( 0, $this->migration->from_option( $this->repository ) );
		$this->assertCount( 1, $this->repository->all() );
	}

	public function test_a_face_missing_from_disk_is_flagged_not_given_a_fake_size() {
		$this->store_legacy_fonts(
			[
				'gone' => [
					'id'        => 'gone',
					'font_name' => 'Gone',
					'regular'   => $this->font_dir . 'test-never-written.ttf',
				],
			]
		);

		$this->migration->from_option( $this->repository );

		$file = $this->repository->get( 'gone' )['files']['R'];

		$this->assertSame( 1, $file['missing'] );
		$this->assertSame( 0, $file['size'] );
	}

	public function test_a_record_with_no_faces_is_skipped() {
		$this->store_legacy_fonts(
			[
				'empty' => [
					'id'        => 'empty',
					'font_name' => 'Empty',
				],
			]
		);

		$this->assertSame( 0, $this->migration->from_option( $this->repository ) );
		$this->assertNull( $this->repository->get( 'empty' ) );
	}

	public function test_an_existing_row_with_the_same_file_set_is_inherited_not_duplicated() {
		$path = $this->write_font_file( 'test-inherit.ttf' );

		$this->repository->insert(
			[
				'font_key' => 'firstsite',
				'label'    => 'First Site',
				'source'   => 'custom',
				'files'    => [ 'R' => [ 'path' => basename( $path ), 'size' => 3 ] ],
			]
		);

		$this->store_legacy_fonts(
			[
				'secondsite' => [
					'id'        => 'secondsite',
					'font_name' => 'Second Site',
					'regular'   => $path,
				],
			]
		);

		$this->assertSame( 0, $this->migration->from_option( $this->repository ) );
		$this->assertCount( 1, $this->repository->all() );
	}

	public function test_the_same_key_with_different_files_is_suffixed() {
		$this->repository->insert(
			[
				'font_key' => 'clash',
				'label'    => 'Clash',
				'source'   => 'custom',
				'files'    => [ 'R' => [ 'path' => basename( $this->write_font_file( 'test-clash-one.ttf' ) ), 'size' => 3 ] ],
			]
		);

		$this->store_legacy_fonts(
			[
				'clash' => [
					'id'        => 'clash',
					'font_name' => 'Clash',
					'regular'   => $this->write_font_file( 'test-clash-two.ttf' ),
				],
			]
		);

		$this->assertSame( 1, $this->migration->from_option( $this->repository ) );

		$keys = array_keys( $this->repository->all() );

		$this->assertCount( 2, $keys );
		$this->assertContains( 'clash', $keys );

		$renamed = array_values( array_diff( $keys, [ 'clash' ] ) )[0];
		$this->assertStringStartsWith( 'clash', $renamed );
		$this->assertSame( 'test-clash-two.ttf', $this->repository->get( $renamed )['files']['R']['path'] );
	}

	public function test_no_records_is_a_no_op() {
		$this->store_legacy_fonts( [] );

		$this->assertSame( 0, $this->migration->from_option( $this->repository ) );
	}

	/**
	 * The whole of step 1 of the upgrade routine, from an upgraded-but-unmigrated site
	 */
	public function test_ensure_ready_runs_the_migration_and_records_the_version() {
		global $gfpdf;

		$schema = $this->repository->get_schema();

		$this->store_legacy_fonts(
			[
				'ensured' => [
					'id'        => 'ensured',
					'font_name' => 'Ensured',
					'regular'   => $this->write_font_file( 'test-ensured.ttf' ),
				],
			]
		);

		/* Put the site back to "upgraded but not migrated": the tables are there, the version is not */
		delete_option( $schema::VERSION_OPTION );
		if ( is_multisite() ) {
			delete_site_option( $schema::VERSION_OPTION );
		}

		$repository = new Font_Repository(
			$schema,
			new Font_Migration( GPDFAPI::get_options_class(), GPDFAPI::get_log_class() ),
			new Font_Lock(),
			$gfpdf->misc,
			GPDFAPI::get_log_class(),
			$this->font_dir
		);

		$this->assertTrue( $repository->ensure_ready() );

		$this->assertNotNull( $repository->get( 'ensured' ) );
		$this->assertSame( $schema->get_version(), get_option( $schema::VERSION_OPTION ) );
	}
}
