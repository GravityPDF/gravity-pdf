<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_logger;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.2
 */

/**
 * Test the logger helper works correctly
 *
 * @since 4.2
 * @group logger
 */
class Test_Logger extends WP_UnitTestCase {

	/**
	 * @var Helper_Logger
	 *
	 * @since 4.2
	 */
	private $logger;

	/**
	 * @since 4.2
	 */
	public function set_up() {
		/* run parent method */
		parent::set_up();

		$this->logger = new Helper_Logger( 'slug', 'Name' );

		update_option( 'gform_enable_logging', true );
	}

	/**
	 * @since 4.2
	 */
	public function test_logger() {
		$this->assertInstanceOf( '\Psr\Log\LoggerInterface', $this->logger->get_logger() );
		$this->assertEquals(
			10,
			has_filter(
				'gform_logging_supported',
				[
					$this->logger,
					'register_logger_with_gf',
				]
			)
		);
	}

	/**
	 * @since 4.2
	 */
	public function test_register_gf_logger() {
		$results = $this->logger->register_logger_with_gf( [] );
		$this->assertArrayHasKey( 'slug', $results );
		$this->assertEquals( 'Name', $results['slug'] );
	}

	/**
	 * @since 6.15.0
	 */
	public function test_logs_rotate() {
		$gf_logger = \GFLogging::get_instance();
		$dir      = $gf_logger->get_log_dir();

		/* Prepare GF logging environment */
		$gf_logger->delete_log_files();
		$gf_logger->update_plugin_settings( [
			'slug' => [
				'enable' => true,
				'file_name' => sha1( time() ),
				'log_level' => 3,
			]
		] );

		$log_filename = $gf_logger->get_log_file_name( 'slug' );

		/* Create and verify log files */
		$path       = pathinfo( $log_filename );
		$file_base  = $path['filename'];
		$file_ext   = $path['extension'];

		wp_mkdir_p( $dir );
		for ( $x = 1; $x <= 15; $x ++ ) {
			$adjusted_date = gmdate( 'YmdGis', time() );
			$new_file_name = $file_base . '_' . $adjusted_date .  $x . '.' . $file_ext;
			touch( $dir . $new_file_name );
		}

		$files = \GFCommon::glob( '*.txt', $dir );
		$this->assertNotEmpty( $files );
		$this->assertCount( 15, $files );

		/* Set up logger + log rotation */
		$this->logger->get_logger();

		/* Verify logs were rotated to the maximum number allowed */
		$files = \GFCommon::glob( '*.txt', $dir );
		$this->assertNotEmpty( $files );
		$this->assertCount( 10, $files );
	}
}
