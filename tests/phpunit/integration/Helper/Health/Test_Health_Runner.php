<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Health;

use GFPDF\Fonts\Font_Lock;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;
use RuntimeException;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * A check that answers whatever the test told it to
 */
class Fake_Check extends Health_Check {

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var Health_Issue[]|null Null throws
	 */
	public $issues;

	/**
	 * @var int
	 */
	public $runs = 0;

	public function __construct( string $id, ?array $issues = [] ) {
		$this->id     = $id;
		$this->issues = $issues;
	}

	public function get_id(): string {
		return $this->id;
	}

	public function get_title(): string {
		return 'Fake ' . $this->id;
	}

	public function run(): array {
		++$this->runs;

		if ( $this->issues === null ) {
			throw new RuntimeException( 'this check is broken' );
		}

		return $this->issues;
	}
}

/**
 * The runner, with checks that are not fonts
 *
 * @package   GFPDF\Helper\Health
 *
 * @group     helper
 */
class Test_Health_Runner extends TestCase {

	public function tear_down(): void {
		remove_all_filters( 'gfpdf_health_checks' );

		delete_site_option( Health_Runner::OPTION );
		( new Font_Lock() )->release( Health_Runner::LOCK );

		parent::tear_down();
	}

	/**
	 * @param Health_Check_Interface[] $checks
	 */
	protected function runner( array $checks = [] ): Health_Runner {
		return new Health_Runner( $checks, new Font_Lock(), GPDFAPI::get_log_class() );
	}

	protected function issue( string $id ): Health_Issue {
		return new Health_Issue( $id, 'Something is wrong', [ 'a detail' ], 'a consequence', 'Fix it', 'https://example.org' );
	}

	public function test_a_run_writes_every_checks_issues_and_when_it_ran() {
		$this->assertTrue( $this->runner( [ new Fake_Check( 'one', [ $this->issue( 'a' ) ] ) ] )->run() );

		$report = $this->runner()->get_report();

		$this->assertGreaterThan( 0, $report['last_run'] );

		$issues = Health_Runner::issues_in( $report, 'one' );

		$this->assertCount( 1, $issues );
		$this->assertSame( 'a', $issues[0]->get_id() );
		$this->assertSame( 'a consequence', $issues[0]->get_consequence() );
		$this->assertSame( [ 'a detail' ], $issues[0]->get_details() );
	}

	public function test_a_check_that_throws_is_skipped_and_the_others_still_run() {
		$good = new Fake_Check( 'good', [ $this->issue( 'a' ) ] );

		$this->runner( [ new Fake_Check( 'broken', null ), $good ] )->run();

		$this->assertSame( 1, $good->runs );
		$this->assertCount( 1, Health_Runner::issues_in( $this->runner()->get_report(), 'good' ) );
	}

	/**
	 * A check that threw has said nothing, which is not the same as saying the problem is gone
	 */
	public function test_a_check_that_throws_keeps_what_it_last_said() {
		$this->runner( [ new Fake_Check( 'one', [ $this->issue( 'a' ) ] ) ] )->run();
		$this->runner( [ new Fake_Check( 'one', null ) ] )->run();

		$this->assertCount( 1, Health_Runner::issues_in( $this->runner()->get_report(), 'one' ) );
	}

	public function test_a_check_that_finds_nothing_clears_what_it_last_said() {
		$this->runner( [ new Fake_Check( 'one', [ $this->issue( 'a' ) ] ) ] )->run();
		$this->runner( [ new Fake_Check( 'one', [] ) ] )->run();

		$this->assertSame( [], Health_Runner::issues_in( $this->runner()->get_report(), 'one' ) );
	}

	/**
	 * The unit of change for a dismissed notice: an issue that has been there all along is not news
	 */
	public function test_an_issue_keeps_the_time_it_was_first_seen() {
		$this->runner( [ new Fake_Check( 'one', [ $this->issue( 'a' ) ] ) ] )->run();

		/* Backdated, because a run and the run after it happen in the same second here */
		$first = $this->backdate_first_seen( 'one', 'a' );

		$this->runner( [ new Fake_Check( 'one', [ $this->issue( 'a' ), $this->issue( 'b' ) ] ) ] )->run();

		$second = Health_Runner::first_seen_in( $this->runner()->get_report(), 'one' );

		$this->assertSame( $first, $second['a'] );
		$this->assertSame( time(), $second['b'] );
	}

	/**
	 * An issue that cleared and came back is a new problem, whatever its id says
	 */
	public function test_an_issue_that_cleared_and_returned_is_first_seen_again() {
		$this->runner( [ new Fake_Check( 'one', [ $this->issue( 'a' ) ] ) ] )->run();

		/* Backdated, because a run and the run after it happen in the same second here */
		$first = $this->backdate_first_seen( 'one', 'a' );

		$this->runner( [ new Fake_Check( 'one', [] ) ] )->run();
		$this->runner( [ new Fake_Check( 'one', [ $this->issue( 'a' ) ] ) ] )->run();

		$this->assertGreaterThan( $first, Health_Runner::first_seen_in( $this->runner()->get_report(), 'one' )['a'] );
	}

	/**
	 * @return int The timestamp it now claims
	 */
	protected function backdate_first_seen( string $check, string $issue ): int {
		$report = $this->runner()->get_report();
		$at     = time() - 600;

		$report['checks'][ $check ]['first_seen'][ $issue ] = $at;

		update_site_option( Health_Runner::OPTION, $report );

		return $at;
	}

	public function test_a_second_run_inside_a_day_does_nothing() {
		$check = new Fake_Check( 'one', [] );

		$this->assertTrue( $this->runner( [ $check ] )->maybe_run() );
		$this->assertFalse( $this->runner( [ $check ] )->maybe_run() );
		$this->assertSame( 1, $check->runs );
	}

	public function test_a_run_a_day_later_happens() {
		$check = new Fake_Check( 'one', [] );

		$this->runner( [ $check ] )->maybe_run();
		$this->set_report_last_run( time() - Health_Runner::INTERVAL - 1 );

		$this->assertTrue( $this->runner( [ $check ] )->maybe_run() );
		$this->assertSame( 2, $check->runs );
	}

	/**
	 * Two requests reaching the hourly listener together must not both walk every check
	 */
	public function test_a_run_while_another_holds_the_lock_does_nothing() {
		$check = new Fake_Check( 'one', [] );

		( new Font_Lock() )->acquire( Health_Runner::LOCK, Health_Runner::LOCK_TTL );

		$this->assertFalse( $this->runner( [ $check ] )->run() );
		$this->assertSame( 0, $check->runs );
	}

	public function test_a_check_registered_through_the_filter_runs_beside_the_core_ones() {
		$registered = new Fake_Check( 'addon', [ $this->issue( 'a' ) ] );

		add_filter(
			'gfpdf_health_checks',
			static function ( array $checks ) use ( $registered ): array {
				$checks[] = $registered;

				return $checks;
			}
		);

		$this->runner( [ new Fake_Check( 'core', [] ) ] )->run();

		$report = $this->runner()->get_report();

		$this->assertSame( 1, $registered->runs );
		$this->assertArrayHasKey( 'core', $report['checks'] );
		$this->assertCount( 1, Health_Runner::issues_in( $report, 'addon' ) );
	}

	public function test_anything_the_filter_adds_that_is_not_a_check_is_ignored() {
		add_filter(
			'gfpdf_health_checks',
			static function ( array $checks ): array {
				$checks[] = 'not a check';

				return $checks;
			}
		);

		$this->assertTrue( $this->runner()->run() );
	}

	protected function set_report_last_run( int $at ): void {
		$report             = $this->runner()->get_report();
		$report['last_run'] = $at;

		update_site_option( Health_Runner::OPTION, $report );
	}
}
