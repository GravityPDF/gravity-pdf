<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFAPI;
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
 * The three moments a site's font needs change with nobody drawing a PDF
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Install_Triggers extends TestCase {

	use HasCatalogRows;
	use QueuesFontInstalls;

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
				'title'  => 'Font triggers',
				'fields' => [],
			]
		);
	}

	public function tear_down(): void {
		GFAPI::delete_form( $this->form_id );

		$this->drop_catalog_rows();
		$this->install_queue()->clear_queue();

		update_option( 'WPLANG', '' );
		GPDFAPI::get_options_class()->update_option( 'default_pdf_language', '' );

		parent::tear_down();
	}

	/**
	 * One file per pack, inlined, so nothing here reaches the network
	 */
	protected function seed_pack( string $entry, array $overrides = [] ): void {
		$name = ucfirst( $entry ) . '.ttf';

		$this->insert_catalog_row(
			'packs',
			$entry,
			array_merge(
				[
					'coverage'   => 1,
					'files'      => 1,
					'entry_json' => (string) wp_json_encode(
						[
							'fonts' => [ $entry => [ 'R' => $name ] ],
							'files' => [
								$name => [
									'sha256'      => hash( 'sha256', $name ),
									'size'        => 128,
									'remote_path' => 'fonts-v1.0.0/' . $name,
								],
							],
						]
					),
				],
				$overrides
			)
		);
	}

	protected function seed_catalogue(): void {
		$this->seed_pack( 'japanese', [ 'languages' => 'ja', 'font_keys' => 'notosansjp' ] );
		$this->seed_pack( 'arabic', [ 'languages' => 'ar', 'font_keys' => 'xbriyaz' ] );
	}

	/**
	 * @return string[] The entries with a queued item, in the order queued
	 */
	protected function claimed(): array {
		return array_values( array_unique( array_column( $this->queued(), 'entry' ) ) );
	}

	public function test_saving_a_pdf_that_names_a_pack_font_installs_that_pack() {
		$this->seed_catalogue();

		GPDFAPI::add_pdf( $this->form_id, [ 'name' => 'One', 'template' => 'zadani', 'font' => 'xbriyaz' ] );

		$this->assertSame( [ 'arabic' ], $this->claimed() );
	}

	public function test_saving_a_pdf_that_names_a_language_installs_the_pack_covering_it() {
		$this->seed_catalogue();

		GPDFAPI::add_pdf( $this->form_id, [ 'name' => 'One', 'template' => 'zadani', 'pdf_language' => 'ja' ] );

		$this->assertSame( [ 'japanese' ], $this->claimed() );
	}

	/**
	 * The settings screen calls `update_pdf()` with `$update_db` false to validate, and nothing is chosen yet
	 */
	public function test_a_validation_only_save_asks_for_nothing() {
		$this->seed_catalogue();

		GPDFAPI::get_options_class()->update_pdf(
			$this->form_id,
			'abc123',
			[ 'name' => 'One', 'template' => 'zadani', 'font' => 'xbriyaz' ],
			false
		);

		$this->assertSame( [], $this->queued() );
	}

	public function test_deleting_a_pdf_asks_for_nothing() {
		$this->seed_catalogue();

		$pdf_id = GPDFAPI::add_pdf( $this->form_id, [ 'name' => 'One', 'template' => 'zadani', 'font' => 'xbriyaz' ] );
		$this->install_queue()->clear_queue();

		GPDFAPI::get_options_class()->update_pdf( $this->form_id, $pdf_id, [] );

		$this->assertSame( [], $this->queued() );
	}

	/**
	 * `get_locale()` reads a global settled during load, so changing the option in-process changes nothing; the
	 * filter is what a request after the change would have seen
	 */
	protected function with_locale( string $locale, callable $do ): void {
		$filter = static function () use ( $locale ): string {
			return $locale;
		};

		add_filter( 'locale', $filter );

		try {
			$do();
		} finally {
			remove_filter( 'locale', $filter );
		}
	}

	public function test_changing_the_site_language_installs_the_pack_covering_it() {
		$this->seed_catalogue();

		/* `sanitize_option()` refuses a locale WordPress has no translation for, and the hook only fires on a real
		   change — so the language has to look installed and the option has to exist first */
		add_filter( 'get_available_languages', [ $this, 'japanese_is_installed' ] );
		update_option( 'WPLANG', '' );

		$this->with_locale(
			'ja_JP',
			function (): void {
				update_option( 'WPLANG', 'ja_JP' );
			}
		);

		remove_filter( 'get_available_languages', [ $this, 'japanese_is_installed' ] );

		$this->assertSame( [ 'japanese' ], $this->claimed() );
	}

	/**
	 * A network admin changing the language never fires the per-site hook
	 */
	/**
	 * @return string[]
	 */
	public function japanese_is_installed(): array {
		return [ 'ja_JP' ];
	}

	public function test_a_network_language_change_installs_the_pack_too() {
		$this->seed_catalogue();

		$this->with_locale(
			'ar',
			function (): void {
				do_action( 'update_site_option_WPLANG', 'WPLANG', 'ar', '' );
			}
		);

		$this->assertSame( [ 'arabic' ], $this->claimed() );
	}

	/**
	 * An English site with an Arabic translation installed still renders Arabic PDFs, so the pack is wanted
	 */
	public function test_an_installed_translation_counts_even_when_it_is_not_the_site_language() {
		$this->seed_catalogue();

		add_filter( 'get_available_languages', [ $this, 'arabic_is_installed' ] );

		$this->with_locale(
			'en_US',
			function (): void {
				do_action( 'gfpdf_plugin_installed' );
			}
		);

		remove_filter( 'get_available_languages', [ $this, 'arabic_is_installed' ] );

		$this->assertSame( [ 'arabic' ], $this->claimed() );
	}

	/**
	 * @return string[]
	 */
	public function arabic_is_installed(): array {
		return [ 'ar' ];
	}

	public function test_installing_the_plugin_installs_the_packs_the_site_language_needs() {
		$this->seed_catalogue();

		$this->with_locale(
			'ar',
			function (): void {
				do_action( 'gfpdf_plugin_installed' );
			}
		);

		$this->assertSame( [ 'arabic' ], $this->claimed() );
	}

	/**
	 * The catch-up for a site whose egress was blocked when it was installed: there was no catalogue to resolve
	 * against then, so the first sync that produces one asks on the install's behalf
	 */
	public function test_the_first_successful_sync_asks_on_the_installs_behalf() {
		$this->seed_catalogue();

		$this->with_locale(
			'ja_JP',
			function (): void {
				do_action( 'gfpdf_font_catalog_first_sync' );
			}
		);

		$this->assertSame( [ 'japanese' ], $this->claimed() );
	}
}
