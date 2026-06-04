<?php

declare( strict_types=1 );

namespace GFPDF\Model;
use Exception;
use GFPDF\Controller\Controller_Templates;
use GFPDF\Helper\Fonts\LocalFile;
use GFPDF\Helper\Fonts\LocalFilesystem;
use GFPDF\Model\Model_Templates;
use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF\Tests\Integration\TestCase;
use ZipArchive;

/**
 * Test Gravity PDF Templates Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / controller for the Templates UI
 *
 * @since 4.1
 * @group templates
 */
class Test_Templates extends TestCase {

	/**
	 * Our Templates Controller
	 *
	 * @var Controller_Templates
	 * @since 4.1
	 */
	public $controller;

	/**
	 * Our Templates Model
	 *
	 * @var Model_Templates
	 * @since 4.1
	 */
	public $model;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.1
	 */
	public function set_up(): void {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		/* Test_Uninstaller leaves the folder structure removed; recreate so tests can write into them. */
		wp_mkdir_p( $gfpdf->data->template_tmp_location );
		wp_mkdir_p( $gfpdf->data->template_location );

		/* Stale template cache from other tests can map basenames to deleted paths, so get_file_data() blows up. */
		\GFCache::flush();
		$gfpdf->templates->flush_template_transient_cache();

		/* Setup our test classes */
		$this->model      = new Model_Templates( $gfpdf->templates, $gfpdf->log, $gfpdf->data, $gfpdf->misc );
		$this->controller = new Controller_Templates( $this->model );
		$this->controller->init();
	}

	/**
	 * Get a stub we can use for testing
	 *
	 * @return LocalFile
	 *
	 * @since 4.1
	 */
	private function getFileStub(): LocalFile {
		global $gfpdf;

		$storage = new LocalFilesystem( $gfpdf->data->template_tmp_location );

		return new LocalFile( 'template', $storage );
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.1
	 */
	public function test_actions() {

		$this->assertSame(
			10,
			has_action(
				'wp_ajax_gfpdf_upload_template',
				[
					$this->model,
					'ajax_process_uploaded_template',
				]
			)
		);

		$this->assertSame(
			10,
			has_action(
				'wp_ajax_gfpdf_delete_template',
				[
					$this->model,
					'ajax_process_delete_template',
				]
			)
		);

		$this->assertSame(
			10,
			has_action(
				'wp_ajax_gfpdf_get_template_options',
				[
					$this->model,
					'ajax_process_build_template_options_html',
				]
			)
		);
	}

	/**
	 * Test we correctly move a file using
	 *
	 * @since 4.1
	 */
	public function test_move_template_to_tmp_dir() {
		global $gfpdf;

		/* Setup a test file */
		$test_file = $gfpdf->data->template_location . 'test-file.txt';
		touch( $test_file );

		$_FILES['template'] = [
			'name'     => 'test-file.txt',
			'tmp_name' => $test_file,
			'error'    => UPLOAD_ERR_OK,
		];

		/* Check the validation works */
		try {
			$this->model->move_template_to_tmp_dir( $this->getFileStub() );
			unlink( $test_file );
			$this->fail( 'Expected UploadException on invalid template upload was not thrown.' );
		} catch ( UploadException $e ) {
			unlink( $test_file );
			$this->assertSame( 'File validation failed', $e->getMessage() );
		}

		/* Setup a valid zip */
		$test_file = $gfpdf->data->template_location . 'test-archive.zip';

		$zip = new ZipArchive();
		$zip->open( $test_file, ZipArchive::CREATE );
		$zip->addFromString( 'tmp', '' );
		$zip->close();

		$_FILES['template']['name']     = 'test-archive.zip';
		$_FILES['template']['tmp_name'] = $test_file;

		try {
			$path = $this->model->move_template_to_tmp_dir( $this->getFileStub() );
		} catch ( UploadException $e ) {
			//do nothing
		}

		$this->assertStringContainsString( $gfpdf->data->template_tmp_location, $path );
		$this->assertStringContainsString( '.zip', $path );

		/* Cleanup */
		@unlink( $test_file );
		@unlink( $path );
	}


	/**
	 * Get if we get the expected results
	 *
	 * @param string $expected
	 * @param string $zip_path
	 *
	 * @since        4.1
	 *
	 * @dataProvider provider_get_unzipped_dir_name
	 */
	public function test_get_unzipped_dir_name( $expected, $zip_path ) {
		$this->assertSame( $expected, $this->model->get_unzipped_dir_name( $zip_path ) );
	}

	/**
	 * Data Provider for test_get_unzipped_dir_name()
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function provider_get_unzipped_dir_name(): array {
		return [
			[
				'expected' => '/my/path/file/',
				'zip_path' => '/my/path/file.zip',
			],

			[
				'expected' => './test_file/',
				'zip_path' => 'test_file.zip',
			],

			[
				'expected' => '/wp-content/uploads/PDF_EXTENDED_TEMPLATES/tmp/923jfa02693/',
				'zip_path' => '/wp-content/uploads/PDF_EXTENDED_TEMPLATES/tmp/923jfa02693.zip',
			],

			[
				'expected' => '/my-working-dir/is/here/the-zip-file/',
				'zip_path' => '/my-working-dir/is/here/the-zip-file.zip',
			],
		];
	}

	/**
	 * Verify we can correctly unzip an archive and check there are valid PDF templates within
	 * said archive.
	 *
	 * Tested: unzip_and_verify_templates() and check_for_valid_pdf_templates()
	 *
	 * @since 4.1
	 */
	public function test_unzip_and_verify_templates() {
		global $gfpdf;

		/* A cached "Legacy" group for an unzipped path short-circuits the v4 header check and would
		 * falsely throw "not a valid PDF Template", so flush the transient cache before and after. */
		$gfpdf->templates->flush_template_transient_cache();

		try {
			$this->model->unzip_and_verify_templates( 'test.txt' );
			$this->fail( 'Expected Exception on incompatible archive was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Incompatible Archive.', $e->getMessage() );
		}

		/* Use a uniquely-named zip per stage so concurrent or prior tests can't pollute the
		 * extraction directory (`get_unzipped_dir_name()` derives the dir from the zip name). */
		try {
			$empty_zip = $this->make_zip( [ 'tmp' => '' ] );
			try {
				$this->model->unzip_and_verify_templates( $empty_zip );
				$this->fail( 'Expected Exception when no valid PDF template in archive was not thrown.' );
			} catch ( Exception $e ) {
				$this->assertSame( 'No valid PDF template found in Zip archive.', $e->getMessage() );
			}

			$valid_zip = $this->make_zip(
				[
					'zadani.php' => PDF_PLUGIN_DIR . 'src/templates/zadani.php',
					'rubix.php'  => PDF_PLUGIN_DIR . 'src/templates/rubix.php',
				]
			);
			try {
				$this->model->unzip_and_verify_templates( $valid_zip );
			} catch ( Exception $e ) {
				$this->fail( 'Unexpected exception unzipping valid templates: ' . $e->getMessage() );
			}

			$invalid_filename_zip = $this->make_zip(
				[
					'zadani.php'         => PDF_PLUGIN_DIR . 'src/templates/zadani.php',
					'zad@!@#$%^&*().php' => PDF_PLUGIN_DIR . 'src/templates/zadani.php',
				]
			);
			$caught = null;
			try {
				$this->model->unzip_and_verify_templates( $invalid_filename_zip );
			} catch ( Exception $e ) {
				$caught = $e;
			}
			$this->assertNotNull( $caught, 'Expected Exception on invalid filename was not thrown.' );
			$this->assertStringContainsString( 'contains invalid characters.', $caught->getMessage() );
		} finally {
			foreach ( [ $empty_zip ?? null, $valid_zip ?? null, $invalid_filename_zip ?? null ] as $zip_path ) {
				if ( $zip_path === null ) {
					continue;
				}
				@unlink( $zip_path ); /* phpcs:ignore */
				$gfpdf->misc->rmdir( $this->model->get_unzipped_dir_name( $zip_path ) );
			}
			$gfpdf->templates->flush_template_transient_cache();
		}
	}

	/** Build a zip at a unique tmp path; entries map archive-name => file path (added via addFile) or raw content string (addFromString). */
	private function make_zip( array $entries ): string {
		global $gfpdf;

		$path = $gfpdf->data->template_tmp_location . uniqid( 'gfpdf-test-', true ) . '.zip';
		$zip  = new ZipArchive();
		$zip->open( $path, ZipArchive::CREATE );
		foreach ( $entries as $name => $source ) {
			is_file( $source ) ? $zip->addFile( $source, $name ) : $zip->addFromString( $name, $source );
		}
		$zip->close();

		return $path;
	}

	/**
	 * Check we can get information about our PDF templates
	 *
	 * @since 4.1
	 */
	public function test_get_template_info() {

		$files = [
			PDF_PLUGIN_DIR . 'src/templates/zadani.php',
			PDF_PLUGIN_DIR . 'src/templates/rubix.php',
		];

		$info = $this->model->get_template_info( $files );

		$this->assertCount( 2, $info );
		$this->assertArrayHasKey( 'version', $info[0] );
		$this->assertArrayHasKey( 'version', $info[1] );
		$this->assertSame( 'Zadani', $info[0]['template'] );
	}

	public function test_check_for_valid_pdf_templates_throws_on_invalid_filename() {
		global $gfpdf;

		$invalid = $gfpdf->data->template_tmp_location . 'bad@name!.php';
		touch( $invalid );

		try {
			$this->model->check_for_valid_pdf_templates( [ $invalid ] );
			$thrown = false;
		} catch ( Exception $e ) {
			$thrown = true;
			$this->assertStringContainsString( 'contains invalid characters', $e->getMessage() );
		}

		unlink( $invalid );

		$this->assertTrue( $thrown );
	}

	public function test_delete_template_unlinks_files_for_template_id() {
		global $gfpdf;

		$template_dir = $gfpdf->templates->get_template_path();
		$template_id  = 'phpunit-fixture-template';
		$template_php = $template_dir . $template_id . '.php';

		file_put_contents( $template_php, "<?php\n/* Template Name: PHPUnit Fixture */\n" );
		$this->assertFileExists( $template_php );

		$gfpdf->templates->flush_template_transient_cache();
		$this->model->delete_template( $template_id );

		$this->assertFileDoesNotExist( $template_php );
	}

	/**
	 * Check our unzipped directory is correctly cleaned up
	 *
	 * @since 4.1
	 */
	public function cleanup_template_files() {
		global $gfpdf;

		/* Create test directory and verify it exists */
		$test_dir = $gfpdf->misc->template_tmp_location . '12323233/';

		mkdir( $test_dir );
		touch( $test_dir . 'test.txt' );

		$this->assertFileExists( $test_dir . 'test.txt' );

		/* Run our method being tested and check it correctly cleaned up files */
		$this->cleanup_template_files();

		$this->assertFileDoesNotExist( $test_dir . 'test.txt' );
		$this->assertFileDoesNotExist( $test_dir );
	}
}
