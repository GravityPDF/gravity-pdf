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
	private $tmp_files = [];

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

	public function test_validate_passes_for_valid_ttf(): void {
		$font_path = ABSPATH . '../vendor/wp-phpunit/wp-phpunit/data/fonts/OpenSans-Regular.ttf';

		if ( ! file_exists( $font_path ) ) {
			$this->markTestSkipped( 'OpenSans-Regular.ttf fixture not present; cannot exercise the valid-font path.' );
		}

		$file_info  = new FileInfo( $font_path, 'OpenSans-Regular.ttf' );
		$validation = new TtfFontValidation();

		$this->expectNotToPerformAssertions();
		$validation->validate( $file_info );
	}
}
