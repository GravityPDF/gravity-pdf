<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Templates;
use GFPDF\Model\Model_Templates;

use Upload\Storage\FileSystem;
use Upload\Exception\UploadException;

use WP_UnitTestCase;
use ZipArchive;

use Exception;

/**
 * Test Gravity PDF Templates Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
	 * @var \GFPDF\Controller\Controller_Templates
	 * @since 4.1
	 */
	public $controller;

	/**
	 * Our Templates Model
	 *
	 * @var \GFPDF\Model\Model_Templates
	 * @since 4.1
	 */
	public $model;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.1
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model      = new Model_Templates( $gfpdf->templates, $gfpdf->log, $gfpdf->data, $gfpdf->misc );
		$this->controller = new Controller_Templates( $this->model );
		$this->controller->init();
	}

	/**
	 * Get a stub we can use for testing
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 *
	 * @since 4.1
	 */
	private function getFileStub() {
		global $gfpdf;

		$storage = new FileSystem( $gfpdf->data->template_tmp_location );

		/* Mock our \Upload\File\isUploadedFile() method */
		$file = $this->getMockBuilder( '\Upload\File' )
					->setConstructorArgs( [ 'template', $storage ] )
		             ->setMethods( [ 'isUploadedFile' ] )
		             ->getMock();

		$file
			->expects( $this->any() )
			->method( 'isUploadedFile' )
			->will( $this->returnValue( true ) );

		return $file;
	}

	/**
	 * Test the appropriate actions are set up
	 *
	 * @since 4.1
	 */
	public function test_actions() {

		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_upload_template', [
			$this->model,
			'ajax_process_uploaded_template',
		] ) );

		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_delete_template', [
			$this->model,
			'ajax_process_delete_template',
		] ) );

		$this->assertEquals( 10, has_action( 'wp_ajax_gfpdf_get_template_options', [
			$this->model,
			'ajax_process_build_template_options_html',
		] ) );
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

		$this->assertNotFalse( strpos( $path, $gfpdf->data->template_tmp_location ) );
		$this->assertNotFalse( strpos( $path, '.zip' ) );

		/* Cleanup */
		@unlink( $test_file );
		@unlink( $path );
	}


	/**
	 * Get if we get the expected results
	 *
	 * @since        4.1
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

		/* Check an error is thrown if trying to unzip a zip file */
		try {
			$this->model->unzip_and_verify_templates( 'test.txt' );
		} catch ( Exception $e ) {
			//do nothing
		}

		$this->assertEquals( 'Incompatible Archive.', $e->getMessage() );
		unset( $e );

		/* Create empty archive and check an exception is thrown for no PDF templates found */
		$test_file = $gfpdf->data->template_tmp_location . 'test-archive.zip';
		$test_dir  = $this->model->get_unzipped_dir_name( $test_file );

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

		$this->assertFalse( isset( $e ) );

		/* Cleanup */
		unlink( $test_file );
		$gfpdf->misc->rmdir( $test_dir );
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

		$this->assertSame( 2, sizeof( $info ) );
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
		$this->cleanup_template_files( substr( $test_dir, 0, -1 ) . '.zip' );

		$this->assertFileNotExists( $test_dir . 'test.txt' );
		$this->assertFileNotExists( $test_dir );
	}
}
