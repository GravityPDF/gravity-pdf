<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Health\Health_Runner;
use GFPDF\Helper\Helper_Abstract_Queue;
use GFPDF\Helper\Log\Option_Ring_Handler;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\QueuesFontInstalls;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * What a support ticket carries away
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Report extends TestCase {

	use HasCatalogRows;
	use HasFontRows;
	use QueuesFontInstalls;

	/**
	 * @var Font_Report
	 */
	public $report;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		$this->report = $gfpdf->get_font_report();
	}

	public function tear_down(): void {
		$this->drop_catalog_rows();
		$this->remove_font_rows();
		$this->remove_font_files();
		$this->install_queue()->clear_queue();

		Option_Ring_Handler::clear();
		delete_option( Helper_Abstract_Queue::DISPATCH_ERROR_OPTION );
		delete_site_option( Health_Runner::OPTION );

		parent::tear_down();
	}

	protected function value( array $items, string $key ): string {
		$this->assertArrayHasKey( $key, $items );

		return (string) $items[ $key ]['value'];
	}

	public function test_the_fonts_section_counts_what_is_installed_by_where_it_came_from() {
		$this->install_font_row( 'my-font' );
		$this->install_entry_row( 'notosansjp', 'japanese' );

		$items = $this->report->fonts();

		$this->assertSame( 'custom: 1, packs: 1', $this->value( $items, 'installed_fonts' ) );
		$this->assertStringContainsString( 'fonts', $this->value( $items, 'font_folder_location' ) );
		$this->assertSame( 'Writable', $this->value( $items, 'font_folder_writable' ) );
	}

	public function test_the_fonts_section_names_every_registered_source_and_when_it_last_synced() {
		$this->assertStringContainsString( 'packs', $this->value( $this->report->fonts(), 'font_sources' ) );
		$this->assertStringContainsString( 'never synced', $this->value( $this->report->fonts(), 'font_sources' ) );
	}

	public function test_the_fonts_section_says_how_far_each_pack_got() {
		$this->insert_catalog_row( 'packs', 'japanese', [ 'coverage' => 1, 'files' => 4 ] );
		$this->install_entry_row( 'notosansjp', 'japanese' );

		$this->assertStringContainsString( 'packs/japanese: 1 of 4 files', $this->value( $this->report->fonts(), 'language_packs' ) );
	}

	public function test_a_site_with_no_packs_says_so() {
		$this->assertSame( 'None installed', $this->value( $this->report->fonts(), 'language_packs' ) );
	}

	/**
	 * The two ways a queue stops are invisible from the outside, so the row that names them is the whole point
	 */
	public function test_the_background_section_names_an_install_that_stopped_moving() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );
		$this->catalog_repository()->set_status(
			'packs',
			'emoji',
			[
				'phase'       => 'installing',
				'phase_since' => gmdate( 'Y-m-d H:i:s', time() - Install_Queue::STALLED_AFTER - 60 ),
			]
		);

		$items = $this->report->background_installs();

		$this->assertStringContainsString( 'packs/emoji: installing for', $this->value( $items, 'queue_stalled' ) );
		$this->assertSame( 'No', $this->value( $items, 'queue_running' ) );
	}

	public function test_the_background_section_says_nothing_is_stuck_when_nothing_is() {
		$this->assertSame( 'None', $this->value( $this->report->background_installs(), 'queue_stalled' ) );
	}

	public function test_the_background_section_carries_the_last_loopback_failure() {
		update_option( Helper_Abstract_Queue::DISPATCH_ERROR_OPTION, 'cURL error 7', false );

		$this->assertSame( 'cURL error 7', $this->value( $this->report->background_installs(), 'dispatch_error' ) );
	}

	/**
	 * The reason the ring exists: an error nobody had turned logging on for still reaches the ticket
	 */
	public function test_the_background_section_carries_the_last_errors() {
		update_option(
			Option_Ring_Handler::OPTION,
			[
				[
					'time'    => '2026-09-10 00:00:00',
					'channel' => 'gravity-pdf',
					'message' => 'Font install failed',
					'context' => '{"entry":"packs/emoji"}',
				],
			],
			false
		);

		$errors = $this->value( $this->report->background_installs(), 'last_errors' );

		$this->assertStringContainsString( 'Font install failed', $errors );
		$this->assertStringContainsString( 'packs/emoji', $errors );
	}

	public function test_the_health_section_has_a_row_per_check_and_says_when_it_last_ran() {
		$items = $this->report->health();

		$this->assertSame( 'Never run', $this->value( $items, 'last_run' ) );
		$this->assertArrayHasKey( 'check_missing_coverage', $items );
		$this->assertArrayHasKey( 'check_missing_font_files', $items );
		$this->assertArrayHasKey( 'check_unregistered_font', $items );
		$this->assertArrayHasKey( 'check_catalog_sync', $items );
		$this->assertArrayHasKey( 'check_font_downloads', $items );
	}

	/**
	 * Generic by construction: a check registered by an add-on appears with no report change
	 */
	public function test_a_registered_check_gets_a_row_of_its_own() {
		add_filter(
			'gfpdf_health_checks',
			static function ( array $checks ): array {
				$checks[] = new \GFPDF\Helper\Health\Fake_Check( 'addon', [] );

				return $checks;
			}
		);

		$this->assertArrayHasKey( 'check_addon', $this->report->health() );

		remove_all_filters( 'gfpdf_health_checks' );
	}

	public function test_the_health_section_lists_what_the_last_run_found() {
		update_site_option(
			Health_Runner::OPTION,
			[
				'last_run' => time() - HOUR_IN_SECONDS,
				'checks'   => [
					'catalog_sync' => [
						'issues' => [
							[
								'id'      => 'catalog_sync',
								'summary' => 'Could not check for new fonts.',
								'details' => [ 'packs: never checked' ],
							],
						],
					],
				],
			]
		);

		$items = $this->report->health();

		$this->assertSame( '1 hour ago', $this->value( $items, 'last_run' ) );
		$this->assertStringContainsString( 'packs: never checked', $this->value( $items, 'check_catalog_sync' ) );
	}
}
