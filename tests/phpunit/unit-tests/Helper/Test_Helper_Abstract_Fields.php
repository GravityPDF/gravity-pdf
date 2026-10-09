<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GF_Field_Text;
use GFPDF\Helper\Fields\Field_Text;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Helper_Abstract_Fields extends WP_UnitTestCase {

	public function test_null_value_raises_no_deprecation() {
		$field = new GF_Field_Text( [ 'id' => 1 ] );
		$entry = [ 'id' => 0, 'form_id' => 0, '1' => '' ];

		add_filter( 'gfpdf_pdf_field_content', '__return_null' );

		$deprecations = [];
		set_error_handler( function ( $errno, $errstr ) use ( &$deprecations ) {
			$deprecations[] = $errstr;

			return true;
		}, E_DEPRECATED );

		try {
			$html = ( new Field_Text( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->html();
		} finally {
			restore_error_handler();
		}

		$this->assertSame( [], $deprecations );
		$this->assertStringContainsString( '<div class="value">&nbsp;</div>', $html );
	}
}
