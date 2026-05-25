<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Date;
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
class Test_Field_Date extends TestCase {

	public function test_value_formats_date_per_field_dateformat() {
		/*
		 * The all-form-fields fixture uses dateFormat 'dmy' for field 12.
		 * GF stores dates as Y-m-d; GFCommon::date_display() converts them.
		 */
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'date' ) {
				$gf_field = new GF_Field_Date( $field );
				break;
			}
		}

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'12'      => '2015-01-01',
		];

		$pdf_field = new Field_Date( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		/* dmy format yields "01/01/2015" — day and month precede the year, unlike the raw "2015-01-01" */
		$this->assertSame( '01/01/2015', $value );
	}

	public function test_html_contains_formatted_date() {
		$form     = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$gf_field = null;
		foreach ( $form['fields'] as $field ) {
			if ( $field->type === 'date' ) {
				$gf_field = new GF_Field_Date( $field );
				break;
			}
		}

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'12'      => '2015-01-01',
		];

		$pdf_field = new Field_Date( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( '2015', $html );
	}

	public function test_is_empty_when_no_date_stored() {
		$gf_field  = new GF_Field_Date( [ 'id' => 12, 'dateFormat' => 'mdy' ] );
		$entry     = [ 'id' => 0, 'form_id' => 0 ];
		$pdf_field = new Field_Date( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}
}
