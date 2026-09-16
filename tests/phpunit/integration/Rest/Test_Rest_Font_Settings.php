<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Fonts\Registry;
use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * What `GET` and `POST /fonts/settings` do with the four language keys
 *
 * @group api
 * @group rest
 * @group fonts
 */
class Test_Rest_Font_Settings extends Test_Rest {

	use HasCatalogRows;
	use HasFontRows;

	public function set_up(): void {
		parent::set_up();

		$this->drop_catalog_rows();

		wp_set_current_user( self::$admin_id );
	}

	public function tear_down(): void {
		$this->remove_font_rows();
		$this->remove_font_files();
		$this->drop_catalog_rows();

		$options = GPDFAPI::get_options_class();
		$reset   = [
			'default_pdf_language'    => '',
			'document_script'         => '',
			'auto_install_fonts'      => '',
			'font_language_overrides' => [],
		];

		foreach ( $reset as $key => $value ) {
			$options->update_option( $key, $value );
		}

		parent::tear_down();
	}

	/**
	 * @return array The response body as an array
	 */
	protected function settings(): array {
		$response = $this->get( '/fonts/settings' );

		$this->assertSame( 200, $response->get_status() );

		return (array) $response->get_data();
	}

	/**
	 * Save, and hand back what the route says the settings now are
	 *
	 * @return array The response body as an array
	 */
	protected function save( array $body ): array {
		$response = $this->post( '/fonts/settings', $body );

		$this->assertSame( 200, $response->get_status() );

		return (array) $response->get_data();
	}

	/**
	 * @return array<string, string> The stored overrides
	 */
	protected function stored_overrides(): array {
		return (array) GPDFAPI::get_options_class()->get_option( 'font_language_overrides', [] );
	}

	public function test_an_anonymous_request_is_refused() {
		wp_set_current_user( 0 );

		$this->assertSame( 401, $this->get( '/fonts/settings' )->get_status() );
		$this->assertSame( 401, $this->post( '/fonts/settings', [ 'default_pdf_language' => 'ja' ] )->get_status() );
	}

	public function test_the_read_carries_the_four_settings_and_the_lists_the_panel_draws_them_from() {
		$this->assertSame(
			[ 'auto_install_fonts', 'auto_install_locked', 'default_pdf_language', 'document_script', 'labels', 'language_map', 'scripts' ],
			$this->sorted_keys( $this->settings() )
		);
	}

	public function test_an_unset_language_follows_the_site_locale() {
		$this->assertSame( 'en-us', $this->settings()['default_pdf_language'] );
	}

	public function test_the_overrides_are_not_sent_back_because_the_rows_already_carry_them() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja' ] );
		$this->install_font_row( 'brandsans' );

		$this->save( [ 'font_language_overrides' => [ 'ja' => 'brandsans' ] ] );

		$settings = $this->settings();

		$this->assertArrayNotHasKey( 'font_language_overrides', $settings );

		$row = $settings['language_map'][1]['rows'][0];

		$this->assertSame( 'ja', $row['code'] );
		$this->assertSame( 'notosansjp', $row['default_font'] );
		$this->assertSame( 'brandsans', $row['font'] );
	}

	public function test_a_language_the_label_table_has_no_name_for_is_still_the_selects_own_option() {
		$this->save( [ 'default_pdf_language' => 'qx-aaaa' ] );

		$labels = (array) $this->settings()['labels'];

		$this->assertSame( 'qx-aaaa', $labels['qx-aaaa'] );
	}

	public function test_auto_install_is_on_and_unlocked_until_something_says_otherwise() {
		$settings = $this->settings();

		$this->assertTrue( $settings['auto_install_fonts'] );
		$this->assertFalse( $settings['auto_install_locked'] );
	}

	public function test_the_toggle_is_stored_as_a_string_so_update_option_cannot_delete_it() {
		$this->save( [ 'auto_install_fonts' => false ] );

		$this->assertSame( 'No', GPDFAPI::get_options_class()->get_option( 'auto_install_fonts' ) );
		$this->assertFalse( $this->settings()['auto_install_fonts'] );

		$this->save( [ 'auto_install_fonts' => true ] );

		$this->assertSame( 'Yes', GPDFAPI::get_options_class()->get_option( 'auto_install_fonts' ) );
	}

	public function test_a_write_carrying_one_key_leaves_the_other_three_alone() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja' ] );
		$this->install_font_row( 'brandsans' );

		$this->save(
			[
				'default_pdf_language'    => 'ja',
				'document_script'         => 'HAN',
				'auto_install_fonts'      => false,
				'font_language_overrides' => [ 'ja' => 'brandsans' ],
			]
		);

		$this->save( [ 'default_pdf_language' => 'ko' ] );

		$settings = $this->settings();

		$this->assertSame( 'ko', $settings['default_pdf_language'] );
		$this->assertSame( 'HAN', $settings['document_script'] );
		$this->assertFalse( $settings['auto_install_fonts'] );
		$this->assertSame( [ 'ja' => 'brandsans' ], $this->stored_overrides() );

		/* Both ways round: a key the request does not carry keeps its value whichever value that is */
		$this->save( [ 'auto_install_fonts' => true ] );
		$this->save( [ 'default_pdf_language' => 'ja' ] );

		$this->assertTrue( $this->settings()['auto_install_fonts'] );
	}

	public function test_the_write_answers_with_the_settings_as_they_now_read() {
		$this->assertSame( 'ja', $this->save( [ 'default_pdf_language' => 'ja' ] )['default_pdf_language'] );
	}

	public function test_a_language_that_is_not_a_tag_reads_as_no_choice_at_all() {
		$this->save( [ 'default_pdf_language' => '<script>alert(1)</script>' ] );

		$this->assertSame( '', GPDFAPI::get_options_class()->get_option( 'default_pdf_language', '' ) );

		/* Which is how "follow the site locale" is spelled, so the read still answers with a language */
		$this->assertSame( 'en-us', $this->settings()['default_pdf_language'] );
	}

	public function test_a_script_that_names_no_constant_is_dropped_rather_than_stored() {
		/* `BANANA` is the case worth pinning: the right shape, so only asking mPDF can refuse it */
		foreach ( [ 'not a script', 'BANANA', 'SCRIPT_BANANA' ] as $value ) {
			$this->save( [ 'document_script' => $value ] );

			$this->assertSame( '', GPDFAPI::get_options_class()->get_option( 'document_script', '' ), $value );
		}
	}

	public function test_the_read_offers_the_scripts_a_document_can_be_declared_in() {
		$scripts = $this->settings()['scripts'];

		$this->assertContains( 'LATIN', $scripts );
		$this->assertContains( 'HAN', $scripts );

		/* Bare names, because that is what the option stores and what the select round-trips */
		foreach ( $scripts as $script ) {
			$this->assertStringStartsNotWith( 'SCRIPT_', $script );
			$this->assertNotNull( Registry::script_constant( $script ), $script );
		}
	}

	/**
	 * The select offers the bare name and mPDF spells it `SCRIPT_LATIN`; both mean the one script, so both store
	 */
	public function test_mpdfs_own_spelling_of_a_script_is_folded_rather_than_refused() {
		$this->save( [ 'document_script' => 'SCRIPT_LATIN' ] );

		$this->assertSame( 'LATIN', GPDFAPI::get_options_class()->get_option( 'document_script', '' ) );
	}

	public function test_an_override_the_default_map_already_agrees_with_is_not_stored() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja' ] );

		$this->save( [ 'font_language_overrides' => [ 'ja' => 'notosansjp' ] ] );

		$this->assertSame( [], $this->stored_overrides() );
	}

	public function test_the_sentinel_is_stored_only_where_it_removes_something() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja' ] );

		$this->save(
			[
				'font_language_overrides' => [
					'ja' => '*',
					'sw' => '*',
				],
			]
		);

		/* `sw` was never routed, so `*` asks for the state it is already in */
		$this->assertSame( [ 'ja' => '*' ], $this->stored_overrides() );
	}

	public function test_an_override_naming_a_font_this_site_does_not_have_is_dropped() {
		$this->install_pack( 'japanese', 'notosansjp', [ 'ja' ] );

		$this->save( [ 'font_language_overrides' => [ 'ja' => 'notinstalled' ] ] );

		$this->assertSame( [], $this->stored_overrides() );
	}

	public function test_a_code_that_is_not_a_tag_is_dropped() {
		$this->install_font_row( 'brandsans' );

		$this->save(
			[
				'font_language_overrides' => [
					'../../etc/passwd' => 'brandsans',
					'JA'               => 'brandsans',
				],
			]
		);

		/* And the one that is a tag is folded, because the map is looked up lowercase */
		$this->assertSame( [ 'ja' => 'brandsans' ], $this->stored_overrides() );
	}

	public function test_installing_the_pack_an_override_stood_in_for_retires_the_override() {
		$this->install_font_row( 'brandsans' );

		$this->save( [ 'font_language_overrides' => [ 'ja' => 'brandsans' ] ] );

		$this->assertSame( [ 'ja' => 'brandsans' ], $this->stored_overrides() );

		$this->install_pack( 'japanese', 'brandsans2', [ 'ja' ] );

		/* The next save re-prunes against the map the pack changed: the row is the pack's answer again */
		$this->save( [ 'font_language_overrides' => [ 'ja' => 'brandsans2' ] ] );

		$this->assertSame( [], $this->stored_overrides() );
	}
}
