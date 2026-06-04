<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @group helper-misc
 */
class Test_Helper_Misc_Forms extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public Helper_Misc $misc;

	public function set_up(): void {
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

		$this->assertSame( 'No', reset( $test ) );
		$this->assertSame( 'Yes', next( $test ) );
		$this->assertSame( 'I do not know', end( $test ) );
	}

	/**
	 * @dataProvider provider_remove_extension_from_string
	 */
	public function test_remove_extension_from_string( $expected, $string, $type ) {
		$this->assertSame( $expected, $this->misc->remove_extension_from_string( $string, $type ) );
	}

	public function provider_remove_extension_from_string(): array {
		return [
			'strips .pdf extension'              => [ 'mydocument', 'mydocument.pdf', '.pdf' ],
			'matches case-insensitively'         => [ 'mydocument', 'mydocument.jpg', '.Jpg' ],
			'leaves extension when type differs' => [ 'mydocument.pdf', 'mydocument.pdf', '.pda' ],
			'ASCII underscore + .php'            => [ 'Helper_Document', 'Helper_Document.php', '.php' ],
			'katakana underscore + .php'         => [ 'カタ_Document', 'カタ_Document.php', '.php' ],
			'katakana underscore + .excel'       => [ 'カタ_Document', 'カタ_Document.excel', '.excel' ],
			'no underscore + .excel'             => [ 'Working', 'Working.excel', '.excel' ],
			'kanji underscore + .pdf'            => [ 'Working_漢字', 'Working_漢字.pdf', '.pdf' ],
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

	/**
	 * Regression for the disabled-toggle + stale-rules case: the runtime must treat
	 * `conditional` as the source of truth and skip rule evaluation when it's empty.
	 */
	public function test_conditional_logic_passes() {
		global $gfpdf;

		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$gfpdf->data->form_settings                = [];
		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		$failing_rules = [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				[
					'fieldId'  => '1',
					'operator' => 'is',
					'value'    => 'this-will-never-match',
				],
			],
		];

		$passing_rules = [
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => [
				[
					'fieldId'  => '1',
					'operator' => 'is',
					'value'    => 'My Single Line Response',
				],
			],
		];

		$this->assertTrue(
			$this->misc->conditional_logic_passes(
				[ 'conditional' => '', 'conditionalLogic' => $failing_rules ],
				$entry
			),
			'Toggle off with stale failing rules must pass — UI says conditional logic is disabled.'
		);

		$this->assertTrue(
			$this->misc->conditional_logic_passes(
				[ 'conditional' => '', 'conditionalLogic' => $passing_rules ],
				$entry
			)
		);

		$this->assertTrue(
			$this->misc->conditional_logic_passes(
				[ 'conditional' => '1', 'conditionalLogic' => $passing_rules ],
				$entry
			)
		);

		$this->assertFalse(
			$this->misc->conditional_logic_passes(
				[ 'conditional' => '1', 'conditionalLogic' => $failing_rules ],
				$entry
			)
		);

		$this->assertTrue( $this->misc->conditional_logic_passes( [ 'conditional' => '1' ], $entry ) );
		$this->assertTrue( $this->misc->conditional_logic_passes( [], $entry ) );

		$this->assertTrue(
			$this->misc->conditional_logic_passes( [ 'conditionalLogic' => $passing_rules ], $entry ),
			'Legacy settings without the toggle should evaluate the rules normally.'
		);
		$this->assertFalse(
			$this->misc->conditional_logic_passes( [ 'conditionalLogic' => $failing_rules ], $entry ),
			'Legacy settings without the toggle should still reject entries against failing rules.'
		);
	}

	public function test_get_fields_sorted_by_id() {
		$this->assertCount( 0, $this->misc->get_fields_sorted_by_id( 0 ) );

		$form   = $this->form( 'all-form-fields' );
		$fields = $this->misc->get_fields_sorted_by_id( $form['id'] );

		$this->assertCount( 56, $fields );
		$this->assertSame( 'Section Break', $fields[10]->label );
	}

	/**
	 * @dataProvider provider_in_array
	 */
	public function test_in_array( $expected, $strict, $needle, $haystack ) {
		$this->assertSame( $expected, $this->misc->in_array( $needle, $haystack, $strict ) );
	}

	public function provider_in_array(): array {
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
