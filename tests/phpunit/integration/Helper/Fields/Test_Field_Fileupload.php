<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field_FileUpload;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Fileupload extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}


	private function fileupload_field( bool $multiple ): GF_Field_FileUpload {
		foreach ( $this->form( 'all-form-fields' )['fields'] as $field ) {
			if ( $field->type === 'fileupload' && (bool) $field->multipleFiles === $multiple ) {
				return new GF_Field_FileUpload( $field );
			}
		}

		$this->fail( sprintf( 'No %s fileupload field found in all-form-fields fixture', $multiple ? 'multi-file' : 'single-file' ) );
	}

	private function pdf_field( bool $multiple, $stored ): Field_Fileupload {
		$gf_field = $this->fileupload_field( $multiple );
		$entry    = [
			'id'          => 0,
			'form_id'     => $this->form( 'all-form-fields' )['id'],
			$gf_field->id => $stored,
		];

		return new Field_Fileupload( $gf_field, $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
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

	/**
	 * @group slow
	 */
	public function test_core_template_html() {
		$base    = 'http://example.org/wp-content/uploads/gravity_forms/1/';
		$form_id = $this->gf_factory()->form->create(
			[],
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
		$entry_id = $this->gf_factory()->entry->create(
			[
				'form_id' => $form_id,
				'1'       => json_encode( [ $base . 'new-single.pdf' ] ),
				'2'       => $base . 'legacy-single.pdf',
				'3'       => json_encode( [ $base . 'multi-a.pdf', $base . 'multi-b.pdf' ] ),
				'4'       => $base . 'legacy-in-multi.pdf',
				'5'       => '[broken',
			]
		);

		$settings             = $this->form( 'all-form-fields' )['gfpdf_form_settings']['555ad84787d7e'];
		$settings['template'] = 'zadani';

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
}
