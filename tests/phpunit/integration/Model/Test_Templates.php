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

		$this->assertSame( 10, has_filter( 'gfpdf_localised_script_array', [ $this->controller, 'add_localised_script_data' ] ) );
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

		/* Nor is installing plugins (only a super admin can on multisite, and they have every capability) */
		if ( ! is_multisite() ) {
			$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
			get_userdata( $user_id )->add_cap( 'install_plugins' );
			wp_set_current_user( $user_id );

			$this->assertFalse( $this->model->current_user_can_manage_templates() );
		}

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		if ( is_multisite() ) {
			$this->assertFalse( $this->model->current_user_can_manage_templates() );
			grant_super_admin( $user_id );
		}

		$this->assertTrue( $this->model->current_user_can_manage_templates() );
		$this->assertTrue( $this->controller->add_localised_script_data( [] )['canManageTemplates'] );

		add_filter( 'gfpdf_current_user_can_manage_templates', '__return_false' );
		$this->assertFalse( $this->model->current_user_can_manage_templates() );
		remove_filter( 'gfpdf_current_user_can_manage_templates', '__return_false' );

		add_filter( 'file_mod_allowed', '__return_false' );
		$this->assertFalse( $this->model->current_user_can_manage_templates() );

		add_filter( 'gfpdf_current_user_can_manage_templates', '__return_true' );
		$this->assertTrue( $this->model->current_user_can_manage_templates() );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_get_manage_templates_notice() {
		add_filter( 'file_mod_allowed', '__return_false' );
		$this->assertSame( '', $this->model->get_manage_templates_notice() );

		/* An editor with Gravity Forms settings access was never allowed, so file mods aren't the reason */
		$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		get_userdata( $user_id )->add_cap( 'gravityforms_edit_settings' );
		wp_set_current_user( $user_id );
		$this->assertSame( '', $this->model->get_manage_templates_notice() );

		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$this->assertStringContainsString( 'disabled on this server', $this->model->get_manage_templates_notice() );
		$this->assertStringContainsString( 'disabled on this server', $this->controller->add_localised_script_data( [] )['templateManageNotice'] );

		remove_filter( 'file_mod_allowed', '__return_false' );
		if ( is_multisite() ) {
			$this->assertStringContainsString( 'network administrator', $this->model->get_manage_templates_notice() );
			grant_super_admin( $user_id );
		}

		$this->assertSame( '', $this->model->get_manage_templates_notice() );

		/* Nothing to explain once the filter grants access */
		add_filter( 'file_mod_allowed', '__return_false' );
		add_filter( 'gfpdf_current_user_can_manage_templates', '__return_true' );
		$this->assertSame( '', $this->model->get_manage_templates_notice() );
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

		/* A font wearing a .zip extension: the extension is allowed, the contents are not */
		$test_file = $gfpdf->data->template_location . 'disguised.zip';
		copy( PDF_PLUGIN_DIR . 'tools/phpunit/data/fonts/DejaVuSans.ttf', $test_file );

		$_FILES['template']['name']     = 'disguised.zip';
		$_FILES['template']['tmp_name'] = $test_file;

		try {
			$this->model->move_template_to_tmp_dir( $this->getFileStub() );
			$this->fail( 'Expected the disguised font to be refused.' );
		} catch ( UploadException $e ) {
			$this->assertSame( 'File validation failed', $e->getMessage() );
		} finally {
			unlink( $test_file );
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
			$dir = $this->model->get_unzipped_dir_name( $zip ) . 'my-template/';

			$this->assertSame(
				[
					'dir'       => $dir,
					'templates' => [ $dir . 'zadani.php' ],
				],
				$this->model->unzip_and_verify_templates( $zip )
			);
		} finally {
			@unlink( $zip ); /* phpcs:ignore */
			$gfpdf->misc->rmdir( $this->model->get_unzipped_dir_name( $zip ) );
			$gfpdf->templates->flush_template_transient_cache();
		}
	}

	/**
	 * @param string[] $expected Template paths relative to the extracted directory
	 * @param array    $entries  Zip contents
	 *
	 * @since        6.18.0
	 *
	 * @dataProvider provider_get_templates_in_extracted_zip
	 */
	public function test_get_templates_in_extracted_zip( array $expected, array $entries ) {
		global $gfpdf;

		$zip = $this->make_zip( $entries );

		add_filter( 'filesystem_method', $direct = fn() => 'direct' );
		WP_Filesystem();

		try {
			$dir = $this->model->get_unzipped_dir_name( $zip );
			unzip_file( $zip, $dir );

			$templates = $gfpdf->templates->get_templates_in_extracted_zip( $dir )['templates'];
			sort( $templates );

			$this->assertSame( array_map( fn( $path ) => $dir . $path, $expected ), $templates );
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
	public function provider_get_templates_in_extracted_zip(): array {
		$zadani = PDF_PLUGIN_DIR . 'src/templates/zadani.php';
		$rubix  = PDF_PLUGIN_DIR . 'src/templates/rubix.php';

		return [
			'templates at the top level' => [
				[ 'rubix.php', 'zadani.php' ],
				[
					'zadani.php'      => $zadani,
					'rubix.php'       => $rubix,
					'images/logo.txt' => 'not a template',
				],
			],

			'wrapped in a single folder' => [
				[ 'my-template/zadani.php' ],
				[ 'my-template/zadani.php' => $zadani ],
			],

			/* A Finder-compressed folder — unzip_file() drops the root __MACOSX, leaving one wrapper */
			'macOS-compressed folder'    => [
				[ 'my-template/zadani.php' ],
				[
					'my-template/zadani.php'            => $zadani,
					'__MACOSX/my-template/._zadani.php' => 'apple double',
				],
			],

			'wrapped three times'        => [
				[ 'a/b/c/zadani.php' ],
				[ 'a/b/c/zadani.php' => $zadani ],
			],

			'wrapped too deep'           => [
				[],
				[ 'a/b/c/d/zadani.php' => $zadani ],
			],

			'hidden folders are skipped' => [
				[ 'my-template/zadani.php' ],
				[
					'my-template/zadani.php' => $zadani,
					'.git/HEAD'              => 'ref: refs/heads/main',
				],
			],

			'ambiguous'                  => [
				[],
				[
					'one/zadani.php' => $zadani,
					'two/rubix.php'  => $rubix,
				],
			],

			'assets only'                => [
				[],
				[ 'images/logo.txt' => 'not a template' ],
			],
		];
	}

	/**
	 * @since 6.18.0
	 */
	public function test_get_max_upload_size() {
		$this->assertLessThanOrEqual( Helper_Templates::MAX_UPLOAD_SIZE, Helper_Templates::get_max_upload_size() );

		add_filter( 'gfpdf_template_max_upload_size', fn() => 5 * MB_IN_BYTES );
		$this->assertSame( 5 * MB_IN_BYTES, Helper_Templates::get_max_upload_size() );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_get_max_upload_size_ignores_network_media_limit() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		/* Network Settings → Max upload file size, in KB */
		update_site_option( 'fileupload_maxk', 1 );
		$this->assertSame( KB_IN_BYTES, wp_max_upload_size() );

		$this->assertGreaterThan( KB_IN_BYTES, Helper_Templates::get_max_upload_size() );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_move_template_to_tmp_dir_size_limit() {
		/* Random hex compresses to about half, so the zip is well over 1 KB (raw bytes can hold a NUL, which is_file() rejects) */
		$test_file          = $this->make_zip( [ 'padding' => bin2hex( random_bytes( 10 * KB_IN_BYTES ) ) ] );
		$_FILES['template'] = [
			'name'     => 'sized.zip',
			'tmp_name' => $test_file,
			'error'    => UPLOAD_ERR_OK,
		];

		/* The media upload limit doesn't apply */
		add_filter( 'upload_size_limit', fn() => KB_IN_BYTES );
		$path = $this->model->move_template_to_tmp_dir( $this->getFileStub() );
		$this->assertFileExists( $path );
		unlink( $path );

		/* The template one does */
		add_filter( 'gfpdf_template_max_upload_size', fn() => KB_IN_BYTES );
		$file = $this->getFileStub();
		try {
			$this->model->move_template_to_tmp_dir( $file );
			$this->fail( 'Expected the oversized zip to be refused.' );
		} catch ( UploadException $e ) {
			$this->assertStringContainsString( 'File size is too large', implode( "\n", $file->getErrors() ) );
		} finally {
			unlink( $test_file );
		}
	}

	/** Build a zip at a unique tmp path; each entry maps an archive name to a file path or raw contents */
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

	public function test_check_for_valid_pdf_templates_requires_developer_toolkit() {
		global $gfpdf;

		$template = $gfpdf->data->template_tmp_location . 'toolkit-template.php';
		file_put_contents( $template, "<?php\n/*\n * Template Name: Toolkit Template\n * Group: Toolkit\n * Toolkit: true\n */\n" );

		try {
			$this->model->check_for_valid_pdf_templates( [ $template ] );
			$this->fail( 'Expected the Toolkit template to be rejected' );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'requires the Gravity PDF Developer Toolkit', $e->getMessage() );
		}

		$add_toolkit_header = fn( $headers ) => $headers + [ 'toolkit' => 'Toolkit' ];

		add_filter( 'gfpdf_template_header_details', $add_toolkit_header );
		try {
			$this->model->check_for_valid_pdf_templates( [ $template ] );
		} finally {
			remove_filter( 'gfpdf_template_header_details', $add_toolkit_header );
			unlink( $template );
		}
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
	 * @since 6.18.0
	 */
	public function test_delete_template_skips_add_on_config_tear_down() {
		global $gfpdf;

		$template_id = 'phpunit-addon-teardown';
		$addon       = $gfpdf->data->template_location . 'addon/';
		$marker      = $addon . 'torn-down';

		wp_mkdir_p( $addon . 'config' );
		file_put_contents(
			$addon . 'config/' . $template_id . '.php',
			"<?php\nnamespace GFPDF\Templates\Config;\nclass Phpunit_Addon_Teardown implements \GFPDF\Helper\Helper_Interface_Setup_TearDown {\n\tpublic function setUp() {}\n\tpublic function tearDown() { touch( '$marker' ); }\n}\n"
		);
		touch( $gfpdf->data->template_location . $template_id . '.php' );

		add_filter(
			'gfpdf_template_config_paths',
			function ( $paths ) use ( $addon ) {
				$paths[] = $addon . 'config/';

				return $paths;
			}
		);

		try {
			$this->model->delete_template( $template_id );

			$this->assertFileDoesNotExist( $gfpdf->data->template_location . $template_id . '.php' );
			$this->assertFileExists( $addon . 'config/' . $template_id . '.php' );
			$this->assertFileDoesNotExist( $marker );
		} finally {
			$gfpdf->misc->rmdir( $addon );
			@unlink( $gfpdf->data->template_location . $template_id . '.php' ); //phpcs:ignore
		}
	}

	/**
	 * @since 6.18.0
	 */
	public function test_delete_template_never_unlinks_outside_the_working_folder() {
		global $gfpdf;

		$outside = get_temp_dir() . 'phpunit-outside-working-folder.php';
		touch( $outside );

		$templates = $this->getMockBuilder( Helper_Templates::class )
			->setConstructorArgs( [ $gfpdf->log, $gfpdf->data, $gfpdf->gform ] )
			->onlyMethods( [ 'get_template_files_by_id' ] )
			->getMock();
		$templates->method( 'get_template_files_by_id' )->willReturn( [ $outside ] );

		try {
			( new Model_Templates( $templates, $gfpdf->log, $gfpdf->data, $gfpdf->misc ) )->delete_template( 'outside' );

			$this->assertFileExists( $outside );
		} finally {
			@unlink( $outside ); //phpcs:ignore
		}
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
