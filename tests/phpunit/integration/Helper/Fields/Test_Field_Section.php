<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

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
class Test_Field_Section extends TestCase {

	public $form;

	public $gf_field;

	public $pdf_field;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}

	public function set_up(): void {
		parent::set_up();

		$this->form    = $this->form( 'all-form-fields' );
		$this->gf_field = new \GF_Field_Section( $this->field_from_fixture( 'section' ) );

		$entry = [
			'form_id' => $this->form['id'],
		];

		$this->pdf_field = new Field_Section( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_exclude_description_markup_if_empty() {
		$this->gf_field->description = 'Contents';
		$field                       = new Field_Section( $this->gf_field, [ 'form_id' => $this->form['id'] ], \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'gfpdf-section-description', $field->html( true ) );

		$this->gf_field->description = '';
		$field                       = new Field_Section( $this->gf_field, [ 'form_id' => $this->form['id'] ], \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringNotContainsString( 'gfpdf-section-description', $field->html( true ) );
	}
}
