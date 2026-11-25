<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 */
class Test_Helper_Form extends WP_UnitTestCase {

	public function test_process_tags_keeps_a_tag_a_merged_value_adds_as_text() {
		$form       = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry      = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$entry['1'] = '{admin_email} {Email:17} {:17}';

		$text = \GPDFAPI::get_form_class()->process_tags( 'Name: {Single Line Text:1} Form: {form_title}', $form, $entry );

		$this->assertStringContainsString( 'Form: ' . $form['title'], $text );
		$this->assertSame( $text, \GFCommon::replace_variables( $text, $form, $entry, false, false, false ) );
	}

	public function test_process_tags_leaves_a_merged_value_as_is_for_plain_text() {
		$form       = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry      = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$entry['1'] = 'Report {Draft}: final';

		$this->assertSame( 'Name: Report {Draft}: final', \GPDFAPI::get_form_class()->process_tags( 'Name: {Single Line Text:1}', $form, $entry, false ) );
	}
}
