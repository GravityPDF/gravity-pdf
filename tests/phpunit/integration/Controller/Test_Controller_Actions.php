<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Concerns\CreatesLegacyDownloadUrls;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\ResetsDetectedFeatures;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   actions
 */
class Test_Controller_Actions extends TestCase {

	use CreatesLegacyDownloadUrls;
	use RecordsFontFailures;
	use ResetsDetectedFeatures;

	/**
	 * @var Controller_Actions
	 */
	private $controller;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$this->controller = $gfpdf->singleton->get_class( 'Controller_Actions' );

		$this->reset_detected_features();
	}

	public function tear_down(): void {
		unset( $_POST['gfpdf_action'], $_POST['gfpdf_action_failed_core_fonts'], $_POST['gfpdf-dismiss-notice'], $_GET['page'] );
		$this->delete_font_failures();

		/* A subsite leaves these behind */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
	}

	public function test_init_registers_admin_init_hooks() {
		remove_all_actions( 'admin_init' );

		$this->controller->init();

		$this->assertNotFalse( has_action( 'admin_init', [ $this->controller, 'route' ] ) );
		$this->assertNotFalse( has_action( 'admin_init', [ $this->controller, 'route_notices' ] ) );
	}

	public function test_get_routes_includes_default_routes() {
		$routes = $this->controller->get_routes();

		$this->assertSame( [ 'unwritable_folders', 'failed_core_fonts', 'deprecated_features' ], array_column( $routes, 'action' ) );

		foreach ( $routes as $route ) {
			$this->assertIsCallable( $route['condition'] );
			$this->assertIsCallable( $route['process'] );
			$this->assertIsCallable( $route['view'] );
		}
	}

	public function test_every_deprecated_feature_shares_one_route() {
		$route = array_column( $this->controller->get_routes(), null, 'action' )['deprecated_features'];

		$this->assertSame( 'gravityforms_view_settings', $route['capability'] );
		$this->assertIsCallable( $route['condition'] );
		$this->assertIsCallable( $route['view'] );
		$this->assertIsCallable( $route['dismiss'] );

		/* Resolved when the notice displays, so nothing queries the site to style a notice that never renders */
		$this->assertIsCallable( $route['view_class'] );

		/* Every v3 feature still works until 7.0, so the notice is a warning rather than an error */
		$this->assertSame( 'notice-warning', call_user_func( $route['view_class'] ) );
	}

	/**
	 * A record taken before a provider was removed can name a feature nothing declares any more
	 */
	public function test_an_unregistered_feature_is_never_listed() {
		global $gfpdf;

		$model = $gfpdf->singleton->get_class( 'Model_Actions' );

		\GPDFAPI::get_options_class()->update_option( 'deprecated_features', [ 'legacy_templates', 'not-a-feature' ] );

		$this->assertSame( [ 'legacy_templates' ], $model->get_undismissed_deprecated_features() );
	}

	/**
	 * One notice covers every feature, and dismissing it records each one it listed — which is what keeps the
	 * notices coming: the next round of removals arrives undismissed and raises it again
	 */
	public function test_dismissing_the_notice_clears_every_feature_it_listed() {
		global $gfpdf, $pagenow;

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'dashboard' );

		$original = $pagenow;
		$pagenow  = 'index.php';

		$model   = $gfpdf->singleton->get_class( 'Model_Actions' );
		$options = \GPDFAPI::get_options_class();

		$options->update_option( 'deprecated_features', [ 'legacy_templates', 'legacy_endpoint' ] );

		$this->assertTrue( $model->has_deprecated_features() );
		$this->assertSame( [ 'legacy_templates', 'legacy_endpoint' ], $model->get_undismissed_deprecated_features() );

		$html = $this->render_notices();
		$this->assertStringContainsString( 'PDFs that use a legacy template can no longer be generated', $html );
		$this->assertStringContainsString( 'Old PDF download links stopped working', $html );

		$model->dismiss_deprecated_features();

		$this->assertSame( [], $model->get_undismissed_deprecated_features() );

		/* Other routes can still have a notice of their own, so only this one has to be gone */
		$html = $this->render_notices();
		$this->assertStringNotContainsString( 'PDFs that use a legacy template can no longer be generated', $html );
		$this->assertStringNotContainsString( 'Old PDF download links stopped working', $html );

		/* A feature detected after that dismissal was never listed, so the notice returns for it alone */
		$options->update_option( 'deprecated_features', [ 'legacy_templates', 'legacy_endpoint', 'business_plus_templates' ] );

		$this->assertTrue( $model->has_deprecated_features() );
		$this->assertSame( [ 'business_plus_templates' ], $model->get_undismissed_deprecated_features() );

		$html = $this->render_notices();
		$this->assertStringContainsString( 'PDFs built with Advanced Templating can no longer be generated', $html );
		$this->assertStringNotContainsString( 'PDFs that use a legacy template can no longer be generated', $html );

		$pagenow = $original;
		$gfpdf->notices->clear();
	}

	public function test_route_notices_raises_a_notice_for_a_detected_feature() {
		global $gfpdf, $pagenow;

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'dashboard' );

		$original = $pagenow;
		$pagenow  = 'index.php';

		$gfpdf->notices->clear();
		$this->controller->route_notices();

		$this->assertStringNotContainsString( 'legacy download URLs', $this->get_rendered_notices() );

		$this->create_form_with_legacy_url();

		/* The notice reads what the last detection recorded, and a release is when the site gets a fresh one */
		do_action( 'gfpdf_version_changed', '6.16.0', '6.17.0' );

		$gfpdf->notices->clear();
		$this->controller->route_notices();

		$html = $this->get_rendered_notices();

		$this->assertStringContainsString( 'Old PDF download links stopped working in Gravity PDF 7.0, so anyone who clicks one will not get their PDF. Replace those links with the [gravitypdf] shortcode or a PDF merge tag.', $html );
		$this->assertStringContainsString( 'View the system report', $html );

		/* The endpoint is already gone rather than going, so the notice reads as an error rather than a warning */
		$this->assertStringContainsString( 'notice-error', $html );

		$pagenow = $original;
		$gfpdf->notices->clear();
	}

	/**
	 * Network admin reports the main site, which is the scope Gravity PDF's settings and detections live at: a
	 * subsite's own admin raises its own notice. Aggregating the network is a 7.x question, not a 6.17 one
	 */
	public function test_route_notices_raises_the_notice_in_network_admin() {
		global $gfpdf, $pagenow;

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$original = $pagenow;
		$pagenow  = 'index.php';
		set_current_screen( 'index.php-network' );

		$this->create_form_with_legacy_url();
		do_action( 'gfpdf_version_changed', '6.16.0', '6.17.0' );

		$gfpdf->notices->clear();
		$this->controller->route_notices();

		$this->assertTrue( is_network_admin() );

		/* Helper_Notices renders this on network_admin_notices rather than admin_notices */
		$this->assertTrue( $gfpdf->notices->has_notice() );
		$this->assertStringContainsString( 'Old PDF download links stopped working', $this->get_rendered_notices() );

		$pagenow = $original;
		set_current_screen( 'dashboard' );
		$gfpdf->notices->clear();
	}

	public function test_the_failed_core_fonts_route() {
		$route = array_column( $this->controller->get_routes(), null, 'action' )['failed_core_fonts'];

		$this->assertSame( 'gravityforms_edit_settings', $route['capability'] );
		$this->assertSame( 'notice-warning', $route['view_class'] );
		$this->assertSame( 'Retry the downloads', $route['action_text'] );
		$this->assertIsCallable( $route['condition'] );
		$this->assertIsCallable( $route['dismiss'] );
	}

	public function test_fonts_that_failed_after_their_retry_raise_a_notice_until_dismissed() {
		$this->with_screen(
			'administrator',
			function () {
				global $gfpdf;

				$model = $gfpdf->singleton->get_class( 'Model_Actions' );

				$this->set_font_failures( [ 'Garuda.ttf' => $this->failure() ] );
				$this->assertStringNotContainsString( 'could not download these fonts', $this->render_notices() );

				$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() - 10 ), 'FreeSans.ttf' => $this->failure() ] );
				$html = $this->render_notices();
				$this->assertStringContainsString( 'Gravity PDF could not download these fonts. Until the fonts are installed, PDFs that need them use a fallback font', $html );
				$this->assertStringContainsString( '<code>Garuda.ttf</code>', $html );
				$this->assertStringNotContainsString( 'FreeSans.ttf', $html );
				$this->assertStringContainsString( 'https://docs.gravitypdf.com/users/core-pdf-fonts', $html );
				$this->assertStringContainsString( 'admin.php?page=gf_system_status#gfpdf-font-download-failed', $html );
				$this->assertStringContainsString( 'Retry the downloads', $html );

				$model->dismiss_failed_core_fonts();
				$this->assertStringNotContainsString( 'could not download these fonts', $this->render_notices() );
				$this->assertTrue( $this->get_font_failures()['Garuda.ttf']['dismissed'] );

				/* A font that gives up later raises it again, for that font alone */
				$failures                 = $this->get_font_failures();
				$failures['FreeSans.ttf'] = $this->failure( time() );
				$this->set_font_failures( $failures );
				$this->assertSame( [ 'FreeSans.ttf' ], $model->get_undismissed_failed_core_fonts() );

				/* A font that downloads leaves the notice, which goes once none are left */
				$this->set_font_failures( [] );
				$this->assertFalse( $model->has_failed_core_fonts() );
				$this->assertStringNotContainsString( 'could not download these fonts', $this->render_notices() );
			}
		);
	}

	public function test_the_failed_core_fonts_notice_needs_the_settings_capability() {
		$this->with_screen(
			'editor',
			function () {
				$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
				$this->assertStringNotContainsString( 'could not download these fonts', $this->render_notices() );
			}
		);
	}

	/**
	 * The fonts folder serves the whole network, so the network admin sees the notice too
	 */
	public function test_the_failed_core_fonts_notice_shows_in_network_admin() {
		$this->with_screen(
			'administrator',
			function () {
				global $gfpdf;

				$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
				$gfpdf->notices->clear();
				$this->controller->route_notices();

				$this->assertTrue( is_network_admin() );
				$this->assertTrue( $gfpdf->notices->has_notice() );
			},
			'index.php-network'
		);
	}

	public function test_dismissing_the_failed_core_fonts_notice_dismisses_it_on_every_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		global $gfpdf;

		$model = $gfpdf->singleton->get_class( 'Model_Actions' );
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
		$model->dismiss_failed_core_fonts();

		switch_to_blog( self::factory()->blog->create() );
		try {
			\GPDFAPI::get_mvc_class( 'Model_Font_Downloader' )->flush();
			$this->assertFalse( $model->has_failed_core_fonts() );
		} finally {
			restore_current_blog();
		}
	}

	public function test_the_dismiss_request_dismisses_the_failed_core_fonts_notice() {
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->post_failed_core_fonts_dismissal();
		$this->controller->route();

		$this->assertTrue( $this->get_font_failures()['Garuda.ttf']['dismissed'] );
	}

	public function test_a_bad_nonce_leaves_the_failed_core_fonts_notice_undismissed() {
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->post_failed_core_fonts_dismissal( 'not-a-nonce' );
		$this->controller->route();

		$this->assertFalse( $this->get_font_failures()['Garuda.ttf']['dismissed'] );
	}

	public function test_a_user_who_cannot_edit_the_settings_cannot_dismiss_the_failed_core_fonts_notice() {
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->post_failed_core_fonts_dismissal();

		try {
			$this->controller->route();
			$this->fail( 'The request should have been refused' );
		} catch ( \WPDieException $e ) {
			$this->assertFalse( $this->get_font_failures()['Garuda.ttf']['dismissed'] );
		}
	}

	public function test_the_retry_request_requeues_the_failures() {
		global $gfpdf;

		wp_clear_scheduled_hook( 'gfpdf_core_font_sync' );
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$gfpdf->notices->clear();

		$this->post_failed_core_fonts_action();
		$this->controller->route();

		try {
			$this->assertSame( 0, $this->get_font_failures()['Garuda.ttf']['gave_up'] );
			$this->assertNotFalse( wp_next_scheduled( 'gfpdf_core_font_sync' ) );
			$this->assertTrue( $gfpdf->notices->has_notice() );
		} finally {
			wp_clear_scheduled_hook( 'gfpdf_core_font_sync' );
			$gfpdf->notices->clear();
		}
	}

	public function test_a_bad_nonce_leaves_the_failures_unretried() {
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->post_failed_core_fonts_action( 'not-a-nonce' );
		$this->controller->route();

		$this->assertArrayHasKey( 'Garuda.ttf', $this->get_font_failures() );
	}

	public function test_a_user_who_cannot_edit_the_settings_cannot_retry_the_failed_core_fonts() {
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->post_failed_core_fonts_action();

		try {
			$this->controller->route();
			$this->fail( 'The request should have been refused' );
		} catch ( \WPDieException $e ) {
			$this->assertArrayHasKey( 'Garuda.ttf', $this->get_font_failures() );
		}
	}

	public function test_a_dismiss_request_with_nothing_failed_stores_nothing() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->post_failed_core_fonts_dismissal();
		$this->controller->route();

		$this->assertFalse( $this->get_font_failures() );
	}

	private function post_failed_core_fonts_dismissal( ?string $nonce = null ): void {
		$this->post_failed_core_fonts_action( $nonce );
		$_POST['gfpdf-dismiss-notice'] = '1';
	}

	private function post_failed_core_fonts_action( ?string $nonce = null ): void {
		$_POST['gfpdf_action']                   = 'gfpdf_failed_core_fonts';
		$_POST['gfpdf_action_failed_core_fonts'] = $nonce ?? wp_create_nonce( 'gfpdf_action_failed_core_fonts' );
	}

	/**
	 * Run $test as a new user with the role, on the admin screen given, then restore the screen and the notices
	 */
	private function with_screen( string $role, callable $test, string $screen = 'dashboard' ): void {
		global $gfpdf, $pagenow;

		$user_id = self::factory()->user->create( [ 'role' => $role ] );
		if ( is_multisite() && $role === 'administrator' ) {
			grant_super_admin( $user_id );
		}
		wp_set_current_user( $user_id );

		$original = $pagenow;
		$pagenow  = 'index.php';
		set_current_screen( $screen );

		try {
			$test();
		} finally {
			$pagenow = $original;
			set_current_screen( 'dashboard' );
			$gfpdf->notices->clear();
		}
	}

	/**
	 * Run the notice routes and render whatever they queue
	 */
	private function render_notices(): string {
		global $gfpdf;

		$gfpdf->notices->clear();
		$this->controller->route_notices();

		return $this->get_rendered_notices();
	}

	/**
	 * Render whatever is queued, since the notices are only markup once WordPress asks for them
	 */
	private function get_rendered_notices(): string {
		ob_start();
		do_action( 'admin_notices' );

		return (string) ob_get_clean();
	}

	public function test_get_routes_is_filterable() {
		add_filter(
			'gfpdf_one_time_action_routes',
			static function ( $routes ) {
				$routes[] = [ 'action' => 'custom' ];

				return $routes;
			}
		);

		$routes = $this->controller->get_routes();
		remove_all_filters( 'gfpdf_one_time_action_routes' );

		$this->assertSame( 'custom', end( $routes )['action'] );
	}

	public function test_route_notices_short_circuits_on_getting_started_page() {
		global $gfpdf;

		$gfpdf->notices->clear();
		$_GET['page'] = 'gfpdf-getting-started';
		set_current_screen( 'gf_settings' );

		$this->controller->route_notices();

		$this->assertFalse( $gfpdf->notices->has_notice() );
	}

	/**
	 * `view_class` is public API carrying a CSS class, and plenty of one-word class names are also PHP function
	 * names — `link`, `key`, `header`. Resolving on `is_callable()` alone would call one and fatal the admin.
	 */
	public function test_a_string_view_class_is_never_called() {
		global $gfpdf;

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'dashboard' );

		add_filter(
			'gfpdf_one_time_action_routes',
			static function () {
				return [
					[
						'action'      => 'always_notify',
						'action_text' => 'Do it',
						'condition'   => '__return_true',
						'process'     => '__return_true',
						'view'        => static function () {
							return 'A notice';
						},
						'view_class'  => 'link',
						'capability'  => 'gravityforms_view_settings',
					],
				];
			}
		);

		$gfpdf->notices->clear();
		$this->controller->route_notices();

		ob_start();
		$gfpdf->notices->process();
		$html = ob_get_clean();

		/* Carried through verbatim as a class, and no `notice-` prefix so it joins the default state */
		$this->assertStringContainsString( 'class="notice updated link"', $html );

		remove_all_filters( 'gfpdf_one_time_action_routes' );
		$gfpdf->notices->clear();
	}

	public function test_route_notices_skips_pages_the_notice_cannot_display_on() {
		global $gfpdf, $pagenow;

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'dashboard' );

		add_filter(
			'gfpdf_one_time_action_routes',
			static function () {
				return [
					[
						'action'      => 'always_notify',
						'action_text' => 'Do it',
						'condition'   => '__return_true',
						'process'     => '__return_true',
						'view'        => static function () {
							return 'A notice';
						},
						'capability'  => 'gravityforms_view_settings',
					],
				];
			}
		);

		$original = $pagenow;
		$pagenow  = 'upload.php';

		$gfpdf->notices->clear();
		$this->controller->route_notices();

		$this->assertFalse( $gfpdf->notices->has_notice() );

		$pagenow = 'index.php';

		$this->controller->route_notices();

		$this->assertTrue( $gfpdf->notices->has_notice() );

		$pagenow = $original;
		remove_all_filters( 'gfpdf_one_time_action_routes' );
		$gfpdf->notices->clear();
	}

	public function test_route_dismisses_notice_when_dismiss_flag_set() {
		global $gfpdf;

		$admin = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );
		set_current_screen( 'edit.php' );

		add_filter(
			'gfpdf_one_time_action_routes',
			static function () {
				return [
					[
						'action'      => 'always_true',
						'action_text' => 'Always',
						'condition'   => '__return_true',
						'process'     => static function () {},
						'view'        => static function () { return ''; },
						'capability'  => 'gravityforms_edit_settings',
					],
				];
			}
		);

		$_POST['gfpdf_action']            = 'gfpdf_always_true';
		$_POST['gfpdf_action_always_true'] = wp_create_nonce( 'gfpdf_action_always_true' );
		$_POST['gfpdf-dismiss-notice']    = '1';

		$model = $gfpdf->singleton->get_class( 'Model_Actions' );
		$this->controller->route();

		remove_all_filters( 'gfpdf_one_time_action_routes' );

		$this->assertTrue( $model->is_notice_already_dismissed( 'always_true' ) );
	}
}
