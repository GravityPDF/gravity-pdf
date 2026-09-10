<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Font_Sources
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Sources extends TestCase {

	/**
	 * @var Font_Sources
	 */
	public $sources;

	public function set_up(): void {
		parent::set_up();

		$this->sources = new Font_Sources( GPDFAPI::get_log_class() );
	}

	/**
	 * A valid coverage entry, the shape `validate_entry()` accepts
	 *
	 * Not `entry()`: `HasGfpdfFixtures` already has one with a different signature, and overriding it is a
	 * class-load fatal that PHPUnit reports as a bare exit 255.
	 */
	protected function valid_entry( array $overrides = [] ): array {
		return array_merge(
			[
				'fonts' => [
					'notosanssc' => [
						'R'        => 'NotoSansSC-Regular.ttf',
						'sip-ext'  => 'sun-extb',
						'useOTL'   => 255,
					],
				],
				'files' => [
					'NotoSansSC-Regular.ttf' => [
						'sha256'      => str_repeat( 'a', 64 ),
						'size'        => 10595932,
						'remote_path' => 'fonts-v1.0.0/NotoSansSC-Regular.ttf',
					],
				],
				'language_to_font' => [ 'zh' => 'notosanssc' ],
			],
			$overrides
		);
	}

	public function test_the_packs_record_is_registered() {
		$packs = $this->sources->get( 'packs' );

		$this->assertInstanceOf( Font_Source::class, $packs );
		$this->assertSame( 'packs', $packs->get_id() );
		$this->assertSame( 'Language packs', $packs->get_label() );
		$this->assertNotSame( '', $packs->get_description() );
	}

	public function test_the_built_in_root_request_carries_no_query_args() {
		/* A licence key must never reach the fonts host or its CDN cache key */
		$this->assertSame( [], $this->sources->get( 'packs' )->get_request_args() );
	}

	public function test_the_root_url_is_trailing_slashed_and_filterable() {
		$this->assertSame( trailingslashit( GPDF_FONTS_URL ), $this->sources->get( 'packs' )->get_root_url() );

		add_filter( 'gfpdf_font_download_base_url', $mirror = static function () {
			return 'https://fonts-staging.gravitypdf.com/v1';
		} );

		$this->assertSame( 'https://fonts-staging.gravitypdf.com/v1/', ( new Font_Sources( GPDFAPI::get_log_class() ) )->get( 'packs' )->get_root_url() );

		remove_filter( 'gfpdf_font_download_base_url', $mirror );
	}

	public function test_a_third_party_source_is_registered_through_the_filter() {
		$record = new Font_Source( 'acme', 'Acme Fonts', 'https://fonts.example.com/v1/', [ 'token' => 'abc' ], 'A library.' );

		add_filter( 'gfpdf_font_sources', $add = static function ( $sources ) use ( $record ) {
			$sources[] = $record;

			return $sources;
		} );

		$sources = new Font_Sources( GPDFAPI::get_log_class() );

		$this->assertSame( $record, $sources->get( 'acme' ) );
		$this->assertSame( [ 'token' => 'abc' ], $sources->get( 'acme' )->get_request_args() );
		$this->assertArrayHasKey( 'packs', $sources->all() );

		remove_filter( 'gfpdf_font_sources', $add );
	}

	/**
	 * @dataProvider provider_rejected_ids
	 */
	public function test_a_reserved_or_malformed_id_is_dropped( string $id ) {
		add_filter( 'gfpdf_font_sources', $add = static function ( $sources ) use ( $id ) {
			$sources[] = new Font_Source( $id, 'Nope', 'https://example.com/' );

			return $sources;
		} );

		$this->assertNull( ( new Font_Sources( GPDFAPI::get_log_class() ) )->get( $id ) );

		remove_filter( 'gfpdf_font_sources', $add );
	}

	public function provider_rejected_ids(): array {
		return [
			'reserved: custom row'   => [ 'custom' ],
			'reserved: imported row' => [ 'imported' ],
			'reserved: sync route'   => [ 'sync' ],
			'underscore'             => [ 'my_source' ],
			'uppercase'              => [ 'Acme' ],
			'slash splits an id'     => [ 'acme/fonts' ],
			'empty'                  => [ '' ],
		];
	}

	public function test_a_filter_cannot_displace_a_built_in_record() {
		$impostor = new Font_Source( 'packs', 'Not ours', 'https://evil.example.com/' );

		add_filter( 'gfpdf_font_sources', $add = static function ( $sources ) use ( $impostor ) {
			$sources[] = $impostor;

			return $sources;
		} );

		$this->assertSame( 'Language packs', ( new Font_Sources( GPDFAPI::get_log_class() ) )->get( 'packs' )->get_label() );

		remove_filter( 'gfpdf_font_sources', $add );
	}

	public function test_pack_labels_are_translated_and_unknown_entries_fall_back() {
		$this->assertSame( 'Extended Latin', Font_Sources::translate_entry( 'packs', 'dejavu', 'label', 'DejaVu' ) );

		/* A pack the server adds before the plugin ships its translation keeps the index string */
		$this->assertSame( 'Klingon', Font_Sources::translate_entry( 'packs', 'klingon', 'label', 'Klingon' ) );

		/* Another source's values are proper nouns and pass through */
		$this->assertSame( 'Lato', Font_Sources::translate_entry( 'google', 'lato', 'label', 'Lato' ) );
	}

	public function test_a_well_formed_entry_validates() {
		$this->assertNull( Font_Sources::validate_entry( $this->valid_entry() ) );
	}

	/**
	 * @dataProvider provider_invalid_entries
	 */
	public function test_an_invalid_entry_is_rejected_with_a_reason( array $overrides ) {
		$this->assertIsString( Font_Sources::validate_entry( $this->valid_entry( $overrides ) ) );
	}

	public function provider_invalid_entries(): array {
		$file = [
			'sha256'      => str_repeat( 'a', 64 ),
			'size'        => 10,
			'remote_path' => 'fonts-v1.0.0/Ok.ttf',
		];

		return [
			'no files'                    => [ [ 'files' => [] ] ],
			'no fonts'                    => [ [ 'fonts' => [] ] ],
			'traversing remote_path'      => [ [ 'files' => [ 'Ok.ttf' => [ 'remote_path' => '../../etc/passwd' ] + $file ] ] ],
			'absolute remote_path'        => [ [ 'files' => [ 'Ok.ttf' => [ 'remote_path' => '/etc/passwd' ] + $file ] ] ],
			'filename with a path'        => [ [ 'files' => [ 'sub/Ok.ttf' => $file ] ] ],
			'dotfile'                     => [ [ 'files' => [ '.htaccess' => $file ] ] ],
			'unknown extension'           => [ [ 'files' => [ 'Ok.php' => $file ] ] ],
			'missing remote_path'         => [ [ 'files' => [ 'Ok.ttf' => [ 'sha256' => '', 'size' => 1 ] ] ] ],
			'role names an unlisted file' => [ [ 'fonts' => [ 'ok' => [ 'R' => 'Missing.ttf' ] ] ] ],
			'a face role naming a list'   => [ [ 'fonts' => [ 'ok' => [ 'R' => [ 'NotoSansSC-Regular.ttf' ] ] ] ] ],
			'an empty licence list'       => [ [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'LICENSE' => [] ] ] ] ],
			'a licence list with a hole'  => [ [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'LICENSE' => [ 'NotoSansSC-Regular.ttf', 3 ] ] ] ] ],
			'a licence naming no file'    => [ [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'LICENSE' => [ 'NotoSansSC-Regular.ttf', 'Missing.txt' ] ] ] ] ],
			'font key with a slash'       => [ [ 'fonts' => [ 'ok/evil' => [ 'R' => 'NotoSansSC-Regular.ttf' ] ] ] ],
			'font key with a dot'         => [ [ 'fonts' => [ '../evil' => [ 'R' => 'NotoSansSC-Regular.ttf' ] ] ] ],
			'invalid sip-ext target'      => [ [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'sip-ext' => 'a/b' ] ] ] ],
			'invalid language_to_font'    => [ [ 'language_to_font' => [ 'zh' => 'evil/key' ] ] ],
			'variant names no file'       => [ [ 'variants' => [ '300' => 'Missing.ttf' ] ] ],
			'invalid variant id'          => [ [ 'variants' => [ 'a/b' => 'NotoSansSC-Regular.ttf' ] ] ],
			'traversing preview'          => [ [ 'preview' => '../evil' ] ],
		];
	}

	/**
	 * A copyleft face ships a notice *and* the full text the notice cites, so `LICENSE` is the one role that may
	 * name a list. Each file becomes a role of its own — the file table is unique on `(font_id, role)` — numbered
	 * in the entry's order, which is hash-pinned and so cannot shift under a site that already installed it
	 */
	public function test_a_licence_role_may_name_a_list_and_each_file_takes_its_own_role() {
		$entry = [
			'fonts' => [
				'khmeros' => [ 'R' => 'KhmerOS.ttf', 'LICENSE' => [ 'KhmerOS-LICENSE.txt', 'LGPL-2.1.txt' ] ],
			],
			'files' => [
				'KhmerOS.ttf'         => [ 'remote_path' => 'v/KhmerOS.ttf' ],
				'KhmerOS-LICENSE.txt' => [ 'remote_path' => 'v/KhmerOS-LICENSE.txt' ],
				'LGPL-2.1.txt'        => [ 'remote_path' => 'v/LGPL-2.1.txt' ],
			],
		];

		$this->assertNull( Font_Sources::validate_entry( $entry ) );

		$this->assertSame(
			[
				'R'         => 'KhmerOS.ttf',
				'LICENSE'   => 'KhmerOS-LICENSE.txt',
				'LICENSE-2' => 'LGPL-2.1.txt',
			],
			Font_Sources::role_map( $entry['fonts']['khmeros'] )
		);
	}

	public function test_a_single_licence_file_may_be_named_without_a_list() {
		$roles = [ 'R' => 'KhmerOS.ttf', 'LICENSE' => 'KhmerOS-LICENSE.txt', 'useOTL' => 255, 'sip-ext' => 'other' ];

		/* The flags are not files and drop out here, which is what saves every caller its own exclusion list */
		$this->assertSame(
			[ 'R' => 'KhmerOS.ttf', 'LICENSE' => 'KhmerOS-LICENSE.txt' ],
			Font_Sources::role_map( $roles )
		);
	}

	public function test_a_font_key_can_never_reach_mpdfs_cache_path() {
		/* Cache.php joins basePath . '/' . filename and renames onto it, so no `/` or `.` may pass */
		foreach ( [ 'a/b', 'a.b', '../x', 'a b' ] as $key ) {
			$this->assertIsString(
				Font_Sources::validate_entry( $this->valid_entry( [ 'fonts' => [ $key => [ 'R' => 'NotoSansSC-Regular.ttf' ] ] ] ) ),
				"font key {$key} should have been rejected"
			);
		}
	}

	public function test_a_sip_ext_target_outside_the_entry_is_allowed() {
		/* Sun-ExtB is its own entry, so the supplement resolves only once cjk-ext-b is installed too */
		$this->assertNull( Font_Sources::validate_entry( $this->valid_entry() ) );
	}

	/**
	 * The pipeline names upright variants by weight alone (`400`, `700`), and PHP turns a numeric JSON object key
	 * into an integer — so an `is_string()` gate here would reject every upright weight the `google` source
	 * publishes while passing every italic (`400i`), and the whole family would fail validation at install
	 */
	public function test_a_numeric_variant_id_is_valid() {
		$entry = [
			'fonts'    => [ 'lato' => [ 'R' => 'Lato-Regular.ttf' ] ],
			'variants' => [
				'400'  => 'Lato-Regular.ttf',
				'400i' => 'Lato-Regular.ttf',
			],
			'files'    => [
				'Lato-Regular.ttf' => [ 'remote_path' => 'v/Lato-Regular.ttf' ],
			],
		];

		$this->assertNull( Font_Sources::validate_entry( $entry ) );
	}
}
