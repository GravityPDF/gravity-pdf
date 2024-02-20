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
class Test_Field_Post_Category extends WP_UnitTestCase {

	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var \GF_Field_Post_Category
	 */
	public $gf_field;

	/**
	 * @var Field_Post_Category
	 */
	public $pdf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'post_category' ) {
				$this->gf_field = $field;
				break;
			}
		}

		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$this->pdf_field = new Field_Poll( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_html() {
		$html = $this->pdf_field->html();

		$this->assertStringContainsString( '<div id="field-31" class="gfpdf-field gfpdf-select ">', $html );
		$this->assertStringContainsString( '<div class="label"><strong>Post Category</strong></div><div class="value">Test Category 2:30</div>', $html );
	}
}
