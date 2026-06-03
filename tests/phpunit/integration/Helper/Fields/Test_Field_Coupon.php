<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use Exception;
use GF_Field;
use GF_Field_Coupon;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 * @group   coupon
 */
class Test_Field_Coupon extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ] );
	}

	/** Entry IDs created during a test, cleaned up in tear_down(). */
	private array $created_entry_ids = [];

	public function set_up(): void {
		parent::set_up();

		if ( ! class_exists( '\GF_Field_Coupon' ) ) {
			$this->markTestSkipped( 'Gravity Forms Coupons add-on is not active in the test environment.' );
		}
	}

	public function tear_down(): void {
		foreach ( $this->created_entry_ids as $id ) {
			\GFAPI::delete_entry( $id );
		}
		$this->created_entry_ids = [];

		parent::tear_down();
	}

	public function test_constructor_rejects_non_coupon_field(): void {
		$this->expectException( Exception::class );

		new Field_Coupon( new GF_Field(), [], \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}

	public function test_html_returns_empty_value_markup_when_entry_has_no_coupon(): void {
		$pdf_field = $this->make_field_with_real_entry( '' );

		$html = $pdf_field->html();

		$this->assertStringContainsString( 'class="gfpdf-field gfpdf-coupon', $html );
		$this->assertStringContainsString( '<div class="value">&nbsp;</div>', $html );
	}

	public function test_value_caches_subsequent_calls(): void {
		$pdf_field = $this->make_field_with_real_entry( 'CODE10' );

		$first  = $pdf_field->value();
		$second = $pdf_field->value();

		$this->assertSame( $first, $second );
		$this->assertTrue( $pdf_field->has_cache() );
	}

	private function make_field_with_real_entry( string $entry_value ): Field_Coupon {
		$gf_field     = new GF_Field_Coupon();
		$gf_field->id = 99;

		$form_id  = $this->form( 'all-form-fields' )['id'];
		$entry_id = $this->gf_factory()->entry->create( [ 'form_id' => $form_id, '99' => $entry_value ] );

		$this->created_entry_ids[] = $entry_id;

		return new Field_Coupon( $gf_field, \GFAPI::get_entry( $entry_id ), \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() );
	}
}
