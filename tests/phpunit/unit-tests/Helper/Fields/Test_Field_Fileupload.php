<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_FileUpload;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Fileupload extends WP_UnitTestCase {

	private function pdf_field( bool $multiple, $stored ): Field_Fileupload {
		$field = new GF_Field_FileUpload(
			[
				'id'            => 1,
				'label'         => 'File',
				'multipleFiles' => $multiple,
			]
		);

		$entry = [
			'id'      => 0,
			'form_id' => 0,
			'1'       => $stored,
		];

		return new Field_Fileupload( $field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_html_contains_anchor_for_single_file() {
		$html = $this->pdf_field( false, 'http://example.org/wp-content/uploads/gravity_forms/test.pdf' )->html();

		$this->assertStringContainsString( '<a href=', $html );
		$this->assertStringContainsString( 'test.pdf', $html );
		$this->assertStringContainsString( '<ul class="bulleted fileupload">', $html );
	}

	public function test_html_is_empty_wrapper_when_no_file() {
		$this->assertTrue( $this->pdf_field( false, '' )->is_empty() );
	}

	public function test_html_renders_multiple_files() {
		$urls = [
			'http://example.org/wp-content/uploads/gravity_forms/1/CPC-JAKE.docx',
			'http://example.org/wp-content/uploads/gravity_forms/1/Tent-Cards.pdf',
		];
		$html = $this->pdf_field( true, json_encode( $urls ) )->html();

		$this->assertStringContainsString( 'CPC-JAKE.docx', $html );
		$this->assertStringContainsString( 'Tent-Cards.pdf', $html );
	}

	public function test_single_file_with_json_storage() {
		$url       = 'http://example.org/wp-content/uploads/gravity_forms/1/test.pdf';
		$pdf_field = $this->pdf_field( false, json_encode( [ $url ] ) );

		$this->assertSame( [ $url ], $pdf_field->value() );
		$this->assertStringContainsString( '>test.pdf</a>', $pdf_field->html() );
	}

	public function test_empty_json_storage_is_empty() {
		$this->assertTrue( $this->pdf_field( false, '[]' )->is_empty() );
	}

	public function test_plain_url_on_multi_file_field() {
		/* Entry saved before the field had Multiple Files enabled */
		$url = 'http://example.org/wp-content/uploads/gravity_forms/1/legacy.pdf';

		$this->assertSame( [ $url ], $this->pdf_field( true, $url )->value() );
	}

	public function test_array_from_field_value_filter() {
		$urls = [
			'http://example.org/wp-content/uploads/gravity_forms/1/one.pdf',
			'http://example.org/wp-content/uploads/gravity_forms/1/two.pdf',
		];
		add_filter(
			'gfpdf_field_value',
			function () use ( $urls ) {
				return $urls;
			}
		);

		$this->assertSame( $urls, $this->pdf_field( true, '' )->value() );
	}

	/**
	 * @dataProvider provider_non_url_values
	 */
	public function test_non_url_values_are_skipped( $stored ) {
		$url = 'http://example.org/wp-content/uploads/gravity_forms/1/ünï.pdf';

		$this->assertSame( [], $this->pdf_field( true, $stored )->value() );

		/* Junk alongside a real file doesn't leave a blank entry */
		$this->assertSame( [ $url ], $this->pdf_field( true, json_encode( [ $stored, $url ] ) )->value() );
	}

	public function provider_non_url_values() {
		return [
			'broken JSON'    => [ '[broken' ],
			'plain text'     => [ 'hello world' ],
			'JSON object'    => [ '{"a":"b"}' ],
			'blocked scheme' => [ 'javascript:alert(1)' ],
			'relative path'  => [ '/wp-content/uploads/x.pdf' ],
			'missing host'   => [ 'http:///x.pdf' ],
		];
	}

	/**
	 * @group slow-pdf-processes
	 */
	public function test_core_template_html() {
		global $gfpdf;

		$base    = 'http://example.org/wp-content/uploads/gravity_forms/1/';
		$form_id = \GFAPI::add_form(
			[
				'title'  => 'Fileupload core template',
				'fields' => [
					[ 'id' => 1, 'type' => 'fileupload', 'label' => 'New Single', 'storageType' => 'json' ],
					[ 'id' => 2, 'type' => 'fileupload', 'label' => 'Legacy Single' ],
					[ 'id' => 3, 'type' => 'fileupload', 'label' => 'Multi', 'multipleFiles' => true ],
					[ 'id' => 4, 'type' => 'fileupload', 'label' => 'Multi Legacy Value', 'multipleFiles' => true ],
					[ 'id' => 5, 'type' => 'fileupload', 'label' => 'Junk', 'multipleFiles' => true ],
				],
			]
		);
		$entry_id = \GFAPI::add_entry(
			[
				'form_id' => $form_id,
				'1'       => json_encode( [ $base . 'new-single.pdf' ] ),
				'2'       => $base . 'legacy-single.pdf',
				'3'       => json_encode( [ $base . 'multi-a.pdf', $base . 'multi-b.pdf' ] ),
				'4'       => $base . 'legacy-in-multi.pdf',
				'5'       => '[broken',
			]
		);

		$settings             = $GLOBALS['GFPDF_Test']->form['all-form-fields']['gfpdf_form_settings']['555ad84787d7e'];
		$settings['template'] = 'zadani';

		$fonts = glob( dirname( __DIR__, 2 ) . '/fonts/*.[tT][tT][fF]' ) ?: [];
		foreach ( $fonts as $font ) {
			copy( $font, $gfpdf->data->template_font_location . basename( $font ) );
		}

		$html = '';
		add_filter(
			'gfpdf_pdf_html_output',
			function ( $output ) use ( &$html ) {
				$html = $output;

				return $output;
			},
			1000
		);

		$file = \GPDFAPI::get_mvc_class( 'Model_PDF' )->generate_and_save_pdf( \GFAPI::get_entry( $entry_id ), $settings );
		@unlink( $file );

		foreach ( $fonts as $font ) {
			@unlink( $gfpdf->data->template_font_location . basename( $font ) );
		}

		$expected = [
			'1-option-1' => 'new-single.pdf',
			'2-option-1' => 'legacy-single.pdf',
			'3-option-1' => 'multi-a.pdf',
			'3-option-2' => 'multi-b.pdf',
			'4-option-1' => 'legacy-in-multi.pdf',
		];

		foreach ( $expected as $id => $name ) {
			$this->assertStringContainsString( sprintf( '<li id="field-%s"><a href="%s">%s</a></li>', $id, $base . $name, $name ), $html );
		}

		/* A field with no valid files is left out of the PDF */
		$this->assertStringNotContainsString( 'id="field-5"', $html );
	}
}
