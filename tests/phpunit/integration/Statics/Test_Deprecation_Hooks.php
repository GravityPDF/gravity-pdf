<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Tests\Concerns\ResetsDetectedFeatures;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group     statics
 */
class Test_Deprecation_Hooks extends TestCase {

	use ResetsDetectedFeatures;

	public function set_up(): void {
		parent::set_up();

		$this->reset_detected_features();
		Deprecation::flush_cache();
	}

	public function test_the_tools_tab_feature_is_registered() {
		$feature = Deprecation::get_feature( 'tools_tab_hooks' );

		$this->assertSame( Deprecation::GROUP_DEPRECATED, $feature['group'] );
		$this->assertSame( '6.19.0', $feature['deprecated_in'] );
		$this->assertSame( '7.0', $feature['removed_in'] );
		$this->assertSame(
			[
				'gfpdf_settings_tools'           => 'gfpdf_settings_general',
				'gfpdf_post_tools_settings_page' => 'gfpdf_post_general_settings_page',
			],
			$feature['hooks']
		);
		$this->assertStringContainsString( 'version 7.0', Deprecation::get_feature_notice( $feature ) );
	}

	/**
	 * Core fires these but never listens, so a clean install reports nothing
	 */
	public function test_a_clean_install_reports_nothing() {
		$this->assertArrayNotHasKey( 'tools_tab_hooks', Deprecation::get_signals() );
	}

	public function test_listeners_on_the_tools_tab_hooks_are_reported() {
		$callback = '__return_empty_array';

		add_filter( 'gfpdf_settings_tools', $callback );
		add_action( 'gfpdf_post_tools_settings_page', $callback );
		add_action( 'gfpdf_post_tools_settings_page', '__return_null' );

		/* A similarly named hook isn't one of these */
		add_filter( 'gfpdf_settings_tools_sanitize', $callback );

		$this->assertSame(
			[
				'gfpdf_post_tools_settings_page' => 2,
				'gfpdf_settings_tools'           => 1,
			],
			Deprecation::refresh_signals()['tools_tab_hooks']
		);
		$this->assertContains( 'tools_tab_hooks', Deprecation::get_detected_features() );

		remove_filter( 'gfpdf_settings_tools', $callback );
		remove_action( 'gfpdf_post_tools_settings_page', $callback );
		remove_action( 'gfpdf_post_tools_settings_page', '__return_null' );
		remove_filter( 'gfpdf_settings_tools_sanitize', $callback );
	}
}
