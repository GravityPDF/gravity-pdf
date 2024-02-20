<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Consent extends WP_UnitTestCase {

	public $form;

	public $gf_field;

	public $pdf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $GLOBALS['GFPDF_Test']->form['repeater-consent-form'];

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'consent' ) {
				$this->gf_field = new \GF_Field_Consent( $field );
				break;
			}
		}

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
