<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Rest\Rest_Download_Pdf;
use WP_REST_Request;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group api
 * @group rest
 */
class Test_Rest_Download_Pdf extends Test_Rest {

	/**
	 * @var Rest_Download_Pdf
	 */
	protected $api;

	public function set_up(): void {
		global $gfpdf;

		$this->api = new Rest_Download_Pdf( $gfpdf->gform, new Helper_Url_Signer() );

		parent::set_up();

		/* Configure mPDF with available fonts so the PDF generator can produce a document */
		$config = static function ( $config ) {
			return array_merge( $config, [
				'fontDir'  => PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/',
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

	/**
	 * Build the download route for a given entry and PDF ID
	 *
	 * @param int    $entry_id
	 * @param string $pdf_id
	 *
	 * @return string
	 */
	protected function get_download_route( $entry_id, $pdf_id ) {
		return '/' . $this->api::get_route_basepath() . '/' . $entry_id . '/' . $pdf_id;
	}

	public function test_get_item_permissions() {
		$pdf_id   = $this->gf_factory()->pdf->create();
		$entry_id = $this->gf_factory()->entry->create( [
			'form_id'    => $this->form_id,
			'created_by' => self::$admin_id,
		] );

		$request = new WP_REST_Request( 'POST', $this->get_download_route( $entry_id, $pdf_id ) );

		/* Anonymous users cannot access the PDF */
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 401, $response->get_status() );

		/* A logged-in user without the required capability (and who is not the entry owner) is forbidden */
		wp_set_current_user( self::$editor_id );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_get_item_owner_permissions() {
		/* The entry is owned by the editor */
		$pdf_id   = $this->gf_factory()->pdf->create();
		$entry_id = $this->gf_factory()->entry->create( [
			'form_id'    => $this->form_id,
			'created_by' => self::$editor_id,
		] );

		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', $this->get_download_route( $entry_id, $pdf_id ) );
		$request->set_url_params( [
			'entry' => $entry_id,
			'pdf'   => $pdf_id,
		] );

		$this->assertSame( true, $this->api->get_item_permissions_check( $request ) );

		/* Owner access can be revoked at the PDF level via restrict_owner */
		$pdf                   = \GPDFAPI::get_pdf( $this->form_id, $pdf_id );
		$pdf['restrict_owner'] = 'Yes';
		\GPDFAPI::update_pdf( $this->form_id, $pdf_id, $pdf );

		$this->assertSame( false, $this->api->get_item_permissions_check( $request ) );
	}

	/**
	 * @group slow
	 */
	public function test_get_item() {
		$pdf_id   = $this->gf_factory()->pdf->create();
		$entry_id = $this->gf_factory()->entry->create( [ 'form_id' => $this->form_id ] );

		wp_set_current_user( self::$admin_id );

		$request  = new WP_REST_Request( 'POST', $this->get_download_route( $entry_id, $pdf_id ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'filename', $data );
		$this->assertArrayHasKey( 'size', $data );
		$this->assertArrayHasKey( 'data', $data );

		/* The returned payload is the base64-encoded PDF, so it should decode to a PDF document */
		$this->assertSame( '%PDF', substr( (string) base64_decode( $data['data'] ), 0, 4 ) );

		/* The self link points back at the download endpoint for this entry and PDF */
		$links = $response->get_links();
		$this->assertArrayHasKey( 'self', $links );
	}

	/**
	 * @group slow
	 */
	public function test_get_item_url_type() {
		$pdf_id   = $this->gf_factory()->pdf->create();
		$entry_id = $this->gf_factory()->entry->create( [ 'form_id' => $this->form_id ] );

		wp_set_current_user( self::$admin_id );

		$request = new WP_REST_Request( 'POST', $this->get_download_route( $entry_id, $pdf_id ) );
		$request->set_body_params( [ 'type' => 'url' ] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		/* A "url" request returns a signed link instead of the base64 document */
		$data = $response->get_data();
		$this->assertArrayHasKey( 'url', $data );
		$this->assertArrayNotHasKey( 'data', $data );

		/* The link points at the download endpoint and carries a signature and expiry */
		$this->assertStringContainsString( $this->api::get_route_basepath() . "/$entry_id/$pdf_id", $data['url'] );
		$this->assertStringContainsString( 'expires=', $data['url'] );
		$this->assertStringContainsString( 'signature=', $data['url'] );
	}

	public function test_download_item_permissions() {
		$pdf_id   = $this->gf_factory()->pdf->create();
		$entry_id = $this->gf_factory()->entry->create( [ 'form_id' => $this->form_id ] );

		$url    = rest_url( $this->api::get_route_basepath() . "/$entry_id/$pdf_id" );
		$signed = ( new Helper_Url_Signer() )->sign( $url, '10 minutes' );

		$original_server = $_SERVER;

		$parts                  = wp_parse_url( $signed );
		$_SERVER['HTTP_HOST']   = $parts['host'] . ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' );
		$_SERVER['REQUEST_URI'] = $parts['path'] . '?' . $parts['query'];

		/* Match the protocol the URL was signed with so verification doesn't depend on the test site's scheme */
		if ( ( $parts['scheme'] ?? '' ) === 'https' ) {
			$_SERVER['HTTPS'] = 'on';
		} else {
			unset( $_SERVER['HTTPS'] );
		}

		/* A correctly signed URL is authorised */
		$this->assertSame( true, $this->api->download_item_permissions_check() );

		/* A tampered signature is rejected */
		$_SERVER['REQUEST_URI'] = (string) preg_replace( '/signature=[^&]+/', 'signature=tampered', $_SERVER['REQUEST_URI'] );
		$this->assertWPError( $this->api->download_item_permissions_check() );

		$_SERVER = $original_server;
	}

	public function test_get_item_with_invalid_entry() {
		$pdf_id = $this->gf_factory()->pdf->create();

		wp_set_current_user( self::$admin_id );

		/* A non-existent entry ID fails validation */
		$request  = new WP_REST_Request( 'POST', $this->get_download_route( 520, $pdf_id ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_get_item_with_invalid_pdf() {
		$entry_id = $this->gf_factory()->entry->create( [ 'form_id' => $this->form_id ] );

		wp_set_current_user( self::$admin_id );

		/* A PDF ID that is not configured on the entry fails validation (must still match the 13-hex pattern) */
		$request  = new WP_REST_Request( 'POST', $this->get_download_route( $entry_id, '0123456789abc' ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 400, $response->get_status() );
	}
}
