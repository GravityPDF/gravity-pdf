<?php

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group helper-misc
 */
class Test_Helper_Misc_Pages extends TestCase {

	/** @var Helper_Misc */
	public $misc;

	public function set_up() {
		global $gfpdf;
		parent::set_up();
		$this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->gform, $gfpdf->data );
	}

	private function minify( $html ) {
		return preg_replace(
			[ '/\n/', '/\t/', '/\>\s+\</' ],
			[ '', '', '><' ],
			$html
		);
	}

	public function test_is_gfpdf_page() {
		$this->assertFalse( $this->misc->is_gfpdf_page() );

		set_current_screen( 'dashboard-user' );
		$this->assertFalse( $this->misc->is_gfpdf_page() );

		$_GET['page'] = 'gfpdf-tools';
		$this->assertTrue( $this->misc->is_gfpdf_page() );

		unset( $_GET['page'] );

		$_GET['subview'] = 'PDF';
		$this->assertTrue( $this->misc->is_gfpdf_page() );
	}

	public function test_is_gfpdf_settings_tab() {
		$this->assertFalse( $this->misc->is_gfpdf_settings_tab( 'general' ) );

		set_current_screen( 'dashboard-user' );
		$_GET['subview'] = 'PDF';

		$this->assertTrue( $this->misc->is_gfpdf_settings_tab( 'general' ) );

		$this->assertFalse( $this->misc->is_gfpdf_settings_tab( 'tools' ) );

		$_GET['tab'] = 'tools';
		$this->assertTrue( $this->misc->is_gfpdf_settings_tab( 'tools' ) );
	}

	/**
	 * @dataProvider provider_test_fix_header_footer
	 */
	public function test_fix_header_footer( $expected, $html ) {
		$test_html     = $this->misc->fix_header_footer( $html );
		$minified_html = $this->minify( $test_html );

		$this->assertEquals( $expected, $minified_html );
	}

	public function provider_test_fix_header_footer() {
		return [
			[
				'<p><img src="my-image.jpg" alt="My Image" class="header-footer-img"/></p>',
				'<img src="my-image.jpg" alt="My Image" />',
			],
			[
				'<div id="header"><img src="my-image.jpg" alt="My Image" class="header-footer-img"/></div>',
				'<div id="header"><img src="my-image.jpg" alt="My Image" /></div>',
			],
			[
				'<p><span>Intro</span><img src="my-image.jpg" alt="My Image" class="header-footer-img"/><span>Outro</span></p>',
				'<span>Intro</span> <img src="my-image.jpg" alt="My Image" /> <span>Outro</span>',
			],
			[
				'<p><b>This is bold</b>. <i>This is italics</i><img src="image.jpg" class="header-footer-img"/></p>',
				'<b>This is bold</b>. <i>This is italics</i> <img src="image.jpg" />',
			],
			[
				'<p><img src="my-image.jpg" alt="My Image" class="header-footer-img"/></p>',
				'<img src="my-image.jpg" alt="My Image">',
			],
			[
				'<p><div class="alternate"><img src="my-image.jpg" alt="My Image" class="alternate header-footer-img"/></div></p>',
				'<img src="my-image.jpg" alt="My Image" class="alternate" />',
			],
			[
				'<p><span>Nothing</span></p>',
				'<span>Nothing</span>',
			],
			[
				'',
				'',
			],
			[
				'<p><a href="#"><img src="my-image.jpg" alt="My Image" class="header-footer-img"/></a></p>',
				'<a href="#"><img src="my-image.jpg" alt="My Image" /></a>',
			],
			[
				'<p><div class="alternate"><a href="#"><img src="my-image.jpg" alt="My Image" class="alternate header-footer-img"/></a></div></p>',
				'<a href="#"><img src="my-image.jpg" alt="My Image" class="alternate" /></a>',
			],
		];
	}

	public function test_fix_header_footer_path() {
		$html = $this->misc->fix_header_footer( '<img src="' . PDF_PLUGIN_URL . 'src/assets/images/cap-paws-sitting.png" alt="My Image" />' );
		$this->assertFalse( strpos( PDF_PLUGIN_URL, $html ) );

		$html          = $this->misc->fix_header_footer( '<img src="http://test.com/image.png" alt="My Image" />' );
		$minified_html = $this->minify( $html );
		$this->assertEquals( '<p><img src="http://test.com/image.png" alt="My Image" class="header-footer-img"/></p>', $minified_html );
	}
}
