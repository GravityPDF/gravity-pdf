<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GFPDF\Tests\Concerns\QueuesFontInstalls;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * What `GET /fonts/` tells the Font Manager and the settings dropdown
 *
 * @group api
 * @group rest
 * @group fonts
 */
class Test_Rest_Font_List extends Test_Rest {

	use HasCatalogRows;
	use HasFontRows;
	use QueuesFontInstalls;

	public function set_up(): void {
		parent::set_up();

		$this->drop_catalog_rows();
		$this->block_dispatch();

		wp_set_current_user( self::$admin_id );
	}

	public function tear_down(): void {
		$this->reset_queue();
		$this->remove_font_rows();
		$this->remove_font_files();
		$this->drop_catalog_rows();

		parent::tear_down();
	}

	/**
	 * A row whose `R` face is a font mPDF can actually parse
	 *
	 * `POST /fonts/{id}` re-reads every face to decide `useOTL`, so a write needs a real file behind the row where
	 * a read is happy with the three bytes `install_font_row()` writes.
	 */
	protected function install_real_font( string $font_key, array $overrides = [] ): int {
		$this->drop_font_fixture( 'Chewy.ttf' );

		return $this->font_repository()->insert(
			array_merge(
				[
					'font_key' => $font_key,
					'label'    => ucfirst( $font_key ),
					'source'   => 'custom',
					'files'    => [
						'R' => [
							'path' => 'Chewy.ttf',
							'size' => filesize( $this->font_dir() . 'Chewy.ttf' ),
						],
					],
				],
				$overrides
			)
		);
	}

	/**
	 * @return array The response body as an array
	 */
	protected function fonts(): array {
		$response = $this->get( '/fonts' );

		$this->assertSame( 200, $response->get_status() );

		return (array) $response->get_data();
	}

	public function test_an_anonymous_request_is_refused() {
		wp_set_current_user( 0 );

		$this->assertSame( 401, $this->get( '/fonts' )->get_status() );
	}

	public function test_the_listing_carries_the_three_groups_and_the_two_route_fields() {
		$this->assertSame(
			[ 'bundled', 'can_delete_files', 'custom', 'groups', 'missing' ],
			$this->sorted_keys( $this->fonts() )
		);
	}

	public function test_a_pack_and_an_upload_land_in_different_halves_of_the_listing() {
		$this->insert_catalog_row( 'packs', 'thai', [ 'coverage' => 1, 'label' => 'Thai', 'files' => 2 ] );

		$this->install_entry_row( 'notosansthai', 'thai' );
		$this->install_font_row( 'brandsans' );

		$fonts = $this->fonts();

		$this->assertSame( 'gfpdf-arimo', $fonts['bundled'][0]['id'] );
		$this->assertSame( [ 'brandsans' ], array_column( $fonts['custom'], 'id' ) );

		$group = $fonts['groups'][0];

		$this->assertSame( 'Thai', $group['label'] );
		$this->assertSame( 'packs', $group['source'] );
		$this->assertSame( 'thai', $group['entry'] );

		/* The sidebar's "N of M files" line: M is the catalogue's count, and the status object carries N */
		$this->assertSame( 2, $group['files'] );
		$this->assertSame( [ 'notosansthai' ], array_column( $group['fonts'], 'id' ) );
	}

	public function test_the_listing_never_carries_install_progress() {
		$this->insert_catalog_row( 'packs', 'thai', [ 'coverage' => 1, 'phase' => 'installing' ] );
		$this->install_entry_row( 'notosansthai', 'thai' );

		/* Progress is the polled route's; a font list that changed every two seconds would be cached by nothing */
		$this->assertSame(
			[ 'entry', 'files', 'fonts', 'label', 'scripts', 'source' ],
			$this->sorted_keys( $this->fonts()['groups'][0] )
		);
	}

	public function test_every_font_carries_what_a_preview_and_a_dropdown_need() {
		$this->install_font_row( 'brandsans' );

		$font = $this->fonts()['custom'][0];

		$this->assertSame(
			[ 'coverage', 'enabled', 'entry', 'files', 'id', 'label', 'source', 'version' ],
			$this->sorted_keys( $font )
		);

		$this->assertSame(
			[ 'missing', 'path', 'role', 'size', 'url', 'variant' ],
			$this->sorted_keys( $font['files']['R'] )
		);

		$this->assertStringEndsWith( '/fonts/test-brandsans.ttf', (string) $font['files']['R']['url'] );
	}

	public function test_an_always_entry_with_nothing_installed_is_offered_to_the_bundled_panel() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1, 'always' => 1, 'label' => 'Emoji', 'size' => 943718 ] );

		$this->assertSame(
			[
				[
					'source' => 'packs',
					'entry'  => 'emoji',
					'label'  => 'Emoji',
					'size'   => 943718,
				],
			],
			$this->fonts()['missing']
		);
	}

	public function test_an_always_entry_that_is_installed_is_not_reported_missing() {
		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1, 'always' => 1 ] );
		$this->install_entry_row( 'notoemoji', 'emoji' );

		$this->assertSame( [], $this->fonts()['missing'] );
	}

	public function test_a_single_site_admin_may_unlink_a_font_file() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only' );
		}

		$this->assertTrue( $this->fonts()['can_delete_files'] );
	}

	public function test_a_tenant_administrator_may_not_unlink_a_shared_font_file() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		revoke_super_admin( self::$admin_id );

		/* One copy of a pack serves every site, so the panel must not offer a delete this admin cannot make (§4.11) */
		$this->assertFalse( $this->fonts()['can_delete_files'] );
	}

	public function test_a_font_this_site_has_hidden_is_listed_as_disabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		$id = $this->install_font_row( 'brandsans' );

		$this->font_repository()->set_site_enabled( $id, get_current_blog_id(), false );

		/* Still installed, and still in the list: the toggle is visibility, not installation */
		$this->assertFalse( $this->fonts()['custom'][0]['enabled'] );
	}

	public function test_an_editor_without_the_capability_is_refused() {
		wp_set_current_user( self::$editor_id );

		$this->assertSame( 403, $this->get( '/fonts' )->get_status() );
	}

	public function test_a_write_answers_with_the_row_the_listing_carries() {
		$this->install_real_font( 'brandsans' );

		$row = $this->post( '/fonts/brandsans', [ 'label' => 'House Sans' ] )->get_data();

		/* The store merges this straight into the list it already holds, so it has to be the same shape */
		$this->assertSame(
			[ 'coverage', 'enabled', 'entry', 'files', 'id', 'label', 'source', 'version' ],
			$this->sorted_keys( (array) $row )
		);

		$this->assertSame( 'brandsans', $row['id'] );
		$this->assertSame( 'House Sans', $row['label'] );
	}

	public function test_a_pack_font_is_sent_back_to_the_entry_route() {
		$this->insert_catalog_row( 'packs', 'japanese', [ 'coverage' => 1 ] );
		$this->install_entry_row( 'notosansjp', 'japanese' );

		foreach ( [ 'POST', 'DELETE' ] as $method ) {
			$response = $this->rest( $method, '/fonts/notosansjp', [ 'label' => 'Mine' ] );

			/* The pack decides which keys exist; taking one away would leave it installed and unable to render */
			$this->assertSame( 400, $response->get_status(), $method );
			$this->assertSame( 'font_owned_by_entry', $response->get_data()['code'], $method );
		}

		$this->assertNotNull( $this->font_repository()->get( 'notosansjp' ) );
	}

	public function test_a_display_family_install_is_this_routes_to_rename() {
		$this->insert_catalog_row( 'google', 'lato' );
		$this->install_real_font( 'latolight', [ 'source' => 'google', 'entry' => 'lato' ] );

		$row = $this->post( '/fonts/latolight', [ 'label' => 'Lato Text' ] )->get_data();

		/* The key stays, because templates reference it; only the name the admin sees changes */
		$this->assertSame( 'latolight', $row['id'] );
		$this->assertSame( 'Lato Text', $row['label'] );
	}

	public function test_hiding_a_font_is_refused_where_there_is_nowhere_to_hide_it() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only' );
		}

		$this->install_real_font( 'brandsans' );

		$response = $this->post( '/fonts/brandsans', [ 'enabled' => false ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'font_visibility_unsupported', $response->get_data()['code'] );
	}

	public function test_hiding_a_font_leaves_it_installed_for_every_other_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite tests only' );
		}

		$this->install_real_font( 'brandsans' );

		$row = $this->post( '/fonts/brandsans', [ 'enabled' => false ] )->get_data();

		$this->assertFalse( $row['enabled'] );

		/* Visibility, not installation: the row and its files are untouched */
		$this->assertNotNull( $this->font_repository()->get( 'brandsans' ) );

		$this->assertTrue( $this->post( '/fonts/brandsans', [ 'enabled' => true ] )->get_data()['enabled'] );
	}

	/**
	 * A display family whose entry document names the files each style maps to
	 */
	protected function seed_family(): void {
		$this->insert_catalog_row(
			'google',
			'lato',
			[
				'label'      => 'Lato',
				'styles'     => '300,400,700',
				'entry_json' => (string) wp_json_encode(
					[
						'fonts'    => [ 'lato' => [ 'R' => 'Lato-400.ttf' ] ],
						'variants' => [
							'300' => 'Lato-300.ttf',
							'400' => 'Lato-400.ttf',
							'700' => 'Lato-700.ttf',
						],
						'files'    => [
							'Lato-300.ttf' => [ 'sha256' => str_repeat( 'c', 64 ), 'size' => 10, 'remote_path' => 'v/Lato-300.ttf' ],
							'Lato-400.ttf' => [ 'sha256' => str_repeat( 'a', 64 ), 'size' => 10, 'remote_path' => 'v/Lato-400.ttf' ],
							'Lato-700.ttf' => [ 'sha256' => str_repeat( 'b', 64 ), 'size' => 10, 'remote_path' => 'v/Lato-700.ttf' ],
						],
					]
				),
			]
		);
	}

	public function test_choosing_styles_re_installs_that_row_alone() {
		$this->seed_family();

		$this->install_real_font( 'latolight', [ 'source' => 'google', 'entry' => 'lato', 'label' => 'Lato Light' ] );
		$this->install_real_font( 'latobold', [ 'source' => 'google', 'entry' => 'lato', 'label' => 'Lato Bold' ] );

		$response = $this->post( '/fonts/latolight', [ 'variants' => [ 'R' => '300', 'B' => '700' ] ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'latolight', $response->get_data()['id'] );

		/* `label` is the identity the installer matches on, so the sibling install is untouched */
		$installs = $this->queued_installs();

		$this->assertSame( [ 'Lato Light' ], array_unique( array_column( $installs, 'label' ) ) );
		$this->assertSame( [ 'R' => '300', 'B' => '700' ], $installs[0]['variants'] );
	}

	public function test_a_style_the_entry_does_not_publish_is_refused() {
		$this->seed_family();
		$this->install_real_font( 'latolight', [ 'source' => 'google', 'entry' => 'lato' ] );

		$response = $this->post( '/fonts/latolight', [ 'variants' => [ 'R' => '900' ] ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'font_variant_unknown', $response->get_data()['code'] );
		$this->assertSame( [], $this->queued_installs() );
	}

	public function test_an_uploaded_font_has_no_styles_to_choose_from() {
		$this->install_real_font( 'brandsans' );

		$response = $this->post( '/fonts/brandsans', [ 'variants' => [ 'R' => '300' ] ] );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'font_has_no_styles', $response->get_data()['code'] );
	}

	/**
	 * The installs the request handed to the queue, one per queued file
	 *
	 * @return array[]
	 */
	protected function queued_installs(): array {
		return array_column( $this->queued(), 'install' );
	}

	/**
	 * @param array $data
	 *
	 * @return string[]
	 */
	protected function sorted_keys( array $data ): array {
		$keys = array_keys( $data );
		sort( $keys );

		return $keys;
	}
}
