<?php

namespace GFPDF\Controller;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_Webhooks
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   webhook
 */
class Test_Controller_Webhooks extends WP_UnitTestCase {

	/**
	 * Test we add the PDF URLs to the Webhook request data when the request type is "all_fields"
	 */
	public function test_webhook_request_data_all_fields() {
		$feed         = [ 'meta' => [ 'requestBodyType' => 'all_fields' ] ];
		$entry        = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$request_data = $entry;

		$request_data = apply_filters( 'gform_webhooks_request_data', $request_data, $feed, $entry );

		$this->assertArrayHasKey( 'gpdf_556690c67856b', $request_data );
		$this->assertStringContainsString( 'http://example.org/?gpdf=1', $request_data['gpdf_556690c67856b'] );

		$this->assertArrayHasKey( 'gpdf_fawf90c678523b', $request_data );
		$this->assertStringContainsString( 'http://example.org/?gpdf=1', $request_data['gpdf_fawf90c678523b'] );
	}

	/**
	 * Test that we do nothing if the request type isn't "all_fields"
	 */
	public function test_webhook_request_data_select_fields() {
		$feed         = [ 'meta' => [ 'requestBodyType' => 'select_fields' ] ];
		$entry        = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$request_data = $entry;

		$request_data = apply_filters( 'gform_webhooks_request_data', $request_data, $feed, $entry );

		$this->assertSame( $entry, $request_data );
		$this->assertArrayNotHasKey( 'gpdf_556690c67856b', $request_data );
	}
}
