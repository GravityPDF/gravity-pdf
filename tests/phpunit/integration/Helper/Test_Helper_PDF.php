<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use Exception;
use GFPDF\Tests\Concerns\CreatesLegacyTemplates;
use GFPDF\Tests\Concerns\SpawnsPhpProcesses;
use GFPDF\Tests\Concerns\UsesLockableTmpLocation;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   pdf
 */
class Test_Helper_PDF extends TestCase {

	use CreatesLegacyTemplates;
	use SpawnsPhpProcesses;
	use UsesLockableTmpLocation;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'gravityform-1' ], [ 'gravityform-1' ] );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

	private Helper_PDF $pdf;

	public function set_up(): void {
		parent::set_up();

		global $gfpdf;

		$entry    = $this->entry( 'gravityform-1' );
		$settings = [
			'template'    => 'zadani',
			'pdf_size'    => 'A4',
			'orientation' => 'portrait',
			'format'      => 'standard',
			'security'    => 'No',
			'rtl'         => 'No',
			'font'        => 'dejavusans',
			'font_size'   => 9,
			'font_colour' => '#000',
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

	public function tear_down(): void {
		$this->restore_tmp_location();

		parent::tear_down();
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

	public function test_set_template_resolves_path_for_zadani(): void {
		$this->pdf->set_template();

		$path = $this->pdf->get_template_path();

		$this->assertNotEmpty( $path );
		$this->assertStringEndsWith( 'zadani.php', $path );
		$this->assertFileExists( $path );
	}

	/**
	 * A v3 template is refused rather than included, since the Gravity Forms scaffolding its boilerplate guards on
	 * is gone: it would return early, and mPDF would write a blank PDF the caller reads as a success
	 */
	public function test_set_template_refuses_a_legacy_template(): void {
		global $gfpdf;

		$path = $this->create_legacy_template();

		$pdf = new Helper_PDF(
			$this->entry( 'gravityform-1' ),
			array_merge( $this->pdf->get_settings(), [ 'template' => 'my-legacy-template' ] ),
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);

		try {
			$this->expectException( Exception::class );
			$this->expectExceptionMessage( 'is a legacy (v3) template' );

			$pdf->set_template();
		} finally {
			$this->delete_legacy_templates( $path );
		}
	}

	public function test_init_constructs_mpdf_object_for_save_output(): void {
		$this->pdf->set_output_type( 'SAVE' );

		$this->pdf->init();

		$this->assertInstanceOf( \GFPDF\Helper\Mpdf\Mpdf::class, $this->mpdf_property() );
	}

	public function test_generate_returns_pdf_binary_for_save_output(): void {
		$this->pdf->set_output_type( 'SAVE' );
		$this->pdf->init();
		$this->pdf->render_html( [ 'settings' => $this->pdf->get_settings() ], '<p>Generated PDF body</p>' );

		$binary = $this->pdf->generate();

		$this->assertIsString( $binary );
		$this->assertNotEmpty( $binary );
		$this->assertStringStartsWith( '%PDF-', $binary );
	}

	public function test_save_pdf_writes_binary_to_disk_at_expected_path(): void {
		$this->pdf->set_output_type( 'SAVE' );
		$this->pdf->set_filename( 'test-helper-pdf-save-' . uniqid() );
		$this->pdf->init();
		$this->pdf->render_html( [ 'settings' => $this->pdf->get_settings() ], '<p>Saved PDF body</p>' );

		$path = $this->pdf->save_pdf( $this->pdf->generate() );

		try {
			$this->assertFileExists( $path );
			$this->assertSame( $this->pdf->get_full_pdf_path(), $path );
			$this->assertStringStartsWith( '%PDF-', file_get_contents( $path ) );
		} finally {
			@unlink( $path );
		}
	}

	public function test_save_pdf_leaves_no_temporary_file(): void {
		$this->use_scratch_path( 'atomic' );

		$path = $this->pdf->save_pdf( '%PDF-1.4 body' );

		try {
			$this->assertSame( '%PDF-1.4 body', file_get_contents( $path ) );
			$this->assertSame( [], glob( $this->pdf->get_path() . '*.tmp' ) );
		} finally {
			$this->gfpdf()->misc->rmdir( $this->pdf->get_path() );
		}
	}

	public function test_save_pdf_rejects_empty_output(): void {
		$this->use_scratch_path( 'empty' );

		try {
			$this->pdf->save_pdf( '' );
			$this->fail( 'An empty PDF should not be saved' );
		} catch ( Exception $e ) {
			$this->assertFileDoesNotExist( $this->pdf->get_full_pdf_path() );
		}
	}

	public function test_save_pdf_removes_the_temporary_file_when_the_rename_fails(): void {
		$this->use_scratch_path( 'blocked' );

		/* A non-empty directory where the PDF should go makes the rename fail */
		wp_mkdir_p( $this->pdf->get_full_pdf_path() . '/child' );

		try {
			$this->pdf->save_pdf( '%PDF-1.4 body' );
			$this->fail( 'A failed rename should throw' );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'Could not save PDF', $e->getMessage() );
			$this->assertSame( [], glob( $this->pdf->get_path() . '*.tmp' ) );
		} finally {
			$this->gfpdf()->misc->rmdir( $this->pdf->get_path() );
		}
	}

	/**
	 * @group slow
	 */
	public function test_a_concurrent_writer_never_leaves_a_torn_pdf(): void {
		$this->use_lockable_tmp_location();
		$this->use_scratch_path( 'rival-writers' );

		$mine   = '%PDF-' . str_repeat( 'A', 2 * MB_IN_BYTES );
		$theirs = $this->pdf->save_pdf( '%PDF-' . str_repeat( 'B', 3 * MB_IN_BYTES ) ) . '.source';
		rename( $this->pdf->get_full_pdf_path(), $theirs );

		/* The same stage-and-rename save_pdf() does */
		$script = '$end = microtime( true ) + $argv[3];'
			. 'while ( microtime( true ) < $end ) {'
			. '$tmp = $argv[1] . "." . uniqid( "", true ) . ".tmp";'
			. 'if ( copy( $argv[2], $tmp ) ) { rename( $tmp, $argv[1] ); } elseif ( is_file( $tmp ) ) { unlink( $tmp ); }'
			. '}';

		try {
			$writer = $this->spawn_php( $script, [ $this->pdf->get_full_pdf_path(), $theirs, '1' ] );
			$end    = microtime( true ) + 1;

			while ( microtime( true ) < $end ) {
				$this->pdf->save_pdf( $mine );
			}

			proc_close( $writer );

			$this->assertContains( md5_file( $this->pdf->get_full_pdf_path() ), [ md5( $mine ), md5_file( $theirs ) ] );
			$this->assertSame( [], glob( $this->pdf->get_path() . '*.tmp' ) );
		} finally {
			$this->gfpdf()->misc->rmdir( $this->pdf->get_path() );
		}
	}

	/**
	 * @group slow
	 */
	public function test_a_reader_never_sees_a_partial_pdf(): void {
		$this->use_lockable_tmp_location();
		$this->use_scratch_path( 'read-during-write' );

		$pdfs   = [ '%PDF-' . str_repeat( 'A', 2 * MB_IN_BYTES ), '%PDF-' . str_repeat( 'B', 3 * MB_IN_BYTES ) ];
		$file   = $this->pdf->save_pdf( $pdfs[0] );
		$result = $this->pdf->get_path() . 'reads.txt';

		$script = '$end = microtime( true ) + $argv[2]; $good = 0; $bad = 0;'
			. 'while ( microtime( true ) < $end ) {'
			. '$pdf = @file_get_contents( $argv[1] ); if ( $pdf === false ) { continue; }'
			. 'in_array( md5( $pdf ), [ $argv[3], $argv[4] ], true ) ? $good++ : $bad++;'
			. '}'
			. 'file_put_contents( $argv[5], "$good $bad" );';

		try {
			$reader = $this->spawn_php( $script, [ $file, '1', md5( $pdfs[0] ), md5( $pdfs[1] ), $result ] );

			for ( $i = 1; proc_get_status( $reader )['running']; $i++ ) {
				$this->pdf->save_pdf( $pdfs[ $i % 2 ] );
			}

			proc_close( $reader );

			[ $good, $bad ] = array_map( 'intval', explode( ' ', (string) file_get_contents( $result ) ) );

			$this->assertSame( 0, $bad, 'A read came back part-written' );
			$this->assertGreaterThan( 50, $good, 'Too few reads landed to say anything about the race' );
		} finally {
			$this->gfpdf()->misc->rmdir( $this->pdf->get_path() );
		}
	}

	public function test_is_cache_path(): void {
		$this->assertTrue( $this->pdf->is_cache_path() );
		$this->assertStringContainsString( '/cache/', $this->pdf->get_path() );

		$this->pdf->set_path( get_temp_dir() );
		$this->assertFalse( $this->pdf->is_cache_path() );

		$this->pdf->set_path();
		$this->assertTrue( $this->pdf->is_cache_path() );
	}

	public function test_set_path_uses_a_one_off_path_when_the_cache_key_cannot_be_built(): void {
		global $gfpdf;

		$settings              = $this->pdf->get_settings();
		$settings['font_size'] = INF;

		$pdf1 = new Helper_PDF( $this->pdf->get_entry(), $settings, $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates, $gfpdf->log );
		$pdf2 = new Helper_PDF( $this->pdf->get_entry(), $settings, $gfpdf->gform, $gfpdf->data, $gfpdf->misc, $gfpdf->templates, $gfpdf->log );

		$this->assertFalse( $pdf1->is_cache_path() );
		$this->assertStringContainsString( '/uncached/', $pdf1->get_path() );
		$this->assertNotSame( $pdf1->get_path(), $pdf2->get_path() );
	}

	public function test_set_creator_overrides_default_value(): void {
		$this->pdf->set_output_type( 'SAVE' );
		$this->pdf->init();

		$this->pdf->set_creator( 'Custom Creator' );

		$this->assertSame( 'Custom Creator', $this->mpdf_property()->creator );
	}

	public function test_get_pdf_class_returns_the_mpdf_instance_set_js_writes_to(): void {
		$this->pdf->set_output_type( 'SAVE' );
		$this->pdf->init();

		$this->assertSame( $this->mpdf_property(), $this->pdf->get_pdf_class() );

		$this->pdf->set_JS( 'this.print();' );
		$this->assertSame( 'this.print();', $this->mpdf_property()->js );
	}

	private function use_scratch_path( string $filename ): void {
		$this->pdf->set_path( $this->gfpdf()->data->template_tmp_location . 'test-save-' . uniqid() );
		$this->pdf->set_filename( $filename );
	}

	private function mpdf_property() {
		$ref = new \ReflectionProperty( $this->pdf, 'mpdf' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}
		return $ref->getValue( $this->pdf );
	}
}
