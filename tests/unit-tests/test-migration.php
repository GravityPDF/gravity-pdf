<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Migration;

use GFForms;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Actions functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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
 * Test the Helper_Migration class
 * @since 4.0
 * @group migration
 */
class Test_Migration extends WP_UnitTestCase
{
	/**
	 * Our migration object
	 * @var Object
	 * @since 4.0
	 */
	public $migration;

	/**
	 * The Gravity Form ID assigned to the imported form
	 * @var Integer
	 * @since 4.0
	 */
	public $form_id;

	/**
	 * The WP Unit Test Set up function
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Remove temporary tables which causes problems with GF */
		remove_all_filters( 'query', 10 );
		GFForms::setup_database();

		/* Setup our test classes */
		$this->migration = new Helper_Migration( $gfpdf->form, $gfpdf->log, $gfpdf->data, $gfpdf->options, $gfpdf->misc, $gfpdf->notices );

		/* Get our form ID */
		$this->form_id = $gfpdf->form->add_form( json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/migration_v3_to_v4.json' ) ), true ) );
	}

	/**
	 * Replaces a string in a file
	 *
	 * @param string $FilePath
	 * @param string $OldText text to be replaced
	 * @param string $NewText new text
	 * @return array $Result status (success | error) & message (file exist, file permissions)
	 */
	private function replace_in_file( $FilePath, $OldText, $NewText ) {
		$Result = array( 'status' => 'error', 'message' => '' );
		if ( file_exists( $FilePath ) === true ) {
			if ( is_writeable( $FilePath ) ) {
				try {
					$FileContent = file_get_contents( $FilePath );
					$FileContent = str_replace( $OldText, $NewText, $FileContent );
					if ( file_put_contents( $FilePath, $FileContent ) > 0 ) {
						$Result['status'] = 'success';
					} else {
						$Result['message'] = 'Error while writing file';
					}
				} catch (Exception $e) {
					$Result['message'] = 'Error : '.$e;
				}
			} else {
				$Result['message'] = 'File '.$FilePath.' is not writable !';
			}
		} else {
			$Result['message'] = 'File '.$FilePath.' does not exist !';
		}
		return $Result;
	}

	/**
	 * Check the appropriate error is thrown
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
     * @since 4.0
     */
    public function test_imported_data( $data ) {
        global $gfpdf;

        $configuration_path = ( is_multisite() ) ? $gfpdf->data->multisite_template_location : $gfpdf->data->template_location;

        /* Create our fake config file */
        copy( dirname( __FILE__ ) . '/php/simple_config', $configuration_path . 'configuration.php' );

        /* Fix up form IDs */
        $this->replace_in_file( $configuration_path . 'configuration.php', "'form_id' => 1,", "'form_id' => {$this->form_id},");

        /* Do our import */
        $this->assertTrue( $this->migration->begin_migration() );

        /* Check the results */
        $settings = $gfpdf->options->get_form_pdfs( $this->form_id );
        $settings = array_values( $settings );

        $data = $this->provider_imported_data();
       
        /* remove the ID as we aren't using it in our comparison */
        foreach( $settings as &$setting ) {
            unset( $setting['id'] );
        }

        /* Do our assertions */
        $this->assertSame( 0, sizeof( array_diff( $settings[ 0 ], $data[0]['config'] ) ) );
        $this->assertSame( 0, sizeof( array_diff( $settings[ 1 ], $data[1]['config'] ) ) );
        $this->assertSame( 0, sizeof( array_diff( $settings[ 2 ], $data[2]['config'] ) ) );
        $this->assertSame( 0, sizeof( array_diff( $settings[ 3 ], $data[3]['config'] ) ) );
        $this->assertSame( 0, sizeof( array_diff( $settings[ 4 ], $data[4]['config'] ) ) );
        $this->assertSame( 0, sizeof( array_diff( $settings[ 5 ], $data[5]['config'] ) ) );
        $this->assertSame( 0, sizeof( array_diff( $settings[ 6 ], $data[6]['config'] ) ) );
        $this->assertSame( 0, sizeof( array_diff( $settings[ 7 ], $data[7]['config'] ) ) );

        /* Verify our config file was archived and clean up */
        $this->assertFileExists( $configuration_path . 'configuration.archive.php' );
        unlink( $configuration_path . 'configuration.archive.php' );
    }

    /**
     * Data provider of expected results after an import
     * @return Array
     */
    public function provider_imported_data() {
        
        return array(
            array(
                'id' => 0,
                'config' => array(
                    'template' => 'health-care-directive',
                    'pdf_size' => 'A4',
                    'format' => 'Standard',
                    'notification' => array(
                        '55598a8994685',
                        '555a9083a1cb5',
                    ),
                    'advanced_template' => 'Yes',
                    'active' => true,
                    'name' => 'Health Care Directive',
                    'conditionalLogic' => '',
                    'filename' => 'form-{form_id}-entry-{entry_id}',
                ),
            ),

            array(
                'id' => 1,
                'config' => array(
                    'template' => 'example-template',
                    'name' => 'Example Template',
                    'filename' => 'testman',
                    'notifications' => array(
                        '55598a8994685'
                    ),
                    'public_access' => 'Yes',

                    'pdf_size' => 'A4',
                    'format' => 'Standard',
                    'conditionalLogic' => '',
                    'active' => true,
                ),
            ),

            array(
                'id' => 2,
                'config' => array(
                    'template' => 'default-template-no-style',
                    'name' => 'Default Template No Style',
                    'filename' => 'Double Trouble',
                    'show_html' => 'Yes',
                    'show_empty' => 'Yes',
                    'show_page_names' => 'Yes',
                    'show_section_content' => 'Yes',

                    'pdf_size' => 'A4',
                    'format' => 'Standard',
                    'conditionalLogic' => '',
                    'active' => true,
                ),
            ),

           array(
                'id' => 3,
                'config' => array(
                    'template' => 'default-template',
                    'name' => 'Default Template',
                    'filename' => 'testman',
                    'notification' => array(
                        '55598a8994685',
                        '555a9083a1cb5',
                    ),
                    'security' => 'Yes',
                    'password' => 'myPDFpass',
                    'privileges' => array('copy', 'print', 'modify', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-highres'),
                    'master_password' => 'admin password',

                    'pdf_size' => 'A4',
                    'format' => 'Standard',
                    'conditionalLogic' => '',
                    'active' => true,
                )
            ),

           array(
                'id' => 4,
                'config' => array(
                    'template' => 'default-template',
                    'name' => 'Default Template #1',
                    'filename' => 'testman2',
                    'notification' => array(
                        '55598a8994685',
                    ),
                    'security' => 'Yes',
                    'password' => '',
                    'privileges' => array('copy', 'print', 'extract', 'assemble', 'print-highres'),
                    'master_password' => 'adfawfawr5q2atd',

                    'pdf_size' => 'A4',
                    'format' => 'Standard',
                    'conditionalLogic' => '',
                    'active' => true,
                ),
            ),

           array(
                'id' => 5,
                'config' => array(
                    'template' => 'default-template',
                    'name' => 'Default Template #2',
                    'filename' => 'testman3',

                    'security' => 'Yes',
                    'password' => '',
                    'master_password' => '',

                    'pdf_size' => 'A4',
                    'format' => 'Standard',
                    'conditionalLogic' => '',
                    'active' => true,
                ),
            ),

           array(
                'id' => 6,
                'config' => array(
                    'template' => 'default-template',
                    'name' => 'Default Template #3',
                    'filename' => 'form-{form_id}-entry-{entry_id}',
                    'pdf_size' => 'LETTER',
                    'format' => 'PDFA1B',
                    'orientation' => 'landscape',
                    'rtl' => 'Yes',
                    'image_dpi' => 300,

                    'conditionalLogic' => '',
                    'active' => true,
                )
            ),

           array(
                'id' => 7,
                'config' => array(
                    'template' => 'default-template',
                    'name' => 'Default Template #4',
                    'filename' => 'form-{form_id}-entry-{entry_id}',
                    'notification' => array(
                        '55598a8994685',
                    ),

                    'pdf_size' => 'CUSTOM',
                    'custom_pdf_size' => array(50, 200, 'millimeters'),
                    'format' => 'PDFX1A',
                    
                    'image_dpi' => 300,
                    'save' => 'Yes',

                    'conditionalLogic' => '',
                    'active' => true,
                ),
            )
        );
    }

	/**
	 * Check multisite migration works as expected
	 * @since 4.0
	 */
	public function test_multisite_migration() {
		$this->markTestIncomplete( 'Write unit test' );
	}
}
