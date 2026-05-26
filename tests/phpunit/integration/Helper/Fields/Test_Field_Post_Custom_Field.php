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
class Test_Field_Post_Custom_Field extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var \GF_Field_Post_Custom_Field
	 */
	public $gf_field;

	/**
	 * @var Field_Post_Category
	 */
	public $pdf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $this->form( 'all-form-fields' );

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'post_custom_field' ) {
				$this->gf_field = $field;
				break;
			}
		}

		$entry = $this->entry( 'all-form-fields' );

		$this->pdf_field = new Field_Poll( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_html() {
		$html = $this->pdf_field->html();

		$this->assertStringContainsString( '<div id="field-33" class="gfpdf-field gfpdf-text ', $html );
		$this->assertStringContainsString( '<div class="label"><strong>Post Custom Field</strong></div><div class="value">post_custom_field</div>', $html );
	}
}
