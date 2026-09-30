<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Statics\Cache;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group model
 * @group cache
 */
class Test_Model_Pdf_Cache extends TestCase {

	public function test_saving_settings_bumps_the_generation_unless_every_change_is_ignored() {
		$generation = Cache::get_generation();

		\GPDFAPI::update_plugin_option( 'license_gravity-pdf-core-booster_status', 'active' );
		\GPDFAPI::update_plugin_option( 'action_dismissal', [ 'review' => 'review' ] );
		\GPDFAPI::update_plugin_option( 'signed_secret_token', 'token' );
		$this->assertSame( $generation, Cache::get_generation(), 'Only ignored settings changed' );

		\GPDFAPI::update_plugin_option( 'default_font_size', '14' );
		$this->assertSame( $generation + 1, Cache::get_generation() );

		\GPDFAPI::delete_plugin_option( 'default_font_size' );
		$this->assertSame( $generation + 2, Cache::get_generation(), 'Removing a setting counts as a change' );

		add_filter(
			'gfpdf_cache_generation_ignored_settings',
			function ( $ignored ) {
				$ignored[] = 'my_addon_*';

				return $ignored;
			}
		);

		\GPDFAPI::update_plugin_option( 'my_addon_setting', 'Yes' );
		$this->assertSame( $generation + 2, Cache::get_generation() );
	}

	public function test_the_first_settings_save_bumps_the_generation() {
		delete_option( 'gfpdf_settings' );
		$generation = Cache::get_generation();

		add_option( 'gfpdf_settings', [ 'signed_secret_token' => 'token' ] );
		$this->assertSame( $generation, Cache::get_generation(), 'Only ignored settings were added' );

		delete_option( 'gfpdf_settings' );
		update_option( 'gfpdf_settings', [ 'default_font' => 'dejavusans' ] );
		$this->assertSame( $generation + 1, Cache::get_generation() );
	}
}
