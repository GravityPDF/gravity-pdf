<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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

	/**
	 * Locate one table in a font's directory, so a corruption can be aimed inside it
	 *
	 * @return array{0: int, 1: int} Its offset and length
	 */
	private function table_position( string $bytes, string $tag ): array {
		$count = unpack( 'n', substr( $bytes, 4, 2 ) )[1];

		for ( $i = 0; $i < $count; $i++ ) {
			$record = substr( $bytes, 12 + $i * 16, 16 );

			if ( substr( $record, 0, 4 ) === $tag ) {
				return [ unpack( 'N', substr( $record, 8, 4 ) )[1], unpack( 'N', substr( $record, 12, 4 ) )[1] ];
			}
		}

		$this->fail( "The fixture font has no {$tag} table" );
	}

	/**
	 * A font whose bytes were altered after it was built still parses and still renders — it simply renders the
	 * wrong glyphs, the first time text reaches the part that moved. The table directory's own checksums are the
	 * only thing that notices, and reading with them on is what this asserts.
	 */
	public function test_a_font_whose_tables_were_altered_is_refused(): void {
		$pristine = (string) file_get_contents( PDF_PLUGIN_DIR . 'fonts/Arimo-Regular.ttf' );

		/* The donor has to pass first, or the corrupted one proves nothing */
		$good = tempnam( sys_get_temp_dir(), 'gfpdf_ttf_' ) . '.ttf';
		file_put_contents( $good, $pristine );
		$this->tmp_files[] = $good;

		( new TtfFontValidation() )->validate( new FileInfo( $good, 'Arimo-Regular.ttf' ) );

		[ $offset, $length ] = $this->table_position( $pristine, 'GSUB' );

		$corrupt = $pristine;
		$at      = $offset + intdiv( $length, 2 );
		for ( $i = 0; $i < 64; $i++ ) {
			$corrupt[ $at + $i ] = chr( ( ord( $corrupt[ $at + $i ] ) + 0x5A ) % 256 );
		}

		$path = tempnam( sys_get_temp_dir(), 'gfpdf_ttf_' ) . '.ttf';
		file_put_contents( $path, $corrupt );
		$this->tmp_files[] = $path;

		$this->expectException( UploadException::class );
		( new TtfFontValidation() )->validate( new FileInfo( $path, 'Arimo-Regular.ttf' ) );
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
