<?php

namespace GFPDF\Model;

use GFPDF\Controller\Controller_Mergetags;
use GFPDF\Controller\Controller_Shortcodes;
use GFPDF\Helper\Helper_Url_Signer;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Model_Mergetags
 *
 * @package   GFPDF\Model
 *
 * @group     model
 * @group     tags
 */
class Test_Model_Mergetags extends WP_UnitTestCase {

	/**
	 * @var Controller_Shortcodes
	 */
	public $controller;

	/**
	 * @var Model_Shortcodes
	 */
	public $model;

	public function set_up() {
		global $gfpdf;

		parent::set_up();

		/* Setup our test classes */
		$this->model      = new Model_Mergetags( $gfpdf->options, GPDFAPI::get_mvc_class( 'Model_PDF' ), $gfpdf->log, $gfpdf->misc, new Helper_Url_Signer() );
		$this->controller = new Controller_Mergetags( $this->model );
		$this->controller->init();
	}

	/**
	 * Test the appropriate filters are set up
	 */
	public function test_filters() {
		$this->assertEquals( 10, has_filter( 'gform_replace_merge_tags', [ $this->model, 'process_pdf_mergetags' ] ) );
		$this->assertEquals( 10, has_filter( 'gform_custom_merge_tags', [ $this->model, 'add_pdf_mergetags' ] ) );
	}

	/**
	 * Check we correctly load the form's PDF mergetags in the correct format
	 */
	public function test_add_mergetags() {
		$form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];

		$tags = $this->model->add_pdf_mergetags( [], $form['id'] );

		$this->assertSame( 3, count( $tags ) );

		$this->assertEquals( 'PDF: My First PDF Template', $tags[0]['label'] );
		$this->assertEquals( '{My First PDF Template:pdf:555ad84787d7e}', $tags[0]['tag'] );

		$this->assertEquals( '{My First PDF Template (copy):pdf:556690c67856b}', $tags[1]['tag'] );
	}

	/**
	 * Check we correctly convert our PDF mergetag with permalinks disabled
	 *
	 * @param string $expected
	 * @param string $text
	 * @param bool   $encode
	 *
	 * @dataProvider provider_standard_pdf_mergetags_no_permalinks
	 * @dataProvider provider_modifier_pdf_mergetags_no_permalinks
	 */
	public function test_process_pdf_mergetags( $expected, $text, $encode = true ) {
		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$results = $this->model->process_pdf_mergetags( $text, $form, $entry, $encode );

		$this->assertEquals( $expected, $results );
	}

	public function provider_standard_pdf_mergetags_no_permalinks(): array {
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

	public function provider_modifier_pdf_mergetags_no_permalinks(): array {
		return [

			/* Download */
			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;action=download',
				'text'     => '{Label:pdf:556690c67856b:download}',
			],

			/* Print */
			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;print=1',
				'text'     => '{Label:pdf:556690c67856b:print}',
			],

			/* Print and Download (any order) */
			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;action=download&#038;print=1',
				'text'     => '{Label:pdf:556690c67856b:download:print}',
			],

			[
				'expected' => 'http://example.org/?gpdf=1&#038;pid=556690c67856b&#038;lid=1&#038;action=download&#038;print=1',
				'text'     => '{Label:pdf:556690c67856b:print:download}',
			],
		];
	}

	/**
	 * Check we correctly convert our PDF mergetag with permalinks enabled
	 *
	 * @param string $expected
	 * @param string $text
	 * @param bool   $encode
	 *
	 * @dataProvider provider_standard_pdf_mergetags_permalinks
	 * @dataProvider provider_modifier_pdf_mergetags_permalinks
	 */
	public function test_process_pdf_mergetags_permalink( $expected, $text, $encode = true ) {
		global $wp_rewrite;

		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		$this->test_process_pdf_mergetags( $expected, $text, $encode );

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
		flush_rewrite_rules();
	}

	public function provider_standard_pdf_mergetags_permalinks(): array {
		return [

			/* Valid single tag */
			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/',
				'text'     => '{My First PDF Template (Copy):pdf:556690c67856b}',
			],

			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/',
				'text'     => '{:pdf:556690c67856b}',
			],

			[
				'expected' => 'This is my content<br> It is awesome<br><br>http://example.org/pdf/556690c67856b/1/',
				'text'     => 'This is my content<br> It is awesome<br><br>{:pdf:556690c67856b}',
			],

			[
				'expected' => 'This is my content<br> It is awesome<br><br>http://example.org/pdf/556690c67856b/1/',
				'text'     => 'This is my content<br> It is awesome<br><br>{:pdf:556690c67856b}',
				'encode'   => false,
			],

			[
				'expected' => "This is my content\n It is awesome\n\nhttp://example.org/pdf/556690c67856b/1/",
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
				'expected' => "My Content goes here\n\nhttp://example.org/pdf/556690c67856b/1/\n",
				'text'     => "My Content goes here\n\n{Not Active:pdf:556690c8d7f82}\n{My First PDF Template (Copy):pdf:556690c67856b}\n{Conditional Failed:pdf:555ad84787d7e}",
			],
		];
	}

	public function provider_modifier_pdf_mergetags_permalinks(): array {
		return [

			/* Download */
			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/download/',
				'text'     => '{Label:pdf:556690c67856b:download}',
			],

			/* Print */
			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/?print=1',
				'text'     => '{Label:pdf:556690c67856b:print}',
			],

			/* Print and Download (any order) */
			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/download/?print=1',
				'text'     => '{Label:pdf:556690c67856b:download:print}',
			],

			[
				'expected' => 'http://example.org/pdf/556690c67856b/1/download/?print=1',
				'text'     => '{Label:pdf:556690c67856b:print:download}',
			],
		];
	}

	/**
	 * Check for signed URL when processing merge tag
	 *
	 * @param string $text
	 *
	 * @dataProvider provider_signed_modifier_pdf_mergetags
	 */
	public function test_process_pdf_mergetags_signed_no_permalink( $text ) {
		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$results = $this->model->process_pdf_mergetags( $text, $form, $entry, false );

		$this->assertStringContainsString( '&signature=', $results );
		$this->assertStringContainsString( '&expires=', $results );

		if ( strpos( $text, 'download' ) !== false ) {
			$this->assertStringContainsString( 'action=download', $results );
		}

		if ( strpos( $text, 'print' ) !== false ) {
			$this->assertStringContainsString( '&print=1', $results );
		}
	}

	/**
	 * Check for signed URL when processing merge tag
	 *
	 * @param string $text
	 *
	 * @dataProvider provider_signed_modifier_pdf_mergetags
	 */
	public function test_process_pdf_mergetags_signed_permalink( $text ) {
		global $wp_rewrite;

		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		$form  = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$results = $this->model->process_pdf_mergetags( $text, $form, $entry, false );

		$this->assertStringContainsString( 'signature=', $results );
		$this->assertStringContainsString( 'expires=', $results );

		if ( strpos( $text, 'download' ) !== false ) {
			$this->assertStringContainsString( '/download/', $results );
		}

		if ( strpos( $text, 'print' ) !== false ) {
			$this->assertStringContainsString( '?print=1', $results );
		}

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
		flush_rewrite_rules();
	}

	public function provider_signed_modifier_pdf_mergetags(): array {
		return [
			[ '{Label:pdf:556690c67856b:signed}' ],
			[ '{Label:pdf:556690c67856b:signed,1 day}' ],
			[ '{Label:pdf:556690c67856b:signed,3 weeks}' ],
			[ '{Label:pdf:556690c67856b:signed,5 months}' ],
			[ '{Label:pdf:556690c67856b:download:signed}' ],
			[ '{Label:pdf:556690c67856b:print:signed}' ],
			[ '{Label:pdf:556690c67856b:download:print:signed}' ],
			[ '{Label:pdf:556690c67856b:print:download:signed}' ],
			[ '{Label:pdf:556690c67856b:download:signed,3 weeks}' ],
			[ '{Label:pdf:556690c67856b:print:signed,1 day}' ],
			[ '{Label:pdf:556690c67856b:download:print:signed,5 months}' ],
			[ '{Label:pdf:556690c67856b:print:download:signed,1 year}' ],
			[ '{Label:pdf:556690c67856b:signed:download}' ],
			[ '{Label:pdf:556690c67856b:signed,1 day:download}' ],
			[ '{Label:pdf:556690c67856b:signed,3 weeks:print}' ],
			[ '{Label:pdf:556690c67856b:signed,5 months:print:download}' ],
			[ '{Label:pdf:556690c67856b:signed,5 months:download:print}' ],
		];
	}
}
