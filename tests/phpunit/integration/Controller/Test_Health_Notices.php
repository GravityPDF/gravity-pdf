<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Fonts\Install_Queue;
use GFPDF\Helper\Health\Health_Check;
use GFPDF\Helper\Health\Health_Issue;
use GFPDF\Helper\Health\Health_Runner;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\QueuesFontInstalls;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * A check whose issues the test controls
 */
class Noticeable_Check extends Health_Check {

	/**
	 * @var Health_Issue[]
	 */
	public static $issues = [];

	public function get_id(): string {
		return 'noticeable';
	}

	public function get_title(): string {
		return 'Noticeable';
	}

	public function run(): array {
		return static::$issues;
	}

	public function notice_issues( array $report ): array {
		return static::$issues;
	}
}

/**
 * The health notices, and the escape hatch for a queue that stopped
 *
 * @package   GFPDF\Controller
 *
 * @group     helper
 * @group     fonts
 */
class Test_Health_Notices extends TestCase {

	use HasCatalogRows;
	use QueuesFontInstalls;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		Noticeable_Check::$issues = [];

		add_filter( 'gfpdf_health_checks', [ $this, 'register_check' ] );

		/* Every condition gates on being on a Gravity Forms or Gravity PDF screen first */
		$_GET['page'] = 'gf_settings';
	}

	public function tear_down(): void {
		remove_all_filters( 'gfpdf_health_checks' );

		unset( $_GET['page'] );

		$this->drop_catalog_rows();
		$this->install_queue()->clear_queue();

		GPDFAPI::get_options_class()->update_option( 'action_dismissal', [] );
		delete_site_option( Health_Runner::OPTION );

		parent::tear_down();
	}

	public function register_check( array $checks ): array {
		$checks[] = new Noticeable_Check();

		return $checks;
	}

	protected function route( string $action ): array {
		$routes = array_column( GPDFAPI::get_mvc_class( 'Controller_Actions' )->get_routes(), null, 'action' );

		$this->assertArrayHasKey( $action, $routes );

		return $routes[ $action ];
	}

	protected function shows( string $action ): bool {
		return (bool) call_user_func( $this->route( $action )['condition'] );
	}

	protected function issue( string $id ): Health_Issue {
		return new Health_Issue( $id, 'Something is wrong', [ 'a detail' ], 'a consequence', 'Fix it', 'https://example.org' );
	}

	public function test_every_registered_check_gets_a_notice_route() {
		$actions = array_column( GPDFAPI::get_mvc_class( 'Controller_Actions' )->get_routes(), 'action' );

		$this->assertContains( 'health_noticeable', $actions );
		$this->assertContains( 'health_missing_font_files', $actions );
	}

	public function test_a_route_takes_its_audience_from_the_check() {
		$this->assertSame( 'gravityforms_edit_forms', $this->route( 'health_noticeable' )['capability'] );
		$this->assertSame( 'manage_options', $this->route( 'health_catalog_sync' )['capability'] );
		$this->assertSame( 'manage_options', $this->route( 'health_font_downloads' )['capability'] );
	}

	public function test_a_check_with_nothing_to_say_shows_no_notice() {
		$this->assertFalse( $this->shows( 'health_noticeable' ) );
	}

	public function test_a_check_with_something_to_say_shows_a_notice() {
		Noticeable_Check::$issues = [ $this->issue( 'a' ) ];

		$this->assertTrue( $this->shows( 'health_noticeable' ) );
	}

	/**
	 * Conditions can query, and the framework runs them on the dashboard and the plugins screen too
	 */
	public function test_no_notice_is_evaluated_away_from_a_gravity_screen() {
		Noticeable_Check::$issues = [ $this->issue( 'a' ) ];

		$_GET['page'] = 'something-else';

		$this->assertFalse( $this->shows( 'health_noticeable' ) );
	}

	public function test_a_dismissed_notice_stays_dismissed_while_the_problem_is_the_same() {
		Noticeable_Check::$issues = [ $this->issue( 'a' ) ];

		call_user_func( $this->route( 'health_noticeable' )['dismiss'] );

		$this->assertFalse( $this->shows( 'health_noticeable' ) );
	}

	/**
	 * Dismissal is recorded against what the notice said, so tomorrow's problem is not silenced by today's
	 */
	public function test_a_new_issue_shows_the_notice_again() {
		Noticeable_Check::$issues = [ $this->issue( 'a' ) ];
		call_user_func( $this->route( 'health_noticeable' )['dismiss'] );

		Noticeable_Check::$issues = [ $this->issue( 'a' ), $this->issue( 'b' ) ];

		$this->assertTrue( $this->shows( 'health_noticeable' ) );
	}

	public function test_the_notice_names_the_issue_and_its_remedy() {
		Noticeable_Check::$issues = [ $this->issue( 'a' ) ];

		$html = call_user_func( $this->route( 'health_noticeable' )['view'], 'health_noticeable', 'View the system report' );

		$this->assertStringContainsString( 'Something is wrong', $html );
		$this->assertStringContainsString( 'a consequence', $html );
		$this->assertStringContainsString( 'https://example.org', $html );

		/* The details belong in the system report, where there is room for them */
		$this->assertStringNotContainsString( 'a detail', $html );
	}

	/* --- the stalled queue --- */

	protected function stall_an_install(): void {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );

		$this->catalog_repository()->set_status(
			'packs',
			'emoji',
			[
				'phase'       => 'installing',
				'phase_since' => gmdate( 'Y-m-d H:i:s', time() - Install_Queue::STALLED_AFTER - 60 ),
			]
		);
	}

	public function test_a_queue_that_is_moving_shows_no_notice() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->catalog_repository()->set_status( 'packs', 'emoji', [ 'phase' => 'installing' ] );

		$this->assertFalse( $this->shows( 'font_install_stalled' ) );
	}

	public function test_an_install_that_stopped_part_way_shows_a_notice() {
		$this->stall_an_install();

		$this->assertTrue( $this->shows( 'font_install_stalled' ) );
		$this->assertSame( 'manage_options', $this->route( 'font_install_stalled' )['capability'] );
	}

	/**
	 * Something is running it after all, so it is not stuck — it is slow
	 */
	public function test_a_stalled_row_with_the_process_running_shows_nothing() {
		$this->stall_an_install();
		$this->lock_queue();

		$this->assertFalse( $this->shows( 'font_install_stalled' ) );
	}

	/**
	 * "Not today" rather than "never": the site is still broken tomorrow
	 */
	public function test_dismissing_the_stalled_notice_silences_it_for_the_day_only() {
		$this->stall_an_install();

		call_user_func( $this->route( 'font_install_stalled' )['dismiss'] );

		$this->assertFalse( $this->shows( 'font_install_stalled' ) );

		$dismissed = GPDFAPI::get_options_class()->get_option( 'action_dismissal', [] );

		$this->assertArrayHasKey( 'font_install_stalled_' . gmdate( 'Y-m-d' ), $dismissed );
		$this->assertArrayNotHasKey( 'font_install_stalled', $dismissed );
	}

	/**
	 * The whole point: it does the work in this request, because whatever should have done it is not running
	 */
	public function test_run_now_drains_the_outstanding_batch() {
		$this->stall_an_install();
		$this->install_queue()->push_to_queue(
			[
				'source' => 'packs',
				'entry'  => 'emoji',
				'name'   => 'Nothing.ttf',
			]
		);
		$this->install_queue()->save();

		call_user_func( $this->route( 'font_install_stalled' )['process'] );

		$this->assertSame( [], $this->queued() );
	}
}
