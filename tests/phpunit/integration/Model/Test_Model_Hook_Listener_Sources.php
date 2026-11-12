<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group model
 * @group system-report
 */
class Test_Model_Hook_Listener_Sources extends TestCase {

	/**
	 * @var Model_Hook_Listener_Sources
	 */
	protected $sources;

	/**
	 * @var string
	 */
	protected $theme_root = '';

	/**
	 * @var array
	 */
	protected $plugin_paths_backup;

	public function set_up(): void {
		parent::set_up();

		$this->sources             = new Model_Hook_Listener_Sources();
		$this->plugin_paths_backup = $GLOBALS['wp_plugin_paths'] ?? [];

		wp_cache_set(
			'plugins',
			[
				'' => [
					'gpdf-fake-addon/gpdf-fake-addon.php' => [ 'Name' => 'Fake Add-on' ],
					'gpdf-single-file.php'                => [ 'Name' => 'Single File Plugin' ],
					'gravity-pdf/pdf.php'                 => [ 'Name' => 'Gravity PDF' ],
				],
			],
			'plugins'
		);
	}

	public function tear_down(): void {
		wp_cache_delete( 'plugins', 'plugins' );
		$GLOBALS['wp_plugin_paths'] = $this->plugin_paths_backup;

		if ( $this->theme_root !== '' ) {
			$GLOBALS['wp_theme_directories'] = array_values( array_diff( $GLOBALS['wp_theme_directories'], [ $this->theme_root ] ) );
			unlink( $this->theme_root . '/gpdf-fake-theme/style.css' );
			array_map( 'rmdir', [ $this->theme_root . '/gpdf-fake-theme/inc', $this->theme_root . '/gpdf-fake-theme', $this->theme_root ] );
		}

		parent::tear_down();
	}

	/**
	 * @dataProvider provider_callbacks
	 */
	public function test_each_callback_shape_is_traced_to_this_file( $callback ) {
		$this->assertSame( wp_normalize_path( __FILE__ ), $this->sources->get_callback_file( $callback ) );
	}

	public function provider_callbacks(): array {
		return [
			'function name'          => [ __NAMESPACE__ . '\gpdf_listener_sources_function' ],
			'Class::method string'   => [ Listener_Sources_Fixture::class . '::static_listener' ],
			'[ object, method ]'     => [ [ new Listener_Sources_Fixture(), 'listener' ] ],
			'[ class, method ]'      => [ [ Listener_Sources_Fixture::class, 'static_listener' ] ],
			'closure'                => [
				static function ( $value ) {
					return $value;
				},
			],
			'invokable object'       => [ new Listener_Sources_Fixture() ],
		];
	}

	public function test_a_callback_with_no_file_is_untraced() {
		$this->assertSame( '', $this->sources->get_callback_file( 'strtolower' ) );
		$this->assertSame( '', $this->sources->get_callback_file( 'gpdf_listener_sources_missing_function' ) );
		$this->assertSame( '', $this->sources->get_callback_file( [ 'GPDF_Listener_Sources_Missing_Class', 'run' ] ) );
		$this->assertSame( '', $this->sources->get_callback_file( null ) );
	}

	public function test_a_wordpress_function_falls_back_to_no_source() {
		$this->assertStringEndsWith( 'wp-includes/functions.php', $this->sources->get_callback_file( '__return_empty_array' ) );
		$this->assertSame( '', $this->sources->get_source( '__return_empty_array' ) );
		$this->assertSame( '', $this->sources->get_source( 'strtolower' ) );
	}

	public function test_a_plugin_file_is_named_by_its_header() {
		$this->assertSame( 'Fake Add-on', $this->sources->get_file_source( WP_PLUGIN_DIR . '/gpdf-fake-addon/vendor/lib/class-shared.php' ) );
		$this->assertSame( 'Single File Plugin', $this->sources->get_file_source( WP_PLUGIN_DIR . '/gpdf-single-file.php' ) );
		$this->assertSame( '', $this->sources->get_file_source( WP_PLUGIN_DIR . '/gpdf-not-installed/file.php' ) );
	}

	public function test_a_symlinked_plugin_is_named_by_its_header() {
		$GLOBALS['wp_plugin_paths'] = [ wp_normalize_path( WP_PLUGIN_DIR . '/gpdf-fake-addon' ) => '/srv/elsewhere/gpdf-fake-addon' ];

		$this->assertSame( 'Fake Add-on', $this->sources->get_file_source( '/srv/elsewhere/gpdf-fake-addon/listener.php' ) );
	}

	public function test_a_must_use_plugin_is_named_by_its_file() {
		$this->assertSame( 'gpdf-mu-listener.php', $this->sources->get_file_source( WPMU_PLUGIN_DIR . '/gpdf-mu-listener.php' ) );
		$this->assertSame( 'gpdf-mu-dir', $this->sources->get_file_source( WPMU_PLUGIN_DIR . '/gpdf-mu-dir/listener.php' ) );
	}

	public function test_a_theme_file_is_named_by_the_theme() {
		$this->theme_root = wp_normalize_path( get_temp_dir() . 'gpdf-listener-themes-' . uniqid() );
		wp_mkdir_p( $this->theme_root . '/gpdf-fake-theme/inc' );
		file_put_contents( $this->theme_root . '/gpdf-fake-theme/style.css', "/*\nTheme Name: Fake Theme\n*/" );
		register_theme_directory( $this->theme_root );

		$this->assertSame( 'Fake Theme', $this->sources->get_file_source( $this->theme_root . '/gpdf-fake-theme/inc/hooks.php' ) );
		$this->assertSame( '', $this->sources->get_file_source( $this->theme_root . '/gpdf-missing-theme/functions.php' ) );
	}

	public function test_a_file_elsewhere_has_no_source() {
		$this->assertSame( '', $this->sources->get_file_source( ABSPATH . 'wp-includes/plugin.php' ) );
		$this->assertSame( '', $this->sources->get_file_source( '/srv/custom/listener.php' ) );
	}

	public function test_sources_are_listed_once_in_callback_order() {
		$closure = static function ( $value ) {
			return $value;
		};

		/* This file is inside the Gravity PDF plugin folder */
		$this->assertSame(
			[ '', 'Gravity PDF' ],
			$this->sources->get_sources( [ '__return_empty_array', $closure, 'strtolower', [ new Listener_Sources_Fixture(), 'listener' ] ] )
		);
	}
}

/**
 * Callbacks of each shape, defined in this file
 */
class Listener_Sources_Fixture {
	public function __invoke( $value ) {
		return $value;
	}

	public function listener( $value ) {
		return $value;
	}

	public static function static_listener( $value ) {
		return $value;
	}
}

function gpdf_listener_sources_function( $value ) {
	return $value;
}
