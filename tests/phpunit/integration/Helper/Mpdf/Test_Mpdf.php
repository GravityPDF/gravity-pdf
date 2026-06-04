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
