<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF_Core_Model;
use GPDFAPI;
use PDF_Common;
use PDFRender;

/**
 * Test Gravity PDF deprecated classes / methods / functions
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * Test all deprecated functionality
 *
 * @since 4.0
 * @group deprecated
 */
class Test_Deprecated extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

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
	public function provider_deprecated(): array {
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

		$this->assertSame( $gfpdf->data->template_tmp_location, PDF_SAVE_LOCATION );
		$this->assertSame( $gfpdf->data->template_font_location, PDF_FONT_LOCATION );

		if ( is_multisite() ) {
			$this->assertSame( $gfpdf->data->multisite_template_location, PDF_TEMPLATE_LOCATION );
			$this->assertSame( $gfpdf->data->multisite_template_location_url, PDF_TEMPLATE_URL_LOCATION );
		} else {
			$this->assertSame( $gfpdf->data->template_location, PDF_TEMPLATE_LOCATION );
			$this->assertSame( $gfpdf->data->template_location_url, PDF_TEMPLATE_URL_LOCATION );
		}
	}

	/**
	 * Test the PDFRender::savePDF() method
	 *
	 * @since 4.0
	 */
	public function test_render_save_pdf() {
		global $gfpdf;

		$this->setExpectedDeprecated( 'PDFRender::savePDF' );

		$render = new PDFRender();
		$render->savePDF( 'testing', 'mydocument.pdf', 20 );

		$this->assertFileExists( $gfpdf->data->template_tmp_location . '20/mydocument.pdf' );
		$this->assertSame( 'testing', file_get_contents( $gfpdf->data->template_tmp_location . '20/mydocument.pdf' ) );

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

		$this->assertSame( 'fid', $form_id );
		$this->assertSame( 'lead IDs', $GLOBALS['lead_ids'] );
	}

	/**
	 * Test PDF_Common::get_ids()
	 *
	 * @since 4.0
	 */
	public function test_common_get_ids() {
		$this->setExpectedDeprecated( 'PDF_Common::get_ids' );

		$GLOBALS['form_id'] = '20';
		$_GET['lid']        = '20,21,23';

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
		$this->setExpectedDeprecated( 'PDF_Common::get_pdf_filename' );

		$this->assertSame( 'form-50-entry-2091.pdf', PDF_Common::get_pdf_filename( 50, 2091 ) );
	}

	/**
	 * Verify our deprecated GFPDF_Core_Model::gfpdfe_save_pdf() method
	 * works as expected.
	 *
	 * @group slow
	 */
	public function test_deprecated_save_pdf() {
		global $gfpdf;

		$this->setExpectedDeprecated( 'GFPDF_Core_Model::gfpdfe_save_pdf' );

		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite saves the PDF under a path the prefix glob does not match (known gfpdfe_save_pdf network-site behaviour).' );
		}

		$form_class = GPDFAPI::get_form_class();

		$results = $this->form_and_entry();
		$entry   = $results['entry'];
		$form    = $form_class->get_form( $results['form']['id'] );

		$filename = "test-{$form['id']}.pdf";

		GFPDF_Core_Model::gfpdfe_save_pdf( $entry, $form );

		/* Hash inputs (filtered form/entry/settings) differ between generation and re-read, so match by prefix. */
		$cache_root = $gfpdf->data->template_tmp_location . 'cache/';
		foreach ( GPDFAPI::get_entry_pdfs( $entry['id'] ) as $pdf ) {
			if ( ! in_array( $pdf['template'], [ 'zadani', 'focus-gravity', 'rubix', 'blank-slate' ], true ) ) {
				continue;
			}

			$prefix  = sprintf( '%ss%d-f%d-e%d-p%s-', $cache_root, get_current_blog_id(), $form['id'], $entry['id'], $pdf['id'] );
			$matches = glob( $prefix . '*/' . $filename ) ?: [];

			$this->assertNotEmpty( $matches, "Expected a saved PDF under {$prefix}*/{$filename}" );

			foreach ( $matches as $match ) {
				unlink( $match );
			}
		}
	}
}
