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
class Test_Field_Signature extends WP_UnitTestCase {

	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var \GF_Field_Signature
	 */
	public $gf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'signature' ) {
				$this->gf_field = $field;
				break;
			}
		}
	}

	public function test_html_with_windows_drive_path() {
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$field = new class( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) extends Field_Signature {
			public function value() {
				return [
					'img' => '<img width="150" src="c:\My Documents\Images\image.jpg" />',
				];
			}

			public function is_empty() {
				return false;
			}
		};

		$html = str_replace( "\n", '', $field->html() );

		$this->assertStringContainsString( '<img width="150" src="c:\My Documents\Images\image.jpg" />', $html );
	}

	public function test_html_with_windows_unc_path() {
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$field = new class( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) extends Field_Signature {
			public function value() {
				return [
					'img' => '<img width="150" src="\\My Documents\Images\image.jpg" />',
				];
			}

			public function is_empty() {
				return false;
			}
		};

		$html = str_replace( "\n", '', $field->html() );

		$this->assertStringContainsString( '<img width="150" src="\\My Documents\Images\image.jpg" />', $html );

		/* UNC path pointed to named drive on the network */
		$field = new class( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) extends Field_Signature {
			public function value() {
				return [
					'img' => '<img width="150" src="\\system07\C$\My Documents\Images\image.jpg" />',
				];
			}

			public function is_empty() {
				return false;
			}
		};

		$html = str_replace( "\n", '', $field->html() );

		$this->assertStringContainsString( '<img width="150" src="\\system07\C$\My Documents\Images\image.jpg" />', $html );
	}

	public function test_html_with_linux_path() {
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$field = new class( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) extends Field_Signature {
			public function value() {
				return [
					'img' => '<img width="150" src="/var/www/html/image.jpg" />',
				];
			}

			public function is_empty() {
				return false;
			}
		};

		$html = str_replace( "\n", '', $field->html() );

		$this->assertStringContainsString( '<img width="150" src="/var/www/html/image.jpg" />', $html );
	}
}