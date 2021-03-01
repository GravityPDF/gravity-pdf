<?php

namespace GFPDF\Tests;

use PDF_Common;
use PDFRender;
use WP_UnitTestCase;

/**
 * Test Gravity PDF deprecated classes / methods / functions
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * Test all deprecated functionality
 *
 * @since 4.0
 * @group deprecated
 */
class Test_Deprecated extends WP_UnitTestCase {
	/**
	 * Ensure all deprecated classes have appropriate fallbacks
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_deprecated
	 */
	public function test_deprecated( $class ) {
		$this->assertTrue( class_exists( $class ) );
	}

	/**
	 * Test we have appropriate deprecated classes frm our v3 version
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function provider_deprecated() {
		return [
			[ 'GFPDF_Core' ],
			[ 'PDFGenerator' ],
			[ 'GFPDF_Settings' ],
			[ 'GFPDF_Core_Model' ],
			[ 'GFPDF_Settings_Model' ],
			[ 'GFPDFE_DATA' ],
			[ 'GFPDF_InstallUpdater' ],
			[ 'GFPDF_Notices' ],
			[ 'PDF_Common' ],
			[ 'GFPDFEntryDetail' ],
			[ 'PDF_Generator' ],
		];
	}

	/**
	 * Check our v3 constants have been defined
	 *
	 * @since 4.0
	 */
	public function test_constants() {
		global $gfpdf;

		$this->assertTrue( defined( 'PDF_SAVE_LOCATION' ) );
		$this->assertTrue( defined( 'PDF_FONT_LOCATION' ) );
		$this->assertTrue( defined( 'PDF_TEMPLATE_LOCATION' ) );
		$this->assertTrue( defined( 'PDF_TEMPLATE_URL_LOCATION' ) );

		$this->assertEquals( $gfpdf->data->template_tmp_location, PDF_SAVE_LOCATION );
		$this->assertEquals( $gfpdf->data->template_font_location, PDF_FONT_LOCATION );

		if ( is_multisite() ) {
			$this->assertEquals( $gfpdf->data->multisite_template_location, PDF_TEMPLATE_LOCATION );
			$this->assertEquals( $gfpdf->data->multisite_template_location_url, PDF_TEMPLATE_URL_LOCATION );
		} else {
			$this->assertEquals( $gfpdf->data->template_location, PDF_TEMPLATE_LOCATION );
			$this->assertEquals( $gfpdf->data->template_location_url, PDF_TEMPLATE_URL_LOCATION );
		}
	}

	/**
	 * Test the PDFRender::savePDF() method
	 *
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
	 *
	 * @since 4.0
	 */
	public function test_render_prepare_ids() {
		unset( $GLOBALS['lead_ids'] );

		$render  = new PDFRender();
		$form_id = $render->prepare_ids( 'fid', '', '', '', '', '', [], [ 'lead_ids' => 'lead IDs' ] );

		$this->assertEquals( 'fid', $form_id );
		$this->assertEquals( 'lead IDs', $GLOBALS['lead_ids'] );
	}

	/**
	 * Test PDF_Common::get_ids()
	 *
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
	 *
	 * @since 4.0
	 */
	public function test_common_get_pdf_filename() {
		$this->assertEquals( 'form-50-entry-2091.pdf', PDF_Common::get_pdf_filename( 50, 2091 ) );
	}
}
