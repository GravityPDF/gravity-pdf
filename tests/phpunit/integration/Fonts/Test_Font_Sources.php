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
	 * One `files` record: every field `validate_entry()` requires, with the digest and size overridable
	 *
	 * @return array{sha256: string, size: int, remote_path: string}
	 */
	protected function file( string $remote_path, array $overrides = [] ): array {
		return $overrides + [
			'sha256'      => str_repeat( 'a', 64 ),
			'size'        => 10,
			'remote_path' => $remote_path,
		];
	}

	/**
	 * A valid coverage entry, the shape `validate_entry()` accepts
	 *
	 * Not `entry()`: `HasGfpdfFixtures` already has one with a different signature, and overriding it is a
	 * class-load fatal that PHPUnit reports as a bare exit 255.
	 */
	protected function valid_entry( array $overrides = [] ): array {
		$entry = array_merge(
			[
				'fonts' => [
					'notosanssc' => [
						'R'        => 'NotoSansSC-Regular.ttf',
						'sip-ext'  => 'sun-extb',
						'useOTL'   => 255,
					],
				],
				'files' => [
					'NotoSansSC-Regular.ttf' => $this->file( 'fonts-v1.0.0/NotoSansSC-Regular.ttf', [ 'size' => 10595932 ] ),
				],
				'language_to_font' => [ 'zh' => 'notosanssc' ],
			],
			$overrides
		);

		/* A coverage map may only name a key the entry registers, so a swapped `fonts` takes the map with it */
		if ( isset( $overrides['fonts'] ) && ! isset( $overrides['language_to_font'] ) ) {
			$entry['language_to_font'] = [ 'zh' => (string) array_key_first( (array) $entry['fonts'] ) ];
		}

		return $entry;
	}

	public function test_the_packs_record_is_registered() {
		$packs = $this->sources->get( 'packs' );

		$this->assertInstanceOf( Font_Source::class, $packs );
		$this->assertSame( 'packs', $packs->get_id() );
		$this->assertSame( 'Language packs', $packs->get_label() );
		$this->assertNotSame( '', $packs->get_description() );
	}

	public function test_the_google_record_is_registered() {
		$google = $this->sources->get( 'google' );

		$this->assertInstanceOf( Font_Source::class, $google );
		$this->assertSame( 'Google Fonts', $google->get_label() );
		$this->assertNotSame( '', $google->get_description() );
	}

	public function test_both_built_ins_share_one_root() {
		/* Which is what makes them one root request between them rather than two */
		$this->assertSame(
			$this->sources->get( 'packs' )->get_root_url(),
			$this->sources->get( 'google' )->get_root_url()
		);
	}

	public function test_core_registers_exactly_these_two() {
		$this->assertSame( [ 'packs', 'google' ], array_keys( $this->sources->all() ) );
	}

	/**
	 * @dataProvider provider_built_in_ids
	 */
	public function test_the_built_in_root_request_carries_no_query_args( string $id ) {
		/* A licence key must never reach the fonts host or its CDN cache key */
		$this->assertSame( [], $this->sources->get( $id )->get_request_args() );
	}

	public function provider_built_in_ids(): array {
		return [
			'packs'  => [ 'packs' ],
			'google' => [ 'google' ],
		];
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
		$this->assertSame( 'West Asian', Font_Sources::translate_entry( 'packs', 'west-asian', 'label', 'West Asian' ) );

		/* A pack the server adds before the plugin ships its translation keeps the index string */
		$this->assertSame( 'Klingon', Font_Sources::translate_entry( 'packs', 'klingon', 'label', 'Klingon' ) );

		/* Another source's values are proper nouns and pass through */
		$this->assertSame( 'Lato', Font_Sources::translate_entry( 'google', 'lato', 'label', 'Lato' ) );
	}

	/**
	 * The one place a pack id is frozen plugin-side, so it is pinned rather than spot-checked
	 *
	 * A label the plugin has not shipped degrades to the index's own English string, but an id that never reaches a
	 * release before its pack publishes cannot be translated at all — and a stale id is dead weight nobody notices.
	 */
	public function test_the_pack_list_is_the_seventeen_the_catalogue_publishes() {
		$ids = array_map(
			static function ( string $key ): string {
				return explode( '/', $key )[1];
			},
			array_keys( Font_Sources::get_translations() )
		);

		$this->assertSame(
			[
				'popular-sans',
				'popular-serif',
				'popular-mono',
				'popular-cursive',
				'emoji',
				'chinese-simplified',
				'japanese',
				'chinese-traditional',
				'indic',
				'arabic',
				'korean',
				'african',
				'west-asian',
				'southeast-asian',
				'insular-southeast-asian',
				'central-asian',
				'americas',
			],
			$ids
		);
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
		$file = $this->file( 'fonts-v1.0.0/Ok.ttf' );

		/*
		 * A `files` override replaces the whole map, so keying one on any other name makes the base entry's role
		 * name a file that is no longer listed — and the case then passes on *that*, whatever it meant to test.
		 * Every case below that is about a file's own fields keys it under the name the role already uses.
		 */
		$listed = static function ( array $record ): array {
			return [ 'NotoSansSC-Regular.ttf' => $record ];
		};

		return [
			'no files'                    => [ [ 'files' => [] ] ],
			'no fonts'                    => [ [ 'fonts' => [] ] ],
			'traversing remote_path'      => [ [ 'files' => [ 'Ok.ttf' => [ 'remote_path' => '../../etc/passwd' ] + $file ] ] ],
			'absolute remote_path'        => [ [ 'files' => [ 'Ok.ttf' => [ 'remote_path' => '/etc/passwd' ] + $file ] ] ],
			'filename with a path'        => [ [ 'files' => [ 'sub/Ok.ttf' => $file ] ] ],
			'dotfile'                     => [ [ 'files' => [ '.htaccess' => $file ] ] ],
			'unknown extension'           => [ [ 'files' => [ 'Ok.php' => $file ] ] ],
			'missing remote_path'         => [ [ 'files' => [ 'Ok.ttf' => [ 'sha256' => '', 'size' => 1 ] ] ] ],
			'missing sha256'              => [ [ 'files' => $listed( [ 'size' => 10, 'remote_path' => 'v/Ok.ttf' ] ) ] ],
			'malformed sha256'            => [ [ 'files' => $listed( [ 'sha256' => str_repeat( 'g', 64 ) ] + $file ) ] ],
			/* The store hashes with `hash()`, which is lowercase — an uppercase digest is a hand-edited index */
			'uppercase sha256'            => [ [ 'files' => $listed( [ 'sha256' => strtoupper( str_repeat( 'a', 64 ) ) ] + $file ) ] ],
			'sha256 that is not a string' => [ [ 'files' => $listed( [ 'sha256' => 12345 ] + $file ) ] ],
			'missing size'                => [ [ 'files' => $listed( [ 'sha256' => str_repeat( 'a', 64 ), 'remote_path' => 'v/Ok.ttf' ] ) ] ],
			'zero size'                   => [ [ 'files' => $listed( [ 'size' => 0 ] + $file ) ] ],
			/* The published artefact emits an int; accepting "10" accepts a build that started stringifying */
			'size as a string'            => [ [ 'files' => $listed( [ 'size' => '10' ] + $file ) ] ],
			'alias of a registered key'   => [ [ 'aliases' => [ 'notosanssc' => 'notosanssc' ] ] ],
			'alias with a slash'          => [ [ 'aliases' => [ 'a/b' => 'notosanssc' ] ] ],
			'alias with a space'          => [ [ 'aliases' => [ 'old name' => 'notosanssc' ] ] ],
			'alias naming no key'         => [ [ 'aliases' => [ 'oldname' => 'nosuchkey' ] ] ],
			'role names an unlisted file' => [ [ 'fonts' => [ 'ok' => [ 'R' => 'Missing.ttf' ] ] ] ],
			'a face role naming a list'   => [ [ 'fonts' => [ 'ok' => [ 'R' => [ 'NotoSansSC-Regular.ttf' ] ] ] ] ],
			'a typo for a face role'      => [ [ 'fonts' => [ 'ok' => [ 'Bl' => 'NotoSansSC-Regular.ttf' ] ] ] ],
			'a role of the wrong case'    => [ [ 'fonts' => [ 'ok' => [ 'r' => 'NotoSansSC-Regular.ttf' ] ] ] ],
			'an empty licence list'       => [ [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'LICENSE' => [] ] ] ] ],
			'a licence list with a hole'  => [ [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'LICENSE' => [ 'NotoSansSC-Regular.ttf', 3 ] ] ] ] ],
			'a licence naming no file'    => [ [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'LICENSE' => [ 'NotoSansSC-Regular.ttf', 'Missing.txt' ] ] ] ] ],
			'font key with a slash'       => [ [ 'fonts' => [ 'ok/evil' => [ 'R' => 'NotoSansSC-Regular.ttf' ] ] ] ],
			'font key with a dot'         => [ [ 'fonts' => [ '../evil' => [ 'R' => 'NotoSansSC-Regular.ttf' ] ] ] ],
			'invalid sip-ext target'      => [ [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'sip-ext' => 'a/b' ] ] ] ],
			'invalid language_to_font'    => [ [ 'language_to_font' => [ 'zh' => 'evil/key' ] ] ],
			'a row for a key not here'    => [ [ 'language_to_font' => [ 'ko' => 'unbatang' ] ] ],
			'a backup_subs key not here'  => [ [ 'backup_subs_fonts' => [ 'freesans' ] ] ],
			'a bmp key not here'          => [ [ 'bmp_fonts' => [ 'dejavusans' ] ] ],
			'a substitution key not here' => [ [ 'family_substitution' => [ 'serif_fonts' => [ 'freeserif' ] ] ] ],
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
				'KhmerOS.ttf'         => $this->file( 'v/KhmerOS.ttf' ),
				'KhmerOS-LICENSE.txt' => $this->file( 'v/KhmerOS-LICENSE.txt' ),
				'LGPL-2.1.txt'        => $this->file( 'v/LGPL-2.1.txt' ),
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

	/**
	 * `insert_file()` refuses a role outside the vocabulary and `install_complete()` is satisfied only once every
	 * downloaded file has a row, so a `Bl` for `BI` used to download the file and leave the entry `installing` for
	 * good. The vocabulary is one predicate now, read here as well, so the mistake costs the source index at sync
	 * — where a build error belongs — instead of every site that installs the pack (§11 D1)
	 */
	public function test_a_role_the_repository_would_refuse_is_rejected_at_sync() {
		$entry = $this->valid_entry( [ 'fonts' => [ 'ok' => [ 'R' => 'NotoSansSC-Regular.ttf', 'Bl' => 'NotoSansSC-Regular.ttf' ] ] ] );

		$this->assertStringContainsString( 'Bl', (string) Font_Sources::validate_entry( $entry ) );

		/* The whole vocabulary passes, including a numbered licence role the pipeline may name outright */
		foreach ( [ 'R', 'B', 'I', 'BI', 'dict_T', 'LICENSE', 'LICENSE-2' ] as $role ) {
			$this->assertNull(
				Font_Sources::validate_entry( $this->valid_entry( [ 'fonts' => [ 'ok' => [ $role => 'NotoSansSC-Regular.ttf' ] ] ] ) ),
				"role {$role} should have been accepted"
			);
		}
	}

	/**
	 * All four coverage maps are consumed by inverting them per font key of the same entry
	 * (`Font_Sources::coverage_meta()`), so a name the entry does not register produces no row at all — the pack
	 * ships the font and nothing points at it, and nothing downstream can tell. It is how `ko` went on pointing at
	 * `unbatang` after the pack moved to Noto Sans KR, and it is the shape of every row bug the 2026-09-10 audit
	 * turned up, so it is refused at sync where the rest of the entry's mistakes are
	 */
	public function test_a_coverage_map_may_only_name_a_font_key_the_entry_registers() {
		$reason = Font_Sources::validate_entry( $this->valid_entry( [ 'language_to_font' => [ 'ko' => 'unbatang' ] ] ) );

		$this->assertIsString( $reason );
		$this->assertStringContainsString( 'unbatang', $reason );
		$this->assertStringContainsString( 'language_to_font', $reason );

		/* And the same key, once the entry registers it, is fine */
		$this->assertNull(
			Font_Sources::validate_entry(
				$this->valid_entry(
					[
						'fonts'            => [ 'unbatang' => [ 'R' => 'NotoSansSC-Regular.ttf' ] ],
						'language_to_font' => [ 'ko' => 'unbatang' ],
					]
				)
			)
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

	/**
	 * `daibannasilbook` → `daibannasil` is a rename the 7.0 packs actually make, and `Registry::font_aliases()`
	 * reads the inverted map back off each row
	 */
	public function test_an_entrys_aliases_reach_the_rows_they_name() {
		$entry = $this->valid_entry(
			[
				'fonts'   => [
					'daibannasil' => [ 'R' => 'NotoSansSC-Regular.ttf' ],
					'notosanssc'  => [ 'R' => 'NotoSansSC-Regular.ttf' ],
				],
				'aliases' => [
					'daibannasilbook' => 'daibannasil',
					'dai-banna'       => 'daibannasil',
					'notosanssc-old'  => 'notosanssc',
				],
			]
		);

		$this->assertNull( Font_Sources::validate_entry( $entry ) );

		$row = [ 'source' => 'packs', 'entry' => 'southeast-asian', 'coverage' => 1, 'label' => 'SE Asian', 'position' => 3 ];

		$this->assertSame(
			[ 'daibannasilbook', 'dai-banna' ],
			Font_Sources::font_row( $row, $entry, 'daibannasil' )['meta']['aliases']
		);

		$this->assertSame(
			[ 'notosanssc-old' ],
			Font_Sources::font_row( $row, $entry, 'notosanssc' )['meta']['aliases']
		);
	}

	/**
	 * A display family carries no coverage maps, so it gets no `meta` from `coverage_meta()` — but renaming is
	 * something any source can do, and Google has renamed a published family before now. Filing aliases as a
	 * coverage concern would drop a display family's rename at row-write time, after validation accepted it
	 */
	public function test_a_display_family_carries_its_aliases_too() {
		$entry = [
			'fonts'   => [ 'notosans' => [ 'R' => 'NotoSans-Regular.ttf' ] ],
			'files'   => [ 'NotoSans-Regular.ttf' => $this->file( 'v/NotoSans-Regular.ttf' ) ],
			'aliases' => [ 'droidsans' => 'notosans' ],
		];

		$this->assertNull( Font_Sources::validate_entry( $entry ) );

		$row = [ 'source' => 'google', 'entry' => 'noto-sans', 'coverage' => 0, 'label' => 'Noto Sans' ];

		$this->assertSame(
			[ 'droidsans' ],
			Font_Sources::font_row( $row, $entry, 'notosans' )['meta']['aliases']
		);
	}

	/**
	 * And a row with nothing to claim carries no key at all, rather than an empty list on all seventeen packs
	 */
	public function test_a_row_with_no_aliases_carries_no_alias_key() {
		$row = [ 'source' => 'packs', 'entry' => 'emoji', 'coverage' => 1, 'label' => 'Emoji', 'position' => 1 ];

		$this->assertArrayNotHasKey(
			'aliases',
			Font_Sources::font_row( $row, $this->valid_entry(), 'notosanssc' )['meta']
		);
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
				'Lato-Regular.ttf' => $this->file( 'v/Lato-Regular.ttf' ),
			],
		];

		$this->assertNull( Font_Sources::validate_entry( $entry ) );
	}
}
