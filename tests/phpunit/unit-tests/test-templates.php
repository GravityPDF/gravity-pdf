<?php

namespace GFPDF\Tests;

use Exception;
use GFPDF\Controller\Controller_Templates;
use GFPDF\Helper\Fonts\LocalFile;
use GFPDF\Helper\Fonts\LocalFilesystem;
use GFPDF\Helper\Helper_Templates;
use GFPDF\Model\Model_Templates;
use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use WP_UnitTestCase;
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
class Test_Templates extends WP_UnitTestCase {

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
	public function set_up() {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

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

		$this->assertEquals(
			10,
			has_action(
				'wp_ajax_gfpdf_upload_template',
				[
					$this->model,
					'ajax_process_uploaded_template',
				]
			)
		);

		$this->assertEquals(
			10,
			has_action(
				'wp_ajax_gfpdf_delete_template',
				[
					$this->model,
					'ajax_process_delete_template',
				]
			)
		);

		$this->assertEquals(
			10,
			has_action(
				'wp_ajax_gfpdf_get_template_options',
				[
					$this->model,
					'ajax_process_build_template_options_html',
				]
			)
		);

		$this->assertEquals( 10, has_filter( 'gfpdf_localised_script_array', [ $this->controller, 'add_localised_script_data' ] ) );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_current_user_can_manage_templates() {
		$this->assertFalse( $this->model->current_user_can_manage_templates() );

		/* Gravity Forms settings access alone is not enough */
		$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		get_userdata( $user_id )->add_cap( 'gravityforms_edit_settings' );
		wp_set_current_user( $user_id );

		$this->assertFalse( $this->model->current_user_can_manage_templates() );
		$this->assertFalse( $this->controller->add_localised_script_data( [] )['canManageTemplates'] );

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		if ( is_multisite() ) {
			$this->assertFalse( $this->model->current_user_can_manage_templates() );
			grant_super_admin( $user_id );
		}

		$this->assertTrue( $this->model->current_user_can_manage_templates() );
		$this->assertTrue( $this->controller->add_localised_script_data( [] )['canManageTemplates'] );

		add_filter( 'file_mod_allowed', '__return_false' );
		$this->assertFalse( $this->model->current_user_can_manage_templates() );

		add_filter( 'gfpdf_current_user_can_manage_templates', '__return_true' );
		$this->assertTrue( $this->model->current_user_can_manage_templates() );
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
		} catch ( UploadException $e ) {
			//do nothing
		}

		unlink( $test_file );

		$this->assertEquals( 'File validation failed', $e->getMessage() );

		/* A font wearing a .zip extension: the extension is allowed, the contents are not */
		$test_file = $gfpdf->data->template_location . 'disguised.zip';
		copy( __DIR__ . '/fonts/DejaVuSans.ttf', $test_file );

		$_FILES['template']['name']     = 'disguised.zip';
		$_FILES['template']['tmp_name'] = $test_file;

		try {
			$this->model->move_template_to_tmp_dir( $this->getFileStub() );
			unlink( $test_file );
			$this->fail( 'Expected the disguised font to be refused.' );
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
		$this->assertEquals( $expected, $this->model->get_unzipped_dir_name( $zip_path ) );
	}

	/**
	 * Data Provider for test_get_unzipped_dir_name()
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function provider_get_unzipped_dir_name() {
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

		/* uniqid() prevents collisions with state leaked from earlier runs */
		$test_file = $gfpdf->data->template_tmp_location . 'test-archive-' . uniqid() . '.zip';
		$test_dir  = $this->model->get_unzipped_dir_name( $test_file );

		/* A cached "Legacy" group for the unzipped path would short-circuit the
		   v4 header check and falsely throw "not a valid PDF Template" */
		$gfpdf->templates->flush_template_transient_cache();

		try {
			/* Check an error is thrown if trying to unzip a zip file */
			try {
				$this->model->unzip_and_verify_templates( 'test.txt' );
			} catch ( Exception $e ) {
				//do nothing
			}

			$this->assertEquals( 'Incompatible Archive.', $e->getMessage() );
			unset( $e );

			/* Create empty archive and check an exception is thrown for no PDF templates found */
			$zip = new ZipArchive();
			$zip->open( $test_file, ZipArchive::CREATE );
			$zip->addFromString( 'tmp', '' );
			$zip->close();

			try {
				$this->model->unzip_and_verify_templates( $test_file );
			} catch ( Exception $e ) {
				//do nothing
			}

			$this->assertEquals( 'No valid PDF template found in Zip archive.', $e->getMessage() );
			unset( $e );

			unlink( $test_file );
			$gfpdf->misc->rmdir( $test_dir );

			/* Zip up two of the core PDF template files and check no exceptions are thrown */
			$zip = new ZipArchive();
			$zip->open( $test_file, ZipArchive::CREATE );
			$zip->addFile( PDF_PLUGIN_DIR . 'src/templates/zadani.php', 'zadani.php' );
			$zip->addFile( PDF_PLUGIN_DIR . 'src/templates/rubix.php', 'rubix.php' );
			$zip->close();

			try {
				$this->model->unzip_and_verify_templates( $test_file );
			} catch ( Exception $e ) {
				//do nothing
			}

			$this->assertFalse(
				isset( $e ),
				'Expected no exception when unzipping a valid template archive, got: ' . ( isset( $e ) ? $e->getMessage() : '' )
			);

			/* Add an invalid filename to the zip and verify an error occurs */
			$zip->open( $test_file );
			$zip->addFile( PDF_PLUGIN_DIR . 'src/templates/zadani.php', 'zad@!@#$%^&*().php' );
			$zip->close();

			try {
				$this->model->unzip_and_verify_templates( $test_file );
			} catch ( Exception $e ) {
				//do nothing
			}

			$this->assertStringContainsString( 'contains invalid characters.', $e->getMessage() );
		} finally {
			if ( file_exists( $test_file ) ) {
				unlink( $test_file );
			}
			$gfpdf->misc->rmdir( $test_dir );
			$gfpdf->templates->flush_template_transient_cache();
		}
	}

	/**
	 * Re-zipping a folder Safari auto-extracted nests the templates a directory deep
	 *
	 * @since 6.18.0
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
	 * @since        6.18.0
	 *
	 * @dataProvider provider_get_template_root_dir
	 */
	public function test_get_template_root_dir( string $expected, array $entries ) {
		global $gfpdf;

		$zip = $this->make_zip( $entries );

		$direct = function () {
			return 'direct';
		};

		add_filter( 'filesystem_method', $direct );
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
	 * @since 6.18.0
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
	 * @since 6.18.0
	 */
	public function test_get_max_upload_size() {
		$limit = function ( $bytes ) {
			return function () use ( $bytes ) {
				return $bytes;
			};
		};

		/* Never offer to accept more than the server itself will */
		add_filter( 'upload_size_limit', $tiny = $limit( MB_IN_BYTES ) );
		$this->assertSame( MB_IN_BYTES, Helper_Templates::get_max_upload_size() );
		remove_filter( 'upload_size_limit', $tiny );

		add_filter( 'upload_size_limit', $huge = $limit( 512 * MB_IN_BYTES ) );
		$this->assertSame( 32 * MB_IN_BYTES, Helper_Templates::get_max_upload_size() );
		remove_filter( 'upload_size_limit', $huge );

		add_filter( 'gfpdf_template_max_upload_size', $override = $limit( 5 * MB_IN_BYTES ) );
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
		$this->assertEquals( 'Zadani', $info[0]['template'] );
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
