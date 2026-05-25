<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Log;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group logger
 */
class Test_Logger extends TestCase {

	private $logger;

	public function set_up() {
		parent::set_up();

		$this->logger = new Logger( 'slug', 'Name' );

		update_option( 'gform_enable_logging', true );
	}

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

	public function test_register_gf_logger() {
		$results = $this->logger->register_logger_with_gf( [] );
		$this->assertArrayHasKey( 'slug', $results );
		$this->assertEquals( 'Name', $results['slug'] );
	}

	public function test_logs_rotate() {
		$gf_logger = \GFLogging::get_instance();
		$dir       = $gf_logger->get_log_dir();

		$gf_logger->delete_log_files();
		$gf_logger->update_plugin_settings(
			[
				'slug' => [
					'enable'    => true,
					'file_name' => sha1( (string) time() ),
					'log_level' => 3,
				],
			]
		);

		$log_filename = $gf_logger->get_log_file_name( 'slug' );
		$path         = pathinfo( $log_filename );
		$file_base    = $path['filename'];
		$file_ext     = $path['extension'];

		wp_mkdir_p( $dir );
		for ( $x = 1; $x <= 15; $x++ ) {
			$adjusted_date = gmdate( 'YmdGis', time() );
			$new_file_name = $file_base . '_' . $adjusted_date . $x . '.' . $file_ext;
			touch( $dir . $new_file_name );
		}

		$files = \GFCommon::glob( '*.txt', $dir );
		$this->assertCount( 15, $files );

		$this->logger->get_logger();

		$files = \GFCommon::glob( '*.txt', $dir );
		$this->assertCount( 10, $files );
	}

	public function test_setup_gravityforms_logging_no_op_when_plugin_disabled_globally() {
		delete_option( 'gform_enable_logging' );

		$logger = ( new Logger( 'slug', 'Name' ) )->get_logger();

		$handlers = $logger->getHandlers();
		$this->assertCount( 1, $handlers );
		$this->assertInstanceOf( 'GFPDF_Vendor\Monolog\Handler\NullHandler', $handlers[0] );
	}

	public function test_setup_gravityforms_logging_no_op_when_log_level_is_off() {
		\GFLogging::get_instance()->update_plugin_settings(
			[
				'level_off_slug' => [
					'enable'    => true,
					'file_name' => sha1( (string) time() ) . '_off',
					'log_level' => 6,
				],
			]
		);

		$logger = ( new Logger( 'level_off_slug', 'Name' ) )->get_logger();

		$handlers = $logger->getHandlers();
		$this->assertCount( 1, $handlers );
		$this->assertInstanceOf( 'GFPDF_Vendor\Monolog\Handler\NullHandler', $handlers[0] );
	}

	public function test_setup_gravityforms_logging_uses_error_level_when_log_level_is_4() {
		$gf_logger = \GFLogging::get_instance();
		$gf_logger->delete_log_files();
		$gf_logger->update_plugin_settings(
			[
				'error_slug' => [
					'enable'    => true,
					'file_name' => sha1( (string) time() ) . '_error',
					'log_level' => 4,
				],
			]
		);

		$logger = ( new Logger( 'error_slug', 'Name' ) )->get_logger();

		$stream_handlers = array_filter(
			$logger->getHandlers(),
			static fn( $h ) => $h instanceof \GFPDF_Vendor\Monolog\Handler\StreamHandler
		);

		$this->assertCount( 1, $stream_handlers );
		$this->assertSame( \GFPDF_Vendor\Monolog\Logger::ERROR, reset( $stream_handlers )->getLevel() );
	}
}
