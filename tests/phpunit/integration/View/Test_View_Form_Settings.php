<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group   view
 * @group   form-settings
 */
class Test_View_Form_Settings extends TestCase {

	public function test_view_type_is_form_settings(): void {
		$view = new View_Form_Settings();
		$ref  = new \ReflectionProperty( $view, 'view_type' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}

		$this->assertSame( 'FormSettings', $ref->getValue( $view ) );
	}

	public function test_get_sections_returns_four_collapsible_panels_with_expected_ids(): void {
		$view     = new View_Form_Settings();
		$markup   = new View_GravityForm_Settings_Markup();
		$sections = $view->get_sections( $markup );

		$this->assertCount( 4, $sections );
		$this->assertSame(
			[
				'gfpdf_form_settings_general',
				'gfpdf_form_settings_appearance',
				'gfpdf_form_settings_template',
				'gfpdf_form_settings_advanced',
			],
			array_column( $sections, 'id' )
		);

		foreach ( $sections as $section ) {
			$this->assertSame( 'full', $section['width'] );
			$this->assertNotEmpty( $section['title'] );
			$this->assertSame( '', $section['desc'] );
			$this->assertTrue( $section['collapsible'] );
			$this->assertTrue( $section['collapsible-open'] );
			$this->assertInstanceOf( \Closure::class, $section['callback'] );
		}
	}

	public function test_get_sections_callbacks_invoke_output_settings_fields_for_their_section(): void {
		$view     = new View_Form_Settings();
		$markup   = new View_GravityForm_Settings_Markup();
		$sections = $view->get_sections( $markup );

		/* Register a settings field under each section so output_settings_fields produces output. */
		$expected_sections = [
			'gfpdf_settings_form_settings'                    => 'general-marker',
			'gfpdf_settings_form_settings_appearance'         => 'appearance-marker',
			'gfpdf_settings_form_settings_custom_appearance'  => 'template-marker',
			'gfpdf_settings_form_settings_advanced'           => 'advanced-marker',
		];

		foreach ( $expected_sections as $section_id => $marker ) {
			add_settings_section( $section_id, "Section $marker", '__return_null', $section_id );
			add_settings_field(
				$marker,
				"Field $marker",
				static function () use ( $marker ) {
					echo '<span class="' . esc_attr( $marker ) . '"></span>';
				},
				$section_id,
				$section_id,
				[ 'id' => $marker, 'type' => 'text' ]
			);
		}

		$expected_markers = array_values( $expected_sections );

		foreach ( $sections as $index => $section ) {
			ob_start();
			( $section['callback'] )();
			$output = ob_get_clean();

			$this->assertStringContainsString( $expected_markers[ $index ], $output );
		}
	}
}
