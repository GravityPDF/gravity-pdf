<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;

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

	public function set_up(): void {
		parent::set_up();

		$this->drop_catalog_rows();

		wp_set_current_user( self::$admin_id );
	}

	public function tear_down(): void {
		$this->remove_font_rows();
		$this->remove_font_files();
		$this->drop_catalog_rows();

		parent::tear_down();
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
