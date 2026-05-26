<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 * @group   pdf-list-table
 */
class Test_Helper_PDF_List_Table extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'form-settings' ] );
	}

	private Helper_PDF_List_Table $table;

	public function set_up(): void {
		parent::set_up();

		global $gfpdf;

		$form        = $this->form( 'form-settings' );
		$this->table = new Helper_PDF_List_Table(
			$form,
			$gfpdf->gform,
			$gfpdf->misc,
			$gfpdf->templates
		);
	}

	public function test_get_columns_returns_expected_keys(): void {
		$columns = $this->table->get_columns();

		$this->assertArrayHasKey( 'cb', $columns );
		$this->assertArrayHasKey( 'name', $columns );
		$this->assertArrayHasKey( 'template', $columns );
		$this->assertArrayHasKey( 'notifications', $columns );
		$this->assertArrayHasKey( 'shortcode', $columns );
	}

	public function test_column_default_outputs_item_value(): void {
		$item = [
			'id'          => 'abc123',
			'custom_col'  => 'my-custom-value',
		];

		ob_start();
		$this->table->column_default( $item, 'custom_col' );
		$output = ob_get_clean();

		$this->assertSame( 'my-custom-value', $output );
	}

	public function test_column_default_outputs_empty_string_for_missing_key(): void {
		$item = [ 'id' => 'xyz' ];

		ob_start();
		$this->table->column_default( $item, 'nonexistent_column' );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_prepare_items_populates_from_form_settings(): void {
		$this->table->prepare_items();

		$form = $this->form( 'form-settings' );

		$this->assertSame(
			$form['gfpdf_form_settings'] ?? [],
			$this->table->items
		);
	}

	public function test_get_columns_respects_filter(): void {
		add_filter(
			'gfpdf_pdf_list_columns',
			static function ( array $columns ): array {
				$columns['extra'] = 'Extra';
				return $columns;
			}
		);

		$columns = $this->table->get_columns();
		$this->assertArrayHasKey( 'extra', $columns );

		remove_all_filters( 'gfpdf_pdf_list_columns' );
	}
}
