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
class Test_Field_Post_Image extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}


	/**
	 * @var array
	 */
	public $form;

	/**
	 * @var \GF_Field_Post_Image
	 */
	public $gf_field;

	public function set_up() {
		parent::set_up();

		$this->form = $this->form( 'all-form-fields' );

		foreach ( $this->form['fields'] as $field ) {
			if ( $field->type === 'post_image' ) {
				$this->gf_field = $field;
				break;
			}
		}
	}

	public function test_html_with_windows_drive_path() {
		$entry = $this->entry( 'all-form-fields' );
		$field = new class( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) extends Field_Post_Image {
			public function value() {
				return [
					'url' => 'http://example.org/image.jpg',
					'path' => 'C:\My Documents\Images\image.jpg',
				];
			}
		};

		$html = str_replace( "\n", '', $field->html() );

		$this->assertStringContainsString( '<a href="http://example.org/image.jpg" target="_blank"><img width="150" src="c:\My Documents\Images\image.jpg" /></a>', $html );
	}

	public function test_html_with_windows_unc_path() {
		$entry = $this->entry( 'all-form-fields' );
		$field = new class( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) extends Field_Post_Image {
			public function value() {
				return [
					'url' => 'http://example.org/image.jpg',
					'path' => '\\My Documents\Images\image.jpg',
				];
			}
		};

		$html = str_replace( "\n", '', $field->html() );

		$this->assertStringContainsString( '<a href="http://example.org/image.jpg" target="_blank"><img width="150" src="\\My Documents\Images\image.jpg" /></a>', $html );

		/* UNC path pointed to named drive on the network */
		$field = new class( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) extends Field_Post_Image {
			public function value() {
				return [
					'url' => 'http://example.org/image.jpg',
					'path' => '\\system07\C$\My Documents\Images\image.jpg',
				];
			}
		};

		$html = str_replace( "\n", '', $field->html() );

		$this->assertStringContainsString( '<a href="http://example.org/image.jpg" target="_blank"><img width="150" src="\\system07\C$\My Documents\Images\image.jpg" /></a>', $html );
	}

	public function test_html_with_linux_path() {
		$entry = $this->entry( 'all-form-fields' );
		$field = new class( $this->gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() ) extends Field_Post_Image {
			public function value() {
				return [
					'url' => 'http://example.org/image.jpg',
					'path' => '/var/www/html/image.jpg',
				];
			}
		};

		$html = str_replace( "\n", '', $field->html() );

		$this->assertStringContainsString( '<a href="http://example.org/image.jpg" target="_blank"><img width="150" src="/var/www/html/image.jpg" /></a>', $html );
	}
}
