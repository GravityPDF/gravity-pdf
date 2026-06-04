<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;


/**
 * @group   helper
 * @group   fields
 */
class Test_Field_Total extends TestCase {

	/* Defaulted to 0 so tear_down_after_class is safe on early set_up_before_class failure. */
	private static int $empty_form_id  = 0;
	private static int $empty_entry_id = 0;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'non-group-products-form' ], [ 'non-group-products-form' ] );

		/* Empty products form for the no-products Field_Total tests. */
		$factory              = new \GF_UnitTest_Factory();
		self::$empty_form_id  = $factory->form->create( [], [ 'title' => 'No Products Form', 'fields' => [] ] );
		self::$empty_entry_id = $factory->entry->create( [ 'form_id' => self::$empty_form_id, 'currency' => 'USD' ] );
	}

	public static function tear_down_after_class(): void {
		if ( self::$empty_entry_id ) {
			\GFAPI::delete_entry( self::$empty_entry_id );
		}
		if ( self::$empty_form_id ) {
			\GFAPI::delete_form( self::$empty_form_id );
		}

		parent::tear_down_after_class();
	}

	private function make_empty_products_entry(): array {
		return \GFAPI::get_entry( self::$empty_entry_id );
	}

	protected function build_total_pdf_field( array $entry ): Field_Total {
		$gf_field  = new GF_Field( [ 'id' => 40, 'type' => 'total', 'label' => 'Total' ] );
		$pdf_field = new Field_Total(
			$gf_field,
			$entry,
			\GPDFAPI::get_form_class(),
			\GPDFAPI::get_misc_class()
		);
		$pdf_field->set_products(
			new Field_Products( new GF_Field(), $entry, \GPDFAPI::get_form_class(), \GPDFAPI::get_misc_class() )
		);

		return $pdf_field;
	}

	public function test_value_returns_empty_array_when_no_products_in_entry() {
		$entry     = $this->make_empty_products_entry();
		$pdf_field = $this->build_total_pdf_field( $entry );

		$this->assertSame( [], $pdf_field->value() );
	}

	public function test_html_is_empty_string_when_no_products() {
		$entry     = $this->make_empty_products_entry();
		$pdf_field = $this->build_total_pdf_field( $entry );

		/* value() returns [] so the field should report itself as empty. */
		$this->assertTrue( $pdf_field->is_empty() );
	}

	public function test_value_contains_total_and_total_formatted_with_products() {
		$entry     = $this->entry( 'non-group-products-form' );
		$pdf_field = $this->build_total_pdf_field( $entry );

		$value = $pdf_field->value();

		$this->assertArrayHasKey( 'total', $value );
		$this->assertArrayHasKey( 'total_formatted', $value );
		$this->assertGreaterThan( 0, $value['total'] );
		$this->assertStringContainsString( '$', $value['total_formatted'] );
	}
}
