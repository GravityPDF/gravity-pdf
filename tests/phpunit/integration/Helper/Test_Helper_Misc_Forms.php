<?php

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @group helper-misc
 */
class Test_Helper_Misc_Forms extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	/** @var Helper_Misc */
	public $misc;

	public function set_up() {
		global $gfpdf;
		parent::set_up();
		$this->misc = new Helper_Misc( $gfpdf->log, $gfpdf->gform, $gfpdf->data );
	}

	public function test_array_unshift_assoc() {
		$array = [
			'item1' => 'Yes',
			'item2' => 'Maybe',
			'item3' => 'I do not know',
		];

		$test = $this->misc->array_unshift_assoc( $array, 'item0', 'No' );

		$this->assertEquals( 'No', reset( $test ) );
		$this->assertEquals( 'Yes', next( $test ) );
		$this->assertEquals( 'I do not know', end( $test ) );
	}

	/**
	 * @dataProvider provider_remove_extension_from_string
	 */
	public function test_remove_extension_from_string( $expected, $string, $type ) {
		$this->assertEquals( $expected, $this->misc->remove_extension_from_string( $string, $type ) );
	}

	public function provider_remove_extension_from_string() {
		return [
			[ 'mydocument', 'mydocument.pdf', '.pdf' ],
			[ 'mydocument', 'mydocument.jpg', '.Jpg' ],
			[ 'mydocument.pdf', 'mydocument.pdf', '.pda' ],
			[ 'Helper_Document', 'Helper_Document.php', '.php' ],
			[ 'カタ_Document', 'カタ_Document.php', '.php' ],
			[ 'カタ_Document', 'カタ_Document.excel', '.excel' ],
			[ 'Working', 'Working.excel', '.excel' ],
			[ 'Working_漢字', 'Working_漢字.pdf', '.pdf' ],
		];
	}

	public function test_evaluate_conditional_logic() {
		global $gfpdf;

		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$gfpdf->data->form_settings                = [];
		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		$logic['actionType'] = 'show';
		$this->assertTrue( $this->misc->evaluate_conditional_logic( $logic, $entry ) );

		$logic['actionType'] = 'hide';
		$this->assertFalse( $this->misc->evaluate_conditional_logic( $logic, $entry ) );
	}

	public function test_get_fields_sorted_by_id() {
		$this->assertSame( 0, count( $this->misc->get_fields_sorted_by_id( 0 ) ) );

		$form   = $this->form( 'all-form-fields' );
		$fields = $this->misc->get_fields_sorted_by_id( $form['id'] );

		$this->assertEquals( 56, count( $fields ) );
		$this->assertEquals( 'Section Break', $fields[10]->label );
	}

	/**
	 * @dataProvider provider_in_array
	 */
	public function test_in_array( $expected, $strict, $needle, $haystack ) {
		$this->assertSame( $expected, $this->misc->in_array( $needle, $haystack, $strict ) );
	}

	public function provider_in_array() {
		return [
			[
				true,
				true,
				'find me',
				[
					'item 1',
					'item 2',
					'item 3' => [ 'test', 'find me' ],
					'item 4',
				],
			],
			[
				false,
				true,
				20,
				[
					'item 1',
					'item 2' => [ 'stuff', 'here', [ '20' ] ],
					'item 3',
				],
			],
			[
				true,
				false,
				20,
				[
					'item 1',
					'item 2' => [ 'stuff', 'here', [ '20' ] ],
					'item 3',
				],
			],
			[
				true,
				true,
				'Find Me',
				[
					'item 1' => [ 'hi', 'how', 'are', [ 'you' => [ 'going' ] ] ],
					'item 2' => [ 'stuff', 'here', [ 'Find Me' ] ],
					'item 3',
				],
			],
			[
				true,
				true,
				'Find Me',
				[
					'item 1' => [ 'hi', 'how', 'are', [ 'you' => [ 'going' => [ 'Find Me' ] ] ] ],
					'item 2' => [ 'stuff', 'here', [ 'wow' ] ],
					'item 3',
				],
			],
			[
				false,
				true,
				'find me',
				[
					'item 1',
					'item 2' => [ 'stuff', 'here', [ 'Find Me' ] ],
					'item 3',
				],
			],
		];
	}

	public function test_cleanup_dir() {
		$data = GPDFAPI::get_data_class();
		$path = $data->template_location . 'folder/';
		wp_mkdir_p( $path );
		touch( $path . 'test' );

		$this->assertFileExists( $path . 'test' );

		$this->misc->cleanup_dir( $path );

		$this->assertFileDoesNotExist( $path . 'test' );
		$this->assertDirectoryExists( $path );

		rmdir( $path );

		$path = sys_get_temp_dir() . '/folder/';
		wp_mkdir_p( $path );
		touch( $path . 'test' );
		$this->assertFileExists( $path . 'test' );

		$this->misc->cleanup_dir( $path );

		$this->assertFileExists( $path . 'test' );
		unlink( $path . 'test' );
		rmdir( $path );
	}

	public function test_rmdir() {
		$data = GPDFAPI::get_data_class();
		$path = $data->template_location . 'folder/';
		wp_mkdir_p( $path );
		touch( $path . 'test' );

		$this->assertFileExists( $path . 'test' );

		$this->misc->rmdir( $path, false );

		$this->assertFileDoesNotExist( $path . 'test' );
		$this->assertDirectoryExists( $path );

		touch( $path . 'test' );

		$this->assertFileExists( $path . 'test' );

		$this->misc->rmdir( $path );

		$this->assertFileDoesNotExist( $path . 'test' );
		$this->assertDirectoryDoesNotExist( $path );

		$path = sys_get_temp_dir() . '/folder/';
		wp_mkdir_p( $path );
		$this->assertDirectoryExists( $path );

		$results = $this->misc->rmdir( $path );
		$this->assertSame( 'gfpdf_rmdir_directory_not_approved', $results->get_error_code() );

		$this->assertDirectoryExists( $path );
		rmdir( $path );
	}

	public function test_flatten_array() {
		$test_array = [
			'one' => 'first',
			'two' => 'second',
		];

		$this->assertSame( [ 'one', 'two' ], $this->misc->flatten_array( $test_array ) );
		$this->assertSame( [ 'first', 'second' ], $this->misc->flatten_array( $test_array, 'values' ) );

		$test_array = [
			'top-one' => [
				'one' => 'first',
			],
			'top-two' => [
				'two' => 'second',
			],
		];

		$this->assertSame( [ 'one', 'two' ], $this->misc->flatten_array( $test_array ) );
		$this->assertSame( [ 'first', 'second' ], $this->misc->flatten_array( $test_array, 'values' ) );

		$test_array = [
			[
				'top-one' => [
					'one' => 'first',
				],
				'top-two' => [
					'two' => 'second',
				],
			],
		];

		$this->assertSame( [ 'top-one', 'top-two' ], $this->misc->flatten_array( $test_array ) );
	}
}
