<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFAPI;
use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_Font_Downloader;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The triggers that queue the background font sync
 *
 * @group controller
 * @group fonts
 */
class Test_Controller_Font_Downloader extends TestCase {

	use CopiesFontFixtures;

	/**
	 * @var Controller_Font_Downloader
	 */
	protected $controller;

	/**
	 * @var Model_Font_Downloader
	 */
	protected $model;

	/**
	 * @var int
	 */
	protected $form_id;

	public function set_up(): void {
		parent::set_up();

		$this->controller = GPDFAPI::get_mvc_class( 'Controller_Font_Downloader' );
		$this->model      = $this->controller->model;
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );

		$this->form_id = (int) $this->gf_factory()->form->create(
			[],
			[
				'title'               => 'Fonts',
				'fields'              => [],
				'gfpdf_form_settings' => [ 'pdf1' => $this->pdf() ],
			]
		);
	}

	public function tear_down(): void {
		wp_clear_scheduled_hook( Model_Font_Downloader::SYNC_EVENT );
		$this->remove_font_fixtures();
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_hooks() {
		$this->assertInstanceOf( Model_Font_Downloader::class, $this->model );
		$this->assertSame( 10, has_action( Model_Font_Downloader::SYNC_EVENT, [ $this->model, 'sync' ] ) );
		$this->assertSame( 10, has_action( Model_Font_Downloader::CLEANUP_EVENT, [ $this->model, 'cleanup' ] ) );
		$this->assertSame( 10, has_action( 'upgrader_process_complete', [ $this->controller, 'maybe_sync_translations' ] ) );
		$this->assertSame( 10, has_action( 'add_option_gfpdf_settings', [ $this->controller, 'maybe_sync_default_font' ] ) );
		$this->assertSame( 10, has_action( 'update_option_gfpdf_settings', [ $this->controller, 'maybe_sync_default_font' ] ) );
		$this->assertSame( 10, has_action( 'gform_post_update_form_meta', [ $this->controller, 'maybe_sync_form_fonts' ] ) );
	}

	/**
	 * @dataProvider provider_lifecycle
	 */
	public function test_the_plugin_lifecycle_schedules_a_sync_without_downloading( string $action, array $args ) {
		$this->assertSame( 10, has_action( $action, [ $this->model, 'schedule_sync' ] ) );
		$this->serve_font_downloads();

		do_action( $action, ...$args );

		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
		$this->assertSame( [], $this->requested );
	}

	public function provider_lifecycle(): array {
		return [
			'install' => [ 'gfpdf_plugin_installed', [] ],
			'upgrade' => [ 'gfpdf_version_changed', [ '6.18.0', PDF_EXTENDED_VERSION ] ],
		];
	}

	/**
	 * @dataProvider provider_translations
	 *
	 * @param mixed $options
	 */
	public function test_a_core_language_pack_schedules_a_sync( $options, bool $scheduled ) {
		$this->controller->maybe_sync_translations( null, $options );

		$this->assertSame( $scheduled, wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) !== false );
	}

	public function provider_translations(): array {
		$translation = static function ( string $type ): array {
			return [
				'language' => 'th',
				'type'     => $type,
				'slug'     => $type === 'core' ? 'default' : 'akismet',
				'version'  => '1.0',
			];
		};

		return [
			'core'               => [
				[
					'type'         => 'translation',
					'translations' => [ $translation( 'plugin' ), $translation( 'core' ) ],
				],
				true,
			],
			'plugin and theme'   => [
				[
					'type'         => 'translation',
					'translations' => [ $translation( 'plugin' ), $translation( 'theme' ) ],
				],
				false,
			],
			'a plugin update'    => [
				[
					'type'    => 'plugin',
					'plugins' => [ 'akismet/akismet.php' ],
				],
				false,
			],
			'no translations'    => [ [ 'type' => 'translation' ], false ],
			'no options'         => [ null, false ],
			'string options'     => [ 'translation', false ],
			'junk translations'  => [
				[
					'type'         => 'translation',
					'translations' => [ 'core', null ],
				],
				false,
			],
		];
	}

	public function test_saving_a_default_font_with_files_to_download_schedules_a_sync() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'freeserif' );

		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function test_adding_the_settings_with_a_core_font_schedules_a_sync() {
		delete_option( 'gfpdf_settings' );
		add_option( 'gfpdf_settings', [ 'default_font' => 'freeserif' ] );

		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	/**
	 * @dataProvider provider_default_fonts_that_do_not_sync
	 *
	 * @param mixed $settings
	 */
	public function test_a_default_font_with_nothing_to_download_does_not_schedule_a_sync( $settings, bool $on_disk ) {
		if ( $on_disk ) {
			$this->put_freeserif_on_disk();
		}

		$this->controller->maybe_sync_default_font( [], $settings );

		$this->assertFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function provider_default_fonts_that_do_not_sync(): array {
		return [
			'already on disk'    => [ [ 'default_font' => 'freeserif' ], true ],
			'the bundled Arimo'  => [ [ 'default_font' => 'arimo' ], false ],
			'non-array settings' => [ 'freeserif', false ],
		];
	}

	public function test_a_save_that_keeps_the_default_font_does_not_schedule_a_sync() {
		$this->controller->maybe_sync_default_font( [ 'default_font' => 'freeserif' ], [ 'default_font' => 'freeserif', 'debug_mode' => 'Yes' ] );

		$this->assertFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	/**
	 * @dataProvider provider_pdf_fonts
	 */
	public function test_a_saved_pdf_with_fonts_to_download_schedules_a_sync( array $settings, bool $on_disk, bool $scheduled ) {
		if ( $on_disk ) {
			$this->put_freeserif_on_disk();
		}

		$this->assertTrue( GPDFAPI::update_pdf( $this->form_id, 'pdf1', $this->pdf( $settings ) ) );

		$this->assertSame( $scheduled, wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) !== false );
	}

	public function provider_pdf_fonts(): array {
		return [
			'a core font'                 => [ [ 'font' => 'FreeSerif' ], false, true ],
			'a core font already on disk' => [ [ 'font' => 'freeserif' ], true, false ],
			'the bundled Arimo'           => [ [ 'font' => 'arimo' ], false, false ],
		];
	}

	public function test_a_new_pdf_with_a_core_font_schedules_a_sync() {
		$this->assertIsString( GPDFAPI::add_pdf( $this->form_id, $this->pdf( [ 'font' => 'freeserif' ] ) ) );

		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function test_an_imported_form_with_a_core_font_schedules_a_sync() {
		$form                        = GFAPI::get_form( $this->form_id );
		$form['gfpdf_form_settings'] = [ 'pdf1' => $this->pdf( [ 'font' => 'freeserif' ] ) ];

		$this->assertTrue( GFAPI::update_form( $form ) );

		$this->assertNotFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	/**
	 * @dataProvider provider_junk_form_meta
	 *
	 * @param mixed $form_meta
	 * @param mixed $meta_name
	 */
	public function test_junk_form_meta_from_the_action_is_ignored( $form_meta, $meta_name ) {
		$this->controller->maybe_sync_form_fonts( $form_meta, $this->form_id, $meta_name );

		$this->assertFalse( wp_next_scheduled( Model_Font_Downloader::SYNC_EVENT ) );
	}

	public function provider_junk_form_meta(): array {
		$meta = wp_json_encode( [ 'gfpdf_form_settings' => [ [ 'font' => 'freeserif' ] ] ] );

		return [
			'another meta column'  => [ $meta, 'confirmations' ],
			'decoded meta'         => [ [ 'gfpdf_form_settings' => [ [ 'font' => 'freeserif' ] ] ], 'display_meta' ],
			'invalid JSON'         => [ '{"gfpdf_form_settings":', 'display_meta' ],
			'non-array PDF list'   => [ wp_json_encode( [ 'gfpdf_form_settings' => 'freeserif' ] ), 'display_meta' ],
			'non-array PDF'        => [ wp_json_encode( [ 'gfpdf_form_settings' => [ 'freeserif' ] ] ), 'display_meta' ],
		];
	}

	public function test_an_unused_downloaded_font_is_listed_and_deleted_over_rest() {
		$this->set_font_user( true );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$list = $this->dispatch_scope( 'all' );
		$this->assertSame( 200, $list->get_status() );
		$font = wp_list_filter( $list->get_data(), [ 'id' => 'dejavuserifcondensed' ] );
		$this->assertTrue( array_values( $font )[0]['can_delete'] );

		$delete = $this->dispatch_core( 'DELETE', 'dejavuserifcondensed' );
		$this->assertSame( 200, $delete->get_status() );
		$this->assertSame( [ 'DejaVuSerifCondensed.ttf' ], $delete->get_data()['removed'] );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_an_in_use_downloaded_font_is_refused_over_rest() {
		$this->set_font_user( true );
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$this->assertSame( [ 'Default font' ], ( (array) $this->dispatch_scope( 'usage' )->get_data() )['dejavuserifcondensed']['reasons'] );
		$this->assertSame( 409, $this->dispatch_core( 'DELETE', 'dejavuserifcondensed' )->get_status() );
		$this->assertSame( 404, $this->dispatch_core( 'DELETE', 'freeserif' )->get_status() );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_a_user_without_the_capability_cannot_list_or_delete() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$this->assertSame( 403, $this->dispatch_scope( 'all' )->get_status() );
		$this->assertSame( 403, $this->dispatch_core( 'DELETE', 'dejavuserifcondensed' )->get_status() );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_only_a_super_admin_deletes_on_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->set_font_user( false );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$list = $this->dispatch_scope( 'all' );
		$this->assertSame( 200, $list->get_status() );
		$font = wp_list_filter( $list->get_data(), [ 'id' => 'dejavuserifcondensed' ] );
		$this->assertFalse( array_values( $font )[0]['can_delete'] );
		$this->assertSame( 403, $this->dispatch_core( 'POST', 'dejavuserifcondensed' )->get_status() );
		$this->assertSame( 403, $this->dispatch_core( 'DELETE', 'dejavuserifcondensed' )->get_status() );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_the_fonts_route_scope_defaults_to_custom_fonts_and_rejects_others() {
		$this->set_font_user( true );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$this->assertSame( [], $this->dispatch( 'GET', 'fonts' )->get_data() );
		$this->assertContains( 'dejavuserifcondensed', array_column( $this->dispatch_scope( 'all' )->get_data(), 'id' ) );
		$this->assertSame( 400, $this->dispatch_scope( 'nope' )->get_status() );
	}

	public function test_the_install_route_reaches_the_model() {
		$this->set_font_user( true );

		$this->assertSame( 404, $this->dispatch_core( 'POST', 'notafont' )->get_status() );
	}

	/**
	 * Log in a user who can edit forms, a super admin on Multisite when $super
	 */
	protected function set_font_user( bool $super ): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		get_userdata( $user_id )->add_cap( 'gravityforms_edit_forms' );

		if ( $super && is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );
	}

	protected function dispatch_scope( string $scope ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'GET', '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts' );
		$request->set_query_params( [ 'scope' => $scope ] );

		return rest_get_server()->dispatch( $request );
	}

	protected function dispatch_core( string $method, string $key ): \WP_REST_Response {
		$request = new \WP_REST_Request( $method, '/' . Helper_Data::REST_API_BASENAME . 'v1/fonts/' . $key );
		$request->set_query_params( [ 'scope' => 'core' ] );

		return rest_get_server()->dispatch( $request );
	}

	protected function dispatch( string $method, string $route ): \WP_REST_Response {
		return rest_get_server()->dispatch( new \WP_REST_Request( $method, '/' . Helper_Data::REST_API_BASENAME . 'v1/' . $route ) );
	}

	/**
	 * Active PDF settings in the font given by $settings, else Arimo
	 */
	protected function pdf( array $settings = [] ): array {
		return array_merge(
			[
				'id'     => 'pdf1',
				'name'   => 'PDF',
				'font'   => 'arimo',
				'active' => true,
			],
			$settings
		);
	}
}
