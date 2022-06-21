<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @package   GFPDF\Model
 *
 * @group     statics
 */
class Test_Kses extends WP_UnitTestCase {

	/**
	 * @dataProvider provider_parse_pdf_tags_and_attributes
	 */
	public function test_parse_pdf_tags_and_attributes( $html ) {
		$this->assertSame( $html, Kses::parse( $html ) );
	}

	/**
	 * @dataProvider provider_parse_pdf_tags_and_attributes
	 */
	public function test_output_pdf_tags_and_attributes( $html ) {
		ob_start();
		Kses::output( $html );

		$this->assertSame( $html, ob_get_clean() );
	}

	public function provider_parse_pdf_tags_and_attributes(): array {
		return [
			[ '<table autosize="1" rotate="90"><tr><td>Cell</td></tr>' ],
			[ '<img rotate="90" src="http://localhost/image.jpg" />' ],
			[ '<dottab />' ],
			[ '<dottab>' ],
			[ '<dottab></dottab>' ],
			[ '<dottab dir="rtl" outdent="1mm" id="divider" class="breaker" />' ],
			[ '<meter value="0.5" />' ],
			[ '<meter dir="rtl" value="20" max="100" min="0" low="10" high="50" optimum="80" width="500" height="100" style="color: red" />' ],
			[ '<progress value="0.5" />' ],
			[ '<progress dir="rtl" value="0.5" max="1.5" width="500" height="100" id="progress" class="indicator" style="color: red" />' ],
			[ '<pagebreak>' ],
			[ '<pagebreak />' ],
			[ '<pagebreak></pagebreak>' ],
			[ '<pagebreak orientation="L" type="ODD" resetpagenum="2" pagenumstyle="a" suppress="1" sheet-size="A4" page-selector="toc" margin-left="10" margin-right="5" margin-top="15" margin-bottom="7" />' ],
			[ '<barcode>' ],
			[ '<barcode type="QR" code="example" text="1" size="2" height="300" pr="2.1" id="qr-example" class="barcode" style="color: red">' ],
			[ '<div style="background-image-opacity: 0.5;background-image-resize: 5;box-shadow: #0a0a0a;hyphens: auto;page: toc;page-break-before: always;page-break-inside: avoid;page-break-after: always;rotate: 90;z-index: 2">My content</div>' ],
		];
	}

	/**
	 * @dataProvider provider_parse_cleaned
	 */
	public function test_parse_cleaned( $expected, $html ) {
		$this->assertSame( $expected, Kses::parse( $html ) );
	}

	public function provider_parse_cleaned(): array {
		return [
			[ '', '<invalid>' ],
			[ '<video src="//w.org/movie.mov" poster="//w.org/movie.jpg" />', '<video src="bad://w.org/movie.mov" poster="bad://w.org/movie.jpg" />' ],
			[ '<video src="https://videos.files.wordpress.com/DZEMDKxc/video-0f9c363010.mp4" />', '<video onload="alert(1);" src="https://videos.files.wordpress.com/DZEMDKxc/video-0f9c363010.mp4" />' ],
			[ '', "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0B\x0C\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\X1C\x1D\x1E\x1F" ],
			[ 'hello world. WORDPRESS KSES./', "\x1Fh\x1Ee\x1Dl\x1Cl\x1Bo\x1A \x19w\x18o\x17r\x16l\x15d\x14.\x13 \x12W\x11O\x10R\x0FD\x0EP\x0CR\x0BE\x08S\x07S\x06 \x05K\x04S\X03E\x02S\x01.\x00/" ],
			[ 'This <div style="float:left"> is more of a concern.', 'This <div style="float:\\0left"> is more of a concern.' ],
		];
	}
}
