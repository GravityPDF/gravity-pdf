<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group helper-misc
 */
class Test_Helper_Misc_Config extends TestCase {

	public Helper_Misc $misc;

	public function set_up(): void {
		global $gfpdf;
		parent::set_up();
		$this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->gform, $gfpdf->data );
	}

	/**
	 * @dataProvider provider_update_deprecated_config
	 */
	public function test_update_deprecated_config( $expected, $value ) {
		$this->assertSame( $expected, $this->misc->update_deprecated_config( $value ) );
	}

	public function provider_update_deprecated_config(): array {
		return [
			'true → Yes'                  => [ 'Yes', true ],
			'false → No'                  => [ 'No', false ],
			'null passthrough'            => [ null, null ],
			'arbitrary string passthrough' => [ 'Other', 'Other' ],
			'array passthrough'           => [ [ 1, 2, 3 ], [ 1, 2, 3 ] ],
			'string "true" passthrough'   => [ 'true', 'true' ],
			'string "false" passthrough'  => [ 'false', 'false' ],
		];
	}

	/**
	 * @dataProvider provider_get_config_class_name
	 */
	public function test_get_config_class_name( $expected, $file ) {
		global $gfpdf;

		$this->assertSame( $expected, $gfpdf->templates->get_config_class_name( $file ) );
	}

	public function provider_get_config_class_name(): array {
		return [
			'hyphenated filename'    => [ 'Manage_Document', '/path/to/templates/manage-document.php' ],
			'underscored filename'   => [ 'Manage_Document', '/path/to/templates/manage_document.php' ],
			'space-separated filename' => [ 'Manage_Document', '/path/to/templates/manage document.php' ],
			'multi-word filename'    => [ 'Superawesome_Working_Directory', '/my/path/superawesome-working-directory.php' ],
			'single-word filename'   => [ 'Template', 'template.php' ],
		];
	}

	public function test_backwards_compat_conversion() {
		$settings = [
			'irrelevant' => 'Yes',
		];

		$compat = $this->misc->backwards_compat_conversion( $settings, [], [] );

		$this->assertCount( 8, $compat );
		$this->assertArrayNotHasKey( 'irrelevant', $compat );
		$this->assertFalse( $compat['premium'] );
		$this->assertFalse( $compat['rtl'] );
		$this->assertFalse( $compat['security'] );
		$this->assertFalse( $compat['pdfa1b'] );
		$this->assertFalse( $compat['pdfx1a'] );
		$this->assertSame( '', $compat['pdf_password'] );
		$this->assertSame( '', $compat['pdf_privileges'] );
		$this->assertSame( 96, $compat['dpi'] );

		$settings = [
			'advanced_template' => 'Yes',
			'rtl'               => 'Yes',
			'image_dpi'         => 300,
			'security'          => 'Yes',
			'password'          => 'password',
			'privileges'        => 'privileges',
			'format'            => 'PDFX1A',
		];

		$compat = $this->misc->backwards_compat_conversion( $settings, [], [] );

		$this->assertTrue( $compat['premium'] );
		$this->assertTrue( $compat['rtl'] );
		$this->assertTrue( $compat['security'] );
		$this->assertFalse( $compat['pdfa1b'] );
		$this->assertTrue( $compat['pdfx1a'] );
		$this->assertSame( 'password', $compat['pdf_password'] );
		$this->assertSame( 'privileges', $compat['pdf_privileges'] );
		$this->assertSame( 300, $compat['dpi'] );
	}

	public function test_backwards_compat_output() {
		$this->assertSame( 'save', $this->misc->backwards_compat_output() );
		$this->assertSame( 'view', $this->misc->backwards_compat_output( 'display' ) );
		$this->assertSame( 'download', $this->misc->backwards_compat_output( 'download' ) );
	}

	public function test_is_secondary_network_site_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Not running single site tests' );
		}

		$this->assertFalse( $this->misc->is_secondary_network_site( 'gravity-pdf/pdf.php' ) );
	}

	/**
	 * @since 6.16.0
	 */
	public function test_is_secondary_network_site_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not running multisite tests' );
		}

		$plugin          = 'gravity-pdf/pdf.php';
		$network_plugins = [ $plugin => time() ];

		/* Fake the network-activated plugin list without touching the shared option */
		$filter = static function () use ( &$network_plugins ) {
			return $network_plugins;
		};
		add_filter( 'pre_site_option_active_sitewide_plugins', $filter );

		/* The primary site is never treated as secondary, even when the plugin is network activated */
		$this->assertFalse( $this->misc->is_secondary_network_site( $plugin ) );

		/* Pose as a secondary site — we only read a network option, so no real blog is needed */
		switch_to_blog( PHP_INT_MAX );

		/* Secondary site, plugin not network activated */
		$network_plugins = [];
		$this->assertFalse( $this->misc->is_secondary_network_site( $plugin ) );

		/* Secondary site, plugin network activated */
		$network_plugins = [ $plugin => time() ];
		$this->assertTrue( $this->misc->is_secondary_network_site( $plugin ) );

		restore_current_blog();
		remove_filter( 'pre_site_option_active_sitewide_plugins', $filter );
	}
}
