<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group helper-misc
 */
class Test_Helper_Misc_Colors extends TestCase {

	public Helper_Misc $misc;

	public function set_up(): void {
		global $gfpdf;
		parent::set_up();
		$this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->gform, $gfpdf->data );
	}

	/**
	 * @dataProvider provider_get_contrast
	 */
	public function test_get_contrast( $expected, $hexcolor ) {
		$this->assertSame( $expected, $this->misc->get_contrast( $hexcolor ) );
	}

	public function provider_get_contrast(): array {
		return [
			'white on #000000 (long black)'  => [ '#FFF', '#000000' ],
			'white on #000 (short black)'    => [ '#FFF', '#000' ],
			'white on #222 (dark grey)'      => [ '#FFF', '#222' ],
			'white on #068a2b (dark green)'  => [ '#FFF', '#068a2b' ],
			'white on #a70404 (dark red)'    => [ '#FFF', '#a70404' ],
			'black on #fff (short white)'    => [ '#000', '#fff' ],
			'black on #FFFFFF (long white)'  => [ '#000', '#FFFFFF' ],
			'black on #999 (mid grey)'       => [ '#000', '#999' ],
			'black on #EEE (light grey)'     => [ '#000', '#EEE' ],
			'black on #CCC (lighter grey)'   => [ '#000', '#CCC' ],
		];
	}

	/**
	 * @dataProvider provider_change_brightness
	 */
	public function test_change_brightness( $expected, $hexcolor, $diff ) {
		$this->assertSame( $expected, $this->misc->change_brightness( $hexcolor, $diff ) );
	}

	public function provider_change_brightness(): array {
		return [
			'#000000 +10 → #0a0a0a' => [ '#0a0a0a', '#000000', 10 ],
			'#000 +10 → #0a0a0a'    => [ '#0a0a0a', '#000', 10 ],
			'#222 -10 → #181818'    => [ '#181818', '#222', -10 ],
			'#222 +10 → #2c2c2c'    => [ '#2c2c2c', '#222', 10 ],
			'#CCC +50 → #fefefe'    => [ '#fefefe', '#CCC', 50 ],
			'#CCC -50 → #9a9a9a'    => [ '#9a9a9a', '#CCC', -50 ],
			'#FFFFFF +25 → #ffffff' => [ '#ffffff', '#FFFFFF', 25 ],
			'#FFF -25 → #e6e6e6'    => [ '#e6e6e6', '#FFF', -25 ],
		];
	}

	/**
	 * @dataProvider provider_get_background_and_border_contrast
	 */
	public function test_get_background_and_border_contrast( $expected, $hex ) {
		$contrast = $this->misc->get_background_and_border_contrast( $hex );

		$this->assertSame( $expected[0], $contrast['background'] );
		$this->assertSame( $expected[1], $contrast['border'] );
	}

	public function provider_get_background_and_border_contrast(): array {
		return [
			'#FFFFFF (long white)' => [ [ '#ebebeb', '#c3c3c3' ], '#FFFFFF' ],
			'#FFF (short white)'   => [ [ '#ebebeb', '#c3c3c3' ], '#FFF' ],
			'#000000 (long black)' => [ [ '#141414', '#3c3c3c' ], '#000000' ],
			'#000 (short black)'   => [ [ '#141414', '#3c3c3c' ], '#000' ],
			'#d41414 (red)'        => [ [ '#e82828', '#ff5050' ], '#d41414' ],
			'#153f85 (blue)'       => [ [ '#295399', '#517bc1' ], '#153f85' ],
			'#70cf64 (green)'      => [ [ '#5cbb50', '#349328' ], '#70cf64' ],
			'#f3f3f3 (off-white)'  => [ [ '#dfdfdf', '#b7b7b7' ], '#f3f3f3' ],
		];
	}
}
