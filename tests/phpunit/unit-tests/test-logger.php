<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_logger;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.2
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
		$this->assertEquals( 10, has_filter( 'gform_logging_supported', [
			$this->logger,
			'register_logger_with_gf',
		] ) );
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