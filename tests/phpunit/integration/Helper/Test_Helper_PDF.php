<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use Exception;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   pdf
 */
class Test_Helper_PDF extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'gravityform-1' ], [ 'gravityform-1' ] );
	}

	private Helper_PDF $pdf;

	public function set_up(): void {
		parent::set_up();

		global $gfpdf;

		$entry    = $this->entry( 'gravityform-1' );
		$settings = [
			'template'   => 'zadani',
			'pdf_size'   => 'A4',
			'orientation' => 'portrait',
			'format'     => 'standard',
			'security'   => 'No',
			'rtl'        => 'No',
		];

		$this->pdf = new Helper_PDF(
			$entry,
			$settings,
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);
	}

	public function test_set_and_get_output_type(): void {
		$this->pdf->set_output_type( 'SAVE' );
		$this->assertSame( 'SAVE', $this->pdf->get_output_type() );

		$this->pdf->set_output_type( 'display' );
		$this->assertSame( 'DISPLAY', $this->pdf->get_output_type() );

		$this->pdf->set_output_type( 'download' );
		$this->assertSame( 'DOWNLOAD', $this->pdf->get_output_type() );
	}

	public function test_set_output_type_throws_for_invalid_type(): void {
		$this->expectException( Exception::class );
		$this->pdf->set_output_type( 'STREAM' );
	}

	public function test_set_and_get_filename(): void {
		$this->pdf->set_filename( 'my-document' );
		$this->assertSame( 'my-document.pdf', $this->pdf->get_filename() );

		$this->pdf->set_filename( 'invoice.pdf' );
		$this->assertSame( 'invoice.pdf', $this->pdf->get_filename() );
	}

	public function test_get_path_returns_trailingslash_string(): void {
		$path = $this->pdf->get_path();

		$this->assertNotEmpty( $path );
		$this->assertSame( '/', substr( $path, -1 ) );
	}

	public function test_get_full_pdf_path_concatenates_path_and_filename(): void {
		$this->pdf->set_filename( 'test-concat' );
		$full = $this->pdf->get_full_pdf_path();

		$this->assertStringEndsWith( 'test-concat.pdf', $full );
		$this->assertStringStartsWith( $this->pdf->get_path(), $full );
	}

	public function test_set_print_dialog_throws_for_non_boolean(): void {
		$this->expectException( Exception::class );
		$this->pdf->set_print_dialog( 1 );
	}

	public function test_get_entry_returns_the_injected_entry(): void {
		$entry  = $this->entry( 'gravityform-1' );
		$result = $this->pdf->get_entry();

		$this->assertSame( $entry['id'], $result['id'] );
	}

	public function test_get_settings_returns_injected_settings(): void {
		$settings = $this->pdf->get_settings();

		$this->assertArrayHasKey( 'template', $settings );
		$this->assertSame( 'A4', $settings['pdf_size'] );
	}
}
