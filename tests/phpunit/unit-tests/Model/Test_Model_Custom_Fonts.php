<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Exceptions\GravityPdfIdException;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Model_Custom_Fonts
 *
 * @package   GFPDF\Model
 *
 * @group     model
 * @group     fonts
 */
class Test_Model_Custom_Fonts extends WP_UnitTestCase {

	/**
	 * @var Model_Custom_Fonts
	 */
	public $model;

	public function set_up() {

		parent::set_up();

		$this->model = new Model_Custom_Fonts( GPDFAPI::get_options_class() );
	}

	public function tear_down() {
		$options = GPDFAPI::get_options_class();
		$options->update_option( 'custom_fonts', [] );

		parent::tear_down();
	}

	/**
	 * @dataProvider data_check_font_name_valid
	 */
	public function test_check_font_name_valid( bool $expected, string $name ) {
		$this->assertSame( $expected, $this->model->check_font_name_valid( $name ) );
	}

	public function data_check_font_name_valid(): array {
		return [
			[ true, 'Font Name' ],
			[ true, 'Font Name 2' ],
			[ true, 'fontname542' ],
			[ true, 'Happy' ],
			[ true, 'H' ],
			[ true, '123' ],
			[ true, ' ' ],
			[ false, '' ],
			[ false, 'Font-Name' ],
			[ false, 'Font Name #' ],
			[ false, 'Fónt Name' ],
			[ false, '(' ],
			[ false, 'My (Name)' ],
		];
	}

	/**
	 * @dataProvider data_check_font_id_valid
	 */
	public function test_check_font_id_valid( bool $expected, string $id ) {
		$this->assertSame( $expected, $this->model->check_font_id_valid( $id ) );
	}

	public function data_check_font_id_valid(): array {
		return [
			[ true, 'fontname' ],
			[ true, 'fontname2' ],
			[ true, 'happy' ],
			[ true, 'h' ],
			[ false, 'Font Name' ],
			[ false, 'Font Name 2' ],
			[ true, 'fontname542' ],
			[ false, 'Happy' ],
			[ false, 'H' ],
			[ true, '123' ],
			[ false, ' ' ],
			[ false, '' ],
			[ false, 'Font-Name' ],
			[ false, 'Font Name #' ],
			[ false, 'Fónt Name' ],
			[ false, '(' ],
			[ false, 'My (Name)' ],
		];
	}

	public function test_get_custom_fonts() {
		$this->model->add_font( [ 'id' => 'font1' ] );
		$this->model->add_font( [ 'id' => 'font2' ] );
		$this->model->add_font( [ 'id' => 'font3' ] );

		$this->assertCount( 3, $this->model->get_custom_fonts() );
	}

	public function test_get_font_by_id_success() {
		$this->model->add_font( [ 'id' => 'font1' ] );

		$this->assertArrayHasKey( 'id', $this->model->get_font_by_id( 'font1' ) );
	}


	public function test_get_font_by_id_exception() {
		$this->expectException( GravityPdfIdException::class );

		$this->model->get_font_by_id( 'nonexistant' );
	}

	public function test_update_font() {
		$this->model->add_font(
			[
				'id'   => 'font1',
				'name' => 'Font',
			]
		);

		$font = $this->model->get_font_by_id( 'font1' );
		$this->assertSame( 'Font', $font['name'] );

		$font['name'] = 'New Font';

		$this->model->update_font( $font );
		$font = $this->model->get_font_by_id( 'font1' );
		$this->assertSame( 'New Font', $font['name'] );
	}

	public function test_delete_font_success() {
		$this->model->add_font(
			[
				'id'   => 'font1',
				'name' => 'Font',
			]
		);

		$this->assertCount( 1, $this->model->get_custom_fonts() );

		$this->model->delete_font( 'font1' );

		$this->assertCount( 0, $this->model->get_custom_fonts() );
	}

	public function test_delete_font_exception() {
		$this->expectException( GravityPdfIdException::class );

		$this->model->delete_font( 'nonexistant' );
	}

	public function test_get_unique_id() {
		$this->assertSame( 'myuniqueid', $this->model->get_unique_id( 'myuniqueid' ) );
		$this->assertMatchesRegularExpression( sprintf( '/%s([0-9]{5})/', 'arial' ), $this->model->get_unique_id( 'arial' ) );
		$this->assertMatchesRegularExpression( sprintf( '/%s([0-9]{5})/', 'symbol' ), $this->model->get_unique_id( 'symbol' ) );
		$this->assertMatchesRegularExpression( sprintf( '/%s([0-9]{5})/', 'dejavusans' ), $this->model->get_unique_id( 'dejavusans' ) );

		$this->assertSame( 'font1', $this->model->get_unique_id( 'font1' ) );
		$this->model->add_font(
			[
				'id'        => 'font1',
				'font_name' => 'Font Name',
			]
		);
		$this->assertMatchesRegularExpression( sprintf( '/%s([0-9]{5})/', 'font1' ), $this->model->get_unique_id( 'font1' ) );
	}

	/**
	 * @dataProvider data_has_unique_font_id
	 */
	public function test_has_unique_font_id( bool $expected, string $id ) {
		$this->assertSame( $expected, $this->model->has_unique_font_id( $id ) );
	}

	public function data_has_unique_font_id(): array {
		return [
			[ true, 'unique' ],
			[ true, 'arial123' ],
			[ false, 'arial' ],
			[ false, 'times' ],
			[ false, 'ctimes' ],
			[ false, 'chelvetica' ],
			[ false, 'dejavusans' ],
			[ true, 'dejavusans1' ],
		];
	}
}
