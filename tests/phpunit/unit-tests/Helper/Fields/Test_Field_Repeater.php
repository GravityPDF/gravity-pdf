<?php
declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use WP_UnitTestCase;
use GFAPI;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Field_Repeater
 *
 *
 * @group   helper
 * @group   fields
 */
class Test_Field_Repeater extends WP_UnitTestCase {

	public $form;

	public $entry;

	public $pdf_field;

	public function set_up() {
		parent::set_up();

		$this->form  = $GLOBALS['GFPDF_Test']->form['repeater-consent-form'];
		$this->entry = $GLOBALS['GFPDF_Test']->entries['repeater-consent-form'][0];

		$form_id                = GFAPI::add_form( $this->form );
		$this->entry['form_id'] = $form_id;
		$entry_id               = GFAPI::add_entry( $this->entry );
		$this->pdf_field        = new Field_Repeater( new \GF_Field_Repeater( $this->form['fields'][1] ), GFAPI::get_entry( $entry_id ), GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );

	}

	/**
	 * Test the Gravity PDF Repeater field add_entry_meta method with an invalid/valid $entry structure.
	 */
	public function test_repeater_add_entry_meta_method() {
		$items = $this->pdf_field->add_entry_meta( [ 999 => 1, 55 => 2, 'currency' => 'GBP', 'created_by' => 3 ] );

		$this->assertSame( 3, $items['created_by'] );
		$this->assertSame( 'GBP', $items['currency'] );

		/* Check if undefined key exists. */
		$this->assertArrayHasKey( 'currency', $items );
		$this->assertArrayHasKey( 'transaction_type', $items );
		$this->assertArrayHasKey( 'created_by', $items );

		$values = array_values( $items );

		$this->assertSame( '7', $values[1] ); // Should be 7 and a string.
		$this->assertSame( 1, $values[20] ); // These test expects integer results.
		$this->assertSame( 2, $values[21] );

		/* Load items directly from a sample repeater field*/
		$items = $this->pdf_field->add_entry_meta( $this->entry[999][0] ); // This should return an valid entry array.
		$this->assertSame( '13', $items['id'] );
		$this->assertSame( '7', $items['form_id'] );
		$this->assertSame( '1', $items['created_by'] );
		$this->assertSame( 'USD', $items['currency'] );

		/* Check if undefined key exists. */
		$this->assertArrayHasKey( 'currency', $items );
		$this->assertArrayHasKey( 'transaction_type', $items );
		$this->assertArrayHasKey( 'created_by', $items );

		$values = array_values( $items );

		$this->assertSame( '13', $values[0] ); // Should be 14 and a string.
		$this->assertSame( '7', $values[1] ); // Should be 8 and a string.
		$this->assertSame( '172.17.0.1', $values[7] ); // These test expects integer results.
		$this->assertSame( 'USD', $values[10] );
	}

	public function test_get_repeater_form_data() {
		/* This method expects the third parameter as an GF_Field_Repeater Instance. */
		$data          = $this->pdf_field->get_repeater_form_data( $this->pdf_field->form_data(), $this->pdf_field->value(), new \GF_Field_Repeater( $this->form['fields'][1] ) );
		$repeater_data = $data['repeater']['999.Team'][0];

		/* Verify that method correctly adds the HTML field with each corresponding indexes. */
		$this->assertArrayHasKey( 17, $repeater_data );
		$this->assertArrayHasKey( 'HTML', $repeater_data );
		$this->assertArrayHasKey( 'HTML.17', $repeater_data );

		/* Check if the content is what we expected. */

		$this->assertSame(  'This a sample HTML' , $repeater_data[17] );
		$this->assertSame(  'This a sample HTML' , $repeater_data['HTML.17'] );
		$this->assertSame(  'This a sample HTML' , $repeater_data['HTML'] );
	}

	public function test_unique_ids() {
		$html = $this->pdf_field->html();

		$this->assertStringNotContainsString( 'id="field-999"', $html );
		$this->assertStringContainsString( 'id="repeater-999-field-999-0"', $html );
		$this->assertStringContainsString( 'id="repeater-999-field-999-11"', $html );

		$this->assertStringNotContainsString( 'id="field-15"', $html );
		$this->assertStringContainsString( 'id="repeater-999-field-15-1"', $html );
		$this->assertStringContainsString( 'id="repeater-999-field-15-12"', $html );
	}
}
