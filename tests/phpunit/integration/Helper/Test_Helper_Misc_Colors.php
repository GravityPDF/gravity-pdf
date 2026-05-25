<?php

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group helper-misc
 */
class Test_Helper_Misc_Colors extends TestCase {

	/** @var Helper_Misc */
	public $misc;

	public function set_up() {
		global $gfpdf;
		parent::set_up();
		$this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->gform, $gfpdf->data );
	}

	/**
	 * @dataProvider provider_get_contrast
	 */
	public function test_get_contrast( $expected, $hexcolor ) {
		$this->assertEquals( $expected, $this->misc->get_contrast( $hexcolor ) );
	}

	public function provider_get_contrast() {
		return [
			[ '#FFF', '#000000' ],
			[ '#FFF', '#000' ],
			[ '#FFF', '#222' ],
			[ '#FFF', '#068a2b' ],
			[ '#FFF', '#a70404' ],
			[ '#000', '#fff' ],
			[ '#000', '#FFFFFF' ],
			[ '#000', '#999' ],
			[ '#000', '#EEE' ],
			[ '#000', '#CCC' ],
		];
	}

	/**
	 * @dataProvider provider_change_brightness
	 */
	public function test_change_brightness( $expected, $hexcolor, $diff ) {
		$this->assertEquals( $expected, $this->misc->change_brightness( $hexcolor, $diff ) );
	}

	public function provider_change_brightness() {
		return [
			[ '#0a0a0a', '#000000', 10 ],
			[ '#0a0a0a', '#000', 10 ],
			[ '#181818', '#222', -10 ],
			[ '#2c2c2c', '#222', 10 ],
			[ '#fefefe', '#CCC', 50 ],
			[ '#9a9a9a', '#CCC', -50 ],
			[ '#ffffff', '#FFFFFF', 25 ],
			[ '#e6e6e6', '#FFF', -25 ],
		];
	}

	/**
	 * @dataProvider provider_get_background_and_border_contrast
	 */
	public function test_get_background_and_border_contrast( $expected, $hex ) {
		$contrast = $this->misc->get_background_and_border_contrast( $hex );

		$this->assertEquals( $expected[0], $contrast['background'] );
		$this->assertEquals( $expected[1], $contrast['border'] );
	}

	public function provider_get_background_and_border_contrast() {
		return [
			[ [ '#ebebeb', '#c3c3c3' ], '#FFFFFF' ],
			[ [ '#ebebeb', '#c3c3c3' ], '#FFF' ],
			[ [ '#141414', '#3c3c3c' ], '#000000' ],
			[ [ '#141414', '#3c3c3c' ], '#000' ],
			[ [ '#e82828', '#ff5050' ], '#d41414' ],
			[ [ '#295399', '#517bc1' ], '#153f85' ],
			[ [ '#5cbb50', '#349328' ], '#70cf64' ],
			[ [ '#dfdfdf', '#b7b7b7' ], '#f3f3f3' ],
		];
	}
}
