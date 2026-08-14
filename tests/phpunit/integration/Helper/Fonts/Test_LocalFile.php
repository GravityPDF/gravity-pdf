<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF_Vendor\GravityPdf\Upload\ValidationInterface;
use GFPDF_Vendor\GravityPdf\Upload\FileInfoInterface;


/**
 * @group   helper
 * @group   fonts
 */
class Test_LocalFile extends TestCase {

	/** Temp file created by the current test; cleaned up in tear_down. */
	private ?string $tmp_file = null;

	public function tear_down(): void {
		unset( $_FILES['font'] );

		if ( $this->tmp_file !== null && file_exists( $this->tmp_file ) ) {
			unlink( $this->tmp_file );
			$this->tmp_file = null;
		}

		parent::tear_down();
	}

	/**
	 * LocalFile::isValid() returns true when no validations are attached.
	 */
	public function test_is_valid_with_no_validations_returns_true(): void {
		$this->tmp_file = tempnam( sys_get_temp_dir(), 'gfpdf_test_' ) . '.ttf';
		touch( $this->tmp_file );

		$_FILES['font'] = [
			'tmp_name' => $this->tmp_file,
			'name'     => 'test.ttf',
			'error'    => UPLOAD_ERR_OK,
		];

		$storage = new LocalFilesystem( sys_get_temp_dir() );
		$file    = new LocalFile( 'font', $storage );

		$this->assertTrue( $file->isValid() );
	}

	/**
	 * LocalFile::isValid() collects errors from failing validations and returns false.
	 */
	public function test_is_valid_returns_false_and_collects_error_when_validation_fails(): void {
		$this->tmp_file = tempnam( sys_get_temp_dir(), 'gfpdf_test_' ) . '.ttf';
		touch( $this->tmp_file );

		$_FILES['font'] = [
			'tmp_name' => $this->tmp_file,
			'name'     => 'mybadfile.ttf',
			'error'    => UPLOAD_ERR_OK,
		];

		$storage = new LocalFilesystem( sys_get_temp_dir() );
		$file    = new LocalFile( 'font', $storage );

		$failing = new class( 'not a real font' ) implements ValidationInterface {
			/** @var string */
			private $msg;

			public function __construct( string $msg ) {
				$this->msg = $msg;
			}

			public function validate( FileInfoInterface $file ): void {
				throw new UploadException( $this->msg );
			}
		};

		$file->addValidation( $failing );

		$this->assertFalse( $file->isValid() );
		$errors = $file->getErrors();
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'not a real font', $errors[0] );
	}

	/**
	 * LocalFile::isValid() resets its errors per call, so repeated calls don't accumulate duplicates.
	 *
	 * The override reimplements the parent's loop, so it needs the parent's reset of its own —
	 * upload() calls isValid() again, which would otherwise report every error twice.
	 */
	public function test_is_valid_resets_errors_between_calls(): void {
		$this->tmp_file = tempnam( sys_get_temp_dir(), 'gfpdf_test_' ) . '.ttf';
		touch( $this->tmp_file );

		$_FILES['font'] = [
			'tmp_name' => $this->tmp_file,
			'name'     => 'mybadfile.ttf',
			'error'    => UPLOAD_ERR_OK,
		];

		$storage = new LocalFilesystem( sys_get_temp_dir() );
		$file    = new LocalFile( 'font', $storage );

		$file->addValidation(
			new class implements ValidationInterface {
				public function validate( FileInfoInterface $file ): void {
					throw new UploadException( 'not a real font' );
				}
			}
		);

		$this->assertFalse( $file->isValid() );
		$this->assertCount( 1, $file->getErrors() );

		$this->assertFalse( $file->isValid() );
		$this->assertCount( 1, $file->getErrors() );
	}

	/**
	 * Errors recorded while reading $_FILES survive the reset.
	 *
	 * LocalFile skips the is-uploaded-file check, so a transfer-level failure is all it can report.
	 */
	public function test_is_valid_keeps_constructor_errors(): void {
		$_FILES['font'] = [
			'tmp_name' => '',
			'name'     => 'too-big.ttf',
			'error'    => UPLOAD_ERR_FORM_SIZE,
		];

		$storage = new LocalFilesystem( sys_get_temp_dir() );
		$file    = new LocalFile( 'font', $storage );

		$this->assertCount( 1, $file->getErrors() );

		$this->assertFalse( $file->isValid() );
		$this->assertCount( 1, $file->getErrors() );
		$this->assertStringContainsString( 'too-big', $file->getErrors()[0] );
	}

	/**
	 * LocalFile::isValid() returns true when all validations pass.
	 */
	public function test_is_valid_returns_true_when_all_validations_pass(): void {
		$this->tmp_file = tempnam( sys_get_temp_dir(), 'gfpdf_test_' ) . '.ttf';
		touch( $this->tmp_file );

		$_FILES['font'] = [
			'tmp_name' => $this->tmp_file,
			'name'     => 'good.ttf',
			'error'    => UPLOAD_ERR_OK,
		];

		$storage = new LocalFilesystem( sys_get_temp_dir() );
		$file    = new LocalFile( 'font', $storage );

		$passing = new class implements ValidationInterface {
			public function validate( FileInfoInterface $file ): void {}
		};

		$file->addValidation( $passing );
		$file->addValidation( $passing );

		$this->assertTrue( $file->isValid() );
		$this->assertEmpty( $file->getErrors() );
	}
}
