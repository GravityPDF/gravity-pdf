<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Exceptions\GravityPdfException;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
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

	public function test_numeric_output() {
		$this->assertSame( '1234567890', Kses::parse( 1234567890 ) );
	}

	public function test_null_output() {
		$this->assertSame( '', Kses::parse( null ) );
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
			[ '<form id="A" class="B" style="margin:0" action="#" method="post"><input type="text" value="Name" /></form>' ],
			[ '<form><input type="checkbox" checked="checked" id="A" class="B" title="C" name="D" /></form>' ],
			[ '<textarea>Contents</textarea>' ],
			[ '<textarea id="A" class="B" dir="rtl" title="C" name="D" disabled="disabled" cols="5" rows="4">Contents</textarea>' ],
			[ '<select><option>A</option><option>B</option></select>' ],
			[ '<select id="A" class="B" style="background:#EEE" size="5" multiple="multiple" required="required"><option>A</option><option>B</option></select>' ],
			[ '<select><option selected="selected" value="aaa">A</option><option>B</option></select>' ],
			[ '<annotation content="Text" pos-x="20" pos-y="50" author="Name" subject="Value" />' ],
			[ '<bookmark content="A" level="1" />' ],
			[ '<column column-count="3" vAlign="j" column-gap="50">' ],
			[ '<column column-count="3" vAlign="j" column-gap="50" />Content<columnbreak /> More content' ],
			[ '<htmlpageheader name="Name">Content here</htmlpageheader>' ],
			[ '<htmlpagefooter name="Name">Content here</htmlpagefooter>' ],
			[ '<sethtmlpageheader name="Name" value="1" show-this-page="1" />' ],
			[ '<sethtmlpagefooter name="Name" value="1" />' ],
			[ '<indexentry />' ],
			[ '<indexentry content="Name" xref="Value" />' ],
			[ '<indexinsert links="on" />' ],
			[ '<tocpagebreak paging="1" links="1" name="A" toc-preHTML="B" toc-postHTML="C" toc-page-selector="D" toc-sheet-size="E" />' ],
			['<tocpagebreak toc-odd-header-name="A" toc-odd-footer-name="B" toc-odd-header-value="C" toc-odd-footer-value="D" />'],
			[ '<tocpagebreak toc-bookmarkText="A" toc-resetpagenum="1" toc-resetpagestyle="A" toc-suppress="1" />' ],
			[ '<tocentry content="A" level="B" name="C" />' ],
			[ '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD+wSzIAAAABlBMVEX///+/v7+jQ3Y5AAAADklEQVQI12P4AIX8EAgALgAD/aNpbtEAAAAASUVORK5CYII" alt="Icon" width="200mm" />']
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
