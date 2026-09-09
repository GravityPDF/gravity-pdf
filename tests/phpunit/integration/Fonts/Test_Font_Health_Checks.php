<?php

declare( strict_types=1 );

namespace GFPDF\Fonts\Health;

use GFAPI;
use GFPDF\Fonts\Catalog_Sync;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * What the four font checks say, and when they say nothing
 *
 * @package   GFPDF\Fonts\Health
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Health_Checks extends TestCase {

	use HasCatalogRows;
	use HasFontRows;

	/**
	 * @var int
	 */
	public $form_id;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		$this->form_id = (int) GFAPI::add_form(
			[
				'title'  => 'Health',
				'fields' => [],
			]
		);
	}

	public function tear_down(): void {
		GFAPI::delete_form( $this->form_id );

		$this->drop_catalog_rows();
		$this->remove_font_rows();
		$this->remove_font_files();

		GPDFAPI::get_options_class()->update_option( 'default_font', '' );
		delete_site_option( Catalog_Sync::OPTION );

		parent::tear_down();
	}

	/**
	 * What a migration to a new server, or a restore without the uploads directory, leaves behind
	 */
	protected function delete_font_file( string $filename ): void {
		unlink( $this->font_dir() . $filename );
	}

	protected function configured(): Configured_Fonts {
		return new Configured_Fonts( GPDFAPI::get_options_class() );
	}

	protected function add_pdf( array $settings ): string {
		return (string) GPDFAPI::add_pdf(
			$this->form_id,
			array_merge( [ 'name' => 'Notification', 'template' => 'zadani' ], $settings )
		);
	}

	/**
	 * @return string[] Each issue's id
	 */
	protected function ids( array $issues ): array {
		return array_map(
			static function ( $issue ): string {
				return $issue->get_id();
			},
			$issues
		);
	}

	/* --- Missing_Coverage_Check --- */

	protected function coverage_check(): Missing_Coverage_Check {
		global $gfpdf;

		return new Missing_Coverage_Check( $gfpdf->get_catalog_repository() );
	}

	protected function seed_missing( string $entry, array $overrides = [] ): void {
		$this->insert_catalog_row(
			'packs',
			$entry,
			array_merge(
				[
					'coverage'        => 1,
					'label'           => ucfirst( $entry ),
					'missing_scripts' => 'ja',
					'missing_since'   => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
				],
				$overrides
			)
		);
	}

	public function test_an_entry_a_render_could_not_cover_raises_one_issue() {
		$this->seed_missing( 'japanese' );

		$issues = $this->coverage_check()->run();

		$this->assertSame( [ 'packs/japanese' ], $this->ids( $issues ) );
		$this->assertStringContainsString( 'Japanese', $issues[0]->get_summary() );
		$this->assertStringContainsString( 'ja', $issues[0]->get_details()[0] );
	}

	public function test_a_catalogue_nothing_asked_for_raises_nothing() {
		$this->insert_catalog_row( 'packs', 'japanese', [ 'coverage' => 1 ] );

		$this->assertSame( [], $this->coverage_check()->run() );
	}

	public function test_the_failed_entrys_error_is_named() {
		$this->seed_missing( 'japanese', [ 'phase' => 'failed', 'error' => 'font_http_error' ] );
		$this->seed_missing( 'arabic' );

		$issues = $this->coverage_check()->run();
		$failed = $issues[ array_search( 'packs/japanese', $this->ids( $issues ), true ) ];

		$this->assertStringContainsString( 'font_http_error', implode( ' ', $failed->get_details() ) );
	}

	/**
	 * A site that cannot reach the origin has one problem, not eight
	 */
	public function test_every_entry_failing_becomes_one_issue_about_the_server() {
		$this->seed_missing( 'japanese', [ 'phase' => 'failed', 'error' => 'font_http_error' ] );
		$this->seed_missing( 'arabic', [ 'phase' => 'failed', 'error' => 'font_http_error' ] );

		$issues = $this->coverage_check()->run();

		$this->assertSame( [ 'font_downloads_failing' ], $this->ids( $issues ) );
		$this->assertStringContainsString( '2 font packs', $issues[0]->get_summary() );
		$this->assertSame( [ 'Arabic', 'Japanese' ], $issues[0]->get_details() );
	}

	/**
	 * "Your server may be blocking outbound connections" sends an admin the wrong way when the answer is `df`
	 */
	public function test_a_full_disk_says_so_instead() {
		$this->seed_missing( 'japanese', [ 'phase' => 'failed', 'error' => 'font_disk_full' ] );
		$this->seed_missing( 'arabic', [ 'phase' => 'failed', 'error' => 'font_disk_full' ] );

		$issues = $this->coverage_check()->run();

		$this->assertSame( [ 'font_disk_full' ], $this->ids( $issues ) );
		$this->assertSame( 'manage_options', $this->coverage_check()->get_capability() );
	}

	/**
	 * A wrong PDF has already gone out, so this one does not wait for the daily run
	 */
	public function test_the_coverage_notice_answers_live_rather_than_from_the_report() {
		$this->seed_missing( 'japanese' );

		$this->assertSame( [ 'packs/japanese' ], $this->ids( $this->coverage_check()->notice_issues( [] ) ) );
	}

	/* --- Missing_Font_Files_Check --- */

	protected function files_check(): Missing_Font_Files_Check {
		global $gfpdf;

		return new Missing_Font_Files_Check( $gfpdf->get_font_repository(), $gfpdf->get_font_registry(), $this->configured() );
	}

	public function test_a_font_whose_file_vanished_raises_an_issue_naming_the_pdfs_that_use_it() {
		$this->install_entry_row( 'notosansjp', 'packs/japanese' );
		$this->add_pdf( [ 'font' => 'notosansjp' ] );

		$this->delete_font_file( 'test-notosansjp-r.ttf' );

		$issues = $this->files_check()->run();

		$this->assertSame( [ 'notosansjp' ], $this->ids( $issues ) );
		$this->assertContains( 'Health → Notification', $issues[0]->get_details() );
		$this->assertSame( 'Reinstall', $issues[0]->get_action_label() );
	}

	/**
	 * A downloaded font can be fetched again; one somebody uploaded can only be replaced by them
	 */
	public function test_an_uploaded_font_is_offered_replace_rather_than_reinstall() {
		$this->install_font_row( 'my-font' );
		$this->delete_font_file( 'test-my-font.ttf' );

		$issues = $this->files_check()->run();

		$this->assertSame( 'Replace', $issues[0]->get_action_label() );
	}

	public function test_a_font_whose_files_are_all_there_raises_nothing() {
		$this->install_font_row( 'my-font' );

		$this->assertSame( [], $this->files_check()->run() );
	}

	/* --- Unregistered_Font_Check --- */

	protected function unregistered_check(): Unregistered_Font_Check {
		global $gfpdf;

		return new Unregistered_Font_Check( $gfpdf->get_font_registry(), $gfpdf->get_catalog_repository(), $this->configured() );
	}

	public function test_a_pdf_naming_a_font_the_site_does_not_have_raises_an_issue() {
		$this->add_pdf( [ 'font' => 'notosansjp' ] );

		$issues = $this->unregistered_check()->run();

		$this->assertSame( [ 'notosansjp' ], $this->ids( $issues ) );
		$this->assertContains( 'Health → Notification', $issues[0]->get_details() );

		/* Nothing in the catalogue installs it, so the remedy is to choose again rather than to install */
		$this->assertSame( 'Choose another font', $issues[0]->get_action_label() );
	}

	public function test_a_font_the_catalogue_can_install_is_offered_install() {
		$this->insert_catalog_row( 'packs', 'japanese', [ 'coverage' => 1, 'font_keys' => 'notosansjp,notosanskr' ] );
		$this->add_pdf( [ 'font' => 'notosansjp' ] );

		$issues = $this->unregistered_check()->run();

		$this->assertSame( 'Install', $issues[0]->get_action_label() );
		$this->assertStringContainsString( 'packs/japanese', $issues[0]->get_action_url() );
	}

	public function test_the_site_wide_default_font_is_checked_too() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'notosansjp' );

		$issues = $this->unregistered_check()->run();

		$this->assertSame( [ 'notosansjp' ], $this->ids( $issues ) );
		$this->assertSame( [ 'The site-wide default font' ], $issues[0]->get_details() );
	}

	public function test_a_watermark_font_is_checked_too() {
		$this->add_pdf( [ 'font' => 'gfpdf-arimo', 'watermark_font' => 'notosansjp' ] );

		$this->assertSame( [ 'notosansjp' ], $this->ids( $this->unregistered_check()->run() ) );
	}

	public function test_an_installed_font_raises_nothing() {
		$this->install_font_row( 'my-font' );
		$this->add_pdf( [ 'font' => 'my-font' ] );

		$this->assertSame( [], $this->unregistered_check()->run() );
	}

	/**
	 * A font whose only face is flagged missing does not resolve at render time, whatever its row says
	 */
	public function test_a_font_with_no_usable_face_counts_as_unregistered() {
		$this->install_font_row( 'my-font' );
		$this->add_pdf( [ 'font' => 'my-font' ] );

		$this->delete_font_file( 'test-my-font.ttf' );
		$this->font_repository()->verify();

		$this->assertSame( [ 'my-font' ], $this->ids( $this->unregistered_check()->run() ) );
	}

	/* --- Catalog_Sync_Check --- */

	protected function sync_check(): Catalog_Sync_Check {
		global $gfpdf;

		return new Catalog_Sync_Check( $gfpdf->get_catalog_sync() );
	}

	public function test_a_source_that_has_never_synced_raises_an_issue() {
		$issues = $this->sync_check()->run();

		$this->assertSame( [ 'catalog_sync' ], $this->ids( $issues ) );
		$this->assertSame( [ 'packs: never checked' ], $issues[0]->get_details() );
		$this->assertSame( 'manage_options', $this->sync_check()->get_capability() );
	}

	public function test_a_source_that_synced_recently_raises_nothing() {
		update_site_option( Catalog_Sync::OPTION, [ 'packs' => [ 'synced' => time() ] ] );

		$this->assertSame( [], $this->sync_check()->run() );
	}

	public function test_a_stale_source_names_its_last_error() {
		update_site_option(
			Catalog_Sync::OPTION,
			[
				'packs' => [
					'synced'       => time() - Catalog_Sync::HEALTH_AFTER - 1,
					'last_attempt' => time() - HOUR_IN_SECONDS,
					'last_error'   => 'font_http_error',
				],
			]
		);

		$this->assertStringContainsString( 'font_http_error', $this->sync_check()->run()[0]->get_details()[0] );
	}
}
