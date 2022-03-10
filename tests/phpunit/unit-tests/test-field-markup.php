<?php

namespace GFPDF\Tests;

use GF_Field_Consent;
use GF_Field_Repeater;
use GFAPI;
use GFPDF\Helper\Fields\Field_Consent;
use GFPDF\Helper\Fields\Field_Repeater;
use GFPDF\Helper\Helper_QueryPath;
use GPDFAPI;
use WP_UnitTestCase;

/**
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.1
 */

/**
 * @since 5.1
 * @group field-markup
 */
class Test_Field_Markup extends WP_UnitTestCase {

	/**
	 * Verify the HTML Mark-up generated by the Repeater field
	 */
	public function test_repeater_field_markup() {
		$form  = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/repeater-consent-form.json' ) ), true );
		$entry = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/repeater-consent-entry.json' ) ), true );

		$form_id          = GFAPI::add_form( $form );
		$entry['form_id'] = $form_id;
		$entry_id         = GFAPI::add_entry( $entry );

		$repeater = new GF_Field_Repeater( $form['fields'][1] );

		$field = new Field_Repeater( $repeater, GFAPI::get_entry( $entry_id ), GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );

		$qp   = new Helper_QueryPath();
		$html = $qp->html5( $field->html() );

		$this->assertSame( 2, $html->find( '.gfpdf-repeater' )->count() );
		$this->assertSame( 4, $html->find( '.repeater-container' )->count() );
		$this->assertSame( 17, $html->find( '.gfpdf-field' )->count() );

		$this->assertEquals( 'Simon Wiseman', $html->find( '.gfpdf-name .value' )->get( 0 )->nodeValue );
		$this->assertEquals( 'Geoff Simpson', $html->find( '.gfpdf-name .value' )->get( 1 )->nodeValue );
	}

	public function test_consent_field() {
		$form  = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/repeater-consent-form.json' ) ), true );
		$entry = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/repeater-consent-entry.json' ) ), true );

		$form_id          = GFAPI::add_form( $form );
		$entry['form_id'] = $form_id;
		$entry_id         = GFAPI::add_entry( $entry );

		$consent = new GF_Field_Consent( $form['fields'][0] );

		$field = new Field_Consent( $consent, GFAPI::get_entry( $entry_id ), GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );

		$qp   = new Helper_QueryPath();
		$html = $qp->html5( $field->html() );

		$this->assertSame( 1, $html->find( '.consent-accepted' )->count() );
		$this->assertSame( 1, $html->find( '.consent-accepted-label' )->count() );
		$this->assertSame( 1, $html->find( '.consent-text' )->count() );
	}
}
