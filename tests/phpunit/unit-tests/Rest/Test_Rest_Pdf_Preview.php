<?php

namespace GFPDF\Tests;

use GFPDF\Rest\Rest_Pdf_Preview;
use WP_REST_Request;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group api
 */
class Test_Rest_Pdf_Preview extends Test_Rest {

	/**
	 * @var Rest_Pdf_Preview
	 */
	protected $api;

	function set_up() {
		global $gfpdf;

		$this->api = new Rest_Pdf_Preview( $gfpdf->options, $gfpdf->gform, $gfpdf->misc, $gfpdf->templates );

		parent::set_up();

		/* Configure mPDF with available fonts */
		$config = static function ( $config ) {
			return array_merge( $config, [
				'fontDir'  => __DIR__ . '/../../data/fonts/',
				'fontdata' => [
					'dejavusans' => [
						'R'          => 'DejaVuSans.ttf',
						'useOTL'     => 0xff,
						'useKashida' => 75,
					],
				],

				'backupSubsFont' => [],
				'backupSIPFont'  => '',
				'BMPonly'        => [ 'dejavusans' ],
			] );
		};

		add_filter( 'gfpdf_mpdf_class_config', $config );
	}

	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		foreach ( $this->api::$endpoints as $route ) {
			$this->assertArrayHasKey( '/' . $this->api::NAMESPACE . $route, $routes );
		}
	}

	public function test_create_item_preview() {

		$request = new WP_REST_Request( 'POST', '/' . $this->api::get_route_basepath() . '/' . $this->form_id . '/preview' );
		$request->set_body_params( [
			'name'     => 'Document',
			'filename' => 'Filename',
		] );

		/* Test for authentication error */
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		/* Test the PDF generator creates the preview */
		wp_set_current_user( self::$admin_id );

		/* Test the PDF was actually created */
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'headers_sent', $response->get_data()['code'] );
	}

	public function test_create_item_preview_with_entry() {

		$entry = $this->factory->entry->create_and_get( [ 'form_id' => $this->form_id ] );

		wp_set_current_user( self::$admin_id );

		$request = new WP_REST_Request( 'POST', '/' . $this->api::get_route_basepath() . '/' . $this->form_id . '/preview' );
		$request->set_body_params( [
			'name'     => 'Document',
			'filename' => 'Filename',
			'entry'    => $entry['id'],
		] );

		/* Test the PDF was actually created */
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'headers_sent', $response->get_data()['code'] );

		/* Don't pass the entry */
		$request->set_body_params( [
			'name'     => 'Document',
			'filename' => 'Filename',
		] );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'headers_sent', $response->get_data()['code'] );
	}

	public function test_create_item_preview_with_invalid_entry() {
		wp_set_current_user( self::$admin_id );

		$request = new WP_REST_Request( 'POST', '/' . $this->api::get_route_basepath() . '/' . $this->form_id . '/preview' );
		$request->set_body_params( [
			'name'     => 'Document',
			'filename' => 'Filename',
			'entry'    => 520,
		] );

		/* Test for error with invalid entry ID */
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		/* Test for error with invalid entry ID for requested form */
		$form_id  = $this->factory->form->create();
		$entry_id = $this->factory->entry->create( [ 'form_id' => $this->form_id ] );

		$request = new WP_REST_Request( 'POST', '/' . $this->api::get_route_basepath() . '/' . $form_id . '/preview' );

		$request->set_body_params( [
			'name'     => 'Document',
			'filename' => 'Filename',
			'entry'    => $entry_id,
		] );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}
}
