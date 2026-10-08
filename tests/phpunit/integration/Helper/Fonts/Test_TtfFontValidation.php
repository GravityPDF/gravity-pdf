<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF_Vendor\GravityPdf\Upload\FileInfo;


/**
 * @group   helper
 * @group   fonts
 */
class Test_TtfFontValidation extends TestCase {

	/** @var string[] Temp files to clean up after each test. */
	private array $tmp_files = [];

	public function tear_down(): void {
		foreach ( $this->tmp_files as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}
		$this->tmp_files = [];

		parent::tear_down();
	}

	public function test_validate_throws_for_non_ttf_content(): void {
		$tmp = tempnam( sys_get_temp_dir(), 'gfpdf_ttf_' ) . '.ttf';
		file_put_contents( $tmp, 'not a real ttf' );
		$this->tmp_files[] = $tmp;

		$file_info  = new FileInfo( $tmp, 'bad.ttf' );
		$validation = new TtfFontValidation();

		$this->expectException( UploadException::class );
		$validation->validate( $file_info );
	}

	public function test_validate_throws_when_mpdfs_cache_cant_be_created(): void {
		$data     = \GPDFAPI::get_data_class();
		$original = $data->mpdf_tmp_location;

		/* A folder can't be created inside a file */
		$file              = tempnam( sys_get_temp_dir(), 'gfpdf_ttf_' );
		$this->tmp_files[] = $file;

		$data->mpdf_tmp_location = $file;

		try {
			( new TtfFontValidation() )->validate( new FileInfo( PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/DejaVuSans.ttf', 'DejaVuSans.ttf' ) );
			$this->fail( 'Expected an UploadException' );
		} catch ( UploadException $e ) {
			$this->assertSame( 'Unknown error occurred.', $e->getMessage() );
		} finally {
			$data->mpdf_tmp_location = $original;
		}
	}

	public function test_validate_passes_for_valid_ttf(): void {
		$file_info  = new FileInfo( PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/DejaVuSans.ttf', 'DejaVuSans.ttf' );
		$validation = new TtfFontValidation();

		$this->expectNotToPerformAssertions();
		$validation->validate( $file_info );
	}
}
