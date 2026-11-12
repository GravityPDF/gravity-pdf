<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\Seamed_Font_Downloader;
use GFPDF\Tests\Concerns\StubsFontSources;
use GFPDF\Tests\Integration\TestCase;
use GFPDF_Vendor\Monolog\Handler\TestHandler;
use GFPDF_Vendor\Monolog\Logger;
use GPDFAPI;
use WP_Error;

/**
 * The Font Manager's downloaded fonts: listing them with why they're in use, and deleting the unused ones
 *
 * @group model
 * @group fonts
 */
class Test_Downloaded_Fonts extends TestCase {

	use CopiesFontFixtures;
	use RecordsFontFailures;
	use StubsFontSources;

	/**
	 * @var string[] The DejaVu Serif Condensed family's files
	 */
	private const SERIF = [ 'DejaVuSerifCondensed.ttf', 'DejaVuSerifCondensed-Bold.ttf', 'DejaVuSerifCondensed-Italic.ttf', 'DejaVuSerifCondensed-BoldItalic.ttf' ];

	/**
	 * @var TestHandler
	 */
	private $log;

	public function set_up(): void {
		parent::set_up();

		GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
	}

	public function tear_down(): void {
		$this->remove_font_fixtures();
		$this->delete_font_failures();
		GPDFAPI::get_options_class()->delete_option( 'default_font' );

		parent::tear_down();
	}

	public function test_only_downloadable_families_with_files_on_disk_are_listed() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed-Bold.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'gfpdf-Arimo-Regular.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuinfo.txt' );

		$this->assertSame(
			[
				[
					'id'           => 'dejavuserifcondensed',
					'font_name'    => 'Dejavu Serif Condensed',
					'type'         => 'core',
					'regular'      => 'DejaVuSerifCondensed.ttf',
					'italics'      => '',
					'bold'         => '',
					'bolditalics'  => '',
					'can_delete'   => ! is_multisite() || is_super_admin(),
					'size_label'   => size_format( filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ), 1 ),
					'file_sizes'   => [
						'regular' => filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ),
					],
					'auto_install' => true,
					'can_install'  => ! is_multisite() || is_super_admin(),
					'languages'    => [ 'Latin', 'Greek', 'Cyrillic', 'Armenian', 'Georgian' ],
					'sample'       => '',
				],
			],
			$this->core_rows( $this->downloader() )
		);
	}

	public function test_core_fonts_not_on_disk_are_available_with_their_download_size() {
		$rows = wp_list_filter( $this->downloader()->get_installed_fonts(), [ 'type' => 'available' ] );
		$font = array_values( wp_list_filter( $rows, [ 'id' => 'freeserif' ] ) )[0];

		$this->assertSame( 'FreeSerif.ttf', $font['regular'] );
		$this->assertFalse( $font['can_delete'] );
		$this->assertSame( ! is_multisite() || is_super_admin(), $font['can_install'] );
		$this->assertNotSame( '', $font['size_label'] );
		$this->assertSame( [ 'regular', 'italics', 'bold', 'bolditalics' ], array_keys( $font['file_sizes'] ) );
		$this->assertStringStartsWith( 'https://raw.githubusercontent.com/GravityPDF/mpdf-core-fonts/', $font['base_url'] );
		$this->assertContains( 'Thai', $font['languages'] );

		/* Fallback and barcode fonts can't be chosen for a PDF */
		$this->assertSame( [], wp_list_filter( $rows, [ 'id' => 'ocrb' ] ) );
	}

	public function test_core_fonts_follow_the_order_of_the_font_dropdown() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );

		$dropdown = [];
		foreach ( GPDFAPI::get_options_class()->get_installed_fonts() as $group ) {
			$dropdown = array_merge( $dropdown, array_keys( $group ) );
		}

		foreach ( [ 'core', 'available' ] as $type ) {
			$ids = array_column( wp_list_filter( $this->downloader()->get_installed_fonts(), [ 'type' => $type ] ), 'id' );

			$this->assertNotEmpty( $ids );
			$this->assertSame( array_values( array_intersect( $dropdown, $ids ) ), $ids, $type );
		}
	}

	public function test_every_font_available_to_install_says_what_it_supports() {
		$rows = wp_list_filter( $this->downloader()->get_installed_fonts(), [ 'type' => 'available' ] );

		$this->assertNotEmpty( $rows );
		foreach ( $rows as $row ) {
			$this->assertNotEmpty( $row['languages'], $row['id'] );
		}
	}

	public function test_core_fonts_are_not_available_with_downloads_off() {
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$this->assertSame( [], wp_list_filter( $this->downloader()->get_installed_fonts(), [ 'type' => 'available' ] ) );
	}

	public function test_deleting_with_downloads_off_leaves_no_row() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$this->assertNull( $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' )['font'] );
	}

	public function test_bundled_fonts_are_listed_but_not_deletable_and_hidden_ones_are_not_listed() {
		$rows = array_values( wp_list_filter( $this->downloader()->get_installed_fonts(), [ 'type' => 'bundled' ] ) );

		$this->assertSame( [ 'arimo' ], array_column( $rows, 'id' ) );
		$this->assertSame( 'Arimo', $rows[0]['font_name'] );
		$this->assertSame( 'gfpdf-Arimo-BoldItalic.ttf', $rows[0]['bolditalics'] );
		$this->assertFalse( $rows[0]['can_delete'] );
		$this->assertNotSame( '', $rows[0]['size_label'] );
		$this->assertSame( [ 'regular', 'italics', 'bold', 'bolditalics' ], array_keys( $rows[0]['file_sizes'] ) );
	}

	public function test_custom_fonts_are_sorted_by_name_before_core_fonts_and_replace_a_core_font_of_the_same_key() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->put_freeserif_on_disk();
		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				'freeserif' => [
					'id'        => 'freeserif',
					'font_name' => 'My Free Serif',
					'regular'   => 'custom-serif.ttf',
				],
				'brand'     => [
					'id'        => 'brand',
					'font_name' => 'Brand',
					'regular'   => 'brand.ttf',
				],
			]
		);

		$fonts = array_values( wp_list_filter( $this->downloader()->get_installed_fonts(), [ 'type' => 'available' ], 'NOT' ) );

		$this->assertSame(
			[ 'arimo', 'brand', 'freeserif', 'dejavuserifcondensed' ],
			array_column( $fonts, 'id' )
		);
		$this->assertSame( [ 'bundled', 'custom', 'custom', 'core' ], array_column( $fonts, 'type' ) );
		$this->assertSame( 'My Free Serif', $fonts[2]['font_name'] );
		$this->assertTrue( $fonts[1]['can_delete'] );
	}

	public function test_a_custom_font_row_has_the_size_of_its_files_in_the_fonts_folder() {
		$this->copy_fixture( 'Chewy.ttf', 'brand.ttf' );
		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				'brand'  => [
					'id'        => 'brand',
					'font_name' => 'Brand',
					'regular'   => '/a/moved/site/brand.ttf',
					'bold'      => 'brand.ttf',
				],
				'absent' => [
					'id'        => 'absent',
					'font_name' => 'Absent',
					'regular'   => 'absent.ttf',
				],
			]
		);

		$fonts = $this->downloader()->get_installed_fonts();
		$rows  = array_column( $fonts, 'size_label', 'id' );
		$sizes = array_column( $fonts, 'file_sizes', 'id' );

		$this->assertSame( size_format( 2 * filesize( $this->font_fixture( 'Chewy.ttf' ) ), 1 ), $rows['brand'] );
		$this->assertSame( '', $rows['absent'] );
		$this->assertSame( filesize( $this->font_fixture( 'Chewy.ttf' ) ), $sizes['brand']['regular'] );
		$this->assertSame( [], $sizes['absent'] );
	}

	public function test_a_custom_font_says_why_it_is_in_use() {
		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				'brand' => [
					'id'        => 'brand',
					'font_name' => 'Brand',
					'regular'   => 'brand.ttf',
				],
			]
		);
		GPDFAPI::get_options_class()->update_option( 'default_font', 'brand' );
		$this->add_form_with_pdfs( [ [ 'font' => 'brand' ] ] );

		$usage = $this->downloader()->get_font_usage()['brand'];

		$this->assertSame( [ 'Default font' ], $usage['reasons'] );
		$this->assertSame( 'Used by 1 PDF', $usage['pdfs_label'] );
		$this->assertArrayNotHasKey( 'pdfs', $usage );
	}

	public function test_font_usage_links_to_the_settings_of_each_pdf_using_the_font() {
		$form_id = $this->add_form_with_pdfs(
			[
				'pdf-one' => [
					'name' => 'Invoice',
					'font' => 'freeserif',
				],
				'pdf-two' => [
					'name' => 'Receipt',
					'font' => 'freeserif',
				],
				'pdf-off' => [
					'name' => 'Other',
					'font' => 'garuda',
				],
			]
		);

		$pdfs = $this->downloader()->get_pdfs_using_font( 'freeserif' );

		$this->assertSame( [ 'Invoice', 'Receipt' ], array_column( $pdfs, 'name' ) );
		$this->assertSame(
			admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=PDF&id=' . $form_id . '&pid=pdf-one' ),
			$pdfs[0]['url']
		);
		$this->assertSame( 'Fonts', $pdfs[0]['form'] );
	}

	public function test_another_sites_use_of_a_key_does_not_hold_a_custom_font() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		GPDFAPI::get_options_class()->update_option(
			'custom_fonts',
			[
				'brand' => [
					'id'        => 'brand',
					'font_name' => 'Brand',
					'regular'   => 'brand.ttf',
				],
			]
		);

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		update_option( 'gfpdf_settings', [ 'default_font' => 'brand' ] );
		restore_current_blog();

		$this->assertArrayNotHasKey( 'brand', $this->downloader()->get_font_usage() );
	}

	public function test_nothing_is_listed_with_an_empty_fonts_folder() {
		$this->assertSame( [], $this->core_rows( $this->downloader() ) );
	}

	public function test_in_use_families_say_why() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->set_languages( [ 'th' ] );
		$this->add_form_with_pdfs( [ [ 'font' => 'freeserif' ], [ 'sidebar_font' => 'FreeSerif' ], [ 'font' => 'arimo' ] ] );

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'FreeMono.ttf' );
		$this->put_freeserif_on_disk();

		$sources = $this->stub_font_sources(
			static function ( array $sources ): array {
				$sources['fixture']             = $sources['core'];
				$sources['fixture']['required'] = true;
				$sources['fixture']['families'] = [ 'fixturemono' => [ 'R' => 'FreeMono.ttf' ] ];

				return $sources;
			}
		);

		$in_use = $this->downloader( $sources )->get_font_usage();

		$this->assertSame( [ 'Default font' ], $in_use['dejavuserifcondensed']['reasons'] );
		$this->assertSame( [ 'Site language (th)' ], $in_use['garuda']['reasons'] );
		$this->assertSame( [], $in_use['freeserif']['reasons'] );
		$this->assertSame( 'Used by 2 PDFs', $in_use['freeserif']['pdfs_label'] );
		$this->assertSame( [ 'Required by an add-on' ], $in_use['fixturemono']['reasons'] );
	}

	public function test_the_downloads_off_flag_is_per_family() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->put_freeserif_on_disk();

		add_filter(
			'gfpdf_auto_install_fonts',
			static function ( $download, $file ) {
				return $file !== 'FreeSerifBold.ttf';
			},
			10,
			2
		);

		/* The fixtures stand in for FreeSerif on the shared sources' manifest */
		$this->assertSame(
			[
				'dejavuserifcondensed' => true,
				'freeserif'            => false,
			],
			array_column( $this->core_rows( $this->downloader( GPDFAPI::get_mvc_class( 'Model_Font_Sources' ) ) ), 'auto_install', 'id' )
		);
	}

	public function test_delete_removes_only_the_familys_own_copies_and_keeps_the_licences() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed-Bold.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'DejaVuinfo.txt' );
		$this->put_freeserif_on_disk();

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame( 'available', $result['font']['type'] );
		unset( $result['font'] );

		$this->assertSame(
			[
				'id'      => 'dejavuserifcondensed',
				'removed' => [ 'DejaVuSerifCondensed.ttf' ],
				'bytes'   => filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ),
			],
			$result
		);

		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( 'DejaVuSerifCondensed-Bold.ttf' ), 'Another font\'s file at the name stays' );
		$this->assertFileExists( $this->font_path( 'DejaVuinfo.txt' ) );
		$this->assertFileExists( $this->font_path( 'FreeSerif.ttf' ) );
		$this->assertCount( 1, $this->log_messages( 'Downloaded font deleted' ) );
	}

	public function test_delete_removes_only_the_hashed_copy_and_never_the_file_at_the_name() {
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$hashed  = $sources->get_hashed_name( 'DejaVuSerifCondensed.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf', $hashed );

		$row = $this->core_font_row( 'dejavuserifcondensed' );
		$this->assertSame( $hashed, $row['regular'], 'The hashed copy is the family\'s file' );
		$this->assertSame( filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ), $row['file_sizes']['regular'] );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame( [ 'DejaVuSerifCondensed.ttf' ], $result['removed'] );
		$this->assertSame( filesize( $this->font_fixture( 'DejaVuSerifCondensed.ttf' ) ), $result['bytes'] );
		$this->assertFileDoesNotExist( $this->font_path( $hashed ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_delete_keeps_another_fonts_file_of_a_known_size_at_the_name() {
		$this->put_same_size_font_at( 'DejaVuSerifCondensed.ttf', 'DejaVuSerifCondensed.ttf' );
		$bytes = (string) file_get_contents( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );

		$this->assert_font_error( $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' ), 'font_not_downloaded', 404 );
		$this->assertStringEqualsFile( $this->font_path( 'DejaVuSerifCondensed.ttf' ), $bytes );
		$this->assertCount( 1, $this->log_messages( 'Kept another font\'s file at a font file\'s name' ) );
	}

	public function test_a_family_sharing_a_name_with_a_record_lists_and_deletes_only_its_hashed_copy() {
		$sources = GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
		$hashed  = $sources->get_hashed_name( 'DejaVuSerifCondensed.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->add_record( 'mine', [ 'regular' => 'DejaVuSerifCondensed.ttf' ] );

		$this->assertSame( 'available', $this->core_font_row( 'dejavuserifcondensed' )['type'], 'The record\'s file is not the family\'s' );

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf', $hashed );

		$this->assertSame( $hashed, $this->core_font_row( 'dejavuserifcondensed' )['regular'] );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assertSame( [ 'DejaVuSerifCondensed.ttf' ], $result['removed'] );
		$this->assertFileDoesNotExist( $this->font_path( $hashed ) );
		$this->assertFileEquals( $this->font_fixture( 'Chewy.ttf' ), $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	public function test_deleting_a_record_leaves_the_hashed_copy() {
		$hashed = GPDFAPI::get_mvc_class( 'Model_Font_Sources' )->get_hashed_name( 'DejaVuSerifCondensed.ttf' );

		$this->copy_fixture( 'Chewy.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf', $hashed );
		$this->add_record( 'mine', [ 'regular' => 'DejaVuSerifCondensed.ttf' ] );

		$this->assertTrue( GPDFAPI::delete_pdf_font( 'mine' ) );
		$this->assertFileDoesNotExist( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
		$this->assertFileExists( $this->font_path( $hashed ) );
	}

	public function test_delete_clears_the_familys_failures_and_metrics() {
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->set_font_failures(
			[
				'DejaVuSerifCondensed-Bold.ttf' => $this->failure( time() ),
				'FreeSerif.ttf'                 => $this->failure(),
			]
		);
		$this->set_back_off( 'DejaVuSerifCondensed-Italic.ttf' );

		$metrics = GPDFAPI::get_misc_class()->get_mpdf_cache_location() . '/ttfontdata/';
		wp_mkdir_p( $metrics );
		file_put_contents( $metrics . 'dejavuserifcondensedB.mtx.json', '{}' );
		file_put_contents( $metrics . 'freeserif.mtx.json', '{}' );

		try {
			$this->assertIsArray( $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' ) );

			$this->assertSame( [ 'FreeSerif.ttf' ], array_keys( $this->get_font_failures() ) );
			$this->assertFalse( $this->get_back_off( 'DejaVuSerifCondensed-Italic.ttf' ) );
			$this->assertFileDoesNotExist( $metrics . 'dejavuserifcondensedB.mtx.json' );
			$this->assertFileExists( $metrics . 'freeserif.mtx.json' );
		} finally {
			wp_delete_file( $metrics . 'freeserif.mtx.json' );
		}
	}

	public function test_an_in_use_family_is_not_deleted() {
		GPDFAPI::get_options_class()->update_option( 'default_font', 'dejavuserifcondensed' );
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$result = $this->downloader()->delete_downloaded_font( 'dejavuserifcondensed' );

		$this->assert_font_error( $result, 'font_in_use', 409 );
		$this->assertFileExists( $this->font_path( 'DejaVuSerifCondensed.ttf' ) );
	}

	/**
	 * @dataProvider provider_not_downloaded
	 */
	public function test_a_family_that_is_not_downloaded_is_not_found( string $key ) {
		$this->copy_fixture( 'Chewy.ttf', 'gfpdf-Arimo-Regular.ttf' );

		$this->assert_font_error( $this->downloader()->delete_downloaded_font( $key ), 'font_not_downloaded', 404 );
	}

	public function provider_not_downloaded(): array {
		return [
			'bundled'     => [ 'arimo' ],
			'unknown'     => [ 'notafont' ],
			'not on disk' => [ 'freeserif' ],
			'traversal'   => [ '../freeserif' ],
		];
	}

	public function test_another_sites_default_font_and_pdfs_are_in_use() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->become_super_admin();
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$this->put_freeserif_on_disk();

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		update_option( 'gfpdf_settings', [ 'default_font' => 'DejaVuSerifCondensed' ] );
		restore_current_blog();

		/* A site without the settings or Gravity Forms tables */
		$empty_site = self::factory()->blog->create();

		$downloader = $this->downloader();
		array_map( [ GPDFAPI::get_mvc_class( 'Model_Required_Fonts' ), 'write_site_fonts' ], [ $blog_id, $empty_site ] );

		$usage = $downloader->get_font_usage();

		$this->assertSame( [ 'Used on 1 other site' ], $usage['dejavuserifcondensed']['reasons'] );
		$this->assertArrayNotHasKey( 'freeserif', $usage );

		$this->assert_font_error( $downloader->delete_downloaded_font( 'dejavuserifcondensed' ), 'font_in_use', 409 );
	}

	public function test_another_sites_locale_font_is_in_use() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->become_super_admin();
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		/* WordPress only keeps a locale it has installed, but this site has no pack for it */
		$installed = static function () {
			return [ 'th' ];
		};
		add_filter( 'get_available_languages', $installed );
		update_option( 'WPLANG', 'th' );
		remove_filter( 'get_available_languages', $installed );
		restore_current_blog();

		GPDFAPI::get_mvc_class( 'Model_Required_Fonts' )->write_site_fonts( $blog_id );

		$this->assertSame( [ 'Used on 1 other site' ], $this->downloader()->get_font_usage()['garuda']['reasons'] );
	}

	public function test_a_subsite_admin_skips_other_sites() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );

		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		update_option( 'gfpdf_settings', [ 'default_font' => 'DejaVuSerifCondensed' ] );
		restore_current_blog();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertArrayNotHasKey( 'dejavuserifcondensed', $this->downloader()->get_font_usage() );
	}

	private function become_super_admin(): void {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );
	}

	/**
	 * @param mixed $result
	 */
	private function assert_font_error( $result, string $code, int $status ): void {
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( [ 'status' => $status ], $result->get_error_data() );
	}

	private function core_font_row( string $key ): array {
		return array_column( $this->downloader()->get_installed_fonts(), null, 'id' )[ $key ];
	}

	private function downloader( ?Model_Font_Sources $sources = null ): Seamed_Font_Downloader {
		$this->log = new TestHandler();

		return new Seamed_Font_Downloader( new Logger( 'test', [ $this->log ] ), [ 'sources' => $sources ?? $this->stub_font_sources() ] );
	}

	private function add_form_with_pdfs( array $pdfs ): int {
		return (int) $this->gf_factory()->form->create(
			[],
			[
				'title'               => 'Fonts',
				'fields'              => [],
				'gfpdf_form_settings' => $pdfs,
			]
		);
	}

	/**
	 * @return array[]
	 */
	private function log_messages( string $message ): array {
		return array_values(
			array_filter(
				$this->log->getRecords(),
				static function ( $record ) use ( $message ) {
					return $record['message'] === $message;
				}
			)
		);
	}

	/**
	 * @return array[] The list's downloaded fonts
	 */
	protected function core_rows( Seamed_Font_Downloader $downloader ): array {
		return array_values( wp_list_filter( $downloader->get_installed_fonts(), [ 'type' => 'core' ] ) );
	}

	public function test_a_site_not_yet_recorded_locks_core_fonts_until_it_is() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->become_super_admin();
		$this->copy_fixture( 'DejaVuSerifCondensed.ttf' );
		$blog_id = self::factory()->blog->create();
		delete_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . $blog_id );

		$downloader = $this->downloader();

		$this->assertSame( [ 'Still checking the other sites' ], $downloader->get_font_usage()['dejavuserifcondensed']['reasons'] );
		$this->assert_font_error( $downloader->delete_downloaded_font( 'dejavuserifcondensed' ), 'font_in_use', 409 );

		GPDFAPI::get_mvc_class( 'Model_Required_Fonts' )->write_site_fonts( $blog_id );

		$this->assertArrayNotHasKey( 'dejavuserifcondensed', $downloader->get_font_usage() );
	}

	public function test_an_unrecorded_site_does_not_lock_a_custom_font() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->become_super_admin();
		delete_network_option( get_main_network_id(), Model_Required_Fonts::SITE_FONTS_PREFIX . self::factory()->blog->create() );
		GPDFAPI::get_options_class()->update_option( 'custom_fonts', [ 'brand' => [ 'id' => 'brand', 'font_name' => 'Brand', 'regular' => 'brand.ttf' ] ] );

		$this->assertArrayNotHasKey( 'brand', $this->downloader()->get_font_usage() );
	}
}
