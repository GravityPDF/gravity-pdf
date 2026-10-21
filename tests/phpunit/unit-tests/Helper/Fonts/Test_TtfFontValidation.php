<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF_Vendor\GravityPdf\Upload\FileInfo;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_TtfFontValidation
 *
 * @package GFPDF\Helper\Fonts
 *
 * @group   helper
 * @group   fonts
 */
class Test_TtfFontValidation extends WP_UnitTestCase {

	/** @var string[] Temp files to clean up after each test. */
	private $tmp_files = [];

	public function tear_down() {
		foreach ( $this->tmp_files as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}
		$this->tmp_files = [];

		parent::tear_down();
	}

	/**
	 * @since 6.18.0
	 */
	public function test_validate_does_not_parse_a_file_that_already_passed() {
		$tmp = $this->tmp_ttf();
		copy( __DIR__ . '/../../fonts/DejaVuSans.ttf', $tmp );

		$file_info  = new FileInfo( $tmp, 'DejaVuSans.ttf' );
		$validation = new TtfFontValidation();
		$validation->validate( $file_info );

		/* Parsing it again would fail now it's no longer a font */
		file_put_contents( $tmp, 'not a real ttf' );
		$validation->validate( $file_info );

		$this->expectException( UploadException::class );
		( new TtfFontValidation() )->validate( $file_info );
	}

	/* A .ttf path in the temp folder, cleaned up along with the empty file tempnam() reserves it beside */
	private function tmp_ttf(): string {
		$base              = tempnam( sys_get_temp_dir(), 'gfpdf_ttf_' );
		$this->tmp_files[] = $base;
		$this->tmp_files[] = $base . '.ttf';

		return $base . '.ttf';
	}
}
