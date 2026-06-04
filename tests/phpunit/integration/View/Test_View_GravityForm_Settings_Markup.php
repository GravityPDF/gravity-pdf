<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   view
 * @group   gravityform-settings
 */
class Test_View_GravityForm_Settings_Markup extends TestCase {

	private View_GravityForm_Settings_Markup $view;

	public function set_up(): void {
		parent::set_up();
		$this->view = new View_GravityForm_Settings_Markup();
	}

	public function tear_down(): void {
		global $wp_settings_fields;
		unset( $wp_settings_fields['gfpdf_view_test'] );
		parent::tear_down();
	}

	public function test_constants_match_documented_values() {
		$this->assertSame( 1, View_GravityForm_Settings_Markup::ENABLE_PANEL_TITLE );
		$this->assertSame( 0, View_GravityForm_Settings_Markup::DISABLE_PANEL_TITLE );
	}

	public function test_get_section_fields_returns_empty_array_for_unknown_section() {
		$this->assertSame( [], $this->view->get_section_fields( 'no_such_section' ) );
	}

	public function test_get_section_fields_returns_registered_fields() {
		$this->register_field( 'gfpdf_view_test', 'demo', 'Demo' );

		$fields = $this->view->get_section_fields( 'gfpdf_view_test' );

		$this->assertArrayHasKey( 'demo', $fields );
		$this->assertSame( 'Demo', $fields['demo']['title'] );
	}

	public function test_do_settings_fields_as_individual_fieldset_maps_registered_fields() {
		$this->register_field( 'gfpdf_view_test', 'demo', 'Demo' );
		$this->register_field(
			'gfpdf_view_test',
			'with_tooltip',
			'With Tooltip',
			[ 'tooltip' => '<strong>Help</strong> goes here' ]
		);

		$sections = $this->view->do_settings_fields_as_individual_fieldset(
			'gfpdf_view_test',
			[
				'demo' => [
					'width' => 'full',
					'desc'  => 'Override description',
				],
			]
		);

		$this->assertCount( 2, $sections );

		$this->assertSame( 'demo', $sections[0]['id'] );
		$this->assertSame( 'Demo', $sections[0]['title'] );
		$this->assertSame( 'full', $sections[0]['width'] );
		$this->assertSame( 'Override description', $sections[0]['desc'] );
		$this->assertSame( '', $sections[0]['tooltip'] );

		$this->assertSame( 'with_tooltip', $sections[1]['id'] );
		$this->assertSame( 'half', $sections[1]['width'] );
		$this->assertSame( '', $sections[1]['desc'] );
		$this->assertStringContainsString( 'gfpdf-tooltip', $sections[1]['tooltip'] );
	}

	public function test_get_tooltip_markup_renders_gform_tooltip_anchor() {
		$markup = $this->view->get_tooltip_markup( '<strong>Help</strong> text' );

		$this->assertStringContainsString( 'gfpdf-tooltip', $markup );
		$this->assertStringContainsString( 'Help', $markup );
	}

	public function test_do_settings_sections_concatenates_fieldset_markup() {
		$sections = [
			[
				'id'       => 'a',
				'title'    => 'Section A',
				'callback' => static function () {
					echo 'AAA';
				},
			],
			[
				'id'       => 'b',
				'title'    => 'Section B',
				'callback' => static function () {
					echo 'BBB';
				},
			],
		];

		$markup = $this->view->do_settings_sections( $sections );

		$this->assertStringContainsString( 'AAA', $markup );
		$this->assertStringContainsString( 'BBB', $markup );
		$this->assertLessThan( strpos( $markup, 'BBB' ), strpos( $markup, 'AAA' ), 'Sections should be concatenated in iteration order' );
	}

	private function register_field( string $page, string $id, string $title, array $args = [] ): void {
		global $wp_settings_fields;
		$wp_settings_fields[ $page ][ $page ][ $id ] = [
			'id'       => $id,
			'title'    => $title,
			'callback' => static function () {},
			'args'     => array_merge( [ 'id' => $id ], $args ),
		];
	}
}
