<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontFixtures;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;
use WP_Error;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Font_Installer
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Installer extends TestCase {

	use HasCatalogRows;
	use HasFontFixtures;
	use MocksHttpRequests;

	/**
	 * @var Font_Installer
	 */
	public $installer;

	/**
	 * @var string
	 */
	public $font_dir;

	public function set_up(): void {
		parent::set_up();

		global $gfpdf;

		$this->installer = $gfpdf->get_font_installer();
		$this->font_dir  = $gfpdf->get_font_repository()->get_font_dir();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();
	}

	public function tear_down(): void {
		global $gfpdf;

		$this->unmock_http();
		$this->drop_catalog_rows();

		foreach ( [ 'packs', 'google' ] as $source ) {
			GPDFAPI::get_misc_class()->rmdir( $this->font_dir . $source );
		}

		foreach ( glob( $gfpdf->get_font_downloader()->get_tmp_dir() . '*.part' ) ?: [] as $part ) {
			unlink( $part );
		}

		$gfpdf->get_font_repository()->flush();

		parent::tear_down();
	}

	/**
	 * A coverage entry with one font and one file, and the bytes its index describes
	 *
	 * @param array $overrides The entry object's own fields
	 * @param array $columns   The catalog row's, for the handful of tests that turn on a column rather than a font
	 */
	protected function seed_pack( string $entry = 'emoji', array $overrides = [], ?string $body = null, array $columns = [] ): array {
		$body = $body ?? $this->font_bytes();
		$data = array_merge(
			[
				'fonts'             => [ 'notoemoji' => [ 'R' => 'NotoEmoji.ttf', 'useOTL' => 255 ] ],
				'files'             => [
					'NotoEmoji.ttf' => [
						'sha256'      => hash( 'sha256', $body ),
						'size'        => strlen( $body ),
						'remote_path' => 'fonts-v1.0.0/NotoEmoji.ttf',
					],
				],
				'language_to_font'  => [ 'und-Zsye' => 'notoemoji' ],
				'backup_subs_fonts' => [ 'notoemoji' ],
			],
			$overrides
		);

		$this->insert_catalog_row(
			'packs',
			$entry,
			array_merge(
				[
					'coverage'   => 1,
					'files'      => count( $data['files'] ),
					'entry_json' => (string) wp_json_encode( $data ),
				],
				$columns
			)
		);

		$this->mock_http( [ 'fonts.gravitypdf.com' => $body ] );

		return $data;
	}

	protected function font( string $key ): ?array {
		global $gfpdf;

		return $gfpdf->get_font_repository()->get( $key );
	}

	protected function status( string $entry = 'emoji' ): array {
		return (array) $this->catalog_repository()->entry( 'packs', $entry );
	}

	public function test_an_install_writes_the_file_the_row_and_its_coverage_meta() {
		$body = $this->font_bytes();
		$this->seed_pack( 'emoji', [], $body );

		$this->assertTrue( $this->installer->install( 'packs/emoji' ) );

		/* Source installs are namespaced, so a download can never overwrite a flat upload or import */
		$this->assertFileExists( $this->font_dir . 'packs/emoji/NotoEmoji.ttf' );
		$this->assertSame( $body, file_get_contents( $this->font_dir . 'packs/emoji/NotoEmoji.ttf' ) );

		$font = $this->font( 'notoemoji' );

		$this->assertSame( 'packs', $font['source'] );
		$this->assertSame( 'emoji', $font['entry'] );
		$this->assertSame( 1, $font['coverage'] );
		$this->assertSame( 255, $font['use_otl'] );
		$this->assertSame( 'fonts-v1.0.0', $font['version'] );

		/* The registry builds every mPDF fallback array from the rows alone, so the maps are copied onto the row */
		$this->assertSame( [ 'und-Zsye' ], $font['meta']['languages'] );
		$this->assertTrue( $font['meta']['backup_subs'] );

		$this->assertSame( 'packs/emoji/NotoEmoji.ttf', $font['files']['R']['path'] );
		$this->assertSame( hash( 'sha256', $body ), $font['files']['R']['sha256'] );
		$this->assertSame( strlen( $body ), $font['files']['R']['size'] );
	}

	public function test_a_successful_install_leaves_no_phase_on_the_catalog_row() {
		$this->seed_pack();

		$this->installer->install( 'packs/emoji' );

		$this->assertNull( $this->status()['phase'] );
		$this->assertNull( $this->status()['error'] );
	}

	/**
	 * @dataProvider provider_install_failures
	 */
	public function test_a_failed_install_records_the_code_and_a_backoff( $response, string $expected ) {
		$this->seed_pack();
		$this->mock_http( [ 'fonts.gravitypdf.com' => is_string( $response ) ? $this->broken_body( $response ) : $response ] );

		$result = $this->installer->install( 'packs/emoji' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $expected, $result->get_error_code() );

		$status = $this->status();

		$this->assertSame( 'failed', $status['phase'] );
		$this->assertSame( $expected, $status['error'] );
		$this->assertNotNull( $status['retry_after'], 'without a backoff every trigger would re-claim a broken entry' );

		/* Nothing half-installed: no row claims a file the disk does not have */
		$this->assertNull( $this->font( 'notoemoji' ) );
		$this->assertFileDoesNotExist( $this->font_dir . 'packs/emoji/NotoEmoji.ttf' );
	}

	public function provider_install_failures(): array {
		return [
			'the bytes do not match the index' => [ 'corrupt', 'font_hash_mismatch' ],
			'the body is the wrong length'     => [ 'short', 'font_size_mismatch' ],
			'the origin is down'               => [ [ 'body' => '', 'code' => 503 ], 'font_http_error' ],
		];
	}

	/**
	 * `corrupt` keeps the fixture's length on purpose: a shorter body trips the size check first, and the hash case
	 * would never reach the hash it claims to test
	 */
	protected function broken_body( string $kind ): string {
		return $kind === 'corrupt' ? $this->corrupt_font_bytes() : substr( $this->font_bytes(), 0, 128 );
	}

	/**
	 * The whole point of parsing at install time: mPDF throws on a font it cannot read, and if the first parse
	 * happens mid-render the PDF dies instead
	 */
	public function test_a_font_mpdf_cannot_parse_fails_the_entry_rather_than_a_later_render() {
		/* Right size, right hash, but not a font — a truncated upload at the origin looks exactly like this */
		$body = str_repeat( 'NOT-A-FONT-AT-ALL', 64 );
		$this->seed_pack( 'emoji', [], $body );

		$result = $this->installer->install( 'packs/emoji' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'font_unparseable', $result->get_error_code() );

		$status = $this->status();
		$this->assertSame( 'failed', $status['phase'] );
		$this->assertSame( 'font_unparseable', $status['error'] );
		$this->assertNotNull( $status['retry_after'] );
	}

	public function test_a_successful_install_leaves_the_mpdf_metrics_cache_warm() {
		global $gfpdf;

		$this->seed_pack();

		GPDFAPI::get_misc_class()->cleanup_dir( $gfpdf->data->mpdf_tmp_location );

		$this->assertTrue( $this->installer->install( 'packs/emoji' ) );

		/*
		 * Written by the install rather than by whichever render got there first — that request is normally a form
		 * submission waiting on a PDF, and this face is 146 KB where a CJK one is 17 MB.
		 */
		$this->assertFileExists( FlushCache::get_font_cache_dir() . 'notoemoji.mtx.json' );
		$this->assertFileExists( FlushCache::get_font_cache_dir() . 'notoemoji.cw.dat' );
	}

	public function test_a_line_break_dictionary_is_never_handed_to_addfont() {
		$body = $this->font_bytes();
		$dict = 'DICTIONARY-DATA';

		/* `dict_*` rows are shaper data, not faces: AddFont() would throw on one */
		$this->seed_pack(
			'thai',
			[
				'fonts' => [ 'notothai' => [ 'R' => 'NotoThai.ttf', 'dict_thai' => 'thai.txt' ] ],
				'files' => [
					'NotoThai.ttf' => [
						'sha256'      => hash( 'sha256', $body ),
						'size'        => strlen( $body ),
						'remote_path' => 'fonts-v1.0.0/NotoThai.ttf',
					],
					'thai.txt'    => [
						'sha256'      => hash( 'sha256', $dict ),
						'size'        => strlen( $dict ),
						'remote_path' => 'fonts-v1.0.0/thai.txt',
					],
				],
			]
		);

		$this->mock_http(
			[
				'NotoThai.ttf' => $body,
				'thai.txt'    => $dict,
			]
		);

		$this->assertTrue( $this->installer->install( 'packs/thai' ) );
		$this->assertNull( $this->status( 'thai' )['phase'] );
	}

	public function test_an_unknown_entry_is_refused_without_a_request() {
		$this->mock_http( [ 'fonts.gravitypdf.com' => 'never reached' ] );

		$result = $this->installer->install( 'packs/nope' );

		$this->assertSame( 'font_entry_unknown', $result->get_error_code() );
		$this->assertSame( [], $this->requested_urls() );
	}

	/**
	 * The signed root proves provenance, not content safety — and a third-party source never went through our
	 * pipeline at all
	 */
	public function test_an_entry_that_fails_validation_is_never_installed() {
		$this->seed_pack( 'emoji', [ 'fonts' => [ 'notoemoji' => [ 'R' => 'Missing.ttf' ] ] ] );

		$result = $this->installer->install( 'packs/emoji' );

		$this->assertSame( 'font_invalid_entry', $result->get_error_code() );
		$this->assertSame( [], $this->requested_urls() );
		$this->assertSame( 'font_invalid_entry', $this->status()['error'] );
	}

	public function test_a_second_install_downloads_nothing() {
		$this->seed_pack();

		$this->installer->install( 'packs/emoji' );

		$first = count( $this->requested_urls() );

		$this->assertTrue( $this->installer->install( 'packs/emoji' ) );

		/* Rows of an entry share files, so a re-install of a file already recorded and on disk is free */
		$this->assertCount( $first, $this->requested_urls() );
	}

	public function test_a_file_already_on_disk_from_a_crashed_install_is_adopted_not_refetched() {
		$body = $this->font_bytes();
		$this->seed_pack( 'emoji', [], $body );

		/* The window between a previous rename() and its row upsert */
		wp_mkdir_p( $this->font_dir . 'packs/emoji' );
		file_put_contents( $this->font_dir . 'packs/emoji/NotoEmoji.ttf', $body );

		$this->assertTrue( $this->installer->install( 'packs/emoji' ) );

		$this->assertSame( [], $this->requested_urls() );
		$this->assertNotNull( $this->font( 'notoemoji' ) );
	}

	public function test_a_file_on_disk_that_does_not_match_is_refetched() {
		$body = $this->font_bytes();
		$this->seed_pack( 'emoji', [], $body );

		wp_mkdir_p( $this->font_dir . 'packs/emoji' );
		file_put_contents( $this->font_dir . 'packs/emoji/NotoEmoji.ttf', $this->corrupt_font_bytes() );

		$this->assertTrue( $this->installer->install( 'packs/emoji' ) );

		$this->assertNotSame( [], $this->requested_urls(), 'a row must never claim a hash the disk does not have' );
		$this->assertSame( $body, file_get_contents( $this->font_dir . 'packs/emoji/NotoEmoji.ttf' ) );
	}

	public function test_a_multi_font_pack_writes_a_row_per_key_sharing_one_download() {
		$body = $this->font_bytes();

		$this->seed_pack(
			'cjk',
			[
				'fonts' => [
					'notosanssc' => [ 'R' => 'Shared.ttf' ],
					'notosansjp' => [ 'R' => 'Shared.ttf' ],
				],
				'files' => [
					'Shared.ttf' => [
						'sha256'      => hash( 'sha256', $body ),
						'size'        => strlen( $body ),
						'remote_path' => 'fonts-v1.0.0/Shared.ttf',
					],
				],
			],
			$body
		);

		$this->assertTrue( $this->installer->install( 'packs/cjk' ) );

		$this->assertNotNull( $this->font( 'notosanssc' ) );
		$this->assertNotNull( $this->font( 'notosansjp' ) );

		/* Roles landing on one file share it on disk: keying the work by filename is what makes that free */
		$this->assertCount( 1, $this->requested_urls() );

		/* A shared label across two CJK fonts helps nobody, so each row is labelled by its key */
		$this->assertSame( 'notosanssc', $this->font( 'notosanssc' )['label'] );
	}

	public function test_only_the_named_files_are_installed() {
		$body = $this->font_bytes();

		$this->seed_pack(
			'emoji',
			[
				'fonts' => [ 'notoemoji' => [ 'R' => 'NotoEmoji.ttf', 'B' => 'Other.ttf' ] ],
				'files' => [
					'NotoEmoji.ttf' => [
						'sha256'      => hash( 'sha256', $body ),
						'size'        => strlen( $body ),
						'remote_path' => 'fonts-v1.0.0/NotoEmoji.ttf',
					],
					'Other.ttf'     => [
						'sha256'      => hash( 'sha256', $body ),
						'size'        => strlen( $body ),
						'remote_path' => 'fonts-v1.0.0/Other.ttf',
					],
				],
			],
			$body
		);

		$this->assertTrue( $this->installer->install( 'packs/emoji', [ 'NotoEmoji.ttf' ] ) );

		/* A partially installed entry is a normal state: the render path fetches the rest a face at a time */
		$this->assertArrayHasKey( 'R', $this->font( 'notoemoji' )['files'] );
		$this->assertArrayNotHasKey( 'B', $this->font( 'notoemoji' )['files'] );
	}

	public function test_a_display_entry_installs_under_the_entry_id_and_re_installs_in_place() {
		$body = $this->font_bytes();

		$this->insert_catalog_row(
			'packs',
			'lato',
			[
				'label'      => 'Lato',
				'coverage'   => 0,
				'entry_json' => (string) wp_json_encode(
					[
						'fonts' => [ 'lato' => [ 'R' => 'Lato-Regular.ttf' ] ],
						'files' => [
							'Lato-Regular.ttf' => [
								'sha256'      => hash( 'sha256', $body ),
								'size'        => strlen( $body ),
								'remote_path' => 'fonts-v1.0.0/Lato-Regular.ttf',
							],
						],
					]
				),
			]
		);

		$this->mock_http( [ 'fonts.gravitypdf.com' => $body ] );

		$this->assertTrue( $this->installer->install( 'packs/lato' ) );

		$font = $this->font( 'lato' );

		$this->assertSame( 'Lato', $font['label'] );
		$this->assertSame( 0, $font['coverage'] );
		$this->assertSame( [], $font['meta'], 'a display entry has no coverage maps to copy' );

		$this->assertTrue( $this->installer->install( 'packs/lato' ) );

		/*
		 * The identity is (source, entry, label), not the key: matching on the key would find it taken by the row
		 * itself and mint `lato-ab12e` on every re-install
		 */
		$this->assertSame( (int) $font['id'], (int) $this->font( 'lato' )['id'] );
	}

	public function test_a_labelled_install_lands_under_the_key_its_label_derives() {
		$body = $this->font_bytes();

		$this->insert_catalog_row(
			'packs',
			'lato',
			[
				'label'      => 'Lato',
				'coverage'   => 0,
				'entry_json' => (string) wp_json_encode(
					[
						'fonts' => [ 'lato' => [ 'R' => 'Lato-Regular.ttf' ] ],
						'files' => [
							'Lato-Regular.ttf' => [
								'sha256'      => hash( 'sha256', $body ),
								'size'        => strlen( $body ),
								'remote_path' => 'fonts-v1.0.0/Lato-Regular.ttf',
							],
						],
					]
				),
			]
		);

		$this->mock_http( [ 'fonts.gravitypdf.com' => $body ] );

		$this->assertTrue( $this->installer->install( 'packs/lato', [], [ 'label' => 'Lato Light Ø' ] ) );

		/*
		 * The route sends a label, never a key: derivation is install policy, and a key computed at the route
		 * would be stale by the time a background batch ran. Non-key characters collapse rather than vanish.
		 */
		$font = $this->font( 'latolight' );

		$this->assertNotNull( $font, 'the label has to derive a key, not fall back to the entry id' );
		$this->assertSame( 'Lato Light Ø', $font['label'] );
		$this->assertNull( $this->font( 'lato' ), 'the entry id is only the key when no label was chosen' );
	}

	/**
	 * The variants path: any of a family's weights can fill any mPDF role, and changing that choice must leave no
	 * orphan behind
	 */
	public function test_a_variants_swap_fetches_the_new_file_and_removes_the_one_nothing_references() {
		/* Two genuinely different faces: a same-size swap is exactly what mPDF's own cache check cannot see */
		$regular = $this->font_bytes( 'Arimo-Regular' );
		$light   = $this->font_bytes( 'DejaVuSansSymbols' );

		$this->insert_catalog_row(
			'packs',
			'lato',
			[
				'label'      => 'Lato',
				'coverage'   => 0,
				'files'      => 2,
				'entry_json' => (string) wp_json_encode(
					[
						'fonts'    => [ 'lato' => [ 'R' => 'Lato-Regular.ttf' ] ],
						'variants' => [
							'400' => 'Lato-Regular.ttf',
							'300' => 'Lato-Light.ttf',
						],
						'files'    => [
							'Lato-Regular.ttf' => [
								'sha256'      => hash( 'sha256', $regular ),
								'size'        => strlen( $regular ),
								'remote_path' => 'fonts-v1.0.0/Lato-Regular.ttf',
							],
							'Lato-Light.ttf'   => [
								'sha256'      => hash( 'sha256', $light ),
								'size'        => strlen( $light ),
								'remote_path' => 'fonts-v1.0.0/Lato-Light.ttf',
							],
						],
					]
				),
			]
		);

		$this->mock_http(
			[
				'Lato-Regular.ttf' => $regular,
				'Lato-Light.ttf'   => $light,
			]
		);

		$this->assertTrue( $this->installer->install( 'packs/lato' ) );
		$this->assertFileExists( $this->font_dir . 'packs/lato/Lato-Regular.ttf' );
		$this->assertNull( $this->font( 'lato' )['files']['R']['variant'], 'a default install chose no variant' );

		$this->assertTrue( $this->installer->install( 'packs/lato', [], [ 'variants' => [ 'R' => '300' ] ] ) );

		$font = $this->font( 'lato' );

		$this->assertSame( 'packs/lato/Lato-Light.ttf', $font['files']['R']['path'] );
		$this->assertSame( '300', $font['files']['R']['variant'] );

		/* The file the role no longer references, and which no surviving row records */
		$this->assertFileDoesNotExist( $this->font_dir . 'packs/lato/Lato-Regular.ttf' );
	}

	public function test_removing_an_entry_takes_its_rows_and_its_files() {
		$this->seed_pack();

		$this->assertTrue( $this->installer->install( 'packs/emoji' ) );
		$this->assertFileExists( FlushCache::get_font_cache_dir() . 'notoemoji.mtx.json' );

		$this->assertTrue( $this->installer->remove( 'packs/emoji' ) );

		$this->assertNull( $this->font( 'notoemoji' ) );
		$this->assertFileDoesNotExist( $this->font_dir . 'packs/emoji/NotoEmoji.ttf' );

		/* mPDF re-parses on a size or `useOTL` change, so a removed key's metrics would otherwise never be evicted */
		$this->assertFileDoesNotExist( FlushCache::get_font_cache_dir() . 'notoemoji.mtx.json' );
	}

	public function test_removing_a_family_installed_under_two_names_takes_both() {
		$body = $this->font_bytes();

		$this->seed_pack(
			'lato',
			[
				'fonts'             => [ 'lato' => [ 'R' => 'Lato-Regular.ttf' ] ],
				'files'             => [
					'Lato-Regular.ttf' => [
						'sha256'      => hash( 'sha256', $body ),
						'size'        => strlen( $body ),
						'remote_path' => 'fonts-v1.0.0/Lato-Regular.ttf',
					],
				],
				'language_to_font'  => [],
				'backup_subs_fonts' => [],
			],
			$body,
			[ 'coverage' => 0 ]
		);

		$this->assertTrue( $this->installer->install( 'packs/lato' ) );
		$this->assertTrue( $this->installer->install( 'packs/lato', [], [ 'label' => 'Lato Light' ] ) );

		/* Both installs record the one file, so it survives the first row's delete and goes with the second */
		$this->assertTrue( $this->installer->remove( 'packs/lato' ) );

		$this->assertNull( $this->font( 'lato' ) );
		$this->assertNull( $this->font( 'latolight' ) );
		$this->assertFileDoesNotExist( $this->font_dir . 'packs/lato/Lato-Regular.ttf' );
	}

	public function test_removing_an_always_entry_leaves_a_tombstone() {
		$this->seed_pack( 'emoji', [], null, [ 'always' => 1 ] );

		$this->assertTrue( $this->installer->install( 'packs/emoji' ) );
		$this->assertTrue( $this->installer->remove( 'packs/emoji' ) );

		/* Without it the always rule reinstalls on the next render what the admin just declined */
		$this->assertSame( 'removed', $this->status()['phase'] );
	}

	public function test_removing_an_entry_mid_install_leaves_a_tombstone() {
		$this->seed_pack();
		$this->catalog_repository()->set_status( 'packs', 'emoji', [ 'phase' => 'queued' ] );

		$this->assertTrue( $this->installer->remove( 'packs/emoji' ) );

		/* The batch still holds this entry's files; `removed` is what stops them being put back */
		$status = $this->status();

		$this->assertSame( 'removed', $status['phase'] );

		/* The tombstone dates itself: written in the same statement, off the phase this one is replacing */
		$this->assertNotNull( $status['phase_since'] );
	}

	public function test_removing_a_failed_entry_takes_its_error_and_backoff_with_it() {
		$this->seed_pack();
		$this->catalog_repository()->set_status(
			'packs',
			'emoji',
			[
				'phase'       => 'failed',
				'error'       => 'nope',
				'retry_after' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
			]
		);

		$this->assertTrue( $this->installer->remove( 'packs/emoji' ) );

		$status = $this->status();

		/* Anything carried past the delete would only refuse the reinstall that usually follows it */
		$this->assertNull( $status['phase'] );
		$this->assertNull( $status['error'] );
		$this->assertNull( $status['retry_after'] );
	}

	public function test_removing_an_entry_that_was_never_installed_is_not_an_error() {
		$this->seed_pack();

		$this->assertTrue( $this->installer->remove( 'packs/emoji' ) );
		$this->assertNull( $this->status()['phase'] );
	}

	public function test_a_removal_is_refused_while_one_of_the_entry_files_is_installing() {
		$this->seed_pack();

		$lock = new Font_Lock();
		$this->assertTrue( $lock->acquire( 'entry_packs_emoji', 60 ) );

		try {
			$removed = $this->installer->remove( 'packs/emoji' );
		} finally {
			$lock->release( 'entry_packs_emoji' );
		}

		$this->assertWPError( $removed );
		$this->assertSame( 'font_install_in_progress', $removed->get_error_code() );
	}

	public function test_a_file_whose_entry_was_deleted_after_it_was_queued_is_never_written() {
		$this->seed_pack();
		$this->catalog_repository()->set_status( 'packs', 'emoji', [ 'phase' => 'removed' ] );

		/* The queue checks before taking the lock; this is the window between the two */
		$this->assertTrue( $this->installer->install_file( 'packs', 'emoji', 'NotoEmoji.ttf' ) );

		$this->assertNull( $this->font( 'notoemoji' ) );
		$this->assertSame( 'removed', $this->status()['phase'] );
	}
}
