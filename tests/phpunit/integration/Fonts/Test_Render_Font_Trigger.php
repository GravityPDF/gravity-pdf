<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontFixtures;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use GFPDF\Tests\Concerns\QueuesFontInstalls;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The concurrent batch, one file at a time
 *
 * `Requests::request_multiple()` sits below `WP_Http` and so below `pre_http_request`, which is the seam every
 * other font test answers on. Running the same files through `download()` keeps every rule that matters here —
 * the URL check, the size and hash verification, the `.part` and its unlink — and gives up only the concurrency,
 * which is a property of curl and not of this plugin.
 */
class Sequential_Font_Downloader extends Font_Downloader {

	/**
	 * @var int
	 */
	public $batches = 0;

	public function download_multiple( array $files ): array {
		++$this->batches;

		$results = [];

		foreach ( $files as $key => $file ) {
			$results[ $key ] = $this->download( (string) $file['url'], $file );
		}

		return $results;
	}
}

/**
 * What a PDF installs while it is being drawn
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Render_Font_Trigger extends TestCase {

	use HasCatalogRows;
	use HasFontRows;
	use HasFontFixtures;
	use MocksHttpRequests;
	use QueuesFontInstalls;

	/**
	 * @var Render_Font_Trigger
	 */
	public $trigger;

	/**
	 * @var Sequential_Font_Downloader
	 */
	public $downloader;

	/**
	 * @var string
	 */
	public $font_dir;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		$this->font_dir   = $gfpdf->get_font_repository()->get_font_dir();
		$this->downloader = new Sequential_Font_Downloader( $gfpdf->log, $gfpdf->data );

		$this->trigger = new Render_Font_Trigger(
			$gfpdf->get_script_detector(),
			$gfpdf->get_coverage_resolver(),
			$this->install_queue(),
			new Font_Installer(
				$gfpdf->get_font_repository(),
				$this->catalog_repository(),
				$this->downloader,
				$gfpdf->get_font_cache_warmer(),
				new Font_Lock(),
				$gfpdf->log
			),
			$gfpdf->get_font_registry(),
			new Font_Lock(),
			$gfpdf->log
		);
	}

	public function tear_down(): void {
		global $gfpdf;

		$this->unmock_http();
		$this->drop_catalog_rows();
		$this->remove_font_rows();
		$this->install_queue()->clear_queue();

		( new Font_Lock() )->release( Render_Font_Trigger::SLOT );
		GPDFAPI::get_misc_class()->rmdir( $this->font_dir . 'packs' );

		unset( $_SERVER['REQUEST_TIME_FLOAT'] );

		$gfpdf->get_font_repository()->flush();

		parent::tear_down();
	}

	/**
	 * A two-face Japanese pack: the Regular is what a first render needs, the Bold can follow
	 */
	protected function seed_pack(): void {
		$regular = $this->font_bytes( 'Arimo-Regular' );
		$bold    = $this->font_bytes( 'Arimo-Bold' );

		$this->insert_catalog_row(
			'packs',
			'japanese',
			[
				'coverage'   => 1,
				'scripts'    => 'ja',
				'languages'  => 'ja',
				'font_keys'  => 'notosansjp',
				'files'      => 2,
				'entry_json' => (string) wp_json_encode(
					[
						'fonts' => [ 'notosansjp' => [ 'R' => 'JP-Regular.ttf', 'B' => 'JP-Bold.ttf' ] ],
						'files' => [
							'JP-Regular.ttf' => [
								'sha256'      => hash( 'sha256', $regular ),
								'size'        => strlen( $regular ),
								'remote_path' => 'fonts-v1.0.0/JP-Regular.ttf',
							],
							'JP-Bold.ttf'    => [
								'sha256'      => hash( 'sha256', $bold ),
								'size'        => strlen( $bold ),
								'remote_path' => 'fonts-v1.0.0/JP-Bold.ttf',
							],
						],
					]
				),
			]
		);

		$this->mock_http(
			[
				'JP-Regular.ttf' => $regular,
				'JP-Bold.ttf'    => $bold,
			]
		);
	}

	/**
	 * A submission carrying the text, the way `Helper_PDF` hands it over
	 */
	protected function render( string $text = 'ひらがな' ): void {
		$this->trigger->before_render( [], [ '1' => $text ], [ 'font' => 'gfpdf-arimo' ] );
	}

	protected function status(): array {
		return (array) $this->catalog_repository()->entry( 'packs', 'japanese' );
	}

	public function test_a_japanese_submission_installs_the_pack_it_needs_before_it_draws() {
		$this->seed_pack();

		$this->render();

		$this->assertFileExists( $this->font_dir . 'packs/japanese/JP-Regular.ttf' );
		$this->assertNotNull( GPDFAPI::get_font_repository()->get( 'notosansjp' ) );

		/* The rest follows this PDF rather than delaying it */
		$this->assertSame( [ 'JP-Bold.ttf' ], array_column( $this->queued(), 'name' ) );
		$this->assertSame( 'installing', $this->status()['phase'] );
	}

	public function test_a_latin_submission_asks_the_network_for_nothing() {
		$this->seed_pack();

		$this->render( 'Hello world' );

		$this->assertSame( [], $this->requested_urls() );
		$this->assertSame( 0, $this->downloader->batches );
		$this->assertSame( [], $this->queued() );
	}

	/**
	 * One PHP worker in a batch at a time, and the loser must leave the entry exactly as it found it
	 */
	public function test_a_second_render_holding_no_slot_fetches_nothing_and_records_nothing() {
		$this->seed_pack();

		( new Font_Lock() )->acquire( Render_Font_Trigger::SLOT, 60 );

		$this->render();

		$this->assertSame( 0, $this->downloader->batches );
		$this->assertSame( [], $this->queued() );
		$this->assertNull( $this->status()['phase'] );
		$this->assertNull( $this->status()['retry_after'] );
	}

	/**
	 * A submission already slowed by a payment gateway does not also pay for a font
	 */
	public function test_a_request_already_over_its_budget_queues_everything_instead() {
		$this->seed_pack();

		$_SERVER['REQUEST_TIME_FLOAT'] = microtime( true ) - ( Render_Font_Trigger::REQUEST_BUDGET + 5 );

		$this->render();

		$this->assertSame( 0, $this->downloader->batches );
		$this->assertSame( [ 'JP-Regular.ttf', 'JP-Bold.ttf' ], array_column( $this->queued(), 'name' ) );
	}

	public function test_the_post_render_scan_queues_the_templates_own_text_and_fetches_nothing() {
		$this->seed_pack();

		$this->trigger->after_render( '<p>ひらがな</p>' );

		$this->assertSame( 0, $this->downloader->batches );
		$this->assertSame( [ 'JP-Regular.ttf', 'JP-Bold.ttf' ], array_column( $this->queued(), 'name' ) );
	}

	public function test_a_render_with_auto_install_off_asks_for_nothing() {
		$this->seed_pack();
		GPDFAPI::get_options_class()->update_option( 'auto_install_fonts', 'No' );

		$this->render();

		$this->assertSame( 0, $this->downloader->batches );
		$this->assertSame( [], $this->queued() );

		GPDFAPI::get_options_class()->update_option( 'auto_install_fonts', 'Yes' );
	}

	/**
	 * A failed inline fetch backs the entry off like any other install, so anonymous traffic cannot hammer the
	 * origin — and the PDF is still drawn, with the bundled faces
	 */
	public function test_a_failed_fetch_records_the_failure_and_backs_off() {
		$this->seed_pack();
		$this->mock_http( [ 'JP-Regular.ttf' => [ 'code' => 500, 'body' => '' ] ] );

		$this->render();

		$this->assertSame( 'failed', $this->status()['phase'] );
		$this->assertNotNull( $this->status()['retry_after'] );
		$this->assertFileDoesNotExist( $this->font_dir . 'packs/japanese/JP-Regular.ttf' );
	}
}
