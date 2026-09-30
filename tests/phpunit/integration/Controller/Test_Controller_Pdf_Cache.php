<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFAPI;
use GFFormsModel;
use GFPDF\Model\Model_Pdf_Cache;
use GFPDF\Statics\Cache;
use GFPDF\Tests\Concerns\UsesLockableTmpLocation;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The hooks that purge the PDF cache and run its sweep
 *
 * @group controller
 * @group cache
 */
class Test_Controller_Pdf_Cache extends TestCase {

	use UsesLockableTmpLocation;

	/** @var Model_Pdf_Cache */
	private $model;

	/** @var int */
	private $form_id;

	/** @var array Kept, as the form only exists on the site that made it */
	private $form;

	/** @var int */
	private $entry_id;

	/** @var int */
	private $other_entry_id;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$this->use_lockable_tmp_location();

		$this->model          = new Model_Pdf_Cache( $gfpdf->log, $gfpdf->data, $gfpdf->misc );
		$this->form_id        = (int) $this->gf_factory()->form->create();
		$this->form           = GFAPI::get_form( $this->form_id );
		$this->entry_id       = (int) $this->gf_factory()->entry->create( [ 'form_id' => $this->form_id ] );
		$this->other_entry_id = (int) $this->gf_factory()->entry->create( [ 'form_id' => $this->form_id ] );

		$this->cache_pdf( $this->entry_id );
		$this->cache_pdf( $this->other_entry_id );

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
		wp_clear_scheduled_hook( 'gfpdf_cache_sweep' );
		$this->restore_tmp_location();

		parent::tear_down();
	}

	public function test_actions() {
		$controller = new Controller_Pdf_Cache( $this->model );
		$controller->init();

		$this->assertSame( 10, has_action( 'gfpdf_cleanup_tmp_dir', [ $this->model, 'run_scheduled_sweep' ] ) );
		$this->assertSame( 10, has_action( 'gfpdf_cache_sweep', [ $this->model, 'run_sweep_slice' ] ) );
		$this->assertSame( 10, has_action( 'gform_delete_entry', [ $this->model, 'purge_entry' ] ) );
		$this->assertSame( 10, has_action( 'gform_after_update_entry', [ $controller, 'purge_updated_entry' ] ) );
		$this->assertSame( 10, has_action( 'gform_post_update_entry', [ $controller, 'purge_api_updated_entry' ] ) );
		$this->assertSame( 10, has_action( 'gform_post_update_entry_property', [ $controller, 'purge_entry_property' ] ) );
		$this->assertSame( 10, has_action( 'wp_privacy_personal_data_erased', [ $this->model, 'purge_all' ] ) );
		$this->assertSame( 10, has_action( 'gfpdf_invalidate_entry', [ $this->model, 'purge_entry' ] ) );
		$this->assertSame( 10, has_action( 'gfpdf_invalidate_form', [ Cache::class, 'bump_form_generation' ] ) );
		$this->assertSame( 10, has_action( 'wp_uninitialize_site', [ $this->model, 'delete_site' ] ) );
		$this->assertSame( 10, has_action( 'update_option_gfpdf_settings', [ Cache::class, 'maybe_bump_generation' ] ) );
		$this->assertSame( 10, has_action( 'add_option_gfpdf_settings', [ Cache::class, 'maybe_bump_generation' ] ) );
	}

	public function test_deleting_an_entry_purges_its_pdfs() {
		$lock = Cache::get_lock_file( $this->cache_pdf( $this->entry_id ) );
		touch( $lock );

		GFAPI::delete_entry( $this->entry_id );

		$this->assertPurged( $this->entry_id );
		$this->assertCached( $this->other_entry_id );
		$this->assertFileExists( $lock, 'Locks live outside the entry' );
	}

	public function test_emptying_the_trash_purges_its_pdfs() {
		GFAPI::update_entry_property( $this->entry_id, 'status', 'trash' );
		$this->assertPurged( $this->entry_id, 'Trashing the entry' );

		$this->cache_pdf( $this->entry_id );
		GFFormsModel::delete_entries_by_form( $this->form_id, 'trash' );

		$this->assertPurged( $this->entry_id );
		$this->assertCached( $this->other_entry_id );
	}

	public function test_deleting_the_form_purges_its_entries_pdfs() {
		GFAPI::delete_form( $this->form_id );

		$this->assertPurged( $this->entry_id );
		$this->assertPurged( $this->other_entry_id );
	}

	public function test_updating_an_entry_purges_its_pdfs() {
		GFAPI::update_entry( GFAPI::get_entry( $this->entry_id ) );
		$this->assertPurged( $this->entry_id, 'GFAPI::update_entry()' );

		$this->cache_pdf( $this->entry_id );
		do_action( 'gform_after_update_entry', GFAPI::get_form( $this->form_id ), (string) $this->entry_id, [] );
		$this->assertPurged( $this->entry_id, 'The admin entry editor' );

		$this->assertCached( $this->other_entry_id );
	}

	public function test_reading_or_starring_an_entry_keeps_its_pdfs() {
		GFAPI::update_entry_property( $this->entry_id, 'is_read', 1 );
		GFAPI::update_entry_property( $this->entry_id, 'is_starred', 1 );
		$this->assertCached( $this->entry_id );

		GFAPI::update_entry_property( $this->entry_id, 'payment_status', 'Paid' );
		$this->assertPurged( $this->entry_id );
	}

	public function test_an_entry_can_be_purged_on_request() {
		do_action( 'gfpdf_invalidate_entry', $this->entry_id );
		$this->assertPurged( $this->entry_id );

		$this->cache_pdf( $this->entry_id );
		$this->assertTrue( \GPDFAPI::purge_pdf_cache( $this->entry_id ) );
		$this->assertPurged( $this->entry_id );
		$this->assertTrue( \GPDFAPI::purge_pdf_cache( $this->entry_id ), 'Nothing left to purge' );

		$this->assertCached( $this->other_entry_id );
	}

	public function test_invalidating_a_form_moves_only_its_keys() {
		$other_form_id = $this->form_id + 1;
		$generation    = Cache::get_form_generation( $this->form_id );
		$other         = Cache::get_form_generation( $other_form_id );

		do_action( 'gfpdf_invalidate_form', $this->form_id );

		$this->assertSame( $generation + 1, Cache::get_form_generation( $this->form_id ) );
		$this->assertSame( $other, Cache::get_form_generation( $other_form_id ) );
	}

	public function test_a_personal_data_erasure_bumps_the_generation_and_requests_a_purge() {
		$generation = Cache::get_generation();

		do_action( 'wp_privacy_personal_data_erased', 1 );

		$this->assertSame( $generation + 1, Cache::get_generation() );
		$this->assertSame( 'purge', $this->model->get_sweep_state()['mode'] );
		$this->assertNotFalse( wp_next_scheduled( 'gfpdf_cache_sweep' ) );
	}

	public function test_a_purge_leaves_other_sites_pdfs() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not running multisite tests' );
		}

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		try {
			$other_site = $this->cache_pdf( $this->entry_id );
		} finally {
			restore_current_blog();

			global $wp_settings_errors, $wp_rewrite;
			$wp_settings_errors = [];
			$wp_rewrite->init();
		}

		$this->model->purge_entry( $this->entry_id );

		$this->assertPurged( $this->entry_id );
		$this->assertFileExists( $other_site . 'document.pdf' );
	}

	/**
	 * Cache a PDF for an entry on the current site
	 *
	 * @return string The key directory
	 */
	private function cache_pdf( int $entry_id ): string {
		$pdf_settings = [
			'id'       => 'abc123',
			'template' => 'zadani',
		];

		$path = Cache::get_path( $this->form, [ 'id' => $entry_id, 'form_id' => $this->form_id ], $pdf_settings );
		wp_mkdir_p( $path );
		file_put_contents( $path . 'document.pdf', '%PDF' );

		return $path;
	}

	private function entry_dir( int $entry_id ): string {
		$site = is_multisite() ? get_current_blog_id() . '/' : '';

		return \GPDFAPI::get_data_class()->template_tmp_location . $site . 'cache/e' . $entry_id . '/';
	}

	private function assertPurged( int $entry_id, string $message = '' ): void {
		$this->assertDirectoryDoesNotExist( $this->entry_dir( $entry_id ), $message );
	}

	private function assertCached( int $entry_id ): void {
		$this->assertNotEmpty( glob( $this->entry_dir( $entry_id ) . 'p*/document.pdf' ) );
	}
}
