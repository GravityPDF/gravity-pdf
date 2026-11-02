<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Model\Model_Core_Font_Downloader;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\Seamed_Core_Font_Downloader;
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
class Test_Controller_Core_Font_Downloader extends TestCase {

	use CopiesFontFixtures;

	/**
	 * @var Controller_Core_Font_Downloader
	 */
	protected $controller;

	/**
	 * @var Model_Core_Font_Downloader
	 */
	protected $model;

	/**
	 * @var int
	 */
	protected $form_id;

	public function set_up(): void {
		parent::set_up();

		$this->controller = GPDFAPI::get_mvc_class( 'Controller_Core_Font_Downloader' );
		$this->model      = $this->controller->model;
		wp_clear_scheduled_hook( Model_Core_Font_Downloader::SYNC_EVENT );

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
		wp_clear_scheduled_hook( Model_Core_Font_Downloader::SYNC_EVENT );
		$this->remove_font_fixtures();
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_hooks() {
		$this->assertInstanceOf( Model_Core_Font_Downloader::class, $this->model );
		$this->assertSame( 10, has_action( Model_Core_Font_Downloader::SYNC_EVENT, [ $this->model, 'sync' ] ) );
		$this->assertSame( 10, has_action( Model_Core_Font_Downloader::RETRY_EVENT, [ $this->model, 'retry_failed' ] ) );
		$this->assertSame( 10, has_action( 'upgrader_process_complete', [ $this->controller, 'maybe_sync_translations' ] ) );
		$this->assertSame( 10, has_action( 'add_option_gfpdf_settings', [ $this->controller, 'maybe_sync_default_font' ] ) );
		$this->assertSame( 10, has_action( 'update_option_gfpdf_settings', [ $this->controller, 'maybe_sync_default_font' ] ) );
		$this->assertSame( 10, has_action( 'gfpdf_post_update_pdf', [ $this->controller, 'maybe_sync_pdf_fonts' ] ) );
	}

	/**
	 * @dataProvider provider_lifecycle
	 */
	public function test_the_plugin_lifecycle_schedules_a_sync_without_downloading( string $action, array $args ) {
		$this->assertSame( 10, has_action( $action, [ $this->model, 'schedule_sync' ] ) );
		$this->serve_font_downloads();

		do_action( $action, ...$args );

		$this->assertNotFalse( wp_next_scheduled( Model_Core_Font_Downloader::SYNC_EVENT ) );
		$this->assertSame( [], $this->requested );
	}

	public function provider_lifecycle(): array {
		return [
			'install'         => [ 'gfpdf_plugin_installed', [] ],
			'upgrade'         => [ 'gfpdf_version_changed', [ '6.18.0', PDF_EXTENDED_VERSION ] ],
			'sources changed' => [ 'gfpdf_font_sources_changed', [] ],
		];
	}

	/**
	 * @dataProvider provider_translations
	 *
	 * @param mixed $options
	 */
	public function test_a_core_language_pack_schedules_a_sync( $options, bool $scheduled ) {
		$this->controller->maybe_sync_translations( null, $options );

		$this->assertSame( $scheduled, wp_next_scheduled( Model_Core_Font_Downloader::SYNC_EVENT ) !== false );
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

		$this->assertNotFalse( wp_next_scheduled( Model_Core_Font_Downloader::SYNC_EVENT ) );
	}

	public function test_adding_the_settings_with_a_core_font_schedules_a_sync() {
		delete_option( 'gfpdf_settings' );
		add_option( 'gfpdf_settings', [ 'default_font' => 'freeserif' ] );

		$this->assertNotFalse( wp_next_scheduled( Model_Core_Font_Downloader::SYNC_EVENT ) );
	}

	/**
	 * @dataProvider provider_default_fonts_that_do_not_sync
	 *
	 * @param mixed $settings
	 */
	public function test_a_default_font_with_nothing_to_download_does_not_schedule_a_sync( $settings, bool $on_disk ) {
		$this->add_custom_font();
		if ( $on_disk ) {
			$this->put_freeserif_on_disk();
		}

		$this->controller->maybe_sync_default_font( [], $settings );

		$this->assertFalse( wp_next_scheduled( Model_Core_Font_Downloader::SYNC_EVENT ) );
	}

	public function provider_default_fonts_that_do_not_sync(): array {
		return [
			'already on disk'    => [ [ 'default_font' => 'freeserif' ], true ],
			'the bundled Arimo'  => [ [ 'default_font' => 'arimo' ], false ],
			'a custom font'      => [ [ 'default_font' => 'chewy' ], false ],
			'no default font'    => [ [ 'debug_mode' => 'Yes' ], false ],
			'a non-string font'  => [ [ 'default_font' => [ 'freeserif' ] ], false ],
			'non-array settings' => [ 'freeserif', false ],
		];
	}

	/**
	 * @dataProvider provider_pdf_fonts
	 */
	public function test_a_saved_pdf_with_fonts_to_download_schedules_a_sync( array $settings, bool $on_disk, bool $scheduled ) {
		$this->add_custom_font();
		if ( $on_disk ) {
			$this->put_freeserif_on_disk();
		}

		$this->assertTrue( GPDFAPI::update_pdf( $this->form_id, 'pdf1', $this->pdf( $settings ) ) );

		$this->assertSame( $scheduled, wp_next_scheduled( Model_Core_Font_Downloader::SYNC_EVENT ) !== false );
	}

	public function provider_pdf_fonts(): array {
		return [
			'a core font'                 => [ [ 'font' => 'FreeSerif' ], false, true ],
			'a template font picker'      => [ [ 'sidebar_font' => 'freeserif' ], false, true ],
			'a core font already on disk' => [ [ 'font' => 'freeserif' ], true, false ],
			'a custom font'               => [ [ 'font' => 'chewy' ], false, false ],
			'the bundled Arimo'           => [ [ 'font' => 'arimo' ], false, false ],
			'a non-string font'           => [ [ 'font' => [ 'freeserif' ] ], false, false ],
		];
	}

	public function test_a_new_pdf_with_a_core_font_schedules_a_sync() {
		$this->assertIsString( GPDFAPI::add_pdf( $this->form_id, $this->pdf( [ 'font' => 'freeserif' ] ) ) );

		$this->assertNotFalse( wp_next_scheduled( Model_Core_Font_Downloader::SYNC_EVENT ) );
	}

	public function test_junk_pdf_settings_from_the_action_are_ignored() {
		$this->controller->maybe_sync_pdf_fonts( 'freeserif' );

		$this->assertFalse( wp_next_scheduled( Model_Core_Font_Downloader::SYNC_EVENT ) );
	}

	protected function add_custom_font(): void {
		$this->copy_fixture( 'Chewy.ttf' );
		$this->add_record( 'chewy', [ 'regular' => 'Chewy.ttf' ] );

		/* Saving the record saves the settings, whose default font may have files to download */
		wp_clear_scheduled_hook( Model_Core_Font_Downloader::SYNC_EVENT );
	}

	protected function put_freeserif_on_disk(): void {
		foreach ( Seamed_Core_Font_Downloader::FREESERIF as $file ) {
			$this->copy_fixture( 'Chewy.ttf', $file );
		}
	}

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
