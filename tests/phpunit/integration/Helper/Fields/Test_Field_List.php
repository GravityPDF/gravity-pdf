<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_List;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_List extends TestCase {

	public function test_value_single_column() {
		$field = new GF_Field_List( [
			'id' => 1,
		] );

		$entry = [
			'form_id' => 0,
			'1'       => serialize( [ '', 'Row 2', '', 'Row 3' ] ),
		];

		$pdf_field = new Field_List( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->value();
		$this->assertCount( 2, $value );
		$this->assertSame( 'Row 2', $value[0] );
		$this->assertSame( 'Row 3', $value[1] );
	}

	public function test_value_multi_column() {
		$field = new GF_Field_List( [
			'id'      => 1,
			'choices' => [
				[ 'text' => 'Column 1' ],
				[ 'text' => 'Column 2' ],
			],
		] );

		$entry = [
			'form_id' => 0,
			'1'       => serialize( [
				[ 'Column 1' => '', 'Column 2' => '' ],
				[ 'Column 1' => 'Row 2 a', 'Column 2' => 'Row 2 b' ],
				[ 'Column 1' => 'Row 3 a', 'Column 2' => 'Row 3 b' ],
				[ 'Column 1' => '', 'Column 2' => '' ],
			] ),
		];

		$pdf_field = new Field_List( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$value = $pdf_field->value();

		$this->assertCount( 2, $value );
		$this->assertSame( 'Row 2 a', $value[0]['Column 1'] );
		$this->assertSame( 'Row 2 b', $value[0]['Column 2'] );
		$this->assertSame( 'Row 3 a', $value[1]['Column 1'] );
		$this->assertSame( 'Row 3 b', $value[1]['Column 2'] );
	}
}
