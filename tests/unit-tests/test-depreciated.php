<?php

namespace GFPDF\Tests;

use GFPDF_Core;
use PDFRender;
use PDF_Common;

use WP_UnitTestCase;

/**
 * Test Gravity PDF depreciated classes / methods / functions
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
 * Test all depreciated functionality
 * @since 4.0
 * @group depreciated
 */
class Test_Depreciated extends WP_UnitTestCase
{
    /**
     * Ensure all depreciated classes have appropriate fallbacks
     * @since 4.0
     * @dataProvider provider_depreciated
     */
    public function test_depreciated( $class ) {
        $this->assertTrue( class_exists( $class ) );
    }

    /**
     * Test we have appropriate depreciated classes frm our v3 version
     * @return array
     * @since 4.0
     */
    public function provider_depreciated() {
        return array(
            array( 'GFPDF_Core' ),
            array( 'PDFGenerator' ),
            array( 'GFPDF_Settings' ),
            array( 'GFPDF_Core_Model' ),
            array( 'GFPDF_Settings_Model' ),
            array( 'GFPDFE_DATA' ),
            array( 'GFPDF_InstallUpdater' ),
            array( 'GFPDF_Notices' ),
            array( 'PDF_Common' ),
            array( 'GFPDFEntryDetail' ),
            array( 'PDF_Generator' ),
        );
    }

    /**
     * Check our global $gfpdf variable gets setup correctly
     * @since 4.0
     */
    public function test_setup() {

        /* Backup our class object and remove */
        $backup = $GLOBALS['gfpdf'];
        unset( $GLOBALS['gfpdf'] );

        /* Test it was removed */
        $this->assertNull( $GLOBALS['gfpdf'] );

        /* Verify out object is set up correctly */
         new GFPDF_Core();

        $this->assertNotNull( $GLOBALS['gfpdf'] );
        $this->assertEquals( 'GFPDF\Router', get_class( $GLOBALS['gfpdf'] ) );

        /* Reset the object */
        unset( $GLOBALS['gfpdf'] );
        $GLOBALS['gfpdf'] = $backup;
    }

    /**
     * Check our v3 constants have been defined
     * @since 4.0
     */
    public function test_constants() {
        global $gfpdf;

        $this->assertTrue( defined( 'PDF_SAVE_LOCATION') );
        $this->assertTrue( defined( 'PDF_FONT_LOCATION') );
        $this->assertTrue( defined( 'PDF_TEMPLATE_LOCATION') );
        $this->assertTrue( defined( 'PDF_TEMPLATE_URL_LOCATION') );

        $this->assertEquals( $gfpdf->data->template_tmp_location, PDF_SAVE_LOCATION );
        $this->assertEquals( $gfpdf->data->template_font_location, PDF_FONT_LOCATION );

        if( is_multisite() ) {
            $this->assertEquals( $gfpdf->data->multisite_template_location, PDF_TEMPLATE_LOCATION );
            $this->assertEquals( $gfpdf->data->multisite_template_location_url, PDF_TEMPLATE_URL_LOCATION );
        } else {
            $this->assertEquals( $gfpdf->data->template_location, PDF_TEMPLATE_LOCATION );
            $this->assertEquals( $gfpdf->data->template_location_url, PDF_TEMPLATE_URL_LOCATION );
        }
    }

    /**
     * Test the PDFRender::savePDF() method
     * @since 4.0
     */
    public function test_render_save_pdf() {
        global $gfpdf;

        $render = new PDFRender();
        $render->savePDF( 'testing', 'mydocument.pdf', 20 );

        $this->assertFileExists( $gfpdf->data->template_tmp_location . '20/mydocument.pdf' );
        $this->assertEquals( 'testing', file_get_contents( $gfpdf->data->template_tmp_location . '20/mydocument.pdf' ) );

        /* cleanup directory */
        $gfpdf->misc->rmdir( $gfpdf->data->template_tmp_location . '20' );

    }

    /**
     * Test the PDFRender::prepare_ids function
     * @since 4.0
     */
    public function test_render_prepare_ids() {
        unset( $GLOBALS['lead_ids'] );

        $render = new PDFRender();
        $form_id = $render->prepare_ids( 'fid', '', '', '', '', '', '', array( 'lead_ids' => 'lead IDs') );

        $this->assertEquals( 'fid', $form_id );
        $this->assertEquals( 'lead IDs', $GLOBALS['lead_ids'] );
    }

    /**
     * Test PDF_Common::get_ids()
     * @since 4.0
     */
    public function test_common_get_ids() {
        $GLOBALS['form_id']  = '20';
        $GLOBALS['lead_ids'] = '20,21,23';

        $this->assertTrue( PDF_Common::get_ids() );

        unset( $GLOBALS['form_id'] );
        $this->assertFalse( PDF_Common::get_ids() );
    }

    /**
     * Test PDF_Common::get_pdf_filename()
     * @since 4.0
     */
    public function test_common_get_pdf_filename() {
        $this->assertEquals( 'form-50-entry-2091.pdf', PDF_Common::get_pdf_filename( 50, 2091 ) );
    }
}
