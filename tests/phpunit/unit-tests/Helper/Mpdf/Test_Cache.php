<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 */
class Test_Cache extends WP_UnitTestCase {

	/**
	 * Verify the cache directory inherits the parent directory permissions
	 *
	 * @dataProvider provider_createDirectory
	 */
	public function test_createDirectory( $permission ) {
		$basepath     = sys_get_temp_dir() . '/mpdf-cache/';
		$tmp_basepath = $basepath . 'tmp/';

		/* ensure we have a clean slate */
		@rmdir( $tmp_basepath );
		@rmdir( $basepath );

		/* Create the base directory with the correct permissions */
		mkdir( $basepath );
		chmod( $basepath, $permission );

		new Cache( $tmp_basepath );

		$base_permission = substr( decoct( fileperms( $basepath ) ), -3 );
		$tmp_permission = substr( decoct( fileperms( $tmp_basepath ) ), -3 );

		$this->assertSame( decoct( $permission ), $base_permission );
		$this->assertSame( decoct( $permission ), $tmp_permission );
	}

	public function provider_createDirectory() {
		return [
			[ 0755 ],
			[ 0775 ],
			[ 0777 ],
		];
	}

	/**
	 * A request that loses the race to create the cache directory still gets a working cache
	 *
	 * @since 6.17.1
	 */
	public function test_createDirectory_accepts_a_directory_created_concurrently() {
		$basepath = sys_get_temp_dir() . '/mpdf-cache-race';

		@rmdir( $basepath . '/mpdf' );
		wp_mkdir_p( $basepath );

		stream_wrapper_register( Concurrent_Mkdir_Stream::SCHEME, Concurrent_Mkdir_Stream::class );

		try {
			$cache = new Cache( Concurrent_Mkdir_Stream::SCHEME . '://' . $basepath . '/mpdf' );
		} finally {
			stream_wrapper_unregister( Concurrent_Mkdir_Stream::SCHEME );
		}

		$this->assertInstanceOf( Cache::class, $cache );
		$this->assertDirectoryExists( $basepath . '/mpdf' );

		@rmdir( $basepath . '/mpdf' );
		@rmdir( $basepath );
	}

	/**
	 * is_writable() can call a writable network share read-only, so the directory existing is enough
	 *
	 * @since 6.17.3
	 */
	public function test_a_directory_reported_read_only_still_gets_a_cache() {
		$basepath = sys_get_temp_dir() . '/mpdf-cache-read-only';
		wp_mkdir_p( $basepath );

		stream_wrapper_register( Read_Only_Stat_Stream::SCHEME, Read_Only_Stat_Stream::class );

		try {
			$path = Read_Only_Stat_Stream::SCHEME . '://' . $basepath;
			$this->assertFalse( is_writable( $path ) );

			$cache = new Cache( $path );
		} finally {
			stream_wrapper_unregister( Read_Only_Stat_Stream::SCHEME );
		}

		$this->assertInstanceOf( Cache::class, $cache );

		@rmdir( $basepath );
	}

	/**
	 * The plugin's idea of where mPDF's cache lives matches where mPDF puts it
	 *
	 * @since 6.17.3
	 */
	public function test_mpdf_keeps_its_cache_in_the_mpdf_folder_of_its_tempdir() {
		$data  = \GPDFAPI::get_data_class();
		$cache = $data->mpdf_tmp_location . '/mpdf';

		wp_mkdir_p( $cache );
		\GPDFAPI::get_misc_class()->rmdir( $cache );

		new \GFPDF\Helper\Helper_Mpdf( [ 'mode' => 'c', 'tempDir' => $data->mpdf_tmp_location ] );

		$this->assertDirectoryExists( $cache . '/ttfontdata' );
		$this->assertDirectoryDoesNotExist( $cache . '/mpdf' );
	}
}

/**
 * Maps SCHEME://path onto the local filesystem, where mkdir() creates the directory but reports failure, as it does
 * for the request that loses a race to create it
 */
class Concurrent_Mkdir_Stream {

	const SCHEME = 'gpdf-concurrent-mkdir';

	/** @var resource|null */
	public $context;

	public function url_stat( $path, $flags ) {
		return @stat( substr( $path, strlen( self::SCHEME ) + 3 ) );
	}

	public function mkdir( $path, $mode, $options ) {
		mkdir( substr( $path, strlen( self::SCHEME ) + 3 ), $mode, true );

		return false;
	}
}

/**
 * Maps SCHEME://path onto the local filesystem, reporting every path read-only as a network share can
 */
class Read_Only_Stat_Stream {

	const SCHEME = 'gpdf-read-only-stat';

	/** @var resource|null */
	public $context;

	public function url_stat( $path, $flags ) {
		$stat = @stat( substr( $path, strlen( self::SCHEME ) + 3 ) );

		if ( $stat ) {
			$stat['mode'] &= ~0222;
			$stat[2]       = $stat['mode'];
		}

		return $stat;
	}
}
