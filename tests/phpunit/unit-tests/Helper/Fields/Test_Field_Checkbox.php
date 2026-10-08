<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Checkbox;
use GF_UnitTest_Factory;
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
class Test_Field_Checkbox extends WP_UnitTestCase {

	public function test_merge_tags_only_processed_in_choices() {
		$form_id = ( new GF_UnitTest_Factory() )->form->create( [], [ 'title' => 'Checkbox Tags', 'fields' => [ new GF_Field_Checkbox( [
			'id'      => 1,
			'choices' => [ [ 'text' => 'Choice {form_id}', 'value' => 'a' ] ],
			'inputs'  => [ [ 'id' => '1.1' ], [ 'id' => '1.2' ] ],
		] ) ] ] );

		$entry = [ 'id' => 0, 'form_id' => $form_id, '1.1' => 'a', '1.2' => 'Posted {form_id}' ];
		$items = ( new Field_Checkbox( \GFAPI::get_form( $form_id )['fields'][0], $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();

		$this->assertSame( [ "Choice $form_id", 'Posted {form_id}' ], array_column( $items, 'label' ) );
	}

	public function test_choice_with_html_still_processed_as_gravity_forms_saved_it() {
		$form_id = ( new GF_UnitTest_Factory() )->form->create( [], [ 'title' => 'Checkbox Html', 'fields' => [ new GF_Field_Checkbox( [
			'id'      => 1,
			'choices' => [ [ 'text' => 'A', 'value' => '<b>a</b> & co' ] ],
			'inputs'  => [ [ 'id' => '1.1' ] ],
		] ) ] ] );

		/* Gravity Forms saves the choice's value sanitised */
		$entry = [ 'id' => 0, 'form_id' => $form_id, '1.1' => '<b>a</b> &amp; co' ];
		$items = ( new Field_Checkbox( \GFAPI::get_form( $form_id )['fields'][0], $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();

		/* Its HTML is kept */
		$this->assertSame( '<b>a</b> &amp; co', $items[0]['value'] );
	}
}
