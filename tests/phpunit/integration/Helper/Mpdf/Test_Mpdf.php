<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF_Vendor\Mpdf\HTMLParserMode;
use GFPDF_Vendor\Mpdf\Mpdf as MpdfCore;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   mpdf
 */
class Test_Mpdf extends TestCase {

	protected function get_pdf(): Mpdf {
		return new Mpdf(
			[
				'mode'    => 'c',
				'tempDir' => sys_get_temp_dir(),
			]
		);
	}

	public function test_extends_mpdf_core() {
		$pdf = new Mpdf(
			[
				'mode'    => 'c',
				'tempDir' => sys_get_temp_dir(),
			]
		);

		$this->assertInstanceOf( MpdfCore::class, $pdf );
	}

	public function test_set_import_use_exists_for_back_compat() {
		/* Removed from mPDF 8.0 — Gravity PDF preserves it as a no-op so legacy templates
		   and add-ons that still call SetImportUse() don't fatal under mPDF 8+. */
		$this->assertTrue( method_exists( Mpdf::class, 'SetImportUse' ) );

		$pdf = new Mpdf(
			[
				'mode'    => 'c',
				'tempDir' => sys_get_temp_dir(),
			]
		);

		$this->assertNull( $pdf->SetImportUse() );
	}

	public function test_write_html_coerces_null_to_string() {
		$pdf = new Mpdf(
			[
				'mode'    => 'c',
				'tempDir' => sys_get_temp_dir(),
			]
		);

		/* Without the override mPDF's typed parent signature would TypeError on null.
		   Custom templates routinely pass null via references to undefined variables. */
		$error = null;
		try {
			$pdf->WriteHTML( null );
		} catch ( \TypeError $e ) {
			$error = $e;
		}

		$this->assertNull( $error, 'WriteHTML must coerce null to string instead of raising a TypeError.' );
	}

	public function test_write_html_falls_back_to_default_mode_for_invalid_mode() {
		$pdf = new Mpdf(
			[
				'mode'    => 'c',
				'tempDir' => sys_get_temp_dir(),
			]
		);

		/* 999 is not in HTMLParserMode::getAllModes(); the override silently substitutes
		   DEFAULT_MODE so callers don't have to validate the mode themselves. */
		$error = null;
		try {
			$pdf->WriteHTML( '<p>content</p>', 999 );
		} catch ( \Throwable $e ) {
			$error = $e;
		}

		$this->assertNull( $error, 'WriteHTML must substitute DEFAULT_MODE for unknown HTMLParserMode integers.' );
	}

	public function test_import_page_strips_leading_slash_and_use_template_mirrors_dimensions() {
		$tempDir = sys_get_temp_dir() . '/gfpdf-mpdf-import-test-' . uniqid( '', true ) . '/';
		wp_mkdir_p( $tempDir );

		$source = new Mpdf( [ 'mode' => 'c', 'tempDir' => $tempDir ] );
		$source->WriteHTML( '<html><body>source page</body></html>' );
		$source_path = $tempDir . 'source.pdf';
		$source->Output( $source_path, 'F' );
		unset( $source );

		/* FPDi rejects box names with a leading slash; the override must strip it. */
		$importer = new Mpdf( [ 'mode' => 'c', 'tempDir' => $tempDir ] );
		$importer->SetSourceFile( $source_path );
		$template_id = $importer->importPage( 1, null, null, 0, 0, '/CropBox' );
		$this->assertNotEmpty( $template_id );

		/* useTemplate must populate legacy w/h alongside modern width/height so consumers
		   written against either pre/post mPDF 8.0 API keep working. */
		$importer->AddPage();
		$size = $importer->useTemplate( $template_id );

		$this->assertArrayHasKey( 'width', $size );
		$this->assertArrayHasKey( 'height', $size );
		$this->assertSame( $size['width'], $size['w'] );
		$this->assertSame( $size['height'], $size['h'] );

		$this->rrmdir( $tempDir );
	}

	/**
	 * The call every pre-1.0.3 Business Plus template makes, which reached strtoupper() as an array and, on PHP 8,
	 * ended the request with a TypeError
	 *
	 * The size is still discarded, exactly as PHP 7 discarded it — the page keeps the size it already had. Only the
	 * fatal goes away.
	 */
	public function test_add_page_discards_an_array_second_argument_instead_of_fataling() {
		$pdf = $this->get_pdf();
		$pdf->AddPage( 'P', [ 200, 100 ] );

		$this->assertEqualsWithDelta( 210, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 297, $pdf->h, 0.01 );
	}

	/**
	 * A template asking for a page of a source PDF it doesn't have used to reach mPDF's "Invalid page format"
	 * exception; discarding the array means it never gets there
	 */
	public function test_add_page_discards_an_array_of_nulls() {
		$pdf = $this->get_pdf();
		$pdf->AddPage( 'P', [ null, null ] );

		$this->assertEqualsWithDelta( 210, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 297, $pdf->h, 0.01 );
	}

	/**
	 * Only the array is emptied, so a page size mPDF does accept still applies
	 */
	public function test_add_page_still_honours_an_explicit_page_size() {
		$pdf = $this->get_pdf();
		$pdf->AddPage( 'P', [ 200, 100 ], '', '', '', '', '', '', '', '', '', '', '', '', '', 0, 0, 0, 0, '', [ 300, 150 ] );

		$this->assertEqualsWithDelta( 300, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 150, $pdf->h, 0.01 );
	}

	/**
	 * The parameter mPDF actually declares there still has to work
	 */
	public function test_add_page_leaves_a_condition_string_alone() {
		$pdf = $this->get_pdf();
		$pdf->AddPage( 'P', 'NEXT-ODD' );

		$this->assertEqualsWithDelta( 210, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 297, $pdf->h, 0.01 );
	}

	public function test_add_page_defaults_to_the_document_page_size() {
		$pdf = $this->get_pdf();
		$pdf->AddPage();

		$this->assertEqualsWithDelta( 210, $pdf->w, 0.01 );
		$this->assertEqualsWithDelta( 297, $pdf->h, 0.01 );
	}

	private function rrmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( scandir( $dir ) ?: [] as $entry ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}

			$path = $dir . $entry;
			if ( is_dir( $path ) ) {
				$this->rrmdir( $path . '/' );
			} else {
				@unlink( $path );
			}
		}

		@rmdir( $dir );
	}
}
