<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Mergetags;
use GFPDF\Model\Model_Mergetags;

use WP_UnitTestCase;
use GPDFAPI;

/**
 * Test Gravity PDF Mergetag functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Test the model / view / controller for the Shortcode MVC
 *
 * @since 4.1
 * @group mergetags
 */
class Test_Mergetags extends WP_UnitTestCase {

	/**
	 * Our Controller
	 *
	 * @var \GFPDF\Controller\Controller_Shortcodes
	 *
	 * @since 4.1
	 */
	public $controller;

	/**
	 * Our Model
	 *
	 * @var \GFPDF\Model\Model_Shortcodes
	 *
	 * @since 4.1
	 */
	public $model;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.1
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model = new Model_Mergetags( $gfpdf->options, GPDFAPI::get_mvc_class( 'Model_PDF' ), $gfpdf->log, $gfpdf->misc );

		$this->controller = new Controller_Mergetags( $this->model );
		$this->controller->init();
	}

	/**
	 * Test the appropriate filters are set up
	 *
	 * @since 4.1
	 */
	public function test_filters() {
		$this->assertEquals( 10, has_filter( 'gform_replace_merge_tags', [ $this->model, 'process_pdf_mergetags' ] ) );
		$this->assertEquals( 10, has_filter( 'gform_custom_merge_tags', [ $this->model, 'add_pdf_mergetags' ] ) );
	}

	/**
	 * Check we correctly load the form's PDF mergetags in the correct format
	 *
	 * @since 4.1
	 */
	public function test_add_mergetags() {
		$form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		$tags = $this->model->add_pdf_mergetags( [], $form['id'] );

		$this->assertSame( 3, sizeof( $tags ) );

		$this->assertEquals( 'PDF: My First PDF Template', $tags[0]['label'] );
		$this->assertEquals( '{My First PDF Template:pdf:555ad84787d7e}', $tags[0]['tag'] );

		$this->assertEquals( '{My First PDF Template (copy):pdf:556690c67856b}', $tags[1]['tag'] );
	}

	/**
	 * Check we correctly convert our PDF mergetag
	 *
	 * @param string $expected
	 * @param string $text
	 * @param bool $encode
	 *
	 * @dataProvider provider_process_pdf_mergetags
	 *
	 * @since        4.1
	 */
	public function test_process_pdf_mergetags( $expected, $text, $encode = true ) {
		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$results = $this->model->process_pdf_mergetags( $text, $form, $entry, $encode );

		$this->assertEquals( $expected, $results );
	}

	/**
	 * Data provider for test_process_pdf_mergetags
	 *
	 * @return array
	 */
	public function provider_process_pdf_mergetags() {
		return [

			/* Valid single tag */
			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1',
				'text'     => '{My First PDF Template (Copy):pdf:556690c67856b}',
			],

			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1',
				'text'     => '{:pdf:556690c67856b}',
			],

			[
				'expected' => 'This is my content<br> It is awesome<br><br>http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1',
				'text'     => 'This is my content<br> It is awesome<br><br>{:pdf:556690c67856b}',
			],

			[
				'expected' => 'This is my content<br> It is awesome<br><br>http://example.org/?gpdf=1&pid=556690c67856b&lid=1',
				'text'     => 'This is my content<br> It is awesome<br><br>{:pdf:556690c67856b}',
				'encode'   => false,
			],

			[
				'expected' => "This is my content\n It is awesome\n\nhttp://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1",
				'text'     => "This is my content\n It is awesome\n\n{:pdf:556690c67856b}",
			],

			/* Invalid format */
			[
				'expected' => ':pdf:556690c67856b}',
				'text'     => ':pdf:556690c67856b}',
			],

			[
				'expected' => '{:pdf:556690c67856b',
				'text'     => '{:pdf:556690c67856b',
			],

			[
				'expected' => ':pdf:556690c67856b',
				'text'     => ':pdf:556690c67856b',
			],

			/* PDF Config not active */
			[
				'expected' => '',
				'text'     => '{Not Active:pdf:556690c8d7f82}',
			],

			[
				'expected' => 'My content ',
				'text'     => 'My content {Not Active:pdf:556690c8d7f82}',
			],

			[
				'expected' => 'My content<br><br>Other Stuff',
				'text'     => 'My content<br><br>{Not Active:pdf:556690c8d7f82}<br>Other Stuff',
			],

			[
				'expected' => 'My content<br /><br>Other Stuff',
				'text'     => 'My content<br /><br>{Not Active:pdf:556690c8d7f82}<br>Other Stuff',
			],

			[
				'expected' => "My content\n\nOther Stuff",
				'text'     => "My content\n\n{Not Active:pdf:556690c8d7f82}\nOther Stuff",
			],

			/* Conditional logic failed */
			[
				'expected' => '',
				'text'     => '{Conditional Failed:pdf:555ad84787d7e}',
			],

			/* Multiple tags */
			[
				'expected' => "My Content goes here\n\nhttp://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1\n",
				'text'     => "My Content goes here\n\n{Not Active:pdf:556690c8d7f82}\n{My First PDF Template (Copy):pdf:556690c67856b}\n{Conditional Failed:pdf:555ad84787d7e}",
			],
		];
	}
}