<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Extension_Settings;
use GFPDF\Helper\Helper_Logger;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Singleton;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @group   view
 * @group   settings
 */
class Test_View_Settings extends TestCase {

	/** @var Helper_Data */
	private $data;

	/** @var View_Settings */
	private $view;

	public function set_up() {
		global $gfpdf;

		parent::set_up();

		$this->data        = new Helper_Data();
		$this->data->addon = [];

		$this->view = new View_Settings(
			[],
			$gfpdf->gform,
			$gfpdf->log,
			$gfpdf->options,
			$this->data,
			$gfpdf->misc,
			$gfpdf->templates
		);
	}

	public function tear_down() {
		remove_all_filters( 'gfpdf_settings_navigation' );
		remove_all_filters( 'gravitypdf_registered_tooltips' );
		remove_all_actions( 'admin_notices' );
		parent::tear_down();
	}

	public function test_get_available_tabs_returns_settings_tools_help_by_default() {
		$tabs = $this->view->get_available_tabs();

		$this->assertSame( [ 5, 100, 120 ], array_keys( $tabs ) );
		$this->assertSame( 'general', $tabs[5]['id'] );
		$this->assertSame( 'tools', $tabs[100]['id'] );
		$this->assertSame( 'help', $tabs[120]['id'] );
	}

	public function test_get_available_tabs_adds_license_tab_when_any_addon_registered() {
		$this->data->add_addon( $this->make_addon( 'addon-a' ) );

		$tabs = $this->view->get_available_tabs();

		$this->assertArrayHasKey( 10, $tabs );
		$this->assertSame( 'license', $tabs[10]['id'] );
		$this->assertArrayNotHasKey( 20, $tabs );
	}

	public function test_get_available_tabs_adds_extensions_tab_when_addon_implements_interface() {
		$this->data->add_addon( $this->make_addon( 'addon-a' ) );
		$this->data->add_addon( $this->make_extension_addon( 'addon-b' ) );

		$tabs = $this->view->get_available_tabs();

		$this->assertArrayHasKey( 10, $tabs );
		$this->assertArrayHasKey( 20, $tabs );
		$this->assertSame( 'extensions', $tabs[20]['id'] );
	}

	public function test_get_available_tabs_returns_tabs_sorted_by_priority_key() {
		$this->data->add_addon( $this->make_extension_addon( 'addon-b' ) );

		$tabs = $this->view->get_available_tabs();

		$this->assertSame( [ 5, 10, 20, 100, 120 ], array_keys( $tabs ) );
	}

	public function test_get_available_tabs_applies_navigation_filter() {
		add_filter(
			'gfpdf_settings_navigation',
			static function ( $nav ) {
				$nav[7] = [
					'name' => 'Custom',
					'id'   => 'custom',
				];
				return $nav;
			}
		);

		$tabs = $this->view->get_available_tabs();

		$this->assertArrayHasKey( 7, $tabs );
		$this->assertSame( 'custom', $tabs[7]['id'] );
	}

	public function test_add_tooltips_adds_pdf_shortcode_entry_and_applies_filter() {
		add_filter(
			'gravitypdf_registered_tooltips',
			static function ( $tooltips ) {
				$tooltips['custom'] = 'CUSTOM_TOOLTIP';
				return $tooltips;
			}
		);

		$result = $this->view->add_tooltips( [ 'existing' => 'keep me' ] );

		$this->assertArrayHasKey( 'existing', $result );
		$this->assertArrayHasKey( 'pdf_shortcode', $result );
		$this->assertArrayHasKey( 'custom', $result );
		$this->assertStringContainsString( '[gravitypdf]', $result['pdf_shortcode'] );
	}

	private function make_addon( string $slug ): Helper_Abstract_Addon {
		return new class(
			$slug,
			ucfirst( $slug ),
			'Test',
			'1.0',
			'/path/to/' . $slug . '/',
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( $slug, ucfirst( $slug ) ),
			new Helper_Notices()
		) extends Helper_Abstract_Addon {
			public function plugin_updater() {}
		};
	}

	private function make_extension_addon( string $slug ): Helper_Abstract_Addon {
		return new class(
			$slug,
			ucfirst( $slug ),
			'Test',
			'1.0',
			'/path/to/' . $slug . '/',
			GPDFAPI::get_data_class(),
			GPDFAPI::get_options_class(),
			new Helper_Singleton(),
			new Helper_Logger( $slug, ucfirst( $slug ) ),
			new Helper_Notices()
		) extends Helper_Abstract_Addon implements Helper_Interface_Extension_Settings {
			public function plugin_updater() {}
			public function get_global_addon_fields() {
				return [];
			}
		};
	}
}
