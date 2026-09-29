<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 */
class Test_Cache extends TestCase {

	/** @var string Unique per test, so a leftover another user owns in the sticky /tmp can't fail a later run */
	private $basepath;

	public function set_up(): void {
		parent::set_up();
		$this->basepath = trailingslashit( sys_get_temp_dir() ) . uniqid( 'gfpdf-mpdf-cache-' ) . '/';
	}

	public function tear_down(): void {
		@rmdir( $this->basepath . 'tmp' );
		@rmdir( $this->basepath . 'mpdf' );
		@rmdir( $this->basepath );
		parent::tear_down();
	}

	/**
	 * Verify the cache directory inherits the parent directory permissions
	 *
	 * @dataProvider provider_createDirectory
	 */
	public function test_createDirectory( $permission ) {
		$basepath     = $this->basepath;
		$tmp_basepath = $basepath . 'tmp/';

		/* Create the base directory with the correct permissions */
		mkdir( $basepath );
		chmod( $basepath, $permission );

		new Cache( $tmp_basepath );

		$base_permission = substr( decoct( fileperms( $basepath ) ), -3 );
		$tmp_permission = substr( decoct( fileperms( $tmp_basepath ) ), -3 );

		$this->assertSame( decoct( $permission ), $base_permission );
		$this->assertSame( decoct( $permission ), $tmp_permission );
	}

	public function provider_createDirectory(): array {
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
		$basepath = untrailingslashit( $this->basepath );

		wp_mkdir_p( $basepath );

		stream_wrapper_register( Concurrent_Mkdir_Stream::SCHEME, Concurrent_Mkdir_Stream::class );

		try {
			$cache = new Cache( Concurrent_Mkdir_Stream::SCHEME . '://' . $basepath . '/mpdf' );
		} finally {
			stream_wrapper_unregister( Concurrent_Mkdir_Stream::SCHEME );
		}

		$this->assertInstanceOf( Cache::class, $cache );
		$this->assertDirectoryExists( $basepath . '/mpdf' );
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
