<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fields;

use GF_Field;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   fields
 */
class Test_Field_V3_Products extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'non-group-products-form' ], [ 'non-group-products-form' ] );
	}


	protected function build_v3_products( array $entry ): Field_V3_Products {
		return new Field_V3_Products(
			new GF_Field(),
			$entry,
			\GPDFAPI::get_form_class(),
			\GPDFAPI::get_misc_class()
		);
	}

	public function test_html_contains_order_heading() {
		$pdf_field = $this->build_v3_products( $this->entry( 'non-group-products-form' ) );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'entry-view-section-break', $html );
		$this->assertStringContainsString( 'Order', $html );
	}

	public function test_html_contains_products_table_content() {
		$pdf_field = $this->build_v3_products( $this->entry( 'non-group-products-form' ) );
		$html      = $pdf_field->html();

		$this->assertStringContainsString( 'entry-products', $html );
	}
}
