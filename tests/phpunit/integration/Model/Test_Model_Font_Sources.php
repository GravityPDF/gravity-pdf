<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\Seamed_Font_Downloader;
use GFPDF\Tests\Concerns\UnregistersMvcClasses;
use GFPDF\Tests\Concerns\StubsFontSources;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Mpdf\Config\FontVariables;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group model
 * @group fonts
 */
class Test_Model_Font_Sources extends TestCase {

	use CopiesFontFixtures;
	use StubsFontSources;
	use UnregistersMvcClasses;

	/** The substitution list on a site with no language font */
	private const SUBSTITUTES = [ 'arimo', 'gfpdf-dejavu-symbols', 'dejavusanscondensed', 'freesans', 'garuda', 'khmeros', 'padaukbook', 'kaputaunicode', 'abyssinicasil', 'sun-exta' ];

	/**
	 * @var Model_Font_Sources
	 */
	protected $model;

	/**
	 * @var Model_Custom_Fonts
	 */
	protected $custom_fonts;

	/**
	 * @var string
	 */
	protected $font_dir;

	/**
	 * @var TestHandler
	 */
	protected $log;

	/**
	 * @var string A test manifest, unique per test so opcache never serves an earlier test's copy
	 */
	protected $manifest_path;

	public function set_up(): void {
		parent::set_up();

		$this->model        = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$this->custom_fonts = GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' );
		$this->font_dir     = GPDFAPI::get_data_class()->template_font_location;

		wp_mkdir_p( $this->font_dir );

		$this->manifest_path = get_temp_dir() . 'gfpdf-test-core-fonts-' . wp_generate_password( 8, false ) . '.php';

		$this->log = new TestHandler();
		GPDFAPI::get_log_class()->pushHandler( $this->log );
	}

	public function tear_down(): void {
		GPDFAPI::get_log_class()->popHandler();
		$this->remove_font_fixtures();
		wp_delete_file( $this->manifest_path );
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_the_bundled_and_core_sources_are_registered() {
		$sources = $this->model->get_sources();

		$this->assertSame( [ 'bundled', 'core' ], array_keys( $sources ) );
		$this->assertSame( $this->core_fontdata(), $sources['core']['families'] );

		$arimo = $sources['bundled']['families']['arimo'];
		foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
			$this->assertFileExists( $sources['bundled']['dir'] . $arimo[ $style ] );
		}

		$this->assertFileExists( $sources['bundled']['dir'] . $sources['bundled']['families']['gfpdf-dejavu-symbols']['R'] );

		$this->assertTrue( $this->model->is_family_key( 'arimo' ) );
		$this->assertTrue( $this->model->is_family_key( 'gfpdf-dejavu-symbols' ) );
		$this->assertTrue( $this->model->is_family_key( 'dejavusanscondensed' ) );
		$this->assertFalse( $this->model->is_family_key( 'chewy' ) );
	}

	public function test_the_bundled_legacy_hashes_are_sha1_to_size() {
		foreach ( $this->model->get_sources()['bundled']['legacy'] as $sha => $size ) {
			$this->assertMatchesRegularExpression( '/^[0-9a-f]{40}$/', $sha );
			$this->assertIsInt( $size );
		}
	}

	public function test_mpdf_font_data_registers_arimo_before_custom_fonts() {
		$this->assertSame( 5, has_filter( 'mpdf_font_data', [ $this->model, 'register_font_data' ] ) );

		$fonts = apply_filters( 'mpdf_font_data', $this->core_fontdata() );

		$this->assertSame(
			[
				'R'      => 'gfpdf-Arimo-Regular.ttf',
				'B'      => 'gfpdf-Arimo-Bold.ttf',
				'I'      => 'gfpdf-Arimo-Italic.ttf',
				'BI'     => 'gfpdf-Arimo-BoldItalic.ttf',
				'useOTL' => 0xFF,
			],
			$fonts['arimo']
		);

		$this->add_record( 'arimo', [ 'regular' => 'Chewy.ttf' ] );
		$fonts = apply_filters( 'mpdf_font_data', $this->core_fontdata() );

		$this->assertSame( 'Chewy.ttf', $fonts['arimo']['R'] );
		$this->assertArrayNotHasKey( 'B', $fonts['arimo'] );
	}

	public function test_mpdf_font_data_leaves_fonts_already_registered() {
		$fonts = $this->model->register_font_data( [ 'arimo' => [ 'R' => 'Other.ttf' ] ] );

		$this->assertSame( [ 'R' => 'Other.ttf' ], $fonts['arimo'] );
	}

	public function test_the_bundled_dir_is_appended_to_font_dir() {
		$this->assertNotFalse( has_filter( 'gfpdf_mpdf_class_config', [ $this->model, 'register_font_directories' ] ) );

		$bundled = PDF_PLUGIN_DIR . 'fonts/';
		$config  = $this->model->register_font_directories( [ 'fontDir' => [ $this->font_dir ] ] );

		$this->assertSame( [ $this->font_dir, $bundled ], $config['fontDir'] );
		$this->assertSame( [ $this->font_dir, $bundled ], $this->model->register_font_directories( $config )['fontDir'] );
		$this->assertSame( [ '/fonts/', $bundled ], $this->model->register_font_directories( [ 'fontDir' => '/fonts/' ] )['fontDir'] );
	}

	public function test_mpdf_renders_the_bundled_arimo() {
		$mpdf = new \GFPDF\Helper\Mpdf\Mpdf(
			apply_filters(
				'gfpdf_mpdf_class_config',
				[
					'fontDir'      => [ get_temp_dir() . 'gfpdf-no-fonts/' ],
					'fontdata'     => apply_filters( 'mpdf_font_data', $this->core_fontdata() ),
					'tempDir'      => get_temp_dir(),
					'default_font' => 'arimo',
				],
				[],
				[],
				[],
				null
			)
		);

		$mpdf->WriteHTML( '<p style="font-family: arimo"><b>Bold</b> <i>Italic</i> <b><i>Both</i></b> Regular</p>' );
		$pdf = $mpdf->Output( '', 'S' );

		foreach ( [ 'Arimo-Regular', 'Arimo-Bold', 'Arimo-Italic', 'Arimo-BoldItalic' ] as $face ) {
			$this->assertStringContainsString( '+' . $face, $pdf );
		}
	}

	public function test_arimo_is_listed_first_in_the_unicode_group() {
		$this->assertSame( 5, has_filter( 'gfpdf_font_list', [ $this->model, 'add_font_list_groups' ] ) );

		$fonts = GPDFAPI::get_pdf_fonts();

		$this->assertSame( 'arimo', array_key_first( $fonts['Unicode'] ) );
		$this->assertSame( 'Arimo', $fonts['Unicode']['arimo'] );
		$this->assertSame( 'Dejavu Sans Condensed', $fonts['Unicode']['dejavusanscondensed'] );

		/* Substitution only */
		foreach ( $fonts as $group ) {
			$this->assertArrayNotHasKey( 'gfpdf-dejavu-symbols', $group );
		}
	}

	public function test_a_custom_font_with_a_source_key_is_listed_once() {
		$this->add_record( 'arimo', [ 'regular' => 'Chewy.ttf' ] );

		$fonts = GPDFAPI::get_pdf_fonts();

		$this->assertArrayNotHasKey( 'arimo', $fonts['Unicode'] );
		$this->assertSame( 'arimo', $fonts['User-Defined Fonts']['arimo'] );
	}

	public function test_the_arimo_default_is_only_for_unsaved_settings() {
		$field = GPDFAPI::get_options_class()->get_registered_fields()['general_defaults']['default_font'];
		$this->assertSame( 'arimo', $field['std'] );

		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavusanscondensed' );
		$this->assertSame( 'dejavusanscondensed', GPDFAPI::get_options_class()->get_form_value( $field ) );
	}

	/**
	 * @dataProvider provider_default_font
	 *
	 * @param mixed[] $fonts
	 * @param mixed   $global_font The default font setting, as the gfpdf_get_option filter returns it
	 */
	public function test_get_default_font( array $fonts, $global_font, string $expected ) {
		add_filter(
			'gfpdf_get_option_default_font',
			static function () use ( $global_font ) {
				return $global_font;
			}
		);

		$fontdata = $this->model->register_font_data( $this->core_fontdata() );

		$this->assertSame( $expected, $this->model->get_default_font( $fonts, $fontdata ) );
	}

	public function provider_default_font(): array {
		return [
			'the PDF font'                           => [ [ 'dejavusans' ], 'dejavusanscondensed', 'dejavusans' ],
			'the PDF font in another case'           => [ [ 'DejaVuSans' ], 'dejavusanscondensed', 'dejavusans' ],
			'the first registered font given'        => [ [ 'notafont', 'freeserif' ], 'dejavusanscondensed', 'freeserif' ],
			'an mPDF sans-serif name'                => [ [ 'Arial' ], 'dejavusanscondensed', 'arial' ],
			'a generic family'                       => [ [ 'monospace' ], 'dejavusanscondensed', 'monospace' ],
			'an mPDF alias to a registered font'     => [ [ 'OCR-B' ], 'dejavusanscondensed', 'ocr-b' ],
			'an mPDF alias to a font of a kind'      => [ [ 'times' ], 'dejavusanscondensed', 'times' ],
			'an mPDF alias to an unregistered font'  => [ [ 'comic' ], 'dejavusanscondensed', 'dejavusanscondensed' ],
			'an mPDF alias as the default font'      => [ [], 'Courier', 'courier' ],
			'the default font with no PDF font'      => [ [], 'dejavusanscondensed', 'dejavusanscondensed' ],
			'the default font in another case'       => [ [], 'DejaVuSansCondensed', 'dejavusanscondensed' ],
			'the default font for an empty PDF font' => [ [ '' ], 'dejavusanscondensed', 'dejavusanscondensed' ],
			'the default font for an unknown font'   => [ [ 'notafont' ], 'dejavusanscondensed', 'dejavusanscondensed' ],
			'the default font for a non-string font' => [ [ [ 'dejavusans' ], null, 12 ], 'dejavusanscondensed', 'dejavusanscondensed' ],
			'Arimo for an unknown default font'      => [ [ 'notafont' ], 'deletedcustomfont', 'arimo' ],
			'Arimo for an empty default font'        => [ [ 'notafont' ], '', 'arimo' ],
			'Arimo for a non-string default font'    => [ [], [ 'dejavusans' ], 'arimo' ],
			'Arimo with nothing set'                 => [ [], false, 'arimo' ],
		];
	}

	/**
	 * @dataProvider provider_unusable_font_data
	 */
	public function test_get_default_font_skips_unusable_font_data( array $fontdata ) {
		add_filter(
			'gfpdf_get_option_default_font',
			static function () {
				return 'dejavusanscondensed';
			}
		);

		$this->assertSame( 'arimo', $this->model->get_default_font( [ 'dejavusans', 'arial' ], $fontdata ) );
	}

	public function provider_unusable_font_data(): array {
		return [
			'no font data'           => [ [] ],
			'no font of the kind'    => [ [ 'arial' => 'x', 'dejavusans' => [] ] ],
			'a string entry'         => [ [ 'dejavusans' => 'DejaVuSans.ttf', 'dejavusanscondensed' => 'x' ] ],
			'no regular file'        => [ [ 'dejavusans' => [ 'B' => 'DejaVuSans-Bold.ttf' ], 'dejavusanscondensed' => [] ] ],
			'a non-string regular'   => [ [ 'dejavusans' => [ 'R' => [ 'x' ] ], 'dejavusanscondensed' => [ 'R' => null ] ] ],
		];
	}

	public function test_mpdf_starts_in_the_pdfs_font() {
		$this->assertSame( 5, has_filter( 'gfpdf_mpdf_class_config', [ $this->model, 'set_default_font' ] ) );

		$fontdata = $this->model->register_font_data( $this->core_fontdata() );
		$config   = $this->model->set_default_font( [ 'fontdata' => $fontdata ], [], [], [ 'font' => 'dejavusans' ] );

		$this->assertSame( 'dejavusans', $config['default_font'] );

		$this->assertSame( $fontdata, $config['fontdata'] );
	}

	/**
	 * @dataProvider provider_junk_default_font_config
	 *
	 * @param mixed $config
	 * @param mixed $settings
	 */
	public function test_mpdf_starts_in_arimo_for_a_junk_config_or_settings( $config, $settings ) {
		$this->assertSame( 'arimo', $this->model->set_default_font( $config, [], [], $settings )['default_font'] );
	}

	public function provider_junk_default_font_config(): array {
		$fontdata = [ 'fontdata' => [ 'dejavusans' => [ 'R' => 'DejaVuSans.ttf' ] ] ];

		return [
			'a string config'      => [ 'config', [ 'font' => 'dejavusans' ] ],
			'no font data'         => [ [], [ 'font' => 'dejavusans' ] ],
			'string font data'     => [ [ 'fontdata' => 'fonts' ], [ 'font' => 'dejavusans' ] ],
			'string settings'      => [ $fontdata, 'settings' ],
			'a non-string font'    => [ $fontdata, [ 'font' => [ 'dejavusans' ] ] ],
			'an unregistered font' => [ $fontdata, [ 'font' => 'notafont' ] ],
		];
	}

	public function test_get_default_font_ignores_font_data_a_filter_broke() {
		add_filter( 'mpdf_font_data', '__return_false', 99 );

		$this->assertSame( 'arimo', $this->model->get_default_font( [ 'dejavusans' ] ) );
	}

	public function test_get_default_font_checks_the_registered_fonts_by_default() {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'chewy', [ 'regular' => 'Chewy.ttf' ] );

		$this->assertSame( 'chewy', $this->model->get_default_font( [ 'chewy' ] ) );
		$this->assertSame( 'arimo', $this->model->get_default_font( [ 'deletedcustomfont' ] ) );
	}

	public function test_the_core_manifest_matches_its_json() {
		$json     = json_decode( (string) file_get_contents( PDF_PLUGIN_DIR . 'fonts/core-fonts.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$expected = [];
		foreach ( $json as $entry ) {
			$expected[ $entry['name'] ] = [
				'url'  => $entry['download_url'],
				'size' => $entry['size'],
				'sha'  => $entry['sha'],
			];
		}

		$this->assertCount( 82, $expected );
		$this->assertSame( $expected, $this->model->get_manifest() );
		$this->assertFalse( $this->log->hasRecords( Logger::WARNING ) );
	}

	public function test_the_core_legacy_manifest_is_the_core_fonts_v1_0_0_release() {
		$manifest = $this->model->get_legacy_manifest( 'core' );

		$this->assertCount( 82, $manifest );
		$this->assertSame( 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/v1.0.0/DejaVuSans.ttf', $manifest['DejaVuSans.ttf']['url'] );
		$this->assertSame( '9d40c325694b3bfb8cdd4c0146ad44379d5c4ade', $manifest['DejaVuSans.ttf']['sha'] );
		$this->assertFalse( $this->log->hasRecords( Logger::WARNING ) );
	}

	public function test_the_manifest_merges_every_source() {
		$this->assertTrue( $this->model->is_core_font_file( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertTrue( $this->model->is_core_font_file( 'Aegean.otf' ) );
		$this->assertFalse( $this->model->is_core_font_file( 'DejaVuinfo.txt' ) );
		$this->assertFalse( $this->model->is_core_font_file( '../DejaVuSerifCondensed.ttf' ) );
		$this->assertFalse( $this->model->is_core_font_file( 'gfpdf-Arimo-Regular.ttf' ) );
		$this->assertSame( $this->font_dir . 'Garuda.ttf', $this->model->get_plain_path( 'Garuda.ttf' ) );
		$this->assertSame( '', $this->model->get_own_copy( 'Garuda.ttf' ) );
		$this->assertSame( PDF_PLUGIN_DIR . 'fonts/gfpdf-Arimo-Regular.ttf', $this->model->get_fallback_file() );
	}

	/**
	 * @dataProvider provider_unreadable_manifest
	 */
	public function test_a_missing_or_invalid_manifest_is_logged_once( ?string $contents ) {
		$model = $this->get_model_with_manifest( $contents );

		$this->assertSame( [], $model->get_manifest() );
		$this->assertSame( [], $model->get_manifest() );
		$this->assertCount( 1, $this->log->getRecords() );
		$this->assertTrue( $this->log->hasRecordThatContains( 'Font manifest is missing or invalid', Logger::ERROR ) );
	}

	public function provider_unreadable_manifest(): array {
		return [
			'missing'          => [ null ],
			'empty'            => [ '' ],
			'a parse error'    => [ '<?php return [' ],
			'returns a string' => [ '<?php return "core-fonts";' ],
		];
	}

	public function test_invalid_manifest_entries_are_skipped() {
		$valid = [
			'url'  => 'https://example.com/Font.ttf',
			'size' => 100,
			'sha'  => str_repeat( 'a', 40 ),
		];

		$model = $this->get_model_with_manifest(
			[
				'Font.ttf'    => $valid,
				'Junk.ttf'    => 'not an entry',
				'../Font.ttf' => $valid,
				''            => $valid,
				5             => $valid,
				'Http.ttf'    => array_merge( $valid, [ 'url' => 'http://example.com/Font.ttf' ] ),
				'NoUrl.ttf'   => array_merge( $valid, [ 'url' => null ] ),
				'Zero.ttf'    => array_merge( $valid, [ 'size' => 0 ] ),
				'Big.ttf'     => array_merge( $valid, [ 'size' => 'big' ] ),
				'Short.ttf'   => array_merge( $valid, [ 'sha' => 'abc' ] ),
				'Upper.ttf'   => array_merge( $valid, [ 'sha' => str_repeat( 'A', 40 ) ] ),
				'NoSha.ttf'   => array_diff_key( $valid, [ 'sha' => true ] ),
			]
		);

		$this->assertSame( [ 'Font.ttf' => $valid ], $model->get_manifest() );
		$this->assertTrue(
			$this->log->hasRecordThatPasses(
				static function ( $record ) {
					return $record['message'] === 'Font manifest entries skipped as invalid' && $record['context']['skipped'] === 11;
				},
				Logger::WARNING
			)
		);
	}

	public function test_adopt_records_removes_a_record_matching_the_manifest() {
		$this->copy_fixture( 'DejaVuSansCondensed.ttf', 'DejaVuSansCondensed85274.ttf' );
		$this->copy_fixture( 'DejaVuSans-Bold.ttf' );
		$this->add_record(
			'dejavusanscondensed',
			[
				'regular' => 'DejaVuSansCondensed85274.ttf',
				'bold'    => 'DejaVuSans-Bold.ttf',
			]
		);

		$this->assertSame( [ 'dejavusanscondensed' ], $this->model->adopt_records( 'core' ) );
		$this->assertSame( [], $this->custom_fonts->get_custom_fonts() );
		$this->assertFileDoesNotExist( $this->font_dir . 'DejaVuSansCondensed85274.ttf' );
		$this->assertFileDoesNotExist( $this->font_dir . 'DejaVuSans-Bold.ttf' );
	}

	public function test_adopt_records_removes_a_record_of_the_core_font_installers_files() {
		$this->copy_fixture( 'DejaVuSerifCondensed-v1.0.0.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->add_record( 'dejavuserifcondensed', [ 'regular' => 'DejaVuSerifCondensed.ttf' ] );

		$this->assertSame( [ 'dejavuserifcondensed' ], $this->model->adopt_records( 'core' ) );
	}

	/**
	 * @dataProvider data_bad_manifests
	 *
	 * @param array|string $manifest
	 */
	public function test_adopt_records_with_a_bad_manifest( $manifest, array $adopted, bool $logs ) {
		$model = $this->get_model_with_manifest( $manifest );

		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSansCondensed.ttf' );
		$this->add_record( 'dejavusanscondensed', [ 'regular' => 'DejaVuSansCondensed.ttf' ] );

		$this->assertSame( $adopted, $model->adopt_records( 'core' ) );
		$this->assertSame( $logs, $this->log->hasRecordThatContains( 'Font manifest is missing or invalid', Logger::ERROR ) );
	}

	public function data_bad_manifests(): array {
		$blob  = $this->fixture_blob( 'Chewy.ttf' );
		$valid = [
			'url'  => 'https://example.com/DejaVuSansCondensed.ttf',
			'size' => current( $blob ),
			'sha'  => (string) key( $blob ),
		];

		$junk = [
			'Chewy.ttf'  => 'Chewy.ttf',
			'Size.ttf'   => array_diff_key( $valid, [ 'sha' => true ] ),
			'Sha.ttf'    => array_diff_key( $valid, [ 'size' => true ] ),
			'Array.ttf'  => array_merge( $valid, [ 'sha' => [ $valid['sha'] ] ] ),
			'String.ttf' => array_merge( $valid, [ 'size' => (string) $valid['size'] ] ),
			'Upper.ttf'  => array_merge( $valid, [ 'sha' => strtoupper( $valid['sha'] ) ] ),
		];

		return [
			'an invalid manifest'     => [ '<?php return "core-fonts";', [], true ],
			'junk beside a valid one' => [ $junk + [ 'DejaVuSansCondensed.ttf' => $valid ], [ 'dejavusanscondensed' ], false ],
		];
	}

	public function test_adopt_records_removes_a_record_matching_legacy_hashes() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular85274.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular85274.ttf' ] );

		$this->assertSame( [ 'arimo' ], $model->adopt_records( 'bundled' ) );
		$this->assertSame( [], $this->custom_fonts->get_custom_fonts() );
		$this->assertFileDoesNotExist( $this->font_dir . 'Arimo-Regular85274.ttf' );
	}

	public function test_adopt_records_keeps_an_edited_record() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );

		$this->copy_fixture( 'Chewy.ttf' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->add_record(
			'arimo',
			[
				'regular' => 'Chewy.ttf',
				'bold'    => 'DejaVuSerifCondensed.ttf',
			]
		);

		$this->assertSame( [], $model->adopt_records( 'bundled' ) );
		$this->assertArrayHasKey( 'arimo', $this->custom_fonts->get_custom_fonts() );
		$this->assertFileExists( $this->font_dir . 'Chewy.ttf' );
	}

	/**
	 * @dataProvider provider_languages
	 */
	public function test_language_fonts_follow_the_installed_languages( array $languages, array $keys ) {
		$this->set_languages( $languages );

		$this->assertSame( $keys, $this->stub_font_sources()->get_language_font_keys() );
	}

	public function provider_languages(): array {
		return [
			'Japanese'             => [ [ 'ja' ], [ 'sun-exta' ] ],
			'Thai'                 => [ [ 'th' ], [ 'garuda' ] ],
			'English'              => [ [ 'en_US' ], [] ],
			'Latin'                => [ [ 'de_DE', 'fr_FR', 'pl_PL' ], [] ],
			'Cyrillic and Greek'   => [ [ 'ru_RU', 'el' ], [ 'dejavusanscondensed' ] ],
			'Chinese and Japanese' => [ [ 'zh_CN', 'ja' ], [ 'sun-exta' ] ],
		];
	}

	public function test_the_locale_counts_as_a_language() {
		$this->set_locale( 'ko_KR' );

		$this->assertSame( [ 'unbatang' ], $this->stub_font_sources()->get_language_font_keys() );
	}

	public function test_the_installed_languages_font_keys_are_cached_for_every_site() {
		$this->set_languages( [ 'th' ] );
		$this->stub_font_sources()->get_language_font_keys();

		$reads = 0;
		add_filter(
			'get_available_languages',
			static function () use ( &$reads ) {
				$reads++;

				return [ 'th' ];
			}
		);

		$this->assertSame( [ 'garuda' ], $this->stub_font_sources()->get_language_font_keys() );
		$this->assertSame( 0, $reads );

		$this->assertSame( [ 'garuda' ], get_site_transient( Model_Font_Sources::LANGUAGE_FONT_KEYS ) );

		/* The locale is per site, so it's mapped on top of the cache */
		add_filter(
			'locale',
			static function () {
				return 'ja';
			}
		);

		$this->assertSame( [ 'garuda', 'sun-exta' ], $this->stub_font_sources()->get_language_font_keys() );
		$this->assertSame( 0, $reads );
		$this->assertSame( [ 'garuda' ], get_site_transient( Model_Font_Sources::LANGUAGE_FONT_KEYS ) );
	}

	/**
	 * @dataProvider provider_corrupt_language_caches
	 *
	 * @param mixed $cached
	 */
	public function test_a_corrupt_language_font_cache_is_rebuilt( $cached ) {
		$this->set_languages( [ 'th' ] );
		set_site_transient( Model_Font_Sources::LANGUAGE_FONT_KEYS, $cached, DAY_IN_SECONDS );

		$this->assertSame( [ 'garuda' ], $this->stub_font_sources()->get_language_font_keys() );
		$this->assertSame( [ 'garuda' ], get_site_transient( Model_Font_Sources::LANGUAGE_FONT_KEYS ) );
	}

	public function provider_corrupt_language_caches(): array {
		return [
			'text'   => [ 'garuda' ],
			'number' => [ 5 ],
			'true'   => [ true ],
		];
	}

	public function test_only_font_keys_are_read_from_the_language_font_cache() {
		$this->set_languages( [] );
		set_site_transient( Model_Font_Sources::LANGUAGE_FONT_KEYS, [ 'garuda', 5, null, [ 'sun-exta' ] ], DAY_IN_SECONDS );

		$this->assertSame( [ 'garuda' ], $this->stub_font_sources()->get_language_font_keys() );
	}

	public function test_a_locale_whose_font_is_already_cached_does_not_repeat_it() {
		$this->set_locale( 'th' );
		set_site_transient( Model_Font_Sources::LANGUAGE_FONT_KEYS, [ 'garuda' ], DAY_IN_SECONDS );

		$this->assertSame( [ 'garuda' ], $this->stub_font_sources()->get_language_font_keys() );
	}

	public function test_a_locale_without_its_language_pack_still_counts_but_is_not_cached() {
		$this->set_languages( [] );
		$this->set_locale( 'th' );

		$this->assertSame( [ 'garuda' ], $this->stub_font_sources()->get_language_font_keys() );
		$this->assertSame( [], get_site_transient( Model_Font_Sources::LANGUAGE_FONT_KEYS ) );
	}

	public function test_a_language_pack_install_flushes_the_language_fonts() {
		$this->assertSame( 10, has_action( 'upgrader_process_complete', [ GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), 'flush' ] ) );
	}

	public function test_language_font_keys_are_read_once_until_flushed() {
		delete_site_transient( Model_Font_Sources::LANGUAGE_FONT_KEYS );
		$reads = 0;
		add_filter(
			'get_available_languages',
			static function () use ( &$reads ) {
				$reads++;

				return [ 'th' ];
			}
		);

		$model = $this->stub_font_sources();
		$model->get_language_font_keys();
		$model->get_language_font_keys();
		$this->assertSame( 1, $reads );

		$model->flush();
		$model->get_language_font_keys();
		$this->assertSame( 2, $reads );
	}

	/**
	 * @dataProvider provider_odd_languages
	 *
	 * @param mixed $languages
	 * @param mixed $locale
	 */
	public function test_odd_languages_and_locales_are_skipped( $languages, $locale, array $expected ) {
		$this->set_languages( $languages );
		$this->set_locale( $locale );

		$this->assertSame( $expected, $this->stub_font_sources()->get_language_font_keys() );
	}

	public function provider_odd_languages(): array {
		return [
			'languages not an array'    => [ null, 'th', [ 'garuda' ] ],
			'non-string languages'      => [ [ [ 'th' ], 7, null ], 'en_US', [] ],
			'a non-string locale'       => [ [ 'th' ], [ 'ja' ], [ 'garuda' ] ],
			'empty and unknown locales' => [ [ '', 'xx_YY', '-', 'th_TH@buddhist' ], '', [ 'garuda' ] ],
		];
	}

	/**
	 * @dataProvider provider_odd_language_options
	 *
	 * @param mixed $options
	 */
	public function test_unexpected_language_options_map_to_no_font( $options ) {
		$this->set_languages( [ 'th' ] );

		$this->assertSame( [], $this->stub_font_sources( null, $this->stub_language_mapper( $options ) )->get_language_font_keys() );
	}

	public function provider_odd_language_options(): array {
		return [
			'null'            => [ null ],
			'a string'        => [ 'garuda' ],
			'too short'       => [ [ false ] ],
			'non-string font' => [ [ false, [ 'garuda' ] ] ],
		];
	}

	public function test_a_mapped_font_is_a_language_font() {
		$this->set_languages( [ 'th' ] );

		$this->assertSame( [ 'garuda' ], $this->stub_font_sources( null, $this->stub_language_mapper( [ false, 'garuda' ] ) )->get_language_font_keys() );
	}

	/**
	 * @dataProvider provider_substitution_fonts
	 */
	public function test_the_substitution_fonts_add_the_language_fonts_then_sun_exta( array $languages, array $expected ) {
		$this->set_languages( $languages );

		$this->assertSame( $expected, $this->stub_font_sources()->get_substitution_fonts() );
	}

	public function provider_substitution_fonts(): array {
		return [
			'English'                           => [ [], self::SUBSTITUTES ],
			'Korean'                            => [ [ 'ko_KR' ], $this->substitutes_with( 'unbatang' ) ],
			'Thai, its font already listed'     => [ [ 'th' ], self::SUBSTITUTES ],
			'Japanese, its font already listed' => [ [ 'ja' ], self::SUBSTITUTES ],
			'Chinese, its font already listed'  => [ [ 'zh_TW' ], self::SUBSTITUTES ],
			'Japanese and Korean'               => [ [ 'ja', 'ko_KR' ], array_merge( array_slice( self::SUBSTITUTES, 0, -1 ), [ 'sun-exta', 'unbatang' ] ) ],
		];
	}

	public function test_the_substitution_fonts_skip_unregistered_fonts() {
		$fontdata = $this->model->get_font_data();
		unset( $fontdata['dejavusanscondensed'] );

		$this->assertSame( $this->substitutes_without( 'dejavusanscondensed' ), $this->stub_font_sources()->get_substitution_fonts( $fontdata ) );
	}

	public function test_the_substitution_fonts_leave_out_the_pdfs_font() {
		$model = $this->stub_font_sources();

		$this->assertSame( $this->substitutes_without( 'arimo' ), $model->get_substitution_fonts( null, 'arimo' ) );
		$this->assertSame( $this->substitutes_without( 'dejavusanscondensed' ), $model->get_substitution_fonts( null, 'dejavusanscondensed' ) );
		$this->assertSame( $this->substitutes_without( 'garuda' ), $model->get_substitution_fonts( null, 'garuda' ) );
		$this->assertSame( self::SUBSTITUTES, $model->get_substitution_fonts( null, 'freeserif' ) );
	}

	/**
	 * @dataProvider provider_large_files
	 */
	public function test_a_large_file_is_one_over_five_mb_on_the_manifest( string $file, bool $large ) {
		$this->assertSame( $large, $this->model->is_large_file( $file ) );
	}

	public function provider_large_files(): array {
		return [
			'Sun-ExtA, 23 MB'           => [ 'Sun-ExtA.ttf', true ],
			'Sun-ExtB, 17.6 MB'         => [ 'Sun-ExtB.ttf', true ],
			'Aegyptus, 5.8 MB'          => [ 'Aegyptus.otf', true ],
			'UnBatang, just under 5 MB' => [ 'UnBatang_0613.ttf', false ],
			'Garuda'                    => [ 'Garuda.ttf', false ],
			'not on the manifest'       => [ 'NotAFont.ttf', false ],
		];
	}

	public function test_the_large_file_threshold_reads_the_manifest_size() {
		$manifest                       = $this->model->get_manifest();
		$manifest['Garuda.ttf']['size'] = Model_Font_Sources::LARGE_FILE_SIZE + 1;
		$this->set_shared_manifest( $manifest );

		$this->assertTrue( $this->model->is_large_file( 'Garuda.ttf' ) );
	}

	/**
	 * The substitution list with a language font, which comes before Sun-ExtA
	 */
	private function substitutes_with( string $font ): array {
		return array_merge( array_slice( self::SUBSTITUTES, 0, -1 ), [ $font, 'sun-exta' ] );
	}

	private function substitutes_without( string $font ): array {
		return array_values( array_diff( self::SUBSTITUTES, [ $font ] ) );
	}

	public function test_the_font_data_is_filtered_once_until_flushed() {
		$runs   = 0;
		$filter = static function ( $fonts ) use ( &$runs ) {
			$runs++;

			return $fonts;
		};
		add_filter( 'mpdf_font_data', $filter );

		$model = $this->stub_font_sources();
		$this->assertArrayHasKey( 'arimo', $model->get_font_data() );
		$model->get_font_data();
		$this->assertSame( 1, $runs );

		$model->flush();
		$model->get_font_data();
		$this->assertSame( 2, $runs );
	}

	/**
	 * @dataProvider provider_junk
	 *
	 * @param mixed $junk
	 */
	public function test_junk_font_data_is_no_fonts( $junk ) {
		add_filter(
			'mpdf_font_data',
			static function () use ( $junk ) {
				return $junk;
			},
			PHP_INT_MAX
		);

		$this->assertSame( [], $this->stub_font_sources()->get_font_data() );
	}

	public function provider_junk(): array {
		return [
			'null'     => [ null ],
			'a string' => [ 'arimo' ],
		];
	}

	public function test_adopt_records_keeps_a_record_whose_file_differs_at_the_same_size() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$path  = $this->font_dir . 'Arimo-Regular.ttf';
		$bytes       = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$bytes[-1]   = $bytes[-1] === 'x' ? 'y' : 'x';
		file_put_contents( $path, $bytes ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->assertSame( [], $model->adopt_records( 'bundled' ) );
		$this->assertArrayHasKey( 'arimo', $this->custom_fonts->get_custom_fonts() );
	}

	public function test_adopt_records_keeps_a_record_with_a_missing_file() {
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-missing.ttf' ] );

		$this->assertSame( [], $this->model->adopt_records( 'bundled' ) );
		$this->assertArrayHasKey( 'arimo', $this->custom_fonts->get_custom_fonts() );
	}

	public function test_adopt_records_keeps_a_record_with_no_files() {
		$this->add_record( 'arimo', [] );

		$this->assertSame( [], $this->model->adopt_records( 'bundled' ) );
	}

	public function test_adopt_records_ignores_records_with_other_keys() {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'mychewy', [ 'regular' => 'Chewy.ttf' ] );

		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->assertSame( [], $model->adopt_records( 'bundled' ) );
		$this->assertSame( [], $model->adopt_records( 'unknown' ) );
		$this->assertArrayHasKey( 'mychewy', $this->custom_fonts->get_custom_fonts() );
	}

	public function test_a_source_with_no_known_copies_adopts_nothing() {
		$model         = $this->get_model_with_legacy( 'Chewy.ttf' );
		$model->legacy = [];

		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$this->assertSame( [], $model->adopt_records( 'bundled' ) );
		$this->assertArrayHasKey( 'arimo', $this->custom_fonts->get_custom_fonts() );
	}

	/**
	 * @dataProvider data_junk_filter_values
	 *
	 * @param mixed $value
	 */
	public function test_mpdf_font_data_accepts_junk( $value ) {
		$this->assertArrayHasKey( 'arimo', $this->model->register_font_data( $value ) );
	}

	/**
	 * @dataProvider data_junk_filter_values
	 *
	 * @param mixed $value
	 */
	public function test_the_bundled_dir_is_added_to_a_junk_config( $value ) {
		$this->assertSame( [ PDF_PLUGIN_DIR . 'fonts/' ], $this->model->register_font_directories( $value )['fontDir'] );
		$this->assertSame( [ PDF_PLUGIN_DIR . 'fonts/' ], $this->model->register_font_directories( [ 'fontDir' => $value ] )['fontDir'] );
	}

	/**
	 * @dataProvider data_junk_filter_values
	 *
	 * @param mixed $value
	 */
	public function test_the_font_list_accepts_junk( $value ) {
		$this->assertSame( [ 'Unicode' => [ 'arimo' => 'Arimo' ] ], $this->model->add_font_list_groups( $value ) );

		$fonts = $this->model->add_font_list_groups(
			[
				'Unicode' => $value,
				'Other'   => [ 'garuda' => 'Garuda' ],
			]
		);
		$this->assertSame( [ 'arimo' => 'Arimo' ], $fonts['Unicode'] );
		$this->assertSame( [ 'garuda' => 'Garuda' ], $fonts['Other'] );
	}

	public function data_junk_filter_values(): array {
		return [
			'null'   => [ null ],
			'false'  => [ false ],
			'string' => [ '' ],
			'int'    => [ 0 ],
		];
	}

	/**
	 * @dataProvider data_non_string_paths
	 *
	 * @param mixed $path
	 */
	public function test_adopt_records_keeps_a_record_with_a_non_string_path( $path ) {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );

		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				[
					'id'        => 'arimo',
					'font_name' => 'arimo',
					'regular'   => $this->font_dir . 'Arimo-Regular.ttf',
					'bold'      => $path,
				],
			]
		);
		$this->recorded_fonts[] = 'arimo';

		$this->assertSame( [], $model->adopt_records( 'bundled' ) );
		$this->assertArrayHasKey( 'arimo', $this->custom_fonts->get_custom_fonts() );
	}

	public function data_non_string_paths(): array {
		return [
			'array' => [ [ 'Arimo-Regular.ttf' ] ],
			'int'   => [ 5 ],
			'true'  => [ true ],
		];
	}

	public function test_adopt_records_keeps_a_record_with_an_unreadable_file() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$path = $this->font_dir . 'Arimo-Regular.ttf';
		chmod( $path, 0000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod

		try {
			if ( is_readable( $path ) ) {
				$this->markTestSkipped( 'Running as root, so every file is readable' );
			}

			$this->assertSame( [], $model->adopt_records( 'bundled' ) );
			$this->assertArrayHasKey( 'arimo', $this->custom_fonts->get_custom_fonts() );
		} finally {
			chmod( $path, 0644 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		}
	}

	public function test_adopt_records_logs_a_record_it_could_not_remove() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$keep = function ( $value, $old_value ) {
			return $old_value;
		};
		add_filter( 'pre_update_option_gfpdf_settings', $keep, 10, 2 );
		$adopted = $model->adopt_records( 'bundled' );
		remove_filter( 'pre_update_option_gfpdf_settings', $keep );

		$this->assert_record_kept( $adopted );
	}

	public function test_adopt_records_keeps_the_record_when_the_custom_fonts_controller_is_unhooked() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$adopted = $this->without_mvc_class(
			'Controller_Custom_Fonts',
			function () use ( $model ) {
				return $model->adopt_records( 'bundled' );
			}
		);

		$this->assert_record_kept( $adopted );
		$this->assertFileExists( $this->font_dir . 'Arimo-Regular.ttf' );
	}

	/**
	 * @group ms-required
	 */
	public function test_adopt_records_keeps_a_file_another_site_uses() {
		$this->skipWithoutMultisite();

		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$other_site = self::factory()->blog->create();
		update_blog_option( $other_site, 'gfpdf_settings', [ 'custom_fonts' => $this->custom_fonts->get_custom_fonts() ] );

		$this->assertSame( [ 'arimo' ], $model->adopt_records( 'bundled' ) );
		$this->assertSame( [], $this->custom_fonts->get_custom_fonts() );
		$this->assertFileExists( $this->font_dir . 'Arimo-Regular.ttf' );
	}

	public function test_adopt_records_moves_on_after_an_error_removing_a_record() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Symbols.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );
		$this->add_record( 'gfpdf-dejavu-symbols', [ 'regular' => 'Symbols.ttf' ] );

		$thrown = false;
		$throw_once = function ( $value ) use ( &$thrown ) {
			if ( $thrown ) {
				return $value;
			}

			$thrown = true;
			throw new \TypeError( 'Broken' );
		};
		add_filter( 'pre_update_option_gfpdf_settings', $throw_once );

		try {
			$adopted = $model->adopt_records( 'bundled' );
		} finally {
			remove_filter( 'pre_update_option_gfpdf_settings', $throw_once );
		}

		$this->assertSame( [ 'gfpdf-dejavu-symbols' ], $adopted );
		$this->assert_record_kept( [] );
	}

	/**
	 * Assert adoption removed nothing because removing the record failed and was logged as an error
	 */
	protected function assert_record_kept( array $adopted ): void {
		$this->assertSame( [], $adopted );
		$this->assertArrayHasKey( 'arimo', $this->custom_fonts->get_custom_fonts() );
		$this->assertTrue( $this->log->hasRecordThatContains( 'Could not remove a custom font record replaced by a font source', Logger::ERROR ) );
	}

	public function test_two_sources_using_one_name_for_different_files_give_the_later_one_the_hashed_name() {
		$first  = [ 'Shared.ttf' => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Shared.ttf' ) ];
		$second = [
			'Shared.ttf' => $this->manifest_entry( 'DejaVuSans.ttf', 'https://example.com/DejaVuSans.ttf' ),
			'Only.ttf'   => $this->manifest_entry( 'DejaVuSans.ttf', 'https://example.com/Only.ttf' ),
		];
		$model  = $this->get_model_with_two_sources( $first, $second );
		$hashed = 'Shared-' . substr( $second['Shared.ttf']['sha'], 0, 8 ) . '.ttf';

		$sources = $model->get_sources();

		$this->assertSame( [ 'Shared.ttf' ], array_keys( $model->get_source_manifest( 'bundled' ) ) );
		$this->assertSame( [ $hashed, 'Only.ttf' ], array_keys( $model->get_source_manifest( 'core' ) ) );
		$this->assertSame( [ 'Shared.ttf', $hashed, 'Only.ttf' ], array_keys( $model->get_manifest() ) );
		$this->assertSame( $first['Shared.ttf'], $model->get_manifest()['Shared.ttf'] );
		$this->assertSame( $second['Shared.ttf'], $model->get_manifest()[ $hashed ] );

		/* Only the later source's family files are rewritten, and only the ones renamed */
		$this->assertSame( 'Shared.ttf', $sources['bundled']['families']['first']['R'] );
		$this->assertSame( $hashed, $sources['core']['families']['second']['R'] );
		$this->assertSame( 'Only.ttf', $sources['core']['families']['second']['B'] );
		$this->assertSame( $hashed, $model->register_font_data( [] )['second']['R'] );

		$this->assertTrue( $this->log->hasRecordThatContains( 'Font file name used by two sources for different files', Logger::NOTICE ) );
	}

	public function test_a_family_uses_the_hashed_name_of_a_file_a_font_manager_record_uses() {
		$this->put_longer_font_at( 'DejaVuSans.ttf', 'DejaVuSans.ttf' );
		$this->add_record( 'mine', [ 'regular' => 'DejaVuSans.ttf' ] );
		$hashed = $this->model->get_hashed_name( 'DejaVuSans.ttf' );

		$fonts = $this->model->register_font_data( [] );

		$this->assertSame( $hashed, $fonts['dejavusans']['R'] );
		$this->assertSame( 'DejaVuSans-Bold.ttf', $fonts['dejavusans']['B'], 'Only the shared file is renamed' );
		$this->assertSame( [ 'DejaVuSans.ttf' => $hashed ], $this->model->get_record_collisions() );
		$this->assertTrue( $this->model->is_record_file( 'DejaVuSans.ttf' ) );
		$this->assertArrayNotHasKey( $hashed, $this->model->get_manifest(), 'The hashed name is no manifest file of its own' );
		$this->assertSame( 'DejaVuSans.ttf', $this->model->get_manifest_file( $hashed ) );
		$this->assertSame( '', $this->model->get_own_copy( 'DejaVuSans.ttf' ), 'The record\'s file is not the family\'s copy' );
	}

	public function test_every_record_file_at_a_family_name_is_a_collision() {
		$this->put_longer_font_at( 'DejaVuSans.ttf', 'DejaVuSans.ttf' );
		$this->put_longer_font_at( 'DejaVuSans-Bold.ttf', 'DejaVuSans-Bold.ttf' );
		$this->add_record(
			'mine',
			[
				'regular' => 'DejaVuSans.ttf',
				'bold'    => 'DejaVuSans-Bold.ttf',
			]
		);

		$this->assertSame( [ 'DejaVuSans.ttf', 'DejaVuSans-Bold.ttf' ], array_keys( $this->model->get_record_collisions() ) );
	}

	public function test_the_own_copy_is_the_hashed_copy_else_the_file_at_the_name_unless_it_is_another_fonts() {
		$file   = 'DejaVuSans.ttf';
		$hashed = $this->model->get_hashed_name( $file );

		$this->assertSame( '', $this->model->get_own_copy( $file ), 'Nothing on disk' );

		$this->copy_fixture( $file );
		$this->assertSame( $this->font_path( $file ), $this->model->get_own_copy( $file ) );

		$this->put_other_font_at( $file );
		$this->assertSame( '', $this->model->get_own_copy( $file ), 'Another font\'s file at the name' );
		$this->assertSame( '', $this->model->get_own_copy( $file ) );

		$this->copy_fixture( $file, $hashed );
		$this->assertSame( $this->font_path( $hashed ), $this->model->get_own_copy( $file ) );
		$this->assertNotSame( '', $this->model->get_own_copy( $file ) );
	}

	public function test_a_hashed_name_stands_for_its_manifest_file_only_for_the_current_release() {
		$file   = 'DejaVuSans.ttf';
		$hashed = $this->model->get_hashed_name( $file );

		$this->assertSame( $file, $this->model->get_manifest_file( $file ) );
		$this->assertSame( $file, $this->model->get_manifest_file( $hashed ) );
		$this->assertSame( '', $this->model->get_manifest_file( 'DejaVuSans-00000000.ttf' ), 'An older release\'s copy' );
		$this->assertSame( '', $this->model->get_manifest_file( 'Chewy.ttf' ) );
	}

	public function test_a_record_holding_a_known_build_of_the_core_font_is_not_custom() {
		$this->copy_fixture( 'DejaVuSans.ttf' );
		$this->add_record( 'mine', [ 'regular' => 'DejaVuSans.ttf' ] );

		$this->assertSame( [], $this->model->get_record_collisions() );
		$this->assertSame( 'DejaVuSans.ttf', $this->model->register_font_data( [] )['dejavusans']['R'] );
	}

	public function test_a_record_on_another_site_is_only_a_collision_on_that_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->put_longer_font_at( 'DejaVuSans.ttf', 'DejaVuSans.ttf' );
		$blog = self::factory()->blog->create();

		switch_to_blog( $blog );
		try {
			$this->add_record( 'mine', [ 'regular' => 'DejaVuSans.ttf' ] );
			$this->assertSame( [ 'DejaVuSans.ttf' ], array_keys( $this->model->get_record_collisions() ) );
			GPDFAPI::delete_plugin_option( 'custom_fonts' );
		} finally {
			restore_current_blog();
		}

		$this->model->flush();
		$this->assertSame( [], $this->model->get_record_collisions() );
	}

	public function test_a_record_whose_file_is_not_a_core_name_reads_no_manifest() {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'chewy', [ 'regular' => 'Chewy.ttf' ] );
		$this->set_private( $this->model, 'manifests', [] );

		$this->assertSame( [], $this->model->get_record_collisions() );
		$this->assertSame( [], $this->get_private( $this->model, 'manifests' ) );
	}

	public function test_a_renamed_file_is_not_renamed_again() {
		$model = $this->get_model_with_two_sources(
			[ 'Shared.ttf' => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Shared.ttf' ) ],
			[ 'Shared.ttf' => $this->manifest_entry( 'DejaVuSans.ttf', 'https://example.com/DejaVuSans.ttf' ) ]
		);

		$renamed = array_keys( $model->get_source_manifest( 'core' ) )[0];

		$this->assertSame( '', $model->get_hashed_name( $renamed ) );

		/* With no other name to take, the file at the name is the one used */
		$this->copy_fixture( 'DejaVuSans.ttf', $renamed );
		$this->assertNotSame( '', $model->get_own_copy( $renamed ) );
	}

	public function test_two_sources_using_one_name_for_the_same_file_share_it() {
		$entry = $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Shared.ttf' );
		$model = $this->get_model_with_two_sources( [ 'Shared.ttf' => $entry ], [ 'Shared.ttf' => $entry ] );

		$sources = $model->get_sources();

		$this->assertSame( [ 'Shared.ttf' ], array_keys( $model->get_manifest() ) );
		$this->assertSame( 'Shared.ttf', $sources['core']['families']['second']['R'] );
		$this->assertFalse( $this->log->hasRecordThatContains( 'Font file name used by two sources for different files', Logger::NOTICE ) );
	}

	public function test_a_single_downloadable_source_is_not_read_when_the_sources_are_built() {
		$model = new Model_Font_Sources( GPDFAPI::get_data_class(), $this->custom_fonts, GPDFAPI::get_log_class() );
		$model->get_sources();

		$this->assertSame( [], $this->get_private( $model, 'manifests' ) );
		$this->assertNull( $this->get_private( $model, 'manifest' ) );
	}

	public function test_the_renamed_source_downloads_to_its_hashed_name() {
		$model = $this->get_model_with_two_sources( [ 'DejaVuSerifCondensed.ttf' => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Chewy.ttf' ) ], [] );
		$core  = $model->get_source_manifest( 'core' );

		/* The later source is the real core manifest, so its file takes the hashed name */
		$name = 'DejaVuSerifCondensed-' . substr( $this->manifest_entry( 'DejaVuSerifCondensed.ttf', '' )['sha'], 0, 8 ) . '.ttf';
		$this->assertArrayHasKey( $name, $core );
		$this->assertSame( $name, $model->get_sources()['core']['families']['dejavuserifcondensed']['R'] );

		$this->serve_font_downloads();
		$this->copied_fonts[] = $this->font_dir . $name;
		$downloader = new Seamed_Font_Downloader( GPDFAPI::get_log_class(), [ 'sources' => $model ] );

		$this->assertTrue( $downloader->download( $name ) );
		$this->assertFileEquals( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ), $this->font_dir . $name );
		$this->assertFileDoesNotExist( $this->font_dir . 'DejaVuSerifCondensed.ttf' );
		$this->assertSame( [ $core[ $name ]['url'] ], $this->requested );
	}

	public function test_a_file_is_another_fonts_when_its_size_is_no_known_build() {
		$model = $this->get_model_with_manifest( [ 'Mine.ttf' => $this->manifest_entry( 'DejaVuSans.ttf', 'https://example.com/Mine.ttf' ) ] );

		$this->assertSame( '', $model->get_own_copy( 'Mine.ttf' ), 'Missing' );

		$this->copy_fixture( 'DejaVuSans.ttf', 'Mine.ttf' );
		$this->assertNotSame( '', $model->get_own_copy( 'Mine.ttf' ) );

		$this->copy_fixture( 'Chewy.ttf', 'Mine.ttf' );
		$this->assertSame( '', $model->get_own_copy( 'Mine.ttf' ) );
		$this->assertFileExists( $this->font_dir . 'Mine.ttf', 'The file at its name' );

		$this->copy_fixture( 'DejaVuSans.ttf', $model->get_hashed_name( 'Mine.ttf' ) );
		$this->assertNotSame( '', $model->get_own_copy( 'Mine.ttf' ) );
		$this->assertSame( $this->font_dir . $model->get_hashed_name( 'Mine.ttf' ), $model->get_own_copy( 'Mine.ttf' ) );
	}

	public function test_an_older_build_of_a_file_has_a_known_size() {
		$model = $this->get_model_with_manifest( [ 'Mine.ttf' => $this->manifest_entry( 'DejaVuSans.ttf', 'https://example.com/Mine.ttf' ) ] );
		$this->set_private( $model, 'legacy_manifests', [ 'core' => [ 'Mine.ttf' => $this->manifest_entry( 'Chewy.ttf', 'https://example.com/Mine.ttf' ) ] ] );

		$this->copy_fixture( 'Chewy.ttf', 'Mine.ttf' );

		$this->assertNotSame( '', $model->get_own_copy( 'Mine.ttf' ) );
		$this->assertCount( 2, $model->get_known_builds( 'Mine.ttf' ) );
	}

	public function test_adoption_checks_the_records_file_not_a_hashed_copy() {
		$model = $this->get_model_with_legacy( 'Chewy.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'Arimo-Regular.ttf' );
		$this->add_record( 'arimo', [ 'regular' => 'Arimo-Regular.ttf' ] );

		$this->assertSame( [ 'arimo' ], $model->adopt_records( 'bundled' ) );
	}

	/**
	 * A model whose core source reads a test manifest
	 *
	 * @param array|string|null $manifest Entries, the file's raw contents, or null for no file
	 */
	protected function get_model_with_manifest( $manifest ): Model_Font_Sources {
		wp_delete_file( $this->manifest_path );
		if ( $manifest !== null ) {
			$contents = is_array( $manifest ) ? '<?php return ' . var_export( $manifest, true ) . ';' : $manifest; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
			file_put_contents( $this->manifest_path, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		$model = new class( GPDFAPI::get_data_class(), $this->custom_fonts, GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			/** @var string */
			public $manifest_path = '';

			protected function get_core_source(): array {
				return array_merge( parent::get_core_source(), [ 'manifest' => $this->manifest_path ] );
			}
		};

		$model->manifest_path = $this->manifest_path;

		return $model;
	}

	protected function core_fontdata(): array {
		return ( new FontVariables() )->getDefaults()['fontdata'];
	}

	/**
	 * A model whose first source (`bundled`) and later source (`core`) each have a manifest of the entries given, and a
	 * family using each file. With no entries for the second, `core` is the real one.
	 *
	 * @param array $first
	 * @param array $second
	 */
	protected function get_model_with_two_sources( array $first, array $second ): Model_Font_Sources {
		$write = function ( string $suffix, array $entries ): string {
			$path = $this->manifest_path . $suffix . '.php';
			wp_delete_file( $path );
			file_put_contents( $path, '<?php return ' . var_export( $entries, true ) . ';' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export, WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$this->copied_fonts[] = $path;

			return $path;
		};

		$model = new class( GPDFAPI::get_data_class(), $this->custom_fonts, GPDFAPI::get_log_class() ) extends Model_Font_Sources {
			/** @var string[] The first source's and second source's manifests */
			public $manifests_at = [];

			/** @var bool Whether the second source is the real core one */
			public $real_core = false;

			protected function get_bundled_source(): array {
				return [
					'families' => [ 'first' => [ 'R' => 'Shared.ttf' ] ],
					'manifest' => $this->manifests_at[0],
				];
			}

			protected function get_core_source(): array {
				if ( $this->real_core ) {
					return parent::get_core_source();
				}

				return [
					'families' => [
						'second' => [
							'R' => 'Shared.ttf',
							'B' => 'Only.ttf',
						],
					],
					'manifest' => $this->manifests_at[1],
				];
			}
		};

		$model->manifests_at = [ $write( '-first', $first ), $second === [] ? '' : $write( '-second', $second ) ];
		$model->real_core    = $second === [];

		return $model;
	}
}
