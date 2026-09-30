<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

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

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public function tear_down(): void {
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

		$this->assertStringEndsWith( '/' . $hash1 . '/', $path );
		$this->assertStringStartsWith( ABSPATH, $path );
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
}
