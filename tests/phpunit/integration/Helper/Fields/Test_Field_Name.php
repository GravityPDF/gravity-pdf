<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_Name;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Name extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}


	private function make_field(): GF_Field_Name {
		return new GF_Field_Name( $this->field_from_fixture( 'name' ) );
	}

	public function test_html_renders_full_name_from_subfields() {
		$gf_field = $this->make_field();
		$form     = $this->form( 'all-form-fields' );

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'11.2'    => 'Mr.',
			'11.3'    => 'Jake',
			'11.6'    => 'Jackson',
		];

		$pdf_field = new Field_Name( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'Mr.', $html );
		$this->assertStringContainsString( 'Jake', $html );
		$this->assertStringContainsString( 'Jackson', $html );
	}

	public function test_value_returns_keyed_name_parts() {
		$gf_field = $this->make_field();
		$form     = $this->form( 'all-form-fields' );

		$entry = [
			'id'      => 0,
			'form_id' => $form['id'],
			'11.3'    => 'Jane',
			'11.4'    => 'M',
			'11.6'    => 'Doe',
			'11.8'    => 'Jr.',
		];

		$pdf_field = new Field_Name( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
		$value     = $pdf_field->value();

		$this->assertIsArray( $value );
		$this->assertSame( 'Jane', $value['first'] );
		$this->assertSame( 'Doe', $value['last'] );
		$this->assertSame( 'Jr.', $value['suffix'] );
	}

	public function test_is_empty_when_all_subfields_blank() {
		$gf_field  = $this->make_field();
		$form      = $this->form( 'all-form-fields' );
		$entry     = [ 'id' => 0, 'form_id' => $form['id'] ];
		$pdf_field = new Field_Name( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertTrue( $pdf_field->is_empty() );
	}
}
