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
class Test_Field_Signature extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var \GF_Field_Signature
	 */
	public $gf_field;

	public function set_up(): void {
		parent::set_up();

		$this->gf_field = $this->field_from_fixture( 'signature' );
	}

	public function test_html_with_windows_drive_path() {
		$entry = $this->entry( 'all-form-fields' );
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
		$entry = $this->entry( 'all-form-fields' );
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
		$entry = $this->entry( 'all-form-fields' );
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
