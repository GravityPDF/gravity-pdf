<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Model\Model_Pdf_Cache;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The PDF Cache global settings, and the cache defaults they set
 *
 * @group statics
 * @group settings
 */
class Test_Cache_Settings extends TestCase {

	public function set_up(): void {
		parent::set_up();

		$_POST['_wp_http_referer'] = '/wp-admin/admin.php?page=gf_settings&subview=PDF&tab=general';
		$_POST['option_page']      = 'gfpdf_settings';

		/* Keeps request_purge() from spawning a cron request */
		add_filter( 'pre_http_request', [ $this, 'block_http' ] );
	}

	public function tear_down(): void {
		/* A subsite leaves these behind */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
	}

	public function block_http() {
		return new \WP_Error( 'blocked' );
	}

	public function test_the_cache_is_on_for_12_hours_with_no_saved_settings() {
		delete_option( 'gfpdf_settings' );

		$this->assertTrue( Cache::is_enabled( [], [], [] ) );
		$this->assertSame( 12 * HOUR_IN_SECONDS, Cache::get_ttl() );

		$fields = $this->gfpdf()->options->get_registered_fields()['general_cache'];
		$this->assertSame( 'Yes', $fields['pdf_cache']['std'] );
		$this->assertSame( 12, $fields['cache_duration']['std'] );
	}

	public function test_an_unchecked_toggle_saves_no_and_turns_the_cache_off() {
		$saved = $this->save_general_settings( [ 'cache_duration' => '12' ] );
		$this->assertSame( 'No', $saved['pdf_cache'] );
		$this->assertFalse( Cache::is_enabled( [], [], [] ) );

		$saved = $this->save_general_settings( [ 'pdf_cache' => 'Yes' ] );
		$this->assertSame( 'Yes', $saved['pdf_cache'] );
		$this->assertTrue( Cache::is_enabled( [], [], [] ) );
	}

	/**
	 * @dataProvider provider_cache_duration
	 */
	public function test_the_cache_duration_is_clamped( $input, $expected ) {
		$saved = $this->save_general_settings( [ 'cache_duration' => $input ] );

		$this->assertEquals( $expected, $saved['cache_duration'] );
		$this->assertSame( $expected * HOUR_IN_SECONDS, Cache::get_ttl() );
	}

	public function provider_cache_duration(): array {
		return [
			'in range' => [ '24', 24 ],
			'too low'  => [ '0', 1 ],
			'too high' => [ '500', 168 ],
			'blank'    => [ '', 12 ],
		];
	}

	public function test_the_filters_default_to_the_settings() {
		$this->gfpdf()->options->update_option( 'pdf_cache', 'No' );
		$this->gfpdf()->options->update_option( 'cache_duration', 2 );

		$this->assertFalse( Cache::is_enabled( [], [], [] ) );
		$this->assertSame( 2 * HOUR_IN_SECONDS, Cache::get_ttl() );

		/* Developers keep the final say */
		add_filter( 'gfpdf_enable_pdf_cache', '__return_true' );
		add_filter(
			'gfpdf_cache_ttl',
			function ( $ttl ) {
				return $ttl + 1;
			}
		);

		$this->assertTrue( Cache::is_enabled( [], [], [] ) );
		$this->assertSame( 2 * HOUR_IN_SECONDS + 1, Cache::get_ttl() );
	}

	public function test_an_unusable_saved_duration_falls_back_to_12_hours() {
		$this->gfpdf()->options->update_option( 'cache_duration', 'abc' );

		$this->assertSame( 12 * HOUR_IN_SECONDS, Cache::get_ttl() );
	}

	public function test_turning_the_cache_off_bumps_the_generation_and_requests_a_purge() {
		$generation = Cache::get_generation();

		$this->gfpdf()->options->update_option( 'cache_duration', 6 );
		$this->assertSame( $generation, Cache::get_generation(), 'Only an ignored setting changed' );
		$this->assertSame( '', get_option( Model_Pdf_Cache::PURGE_REQUEST_OPTION, '' ) );

		$this->gfpdf()->options->update_option( 'pdf_cache', 'No' );
		$this->assertSame( $generation + 1, Cache::get_generation() );

		$request = get_option( Model_Pdf_Cache::PURGE_REQUEST_OPTION, '' );
		$this->assertNotSame( '', $request );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_cache_sweep' ) );

		/* Turning it back on moves every key again, so nothing cached before it went off is served */
		$this->gfpdf()->options->update_option( 'pdf_cache', 'Yes' );
		$this->assertSame( $generation + 2, Cache::get_generation() );
		$this->assertSame( $request, get_option( Model_Pdf_Cache::PURGE_REQUEST_OPTION ) );
	}

	public function test_a_first_save_with_the_cache_off_bumps_the_generation_and_requests_a_purge() {
		delete_option( 'gfpdf_settings' );
		$generation = Cache::get_generation();

		add_option( 'gfpdf_settings', [ 'pdf_cache' => 'No' ] );

		$this->assertSame( $generation + 1, Cache::get_generation() );
		$this->assertNotSame( '', get_option( Model_Pdf_Cache::PURGE_REQUEST_OPTION, '' ) );
	}

	public function test_the_clear_button_is_only_offered_to_users_who_can_edit_settings() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$fields = $this->gfpdf()->options->get_registered_fields()['general_cache'];
		$this->assertSame( [ 'pdf_cache', 'cache_duration' ], array_keys( $fields ) );
		$this->assertStringContainsString( 'gfpdf-clear-pdf-cache', $fields['pdf_cache']['desc2'] );

		$user = new \WP_User( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$user->add_cap( 'gravityforms_view_settings' );
		wp_set_current_user( $user->ID );

		$this->assertSame( '', $this->gfpdf()->options->get_registered_fields()['general_cache']['pdf_cache']['desc2'] );
	}

	public function test_a_posted_clear_pdf_cache_value_is_not_saved() {
		$saved = $this->save_general_settings(
			[
				'pdf_cache'       => 'Yes',
				'clear_pdf_cache' => 'Clear Cache',
			]
		);

		$this->assertArrayNotHasKey( 'clear_pdf_cache', $saved );
	}

	public function test_a_site_switched_to_uses_its_own_settings() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->gfpdf()->options->update_option( 'cache_duration', 2 );

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		try {
			update_option( 'gfpdf_settings', [ 'cache_duration' => 5 ] );
			$this->assertSame( 5 * HOUR_IN_SECONDS, Cache::get_ttl() );
		} finally {
			restore_current_blog();
		}

		$this->assertSame( 2 * HOUR_IN_SECONDS, Cache::get_ttl() );
	}

	/**
	 * Save the General tab as options.php does
	 */
	private function save_general_settings( array $input ): array {
		$options = $this->gfpdf()->options;

		$saved = $options->settings_sanitize( $input );
		update_option( 'gfpdf_settings', $saved );
		$options->set_plugin_settings();

		return $saved;
	}
}
