<?php

namespace GFPDF\Helper\Log;

use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class Test_Context_Logger extends TestCase {

	public function test_adds_context_to_every_record() {
		$handler = new TestHandler();
		$logger  = new Context_Logger( new Logger( 'test', [ $handler ] ), [ 'pdf_id' => 'abc' ] );

		$logger->debug( 'Debug', [ 'context' => 'statistics' ] );
		$logger->error( 'Error' );
		$logger->info( 'Info', [ 'pdf_id' => 'xyz' ] );

		$records = $handler->getRecords();

		$this->assertCount( 3, $records );
		$this->assertSame( [ 'context' => 'statistics', 'pdf_id' => 'abc' ], $records[0]['context'] );
		$this->assertSame( 'DEBUG', $records[0]['level_name'] );
		$this->assertSame( [ 'pdf_id' => 'abc' ], $records[1]['context'] );
		$this->assertSame( 'ERROR', $records[1]['level_name'] );

		/* A key the record already has is kept */
		$this->assertSame( [ 'pdf_id' => 'xyz' ], $records[2]['context'] );
	}
}
