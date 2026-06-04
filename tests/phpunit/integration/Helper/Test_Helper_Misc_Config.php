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
}
