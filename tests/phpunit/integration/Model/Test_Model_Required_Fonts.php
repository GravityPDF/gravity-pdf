<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\StubsFontSources;
use GFPDF\Tests\Concerns\SwitchesNetwork;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GFFormsModel;
use GPDFAPI;

/**
 * @group model
 * @group fonts
 */
class Test_Model_Required_Fonts extends TestCase {

	use CopiesFontFixtures;
	use RecordsFontFailures;
	use StubsFontSources;
	use SwitchesNetwork;

	private const FONT = 'DejaVuSerifCondensed.ttf';

	private const GARUDA = [ 'Garuda.ttf', 'Garuda-Bold.ttf', 'Garuda-Oblique.ttf', 'Garuda-BoldOblique.ttf' ];

	/**
	 * @var Model_Required_Fonts
	 */
	private $required;

	public function set_up(): void {
		parent::set_up();

		$this->required = $this->required_with( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ) );
	}

	public function tear_down(): void {
		$this->remove_font_fixtures();
		$this->delete_font_failures();
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		/* A subsite leaves these behind */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
	}

	public function test_it_is_registered() {
		$this->assertInstanceOf( Model_Required_Fonts::class, GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) );
	}

	public function test_get_font_files_lists_a_core_keys_files_on_the_manifest() {
		$this->assertSame( [ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ], $this->required->get_font_files( ' DejaVuSerif ' ) );
		$this->assertSame( [ 'Sun-ExtA.ttf', 'Sun-ExtB.ttf' ], $this->required->get_font_files( 'sun-exta' ) );
		$this->assertSame( [ 'Sun-ExtB.ttf' ], $this->required->get_font_files( 'sun-extb' ) );
		$this->assertSame( [], $this->required->get_font_files( 'arimo' ) );
		$this->assertSame( [], $this->required->get_font_files( 'notafont' ) );
	}

	public function test_get_font_files_skips_a_custom_font() {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'chewy', [ 'regular' => 'Chewy.ttf' ] );

		$this->assertSame( [], $this->required->get_font_files( 'chewy' ) );
	}

	public function test_get_font_files_counts_a_sip_ext_font_one_level_deep() {
		add_filter(
			'mpdf_font_data',
			static function ( array $fontdata ): array {
				$fontdata['dejavuserif']['sip-ext']   = ' FreeSerif ';
				$fontdata['freeserif']['sip-ext']     = 'dejavuserif';
				$fontdata['garuda']['sip-ext']        = 'notafont';
				$fontdata['kaputaunicode']['sip-ext'] = [ 'freeserif' ];

				return $fontdata;
			},
			PHP_INT_MAX
		);

		$required = $this->required_with( $this->stub_font_sources() );
		$dejavu   = [ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ];

		$this->assertSame( array_merge( $dejavu, self::$freeserif ), $required->get_font_files( 'dejavuserif' ) );
		$this->assertSame( array_merge( self::$freeserif, $dejavu ), $required->get_font_files( 'freeserif' ) );
		$this->assertSame( self::GARUDA, $required->get_font_files( 'garuda' ) );
		$this->assertSame( [ 'kaputaunicode.ttf' ], $required->get_font_files( 'kaputaunicode' ) );
	}

	public function test_a_sun_exta_default_font_requires_sun_extb() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'sun-exta' );

		$this->assertSame( [ 'Sun-ExtA.ttf', 'Sun-ExtB.ttf' ], $this->required->get_required_font_files() );
		$this->assertSame( [ 'Sun-ExtA.ttf', 'Sun-ExtB.ttf' ], $this->required->get_install_font_files( [ 'sun-exta' ] ) );
	}

	public function test_a_sip_ext_font_is_in_use_while_a_font_linking_it_is() {
		$this->assertFalse( $this->required->is_font_in_use( 'sun-extb' ) );

		GPDFAPI::get_options_class()->update_option( 'default_font', 'sun-exta' );
		$this->assertTrue( $this->required->is_font_in_use( 'sun-extb' ) );

		GPDFAPI::get_options_class()->delete_option( 'default_font' );
		$this->add_form_with_pdfs( [ [ 'font' => 'sun-exta' ] ] );
		$this->assertTrue( $this->required->is_font_in_use( 'sun-extb' ) );
	}

	/**
	 * @dataProvider provider_junk_font_data
	 *
	 * @param mixed $fontdata
	 */
	public function test_get_font_files_ignores_junk_font_data( $fontdata ) {
		add_filter(
			'mpdf_font_data',
			static function () use ( $fontdata ) {
				return $fontdata;
			},
			PHP_INT_MAX
		);

		$this->assertSame( [], $this->required_with( $this->stub_font_sources() )->get_font_files( 'dejavuserif' ) );
	}

	public function provider_junk_font_data(): array {
		return [
			'a family string'  => [ [ 'dejavuserif' => 'DejaVuSerif.ttf' ] ],
			'non-string files' => [ [ 'dejavuserif' => [ 'R' => [ 'DejaVuSerif.ttf' ], 'B' => 1, 'I' => null ] ] ],
		];
	}

	public function test_the_required_set_has_the_default_language_and_selected_fonts() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'DejaVuSansCondensed' );
		$this->set_languages( [ 'th' ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ], [ 'sidebar_font' => 'DejaVuSerif', 'orientation' => 'portrait' ] ] );

		$this->assertSame(
			array_merge(
				[
					'DejaVuSansCondensed.ttf',
					'DejaVuSansCondensed-Bold.ttf',
					'DejaVuSansCondensed-Oblique.ttf',
					'DejaVuSansCondensed-BoldOblique.ttf',
					'Garuda.ttf',
					'Garuda-Bold.ttf',
					'Garuda-Oblique.ttf',
					'Garuda-BoldOblique.ttf',
				],
				self::$freeserif,
				/* A template's own font picker counts too */
				[ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ]
			),
			$this->required_with( $this->stub_font_sources() )->get_required_font_files()
		);
	}

	public function test_the_font_reasons_match_the_required_set() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'DejaVuSansCondensed' );
		$this->set_languages( [ 'th' ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ], [ 'font' => 'garuda' ] ] );
		$this->add_form_with_pdfs( [ [ 'sidebar_font' => 'FreeSerif' ] ] );

		$sources = $this->stub_font_sources(
			static function ( array $sources ): array {
				$sources['fixture']             = $sources['core'];
				$sources['fixture']['required'] = true;
				$sources['fixture']['families'] = [ 'fixturemono' => [ 'R' => 'FreeMono.ttf' ] ];

				return $sources;
			}
		);

		$none = [
			'default'  => false,
			'language' => false,
			'pdfs'     => 0,
			'source'   => false,
			'sites'    => 0,
			'pending'  => false,
		];

		$this->assertSame(
			[
				'dejavusanscondensed' => array_merge( $none, [ 'default' => true ] ),
				'garuda'              => array_merge(
					$none,
					[
						'language' => true,
						'pdfs'     => 1,
					]
				),
				'fixturemono'         => array_merge( $none, [ 'source' => true ] ),
				'freeserif'           => array_merge( $none, [ 'pdfs' => 2 ] ),
			],
			$this->required_with( $sources )->get_font_reasons( true )
		);
	}

	public function test_the_font_selections_are_read_once_until_flushed() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->assertSame( [], $this->required->get_required_font_files() );

		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		$this->assertSame( [], $this->required->get_required_font_files() );

		$this->required->flush();
		$this->assertSame( self::$freeserif, $this->required->get_required_font_files() );
	}

	public function test_the_required_set_is_empty_for_a_bundled_default_on_an_english_site() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->add_form_with_pdfs( [ [ 'font' => 'arimo' ] ] );

		$this->assertSame( [], $this->required->get_required_font_files() );
	}

	public function test_the_required_set_has_every_file_of_a_required_source() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );

		$sources = $this->stub_font_sources(
			static function ( array $sources ): array {
				$sources['fixture']             = $sources['core'];
				$sources['fixture']['required'] = true;
				$sources['fixture']['families'] = [];

				return $sources;
			}
		);

		$files = $this->required_with( $sources )->get_required_font_files();

		$this->assertCount( count( GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_manifest() ), $files );
		$this->assertContains( 'DejaVuinfo.txt', $files );
	}

	public function test_corrupt_settings_are_left_out_of_the_required_set() {
		GPDFAPI::get_options_class()->update_option( 'default_font', [ 'dejavuserif' ] );
		$this->add_form_with_pdfs( [ 'freeserif', [ 'font' => [ 'dejavuserif' ] ], [ 'font' => 7 ], [ 'font' => 'freeserif' ] ] );
		$this->gf_factory()->form->create(
			[],
			[
				'title'               => 'Corrupt',
				'fields'              => [],
				'gfpdf_form_settings' => 'dejavuserif',
			]
		);

		$this->assertSame( self::$freeserif, $this->required->get_required_font_files() );
	}

	/**
	 * @dataProvider provider_pdf_needs_download
	 */
	public function test_a_pdf_needs_a_download_when_a_font_it_names_has_files_missing( array $pdf, bool $on_disk, bool $expected ) {
		if ( $on_disk ) {
			$this->put_freeserif_on_disk();
		}

		$this->assertSame( $expected, $this->required->pdf_needs_download( $pdf ) );
	}

	public function provider_pdf_needs_download(): array {
		return [
			'the font setting'        => [ [ 'font' => 'FreeSerif' ], false, true ],
			'a template font picker'  => [ [ 'sidebar_font' => 'freeserif', 'orientation' => 'portrait' ], false, true ],
			'already on disk'         => [ [ 'font' => 'freeserif' ], true, false ],
			'the bundled Arimo'       => [ [ 'font' => 'arimo' ], false, false ],
			'a font named in HTML'    => [ [ 'header' => '<p>freeserif</p>' ], false, false ],
			'nested or not a string'  => [ [ 'nested' => [ 'font' => 'freeserif' ], 'size' => 12 ], false, false ],
		];
	}

	/**
	 * @dataProvider provider_needs_download
	 *
	 * @param mixed $font
	 */
	public function test_needs_download_when_a_font_file_is_missing( $font, bool $on_disk, bool $expected ) {
		if ( $on_disk ) {
			$this->put_freeserif_on_disk();
		}

		$this->assertSame( $expected, $this->required->needs_download( $font ) );
	}

	public function provider_needs_download(): array {
		return [
			'missing'          => [ 'FreeSerif', false, true ],
			'on disk'          => [ 'freeserif', true, false ],
			'the bundled font' => [ 'arimo', false, false ],
			'an unknown font'  => [ 'notafont', false, false ],
			'not a string'     => [ [ 'freeserif' ], false, false ],
			'null'             => [ null, false, false ],
		];
	}

	public function test_installed_counts_are_the_required_files_per_downloadable_source() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );

		$this->assertSame(
			[
				'core' => [
					'installed' => 0,
					'total'     => 4,
					'manifest'  => true,
				],
			],
			$this->required->get_installed_counts()
		);

		$this->copy_fixture( self::FONT );

		$this->assertSame( 1, $this->required->get_installed_counts()['core']['installed'] );
	}

	public function test_another_fonts_file_at_a_name_is_not_an_installed_file() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->put_other_font_at( self::FONT );

		$this->assertSame( 0, $this->required->get_installed_counts()['core']['installed'] );
		$this->assertTrue( $this->required->needs_download( 'dejavuserifcondensed' ) );
		$this->assertTrue( $this->required->pdf_needs_download( [ 'font' => 'dejavuserifcondensed' ] ) );

		/* Its copy under the hashed name is */
		$this->copy_fixture( self::FONT, GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( self::FONT ) );

		$this->assertSame( 1, $this->required->get_installed_counts()['core']['installed'] );
	}

	public function test_another_fonts_file_a_failed_download_found_is_not_installed() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->put_same_size_font_at( self::FONT, self::FONT );
		$path = $this->font_path( self::FONT );
		$this->set_font_failures( [ self::FONT => $this->conflict_failure( $path ) ] );

		$this->assertContains( self::FONT, $this->required->get_missing_files() );
		$this->assertSame( 0, $this->required->get_installed_counts()['core']['installed'] );
		$this->assertTrue( $this->required->needs_download( 'dejavuserifcondensed' ) );

		/* A file copied over it after the failure is installed again */
		touch( $path, filemtime( $path ) + 10 );
		clearstatcache();

		$this->assertNotContains( self::FONT, $this->required->get_missing_files() );
		$this->assertSame( 1, $this->required->get_installed_counts()['core']['installed'] );
	}

	public function test_a_family_sharing_a_name_with_a_record_requires_its_manifest_file() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->put_other_font_at( self::FONT );
		$this->add_record( 'mine', [ 'regular' => self::FONT ] );
		$hashed = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( self::FONT );

		$this->assertContains( self::FONT, $this->required->get_required_font_files() );
		$this->assertNotContains( $hashed, $this->required->get_required_font_files() );
		$this->assertContains( self::FONT, $this->required->get_missing_files(), 'The record\'s file is not the family\'s copy' );
		$this->assertSame( [ 'installed' => 0, 'total' => 4, 'manifest' => true ], $this->required->get_installed_counts()['core'] );

		$this->copy_fixture( self::FONT, $hashed );

		$this->assertSame( 1, $this->required->get_installed_counts()['core']['installed'] );
	}

	public function test_a_source_with_a_missing_manifest_is_flagged() {
		$this->assertSame(
			[
				'core' => [
					'installed' => 0,
					'total'     => 0,
					'manifest'  => false,
				],
			],
			$this->required_with( $this->font_sources_without_a_manifest() )->get_installed_counts()
		);
	}

	public function test_the_form_scan_reads_every_page() {
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'arimo' ] ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'dejavuserif' ] ] );

		$required = new class( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), GPDFAPI::get_options_class(), GPDFAPI::get_log_class() ) extends Model_Required_Fonts {
			/** @var int */
			protected $form_page = 2;
		};

		$this->assertSame(
			array_merge( self::$freeserif, [ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ] ),
			$required->get_required_font_files()
		);
	}

	public function test_the_in_use_check_stops_at_the_first_form_using_the_font() {
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'dejavuserif' ] ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'dejavuserif' ] ] );

		$required = new class( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), GPDFAPI::get_options_class(), GPDFAPI::get_log_class() ) extends Model_Required_Fonts {
			/** @var int */
			protected $form_page = 1;
		};

		$this->assert_form_reads(
			1,
			function () use ( $required ) {
			$this->assertTrue( $required->is_font_in_use( 'freeserif' ) );
			}
		);

		$this->assertTrue( $required->is_font_in_use( 'dejavuserif' ) );
		$this->assertFalse( $required->is_font_in_use( 'notafont' ) );
	}

	public function test_the_in_use_check_reads_no_forms_for_a_default_font_but_always_reads_them_otherwise() {
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserif' );
		$required = $this->required_with( $this->stub_font_sources() );

		$this->assert_form_reads(
			0,
			function () use ( $required ) {
			$this->assertTrue( $required->is_font_in_use( 'dejavuserif' ) );
			}
		);

		$required->get_font_pdfs();

		$this->assert_form_reads(
			2,
			function () use ( $required ) {
			$this->assertTrue( $required->is_font_in_use( 'freeserif' ) );
			$this->assertFalse( $required->is_font_in_use( 'notafont' ) );
			}
		);
	}

	private function assert_form_reads( int $expected, callable $callback ): void {
		$reads  = 0;
		$filter = static function ( $query ) use ( &$reads ) {
			$reads += strpos( (string) $query, 'gf_form_meta' ) !== false ? 1 : 0;

			return $query;
		};

		add_filter( 'query', $filter );
		$callback();
		remove_filter( 'query', $filter );

		$this->assertSame( $expected, $reads );
	}

	/**
	 * @dataProvider provider_unreadable_form_meta
	 *
	 * @param mixed $display_meta
	 */
	public function test_unreadable_form_meta_has_no_pdfs_and_is_logged( $display_meta, string $error ) {
		$log = new TestHandler();

		$this->assertSame( [], $this->required_with( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), $log )->get_form_pdfs( $display_meta, 5 ) );
		$this->assertSame( [ [ 'WARNING', 'Form meta could not be read for its PDF fonts', [ 'form_id' => 5, 'error' => $error ] ] ], $this->records( $log ) );
	}

	public function provider_unreadable_form_meta(): array {
		return [
			'invalid JSON'           => [ '{"gfpdf_form_settings":', 'Syntax error' ],
			'JSON that is no form'   => [ '"gfpdf_form_settings"', 'No error' ],
			'json_encode() failures' => [ false, 'Not a string: boolean' ],
			'decoded meta'           => [ [ 'gfpdf_form_settings' => [] ], 'Not a string: array' ],
		];
	}

	public function test_get_form_pdfs_keeps_only_the_pdf_settings_arrays() {
		$log      = new TestHandler();
		$required = $this->required_with( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), $log );
		$meta     = wp_json_encode( [ 'gfpdf_form_settings' => [ 'a' => [ 'font' => 'freeserif' ], 'b' => 'freeserif', 'c' => 7 ] ] );

		$this->assertSame( [ 'a' => [ 'font' => 'freeserif' ] ], $required->get_form_pdfs( $meta, 5 ) );
		$this->assertSame( [], $required->get_form_pdfs( wp_json_encode( [ 'gfpdf_form_settings' => 'freeserif' ] ), 5 ) );
		$this->assertSame( [], $required->get_form_pdfs( wp_json_encode( [ 'title' => 'No PDFs' ] ), 5 ) );
		$this->assertSame( [], $this->records( $log ) );
	}

	public function test_a_form_with_corrupt_stored_meta_is_logged_and_the_rest_still_read() {
		global $wpdb;

		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'dejavuserif' ] ] );
		$corrupt = (int) $wpdb->get_var( "SELECT MAX(form_id) FROM " . GFFormsModel::get_meta_table_name() ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		/* Still matches the scan's LIKE, as a truncated write would */
		$wpdb->update( GFFormsModel::get_meta_table_name(), [ 'display_meta' => '{"gfpdf_form_settings":{"pdf1":{"font":"dejavuserif"' ], [ 'form_id' => $corrupt ] ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$log = new TestHandler();

		$this->assertSame( self::$freeserif, $this->required_with( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), $log )->get_required_font_files() );
		$this->assertSame( [ [ 'WARNING', 'Form meta could not be read for its PDF fonts', [ 'form_id' => $corrupt, 'error' => 'Syntax error' ] ] ], $this->records( $log ) );
	}

	public function test_a_failed_form_query_is_logged() {
		global $wpdb;

		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );

		$break = static function ( $query ) {
			return strpos( $query, 'meta.display_meta LIKE' ) !== false ? str_replace( 'INNER JOIN', 'INNER JOIN gfpdf_missing_table missing INNER JOIN', $query ) : $query;
		};
		add_filter( 'query', $break );
		$suppressed = $wpdb->suppress_errors( true );

		$log = new TestHandler();

		try {
			$this->assertSame( [], $this->required_with( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), $log )->get_required_font_files() );
		} finally {
			remove_filter( 'query', $break );
			$wpdb->suppress_errors( $suppressed );
		}

		$records = $this->records( $log );
		$this->assertCount( 1, $records );
		$this->assertSame( [ 'ERROR', 'Forms could not be read for their PDF fonts' ], array_slice( $records[0], 0, 2 ) );
		$this->assertStringContainsString( 'gfpdf_missing_table', $records[0][2]['error'] );
	}

	public function test_a_sites_fonts_are_its_default_locale_and_pdf_fonts() {
		GPDFAPI::get_options_class()->update_option( 'default_font', ' DejaVuSerifCondensed ' );
		/* WordPress only keeps a locale it has installed */
		$this->set_languages( [ 'th' ] );
		update_option( 'WPLANG', 'th' );
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ], [ 'sidebar_font' => 'FreeSerif' ], [ 'font' => 'arimo' ] ] );

		try {
			$keys = $this->required_with( $this->stub_font_sources() )->get_site_font_keys( get_current_blog_id() );
		} finally {
			delete_option( 'WPLANG' );
		}

		$this->assertEqualsCanonicalizing( [ 'garuda', 'dejavuserifcondensed', 'freeserif', 'arimo' ], $keys );
	}

	public function test_a_site_with_no_default_font_saved_has_none() {
		GPDFAPI::get_options_class()->delete_option( 'default_font' );
		$required = $this->required_with( $this->stub_font_sources() );

		$this->assertSame( [], $required->get_site_font_keys( get_current_blog_id() ) );

		GPDFAPI::get_options_class()->update_option( 'default_font', 'freeserif' );
		$this->assertSame( [ 'freeserif' ], $required->get_site_font_keys( get_current_blog_id() ) );
	}

	public function test_corrupt_settings_leave_a_sites_default_font_unset() {
		update_option( 'gfpdf_settings', 'corrupt' );
		$required = $this->required_with( $this->stub_font_sources() );

		$this->assertSame( [], $required->get_site_font_keys( get_current_blog_id() ) );
	}

	public function test_a_site_gravity_forms_has_not_run_on_has_no_pdf_fonts() {
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		$version = get_option( 'gf_db_version' );
		delete_option( 'gf_db_version' );

		try {
			$keys = $this->required_with( $this->stub_font_sources() )->get_site_font_keys( get_current_blog_id() );
		} finally {
			update_option( 'gf_db_version', $version, false );
		}

		$this->assertSame( [], $keys );
		$this->assertSame( [ 'freeserif' ], $this->required_with( $this->stub_font_sources() )->get_site_font_keys( get_current_blog_id() ) );
	}

	public function test_on_its_own_a_site_requires_the_same_files_as_across_all_sites() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserif' );
		$this->set_languages( [ 'th' ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );

		$required = $this->required_with( $this->stub_font_sources() );
		$files    = $required->get_required_font_files();

		$this->assertCount( 12, $files );
		$this->assertEqualsCanonicalizing( $files, $this->all_sites_files( 'dejavusanscondensed', $required ) );
	}

	/**
	 * @dataProvider provider_unset_default_fonts
	 *
	 * @param mixed $settings
	 */
	public function test_a_site_without_a_saved_default_font_requires_the_one_given( $settings ) {
		update_option( 'gfpdf_settings', $settings );

		$this->assertSame( [], $this->all_sites_files() );
		$this->assertSame(
			[ 'DejaVuSansCondensed.ttf', 'DejaVuSansCondensed-Bold.ttf', 'DejaVuSansCondensed-Oblique.ttf', 'DejaVuSansCondensed-BoldOblique.ttf' ],
			$this->all_sites_files( 'dejavusanscondensed' )
		);
	}

	public function provider_unset_default_fonts(): array {
		return [
			'none saved'       => [ [ 'debug_mode' => 'No' ] ],
			'an empty font'    => [ [ 'default_font' => '' ] ],
			'corrupt settings' => [ 'arimo' ],
		];
	}

	public function test_every_sites_fonts_are_required_across_all_sites() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		update_blog_option( self::factory()->blog->create(), 'gfpdf_settings', [ 'default_font' => 'dejavuserif' ] );

		/* A site Gravity Forms hasn't run on has no form tables; its locale has no language pack */
		$no_forms = self::factory()->blog->create();
		update_blog_option( $no_forms, 'gfpdf_settings', [ 'default_font' => 'arimo' ] );
		/* Raw, as saving WPLANG drops a locale without its pack */
		$GLOBALS['wpdb']->replace( $GLOBALS['wpdb']->get_blog_prefix( $no_forms ) . 'options', [ 'option_name' => 'WPLANG', 'option_value' => 'th' ] );

		switch_to_blog( self::factory()->blog->create() );
		try {
			gf_upgrade()->install();
			update_option( 'gfpdf_settings', [ 'default_font' => 'arimo' ] );
			$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		} finally {
			restore_current_blog();
		}

		$this->assertSame( [], $this->required->get_required_font_files() );
		$this->assertSame(
			array_merge(
				[ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ],
				self::GARUDA,
				self::$freeserif
			),
			$this->all_sites_files()
		);
	}

	public function test_a_single_site_is_its_only_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only' );
		}

		$this->assertSame( [ get_current_blog_id() ], $this->required->get_site_ids() );
		$this->assertSame( [], $this->get_other_site_ids() );
	}

	public function test_the_site_ids_cover_every_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$same_network = self::factory()->blog->create();

		$this->on_another_network(
			function ( int $network ) use ( $same_network ) {
				$other_network = self::factory()->blog->create( [ 'network_id' => $network ] );
				$ids           = $this->required->get_site_ids();

				$this->assertContains( get_main_site_id( get_main_network_id() ), $ids );
				$this->assertContains( $same_network, $ids );
				$this->assertContains( $other_network, $ids );
			}
		);
	}

	public function test_the_other_sites_leave_out_only_the_current_one() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$site = self::factory()->blog->create();

		$others = $this->get_other_site_ids();
		$this->assertContains( $site, $others );
		$this->assertNotContains( get_current_blog_id(), $others );
		$this->assertSame( array_keys( $others ), range( 0, count( $others ) - 1 ), 'A list, with no gap where the current site was' );
	}

	/**
	 * @dataProvider provider_saved_default_fonts
	 */
	public function test_a_saved_default_font_is_used_over_the_one_given( string $saved ) {
		update_option( 'gfpdf_settings', [ 'default_font' => $saved ] );

		$keys = $this->required->get_site_font_keys( get_current_blog_id(), 'dejavusanscondensed' );

		$this->assertContains( 'dejavuserif', $keys );
		$this->assertNotContains( 'dejavusanscondensed', $keys );
	}

	public function provider_saved_default_fonts(): array {
		return [
			'as stored'        => [ 'dejavuserif' ],
			'mixed case'       => [ 'DejaVuSerif' ],
			'stray whitespace' => [ ' dejavuserif ' ],
		];
	}

	public function test_a_site_whose_tables_cant_be_read_has_no_keys() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->with_failing_site_option_reads(
			function () {
				$this->assertNull( $this->required->get_site_font_keys( get_main_site_id(), 'arimo' ) );
			}
		);
	}

	public function test_a_site_without_tables_has_only_the_unset_default_font() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->assertSame(
			[ 'arimo' ],
			$this->without_db_errors(
				function () {
					return $this->required->get_site_font_keys( 999999, 'arimo' );
				}
			)
		);
	}

	/**
	 * @dataProvider provider_install_font_keys
	 */
	public function test_the_install_font_files_are_those_of_the_keys_given( array $keys, array $expected ) {
		$this->set_languages( [] );

		$this->assertSame( $expected, $this->required_with( $this->stub_font_sources() )->get_install_font_files( $keys ) );
	}

	public function provider_install_font_keys(): array {
		return [
			'no keys'      => [ [], [] ],
			'unknown font' => [ [ 'not-a-font' ], [] ],
			'a font'       => [ [ 'garuda' ], self::GARUDA ],
			'a repeat'     => [ [ 'garuda', 'garuda' ], self::GARUDA ],
			'mixed'        => [ [ 'not-a-font', 'garuda' ], self::GARUDA ],
		];
	}

	public function test_the_install_font_files_include_the_installed_languages() {
		$this->set_languages( [ 'th' ] );

		$this->assertSame(
			self::GARUDA,
			$this->required_with( $this->stub_font_sources() )->get_install_font_files( [] )
		);
	}

	public function test_the_install_font_files_include_every_required_source() {
		$this->set_languages( [] );

		$sources = $this->stub_font_sources(
			static function ( array $sources ): array {
				$sources['fixture']             = $sources['core'];
				$sources['fixture']['required'] = true;

				return $sources;
			}
		);

		$this->assertContains( 'FreeMono.ttf', $this->required_with( $sources )->get_install_font_files( [] ) );
	}

	/**
	 * @return int[]
	 */
	private function get_other_site_ids(): array {
		return ( function () {
			return $this->get_other_site_ids();
		} )->call( $this->required );
	}

	/**
	 * The files every site needs, as the 6.19 cleanup gathers them
	 *
	 * @return string[]
	 */
	private function all_sites_files( string $unset_default = '', ?Model_Required_Fonts $required = null ): array {
		$required = $required ?? $this->required;
		$keys     = [];
		foreach ( $required->get_site_ids() as $site_id ) {
			$keys = array_merge( $keys, $required->get_site_font_keys( $site_id, $unset_default ) );
		}

		return $required->get_install_font_files( $keys );
	}

	/**
	 * Each log record as its level name, message and context
	 */
	private function records( TestHandler $log ): array {
		return array_map(
			static function ( $record ) {
				return [ $record['level_name'], $record['message'], $record['context'] ];
			},
			$log->getRecords()
		);
	}

	/**
	 * A required fonts model on the given sources, logging to the handler if one is given
	 */
	private function required_with( Model_Font_Sources $sources, ?TestHandler $log = null ): Model_Required_Fonts {
		return new Model_Required_Fonts( $sources, GPDFAPI::get_options_class(), $log ? new Logger( 'test', [ $log ] ) : GPDFAPI::get_log_class() );
	}

	private function unreadable_forms(): Model_Required_Fonts {
		return new class( $this->stub_font_sources(), GPDFAPI::get_options_class(), GPDFAPI::get_log_class() ) extends Model_Required_Fonts {
			protected function get_pdf_form_meta( int $after_id, int $limit, string $prefix = '' ): array {
				$this->form_read_failed = true;

				return [];
			}
		};
	}

	/**
	 * Create a form holding the given PDFs
	 *
	 * @param array $pdfs Each PDF's settings
	 */
	private function add_form_with_pdfs( array $pdfs ): void {
		$form_id = $this->gf_factory()->form->create(
			[],
			[
				'title'               => 'Fonts',
				'fields'              => [],
				'gfpdf_form_settings' => $pdfs,
			]
		);

		$this->assertIsInt( $form_id );
	}

	public function test_a_stored_map_is_read_without_the_forms() {
		$this->add_form_with_pdfs( [ 'one' => [ 'name' => 'Invoice', 'font' => 'freeserif' ] ] );

		$sources = $this->stub_font_sources();
		$this->assert_form_reads(
			1,
			function () use ( $sources ) {
				$this->assertCount( 1, $this->required_with( $sources )->get_font_pdfs()['freeserif'] );
			}
		);

		$this->assert_form_reads(
			0,
			function () use ( $sources ) {
				$pdfs = $this->required_with( $sources )->get_pdfs_using_font( 'freeserif' );

				$this->assertSame( 'Invoice', $pdfs[0]['name'] );
				$this->assertStringContainsString( 'pid=one', $pdfs[0]['url'] );
				$this->assertSame( [], $this->required_with( $sources )->get_pdfs_using_font( 'notafont' ) );
			}
		);
	}

	public function test_the_stored_map_is_not_autoloaded() {
		$this->required_with( $this->stub_font_sources() )->get_font_pdfs();

		global $wpdb;
		$this->assertContains(
			$wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", Model_Required_Fonts::FONT_PDFS ) ),
			[ 'off', 'no' ]
		);
	}

	public function test_a_day_old_map_is_rebuilt() {
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		update_option( Model_Required_Fonts::FONT_PDFS, [ 'built' => time() - DAY_IN_SECONDS - 1, 'pdfs' => [] ], false );

		$this->assertArrayHasKey( 'freeserif', $this->required_with( $this->stub_font_sources() )->get_font_pdfs() );
		$this->assertArrayHasKey( 'freeserif', get_option( Model_Required_Fonts::FONT_PDFS )['pdfs'] );
	}

	/**
	 * @dataProvider provider_form_changes
	 */
	public function test_a_form_change_clears_the_stored_map( string $action, array $args ) {
		update_option( Model_Required_Fonts::FONT_PDFS, [ 'built' => time(), 'pdfs' => [ 'stale' => [] ] ], false );

		do_action( $action, ...$args );

		$this->assertArrayNotHasKey( 'pdfs', get_option( Model_Required_Fonts::FONT_PDFS ) );
	}

	public function provider_form_changes(): array {
		return [
			'saved'       => [ 'gform_post_update_form_meta', [ '{}', 1, 'display_meta' ] ],
			'activated'   => [ 'gform_post_form_activated', [ 1 ] ],
			'deactivated' => [ 'gform_post_form_deactivated', [ 1 ] ],
			'trashed'     => [ 'gform_post_form_trashed', [ 1 ] ],
			'restored'    => [ 'gform_post_form_restored', [ 1 ] ],
			'duplicated'  => [ 'gform_post_form_duplicated', [ 1, 2 ] ],
			'deleted'     => [ 'gform_after_delete_form', [ 1 ] ],
		];
	}

	public function test_saving_other_form_meta_keeps_the_stored_map() {
		update_option( Model_Required_Fonts::FONT_PDFS, [ 'built' => time(), 'pdfs' => [] ], false );

		do_action( 'gform_post_update_form_meta', '{}', 1, 'notifications' );

		$this->assertArrayHasKey( 'pdfs', get_option( Model_Required_Fonts::FONT_PDFS ) );
	}

	public function test_a_real_form_rename_reaches_the_stored_map() {
		$form_id = $this->gf_factory()->form->create( [], [ 'title' => 'Before', 'fields' => [], 'gfpdf_form_settings' => [ [ 'font' => 'freeserif' ] ] ] );
		$sources = $this->stub_font_sources();
		$this->required_with( $sources )->get_font_pdfs();

		$form          = \GFAPI::get_form( $form_id );
		$form['title'] = 'After';
		\GFAPI::update_form( $form );

		$this->assertSame( 'After', $this->required_with( $sources )->get_font_pdfs()['freeserif'][0]['form'] );
	}

	public function test_changing_the_custom_fonts_clears_the_stored_map() {
		update_option( Model_Required_Fonts::FONT_PDFS, [ 'built' => time(), 'pdfs' => [] ], false );

		GPDFAPI::get_options_class()->update_option( 'custom_fonts', [ 'brand' => [ 'id' => 'brand' ] ] );

		$this->assertArrayNotHasKey( 'pdfs', get_option( Model_Required_Fonts::FONT_PDFS ) );

		GPDFAPI::get_options_class()->delete_option( 'custom_fonts' );
	}

	public function test_a_clear_during_a_rebuild_is_not_overwritten() {
		$required = new class( $this->stub_font_sources(), GPDFAPI::get_options_class(), GPDFAPI::get_log_class() ) extends Model_Required_Fonts {
			protected function get_pdf_form_meta( int $after_id, int $limit, string $prefix = '' ): array {
				$rows = parent::get_pdf_form_meta( $after_id, $limit, $prefix );
				$this->clear_font_usage();

				return $rows;
			}
		};

		$required->get_font_pdfs();

		$this->assertArrayNotHasKey( 'pdfs', get_option( Model_Required_Fonts::FONT_PDFS ) );
	}

	public function test_a_failed_form_read_is_not_stored() {
		$required = $this->unreadable_forms();

		$this->assertSame( [], $required->get_font_pdfs() );
		$this->assertArrayNotHasKey( 'pdfs', (array) get_option( Model_Required_Fonts::FONT_PDFS ) );
	}

	public function test_the_delete_check_ignores_a_stale_map() {
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		update_option( Model_Required_Fonts::FONT_PDFS, [ 'built' => time(), 'pdfs' => [] ], false );

		$this->assertTrue( $this->required_with( $this->stub_font_sources() )->is_font_in_use( 'freeserif' ) );
	}

	public function test_a_single_site_queues_no_site_record() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only' );
		}

		$this->required->queue_site_fonts();

		$this->assertFalse( has_action( 'shutdown', [ $this->required, 'write_queued_site_fonts' ] ) );
		$this->assertSame( [], $this->required->get_sites_without_fonts() );
	}

	public function test_each_site_records_its_fonts_in_its_own_row() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$site = self::factory()->blog->create();
		update_blog_option( $site, 'gfpdf_settings', [ 'default_font' => 'dejavuserif' ] );

		$this->required->write_site_fonts( $site );

		$this->assertSame( [ 'dejavuserif' ], get_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site ) );
		$this->assertNotContains( $site, $this->required->get_sites_without_fonts() );
		$this->assertContains( get_current_blog_id(), $this->required->get_sites_without_fonts() );
	}

	public function test_network_usage_is_one_query_on_the_records() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$site = self::factory()->blog->create();
		switch_to_blog( $site );
		try {
			gf_upgrade()->install();
			update_option( 'gfpdf_settings', [ 'default_font' => 'dejavuserif' ] );
			$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		} finally {
			restore_current_blog();
		}

		$this->required->write_site_fonts( $site );
		$this->required->get_font_pdfs();

		/* The one read is this site's live check; the other site's forms aren't read */
		$this->assert_form_reads(
			1,
			function () {
				$this->required->get_font_reasons( true );
				$this->assertTrue( $this->required->is_font_in_use( 'freeserif', true ) );
			}
		);

		$this->assertSame( 1, $this->required->get_font_reasons( true )['dejavuserif']['sites'] );
		$this->assertFalse( $this->required->is_font_in_use( 'notafont', true ) );
	}

	public function test_a_site_without_a_usable_record_counts_as_using_every_core_font() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$site = self::factory()->blog->create();
		update_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site, 'corrupt' );

		$reasons = $this->required->get_font_reasons( true );

		$this->assertTrue( $reasons['dejavuserif']['pending'] );
		$this->assertFalse( $reasons['arimo']['pending'] ?? false );
		$this->assertTrue( $this->required->is_font_in_use( 'dejavuserif', true ) );
	}

	public function test_a_sip_ext_font_is_in_use_while_another_site_uses_a_font_linking_it() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$site = self::factory()->blog->create();
		update_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site, [ 'dejavuserif' ] );
		$this->assertFalse( $this->required->is_font_in_use( 'sun-extb', true ) );

		update_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site, [ 'sun-exta' ] );
		$this->assertTrue( $this->required->is_font_in_use( 'sun-extb', true ) );
	}

	public function test_a_failed_form_read_records_nothing() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$site = self::factory()->blog->create();
		update_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site, [ 'stale' ] );
		update_blog_option( $site, 'gf_db_version', '3.0' );
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );

		$required = $this->unreadable_forms();

		$required->write_site_fonts( $site );

		/* The stale record goes, so the site's fonts stay locked until a sync an hour on reads it again */
		$this->assertFalse( get_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site ) );
		$this->assertContains( $site, $required->get_sites_without_fonts() );
		$next = wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT );
		$this->assertEqualsWithDelta( time() + HOUR_IN_SECONDS, $next, 5 );

		/* A sync already queued sooner is kept */
		$required->write_site_fonts( $site );
		$this->assertSame( $next, wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function test_a_failed_settings_read_records_nothing() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$site = self::factory()->blog->create();
		update_blog_option( $site, 'gfpdf_settings', [ 'default_font' => 'dejavuserif' ] );

		$log = new TestHandler();
		$this->with_failing_site_option_reads(
			function () use ( $site, $log ) {
				$this->required_with( $this->stub_font_sources(), $log )->write_site_fonts( $site );
			}
		);

		$this->assertFalse( get_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $site ) );
		$this->assertContains( [ 'ERROR', 'A site\'s settings could not be read for its fonts', [ 'site_id' => $site ] ], $this->records( $log ) );
	}

	public function test_the_delete_check_counts_a_font_in_use_when_forms_cant_be_read() {
		$required = $this->unreadable_forms();

		$this->assertTrue( $required->is_font_in_use( 'freeserif' ) );
		$this->assertFalse( $this->required_with( $this->stub_font_sources() )->is_font_in_use( 'freeserif' ) );
	}

	public function test_queued_sites_are_recorded_once_and_a_deleted_site_is_skipped() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$kept    = self::factory()->blog->create();
		$deleted = self::factory()->blog->create();

		foreach ( [ $kept, $deleted ] as $site ) {
			switch_to_blog( $site );
			$this->required->queue_site_fonts();
			$this->required->queue_site_fonts();
			restore_current_blog();
		}

		wp_delete_site( $deleted );
		$this->required->write_queued_site_fonts();

		$this->assertIsArray( get_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $kept ) );
		$this->assertFalse( get_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $deleted ) );
	}

	public function test_a_new_site_is_recorded_and_a_deleted_site_is_forgotten() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$site = self::factory()->blog->create();
		$key  = Model_Required_Fonts::SITE_FONTS_PREFIX . $site;

		$this->assertIsArray( get_network_option( get_main_network_id(), $key ) );

		wp_delete_site( $site );

		$this->assertFalse( get_network_option( get_main_network_id(), $key ) );
	}

	public function test_a_default_font_or_language_change_queues_the_sites_record() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$required   = $this->createMock( Model_Required_Fonts::class );
		$controller = new \GFPDF\Controller\Controller_Font_Downloader( $this->createMock( Model_Font_Downloader::class ), $required );

		$required->expects( $this->once() )->method( 'queue_site_fonts' );
		$required->expects( $this->never() )->method( 'clear_font_usage' );

		$controller->maybe_refresh_site_fonts( [ 'default_font' => 'a' ], [ 'default_font' => 'b' ] );
		$controller->maybe_refresh_site_fonts( [ 'default_font' => 'b' ], [ 'default_font' => 'b' ] );
		$controller->maybe_refresh_site_fonts( [ 'default_font' => 'b' ], 'corrupt' );
	}
}
