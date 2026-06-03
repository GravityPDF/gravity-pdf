<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   form-settings
 */
class Test_Controller_Form_Settings extends TestCase {

	/**
	 * @var Controller_Form_Settings
	 */
	private $controller;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$this->controller = $gfpdf->singleton->get_class( 'Controller_Form_Settings' );
	}

	public function tear_down(): void {
		unset( $_GET['id'], $_GET['pid'], $_POST['action'], $_POST['gfpdf_save_pdf'], $_POST['gforms_update_form'] );

		parent::tear_down();
	}

	public function test_init_registers_action_and_filter_hooks() {
		global $gfpdf;

		foreach (
			[
				'admin_init',
				'gform_form_settings_menu',
				'gform_form_settings_page_' . $gfpdf->data->slug,
				'wp_ajax_gfpdf_list_delete',
				'wp_ajax_gfpdf_list_duplicate',
				'wp_ajax_gfpdf_change_state',
				'wp_ajax_gfpdf_get_template_fields',
			] as $hook
		) {
			remove_all_actions( $hook );
		}

		foreach (
			[
				'gfpdf_form_settings_custom_appearance',
				'gfpdf_form_settings',
				'gfpdf_form_settings_appearance',
				'gfpdf_form_settings_sanitize',
				'tiny_mce_before_init',
				'gform_form_update_meta',
				'gform_rule_source_value',
				'gform_is_value_match',
			] as $hook
		) {
			remove_all_filters( $hook );
		}

		$this->controller->init();

		$this->assertNotFalse( has_action( 'admin_init', [ $this->controller, 'maybe_save_pdf_settings' ] ) );
		$this->assertNotFalse( has_action( 'wp_ajax_gfpdf_list_delete' ) );
		$this->assertNotFalse( has_filter( 'gfpdf_form_settings' ) );
		$this->assertNotFalse( has_filter( 'gform_form_update_meta', [ $this->controller, 'clear_cached_pdf_settings' ] ) );
		$this->assertNotFalse( has_filter( 'tiny_mce_before_init', [ $this->controller, 'store_tinymce_settings' ] ) );
	}

	public function test_store_tinymce_settings_caches_first_call_only() {
		global $gfpdf;

		$gfpdf->data->tiny_mce_editor_settings = [];

		$result = $this->controller->store_tinymce_settings( [ 'foo' => 'bar' ] );
		$this->assertSame( [ 'foo' => 'bar' ], $result );
		$this->assertSame( [ 'foo' => 'bar' ], $gfpdf->data->tiny_mce_editor_settings );

		$second = $this->controller->store_tinymce_settings( [ 'baz' => 'qux' ] );
		$this->assertSame( [ 'baz' => 'qux' ], $second, 'Returns whatever is passed in' );
		$this->assertSame( [ 'foo' => 'bar' ], $gfpdf->data->tiny_mce_editor_settings, 'Cache stays sticky once populated' );
	}

	public function test_clear_cached_pdf_settings_ignores_unrelated_meta() {
		$form = [ 'gfpdf_form_settings' => [ 'unchanged' => true ] ];

		$result = $this->controller->clear_cached_pdf_settings( $form, 1, 'something_else' );

		$this->assertSame( $form, $result );
	}

	public function test_clear_cached_pdf_settings_ignores_when_no_save_action_posted() {
		set_current_screen( 'edit.php' );
		$form = [ 'gfpdf_form_settings' => [ 'unchanged' => true ] ];

		$result = $this->controller->clear_cached_pdf_settings( $form, 1, 'display_meta' );

		$this->assertSame( $form, $result );
	}

	public function test_conditional_logic_is_value_match_returns_original_for_unrelated_fields() {
		$result = $this->controller->conditional_logic_is_value_match(
			false,
			'2026-01-01',
			'2025-01-01',
			'>',
			null,
			[ 'fieldId' => 'unrelated' ]
		);

		$this->assertFalse( $result, 'unrelated field passes through original $is_match' );
	}

	public function test_conditional_logic_is_value_match_compares_date_created_with_greater_than() {
		$result = $this->controller->conditional_logic_is_value_match(
			false,
			'2026-06-01',
			'2026-01-01',
			'>',
			null,
			[ 'fieldId' => 'date_created' ]
		);

		$this->assertTrue( $result );
	}

	public function test_conditional_logic_is_value_match_compares_payment_date_with_less_than() {
		$result = $this->controller->conditional_logic_is_value_match(
			true,
			'2026-01-01',
			'2026-06-01',
			'<',
			null,
			[ 'fieldId' => 'payment_date' ]
		);

		$this->assertTrue( $result );
	}

	public function test_conditional_logic_set_rule_source_value_passes_through_when_no_entry() {
		$result = $this->controller->conditional_logic_set_rule_source_value(
			'original',
			[ 'fieldId' => 'date_created' ],
			[ 'id' => 1 ],
			[],
			null
		);

		$this->assertSame( 'original', $result );
	}
}
