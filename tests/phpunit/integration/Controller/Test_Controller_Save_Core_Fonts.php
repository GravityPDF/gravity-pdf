<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Model\Model_Core_Font_Downloader;
use GFPDF\Tests\Concerns\UnregistersMvcClasses;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;
use ReflectionMethod;
use ReflectionProperty;

/**
 * The downloads themselves are tested in Test_Model_Core_Font_Downloader
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   core-fonts
 */
class Test_Controller_Save_Core_Fonts extends TestCase {

	use UnregistersMvcClasses;

	/**
	 * @var Controller_Save_Core_Fonts
	 */
	private $controller;

	/**
	 * @var Model_Core_Font_Downloader&object{downloads: array<int, array{0: string, 1: bool}>}
	 */
	private $downloader;

	/**
	 * @var array|null The singleton's classes before a test swapped the downloader
	 */
	private $registered;

	public function set_up(): void {
		parent::set_up();

		$this->downloader = new class( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ), GPDFAPI::get_data_class(), GPDFAPI::get_options_class(), GPDFAPI::get_misc_class(), GPDFAPI::get_log_class() ) extends Model_Core_Font_Downloader {
			/** @var array<int, array{0: string, 1: bool}> The files the controller asked for, and whether it forced them */
			public $downloads = [];

			public function download( string $file, bool $force = false ): bool {
				$this->downloads[] = [ $file, $force ];

				return $file === 'DejaVuSerifCondensed.ttf';
			}
		};

		$this->controller = new Controller_Save_Core_Fonts( GPDFAPI::get_log_class(), GPDFAPI::get_data_class(), GPDFAPI::get_misc_class(), $this->downloader );
	}

	public function tear_down(): void {
		global $gfpdf;

		unset( $_POST['font_name'] );

		if ( $this->registered !== null ) {
			$classes = new ReflectionProperty( get_class( $gfpdf->singleton ), 'classes' );
			if ( PHP_VERSION_ID < 80100 ) {
				$classes->setAccessible( true );
			}
			$classes->setValue( $gfpdf->singleton, $this->registered );
		}

		parent::tear_down();
	}

	public function test_init_registers_ajax_endpoint() {
		remove_all_actions( 'wp_ajax_gfpdf_save_core_font' );

		$this->controller->init();

		$this->assertNotFalse( has_action( 'wp_ajax_gfpdf_save_core_font', [ $this->controller, 'save_core_font' ] ) );
	}

	public function test_download_forces_the_requested_font_so_a_corrupt_copy_is_replaced() {
		$_POST['font_name'] = 'DejaVuSerifCondensed.ttf';

		$this->assertTrue( $this->invoke_download() );
		$this->assertSame( [ [ 'DejaVuSerifCondensed.ttf', true ] ], $this->downloader->downloads );
	}

	public function test_download_returns_the_downloaders_failure() {
		$_POST['font_name'] = 'NotARealFont.ttf';

		$this->assertFalse( $this->invoke_download() );
	}

	public function test_download_with_no_font_name_asks_for_nothing_named() {
		unset( $_POST['font_name'] );

		$this->assertFalse( $this->invoke_download() );
		$this->assertSame( [ [ '', true ] ], $this->downloader->downloads );
	}

	public function test_a_font_name_that_is_not_a_string_asks_for_nothing_named() {
		$_POST['font_name'] = [ 'DejaVuSerifCondensed.ttf' ];

		$this->assertFalse( $this->invoke_download() );
		$this->assertSame( [ [ '', true ] ], $this->downloader->downloads );
	}

	public function test_without_a_downloader_it_uses_the_registered_one() {
		$this->set_registered_downloader( $this->downloader );
		$this->controller = new Controller_Save_Core_Fonts( GPDFAPI::get_log_class(), GPDFAPI::get_data_class(), GPDFAPI::get_misc_class() );
		$_POST['font_name'] = 'DejaVuSerifCondensed.ttf';

		$this->assertTrue( $this->invoke_download() );
		$this->assertSame( [ [ 'DejaVuSerifCondensed.ttf', true ] ], $this->downloader->downloads );
	}

	public function test_without_any_downloader_the_download_fails() {
		$controller         = new Controller_Save_Core_Fonts( GPDFAPI::get_log_class(), GPDFAPI::get_data_class(), GPDFAPI::get_misc_class() );
		$_POST['font_name'] = 'DejaVuSerifCondensed.ttf';

		$this->assertFalse(
			$this->without_mvc_class(
				'Model_Core_Font_Downloader',
				function () use ( $controller ) {
					$this->controller = $controller;

					return $this->invoke_download();
				}
			)
		);
	}

	/**
	 * Swap the singleton's downloader for this test; tear_down() puts the original back
	 */
	private function set_registered_downloader( ?Model_Core_Font_Downloader $downloader ): void {
		global $gfpdf;

		$classes = new ReflectionProperty( get_class( $gfpdf->singleton ), 'classes' );
		if ( PHP_VERSION_ID < 80100 ) {
			$classes->setAccessible( true );
		}

		$registered       = $classes->getValue( $gfpdf->singleton );
		$this->registered = $this->registered ?? $registered;

		$registered['Model_Core_Font_Downloader'] = $downloader;
		$classes->setValue( $gfpdf->singleton, array_filter( $registered ) );
	}

	private function invoke_download() {
		$ref = new ReflectionMethod( $this->controller, 'download_and_save_font' );
		if ( PHP_VERSION_ID < 80100 ) {
			$ref->setAccessible( true );
		}

		return $ref->invoke( $this->controller );
	}
}
