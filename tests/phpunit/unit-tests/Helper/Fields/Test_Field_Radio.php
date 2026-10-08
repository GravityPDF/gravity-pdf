<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_FlushCache
 *
 * @package GFPDF\Helper\Fonts
 *
 * @group   helper
 * @group   fields
 */
class Test_Field_Radio extends WP_UnitTestCase {

	public $form;

	public $gf_field;

	public $pdf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'radio' ) {
				$this->gf_field = new \GF_Field_Radio( $field );
				break;
			}
		}

		$entry = [
			'form_id' => $this->form['id'],
		];

		$this->pdf_field = new Field_Radio( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_is_empty() {
		$this->assertTrue( $this->pdf_field->is_empty() );

		add_filter( 'gfpdf_field_is_empty_value_instead_of_label', '__return_false' );

		$this->assertFalse( $this->pdf_field->is_empty() );

		remove_filter( 'gfpdf_field_is_empty_value_instead_of_label', '__return_false' );
	}

	public function test_value_with_empty_value() {
		$value = $this->pdf_field->value();

		$this->assertEmpty( $value['value'] );
		$this->assertNotEmpty( $value['label'] );
	}

	public function test_form_data_with_empty_value() {
		$form_data = $this->pdf_field->form_data();

		$this->assertSame( '', $form_data['field'][ $this->gf_field->id ] );
		$this->assertSame( 'Radio Fourth Choice Label', $form_data['field'][ $this->gf_field->id . '_name' ] );
	}

	public function test_merge_tags_only_processed_in_choices() {
		$form_id = ( new \GF_UnitTest_Factory() )->form->create( [], [ 'title' => 'Radio Tags', 'fields' => [ new \GF_Field_Radio( [
			'id'      => 1,
			'choices' => [ [ 'text' => 'Choice {form_id}', 'value' => 'a' ] ],
		] ) ] ] );

		$value = function ( $posted ) use ( $form_id ) {
			$entry = [ 'id' => 0, 'form_id' => $form_id, '1' => $posted ];

			return ( new Field_Radio( \GFAPI::get_form( $form_id )['fields'][0], $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value()['label'];
		};

		$this->assertSame( "Choice $form_id", $value( 'a' ) );
		$this->assertSame( 'Posted {form_id}', $value( 'Posted {form_id}' ) );
	}

	public function test_other_choice_value_is_not_processed() {
		$form_id = ( new \GF_UnitTest_Factory() )->form->create( [], [ 'title' => 'Radio Other', 'fields' => [ new \GF_Field_Radio( [
			'id'                => 1,
			'enableOtherChoice' => true,
			'choices'           => [ [ 'text' => 'A', 'value' => 'a' ] ],
		] ) ] ] );

		$entry = [ 'id' => 0, 'form_id' => $form_id, '1' => '<b>{form_id}</b>' ];
		$value = ( new Field_Radio( \GFAPI::get_form( $form_id )['fields'][0], $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) )->value();

		$this->assertSame( '&lt;b&gt;{form_id}&lt;/b&gt;', $value['label'] );
	}
}
