<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group     statics
 */
class Test_Cache extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public function test_get_hash() {
		$results = $this->create_form_and_entries();

		$form  = $results['form'];
		$entry = $results['entry'];

		$pdf_settings             = $form['gfpdf_form_settings']['555ad84787d7e'];
		$pdf_settings['template'] = 'zadani';

		/* Verify the hash is the same when called multiple times with the same inputs */
		$hash1 = Cache::get_hash( $form, $entry, $pdf_settings );
		$hash2 = Cache::get_hash( $form, $entry, $pdf_settings );

		$this->assertEquals( $hash1, $hash2 );

		/* Verify the hash changes when the input changes */
		$pdf_settings['active'] = false;

		$hash3 = Cache::get_hash( $form, $entry, $pdf_settings );

		$this->assertNotEquals( $hash3, $hash2 );
	}

	protected function create_form_and_entries() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$gfpdf                                     = $this->gfpdf();
		$gfpdf->data->form_settings                = [];
		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		return [
			'form'  => $form,
			'entry' => $entry,
		];
	}

	public function test_get_path() {
		$results = $this->create_form_and_entries();

		$form  = $results['form'];
		$entry = $results['entry'];

		$pdf_settings             = $form['gfpdf_form_settings']['555ad84787d7e'];
		$pdf_settings['template'] = 'zadani';

		$hash1 = Cache::get_hash( $form, $entry, $pdf_settings );
		$path  = Cache::get_path( $form, $entry, $pdf_settings );

		$this->assertStringEndsWith( '/' . $hash1 . '/', $path );
		$this->assertStringStartsWith( ABSPATH, $path );
	}

}
