<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\Seamed_Core_Font_Downloader;
use GFPDF\Tests\Concerns\StubsFontSources;
use GFPDF\Tests\Integration\TestCase;
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
			'null'             => [ null ],
			'a string'         => [ 'dejavuserif' ],
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
				Seamed_Core_Font_Downloader::FREESERIF,
				/* A template's own font picker counts too */
				[ 'DejaVuSerif.ttf', 'DejaVuSerif-Bold.ttf', 'DejaVuSerif-Italic.ttf', 'DejaVuSerif-BoldItalic.ttf' ]
			),
			$this->required_with( $this->stub_font_sources() )->get_required_font_files()
		);
	}

	public function test_the_font_selections_are_read_once_until_flushed() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->assertSame( [], $this->required->get_required_font_files() );

		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ] ] );
		$this->assertSame( [], $this->required->get_required_font_files() );

		$this->required->flush();
		$this->assertSame( Seamed_Core_Font_Downloader::FREESERIF, $this->required->get_required_font_files() );
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

		$this->assertSame( Seamed_Core_Font_Downloader::FREESERIF, $this->required->get_required_font_files() );
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

	private function required_with( Model_Font_Sources $sources ): Model_Required_Fonts {
		return new Model_Required_Fonts( $sources, GPDFAPI::get_options_class() );
	}

	private function put_freeserif_on_disk(): void {
		foreach ( Seamed_Core_Font_Downloader::FREESERIF as $file ) {
			$this->copy_fixture( 'Chewy.ttf', $file );
		}
	}

	/**
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
