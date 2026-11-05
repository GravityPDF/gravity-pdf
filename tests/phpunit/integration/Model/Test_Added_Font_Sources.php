<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\Seamed_Font_Downloader;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Font sources a plugin adds through `gfpdf_font_sources`
 *
 * @group model
 * @group fonts
 */
class Test_Added_Font_Sources extends TestCase {

	use CopiesFontFixtures;

	/**
	 * @var Model_Font_Sources
	 */
	protected $model;

	/**
	 * @var TestHandler
	 */
	protected $log;

	/**
	 * @var int How often `gfpdf_font_sources_changed` fired
	 */
	protected $changes = 0;

	public function set_up(): void {
		parent::set_up();

		$this->model = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		wp_mkdir_p( GPDFAPI::get_data_class()->template_font_location );

		$this->log = new TestHandler();
		GPDFAPI::get_log_class()->pushHandler( $this->log );

		add_action(
			'gfpdf_font_sources_changed',
			function () {
				$this->changes++;
			}
		);
	}

	public function tear_down(): void {
		GPDFAPI::get_log_class()->popHandler();
		$this->remove_font_fixtures();
		delete_option( Model_Font_Sources::SOURCE_VERSIONS );
		GPDFAPI::get_options_class()->delete_option( 'default_font' );
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );

		parent::tear_down();

		/* The filter is gone now, so rebuild the sources without the fixture */
		$this->rebuild_font_sources();
	}

	public function test_an_added_source_is_registered() {
		$this->add_fixture_source( [ 'dir' => PDF_PLUGIN_DIR . 'src/' ] );

		$sources = $this->model->get_sources();

		$this->assertSame( [ 'bundled', 'core', 'fixture' ], array_keys( $sources ) );
		$this->assertSame( [ 'version', 'families', 'manifest', 'group', 'required' ], array_keys( $sources['fixture'] ) );
		$this->assertTrue( $this->model->is_family_key( 'fixturesans' ) );
		$this->assertTrue( $this->model->is_core_font_file( 'Chewy.ttf' ) );
		$this->assertFalse( $this->log->hasRecords( Logger::ERROR ) );

		/* Only the bundled source has a dir */
		$font_dir = GPDFAPI::get_data_class()->template_font_location;
		$this->assertSame( [ $font_dir, PDF_PLUGIN_DIR . 'fonts/' ], $this->model->register_font_directories( [ 'fontDir' => [ $font_dir ] ] )['fontDir'] );
	}

	public function test_an_added_source_registers_its_families_before_custom_fonts() {
		$this->add_fixture_source();

		$fonts = apply_filters( 'mpdf_font_data', [] );
		$this->assertSame( [ 'R' => 'Chewy.ttf' ], $fonts['fixturesans'] );

		$this->add_record( 'fixturesans', [ 'regular' => 'Override.ttf' ] );
		$fonts = apply_filters( 'mpdf_font_data', [] );
		$this->assertSame( 'Override.ttf', $fonts['fixturesans']['R'] );
	}

	public function test_an_added_source_is_listed_only_in_its_own_group() {
		$this->add_fixture_source();

		$fonts = GPDFAPI::get_pdf_fonts();

		$this->assertSame( [ 'fixturesans' => 'Fixture Sans' ], $fonts['Fixture Fonts'] );
		$this->assertSame( 1, $this->count_listings( $fonts, 'fixturesans' ) );
	}

	public function test_an_added_source_without_a_group_is_unlisted() {
		$this->add_fixture_source( [ 'group' => null ] );

		$this->assertTrue( $this->model->is_family_key( 'fixturesans' ) );
		$this->assertSame( 0, $this->count_listings( GPDFAPI::get_pdf_fonts(), 'fixturesans' ) );
	}

	/**
	 * @dataProvider provider_invalid_sources
	 *
	 * @param mixed $id
	 * @param mixed $changes Keys to change in the fixture source, or a whole non-array source
	 */
	public function test_an_invalid_source_is_dropped_and_logged( $id, array $changes, string $reason ) {
		$this->add_fixture_source( $changes, null, $id );

		$this->assert_dropped( $reason );
	}

	public function test_a_source_that_is_not_an_array_is_dropped() {
		add_filter(
			'gfpdf_font_sources',
			static function ( $sources ) {
				return $sources + [ 'fixture' => 'fixture' ];
			}
		);
		$this->rebuild_font_sources();

		$this->assert_dropped( 'it is not an array' );
	}

	public function provider_invalid_sources(): array {
		return [
			'missing manifest'       => [ 'fixture', [ 'manifest' => '/missing/manifest.php' ], 'its manifest is missing' ],
			'no manifest'            => [ 'fixture', [ 'manifest' => null ], 'its manifest is missing' ],
			'no families'            => [ 'fixture', [ 'families' => [] ], 'it has no families' ],
			'a family with no R'     => [ 'fixture', [ 'families' => [ 'fixturesans' => [ 'B' => 'Chewy.ttf' ] ] ], 'a family is invalid' ],
			'a family file path'     => [ 'fixture', [ 'families' => [ 'fixturesans' => [ 'R' => '../Chewy.ttf' ] ] ], 'a family is invalid' ],
			'an upper case key'      => [ 'fixture', [ 'families' => [ 'FixtureSans' => [ 'R' => 'Chewy.ttf' ] ] ], 'a family is invalid' ],
			'an empty group'         => [ 'fixture', [ 'group' => '' ], 'its group is not a label' ],
			'an array version'       => [ 'fixture', [ 'version' => [ 1 ] ], 'its version is not a string' ],
			'bad legacy hashes'      => [ 'fixture', [ 'legacy' => [ 'abc' => 100 ] ], 'its legacy hashes are invalid' ],
			'a numeric ID'           => [ 0, [], 'its ID is not a lowercase key' ],
			'an ID with spaces'      => [ 'Fixture Fonts', [], 'its ID is not a lowercase key' ],
			'the reserved core ID'   => [ 'core', [], 'its ID is reserved' ],
			'the reserved bundle ID' => [ 'bundled', [], 'its ID is reserved' ],
		];
	}

	public function test_a_reserved_source_keeps_its_own_definition() {
		$core = $this->model->get_sources()['core'];

		$this->add_fixture_source( [], null, 'core' );

		$this->assertSame( $core, $this->model->get_sources()['core'] );
		$this->assertFalse( $this->model->is_family_key( 'fixturesans' ) );
	}

	public function test_a_filter_that_removes_the_core_sources_does_not_unregister_them() {
		add_filter( 'gfpdf_font_sources', '__return_empty_array' );
		$this->rebuild_font_sources();

		$this->assertSame( [ 'bundled', 'core' ], array_keys( $this->model->get_sources() ) );
		$this->assertFalse( $this->log->hasRecords( Logger::ERROR ) );
	}

	/**
	 * @dataProvider provider_invalid_manifests
	 *
	 * @param mixed $manifest
	 */
	public function test_an_invalid_manifest_is_rejected_whole_when_first_read( $manifest, string $message ) {
		$this->add_fixture_source( [ 'required' => true ], $manifest );
		$this->assertFalse( $this->log->hasRecords( Logger::ERROR ) );

		$this->assertSame( [], $this->model->get_source_manifest( 'fixture' ) );
		$this->assertFalse( $this->model->is_core_font_file( 'Chewy.ttf' ) );
		$this->assertArrayNotHasKey( 'OFL-Fixture.txt', $this->model->get_manifest() );
		$this->assertTrue( $this->log->hasRecordThatContains( $message, Logger::ERROR ) );
	}

	public function provider_invalid_manifests(): array {
		$licence = [ 'OFL-Fixture.txt' => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/OFL-Fixture.txt' ) ];

		return [
			'an http:// URL'               => [ $licence + [ 'Chewy.ttf' => $this->manifest_entry( 'Chewy.ttf', 'http://example.com/Chewy.ttf' ) ], 'Font manifest rejected: an entry is invalid' ],
			'a family file not on it'      => [ $licence, 'Font manifest rejected: a family file is not on it' ],
			'a manifest returning a value' => [ '<?php return "fixture";', 'Font manifest is missing or invalid' ],
		];
	}

	public function test_adding_a_source_adopts_its_records_and_syncs_once() {
		$this->assertSame( 10, has_action( 'admin_init', [ $this->model, 'check_added_sources' ] ) );
		$this->assertSame( 10, has_action( 'gfpdf_font_sources_changed', [ GPDFAPI::get_mvc_class( 'Model_Font_Downloader' ), 'schedule_sync' ] ) );

		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'fixturesans', [ 'regular' => 'Chewy.ttf' ] );
		$this->add_fixture_source();

		$this->model->check_added_sources();

		$this->assertArrayNotHasKey( 'fixturesans', $this->custom_font_records() );
		$this->assertSame( [ 'fixture' => '1.0.0' ], get_option( Model_Font_Sources::SOURCE_VERSIONS ) );
		$this->assertSame( 1, $this->changes );
		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );

		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );
		$this->model->check_added_sources();

		$this->assertSame( 1, $this->changes );
		$this->assertFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function test_bumping_a_sources_version_adopts_and_syncs_again() {
		update_option( Model_Font_Sources::SOURCE_VERSIONS, [ 'fixture' => '1.0.0' ] );

		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'fixturesans', [ 'regular' => 'Chewy.ttf' ] );
		$this->add_fixture_source( [ 'version' => '1.1.0' ] );

		$this->model->check_added_sources();

		$this->assertArrayNotHasKey( 'fixturesans', $this->custom_font_records() );
		$this->assertSame( [ 'fixture' => '1.1.0' ], get_option( Model_Font_Sources::SOURCE_VERSIONS ) );
		$this->assertSame( 1, $this->changes );
		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function test_an_unchanged_source_keeps_an_edited_record() {
		update_option( Model_Font_Sources::SOURCE_VERSIONS, [ 'fixture' => '1.0.0' ] );

		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'fixturesans', [ 'regular' => 'Chewy.ttf' ] );
		$this->add_fixture_source();

		$this->model->check_added_sources();

		$this->assertArrayHasKey( 'fixturesans', $this->custom_font_records() );
		$this->assertSame( 0, $this->changes );
	}

	public function test_a_removed_source_is_forgotten() {
		update_option( Model_Font_Sources::SOURCE_VERSIONS, [ 'fixture' => '1.0.0' ] );

		$this->model->check_added_sources();

		$this->assertSame( [], get_option( Model_Font_Sources::SOURCE_VERSIONS ) );
		$this->assertSame( 0, $this->changes );
	}

	public function test_a_source_with_a_rejected_manifest_is_not_recorded() {
		$this->add_fixture_source( [], [ 'Other.ttf' => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Other.ttf' ) ] );

		$this->model->check_added_sources();

		$this->assertSame( [], get_option( Model_Font_Sources::SOURCE_VERSIONS ) );
		$this->assertSame( 0, $this->changes );
	}

	public function test_a_site_without_added_sources_stores_the_versions_once_to_autoload_them() {
		$this->model->check_added_sources();
		$this->assertSame( [], get_option( Model_Font_Sources::SOURCE_VERSIONS ) );

		add_action(
			'update_option_' . Model_Font_Sources::SOURCE_VERSIONS,
			function () {
				$this->fail( 'The versions were stored again' );
			}
		);
		$this->model->check_added_sources();
		$this->assertSame( 0, $this->changes );
	}

	public function test_add_pdf_font_is_a_no_op_only_while_the_source_is_registered() {
		$font = [
			'font_name' => 'Fixture Sans',
			'regular'   => $this->font_fixture( 'Chewy.ttf' ),
		];

		$this->add_fixture_source();
		$this->assertTrue( GPDFAPI::add_pdf_font( $font ) );
		$this->assertSame( [], $this->custom_font_records() );

		remove_all_filters( 'gfpdf_font_sources' );
		$this->rebuild_font_sources();
		$this->recorded_fonts[] = 'fixturesans';
		$this->copied_fonts[]   = $this->font_path( 'Chewy.ttf' );

		$this->assertTrue( GPDFAPI::add_pdf_font( $font ) );
		$this->assertArrayHasKey( 'fixturesans', $this->custom_font_records() );
	}

	public function test_only_a_required_sources_files_are_required() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$required = new Model_Required_Fonts( $this->model, GPDFAPI::get_options_class(), GPDFAPI::get_log_class() );

		$this->add_fixture_source();
		$this->assertSame( [], $required->get_required_font_files() );

		$this->add_fixture_source( [ 'required' => true ] );
		$this->assertSame( [ 'Chewy.ttf', 'OFL-Fixture.txt' ], $required->get_required_font_files() );
	}

	public function test_an_added_file_colliding_with_a_core_file_has_the_hashed_name_everywhere() {
		$hashed = 'DejaVuSerifCondensed-' . substr( $this->manifest_entry( 'Chewy.ttf', '' )['sha'], 0, 8 ) . '.ttf';
		$this->add_colliding_source( 'Chewy.ttf', [ 'required' => true ] );

		$sources = $this->model->get_sources();

		/* Font data: the added family uses the hashed name, the core family keeps its own */
		$fonts = $this->model->register_font_data( [] );
		$this->assertSame( $hashed, $sources['fixture']['families']['fixturesans']['R'] );
		$this->assertSame( $hashed, $fonts['fixturesans']['R'] );
		$this->assertSame( 'DejaVuSerifCondensed.ttf', $fonts['dejavuserifcondensed']['R'] );

		/* Manifest: the core file keeps the name, the added one takes the hashed name, with its own URL */
		$this->assertSame( [ $hashed ], array_keys( $this->model->get_source_manifest( 'fixture' ) ) );
		$this->assertArrayHasKey( 'DejaVuSerifCondensed.ttf', $this->model->get_source_manifest( 'core' ) );
		$this->assertSame( $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Fixture-Chewy.ttf' ), $this->model->get_manifest()[ $hashed ] );
		$this->assertSame( $this->manifest_entry( 'DejaVuSerifCondensed.ttf', '' )['sha'], $this->model->get_manifest()['DejaVuSerifCondensed.ttf']['sha'] );
		$this->assertTrue( $this->model->is_core_font_file( $hashed ) );
		$this->assertTrue( $this->log->hasRecordThatContains( 'Font file name used by two sources for different files', Logger::NOTICE ) );

		/* A required added source's file is required under the hashed name */
		$required = new Model_Required_Fonts( $this->model, GPDFAPI::get_options_class(), GPDFAPI::get_log_class() );
		$this->assertContains( $hashed, $required->get_required_font_files() );

		/* Download path: the added file is verified and saved under the hashed name, never over the core file's name */
		$this->serve_font_downloads();
		$this->copied_fonts[] = $this->font_path( $hashed );
		$downloader           = new Seamed_Font_Downloader( GPDFAPI::get_log_class(), [ 'sources' => $this->model ] );

		$this->assertFalse( $this->model->is_installed( $hashed ) );
		$this->assertTrue( $downloader->download( $hashed ) );
		$this->assertSame( [ 'https://example.com/Fixture-Chewy.ttf' ], $this->requested );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( $hashed ) );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertSame( $this->font_path( $hashed ), $this->model->get_font_path( $hashed ) );
		$this->assertTrue( $this->model->is_installed( $hashed ) );
		$this->assertFalse( $this->model->is_installed( 'DejaVuSerifCondensed.ttf' ), 'The core font is not on disk' );
	}

	public function test_an_added_file_with_the_same_bytes_as_a_core_file_shares_its_file() {
		$this->add_colliding_source( 'DejaVuSerifCondensed.ttf' );

		$sources = $this->model->get_sources();
		$fonts   = $this->model->register_font_data( [] );

		$this->assertSame( 'DejaVuSerifCondensed.ttf', $sources['fixture']['families']['fixturesans']['R'] );
		$this->assertSame( 'DejaVuSerifCondensed.ttf', $fonts['fixturesans']['R'] );
		$this->assertSame( [ 'DejaVuSerifCondensed.ttf' ], array_keys( $this->model->get_source_manifest( 'fixture' ) ) );
		$this->assertFalse( $this->log->hasRecordThatContains( 'Font file name used by two sources', Logger::NOTICE ) );

		/* One manifest entry, and so one download, for the one file */
		$this->assertCount( 1, array_keys( $this->model->get_manifest(), $this->model->get_manifest()['DejaVuSerifCondensed.ttf'], true ) );
		$this->assertSame( $this->model->get_font_path( 'DejaVuSerifCondensed.ttf' ), $this->font_path( 'DejaVuSerifCondensed.ttf' ) );

		$this->serve_font_downloads();
		$this->copied_fonts[] = $this->font_path( 'DejaVuSerifCondensed.ttf' );
		$downloader           = new Seamed_Font_Downloader( GPDFAPI::get_log_class(), [ 'sources' => $this->model ] );

		$this->assertTrue( $downloader->download( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertCount( 1, $this->requested );
		$this->assertFileEquals( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ), $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertTrue( $this->model->is_installed( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFalse( $this->model->has_hashed_copy( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_invalid_sources_and_manifests_do_not_stop_a_valid_source_being_renamed() {
		$licence = [ 'OFL-Fixture.txt' => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/OFL-Fixture.txt' ) ];
		$name    = 'DejaVuSerifCondensed.ttf';
		$hashed  = 'DejaVuSerifCondensed-' . substr( $this->manifest_entry( 'Chewy.ttf', '' )['sha'], 0, 8 ) . '.ttf';

		/* Not arrays, a family without a Regular file, a path as a file, and no manifest are dropped before any manifest is read */
		add_filter(
			'gfpdf_font_sources',
			function ( $sources ) {
				return $sources + [
					'notarray'  => 'x',
					'nofamily'  => [ 'families' => 'x', 'manifest' => $this->write_manifest_file( [] ) ],
					'badpath'   => [ 'families' => [ 'a' => [ 'R' => '../DejaVuSerifCondensed.ttf' ] ], 'manifest' => $this->write_manifest_file( [] ) ],
					'nomanifest' => [ 'families' => [ 'b' => [ 'R' => 'B.ttf' ] ] ],
				];
			}
		);

		/* A rejected manifest (an http:// entry, a family file missing, a non-array) takes no file name */
		$this->add_fixture_source( [ 'families' => [ 'httpsans' => [ 'R' => $name ] ] ], [ $name => $this->manifest_entry( 'Chewy.ttf', 'http://example.com/a.ttf' ) ], 'http' );
		$this->add_fixture_source( [ 'families' => [ 'missingsans' => [ 'R' => $name ] ] ], $licence, 'missing' );
		$this->add_fixture_source( [ 'families' => [ 'valuesans' => [ 'R' => $name ] ] ], '<?php return "x";', 'value' );
		$this->add_fixture_source( [ 'families' => [ 'goodsans' => [ 'R' => $name ] ] ], [ $name => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Good.ttf' ) ], 'good' );

		$sources = $this->model->get_sources();

		$this->assertSame( [ 'bundled', 'core', 'http', 'missing', 'value', 'good' ], array_keys( $sources ) );
		$this->assertSame( [], $this->model->get_source_manifest( 'http' ) );
		$this->assertSame( [], $this->model->get_source_manifest( 'missing' ) );
		$this->assertSame( [], $this->model->get_source_manifest( 'value' ) );
		$this->assertSame( [ $hashed ], array_keys( $this->model->get_source_manifest( 'good' ) ) );
		$this->assertSame( $hashed, $sources['good']['families']['goodsans']['R'] );

		/* The rejected sources keep their families as listed, and the notice is for the one valid source */
		$this->assertSame( $name, $sources['http']['families']['httpsans']['R'] );
		foreach ( [ 'notarray', 'nofamily', 'badpath', 'nomanifest' ] as $id ) {
			$this->assertArrayNotHasKey( $id, $sources );
		}
		$this->assertCount( 1, array_filter( $this->log->getRecords(), static function ( $record ) {
			return strpos( $record['message'], 'Font file name used by two sources' ) === 0;
		} ) );
		$this->assertTrue( $this->log->hasRecordThatContains( 'Font manifest rejected: an entry is invalid', Logger::ERROR ) );
		$this->assertTrue( $this->log->hasRecordThatContains( 'Font manifest rejected: a family file is not on it', Logger::ERROR ) );
	}

	public function test_the_filter_runs_once_and_the_sources_are_memoised() {
		$runs   = 0;
		$inside = null;
		add_filter(
			'gfpdf_font_sources',
			function ( $sources ) use ( &$runs, &$inside ) {
				$runs++;
				$inside = $this->model->get_sources();

				/* Reading a manifest from the callback must not stick, as it only sees the core sources */
				$this->model->get_manifest();

				return $sources;
			},
			5
		);
		$this->add_colliding_source( 'Chewy.ttf' );
		$hashed = 'DejaVuSerifCondensed-' . substr( $this->manifest_entry( 'Chewy.ttf', '' )['sha'], 0, 8 ) . '.ttf';

		$first = $this->model->get_sources();
		$this->model->get_manifest();
		$this->model->get_source_manifest( 'fixture' );
		$this->model->register_font_data( [] );
		$this->model->check_added_sources();
		$this->model->flush();

		$this->assertSame( 1, $runs );
		$this->assertSame( [ 'bundled', 'core' ], array_keys( $inside ), 'A callback gets the core sources, not a recursion' );
		$this->assertSame( $first, $this->model->get_sources() );
		$this->assertArrayHasKey( $hashed, $this->model->get_manifest() );
		$this->assertSame( [ 'fixture' => '1.0.0' ], get_option( Model_Font_Sources::SOURCE_VERSIONS ) );
	}

	/**
	 * Register an added source whose one file has a core file's name but the fixture's bytes
	 *
	 * @param string $fixture The fixture the source lists for DejaVuSerifCondensed.ttf
	 */
	protected function add_colliding_source( string $fixture, array $changes = [] ): void {
		$url = $fixture === 'Chewy.ttf' ? 'https://example.com/Fixture-Chewy.ttf' : 'https://example.com/Fixture-DejaVuSerifCondensed.ttf';
		$this->served_as[ basename( $url ) ] = $fixture;

		$this->add_fixture_source(
			$changes + [ 'families' => [ 'fixturesans' => [ 'name' => 'Fixture Sans', 'R' => 'DejaVuSerifCondensed.ttf' ] ] ],
			[ 'DejaVuSerifCondensed.ttf' => $this->manifest_entry( $fixture, $url ) ]
		);
	}

	/**
	 * Register the fixture source: `fixturesans`, whose Regular is Chewy, in a "Fixture Fonts" group
	 *
	 * @param array             $changes  Keys to replace in the source; a null value removes the key
	 * @param array|string|null $manifest Manifest entries, the file's raw contents, or null for Chewy and a licence
	 * @param mixed             $id       The source ID
	 */
	protected function add_fixture_source( array $changes = [], $manifest = null, $id = 'fixture' ): void {
		$manifest = $manifest ?? [
			'Chewy.ttf'       => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Chewy.ttf' ),
			'OFL-Fixture.txt' => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/OFL-Fixture.txt' ),
		];

		$source = array_merge(
			[
				'version'  => '1.0.0',
				'families' => [
					'fixturesans' => [
						'name' => 'Fixture Sans',
						'R'    => 'Chewy.ttf',
					],
				],
				'manifest' => $this->write_manifest_file( $manifest ),
				'group'    => 'Fixture Fonts',
			],
			$changes
		);

		$source = array_filter(
			$source,
			static function ( $value ) {
				return $value !== null;
			}
		);

		add_filter(
			'gfpdf_font_sources',
			static function ( $sources ) use ( $id, $source ) {
				$sources[ $id ] = $source;

				return $sources;
			}
		);

		$this->rebuild_font_sources();
	}

	/**
	 * The custom font records keyed by ID
	 */
	protected function custom_font_records(): array {
		return GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' )->get_custom_fonts();
	}

	/**
	 * Assert the registered sources are the core ones, logged as dropping the fixture for the reason
	 */
	protected function assert_dropped( string $reason ): void {
		$this->assertSame( [ 'bundled', 'core' ], array_keys( $this->model->get_sources() ) );
		$this->assertFalse( $this->model->is_family_key( 'fixturesans' ) );
		$this->assertTrue( $this->log->hasRecordThatContains( 'Font source dropped: ' . $reason, Logger::ERROR ) );
	}

	/**
	 * Make the registered font sources apply `gfpdf_font_sources` again
	 */
	protected function rebuild_font_sources(): void {
		$this->set_private( $this->model, 'sources', null );
		$this->set_private( $this->model, 'manifests', [] );
		$this->set_private( $this->model, 'manifest', null );
		$this->model->flush();
	}

	/**
	 * How many font list groups list the key
	 */
	protected function count_listings( array $fonts, string $key ): int {
		return count(
			array_filter(
				$fonts,
				static function ( $group ) use ( $key ) {
					return isset( $group[ $key ] );
				}
			)
		);
	}
}
