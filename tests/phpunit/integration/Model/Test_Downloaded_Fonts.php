<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\Seamed_Font_Downloader;
use GFPDF\Tests\Concerns\StubsFontSources;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GPDFAPI;
use WP_Error;

/**
 * The Font Manager's downloaded fonts: listing them with why they're in use, and deleting the unused ones
 *
 * @group model
 * @group fonts
 */
class Test_Downloaded_Fonts extends TestCase {

	use CopiesFontFixtures;
	use RecordsFontFailures;
	use StubsFontSources;

	/**
	 * @var string[] The DejaVu Serif Condensed family's files
	 */
	private const SERIF = [ 'DejaVuSerifCondensed.ttf', 'DejaVuSerifCondensed-Bold.ttf', 'DejaVuSerifCondensed-Italic.ttf', 'DejaVuSerifCondensed-BoldItalic.ttf' ];

	/**
	 * @var TestHandler
	 */
	private $log;

	public function set_up(): void {
		parent::set_up();

		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
	}

	public function tear_down(): void {
		$this->remove_font_fixtures();
		$this->delete_font_failures();
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_only_downloadable_families_with_files_on_disk_are_listed() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed-Bold.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'gfpdf-Arimo-Regular.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuinfo.txt' );

		$fonts = $this->downloader()->get_downloaded_fonts();
		$size  = filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ) + filesize( $this->font_fixture( 'Chewy.ttf' ) );

		$this->assertSame(
			[
				[
					'id'           => 'dejavuserifcondensed',
					'label'        => 'Dejavu Serif Condensed',
					'source'       => 'core',
					'files'        => [
						[
							'style' => 'R',
							'file'  => 'DejaVuSerifCondensed.ttf',
							'size'  => filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ),
						],
						[
							'style' => 'B',
							'file'  => 'DejaVuSerifCondensed-Bold.ttf',
							'size'  => filesize( $this->font_fixture( 'Chewy.ttf' ) ),
						],
					],
					'size'         => $size,
					'size_label'   => size_format( $size, 1 ),
					'in_use'       => [],
					'auto_install' => true,
				],
			],
			$fonts
		);
	}

	public function test_nothing_is_listed_with_an_empty_fonts_folder() {
		$this->assertSame( [], $this->downloader()->get_downloaded_fonts() );
	}

	public function test_in_use_families_say_why() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->set_languages( [ 'th' ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ], [ 'sidebar_font' => 'FreeSerif' ], [ 'font' => 'arimo' ] ] );

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'FreeMono.ttf' );
		$this->put_freeserif_on_disk();

		$sources = $this->stub_font_sources(
			static function ( array $sources ): array {
				$sources['fixture']             = $sources['core'];
				$sources['fixture']['required'] = true;
				$sources['fixture']['families'] = [ 'fixturemono' => [ 'R' => 'FreeMono.ttf' ] ];

				return $sources;
			}
		);

		$in_use = array_column( $this->downloader( $sources )->get_downloaded_fonts(), 'in_use', 'id' );

		$this->assertSame( [ 'Default font' ], $in_use['dejavuserifcondensed'] );
		$this->assertSame( [ 'Site language (th)' ], $in_use['garuda'] );
		$this->assertSame( [ 'Used by 2 PDFs' ], $in_use['freeserif'] );
		$this->assertSame( [ 'Required by an add-on' ], $in_use['fixturemono'] );
	}

	public function test_the_downloads_off_flag_is_per_family() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->put_freeserif_on_disk();

		add_filter(
			'gfpdf_auto_install_fonts',
			static function ( $download, $file ) {
				return $file !== 'FreeSerifBold.ttf';
			},
			10,
			2
		);

		$this->assertSame(
			[
				'dejavuserifcondensed' => true,
				'freeserif'            => false,
			],
			array_column( $this->downloader()->get_downloaded_fonts(), 'auto_install', 'id' )
		);
	}

	public function test_delete_removes_the_family_whatever_its_hash_and_keeps_the_licences() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed-Bold.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuinfo.txt' );
		$this->put_freeserif_on_disk();

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame(
			[
				'id'      => 'dejavuserifcondensed',
				'removed' => [ 'DejaVuSerifCondensed.ttf', 'DejaVuSerifCondensed-Bold.ttf' ],
				'bytes'   => filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ) + filesize( $this->font_fixture( 'Chewy.ttf' ) ),
			],
			$result
		);

		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed-Bold.ttf' ) );
		$this->assertFileExists( $this->font_path( 'DejaVuinfo.txt' ) );
		$this->assertFileExists( $this->font_path( 'FreeSerif.ttf' ) );
		$this->assertCount( 1, $this->log_messages( 'Downloaded font deleted' ) );
	}

	public function test_delete_removes_only_the_hashed_copy_and_never_the_file_at_the_name() {
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$hashed  = $sources->get_hashed_name( 'DejaVuSerifCondensed.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf', $hashed );

		$fonts = $this->downloader()->get_downloaded_fonts();
		$this->assertSame( filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ), $fonts[0]['size'], 'The hashed copy is the family\'s file' );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame( [ 'DejaVuSerifCondensed.ttf' ], $result['removed'] );
		$this->assertSame( filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ), $result['bytes'] );
		$this->assertFileDoesNotExist( $this->font_path( $hashed ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_a_family_sharing_a_name_with_a_record_lists_and_deletes_only_its_hashed_copy() {
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$hashed  = $sources->get_hashed_name( 'DejaVuSerifCondensed.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->add_record( 'mine', [ 'regular' => 'DejaVuSerifCondensed.ttf' ] );

		$this->assertSame( [], $this->downloader()->get_downloaded_fonts(), 'The record\'s file is not the family\'s' );

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf', $hashed );
		$fonts = $this->downloader()->get_downloaded_fonts();

		$this->assertSame( $hashed, $fonts[0]['files'][0]['file'] );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame( [ $hashed ], $result['removed'] );
		$this->assertFileDoesNotExist( $this->font_path( $hashed ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_deleting_a_record_leaves_the_hashed_copy() {
		$hashed = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( 'DejaVuSerifCondensed.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf', $hashed );
		$this->add_record( 'mine', [ 'regular' => 'DejaVuSerifCondensed.ttf' ] );

		$this->assertTrue( GPDFAPI::delete_pdf_font( 'mine' ) );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileExists( $this->font_path( $hashed ) );
	}

	public function test_delete_clears_the_familys_failures_and_metrics() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->set_font_failures(
			[
				'DejaVuSerifCondensed-Bold.ttf' => $this->failure( time() ),
				'FreeSerif.ttf'                 => $this->failure(),
			]
		);
		$this->set_back_off( 'DejaVuSerifCondensed-Italic.ttf' );

		$metrics = GPDFAPI::get_misc_class()->get_mpdf_cache_location() . '/ttfontdata/';
		wp_mkdir_p( $metrics );
		file_put_contents( $metrics . 'dejavuserifcondensedB.mtx.json', '{}' );
		file_put_contents( $metrics . 'freeserif.mtx.json', '{}' );

		try {
			$this->assertIsArray( $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' ) );

			$this->assertSame( [ 'FreeSerif.ttf' ], array_keys( $this->get_font_failures() ) );
			$this->assertFalse( $this->get_back_off( 'DejaVuSerifCondensed-Italic.ttf' ) );
			$this->assertFileDoesNotExist( $metrics . 'dejavuserifcondensedB.mtx.json' );
			$this->assertFileExists( $metrics . 'freeserif.mtx.json' );
		} finally {
			wp_delete_file( $metrics . 'freeserif.mtx.json' );
		}
	}

	public function test_an_in_use_family_is_not_deleted() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assert_font_error( $result, 'font_in_use', 409 );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	/**
	 * @dataProvider provider_not_downloaded
	 */
	public function test_a_family_that_is_not_downloaded_is_not_found( string $key ) {
		$this->copy_fixture( 'Chewy.ttf', 'gfpdf-Arimo-Regular.ttf' );

		$this->assert_font_error( $this->downloader()->delete_downloaded_font( $key ), 'font_not_downloaded', 404 );
	}

	public function provider_not_downloaded(): array {
		return [
			'bundled'     => [ 'arimo' ],
			'unknown'     => [ 'notafont' ],
			'not on disk' => [ 'freeserif' ],
			'traversal'   => [ '../freeserif' ],
		];
	}

	public function test_another_sites_default_font_and_pdfs_are_in_use() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->put_freeserif_on_disk();

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		update_option( 'gfpdf_settings', [ 'default_font' => 'DejaVuSerifCondensed' ] );
		restore_current_blog();

		/* A site without the settings or Gravity Forms tables */
		self::factory()->blog->create();

		$downloader = $this->downloader();

		$this->assertSame(
			[
				'dejavuserifcondensed' => [ 'Used on 1 other site' ],
				'freeserif'            => [],
			],
			array_column( $downloader->get_downloaded_fonts(), 'in_use', 'id' )
		);

		$this->assert_font_error( $downloader->delete_downloaded_font( 'dejavuserifcondensed' ), 'font_in_use', 409 );
	}

	public function test_another_sites_locale_font_is_in_use() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		/* WordPress only keeps a locale it has installed, but this site has no pack for it */
		$installed = static function () {
			return [ 'th' ];
		};
		add_filter( 'get_available_languages', $installed );
		update_option( 'WPLANG', 'th' );
		remove_filter( 'get_available_languages', $installed );
		restore_current_blog();

		$this->assertSame(
			[ 'garuda' => [ 'Used on 1 other site' ] ],
			array_column( $this->downloader()->get_downloaded_fonts(), 'in_use', 'id' )
		);
	}

	/**
	 * @param mixed $result
	 */
	private function assert_font_error( $result, string $code, int $status ): void {
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( [ 'status' => $status ], $result->get_error_data() );
	}

	private function downloader( ?Model_Font_Sources $sources = null ): Seamed_Font_Downloader {
		$this->log = new TestHandler();

		return new Seamed_Font_Downloader( new Logger( 'test', [ $this->log ] ), [ 'sources' => $sources ?? $this->stub_font_sources() ] );
	}

	private function add_form_with_pdfs( array $pdfs ): void {
		$this->gf_factory()->form->create(
			[],
			[
				'title'               => 'Fonts',
				'fields'              => [],
				'gfpdf_form_settings' => $pdfs,
			]
		);
	}

	/**
	 * @return array[]
	 */
	private function log_messages( string $message ): array {
		return array_values(
			array_filter(
				$this->log->getRecords(),
				static function ( $record ) use ( $message ) {
					return $record['message'] === $message;
				}
			)
		);
	}
}
