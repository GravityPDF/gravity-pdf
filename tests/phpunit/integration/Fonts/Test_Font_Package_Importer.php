<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontPackages;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\MocksHttpRequests;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * What an offline package may and may not put on disk
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Package_Importer extends TestCase {

	use HasCatalogRows;
	use HasFontRows;
	use HasFontPackages;
	use MocksHttpRequests;

	/**
	 * @var Font_Package_Importer
	 */
	public $importer;

	/**
	 * @var string
	 */
	public $font_dir;

	/**
	 * @var string
	 */
	public $tmp_dir;

	public function set_up(): void {
		global $gfpdf;

		parent::set_up();

		$gfpdf->get_font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		$this->font_dir = $gfpdf->get_font_repository()->get_font_dir();
		$this->tmp_dir  = $gfpdf->get_font_downloader()->get_tmp_dir();
		$this->importer = $gfpdf->get_font_package_importer();
	}

	public function tear_down(): void {
		global $gfpdf;

		$this->remove_packages();
		$this->unmock_http();

		( new Font_Lock() )->release( Font_Sources::entry_lock( 'packs', 'emoji' ) );

		$this->drop_catalog_rows();
		$this->remove_font_rows();

		GPDFAPI::get_misc_class()->rmdir( $this->font_dir . 'packs' );
		GPDFAPI::get_misc_class()->rmdir( $this->tmp_dir );

		$gfpdf->get_font_repository()->flush();

		parent::tear_down();
	}

	protected function seed_pack( array $overrides = [] ): void {
		$this->insert_catalog_row(
			'packs',
			'emoji',
			array_merge(
				[
					'coverage'   => 1,
					'files'      => 1,
					'entry_json' => (string) wp_json_encode( $this->package_entry() ),
					'package'    => $this->package_name(),
				],
				$overrides
			)
		);
	}

	protected function installed(): string {
		return $this->font_dir . Font_Sources::install_path( 'packs', 'emoji', 'Noto.ttf' );
	}

	public function test_a_package_lands_every_file_the_catalogue_lists() {
		$this->seed_pack();

		$row = $this->importer->import( $this->package_archive() );

		$this->assertSame( 'packs', $row['source'] );
		$this->assertSame( 'emoji', $row['entry'] );
		$this->assertFileExists( $this->installed() );
		$this->assertSame( hash( 'sha256', $this->font_bytes( 'DejaVuSansSymbols' ) ), hash_file( 'sha256', $this->installed() ) );
	}

	/**
	 * The whole security claim: the archive names the row and the row names the bytes, so an archive vouching for
	 * its own contents vouches for nothing
	 */
	public function test_the_archives_own_entry_decides_nothing_but_which_row_to_check() {
		$this->seed_pack();

		$payload = $this->corrupt_font_bytes( 'DejaVuSansSymbols' );
		$entry   = $this->package_entry();

		$entry['files']['Noto.ttf']['sha256'] = hash( 'sha256', $payload );

		$error = $this->importer->import(
			$this->package_archive(
				[
					Font_Package_Importer::MANIFEST => (string) wp_json_encode( $entry ),
					'Noto.ttf'                      => $payload,
				]
			)
		);

		$this->assertSame( 'font_package_invalid', $error->get_error_code() );
		$this->assertFileDoesNotExist( $this->installed() );
	}

	public function test_a_file_that_is_not_what_the_catalogue_lists_is_refused() {
		$this->seed_pack();

		$error = $this->importer->import(
			$this->package_archive(
				[
					Font_Package_Importer::MANIFEST => (string) wp_json_encode( $this->package_entry() ),
					'Noto.ttf'                      => $this->corrupt_font_bytes( 'DejaVuSansSymbols' ),
				]
			)
		);

		$this->assertSame( 'font_package_invalid', $error->get_error_code() );
		$this->assertFileDoesNotExist( $this->installed() );
	}

	/**
	 * The cap stops the copy and the length check turns it into an error, so a decompression bomb is a 400
	 */
	public function test_a_member_that_inflates_past_the_size_the_entry_lists_is_refused() {
		$this->seed_pack();

		$error = $this->importer->import(
			$this->package_archive(
				[
					Font_Package_Importer::MANIFEST => (string) wp_json_encode( $this->package_entry() ),
					'Noto.ttf'                      => $this->font_bytes( 'DejaVuSansSymbols' ) . str_repeat( "\0", 1024 * 1024 ),
				]
			)
		);

		$this->assertSame( 'font_package_invalid', $error->get_error_code() );
		$this->assertFileDoesNotExist( $this->installed() );
	}

	/**
	 * @dataProvider provider_unlisted_members
	 */
	public function test_a_member_the_entry_does_not_list_refuses_the_whole_package( string $name ) {
		$this->seed_pack();

		$error = $this->importer->import(
			$this->package_archive(
				[
					Font_Package_Importer::MANIFEST => (string) wp_json_encode( $this->package_entry() ),
					'Noto.ttf'                      => $this->font_bytes( 'DejaVuSansSymbols' ),
					$name                           => 'payload',
				]
			)
		);

		$this->assertSame( 'font_package_invalid', $error->get_error_code() );

		/* Refused whole, so the legitimate member of the same archive is not on disk either */
		$this->assertFileDoesNotExist( $this->installed() );
	}

	/**
	 * A path is not a filename, so none of these is a file the entry lists — which is the whole traversal rule
	 */
	public function provider_unlisted_members(): array {
		return [
			'a directory'      => [ 'fonts/Noto.ttf' ],
			'a parent'         => [ '../Noto.ttf' ],
			'an absolute path' => [ '/etc/passwd' ],
			'an extra file'    => [ 'README.md' ],
		];
	}

	public function test_a_package_missing_a_file_the_entry_lists_is_refused() {
		$this->seed_pack();

		$error = $this->importer->import(
			$this->package_archive( [ Font_Package_Importer::MANIFEST => (string) wp_json_encode( $this->package_entry() ) ] )
		);

		$this->assertSame( 'font_package_invalid', $error->get_error_code() );
	}

	/**
	 * @dataProvider provider_unreadable_manifests
	 */
	public function test_a_zip_that_does_not_name_a_package_is_refused( array $members ) {
		$this->seed_pack();

		$error = $this->importer->import( $this->package_archive( $members ) );

		$this->assertSame( 'font_package_invalid', $error->get_error_code() );
	}

	public function provider_unreadable_manifests(): array {
		return [
			'no entry.json'      => [ [ 'Noto.ttf' => 'bytes' ] ],
			'not JSON'           => [ [ Font_Package_Importer::MANIFEST => 'not json' ] ],
			'no package field'   => [ [ Font_Package_Importer::MANIFEST => '{"files":{}}' ] ],
			'a path, not a name' => [ [ Font_Package_Importer::MANIFEST => '{"package":{"path":"../emoji-fonts-v1.0.0.zip"}}' ] ],
		];
	}

	/**
	 * A display entry's row is a pointer, not a document — and reaching for the entry file it points at would put
	 * a blocking HTTPS fetch on the one path that exists because the site has no network
	 *
	 * The archive embeds that file byte for byte, and the row's `entry_sha256` is what decides it is the same one,
	 * so the import verifies rather than fetches. Asserted as "asked the network for nothing", because a fetch
	 * that happens to succeed in a test environment would hide exactly the failure this prevents.
	 */
	public function test_a_pointer_entry_is_read_out_of_the_archive_instead_of_fetched() {
		$entry = $this->package_entry();

		$this->mock_http( [] );
		$this->seed_pack(
			[
				'entry_json'   => null,
				'entry_sha256' => hash( 'sha256', (string) wp_json_encode( $entry ) ),
			]
		);

		$row = $this->importer->import( $this->package_archive() );

		$this->assertSame( 'emoji', $row['entry'] );
		$this->assertSame( [], $this->requested_urls() );
		$this->assertFileExists( $this->installed() );
	}

	/**
	 * The other half of the same rule: an archive whose entry is not the one the row names installs nothing, and
	 * does not get to fall back to a fetch that would answer for a different version
	 */
	public function test_a_pointer_entry_refuses_an_archive_describing_another_version() {
		$this->mock_http( [] );
		$this->seed_pack( [ 'entry_json' => null, 'entry_sha256' => str_repeat( 'f', 64 ) ] );

		$error = $this->importer->import( $this->package_archive() );

		$this->assertSame( 'font_package_invalid', $error->get_error_code() );
		$this->assertSame( [], $this->requested_urls() );
		$this->assertFileDoesNotExist( $this->installed() );
	}

	/**
	 * The archive is found by the name the SOURCE published for it, not by `{entry}-{version}.zip` rebuilt here
	 *
	 * The spelling belongs to the pipeline, and P5b has to change it: a display family's archive needs a source
	 * segment to stay unique in a flat `files/` prefix. A lookup that reassembled the name from two columns would
	 * make that a coordinated release across two repositories, and would agree with the pipeline by luck until then.
	 */
	public function test_an_archive_is_matched_by_the_name_the_source_published_for_it() {
		$entry                    = $this->package_entry();
		$entry['package']['path'] = 'packs-emoji-fonts-v1.0.0.zip';

		$this->seed_pack( [ 'entry_json' => (string) wp_json_encode( $entry ), 'package' => 'packs-emoji-fonts-v1.0.0.zip' ] );

		$row = $this->importer->import(
			$this->package_archive(
				[
					Font_Package_Importer::MANIFEST => (string) wp_json_encode( $entry ),
					'Noto.ttf'                      => $this->font_bytes( 'DejaVuSansSymbols' ),
				]
			)
		);

		$this->assertSame( 'emoji', $row['entry'] );
		$this->assertFileExists( $this->installed() );
	}

	/**
	 * The other half: a row that published no archive cannot be reached by naming the one it would have had
	 */
	public function test_an_entry_the_source_published_no_archive_for_is_a_404() {
		$this->seed_pack( [ 'package' => null ] );

		$error = $this->importer->import( $this->package_archive() );

		$this->assertSame( 'font_entry_unknown', $error->get_error_code() );
		$this->assertFileDoesNotExist( $this->installed() );
	}

	/**
	 * The airgapped case, and the one the message has to be honest about: with nothing to verify against there is
	 * no import to make yet (§10)
	 */
	public function test_a_package_the_catalogue_does_not_list_is_a_404() {
		$error = $this->importer->import( $this->package_archive() );

		$this->assertSame( 'font_entry_unknown', $error->get_error_code() );
		$this->assertSame( 404, $error->get_error_data()['status'] );
	}

	/**
	 * The same lock an install and a removal contend for, so an import cannot write a file into an entry being
	 * deleted out from under it
	 */
	public function test_an_entry_something_else_is_working_on_is_refused() {
		$this->seed_pack();

		( new Font_Lock() )->acquire( Font_Sources::entry_lock( 'packs', 'emoji' ), Font_Installer::LOCK_TTL );

		$error = $this->importer->import( $this->package_archive() );

		$this->assertSame( 'font_install_in_progress', $error->get_error_code() );
		$this->assertSame( 409, $error->get_error_data()['status'] );
		$this->assertFileDoesNotExist( $this->installed() );
	}

	/**
	 * Nothing is left in `.tmp/` for the hourly sweep to find, on either outcome
	 */
	public function test_a_refused_package_leaves_no_part_behind() {
		$this->seed_pack();

		$this->importer->import(
			$this->package_archive(
				[
					Font_Package_Importer::MANIFEST => (string) wp_json_encode( $this->package_entry() ),
					'Noto.ttf'                      => $this->corrupt_font_bytes( 'DejaVuSansSymbols' ),
				]
			)
		);

		$this->assertSame( [], glob( $this->tmp_dir . '*.part' ) );
	}
}
