<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_logger;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
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
	public function setUp() {
		/* run parent method */
		parent::setUp();

		$this->logger = new Helper_Logger( 'slug', 'Name' );
	}

	/**
	 * @since 4.2
	 */
	public function test_logger() {
		$this->assertInstanceOf( '\Monolog\Logger', $this->logger->get_logger() );
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
}
