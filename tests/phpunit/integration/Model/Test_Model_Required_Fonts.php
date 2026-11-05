<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\StubsFontSources;
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
	use StubsFontSources;

	private const FONT = 'DejaVuSerifCondensed.ttf';

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
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_it_is_registered() {
		$this->assertInstanceOf( Model_Required_Fonts::class, GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ) );
	}

	public function test_get_font_files_lists_a_core_keys_files_on_the_manifest() {
		$this->assertSame( [ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ], $this->required->get_font_files( ' DejaVuSerif ' ) );
		$this->assertSame( [ 'Sun-ExtA.ttf' ], $this->required->get_font_files( 'sun-exta' ) );
		$this->assertSame( [], $this->required->get_font_files( 'arimo' ) );
		$this->assertSame( [], $this->required->get_font_files( 'notafont' ) );
	}

	public function test_get_font_files_skips_a_custom_font() {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'chewy', [ 'regular' => 'Chewy.ttf' ] );

		$this->assertSame( [], $this->required->get_font_files( 'chewy' ) );
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
				'freeserif'           => array_merge( $none, [ 'pdfs' => 2 ] ),
				'fixturemono'         => array_merge( $none, [ 'source' => true ] ),
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

	public function test_a_site_with_no_default_font_saved_uses_the_one_given() {
		GPDFAPI::get_options_class()->delete_option( 'default_font' );
		$required = $this->required_with( $this->stub_font_sources() );

		$this->assertSame( [], $required->get_site_font_keys( get_current_blog_id() ) );
		$this->assertSame( [ 'dejavusanscondensed' ], $required->get_site_font_keys( get_current_blog_id(), 'DejaVuSansCondensed' ) );

		GPDFAPI::get_options_class()->update_option( 'default_font', 'freeserif' );
		$this->assertSame( [ 'freeserif' ], $required->get_site_font_keys( get_current_blog_id(), 'dejavusanscondensed' ) );
	}

	public function test_corrupt_settings_leave_a_sites_default_font_unset() {
		update_option( 'gfpdf_settings', 'corrupt' );
		$required = $this->required_with( $this->stub_font_sources() );

		$this->assertSame( [ 'dejavusanscondensed' ], $required->get_site_font_keys( get_current_blog_id(), 'dejavusanscondensed' ) );
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
}
