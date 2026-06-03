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
class Test_Field_Consent extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'repeater-consent-form' ] );
	}


	public $form;

	public $gf_field;

	public $pdf_field;

	public function set_up(): void {
		parent::set_up();

		$this->form    = $this->form( 'repeater-consent-form' );
		$this->gf_field = new \GF_Field_Consent( $this->field_from_fixture( 'consent', 'repeater-consent-form' ) );

		$entry = [
			'form_id' => $this->form['id'],
		];

		$this->pdf_field = new Field_Consent( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_if_has_consent() {
		$entry = [
			'form_id'                  => $this->form['id'],
			'id'                       => '0',
			$this->gf_field->id . '.1' => '1',
			$this->gf_field->id . '.2' => '',
			$this->gf_field->id . '.3' => '',
		];

		$this->pdf_field = new Field_Consent( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'consent-accepted-label', $this->pdf_field->html() );
	}

	public function test_if_has_not_consent() {
		$entry = [
			'form_id'                  => $this->form['id'],
			'id'                       => '0',
			$this->gf_field->id . '.1' => '0',
			$this->gf_field->id . '.2' => '',
			$this->gf_field->id . '.3' => '',
		];

		$this->pdf_field = new Field_Consent( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'consent-not-accepted-label', $this->pdf_field->html() );
	}

	public function test_if_skip_when_not_consented() {
		$entry = [
			'form_id'                  => $this->form['id'],
			'id'                       => '0',
			$this->gf_field->id . '.1' => '0',
			$this->gf_field->id . '.2' => '',
			$this->gf_field->id . '.3' => '',
		];

		$this->pdf_field = new Field_Consent( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		add_filter( 'gfpdf_hide_consent_field_if_empty', '__return_true' );

		$this->assertSame( '', $this->pdf_field->html() );

		/* Verify it only applies if no consent given */
		$entry[$this->gf_field->id . '.1'] = '1';

		$this->pdf_field = new Field_Consent( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );

		$this->assertStringContainsString( 'consent-accepted-label', $this->pdf_field->html() );
	}
}
