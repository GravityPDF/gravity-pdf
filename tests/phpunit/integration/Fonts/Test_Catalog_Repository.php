<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Catalog_Repository
 *
 * @package   GFPDF\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Catalog_Repository extends TestCase {

	use HasCatalogRows;

	/**
	 * @var Catalog_Repository
	 */
	public $catalog;

	public function set_up(): void {
		parent::set_up();

		$this->catalog = $this->catalog_repository();
		$this->drop_catalog_rows();
	}

	public function tear_down(): void {
		$this->drop_catalog_rows();

		parent::tear_down();
	}

	/**
	 * Three packs in a deliberately non-alphabetical index order
	 */
	protected function seed_packs(): void {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'label' => 'Emoji', 'position' => 0, 'coverage' => 1, 'always' => 1, 'scripts' => 'und-Zsye', 'size' => 900000, 'font_keys' => 'notoemoji' ] );
		$this->insert_catalog_row( 'packs', 'dejavu', [ 'label' => 'Extended Latin', 'position' => 1, 'coverage' => 1, 'languages' => 'en,fr', 'size' => 10100000, 'font_keys' => 'dejavusans,dejavuserif' ] );
		$this->insert_catalog_row( 'packs', 'barcode', [ 'label' => 'Barcode', 'position' => 2, 'coverage' => 1, 'size' => 20000 ] );
	}

	public function test_search_returns_entries_in_index_order_not_alphabetical() {
		$this->seed_packs();

		$result = $this->catalog->search( 'packs' );

		$this->assertSame( 3, $result['total'] );
		$this->assertSame( 1, $result['pages'] );
		$this->assertSame( [ 'emoji', 'dejavu', 'barcode' ], wp_list_pluck( $result['entries'], 'entry' ) );
	}

	public function test_search_pages() {
		$this->seed_packs();

		$first = $this->catalog->search( 'packs', [ 'per_page' => 2, 'page' => 1 ] );
		$last  = $this->catalog->search( 'packs', [ 'per_page' => 2, 'page' => 2 ] );

		$this->assertSame( 3, $first['total'] );
		$this->assertSame( 2, $first['pages'] );
		$this->assertCount( 2, $first['entries'] );
		$this->assertSame( [ 'barcode' ], wp_list_pluck( $last['entries'], 'entry' ) );
	}

	public function test_search_matches_label_and_entry_id() {
		$this->seed_packs();

		$this->assertSame( [ 'dejavu' ], wp_list_pluck( $this->catalog->search( 'packs', [ 's' => 'Latin' ] )['entries'], 'entry' ) );
		$this->assertSame( [ 'barcode' ], wp_list_pluck( $this->catalog->search( 'packs', [ 's' => 'barcod' ] )['entries'], 'entry' ) );
		$this->assertSame( [], $this->catalog->search( 'packs', [ 's' => 'nothing' ] )['entries'] );
	}

	public function test_search_filters_a_subset_out_of_the_csv_column() {
		$this->insert_catalog_row( 'google', 'lato', [ 'subsets' => 'latin,latin-ext', 'category' => 'sans-serif' ] );
		$this->insert_catalog_row( 'google', 'amiri', [ 'subsets' => 'arabic,latin', 'category' => 'serif' ] );

		/* FIND_IN_SET, so `latin` must not match `latin-ext` */
		$this->assertCount( 2, $this->catalog->search( 'google', [ 'subset' => 'latin' ] )['entries'] );
		$this->assertSame( [ 'lato' ], wp_list_pluck( $this->catalog->search( 'google', [ 'subset' => 'latin-ext' ] )['entries'], 'entry' ) );
		$this->assertSame( [ 'amiri' ], wp_list_pluck( $this->catalog->search( 'google', [ 'category' => 'serif' ] )['entries'], 'entry' ) );
	}

	public function test_search_is_scoped_to_one_source() {
		$this->seed_packs();
		$this->insert_catalog_row( 'google', 'lato' );

		$this->assertSame( 3, $this->catalog->search( 'packs' )['total'] );
		$this->assertSame( 1, $this->catalog->search( 'google' )['total'] );
	}

	public function test_a_list_read_never_selects_entry_json() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1, 'entry_json' => '{"fonts":{}}' ] );

		/* A LONGTEXT per row would otherwise be dragged through PHP and into the object cache for a browse */
		$this->assertArrayNotHasKey( 'entry_json', $this->catalog->search( 'packs' )['entries'][0] );
		$this->assertArrayNotHasKey( 'entry_json', $this->catalog->coverage_entries()[0] );
	}

	public function test_entry_decodes_an_inlined_entry() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'entry_json' => '{"fonts":{"notoemoji":{"R":"NotoEmoji-Regular.ttf"}}}' ] );

		$row = $this->catalog->entry( 'packs', 'emoji' );

		$this->assertSame( 'emoji', $row['entry'] );
		$this->assertSame( [ 'notoemoji' => [ 'R' => 'NotoEmoji-Regular.ttf' ] ], $row['data']['fonts'] );
		$this->assertArrayNotHasKey( 'entry_json', $row );
	}

	public function test_entry_leaves_a_pointed_at_entry_for_the_installer() {
		$this->insert_catalog_row( 'google', 'lato', [ 'entry_sha256' => str_repeat( 'b', 64 ) ] );

		$row = $this->catalog->entry( 'google', 'lato' );

		$this->assertNull( $row['data'] );
		$this->assertSame( str_repeat( 'b', 64 ), $row['entry_sha256'] );
	}

	public function test_entry_is_null_when_the_row_is_missing() {
		$this->assertNull( $this->catalog->entry( 'packs', 'nope' ) );
	}

	public function test_coverage_entries_crosses_sources_and_excludes_display_families() {
		$this->seed_packs();
		$this->insert_catalog_row( 'google', 'lato', [ 'coverage' => 0 ] );
		$this->insert_catalog_row( 'google', 'noto-thing', [ 'coverage' => 1, 'position' => 9 ] );

		$entries = $this->catalog->coverage_entries();

		$this->assertSame( [ 'emoji', 'dejavu', 'barcode', 'noto-thing' ], wp_list_pluck( $entries, 'entry' ) );
	}

	public function test_summary_counts_the_rows_and_builds_the_filter_vocabulary() {
		$this->insert_catalog_row( 'google', 'lato', [ 'subsets' => 'latin,latin-ext', 'category' => 'sans-serif' ] );
		$this->insert_catalog_row( 'google', 'amiri', [ 'subsets' => 'arabic,latin', 'category' => 'serif' ] );
		$this->insert_catalog_row( 'google', 'noto', [ 'subsets' => 'latin', 'category' => 'sans-serif', 'coverage' => 1 ] );

		$summary = $this->catalog->summary( 'google' );

		$this->assertSame( 3, $summary['total'] );
		$this->assertSame( 1, $summary['coverage'] );
		$this->assertSame( [ 'sans-serif' => 2, 'serif' => 1 ], $summary['filters']['category'] );
		$this->assertSame( 3, $summary['filters']['subsets']['latin'] );
		$this->assertSame( 1, $summary['filters']['subsets']['latin-ext'] );
		$this->assertSame( 1, $summary['filters']['subsets']['arabic'] );
	}

	public function test_summary_of_an_empty_source_is_zeroed() {
		$summary = $this->catalog->summary( 'packs' );

		$this->assertSame( 0, $summary['total'] );
		$this->assertSame( 0, $summary['coverage'] );
		$this->assertSame( [], $summary['filters']['category'] );
	}

	public function test_set_status_writes_only_the_status_columns() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'label' => 'Emoji', 'version' => 'fonts-v1.0.0' ] );

		$this->assertTrue(
			$this->catalog->set_status( 'packs', 'emoji', [ 'phase' => 'queued', 'label' => 'Hijacked', 'source' => 'evil' ] )
		);

		$row = $this->catalog->entry( 'packs', 'emoji' );

		$this->assertSame( 'queued', $row['phase'] );
		$this->assertSame( 'Emoji', $row['label'] );
		$this->assertSame( 'packs', $row['source'] );
	}

	public function test_set_status_with_nothing_writable_does_not_query() {
		$this->insert_catalog_row( 'packs', 'emoji' );

		$this->assertFalse( $this->catalog->set_status( 'packs', 'emoji', [ 'label' => 'Hijacked' ] ) );
	}

	public function test_a_status_write_invalidates_cached_reads() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );

		$this->assertNull( $this->catalog->coverage_entries()[0]['phase'] );

		$this->catalog->set_status( 'packs', 'emoji', [ 'phase' => 'installing' ] );

		$this->assertSame( 'installing', $this->catalog->coverage_entries()[0]['phase'] );
	}

	public function test_the_render_paths_write_can_skip_the_cache_bump() {
		$this->insert_catalog_row( 'packs', 'japanese', [ 'coverage' => 1 ] );

		$stamp = $this->catalog->get_last_changed();

		/* Anonymous renders record a miss; letting them churn the object cache is what the flag prevents */
		$this->catalog->set_status( 'packs', 'japanese', [ 'missing_scripts' => 'und-Jpan' ], false );

		$this->assertSame( $stamp, $this->catalog->get_last_changed() );
	}

	public function test_the_catalog_stamp_is_separate_from_the_font_row_stamp() {
		global $gfpdf;

		$fonts = $gfpdf->get_font_repository()->get_last_changed();

		$this->catalog->flush();

		/* A sync must not evict the render path's font rows */
		$this->assertSame( $fonts, $gfpdf->get_font_repository()->get_last_changed() );
	}

	public function test_url_for_builds_from_the_registered_root_not_the_index() {
		$url = $this->catalog->url_for( 'packs', [ 'remote_path' => 'fonts-v1.0.0/NotoEmoji-Regular.ttf' ] );

		$this->assertSame( trailingslashit( GPDF_FONTS_URL ) . 'files/fonts-v1.0.0/NotoEmoji-Regular.ttf', $url );
	}

	public function test_url_for_an_unregistered_source_is_null() {
		$this->assertNull( $this->catalog->url_for( 'nope', [ 'remote_path' => 'x.ttf' ] ) );
	}

	public function test_preview_urls_name_one_face_per_key_without_an_entry() {
		$this->insert_catalog_row( 'packs', 'dejavu', [ 'preview' => 'prev-abc', 'font_keys' => 'dejavusans,dejavuserif' ] );

		$urls = $this->catalog->preview_urls( $this->catalog->search( 'packs' )['entries'][0] );

		$root = trailingslashit( GPDF_FONTS_URL );

		$this->assertSame( $root . 'files/prev-abc-dejavusans-R.woff2', $urls['dejavusans']['R'] );
		$this->assertSame( $root . 'files/prev-abc-dejavuserif-R.woff2', $urls['dejavuserif']['R'] );
	}

	public function test_preview_urls_take_their_roles_from_a_decoded_entry() {
		$this->insert_catalog_row( 'packs', 'dejavu', [ 'preview' => 'prev-abc', 'font_keys' => 'dejavusans' ] );

		$row = $this->catalog->search( 'packs' )['entries'][0];

		$urls = $this->catalog->preview_urls(
			$row,
			[
				'dejavusans' => [
					'R'        => 'a.ttf',
					'B'        => 'b.ttf',
					'useOTL'   => 255,
					'sip-ext'  => 'sun-extb',
					'dict_T'   => 'linebrdictT.dat',
					'LICENSE'  => [ 'LICENSE.txt', 'GPL-2.0.txt' ],
				],
			]
		);

		/* Only the four faces render: a flag, a line-break dictionary and a licence text have no preview */
		$this->assertSame( [ 'R', 'B' ], array_keys( $urls['dejavusans'] ) );
	}

	public function test_a_row_with_no_preview_has_no_preview_urls() {
		$this->insert_catalog_row( 'packs', 'barcode', [ 'font_keys' => 'ocrb' ] );

		$this->assertSame( [], $this->catalog->preview_urls( $this->catalog->search( 'packs' )['entries'][0] ) );
	}

	public function test_integer_columns_come_back_as_integers() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1, 'always' => 1, 'size' => 900000, 'files' => 2, 'position' => 3 ] );

		$row = $this->catalog->search( 'packs' )['entries'][0];

		foreach ( [ 'coverage', 'always', 'size', 'files', 'position' ] as $column ) {
			$this->assertIsInt( $row[ $column ], "{$column} should be an int" );
		}
	}
}
