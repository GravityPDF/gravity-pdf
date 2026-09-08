<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The v3 path constants and the `$gfpdfe_data` global third-party PDF templates rely on
 *
 * These are supported API, but only inside a PDF render — `Helper_PDF::__construct()` is what defines them. There is
 * no test for their absence beforehand: PHPUnit shares one process, so any earlier test that built a PDF generator
 * has already defined them.
 *
 * @group  statics
 * @since  7.0
 */
class Test_Template_Constants extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'gravityform-1' ], [ 'gravityform-1' ] );
	}

	/**
	 * Building a PDF generator is what defines the constants, and each has to resolve to the path a template expects
	 *
	 * @since 7.0
	 */
	public function test_a_pdf_generator_defines_the_template_constants() {
		$gfpdf = $this->gfpdf();

		$this->pdf_generator();

		$this->assertTrue( defined( 'PDF_SAVE_LOCATION' ) );
		$this->assertTrue( defined( 'PDF_FONT_LOCATION' ) );
		$this->assertTrue( defined( 'PDF_TEMPLATE_LOCATION' ) );
		$this->assertTrue( defined( 'PDF_TEMPLATE_URL_LOCATION' ) );

		$this->assertSame( $gfpdf->data->template_tmp_location, PDF_SAVE_LOCATION );
		$this->assertSame( $gfpdf->data->template_font_location, PDF_FONT_LOCATION );
		$this->assertSame( $gfpdf->templates->get_template_path(), PDF_TEMPLATE_LOCATION );
		$this->assertSame( $gfpdf->templates->get_template_url(), PDF_TEMPLATE_URL_LOCATION );

		/* `$gfpdfe_data` aliases the data class rather than copying it */
		$this->assertSame( $gfpdf->data, $GLOBALS['gfpdfe_data'] );
	}

	/**
	 * A second render must change nothing, or a template that mutated `$gfpdfe_data` would lose its changes
	 *
	 * @since 7.0
	 */
	public function test_the_constants_are_only_defined_once() {
		$gfpdf = $this->gfpdf();

		$this->pdf_generator();

		/* Hand the second call a data class nothing else holds, so the guard failing is visible and harmless */
		$throwaway                         = new Helper_Data();
		$throwaway->template_tmp_location  = 'tmp/';
		$throwaway->template_font_location = 'fonts/';

		Template_Constants::maybe_define( $throwaway, $gfpdf->templates );

		$this->assertSame( $gfpdf->data, $GLOBALS['gfpdfe_data'] );
	}

	/**
	 * Build the PDF generator the three render paths build, which is the call site under test
	 *
	 * @since 7.0
	 */
	protected function pdf_generator(): Helper_PDF {
		$gfpdf = $this->gfpdf();

		return new Helper_PDF(
			$this->entry( 'gravityform-1' ),
			[ 'template' => 'zadani' ],
			$gfpdf->gform,
			$gfpdf->data,
			$gfpdf->misc,
			$gfpdf->templates,
			$gfpdf->log
		);
	}
}
