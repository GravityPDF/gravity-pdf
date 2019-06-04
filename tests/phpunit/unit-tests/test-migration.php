<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Migration;

use GFForms;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Actions functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the Helper_Migration class
 *
 * @since 4.0
 * @group migration
 */
class Test_Migration extends WP_UnitTestCase {
	/**
	 * Our migration object
	 *
	 * @var \GFPDF\Helper\Helper_Migration
	 *
	 * @since 4.0
	 */
	public $migration;

	/**
	 * The Gravity Form ID assigned to the imported form
	 *
	 * @var integer
	 *
	 * @since 4.0
	 */
	public $form_id;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->migration = new Helper_Migration( $gfpdf->gform, $gfpdf->log, $gfpdf->data, $gfpdf->options, $gfpdf->misc, $gfpdf->notices, $gfpdf->templates );

		/* Get our form ID */
		$this->form_id[] = $gfpdf->gform->add_form( json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/migration_v3_to_v4.json' ) ), true ) );
		$this->form_id[] = $gfpdf->gform->add_form( json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/migration_v3_to_v4.json' ) ), true ) );
	}

	/**
	 * Replaces a string in a file
	 *
	 * @param string $path
	 * @param string $old_text text to be replaced
	 * @param string $new_text new text
	 *
	 * @return array $Result status (success | error) & message (file exist, file permissions)
	 *
	 * @since 4.0
	 */
	private function replace_in_file( $path, $old_text, $new_text ) {
		$result = [
			'status'  => 'error',
			'message' => '',
		];
		if ( file_exists( $path ) === true ) {
			if ( is_writeable( $path ) ) {
				try {
					$contents = file_get_contents( $path );
					$contents = str_replace( $old_text, $new_text, $contents );
					if ( file_put_contents( $path, $contents ) > 0 ) {
						$result['status'] = 'success';
					} else {
						$result['message'] = 'Error while writing file';
					}
				} catch ( Exception $e ) {
					$result['message'] = 'Error : ' . $e;
				}
			} else {
				$result['message'] = 'File ' . $path . ' is not writable !';
			}
		} else {
			$result['message'] = 'File ' . $path . ' does not exist !';
		}

		return $result;
	}

	/**
	 * Check the appropriate error is thrown
	 *
	 * @since 4.0
	 */
	public function test_config_loading_error() {
		global $gfpdf;

		$gfpdf->notices->clear();
		$this->assertFalse( $this->migration->begin_migration() );
		$this->assertTrue( $gfpdf->notices->has_error() );
	}

	/**
	 * Check our config was imported into the database correctly
	 *
	 * @since 4.0
	 */
	public function test_imported_data() {
		global $gfpdf;

		$main_form_id      = $this->form_id[0];
		$secondary_form_id = $this->form_id[1];

		$configuration_path = ( is_multisite() ) ? $gfpdf->data->multisite_template_location : $gfpdf->data->template_location;

		/* Create our fake config file */
		copy( dirname( __FILE__ ) . '/php/simple_config', $configuration_path . 'configuration.php' );

		/* Fix up form IDs */
		$this->replace_in_file( $configuration_path . 'configuration.php', "'form_id' => 1,", "'form_id' => {$main_form_id}," );

		/* Do our import */
		$this->assertTrue( $this->migration->begin_migration() );

		/* Check the results */
		$settings = $gfpdf->options->get_form_pdfs( $main_form_id );
		$settings = array_values( $settings );

		$data = $this->provider_imported_data();

		/* remove the ID as we aren't using it in our comparison */
		foreach ( $settings as &$setting ) {
			unset( $setting['id'] );
		}

		/* Do our assertions */
		$this->assertSame( 0, strcmp( json_encode( $settings[0] ), json_encode( $data[0]['config'] ) ) );
		$this->assertSame( 0, strcmp( json_encode( $settings[1] ), json_encode( $data[1]['config'] ) ) );
		$this->assertSame( 0, strcmp( json_encode( $settings[2] ), json_encode( $data[2]['config'] ) ) );
		$this->assertSame( 0, strcmp( json_encode( $settings[3] ), json_encode( $data[3]['config'] ) ) );
		$this->assertSame( 0, strcmp( json_encode( $settings[4] ), json_encode( $data[4]['config'] ) ) );
		$this->assertSame( 0, strcmp( json_encode( $settings[5] ), json_encode( $data[5]['config'] ) ) );
		$this->assertSame( 0, strcmp( json_encode( $settings[6] ), json_encode( $data[6]['config'] ) ) );
		$this->assertSame( 0, strcmp( json_encode( $settings[7] ), json_encode( $data[7]['config'] ) ) );

		/* Check our default config was imported into our second form */
		$settings = $gfpdf->options->get_form_pdfs( $secondary_form_id );
		$settings = array_values( $settings );

		$this->assertEquals( 'default-template', $settings[0]['template'] );
		$this->assertEquals( 'A4', $settings[0]['pdf_size'] );
		$this->assertEquals( 'form-{form_id}-entry-{entry_id}', $settings[0]['filename'] );

		/* Verify our config file was archived and clean up */
		$this->assertFileExists( $configuration_path . 'configuration.archive.php' );
		unlink( $configuration_path . 'configuration.archive.php' );
	}

	/**
	 * The config data used while testing the import
	 *
	 * @return array
	 */
	public function provider_imported_data() {

		return [
			[
				'config' => [
					'template'          => 'health-care-directive',
					'pdf_size'          => 'A4',
					'format'            => 'Standard',
					'notification'      => [
						'5578e163413b3',
					],
					'advanced_template' => 'Yes',
					'active'            => true,
					'name'              => 'Health Care Directive',
					'conditionalLogic'  => '',
					'filename'          => 'form-{form_id}-entry-{entry_id}',
				],
			],

			[
				'config' => [
					'template'         => 'example-template',
					'pdf_size'         => 'A4',
					'format'           => 'Standard',
					'filename'         => 'testman',
					'notification'     => [
						'5578e163413b3',
					],
					'public_access'    => 'Yes',
					'active'           => true,
					'name'             => 'Example Template',
					'conditionalLogic' => '',
				],
			],

			[
				'config' => [
					'template'             => 'default-template-no-style',
					'pdf_size'             => 'A4',
					'format'               => 'Standard',
					'filename'             => 'Double Trouble',
					'show_html'            => 'Yes',
					'show_empty'           => 'Yes',
					'show_page_names'      => 'Yes',
					'show_section_content' => 'Yes',
					'active'               => true,
					'name'                 => 'Default Template No Style',
					'conditionalLogic'     => '',
				],
			],

			[
				'config' => [
					'template'         => 'default-template',
					'pdf_size'         => 'A4',
					'format'           => 'Standard',
					'filename'         => 'testman',
					'security'         => 'Yes',
					'notification'     => [
						'5578e163413b3',
					],
					'password'         => 'myPDFpass',
					'privileges'       => [
						'copy',
						'print',
						'modify',
						'annot-forms',
						'fill-forms',
						'extract',
						'assemble',
						'print-highres',
					],
					'master_password'  => 'admin password',
					'active'           => true,
					'name'             => 'Default Template',
					'conditionalLogic' => '',
				],
			],

			[
				'config' => [
					'template'         => 'default-template',
					'pdf_size'         => 'A4',
					'format'           => 'Standard',
					'filename'         => 'testman2',
					'security'         => 'Yes',
					'notification'     => [
						'5578e163413b3',
					],
					'password'         => '',
					'privileges'       => [ 'copy', 'print', 'extract', 'assemble', 'print-highres' ],
					'master_password'  => 'adfawfawr5q2atd',
					'active'           => true,
					'name'             => 'Default Template #1',
					'conditionalLogic' => '',
				],
			],

			[
				'config' => [
					'template'         => 'default-template',
					'pdf_size'         => 'A4',
					'format'           => 'Standard',
					'filename'         => 'testman3',
					'security'         => 'Yes',
					'password'         => '',
					'master_password'  => '',
					'active'           => true,
					'name'             => 'Default Template #2',
					'conditionalLogic' => '',
				],
			],

			[
				'config' => [
					'template'         => 'default-template',
					'pdf_size'         => 'LETTER',
					'format'           => 'PDFA1B',
					'orientation'      => 'landscape',
					'rtl'              => 'Yes',
					'image_dpi'        => 300,
					'active'           => true,
					'name'             => 'Default Template #3',
					'conditionalLogic' => '',
					'filename'         => 'form-{form_id}-entry-{entry_id}',
				],
			],

			[
				'config' => [
					'template'         => 'default-template',
					'pdf_size'         => 'CUSTOM',
					'format'           => 'PDFX1A',
					'save'             => 'Yes',
					'custom_pdf_size'  => [ 50, 200, 'millimeters' ],
					'notification'     => [
						'5578e163413b3',
					],
					'image_dpi'        => 300,
					'active'           => true,
					'name'             => 'Default Template #4',
					'conditionalLogic' => '',
					'filename'         => 'form-{form_id}-entry-{entry_id}',
				],
			],
		];
	}

	/**
	 * Check that the output directory is cleaned up correctly during a migration
	 *
	 * @since 4.0
	 */
	public function test_cleanup_output_directory() {
		global $gfpdf;

		/* Create a config so we can do a migration */
		$configuration_path = ( is_multisite() ) ? $gfpdf->data->multisite_template_location : $gfpdf->data->template_location;
		wp_mkdir_p( $configuration_path );
		touch( $configuration_path . 'configuration.php' );

		/* Setup an output directory and fill it with files */
		mkdir( $configuration_path . 'output' );
		mkdir( $configuration_path . 'output/123/' );
		touch( $configuration_path . 'output/file' );
		touch( $configuration_path . 'output/123/file' );

		/* Verify the output folder exists */
		$this->assertTrue( is_dir( $configuration_path . 'output' ) );

		/* Run the migration */
		$this->assertTrue( $this->migration->begin_migration() );

		/* Verify our output folder no longer exists */
		$this->assertFalse( is_dir( $configuration_path . 'output' ) );
		$this->assertTrue( is_dir( $gfpdf->data->template_location ) );

		/* Verify our config file was archived and clean up */
		$this->assertFileExists( $configuration_path . 'configuration.archive.php' );
		unlink( $configuration_path . 'configuration.archive.php' );
	}
}
