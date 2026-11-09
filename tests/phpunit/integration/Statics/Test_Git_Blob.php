<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group statics
 * @group fonts
 */
class Test_Git_Blob extends TestCase {

	private const FIXTURE = PDF_PLUGIN_DIR . 'tools/phpunit/data/fonts/DejaVuSerifCondensed.ttf';

	/* The SHA-1 GitHub lists for the fixture in core-fonts.json */
	private const SHA = '9fec02fcea44d6525c1cb8187883a11c01fcd9d1';

	public function test_sha1_is_the_git_blob_hash() {
		$size = (int) filesize( self::FIXTURE );

		$this->assertSame( self::SHA, Git_Blob::sha1( self::FIXTURE, $size ) );
		$this->assertTrue( Git_Blob::matches( self::FIXTURE, $size, self::SHA ) );
	}

	/**
	 * @dataProvider provider_unhashable
	 */
	public function test_an_unhashable_path_has_no_hash_and_matches_nothing( string $path ) {
		$this->assertSame( '', Git_Blob::sha1( $path, 10 ) );
		$this->assertFalse( Git_Blob::matches( $path, 10, '' ) );
	}

	public function provider_unhashable(): array {
		return [
			'missing'   => [ '/missing/font.ttf' ],
			'a folder'  => [ PDF_PLUGIN_DIR . 'fonts' ],
			'no path'   => [ '' ],
		];
	}

	public function test_an_unreadable_file_has_no_hash() {
		$path = get_temp_dir() . 'gfpdf-unreadable-' . wp_generate_password( 6, false ) . '.ttf';
		file_put_contents( $path, 'font' );
		chmod( $path, 0000 );

		try {
			$this->assertSame( '', Git_Blob::sha1( $path, 4 ) );
			$this->assertFalse( Git_Blob::matches( $path, 4, '' ) );
		} finally {
			chmod( $path, 0644 );
			wp_delete_file( $path );
		}
	}

	/**
	 * @dataProvider provider_mismatch
	 */
	public function test_a_file_that_differs_does_not_match( int $size, string $sha ) {
		$this->assertFalse( Git_Blob::matches( self::FIXTURE, $size, $sha ) );
	}

	public function provider_mismatch(): array {
		$size = (int) filesize( self::FIXTURE );

		return [
			'another size' => [ $size - 1, self::SHA ],
			'another hash' => [ $size, str_repeat( '0', 40 ) ],
			'no hash'      => [ $size, '' ],
		];
	}
}
