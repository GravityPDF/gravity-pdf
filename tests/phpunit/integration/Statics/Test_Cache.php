<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Tests\Concerns\UsesLockableTmpLocation;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group     statics
 */
class Test_Cache extends TestCase {

	use UsesLockableTmpLocation;

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public function tear_down(): void {
		$this->restore_tmp_location();

		/* A subsite leaves these behind */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
	}

	public function test_get_hash() {
		$results = $this->form_and_entry();

		$form  = $results['form'];
		$entry = $results['entry'];

		$pdf_settings             = $form['gfpdf_form_settings']['555ad84787d7e'];
		$pdf_settings['template'] = 'zadani';

		/* Verify the hash is the same when called multiple times with the same inputs */
		$hash1 = Cache::get_hash( $form, $entry, $pdf_settings );
		$hash2 = Cache::get_hash( $form, $entry, $pdf_settings );

		$this->assertSame( $hash1, $hash2 );

		/* Verify the hash changes when the input changes */
		$pdf_settings['active'] = false;

		$hash3 = Cache::get_hash( $form, $entry, $pdf_settings );

		$this->assertNotEquals( $hash3, $hash2 );
	}

	public function test_get_path() {
		$results = $this->form_and_entry();

		$form  = $results['form'];
		$entry = $results['entry'];

		$pdf_settings             = $form['gfpdf_form_settings']['555ad84787d7e'];
		$pdf_settings['template'] = 'zadani';

		$hash1 = Cache::get_hash( $form, $entry, $pdf_settings );
		$path  = Cache::get_path( $form, $entry, $pdf_settings );

		$this->assertStringEndsWith( '/cache/e' . $entry['id'] . '/p' . $pdf_settings['id'] . '-' . $hash1 . '/', $path );
		$this->assertStringStartsWith( ABSPATH, $path );
		$this->assertFileExists( dirname( $path ) . '/index.html', 'Entry directory names are guessable' );
	}

	public function test_lock_files_are_unique_to_the_entry_and_key() {
		$root = dirname( Cache::get_lock_file( 'x' ), 2 ) . '/';

		$this->assertNotSame( Cache::get_lock_file( $root . 'e1/pabc-123/' ), Cache::get_lock_file( $root . 'e2/pabc-123/' ) );
		$this->assertNotSame( Cache::get_lock_file( $root . 'e1/pabc-123/' ), Cache::get_lock_file( $root . 'e1/pdef-123/' ) );
		$this->assertSame( $root . 'locks/e1-pabc-123.lock', Cache::get_lock_file( $root . 'e1/pabc-123/' ) );
	}

	public function test_get_hash_is_false_when_the_key_cannot_be_encoded() {
		global $gfpdf;

		$results = $this->form_and_entry();

		$pdf_settings              = $results['form']['gfpdf_form_settings']['555ad84787d7e'];
		$pdf_settings['font_size'] = INF;

		$handler = new TestHandler();
		$gfpdf->log->pushHandler( $handler );

		try {
			$this->assertFalse( Cache::get_hash( $results['form'], $results['entry'], $pdf_settings ) );
			$this->assertFalse( Cache::get_path( $results['form'], $results['entry'], $pdf_settings ) );
		} finally {
			$gfpdf->log->popHandler();
		}

		$this->assertTrue(
			$handler->hasRecordThatPasses(
				function ( $record ) {
					return strpos( $record['message'], 'cache key could not be built' ) !== false;
				},
				Logger::WARNING
			)
		);
	}

	public function test_get_path_keys_the_requested_template() {
		$results = $this->form_and_entry();

		$form         = $results['form'];
		$entry        = $results['entry'];
		$pdf_settings = $form['gfpdf_form_settings']['555ad84787d7e'];

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$plain_path = Cache::get_path( $form, $entry, $pdf_settings );

		$_GET['template'] = 'rubix';

		try {
			$override_path = Cache::get_path( $form, $entry, $pdf_settings );
		} finally {
			unset( $_GET['template'] );
		}

		$this->assertNotSame( $plain_path, $override_path );
		$this->assertSame( $plain_path, Cache::get_path( $form, $entry, $pdf_settings ) );
	}

	public function test_get_path_is_per_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not running multisite tests' );
		}

		$results = $this->form_and_entry();

		$form         = $results['form'];
		$entry        = $results['entry'];
		$pdf_settings = $form['gfpdf_form_settings']['555ad84787d7e'];

		$blog_id = get_current_blog_id();
		$this->assertStringContainsString( "/$blog_id/cache/", Cache::get_path( $form, $entry, $pdf_settings ) );
		$this->assertStringContainsString( "/$blog_id/uncached/", Cache::get_uncached_path() );

		$other_blog_id = self::factory()->blog->create();
		switch_to_blog( $other_blog_id );

		try {
			$this->assertStringContainsString( "/$other_blog_id/cache/", Cache::get_path( $form, $entry, $pdf_settings ) );
			$this->assertStringContainsString( "/$other_blog_id/uncached/", Cache::get_uncached_path() );
		} finally {
			restore_current_blog();
		}
	}

	public function test_get_uncached_path() {
		$path1 = Cache::get_uncached_path();
		$path2 = Cache::get_uncached_path();

		$this->assertNotSame( $path1, $path2 );
		$this->assertMatchesRegularExpression( '#/uncached/[a-f0-9]{32}/$#', $path1 );
		$this->assertStringStartsWith( $this->gfpdf()->data->template_tmp_location, $path1 );
		$this->assertFileExists( dirname( $path1 ) . '/index.html' );
		$this->assertDirectoryDoesNotExist( $path1 );
	}

	public function test_is_hit() {
		$file = get_temp_dir() . 'gfpdf-cache-is-hit-' . uniqid() . '.pdf';

		try {
			$this->assertFalse( Cache::is_hit( $file ), 'a missing file is a miss' );

			touch( $file );
			$this->assertFalse( Cache::is_hit( $file ), 'an empty file is a miss' );

			file_put_contents( $file, '%PDF-' );
			clearstatcache( true, $file );
			$this->assertTrue( Cache::is_hit( $file ) );

			touch( $file, time() - Cache::get_ttl() - 1 );
			clearstatcache( true, $file );
			$this->assertFalse( Cache::is_hit( $file ), 'a file older than the TTL is a miss' );
		} finally {
			@unlink( $file );
		}
	}
	public function test_lock_is_held_until_unlocked() {
		$path = $this->key_path();
		$lock = Cache::lock( $path );

		try {
			$this->assertIsResource( $lock );
			$this->assertFileExists( Cache::get_lock_file( $path ) );
			$this->assertStringNotContainsString( untrailingslashit( $path ), Cache::get_lock_file( $path ), 'Purging a key must not delete its lock' );
			$this->assertFileExists( dirname( Cache::get_lock_file( $path ) ) . '/index.html' );

			add_filter( 'gfpdf_cache_lock_wait', '__return_zero' );
			$this->assertNull( Cache::lock( $path ), 'A held lock cannot be taken again' );
		} finally {
			Cache::unlock( $lock );
		}

		$lock = Cache::lock( $path );
		$this->assertIsResource( $lock );
		Cache::unlock( $lock );

		Cache::unlock( null );
	}

	public function test_lock_gives_up_after_the_wait() {
		$path = $this->key_path();
		$lock = Cache::lock( $path );

		add_filter(
			'gfpdf_cache_lock_wait',
			function () {
				return 0.3;
			}
		);

		try {
			$started = microtime( true );
			$this->assertNull( Cache::lock( $path ) );
			$waited = microtime( true ) - $started;
		} finally {
			Cache::unlock( $lock );
		}

		$this->assertGreaterThanOrEqual( 0.3, $waited );
		$this->assertLessThan( 2, $waited );
	}

	public function test_lock_wait_cannot_be_negative() {
		$path = $this->key_path();
		$lock = Cache::lock( $path );

		add_filter(
			'gfpdf_cache_lock_wait',
			function () {
				return -10;
			}
		);

		try {
			$started = microtime( true );
			$this->assertNull( Cache::lock( $path ) );
			$this->assertLessThan( 0.5, microtime( true ) - $started );
		} finally {
			Cache::unlock( $lock );
		}
	}

	public function test_lock_does_not_wait_when_the_filesystem_cannot_lock() {
		$started = microtime( true );

		$this->assertNull( Cache_Without_Flock::lock( $this->key_path() ) );
		$this->assertLessThan( 1, microtime( true ) - $started );
	}

	/**
	 * @dataProvider provider_key_components
	 */
	public function test_each_key_component_moves_the_key( callable $change ) {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		list( $form, $entry, $pdf_settings ) = $this->key_inputs();

		$baseline = Cache::get_hash( $form, $entry, $pdf_settings );
		$this->assertSame( $baseline, Cache::get_hash( $form, $entry, $pdf_settings ), 'Identical inputs give an identical key' );

		$restore = $change( $form, $entry, $pdf_settings );

		try {
			$this->assertNotSame( $baseline, Cache::get_hash( $form, $entry, $pdf_settings ) );
		} finally {
			if ( is_callable( $restore ) ) {
				$restore();
			}
		}
	}

	public function provider_key_components(): array {
		return [
			'entry value'              => [
				function ( &$form, &$entry ) {
					$entry['1'] = 'Changed';
				},
			],
			'PDF settings'             => [
				function ( &$form, &$entry, &$pdf_settings ) {
					$pdf_settings['font_size'] = 20;
				},
			],
			'form title'               => [
				function ( &$form ) {
					$form['title'] .= ' changed';
				},
			],
			'add-on settings on form'  => [
				function ( &$form ) {
					$form['gravityformsquiz'] = [ 'grades' => 'letter' ];
				},
			],
			'field'                    => [
				function ( &$form ) {
					$form['fields'][0]        = clone $form['fields'][0];
					$form['fields'][0]->label = 'Changed';
				},
			],
			'Gravity Forms version'    => [
				function () {
					$version            = \GFForms::$version;
					\GFForms::$version .= '.1';

					return function () use ( $version ) {
						\GFForms::$version = $version;
					};
				},
			],
			'generation'               => [
				function () {
					Cache::bump_generation();
				},
			],
			'form generation'          => [
				function ( &$form ) {
					Cache::bump_form_generation( $form['id'] );
				},
			],
			'date format'              => [
				function () {
					update_option( 'date_format', 'd/m/Y' );
				},
			],
			'timezone'                 => [
				function () {
					update_option( 'timezone_string', 'Australia/Sydney' );
				},
			],
			'site title'               => [
				function () {
					update_option( 'blogname', 'Changed' );
				},
			],
			'Gravity Forms currency'   => [
				function () {
					update_option( 'rg_gforms_currency', 'EUR' );
				},
			],
			'user'                     => [
				function () {
					wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );
				},
			],
			'roles'                    => [
				function () {
					wp_get_current_user()->set_role( 'author' );
				},
			],
			'locale'                   => [
				function () {
					add_filter(
						'pre_determine_locale',
						function () {
							return 'fr_FR';
						}
					);
				},
			],
			'extra'                    => [
				function () {
					add_filter(
						'gfpdf_cache_hash_extra',
						function ( $extra ) {
							$extra['my_addon'] = '1.2.3';

							return $extra;
						}
					);
				},
			],
		];
	}

	public function test_extra_cannot_remove_the_viewer() {
		list( $form, $entry, $pdf_settings ) = $this->key_inputs();

		add_filter(
			'gfpdf_cache_hash_extra',
			function () {
				return [
					'user_id' => 0,
					'roles'   => [],
				];
			}
		);

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$admin_hash = Cache::get_hash( $form, $entry, $pdf_settings );

		wp_set_current_user( 0 );
		$this->assertNotSame( $admin_hash, Cache::get_hash( $form, $entry, $pdf_settings ) );
	}

	public function test_extra_is_order_independent() {
		list( $form, $entry, $pdf_settings ) = $this->key_inputs();

		$add = function ( $key ) {
			return function ( $extra ) use ( $key ) {
				$extra[ $key ] = $key;

				return $extra;
			};
		};

		add_filter( 'gfpdf_cache_hash_extra', $add( 'a' ) );
		add_filter( 'gfpdf_cache_hash_extra', $add( 'b' ) );
		$hash = Cache::get_hash( $form, $entry, $pdf_settings );

		remove_all_filters( 'gfpdf_cache_hash_extra' );
		add_filter( 'gfpdf_cache_hash_extra', $add( 'b' ) );
		add_filter( 'gfpdf_cache_hash_extra', $add( 'a' ) );

		$this->assertSame( $hash, Cache::get_hash( $form, $entry, $pdf_settings ) );
	}

	public function test_ignored_entry_meta_and_form_keys_do_not_move_the_key() {
		list( $form, $entry, $pdf_settings ) = $this->key_inputs();

		$baseline = Cache::get_hash( $form, $entry, $pdf_settings );

		$entry['is_read']    = 1;
		$entry['is_starred'] = 1;
		$entry['status']     = 'trash';

		$form['notifications']    = [ 'abc' => [ 'name' => 'Changed' ] ];
		$form['confirmations']    = [ 'abc' => [ 'name' => 'Changed' ] ];
		$form['date_created']     = '2001-01-01 00:00:00';
		$form['is_active']        = '0';
		$form['is_trash']         = '1';
		$form['confirmation']     = [ 'type' => 'message' ];
		$form['page_instance']    = 2;
		$form['gfpdf_form_settings']['another-pdf'] = [ 'name' => 'Another PDF' ];

		$this->assertSame( $baseline, Cache::get_hash( $form, $entry, $pdf_settings ) );

		add_filter(
			'gfpdf_cache_hash_ignored_form_keys',
			function ( $keys ) {
				$keys[] = 'title';

				return $keys;
			}
		);

		$baseline      = Cache::get_hash( $form, $entry, $pdf_settings );
		$form['title'] = 'Changed';
		$this->assertSame( $baseline, Cache::get_hash( $form, $entry, $pdf_settings ) );
	}

	public function test_bumping_a_form_generation_only_moves_that_form() {
		list( $form, $entry, $pdf_settings ) = $this->key_inputs();

		$other_form       = $form;
		$other_form['id'] = $form['id'] + 1000;

		$form_hash  = Cache::get_hash( $form, $entry, $pdf_settings );
		$other_hash = Cache::get_hash( $other_form, $entry, $pdf_settings );

		Cache::bump_form_generation( $form['id'] );

		$this->assertNotSame( $form_hash, Cache::get_hash( $form, $entry, $pdf_settings ) );
		$this->assertSame( $other_hash, Cache::get_hash( $other_form, $entry, $pdf_settings ) );
	}

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

	public function test_generation_options_are_not_autoloaded() {
		Cache::bump_generation();
		Cache::bump_form_generation( 1 );

		$autoloaded = wp_load_alloptions();
		$this->assertArrayNotHasKey( 'gfpdf_cache_generation', $autoloaded );
		$this->assertArrayNotHasKey( 'gfpdf_cache_form_generation', $autoloaded );
	}

	public function test_ttl_is_filterable_and_at_least_one_second() {
		add_filter(
			'gfpdf_cache_ttl',
			function () {
				return 60;
			}
		);

		$this->assertSame( 60, Cache::get_ttl() );

		add_filter( 'gfpdf_cache_ttl', '__return_zero', 20 );
		$this->assertSame( 1, Cache::get_ttl() );
	}

	public function test_is_enabled_is_filterable() {
		list( $form, $entry, $pdf_settings ) = $this->key_inputs();

		$this->assertTrue( Cache::is_enabled( $form, $entry, $pdf_settings ) );

		add_filter( 'gfpdf_enable_pdf_cache', '__return_false' );
		$this->assertFalse( Cache::is_enabled( $form, $entry, $pdf_settings ) );
	}

	private function key_inputs(): array {
		$results = $this->form_and_entry();

		return [ $results['form'], $results['entry'], $results['form']['gfpdf_form_settings']['555ad84787d7e'] ];
	}

	private function key_path(): string {
		$this->use_lockable_tmp_location();

		$results      = $this->form_and_entry();
		$pdf_settings = $results['form']['gfpdf_form_settings']['555ad84787d7e'];

		$pdf_settings['lock_test'] = uniqid( '', true );

		return Cache::get_path( $results['form'], $results['entry'], $pdf_settings );
	}
}

class Cache_Without_Flock extends Cache {
	protected static function try_lock( $fp, &$wouldblock ) {
		$wouldblock = 0;

		return false;
	}
}
