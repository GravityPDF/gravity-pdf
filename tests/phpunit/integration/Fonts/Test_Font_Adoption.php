<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * Class Test_Font_Adoption
 *
 * Covers `Catalog_Font_Adopter`: files already on disk becoming installed rows without a download.
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Adoption extends TestCase {

	use HasCatalogRows;
	use HasFontRows;

	/**
	 * @var string[] Absolute paths written by a test, removed in tear down
	 */
	private $written = [];

	public function set_up(): void {
		parent::set_up();

		$this->drop_catalog_rows();
	}

	protected function adopter(): Catalog_Font_Adopter {
		return new Catalog_Font_Adopter( $this->font_repository(), $this->catalog_repository(), GPDFAPI::get_log_class() );
	}

	/**
	 * The emoji entry most of these cases vary one detail of
	 */
	protected function emoji_entry( array $file, array $overrides = [] ): array {
		return array_merge(
			[
				'fonts' => [ 'notoemoji' => [ 'R' => 'NotoEmoji-Regular.ttf' ] ],
				'files' => [ 'NotoEmoji-Regular.ttf' => $file ],
			],
			$overrides
		);
	}

	public function tear_down(): void {
		foreach ( $this->written as $path ) {
			if ( is_file( $path ) ) {
				unlink( $path );
			}
		}

		$this->written = [];

		$this->remove_font_files();
		$this->drop_catalog_rows();

		parent::tear_down();
	}

	/**
	 * Place a pack file where an install would have written it, and return what the entry must list for it
	 *
	 * @return array{sha256: string, size: int, remote_path: string}
	 */
	protected function place_file( string $source, string $entry, string $filename, string $contents ): array {
		$dir = $this->font_dir() . $source . '/' . $entry;
		wp_mkdir_p( $dir );

		$path = $dir . '/' . $filename;
		file_put_contents( $path, $contents );

		$this->written[] = $path;

		return [
			'sha256'      => hash( 'sha256', $contents ),
			'size'        => strlen( $contents ),
			'remote_path' => 'fonts-v1.0.0/' . $filename,
		];
	}

	/**
	 * Write a coverage catalog row carrying an inlined entry
	 */
	protected function catalog_entry( string $entry_id, array $entry, array $overrides = [] ): void {
		$this->insert_catalog_row(
			'packs',
			$entry_id,
			array_merge(
				[
					'label'      => ucfirst( $entry_id ),
					'coverage'   => 1,
					'version'    => 'fonts-v1.0.0',
					'entry_json' => (string) wp_json_encode( $entry ),
					'font_keys'  => implode( ',', array_keys( (array) ( $entry['fonts'] ?? [] ) ) ),
				],
				$overrides
			)
		);
	}

	public function test_a_file_on_disk_becomes_an_installed_row_without_a_download() {
		$file = $this->place_file( 'packs', 'emoji', 'NotoEmoji-Regular.ttf', 'emoji-bytes' );

		$this->catalog_entry(
			'emoji',
			[
				'fonts' => [ 'notoemoji' => [ 'R' => 'NotoEmoji-Regular.ttf', 'useOTL' => 255 ] ],
				'files' => [ 'NotoEmoji-Regular.ttf' => $file ],
			]
		);

		$this->assertSame( 1, $this->adopter()->run( 'packs' ) );

		$row = $this->font_repository()->get( 'notoemoji' );

		$this->assertSame( 'packs', $row['source'] );
		$this->assertSame( 'emoji', $row['entry'] );
		$this->assertSame( 1, $row['coverage'] );
		$this->assertSame( 255, $row['use_otl'] );
		$this->assertSame( 'fonts-v1.0.0', $row['version'] );
		$this->assertSame( 'packs/emoji/NotoEmoji-Regular.ttf', $row['files']['R']['path'] );
		$this->assertSame( $file['sha256'], $row['files']['R']['sha256'] );
		$this->assertSame( $file['size'], $row['files']['R']['size'] );
	}

	public function test_adoption_is_idempotent() {
		$file = $this->place_file( 'packs', 'emoji', 'NotoEmoji-Regular.ttf', 'emoji-bytes' );

		$this->catalog_entry( 'emoji', $this->emoji_entry( $file ) );

		$this->assertSame( 1, $this->adopter()->run( 'packs' ) );
		$this->assertSame( 0, $this->adopter()->run( 'packs' ), 'a second pass must insert nothing' );
	}

	public function test_a_file_whose_hash_does_not_match_gets_no_row() {
		$file           = $this->place_file( 'packs', 'emoji', 'NotoEmoji-Regular.ttf', 'emoji-bytes' );
		$file['sha256'] = str_repeat( 'f', 64 );

		$this->catalog_entry( 'emoji', $this->emoji_entry( $file ) );

		/* A row must never claim a sha256 the disk does not have; a real install downloads it fresh instead */
		$this->assertSame( 0, $this->adopter()->run( 'packs' ) );
		$this->assertNull( $this->font_repository()->get( 'notoemoji' ) );
	}

	public function test_a_truncated_file_is_rejected_on_size_before_it_is_hashed() {
		$file         = $this->place_file( 'packs', 'emoji', 'NotoEmoji-Regular.ttf', 'short' );
		$file['size'] = 99999;

		$this->catalog_entry( 'emoji', $this->emoji_entry( $file ) );

		$this->assertSame( 0, $this->adopter()->run( 'packs' ) );
	}

	public function test_an_absent_file_is_simply_not_adopted() {
		$this->catalog_entry(
			'korean',
			[
				'fonts' => [ 'notosanskr' => [ 'R' => 'NotoSansKR-Regular.ttf' ] ],
				'files' => [
					'NotoSansKR-Regular.ttf' => [
						'sha256'      => str_repeat( 'a', 64 ),
						'size'        => 10,
						'remote_path' => 'fonts-v1.0.0/NotoSansKR-Regular.ttf',
					],
				],
			]
		);

		$this->assertSame( 0, $this->adopter()->run( 'packs' ) );
	}

	public function test_a_font_without_a_verified_regular_face_is_skipped_entirely() {
		$bold = $this->place_file( 'packs', 'dejavu', 'DejaVuSans-Bold.ttf', 'bold-bytes' );

		$this->catalog_entry(
			'dejavu',
			[
				'fonts' => [
					'dejavusans' => [
						'R' => 'DejaVuSans.ttf',
						'B' => 'DejaVuSans-Bold.ttf',
					],
				],
				'files' => [
					'DejaVuSans.ttf'      => [
						'sha256'      => str_repeat( 'a', 64 ),
						'size'        => 10,
						'remote_path' => 'fonts-v1.0.0/DejaVuSans.ttf',
					],
					'DejaVuSans-Bold.ttf' => $bold,
				],
			]
		);

		/* mPDF needs R; a bold-only row would never render */
		$this->assertSame( 0, $this->adopter()->run( 'packs' ) );
		$this->assertNull( $this->font_repository()->get( 'dejavusans' ) );
	}

	public function test_every_present_face_of_a_family_is_adopted_together() {
		$regular = $this->place_file( 'packs', 'dejavu', 'DejaVuSans.ttf', 'regular-bytes' );
		$bold    = $this->place_file( 'packs', 'dejavu', 'DejaVuSans-Bold.ttf', 'bold-bytes' );

		$this->catalog_entry(
			'dejavu',
			[
				'fonts' => [
					'dejavusans' => [
						'R' => 'DejaVuSans.ttf',
						'B' => 'DejaVuSans-Bold.ttf',
						'I' => 'DejaVuSans-Oblique.ttf',
					],
				],
				'files' => [
					'DejaVuSans.ttf'         => $regular,
					'DejaVuSans-Bold.ttf'    => $bold,
					'DejaVuSans-Oblique.ttf' => [
						'sha256'      => str_repeat( 'a', 64 ),
						'size'        => 10,
						'remote_path' => 'fonts-v1.0.0/DejaVuSans-Oblique.ttf',
					],
				],
			]
		);

		$this->assertSame( 1, $this->adopter()->run( 'packs' ) );

		$row = $this->font_repository()->get( 'dejavusans' );

		/* A partially installed entry is a normal state: the italic simply is not there yet */
		$this->assertSame( [ 'R', 'B' ], array_keys( $row['files'] ) );
	}

	public function test_a_multi_font_entry_becomes_one_row_per_key() {
		$tinos   = $this->place_file( 'packs', 'serif-mono', 'Tinos-Regular.ttf', 'tinos' );
		$cousine = $this->place_file( 'packs', 'serif-mono', 'Cousine-Regular.ttf', 'cousine' );

		$this->catalog_entry(
			'serif-mono',
			[
				'fonts' => [
					'tinos'   => [ 'R' => 'Tinos-Regular.ttf' ],
					'cousine' => [ 'R' => 'Cousine-Regular.ttf' ],
				],
				'files' => [
					'Tinos-Regular.ttf'   => $tinos,
					'Cousine-Regular.ttf' => $cousine,
				],
			]
		);

		$this->assertSame( 2, $this->adopter()->run( 'packs' ) );

		/* Sharing source and entry is what groups them; the label falls back to the key rather than repeating */
		$this->assertSame( 'tinos', $this->font_repository()->get( 'tinos' )['label'] );
		$this->assertSame( 'serif-mono', $this->font_repository()->get( 'cousine' )['entry'] );
	}

	public function test_a_single_font_entry_takes_the_entry_label() {
		$file = $this->place_file( 'packs', 'emoji', 'NotoEmoji-Regular.ttf', 'emoji-bytes' );

		$this->catalog_entry( 'emoji', $this->emoji_entry( $file ), [ 'label' => 'Emoji' ] );

		$this->adopter()->run( 'packs' );

		$this->assertSame( 'Emoji', $this->font_repository()->get( 'notoemoji' )['label'] );
	}

	public function test_the_coverage_maps_are_split_onto_each_row() {
		$sc = $this->place_file( 'packs', 'chinese-simplified', 'NotoSansSC-Regular.ttf', 'sc-bytes' );

		$this->catalog_entry(
			'chinese-simplified',
			[
				'fonts'               => [
					'notosanssc' => [
						'R'       => 'NotoSansSC-Regular.ttf',
						'sip-ext' => 'sun-extb',
						'useOTL'  => 255,
					],
				],
				'files'               => [ 'NotoSansSC-Regular.ttf' => $sc ],
				'language_to_font'    => [
					'zh'       => 'notosanssc',
					'und-hans' => 'notosanssc',
					'ja'       => 'notosansjp',
				],
				'backup_subs_fonts'   => [ 'notosanssc' ],
				'bmp_fonts'           => [],
				'family_substitution' => [ 'sans_fonts' => [ 'notosanssc' ] ],
			]
		);

		$this->adopter()->run( 'packs' );

		$meta = $this->font_repository()->get( 'notosanssc' )['meta'];

		/* This key's share only: `ja` belongs to the japanese entry and must not land here */
		$this->assertSame( [ 'zh', 'und-hans' ], $meta['languages'] );
		$this->assertTrue( $meta['backup_subs'] );
		$this->assertFalse( $meta['bmp'] );
		$this->assertSame( [ 'sans_fonts' ], $meta['family_substitution'] );
		$this->assertSame( 'sun-extb', $meta['sip_ext'] );
	}

	public function test_a_key_something_else_already_answers_to_is_left_alone() {
		$this->install_font_row( 'notoemoji' );

		$file = $this->place_file( 'packs', 'emoji', 'NotoEmoji-Regular.ttf', 'emoji-bytes' );

		$this->catalog_entry( 'emoji', $this->emoji_entry( $file ) );

		$this->assertSame( 0, $this->adopter()->run( 'packs' ) );

		/* The existing row wins: re-keying would register a font under a name no template mentions */
		$this->assertSame( 'custom', $this->font_repository()->get( 'notoemoji' )['source'] );
	}

	public function test_a_display_entry_is_never_adopted() {
		$file = $this->place_file( 'google', 'lato', 'Lato-Regular.ttf', 'lato-bytes' );

		$this->insert_catalog_row(
			'google',
			'lato',
			[
				'coverage'   => 0,
				'entry_json' => (string) wp_json_encode(
					[
						'fonts' => [ 'lato' => [ 'R' => 'Lato-Regular.ttf' ] ],
						'files' => [ 'Lato-Regular.ttf' => $file ],
					]
				),
			]
		);

		/*
		 * Run against `google` deliberately: with `packs` the source filter alone would answer 0 and the coverage
		 * rule — a display family is an explicit install, never adopted — would go untested.
		 */
		$this->assertSame( 0, $this->adopter()->run( 'google' ) );
	}

	public function test_adoption_is_scoped_to_the_replaced_source() {
		$emoji = $this->place_file( 'packs', 'emoji', 'NotoEmoji-Regular.ttf', 'emoji-bytes' );
		$other = $this->place_file( 'other', 'thing', 'Thing-Regular.ttf', 'thing-bytes' );

		$this->catalog_entry( 'emoji', $this->emoji_entry( $emoji ) );
		$this->insert_catalog_row(
			'other',
			'thing',
			[
				'coverage'   => 1,
				'entry_json' => (string) wp_json_encode(
					[
						'fonts' => [ 'thing' => [ 'R' => 'Thing-Regular.ttf' ] ],
						'files' => [ 'Thing-Regular.ttf' => $other ],
					]
				),
			]
		);

		/* One sync replaces one source; walking every source per replacement re-reads the catalogue S times */
		$this->assertSame( 1, $this->adopter()->run( 'packs' ) );
		$this->assertNull( $this->font_repository()->get( 'thing' ) );
	}

	public function test_a_pointed_at_entry_is_skipped() {
		$this->place_file( 'packs', 'korean', 'NotoSansKR-Regular.ttf', 'kr-bytes' );

		$this->insert_catalog_row(
			'packs',
			'korean',
			[
				'coverage'     => 1,
				'entry_sha256' => str_repeat( 'c', 64 ),
			]
		);

		/* Without an inlined entry there is nothing naming the files, and adoption never fetches */
		$this->assertSame( 0, $this->adopter()->run( 'packs' ) );
	}

	public function test_a_file_another_row_already_claims_is_not_adopted_twice() {
		$file = $this->place_file( 'packs', 'emoji', 'NotoEmoji-Regular.ttf', 'emoji-bytes' );

		$this->catalog_entry( 'emoji', $this->emoji_entry( $file ) );

		$this->adopter()->run( 'packs' );

		/* A second entry naming the same file must not mint a second row over it */
		$this->catalog_entry(
			'emoji-alt',
			[
				'fonts' => [ 'notoemojialt' => [ 'R' => 'NotoEmoji-Regular.ttf' ] ],
				'files' => [ 'NotoEmoji-Regular.ttf' => $file ],
			]
		);

		$this->assertSame( 0, $this->adopter()->run( 'packs' ) );
	}

	public function test_a_flat_legacy_file_is_not_what_adoption_looks_at() {
		/* The 6.x installer wrote flat; those are Legacy_Font_Adopter's and are rows before this runs */
		$path = $this->font_dir() . 'NotoEmoji-Regular.ttf';
		file_put_contents( $path, 'emoji-bytes' );
		$this->written[] = $path;

		$this->catalog_entry(
			'emoji',
			[
				'fonts' => [ 'notoemoji' => [ 'R' => 'NotoEmoji-Regular.ttf' ] ],
				'files' => [
					'NotoEmoji-Regular.ttf' => [
						'sha256'      => hash( 'sha256', 'emoji-bytes' ),
						'size'        => strlen( 'emoji-bytes' ),
						'remote_path' => 'fonts-v1.0.0/NotoEmoji-Regular.ttf',
					],
				],
			]
		);

		$this->assertSame( 0, $this->adopter()->run( 'packs' ) );
	}
}
