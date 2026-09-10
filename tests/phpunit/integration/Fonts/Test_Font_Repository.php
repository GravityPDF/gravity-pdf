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
 * Class Test_Font_Repository
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Repository extends TestCase {

	use HasFontRows;

	/**
	 * @var Font_Repository
	 */
	public $repository;

	/**
	 * @var string
	 */
	public $font_dir;

	public function set_up(): void {
		parent::set_up();

		$this->repository = $this->font_repository();
		$this->font_dir   = $this->font_dir();
	}

	public function tear_down(): void {
		$this->remove_font_rows();

		GPDFAPI::get_options_class()->update_option( 'custom_fonts', [] );

		parent::tear_down();
	}

	protected function write_font_file( string $name, string $contents = 'ttf' ): string {
		file_put_contents( $this->font_dir . $name, $contents );

		return $name;
	}

	protected function insert_font( string $font_key, array $overrides = [] ): int {
		return $this->install_font_row( $font_key, $overrides );
	}

	public function test_insert_round_trips_a_row_and_its_files() {
		$id = $this->insert_font( 'alpha' );

		$this->assertGreaterThan( 0, $id );

		$row = $this->repository->get( 'alpha' );

		$this->assertSame( $id, $row['id'] );
		$this->assertSame( 'Alpha', $row['label'] );
		$this->assertSame( 'custom', $row['source'] );
		$this->assertSame( 0, $row['coverage'] );
		$this->assertSame( 'test-alpha.ttf', $row['files']['R']['path'] );
		$this->assertSame( 3, $row['files']['R']['size'] );
	}

	public function test_meta_round_trips_as_json() {
		$this->insert_font( 'beta', [ 'meta' => [ 'backup_subs' => true, 'languages' => [ 'zh' ] ] ] );

		$row = $this->repository->get( 'beta' );

		$this->assertSame( [ 'backup_subs' => true, 'languages' => [ 'zh' ] ], $row['meta'] );
	}

	public function test_a_row_with_no_meta_reads_as_an_empty_array() {
		$this->insert_font( 'gamma' );

		$this->assertSame( [], $this->repository->get( 'gamma' )['meta'] );
	}

	public function test_get_returns_null_for_an_unknown_key() {
		$this->assertNull( $this->repository->get( 'nothing-here' ) );
	}

	public function test_update_changes_columns_and_leaves_files_alone() {
		$id = $this->insert_font( 'delta' );

		$this->assertTrue( $this->repository->update( $id, [ 'label' => 'Renamed', 'use_otl' => 255 ] ) );

		$row = $this->repository->get( 'delta' );

		$this->assertSame( 'Renamed', $row['label'] );
		$this->assertSame( 255, $row['use_otl'] );
		$this->assertSame( 'test-delta.ttf', $row['files']['R']['path'] );
	}

	public function test_a_write_is_visible_to_the_next_read_with_no_cache_flush() {
		$this->insert_font( 'epsilon' );

		$this->assertArrayHasKey( 'epsilon', $this->repository->all() );

		$this->repository->delete( 'epsilon', false );

		$this->assertArrayNotHasKey( 'epsilon', $this->repository->all() );
	}

	public function test_delete_removes_the_rows_and_the_file() {
		$this->insert_font( 'zeta' );

		$this->assertFileExists( $this->font_dir . 'test-zeta.ttf' );

		$this->assertTrue( $this->repository->delete( 'zeta' ) );

		$this->assertNull( $this->repository->get( 'zeta' ) );
		$this->assertFileDoesNotExist( $this->font_dir . 'test-zeta.ttf' );
	}

	public function test_a_file_another_row_still_records_is_not_unlinked() {
		$shared = $this->write_font_file( 'test-shared.ttf' );

		$this->repository->insert(
			[
				'font_key' => 'eta',
				'label'    => 'Eta',
				'source'   => 'packs',
				'files'    => [ 'R' => [ 'path' => $shared, 'size' => 3 ] ],
			]
		);

		$this->repository->insert(
			[
				'font_key' => 'theta',
				'label'    => 'Theta',
				'source'   => 'packs',
				'files'    => [ 'R' => [ 'path' => $shared, 'size' => 3 ] ],
			]
		);

		$this->repository->delete( 'eta' );

		$this->assertFileExists( $this->font_dir . $shared );

		$this->repository->delete( 'theta' );

		$this->assertFileDoesNotExist( $this->font_dir . $shared );
	}

	public function test_verify_flags_a_deleted_file_and_clears_it_when_restored() {
		$this->insert_font( 'iota' );

		$this->assertSame( [ 'missing' => [], 'restored' => [] ], $this->repository->verify() );

		unlink( $this->font_dir . 'test-iota.ttf' );

		$this->assertSame( [ 'test-iota.ttf' ], $this->repository->verify()['missing'] );
		$this->assertSame( 1, $this->repository->get( 'iota' )['files']['R']['missing'] );

		$this->write_font_file( 'test-iota.ttf' );

		$this->assertSame( [ 'test-iota.ttf' ], $this->repository->verify()['restored'] );
		$this->assertSame( 0, $this->repository->get( 'iota' )['files']['R']['missing'] );
	}

	public function test_verify_flags_a_truncated_file() {
		$this->insert_font( 'kappa' );

		$this->write_font_file( 'test-kappa.ttf', 'a' );

		$this->assertSame( [ 'test-kappa.ttf' ], $this->repository->verify()['missing'] );
	}

	public function test_verify_flags_every_row_recording_the_same_path() {
		$shared = $this->write_font_file( 'test-shared.ttf' );

		foreach ( [ 'lambda', 'mu' ] as $font_key ) {
			$this->repository->insert(
				[
					'font_key' => $font_key,
					'label'    => $font_key,
					'source'   => 'packs',
					'files'    => [ 'R' => [ 'path' => $shared, 'size' => 3 ] ],
				]
			);
		}

		unlink( $this->font_dir . $shared );
		$this->repository->verify();

		$this->assertSame( 1, $this->repository->get( 'lambda' )['files']['R']['missing'] );
		$this->assertSame( 1, $this->repository->get( 'mu' )['files']['R']['missing'] );
	}

	public function test_reserved_keys_are_unavailable() {
		foreach ( Font_Repository::RESERVED_KEYS as $font_key ) {
			$this->assertFalse( $this->repository->is_key_available( $font_key ), $font_key );
		}
	}

	public function test_the_core_14_names_stay_available_to_uploads() {
		$this->assertTrue( $this->repository->is_key_available( 'arial' ) );
		$this->assertTrue( $this->repository->is_key_available( 'helvetica' ) );
	}

	public function test_a_taken_key_is_suffixed() {
		$this->insert_font( 'nu' );

		$suffixed = $this->repository->unique_key( 'nu' );

		$this->assertNotSame( 'nu', $suffixed );
		$this->assertStringStartsWith( 'nu-', $suffixed );
		$this->assertTrue( $this->repository->is_key_available( $suffixed ) );
	}

	public function test_a_free_key_is_returned_unchanged() {
		$this->assertSame( 'xi', $this->repository->unique_key( 'xi' ) );
	}

	public function test_an_unknown_file_role_is_refused() {
		$id = $this->insert_font( 'omicron' );

		$this->assertFalse( $this->repository->insert_file( $id, 'X', [ 'path' => 'test-omicron.ttf', 'size' => 3 ] ) );
		$this->assertTrue( $this->repository->insert_file( $id, 'dict_thai', [ 'path' => 'test-omicron.ttf', 'size' => 3 ] ) );

		/* A copyleft face ships the notice and the text it cites, and the table is unique on (font_id, role) */
		$this->assertTrue( $this->repository->insert_file( $id, 'LICENSE', [ 'path' => 'test-omicron-licence.txt', 'size' => 3 ] ) );
		$this->assertTrue( $this->repository->insert_file( $id, 'LICENSE-2', [ 'path' => 'test-omicron-lgpl.txt', 'size' => 3 ] ) );
	}

	public function test_ensure_ready_is_a_no_op_once_the_version_is_recorded() {
		global $wpdb;

		$queries = $wpdb->num_queries;

		$this->assertTrue( $this->repository->ensure_ready() );
		$this->assertSame( $queries, $wpdb->num_queries );
	}

	public function test_the_repository_is_the_source_for_the_custom_fonts_facade() {
		global $gfpdf;

		$this->insert_font( 'pi', [ 'label' => 'Pi Font', 'use_otl' => 255, 'use_kashida' => 75 ] );

		$fonts = $gfpdf->singleton->get_class( 'Model_Custom_Fonts' )->get_custom_fonts();

		$this->assertArrayHasKey( 'pi', $fonts );
		$this->assertSame( 'pi', $fonts['pi']['id'] );
		$this->assertSame( 'Pi Font', $fonts['pi']['font_name'] );
		$this->assertSame( $this->font_dir . 'test-pi.ttf', $fonts['pi']['regular'] );
		$this->assertSame( '', $fonts['pi']['bold'] );
		$this->assertSame( 255, $fonts['pi']['useOTL'] );
		$this->assertSame( 75, $fonts['pi']['useKashida'] );
	}

	public function test_coverage_rows_are_hidden_from_the_custom_fonts_facade() {
		global $gfpdf;

		$this->insert_font( 'rho', [ 'source' => 'packs', 'entry' => 'arabic', 'coverage' => 1 ] );

		$this->assertArrayHasKey( 'rho', $this->repository->all() );
		$this->assertArrayNotHasKey( 'rho', $gfpdf->singleton->get_class( 'Model_Custom_Fonts' )->get_custom_fonts() );
	}
}
