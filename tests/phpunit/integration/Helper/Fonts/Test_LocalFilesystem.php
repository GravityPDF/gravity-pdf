<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\GravityPdf\Upload\FileInfo;

/**
 * @group   helper
 * @group   fonts
 */
class Test_LocalFilesystem extends TestCase {

	private string $dest_dir;

	public function set_up(): void {
		parent::set_up();

		$this->dest_dir = sys_get_temp_dir() . '/gfpdf_fs_test_' . uniqid();
		mkdir( $this->dest_dir );
	}

	public function tear_down(): void {
		parent::tear_down();

		foreach ( glob( $this->dest_dir . '/*' ) as $file ) {
			@unlink( $file );
		}
		@rmdir( $this->dest_dir );
	}

	public function test_instantiates_with_valid_directory(): void {
		$fs = new LocalFilesystem( $this->dest_dir );

		$this->assertInstanceOf( LocalFilesystem::class, $fs );
		$this->assertSame( $this->dest_dir, $fs->getDirectory() );
	}

	public function test_upload_copies_source_to_destination(): void {
		$src = tempnam( sys_get_temp_dir(), 'gfpdf_src_' );
		file_put_contents( $src, 'font-data-stub' );

		$file_info = new FileInfo( $src, 'myfont.ttf' );
		$fs        = new LocalFilesystem( $this->dest_dir );
		$dest      = $fs->upload( $file_info );

		$this->assertFileExists( $dest );
		$this->assertSame( 'font-data-stub', file_get_contents( $dest ) );

		unlink( $src );
	}

	public function test_upload_returns_path_containing_filename(): void {
		$src = tempnam( sys_get_temp_dir(), 'gfpdf_src_' );
		touch( $src );

		$file_info = new FileInfo( $src, 'MySpecialFont.ttf' );
		$fs        = new LocalFilesystem( $this->dest_dir );
		$dest      = $fs->upload( $file_info );

		$this->assertStringContainsString( 'MySpecialFont', $dest );

		unlink( $src );
	}
}
