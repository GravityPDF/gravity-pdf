<?php

declare( strict_types=1 );

namespace GFPDF\Model;
use Exception;
use GFPDF\Controller\Controller_Templates;
use GFPDF\Helper\Fonts\LocalFile;
use GFPDF\Helper\Fonts\LocalFilesystem;
use GFPDF\Helper\Helper_Templates;
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
	 * The upload has to be a zip by extension *and* by contents
	 *
	 * Validation\FileType pairs the two, where Extension and Mimetype side by side checked two
	 * independent allow-lists and so accepted a file whose extension and contents disagreed.
	 *
	 * @since 6.17
	 */
	public function test_move_template_to_tmp_dir_requires_the_contents_to_match_the_extension() {
		global $gfpdf;

		/* A font wearing a .zip extension: the extension is allowed, the contents are not */
		$disguised = $gfpdf->data->template_tmp_location . 'disguised.zip';
		copy( PDF_PLUGIN_DIR . 'tools/phpunit/data/fonts/DejaVuSans.ttf', $disguised );

		$_FILES['template'] = [
			'name'     => 'disguised.zip',
			'tmp_name' => $disguised,
			'error'    => UPLOAD_ERR_OK,
		];

		try {
			$this->model->move_template_to_tmp_dir( $this->getFileStub() );
			$this->fail( 'Expected the disguised font to be refused.' );
		} catch ( UploadException $e ) {
			$this->assertSame( 'File validation failed', $e->getMessage() );
		}

		/* A real zip still passes */
		$real = $this->make_zip( [ 'tmp' => '' ] );

		$_FILES['template']['name']     = basename( $real );
		$_FILES['template']['tmp_name'] = $real;

		$path = $this->model->move_template_to_tmp_dir( $this->getFileStub() );

		$this->assertFileExists( $path );

		/* Cleanup */
		@unlink( $disguised );
		@unlink( $real );
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

	/**
	 * Safari auto-extracts template zips on download. Re-zipping the resulting folder buries the PDF
	 * templates a directory deep, which we need to see through.
	 *
	 * @since 6.17
	 */
	public function test_unzip_and_verify_templates_handles_rezipped_folder() {
		global $gfpdf;

		$gfpdf->templates->flush_template_transient_cache();

		$zip = $this->make_zip(
			[
				'my-template/zadani.php'      => PDF_PLUGIN_DIR . 'src/templates/zadani.php',
				'my-template/images/logo.txt' => 'not a template',
			]
		);

		try {
			$dir = $this->model->unzip_and_verify_templates( $zip );

			$this->assertSame( $this->model->get_unzipped_dir_name( $zip ) . 'my-template/', $dir );
			$this->assertFileExists( $dir . 'zadani.php' );
		} finally {
			@unlink( $zip ); /* phpcs:ignore */
			$gfpdf->misc->rmdir( $this->model->get_unzipped_dir_name( $zip ) );
			$gfpdf->templates->flush_template_transient_cache();
		}
	}

	/**
	 * @param string $expected  Path relative to the extracted directory
	 * @param array  $entries   Zip contents
	 *
	 * @since        6.17
	 *
	 * @dataProvider provider_get_template_root_dir
	 */
	public function test_get_template_root_dir( string $expected, array $entries ) {
		global $gfpdf;

		$zip = $this->make_zip( $entries );

		add_filter( 'filesystem_method', $direct = fn() => 'direct' );
		WP_Filesystem();

		try {
			$dir = $this->model->get_unzipped_dir_name( $zip );
			unzip_file( $zip, $dir );

			$this->assertSame( $dir . $expected, $gfpdf->templates->get_template_root_dir( $dir ) );
		} finally {
			remove_filter( 'filesystem_method', $direct );
			@unlink( $zip ); /* phpcs:ignore */
			$gfpdf->misc->rmdir( $this->model->get_unzipped_dir_name( $zip ) );
		}
	}

	/**
	 * @return array
	 *
	 * @since 6.17
	 */
	public function provider_get_template_root_dir(): array {
		$zadani = PDF_PLUGIN_DIR . 'src/templates/zadani.php';

		return [
			'templates at the top level'      => [
				'',
				[ 'zadani.php' => $zadani ],
			],

			'wrapped in a single folder'      => [
				'my-template/',
				[ 'my-template/zadani.php' => $zadani ],
			],

			/* A Finder-compressed folder — unzip_file() drops the root __MACOSX, leaving one wrapper */
			'macOS-compressed folder'         => [
				'my-template/',
				[
					'my-template/zadani.php'            => $zadani,
					'__MACOSX/my-template/._zadani.php' => 'apple double',
				],
			],

			'wrapped twice'                   => [
				'outer/inner/',
				[ 'outer/inner/zadani.php' => $zadani ],
			],

			'hidden folders are skipped'      => [
				'my-template/',
				[
					'my-template/zadani.php' => $zadani,
					'.git/HEAD'              => 'ref: refs/heads/main',
				],
			],

			'ambiguous, so left alone'        => [
				'',
				[
					'one/zadani.php' => $zadani,
					'two/rubix.php'  => PDF_PLUGIN_DIR . 'src/templates/rubix.php',
				],
			],

			'assets only, so left alone'      => [
				'images/',
				[ 'images/logo.txt' => 'not a template' ],
			],
		];
	}

	/**
	 * @since 6.17
	 */
	public function test_get_max_upload_size() {
		$this->assertSame( 32 * MB_IN_BYTES, Helper_Templates::MAX_UPLOAD_SIZE );

		/* Never offer to accept more than the server itself will */
		add_filter( 'upload_size_limit', $tiny = fn() => MB_IN_BYTES );
		$this->assertSame( MB_IN_BYTES, Helper_Templates::get_max_upload_size() );
		remove_filter( 'upload_size_limit', $tiny );

		add_filter( 'upload_size_limit', $huge = fn() => 512 * MB_IN_BYTES );
		$this->assertSame( 32 * MB_IN_BYTES, Helper_Templates::get_max_upload_size() );
		remove_filter( 'upload_size_limit', $huge );

		add_filter( 'gfpdf_template_max_upload_size', $override = fn() => 5 * MB_IN_BYTES );
		$this->assertSame( 5 * MB_IN_BYTES, Helper_Templates::get_max_upload_size() );
		remove_filter( 'gfpdf_template_max_upload_size', $override );
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
