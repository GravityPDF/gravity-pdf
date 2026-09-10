<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Tests\Concerns\HasCatalogRows;
use GFPDF\Tests\Concerns\HasFontRows;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Who may remove a font file
 *
 * Font files are network-global — one copy of a pack serves every site — so on multisite removing one is a
 * network administrator's decision rather than a tenant's. The exception is a font only the current site can see.
 *
 * @package   GFPDF\Rest
 *
 * Named `Test_Rest_*` like its siblings for more than tidiness: PHPUnit loads each test file on its own, in
 * directory order, so a name sorting before `Test_Rest.php` cannot find the base class it extends.
 *
 * @group     api
 * @group     fonts
 */
class Test_Rest_Font_Delete_Capability extends Test_Rest {

	use HasCatalogRows;
	use HasFontRows;

	public function set_up(): void {
		parent::set_up();

		$this->font_repository()->ensure_ready();
		$this->drop_catalog_rows();

		wp_set_current_user( self::$admin_id );
	}

	public function tear_down(): void {
		remove_all_filters( 'gfpdf_font_file_delete_capability' );

		$this->remove_font_rows();
		$this->drop_catalog_rows();
		$this->font_repository()->flush();

		parent::tear_down();
	}

	/**
	 * The rule lives on the route base, where the request it is about does
	 */
	protected function capability_for( array $font = [] ): string {
		$method = new \ReflectionMethod( \GFPDF\Rest\Rest_Font_Base::class, 'file_delete_capability' );

		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		return (string) $method->invoke( GPDFAPI::get_mvc_class( 'Rest_Custom_Fonts' ), $font );
	}

	public function test_a_single_site_asks_for_nothing_more_than_editing_forms() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only' );
		}

		$this->assertSame( 'gravityforms_edit_forms', $this->capability_for() );
		$this->assertSame( 'gravityforms_edit_forms', $this->capability_for( [ 'blog_id' => 1 ] ) );
	}

	public function test_a_network_wide_font_is_the_networks_to_remove() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->assertSame( 'manage_network_options', $this->capability_for() );
		$this->assertSame( 'manage_network_options', $this->capability_for( [ 'blog_id' => null ] ) );
	}

	/**
	 * Nobody else's PDFs can reach it, and asking a super admin would make a per-site upload permanent
	 */
	public function test_a_font_only_this_site_can_see_is_this_sites_to_remove() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->assertSame( 'gravityforms_edit_forms', $this->capability_for( [ 'blog_id' => get_current_blog_id() ] ) );
		$this->assertSame( 'manage_network_options', $this->capability_for( [ 'blog_id' => get_current_blog_id() + 1 ] ) );
	}

	public function test_the_capability_can_be_filtered() {
		add_filter(
			'gfpdf_font_file_delete_capability',
			static function (): string {
				return 'manage_options';
			}
		);

		$this->assertSame( 'manage_options', $this->capability_for() );
	}

	public function test_deleting_a_font_a_site_admin_may_not_remove_is_a_403() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		/* A network-shared row: the 6.x import shape, and every pack */
		$this->install_font_row( 'shared', [ 'blog_id' => null ] );

		revoke_super_admin( self::$admin_id );

		$response = $this->delete( '/fonts/shared' );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'font_delete_forbidden', $response->get_data()['code'] );
		$this->assertNotNull( GPDFAPI::get_font_repository()->get( 'shared' ) );
	}

	public function test_deleting_a_font_this_site_owns_is_allowed() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->install_font_row( 'mine', [ 'blog_id' => get_current_blog_id() ] );

		revoke_super_admin( self::$admin_id );

		$this->assertSame( 200, $this->delete( '/fonts/mine' )->get_status() );
		$this->assertNull( GPDFAPI::get_font_repository()->get( 'mine' ) );
	}

	/**
	 * A pack is never one site's, so removing it is always the network's decision
	 */
	public function test_deleting_a_language_pack_is_a_403_for_a_site_admin() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$this->insert_catalog_row( 'packs', 'emoji', [ 'coverage' => 1 ] );

		revoke_super_admin( self::$admin_id );

		$response = $this->delete( '/fonts/sources/packs/emoji' );

		$this->assertSame( 403, $response->get_status() );
	}
}
