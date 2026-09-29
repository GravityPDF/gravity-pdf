<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Statics\Cache;
use GFPDF\Tests\Concerns\UsesLockableTmpLocation;
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
class Test_Model_Pdf_Cache_Sweep extends TestCase {

	use UsesLockableTmpLocation;

	/** @var Model_Pdf_Cache */
	private $model;

	/** @var Model_Pdf_Cache_With_Slice_Budget */
	private $budgeted;

	/** @var resource[] */
	private $handles = [];

	public function set_up(): void {
		parent::set_up();

		$this->use_lockable_tmp_location();

		$this->model    = $this->new_model( Model_Pdf_Cache::class );
		$this->budgeted = $this->new_model( Model_Pdf_Cache_With_Slice_Budget::class );

		delete_option( Model_Pdf_Cache::SWEEP_STATE_OPTION );
		wp_clear_scheduled_hook( 'gfpdf_cache_sweep' );

		/* spawn_cron()'s loopback */
		add_filter(
			'pre_http_request',
			function () {
				return [
					'headers'  => [],
					'body'     => '',
					'response' => [ 'code' => 200 ],
				];
			}
		);
	}

	public function tear_down(): void {
		foreach ( $this->handles as $handle ) {
			Cache::unlock( $handle );
		}

		wp_clear_scheduled_hook( 'gfpdf_cache_sweep' );
		$this->restore_tmp_location();

		/* A subsite leaves these behind */
		global $wp_settings_errors, $wp_rewrite;
		$wp_settings_errors = [];
		$wp_rewrite->init();

		parent::tear_down();
	}

	public function test_reaps_pdfs_past_the_ttl_and_grace() {
		$ttl = Cache::get_ttl();

		$this->make( 'e1/pa-1/fresh.pdf', 60 );
		$this->make( 'e1/pa-2/in-grace.pdf', $ttl + 1800 );
		$this->make( 'e1/pa-3/expired.pdf', $ttl + HOUR_IN_SECONDS + 60 );
		$this->make( 'e1/pa-3/add-on-file.txt', $ttl + HOUR_IN_SECONDS + 60, 'abc' );

		$state = $this->sweep();

		$this->assertFileExists( $this->root() . 'e1/pa-1/fresh.pdf' );
		$this->assertFileExists( $this->root() . 'e1/pa-2/in-grace.pdf', 'A path handed out just before expiry must survive until it is used' );
		$this->assertFileDoesNotExist( $this->root() . 'e1/pa-3/expired.pdf' );
		$this->assertFileDoesNotExist( $this->root() . 'e1/pa-3/add-on-file.txt' );

		$this->assertSame( 0, $state['pass_started'], 'The pass is complete' );
		$this->assertSame( '', $state['cursor'] );
		$this->assertGreaterThan( 0, $state['last_complete_at'] );
		$this->assertSame( 'expire', $state['last_pass']['mode'] );
		$this->assertSame( 2, $state['last_pass']['files_reaped'] );
		$this->assertSame( strlen( '%PDF' ) + strlen( 'abc' ), $state['last_pass']['bytes_reaped'] );
		$this->assertSame( 2, $state['last_pass']['files_left'] );
		$this->assertSame( 2 * strlen( '%PDF' ), $state['last_pass']['bytes_left'] );
	}

	public function test_sweep_grace_is_filterable() {
		$this->make( 'e1/pa-1/in-grace.pdf', Cache::get_ttl() + 60 );

		add_filter( 'gfpdf_cache_sweep_grace', '__return_zero' );
		$this->sweep();

		$this->assertFileDoesNotExist( $this->root() . 'e1/pa-1/in-grace.pdf' );
	}

	public function test_reaps_abandoned_tmp_files() {
		$this->make( 'e1/pa-1/document.pdf.abc.tmp', 6 * MINUTE_IN_SECONDS );
		$this->make( 'e1/pa-1/document.pdf.def.tmp', MINUTE_IN_SECONDS );

		$this->sweep();

		$this->assertFileDoesNotExist( $this->root() . 'e1/pa-1/document.pdf.abc.tmp' );
		$this->assertFileExists( $this->root() . 'e1/pa-1/document.pdf.def.tmp', 'A PDF may still be being written' );
	}

	public function test_reaps_unheld_orphan_locks_only() {
		$this->make( 'locks/e1-pa-1.lock', 11 * MINUTE_IN_SECONDS, '' );
		$this->make( 'locks/e1-pa-2.lock', 11 * MINUTE_IN_SECONDS, '' );
		$this->make( 'locks/e1-pa-3.lock', MINUTE_IN_SECONDS, '' );

		$this->hold( $this->root() . 'locks/e1-pa-2.lock' );

		$this->sweep();

		$this->assertFileDoesNotExist( $this->root() . 'locks/e1-pa-1.lock' );
		$this->assertFileExists( $this->root() . 'locks/e1-pa-2.lock', 'A render holds this lock' );
		$this->assertFileExists( $this->root() . 'locks/e1-pa-3.lock' );
		$this->assertDirectoryExists( $this->root() . 'locks' );
	}

	public function test_prunes_emptied_directories() {
		$this->make( 'e1/pa-1/expired.pdf', DAY_IN_SECONDS );
		$this->make( 'e2/pa-1/fresh.pdf', 60 );
		$this->make( 'e3/pa-1/index.html', 0, '' );
		$this->make( 'e4/pa-1/index.html', 0, '' );

		$this->age_dirs( [ 'e1/pa-1', 'e1', 'e2/pa-1', 'e2', 'e3/pa-1', 'e3', 'e4' ], HOUR_IN_SECONDS );

		$this->sweep();

		$this->assertDirectoryDoesNotExist( $this->root() . 'e1', 'Emptied by the sweep' );
		$this->assertFileExists( $this->root() . 'e2/pa-1/fresh.pdf' );
		$this->assertDirectoryDoesNotExist( $this->root() . 'e3' );
		$this->assertDirectoryExists( $this->root() . 'e4/pa-1', 'A render may be about to write into a new directory' );
		$this->assertFileExists( $this->root() . 'index.html' );
	}

	public function test_a_cut_slice_resumes_past_its_cursor_and_wraps() {
		foreach ( [ 'e1/pa/a.pdf', 'e1/pa/b.pdf', 'e2/pa/c.pdf', 'e3/pa/d.pdf' ] as $file ) {
			$this->make( $file, 60 );
		}

		/* e1/index.html, then a.pdf and b.pdf */
		$this->budgeted->budget = 3;
		$state                  = $this->budgeted->sweep( $this->model->get_sweep_state(), microtime( true ) + 30 );

		$this->assertSame( 'e1/pa/b.pdf', $state['cursor'] );
		$this->assertGreaterThan( 0, $state['pass_started'], 'The pass is still under way' );
		$this->assertSame( 2, $state['pass']['files_left'] );

		/* The cursor is a position, so it resolves after its directory is gone */
		\GPDFAPI::get_misc_class()->rmdir( $this->root() . 'e1' );

		$state = $this->model->sweep( $state, microtime( true ) + 30 );

		$this->assertSame( 0, $state['pass_started'] );
		$this->assertSame( 4, $state['last_pass']['files_left'], 'The two slices covered the tree without overlap' );

		$this->make( 'e1/pa/a.pdf', 60 );
		$state = $this->model->sweep( $state, microtime( true ) + 30 );

		$this->assertSame( 3, $state['last_pass']['files_left'], 'The next pass starts at the top again' );
	}

	public function test_a_held_gc_lock_makes_the_sweep_a_no_op() {
		$this->make( 'e1/pa-1/expired.pdf', DAY_IN_SECONDS );

		$this->hold( $this->root() . 'gc.lock' );

		$this->assertNull( $this->model->sweep( $this->model->get_sweep_state(), microtime( true ) + 30 ) );
		$this->assertFileExists( $this->root() . 'e1/pa-1/expired.pdf' );

		$this->model->run_sweep_slice();
		$this->assertSame( 0, $this->model->get_sweep_state()['last_slice_at'], 'The losing slice saves nothing' );
	}

	public function test_a_site_without_a_cache_completes_its_pass() {
		$state = $this->model->sweep( $this->model->get_sweep_state(), microtime( true ) + 30 );

		$this->assertSame( 0, $state['pass_started'] );
		$this->assertSame( 0, $state['last_pass']['files_left'] );
		$this->assertDirectoryDoesNotExist( $this->root( false ) );
	}

	public function test_a_cut_short_slice_schedules_one_follow_up() {
		$this->make( 'e1/pa-1/a.pdf', 60 );

		$this->budgeted->budget = 0;
		$this->budgeted->run_sweep_slice();
		$this->budgeted->budget = 0;
		$this->budgeted->run_sweep_slice();

		$this->assertSame( 1, $this->count_scheduled( 'gfpdf_cache_sweep' ) );
		$this->assertGreaterThan( time(), wp_next_scheduled( 'gfpdf_cache_sweep' ) );
		$this->assertGreaterThan( 0, $this->model->get_sweep_state()['pass_started'] );
		$this->assertGreaterThan( 0, $this->model->get_sweep_state()['last_slice_at'] );

		wp_clear_scheduled_hook( 'gfpdf_cache_sweep' );
		$this->model->run_sweep_slice();

		$this->assertFalse( wp_next_scheduled( 'gfpdf_cache_sweep' ), 'A finished pass needs no follow-up' );
	}

	public function test_the_hourly_event_runs_a_slice() {
		$this->make( 'e1/pa-1/expired.pdf', DAY_IN_SECONDS );

		do_action( 'gfpdf_cleanup_tmp_dir' );

		$this->assertFileDoesNotExist( $this->root() . 'e1/pa-1/expired.pdf' );
		$this->assertGreaterThan( 0, $this->model->get_sweep_state()['last_slice_at'] );
	}

	public function test_a_purge_reaps_regardless_of_age_and_supersedes_an_expire_pass() {
		$this->make( 'e1/pa-1/a.pdf', 10 );
		$this->make( 'e2/pa-1/b.pdf', 10 );

		$this->budgeted->budget = 1;
		$this->budgeted->run_sweep_slice();
		$this->assertNotSame( '', $this->model->get_sweep_state()['cursor'] );

		$this->model->request_purge();

		$state = $this->model->get_sweep_state();
		$this->assertSame( 'purge', $state['mode'] );
		$this->assertSame( '', $state['cursor'] );

		/* Saved after the purge was requested, so it belongs to the new generation */
		$this->make( 'e3/pa-1/new.pdf', -5 );

		$this->model->run_sweep_slice();

		$this->assertFileDoesNotExist( $this->root() . 'e1/pa-1/a.pdf' );
		$this->assertFileDoesNotExist( $this->root() . 'e2/pa-1/b.pdf' );
		$this->assertFileExists( $this->root() . 'e3/pa-1/new.pdf' );

		$state = $this->model->get_sweep_state();
		$this->assertSame( 'purge', $state['last_pass']['mode'] );
		$this->assertSame( 2, $state['last_pass']['files_reaped'] );
		$this->assertSame( 'expire', $state['mode'], 'The next pass only expires' );
	}

	public function test_a_repeat_purge_request_is_idempotent() {
		wp_schedule_single_event( time() + 5 * MINUTE_IN_SECONDS, 'gfpdf_cache_sweep' );

		$this->model->request_purge();
		$first = $this->model->get_sweep_state();
		$this->model->request_purge();
		$second = $this->model->get_sweep_state();

		$this->assertSame( 1, $this->count_scheduled( 'gfpdf_cache_sweep' ) );
		$this->assertLessThanOrEqual( time(), wp_next_scheduled( 'gfpdf_cache_sweep' ), 'A queued slice is pulled forward' );

		unset( $first['pass_started'], $first['purge_request'], $second['pass_started'], $second['purge_request'] );
		$this->assertSame( $first, $second );
		$this->assertSame( 'purge', $second['mode'] );
	}

	public function test_a_purge_requested_during_a_slice_is_kept() {
		$this->make( 'e1/pa-1/a.pdf', 60 );

		add_action(
			'gfpdf_cache_sweep_test_mid_slice',
			function () {
				$this->model->request_purge();
			}
		);

		$this->new_model( Model_Pdf_Cache_Purged_Mid_Slice::class )->run_sweep_slice();

		$state = $this->model->get_sweep_state();
		$this->assertSame( 'purge', $state['mode'] );
		$this->assertSame( '', $state['cursor'] );
		$this->assertGreaterThan( 0, $state['last_slice_at'] );
		$this->assertFileExists( $this->root() . 'e1/pa-1/a.pdf', 'The purge hasn\'t run yet' );
	}

	public function test_the_main_site_sweeps_a_site_whose_cron_has_stopped() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not running multisite tests' );
		}

		$neglected = self::factory()->blog->create();
		$healthy   = self::factory()->blog->create();

		foreach ( [ $neglected => time() - 2 * DAY_IN_SECONDS, $healthy => time() - HOUR_IN_SECONDS ] as $blog_id => $last_slice_at ) {
			switch_to_blog( $blog_id );
			$this->make( 'e1/pa-1/expired.pdf', 2 * DAY_IN_SECONDS );
			update_option( Model_Pdf_Cache::SWEEP_STATE_OPTION, [ 'last_slice_at' => $last_slice_at ], false );
			restore_current_blog();
		}

		$deleted_site = \GPDFAPI::get_data_class()->template_tmp_location . '999999/cache/e1/pa-1/';
		wp_mkdir_p( $deleted_site );
		touch( $deleted_site . 'expired.pdf' );

		$this->model->run_scheduled_sweep();

		switch_to_blog( $neglected );
		$this->assertFileDoesNotExist( $this->root() . 'e1/pa-1/expired.pdf' );
		$this->assertTrue( $this->model->get_sweep_state()['fallback'] );
		$this->assertGreaterThan( time() - MINUTE_IN_SECONDS, $this->model->get_sweep_state()['last_slice_at'] );
		restore_current_blog();

		switch_to_blog( $healthy );
		$this->assertFileExists( $this->root() . 'e1/pa-1/expired.pdf', 'Its own cron sweeps it' );
		restore_current_blog();

		$this->assertDirectoryDoesNotExist( dirname( $deleted_site, 2 ), 'A site deleted while Gravity PDF was inactive' );

		/* Taken over until its own cron runs a slice */
		switch_to_blog( $neglected );
		$this->make( 'e2/pa-1/expired.pdf', 2 * DAY_IN_SECONDS );
		restore_current_blog();

		$this->model->run_scheduled_sweep();

		switch_to_blog( $neglected );
		$this->assertFileDoesNotExist( $this->root() . 'e2/pa-1/expired.pdf' );
		$this->model->run_sweep_slice();
		$this->assertFalse( $this->model->get_sweep_state()['fallback'] );
		restore_current_blog();
	}

	public function test_deleting_a_site_deletes_its_pdfs() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not running multisite tests' );
		}

		$blog_id = self::factory()->blog->create();

		switch_to_blog( $blog_id );
		$this->make( 'e1/pa-1/document.pdf', 60 );
		$site_dir = dirname( $this->root(), 1 );
		restore_current_blog();

		wp_delete_site( $blog_id );

		$this->assertDirectoryDoesNotExist( $site_dir );
	}

	private function new_model( string $class_name ): Model_Pdf_Cache {
		global $gfpdf;

		return new $class_name( $gfpdf->log, $gfpdf->data, $gfpdf->misc );
	}

	private function sweep(): array {
		$state = $this->model->sweep( $this->model->get_sweep_state(), microtime( true ) + 30 );
		$this->assertIsArray( $state );

		return $state;
	}

	private function root( bool $create = true ): string {
		return Cache_Paths::root( $create );
	}

	/**
	 * Create a file in the current site's cache, $age seconds old, with an index.html in each new directory
	 */
	private function make( string $relative, int $age, string $content = '%PDF' ): void {
		$root = $this->root();
		$dir  = $root;

		foreach ( array_slice( explode( '/', $relative ), 0, -1 ) as $part ) {
			$dir .= $part . '/';
			if ( ! is_dir( $dir ) ) {
				mkdir( $dir );
				touch( $dir . 'index.html' );
			}
		}

		file_put_contents( $root . $relative, $content );
		touch( $root . $relative, time() - $age );
	}

	private function age_dirs( array $dirs, int $age ): void {
		foreach ( $dirs as $dir ) {
			touch( $this->root() . $dir, time() - $age );
		}
	}

	private function hold( string $file ): void {
		$handle = fopen( $file, 'c' );
		$this->assertTrue( flock( $handle, LOCK_EX ) );

		$this->handles[] = $handle;
	}

	private function count_scheduled( string $hook ): int {
		$count = 0;
		foreach ( _get_cron_array() as $events ) {
			$count += count( $events[ $hook ] ?? [] );
		}

		return $count;
	}
}

class Cache_Paths extends Cache {
	public static function root( bool $create = true ): string {
		return $create ? static::get_basepath() : static::get_root();
	}
}

class Model_Pdf_Cache_With_Slice_Budget extends Model_Pdf_Cache {

	/** @var int How many files a slice reaches before its deadline */
	public $budget = PHP_INT_MAX;

	protected function is_past( $deadline ) {
		return $this->budget-- <= 0;
	}
}

class Model_Pdf_Cache_Purged_Mid_Slice extends Model_Pdf_Cache {
	protected function sweep_dir( $dir, $relative, &$state, $sweep ) {
		$cut = parent::sweep_dir( $dir, $relative, $state, $sweep );

		if ( $relative === '' ) {
			do_action( 'gfpdf_cache_sweep_test_mid_slice' );
		}

		return $cut;
	}
}
