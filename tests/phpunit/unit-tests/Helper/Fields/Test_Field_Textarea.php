<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Textarea;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Textarea extends WP_UnitTestCase {

	public function test_rich_text_html() {
		$field = new GF_Field_Textarea( [
			'id' => 1,
			'useRichTextEditor' => true,
		] );

		$entry = [
			'id' => 0,
			'form_id' => 0,
			'1'       => '<div class="a b c d e f g h i j k l m n o p q r s t u v w x y z">Hi <ul id="list"><li>Item 1</li><li class="1 2 3 4 5 6 7 8 9 10 11 12 13">Item 2</li><li>Item 3</li></ul></div><p class="a b c">My paragraph</p>',
		];

		$pdf_field = new Field_Textarea( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->html();
		$this->assertStringContainsString('<div class="a b c d e f g h">Hi <ul id="list"><li>Item 1</li><li class="1 2 3 4 5 6 7 8">Item 2</li><li>Item 3</li></ul></div><p class="a b c">My paragraph</p>', str_replace( [ "\n", "\t" ], '', $value ) );
	}
}