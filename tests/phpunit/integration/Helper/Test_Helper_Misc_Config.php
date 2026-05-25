<?php

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group helper-misc
 */
class Test_Helper_Misc_Config extends TestCase {

	/** @var Helper_Misc */
	public $misc;

	public function set_up() {
		global $gfpdf;
		parent::set_up();
		$this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->gform, $gfpdf->data );
	}

	/**
	 * @dataProvider provider_update_deprecated_config
	 */
	public function test_update_deprecated_config( $expected, $value ) {
		$this->assertEquals( $expected, $this->misc->update_deprecated_config( $value ) );
	}

	public function provider_update_deprecated_config() {
		return [
			[ 'Yes', true ],
			[ 'No', false ],
			[ null, null ],
			[ 'Other', 'Other' ],
			[ [ 1, 2, 3 ], [ 1, 2, 3 ] ],
			[ 'true', 'true' ],
			[ 'false', 'false' ],
		];
	}

	/**
	 * @dataProvider provider_get_config_class_name
	 */
	public function test_get_config_class_name( $expected, $file ) {
		global $gfpdf;

		$this->assertEquals( $expected, $gfpdf->templates->get_config_class_name( $file ) );
	}

	public function provider_get_config_class_name() {
		return [
			[ 'Manage_Document', '/path/to/templates/manage-document.php' ],
			[ 'Manage_Document', '/path/to/templates/manage_document.php' ],
			[ 'Manage_Document', '/path/to/templates/manage document.php' ],
			[ 'Superawesome_Working_Directory', '/my/path/superawesome-working-directory.php' ],
			[ 'Template', 'template.php' ],
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
		$this->assertEquals( '', $compat['pdf_password'] );
		$this->assertEquals( '', $compat['pdf_privileges'] );
		$this->assertEquals( 96, $compat['dpi'] );

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
		$this->assertEquals( 'password', $compat['pdf_password'] );
		$this->assertEquals( 'privileges', $compat['pdf_privileges'] );
		$this->assertEquals( 300, $compat['dpi'] );
	}

	public function test_backwards_compat_output() {
		$this->assertEquals( 'save', $this->misc->backwards_compat_output() );
		$this->assertEquals( 'view', $this->misc->backwards_compat_output( 'display' ) );
		$this->assertEquals( 'download', $this->misc->backwards_compat_output( 'download' ) );
	}
}
