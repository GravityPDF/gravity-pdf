<?php

namespace GFPDF\Tests;

use GFAPI;
use GFPDF\Helper\Fields\Field_Repeater;
use GFPDFEntryDetail;
use GPDFAPI;
use WP_UnitTestCase;

/**
 * Test our custom template $form_data array
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the Gravity Forms functionality we rely on in Gravity PDF
 *
 * @since 4.0
 * @group form-data
 */
class Test_Form_Data extends WP_UnitTestCase {
	/**
	 * The Gravity Form
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $form;

	/**
	 * The Gravity Form entries imported
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	public $entries = [];

	/**
	 * The $form_data array
	 *
	 * @var array
	 */
	private $form_data;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {
		parent::set_up();

		$this->setup_stubs();
	}

	/**
	 * Create our testing data
	 *
	 * @since 4.0
	 */
	private function setup_stubs() {
		$this->form      = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$this->entries   = $GLOBALS['GFPDF_Test']->entries['all-form-fields'];
		$this->form_data = $GLOBALS['GFPDF_Test']->form_data['all-form-fields'][0];
	}

	/**
	 * Do basic tests on the form data array
	 *
	 * @since 4.0
	 */
	public function test_basic_form_data() {
		$data = $this->form_data;

		/*
		 * Run our tests...
		 */
		$this->assertEquals( 'ALL FIELDS', $data['form_title'] );
		$this->assertEquals( 'This is the form description...', $data['form_description'] );
		$this->assertArrayHasKey( 'pages', $data );

		$date_dmy = '20/1/2015';
		$date_mdy = '1/20/2015';

		$this->assertEquals( $date_dmy, $data['date_created'] );
		$this->assertEquals( $date_mdy, $data['date_created_usa'] );
	}

	/**
	 * Test all the misc data
	 * Most of this blank in our test form so we'll just check all the fields exist for the moment
	 *
	 * @todo  expand unit test later
	 *
	 * @since 4.0
	 */
	public function test_misc_form_data() {
		$data = $this->form_data;

		/*
		 * Run our tests...
		 */
		$misc_array = [
			'date_time',
			'time_24hr',
			'time_12hr',
			'is_starred',
			'is_read',
			'ip',
			'source_url',
			'post_id',
			'currency',
			'payment_status',
			'payment_date',
			'transaction_id',
			'payment_amount',
			'is_fulfilled',
			'created_by',
			'transaction_type',
			'user_agent',
			'status',
		];

		foreach ( $misc_array as $key ) {
			$this->assertArrayHasKey( $key, $data['misc'] );
		}

		$this->assertEquals( '124.183.82.7', $data['misc']['ip'] );
		$this->assertEquals( 'active', $data['misc']['status'] );
		$this->assertEquals( '1', $data['misc']['created_by'] );
	}

	/**
	 * Check the field descriptions are being loads
	 *
	 * @since 4.0
	 */
	public function test_field_descriptions() {
		$data = $this->form_data;

		/*
		 * Run our tests...
		 */
		$this->assertArrayHasKey( 'field_descriptions', $data );
		$this->assertEquals( 'This is the multi select box description', $data['field_descriptions'][4] );
		$this->assertEquals( 'Name Description', $data['field_descriptions'][11] );
	}

	/**
	 * Check the $form_data['field'] key exists
	 *
	 * @since 4.0
	 */
	public function test_field() {
		$data = $this->form_data;

		$this->assertArrayHasKey( 'field', $data );
		$this->assertTrue( is_array( $data['field'] ) );
	}

	/**
	 * Check the single field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_field_single() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'My Single Line Response';
		$this->assertEquals( $response, $field[1] );
		$this->assertEquals( $response, $field['1.Single Line Text'] );
		$this->assertEquals( $response, $field['Single Line Text'] );
	}

	/**
	 * Check the paragraph field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_field_paragraph() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = "My paragraph text response over...<br />\r\n<br />\r\nMultiple lines.";
		$this->assertEquals( $response, $field[2] );
		$this->assertEquals( $response, $field['2.Paragraph Text'] );
		$this->assertEquals( $response, $field['Paragraph Text'] );
	}

	/**
	 * Check the dropdown field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_dropdown() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'Option 3 Value';
		$this->assertEquals( $response, $field[3] );
		$this->assertEquals( $response, $field['3.Drop Down'] );
		$this->assertEquals( $response, $field['Drop Down'] );

		$response = 'Option 3';
		$this->assertEquals( $response, $field['3_name'] );
		$this->assertEquals( $response, $field['3.Drop Down_name'] );
		$this->assertEquals( $response, $field['Drop Down_name'] );
	}

	/**
	 * Check the multiselect field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_multiselect() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'Second Choice';
		$this->assertTrue( in_array( $response, $field[4], true ) );
		$this->assertTrue( in_array( $response, $field['4.Multi Select Box'], true ) );
		$this->assertTrue( in_array( $response, $field['Multi Select Box'], true ) );

		$response = 'Multi Select Second Choice';
		$this->assertTrue( in_array( $response, $field['4_name'], true ) );
		$this->assertTrue( in_array( $response, $field['4.Multi Select Box_name'], true ) );
		$this->assertTrue( in_array( $response, $field['Multi Select Box_name'], true ) );

		$this->assertEquals( 2, count( $field[4] ) );
		$this->assertEquals( 2, count( $field['4_name'] ) );
	}

	/**
	 * Check the number field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_field_number() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = '50032145';
		$this->assertEquals( $response, $field[5] );
		$this->assertEquals( $response, $field['5.Number'] );
		$this->assertEquals( $response, $field['Number'] );
	}

	/**
	 * Test that number fields will use the local currency set in the entry
	 */
	public function test_field_number_currency() {
		$form_json = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/number-fields.json' ) ), true );
		$form_id   = GFAPI::add_form( $form_json );

		$entry_id = GFAPI::add_entry(
			[
				'form_id'  => $form_id,
				'currency' => 'EUR',
				'1'        => 1000.10,
				'2'        => 2000.10,
				'3'        => 3000.10,
			]
		);

		$form_data = GPDFAPI::get_form_data( $entry_id );

		$this->assertEquals( '1000.1', $form_data['field'][1] );
		$this->assertEquals( '2000,1', $form_data['field'][2] );
		$this->assertEquals( '3.000,10 &#8364;', $form_data['field'][3] );

		$entry_id = GFAPI::add_entry(
			[
				'form_id'  => $form_id,
				'currency' => 'AUD',
				'1'        => 1000.10,
				'2'        => 2000.10,
				'3'        => 3000.10,
			]
		);

		$form_data = GPDFAPI::get_form_data( $entry_id );

		$this->assertEquals( '1000.1', $form_data['field'][1] );
		$this->assertEquals( '2000,1', $form_data['field'][2] );
		$this->assertEquals( '$ 3,000.10', $form_data['field'][3] );
	}

	/**
	 * Check the checkbox field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_checkbox() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */

		$response = 'Checkbox Choice 2';
		$this->assertTrue( in_array( $response, $field[6], true ) );
		$this->assertTrue( in_array( $response, $field['6.Checkbox'], true ) );
		$this->assertTrue( in_array( $response, $field['Checkbox'], true ) );

		$response = 'Checkbox Choice 2 Text';
		$this->assertTrue( in_array( $response, $field['6_name'], true ) );
		$this->assertTrue( in_array( $response, $field['6.Checkbox_name'], true ) );
		$this->assertTrue( in_array( $response, $field['Checkbox_name'], true ) );

		$response = 'Checkbox Choice 3';
		$this->assertTrue( in_array( $response, $field[6], true ) );
		$this->assertTrue( in_array( $response, $field['6.Checkbox'], true ) );
		$this->assertTrue( in_array( $response, $field['Checkbox'], true ) );

		$response = 'Checkbox Choice 3 Text';
		$this->assertTrue( in_array( $response, $field['6_name'], true ) );
		$this->assertTrue( in_array( $response, $field['6.Checkbox_name'], true ) );
		$this->assertTrue( in_array( $response, $field['Checkbox_name'], true ) );

		$this->assertEquals( 2, count( $field[6] ) );
	}

	/**
	 * Check the radio field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_radio_button() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'Radio Second Choice';
		$this->assertEquals( $response, $field[7] );
		$this->assertEquals( $response, $field['7.Radio Button'] );
		$this->assertEquals( $response, $field['Radio Button'] );

		$response = 'Radio Second Choice Name';
		$this->assertEquals( $response, $field['7_name'] );
		$this->assertEquals( $response, $field['7.Radio Button_name'] );
		$this->assertEquals( $response, $field['Radio Button_name'] );
	}

	/**
	 * Check the hidden field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_hidden_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'hidden field value';
		$this->assertEquals( $response, $field[8] );
		$this->assertEquals( $response, $field['8.Hidden Field'] );
		$this->assertEquals( $response, $field['Hidden Field'] );
	}

	/**
	 * Check the name field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_name_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$this->assertEquals( 'Mr.', $field[11]['prefix'] );
		$this->assertEquals( 'Jake', $field[11]['first'] );
		$this->assertEquals( 'Middle', $field[11]['middle'] );
		$this->assertEquals( 'Jackson', $field[11]['last'] );
		$this->assertEquals( 'MD', $field[11]['suffix'] );

		$this->assertEquals( 'Mr.', $field['11.Name']['prefix'] );
		$this->assertEquals( 'Jake', $field['11.Name']['first'] );
		$this->assertEquals( 'Middle', $field['11.Name']['middle'] );
		$this->assertEquals( 'Jackson', $field['11.Name']['last'] );
		$this->assertEquals( 'MD', $field['11.Name']['suffix'] );

		$this->assertEquals( 'Mr.', $field['Name']['prefix'] );
		$this->assertEquals( 'Jake', $field['Name']['first'] );
		$this->assertEquals( 'Middle', $field['Name']['middle'] );
		$this->assertEquals( 'Jackson', $field['Name']['last'] );
		$this->assertEquals( 'MD', $field['Name']['suffix'] );
	}

	/**
	 * Check the date field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_date_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = '01/01/2015';
		$this->assertEquals( $response, $field[12] );
		$this->assertEquals( $response, $field['12.Date'] );
		$this->assertEquals( $response, $field['Date'] );
	}

	/**
	 * Check the time field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_time_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = '10:30 am';

		$this->assertEquals( $response, $field[13] );
		$this->assertEquals( $response, $field['13.Time'] );
		$this->assertEquals( $response, $field['Time'] );
	}

	/**
	 * Check the phone field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_phone_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = '(555) 678-1210';
		$this->assertEquals( $response, $field[14] );
		$this->assertEquals( $response, $field['14.Phone'] );
		$this->assertEquals( $response, $field['Phone'] );
	}

	/**
	 * Check the address field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_address_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$this->assertEquals( '12 Address St', $field[15]['street'] );
		$this->assertEquals( 'Line 2', $field[15]['street2'] );
		$this->assertEquals( 'Cityville', $field[15]['city'] );
		$this->assertEquals( 'Statesman', $field[15]['state'] );
		$this->assertEquals( '5000', $field[15]['zip'] );
		$this->assertEquals( 'Chad', $field[15]['country'] );

		$this->assertEquals( '12 Address St', $field['15.Address']['street'] );
		$this->assertEquals( 'Line 2', $field['15.Address']['street2'] );
		$this->assertEquals( 'Cityville', $field['15.Address']['city'] );
		$this->assertEquals( 'Statesman', $field['15.Address']['state'] );
		$this->assertEquals( '5000', $field['15.Address']['zip'] );
		$this->assertEquals( 'Chad', $field['15.Address']['country'] );

		$this->assertEquals( '12 Address St', $field['Address']['street'] );
		$this->assertEquals( 'Line 2', $field['Address']['street2'] );
		$this->assertEquals( 'Cityville', $field['Address']['city'] );
		$this->assertEquals( 'Statesman', $field['Address']['state'] );
		$this->assertEquals( '5000', $field['Address']['zip'] );
		$this->assertEquals( 'Chad', $field['Address']['country'] );
	}

	/**
	 * Check the website field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_website_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'https://gravitypdf.com';
		$this->assertEquals( $response, $field[16] );
		$this->assertEquals( $response, $field['16.Website'] );
		$this->assertEquals( $response, $field['Website'] );
	}

	/**
	 * Check the email field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_email_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'support@gravitypdf.com';
		$this->assertEquals( $response, $field[17] );
		$this->assertEquals( $response, $field['17.Email'] );
		$this->assertEquals( $response, $field['Email'] );
	}

	/**
	 * Check the upload field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_upload_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$this->assertCount( 1, $field[18] );
		$this->assertCount( 1, $field['18.File'] );
		$this->assertCount( 1, $field['18.File_path'] );
		$this->assertCount( 1, $field['18_path'] );
		$this->assertCount( 1, $field['18.File_secured'] );
		$this->assertCount( 1, $field['18_secured'] );

		$this->assertCount( 2, $field[19] );
		$this->assertCount( 2, $field['19.File'] );
		$this->assertCount( 2, $field['19.File_path'] );
		$this->assertCount( 2, $field['19_path'] );
		$this->assertCount( 2, $field['19.File_secured'] );
		$this->assertCount( 2, $field['19_secured'] );

		$this->assertStringStartsWith( 'http://', $field[18][0] );
		$this->assertStringStartsWith( 'http://', $field['18.File'][0] );
		$this->assertStringStartsWith( 'http://', $field[19][0] );
		$this->assertStringStartsWith( 'http://', $field[19][1] );
		$this->assertStringStartsWith( 'http://', $field['19.File'][0] );
		$this->assertStringStartsWith( 'http://', $field['19.File'][1] );
		$this->assertStringStartsWith( 'http://', $field['19.File_secured'][0] );
		$this->assertStringStartsWith( 'http://', $field['19.File_secured'][1] );
		$this->assertStringContainsString( '?gf-download=', $field['19.File_secured'][0] );
		$this->assertStringContainsString( '?gf-download=', $field['19.File_secured'][1] );
	}

	/**
	 * Check the list field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_list_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = '<table autosize="1"';
		$this->assertNotFalse( strpos( $field[20], $response ) );
		$this->assertNotFalse( strpos( $field['20.Basic List'], $response ) );
		$this->assertNotFalse( strpos( $field['Basic List'], $response ) );

		$response = '<table autosize="1"';
		$this->assertNotFalse( strpos( $field[21], $response ) );
		$this->assertNotFalse( strpos( $field['21.Extended List'], $response ) );
		$this->assertNotFalse( strpos( $field['Extended List'], $response ) );
	}

	/**
	 * Check the poll field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_poll_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'Poll Dropdown - First Choice';
		$this->assertEquals( $response, $field[22] );
		$this->assertEquals( $response, $field['22.Poll Field - Drop Down_name'] );

		$response = 'Poll Radio - Second Choice';
		$this->assertEquals( $response, $field[23] );
		$this->assertEquals( $response, $field['23.Poll Field - Radio Buttons_name'] );

		$this->assertTrue( is_array( $field[41][0] ) );
		$this->assertTrue( in_array( 'Poll Check First Choice', $field[41][0], true ) );
		$this->assertTrue( in_array( 'Poll Check Second Choice', $field[41][0], true ) );
		$this->assertTrue( in_array( 'Poll Check Third Choice', $field[41][0], true ) );

		$this->assertTrue( is_array( $field['41.Poll Field - Checkboxes'][0] ) );
		$this->assertTrue( in_array( 'Poll Check First Choice', $field['41.Poll Field - Checkboxes'][0], true ) );
		$this->assertTrue( in_array( 'Poll Check Second Choice', $field['41.Poll Field - Checkboxes'][0], true ) );
		$this->assertTrue( in_array( 'Poll Check Third Choice', $field['41.Poll Field - Checkboxes'][0], true ) );

		$this->assertTrue( is_array( $field['Poll Field - Checkboxes'][0] ) );
		$this->assertTrue( in_array( 'Poll Check First Choice', $field['Poll Field - Checkboxes'][0], true ) );
		$this->assertTrue( in_array( 'Poll Check Second Choice', $field['Poll Field - Checkboxes'][0], true ) );
		$this->assertTrue( in_array( 'Poll Check Third Choice', $field['Poll Field - Checkboxes'][0], true ) );
	}

	/**
	 * Check the quiz field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_quiz_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 */
		$response = 'Quiz Dropdown - Second Choice';

		$this->assertEquals( $response, $field[24]['text'] );
		$this->assertEquals( $response, $field['24.Quiz Dropdown_name']['text'] );

		$this->assertArrayHasKey( 'text', $field[24] );
		$this->assertArrayHasKey( 'text', $field['24.Quiz Dropdown_name'] );
	}

	/**
	 * Check the survey field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_survey_basic_field() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 * Radio button first
		 */

		$response = 'Survay Radio - First Choice';
		$this->assertEquals( $response, $field[46] );
		$this->assertEquals( $response, $field['46.Radio Survey Field_name'] );

		/*
		 * Run checkbox survey test
		 */
		$this->assertEquals( 2, count( array_filter( $field[47][0] ) ) );
		$this->assertEquals( 2, count( array_filter( $field['47.Checkbox Survey Field'][0] ) ) );
		$this->assertEquals( 2, count( array_filter( $field['Checkbox Survey Field'][0] ) ) );

		$this->assertEquals( 'Check - First Choice', $field[47][0]['47.1'] );
		$this->assertEquals( 'Check - Second Choice', $field[47][0]['47.2'] );

		$this->assertEquals( 'Check - First Choice', $field['47.Checkbox Survey Field'][0]['47.1'] );
		$this->assertEquals( 'Check - Second Choice', $field['47.Checkbox Survey Field'][0]['47.2'] );

		$this->assertEquals( 'Check - First Choice', $field['Checkbox Survey Field'][0]['47.1'] );
		$this->assertEquals( 'Check - Second Choice', $field['Checkbox Survey Field'][0]['47.2'] );

		/*
		 * Run single line survey
		 */
		$response = 'Survey Field Single Line Response';
		$this->assertEquals( $response, $field[48] );
		$this->assertEquals( $response, $field['48.Single Line Survey Field'] );
		$this->assertEquals( $response, $field['Single Line Survey Field'] );

		/*
		 * Run paragraph test
		 */
		$response = 'Paragraph survey field response...';
		$this->assertEquals( $response, $field[49] );
		$this->assertEquals( $response, $field['49.Paragraph Survey Field'] );
		$this->assertEquals( $response, $field['Paragraph Survey Field'] );

		/*
		 * Run Dropdown Test
		 */
		$response = 'DropDown - Second Choice';
		$this->assertEquals( $response, $field[50] );
		$this->assertEquals( $response, $field['50.DropDown Survey Field_name'] );
	}

	/**
	 * Check the post field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_post_fields() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 * Post Title
		 */
		$response = 'My Post Title';
		$this->assertEquals( $response, $field[28] );
		$this->assertEquals( $response, $field['28.Post Title'] );
		$this->assertEquals( $response, $field['Post Title'] );

		/*
		 * Post Excerpt
		 */
		$response = 'My Post Excerpt';
		$this->assertEquals( $response, $field[29] );
		$this->assertEquals( $response, $field['29.Post Excerpt'] );
		$this->assertEquals( $response, $field['Post Excerpt'] );

		/*
		 * Post Tags
		 */
		$response = 'tag1, tag2, tag3';
		$this->assertEquals( $response, $field[30] );
		$this->assertEquals( $response, $field['30.Post Tags'] );
		$this->assertEquals( $response, $field['Post Tags'] );

		/*
		 * Post Category
		 */
		$response = '30';
		$this->assertEquals( $response, $field[31] );
		$this->assertEquals( $response, $field['31.Post Category'] );
		$this->assertEquals( $response, $field['Post Category'] );

		$response = 'Test Category 2';
		$this->assertEquals( $response, $field['31.Post Category_name'] );
		$this->assertEquals( $response, $field['31_name'] );

		/*
		 * Post Image
		 */
		$this->assertCount( 6, $field[32] );
		$this->assertCount( 6, $field['32.Post Image'] );
		$this->assertCount( 6, $field['Post Image'] );

		$title   = 'Post Image Title';
		$caption = 'Post Image caption';
		$desc    = 'Post Image Description';

		$keys = [ '32', '32.Post Image', 'Post Image' ];

		foreach ( $keys as $key ) {
			$this->assertStringStartsWith( 'http://',$field[ $key ]['url'] );
			$this->assertStringStartsWith( 'http://', $field[ $key ]['secured_url'] );
			$this->assertStringContainsString( '?gf-download=', $field[ $key ]['secured_url'] );
			$this->assertArrayHasKey( 'path', $field[ $key ] );
			$this->assertEquals( $title, $field[ $key ]['title'] );
			$this->assertEquals( $caption, $field[ $key ]['caption'] );
			$this->assertEquals( $desc, $field[ $key ]['description'] );
		}

		/*
		 * Post Custom Field
		 */
		$response = 'post_custom_field';
		$this->assertEquals( $response, $field[33] );
		$this->assertEquals( $response, $field['33.Post Custom Field'] );
	}

	/**
	 * Check the basic product field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_basic_product_fields() {
		$field = $this->form_data['field'];

		/*
		 * Run our tests...
		 * Basic Product Drop down
		 */
		$response = 'DD - Second Choice ($10.00)';
		$this->assertEquals( $response, $field[35] );
		$this->assertEquals( $response, $field['35.Product Name - Drop Down'] );
		$this->assertEquals( $response, $field['Product Name - Drop Down'] );
		$this->assertEquals( $response, $field['35.Product Name - Drop Down_name'] );
		$this->assertEquals( $response, $field['Product Name - Drop Down_name'] );
		$this->assertEquals( $response, $field['35_name'] );

		/*
		 * Product Radio Button
		 */
		$response = 'Radio - Second Choice ($10.00)';
		$this->assertEquals( $response, $field[51] );
		$this->assertEquals( $response, $field['51.Product Name - Radio Buttons'] );
		$this->assertEquals( $response, $field['Product Name - Radio Buttons'] );
		$this->assertEquals( $response, $field['51.Product Name - Radio Buttons_name'] );
		$this->assertEquals( $response, $field['51_name'] );
		$this->assertEquals( $response, $field['Product Name - Radio Buttons_name'] );

		/*
		 * Product Option Single
		 */
		$response = 'Option 2';
		$this->assertEquals( $response, $field[37] );
		$this->assertEquals( $response, $field['37.Product Options for Basic Product'] );
		$this->assertEquals( $response, $field['Product Options for Basic Product'] );
		$this->assertEquals( $response, $field['37.Product Options for Basic Product_name'] );
		$this->assertEquals( $response, $field['37_name'] );
		$this->assertEquals( $response, $field['Product Options for Basic Product_name'] );

		$response = '30';

		$this->assertEquals( $response, $field['37.Product Options for Basic Product_value'] );
		$this->assertEquals( $response, $field['37_value'] );
		$this->assertEquals( $response, $field['Product Options for Basic Product_value'] );

		/*
		 * Product Shipping Basic
		 */
		$response = 'Regular ($30.00)';
		$this->assertEquals( $response, $field[39] );
		$this->assertEquals( $response, $field['39.Shipping'] );
		$this->assertEquals( $response, $field['Shipping'] );
		$this->assertEquals( $response, $field['39.Shipping_name'] );
		$this->assertEquals( $response, $field['39_name'] );
		$this->assertEquals( $response, $field['Shipping_name'] );

		/*
		 * Quantity
		 */
		$response = '6';
		$this->assertEquals( $response, $field[36] );
		$this->assertEquals( $response, $field['36.Quantity Field for Hidden Price'] );
		$this->assertEquals( $response, $field['Quantity Field for Hidden Price'] );
		$this->assertEquals( $response, $field['36.Quantity Field for Hidden Price_name'] );
		$this->assertEquals( $response, $field['36_name'] );
		$this->assertEquals( $response, $field['Quantity Field for Hidden Price_name'] );
	}

	/**
	 * Check the HTML description outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_html_block() {
		$data = $this->form_data;

		/*
		 * Run our tests...
		 */
		$response = 'This is a HTML block';

		$this->assertEquals( $response, trim( $data['html'][0] ) );
		$this->assertEquals( $response, trim( $data['html_id'][9] ) );

	}

	/**
	 * Check the list field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_list_field_block() {
		$lists = $this->form_data['list'];

		/*
		 * Run our tests...
		 */
		$this->assertEquals( 2, count( $lists ) );
		$this->assertEquals( 3, count( $lists[20] ) );
		$this->assertEquals( 2, count( $lists[21] ) );
		$this->assertEquals( 3, count( $lists[21][0] ) );
		$this->assertEquals( 3, count( $lists[21][1] ) );

		/*
		 * Check the basic list content
		 */
		$this->assertEquals( 'List Item Response 1', $lists[20][0] );
		$this->assertEquals( 'List Item Response 2', $lists[20][1] );
		$this->assertEquals( 'List Item Response 3', $lists[20][2] );

		/*
		 * Check the multirow list content
		 */
		$this->assertEquals( 'List Response Col 1', $lists[21][0]['Column 1'] );
		$this->assertEquals( 'List Response Col 2', $lists[21][0]['Column 2'] );
		$this->assertEquals( 'List Response Col 3', $lists[21][0]['Column 3'] );

		$this->assertEquals( 'List Response #2 Col 1', $lists[21][1]['Column 1'] );
		$this->assertEquals( 'List Response #2 Col 2', $lists[21][1]['Column 2'] );
		$this->assertEquals( 'List Response #2 Col 3', $lists[21][1]['Column 3'] );
	}

	/**
	 * Check the signature field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_signature_blocks() {
		$data = $this->form_data;

		$response = '<img src="' . ABSPATH . 'wp-content/uploads/gravity_forms/signatures/54bdac4ed24af5.01502579.png" alt="Signature" width="75" />';

		/*
		 * Standard Signature Array
		 */
		$this->assertEquals( $response, $data['signature_details_id'][25]['img'] );
		$this->assertNotFalse( strpos( $data['signature_details_id'][25]['path'], ABSPATH ) );
		$this->assertEquals( 'http://', substr( $data['signature_details_id'][25]['url'], 0, 7 ) );
		$this->assertEquals( 75, $data['signature_details_id'][25]['width'] );
		$this->assertEquals( 45, $data['signature_details_id'][25]['height'] );

		/*
		 * Old Signature that doesn't index by ID
		 * Deprecated
		 */
		$this->assertEquals( $response, $data['signature_details'][0]['img'] );
		$this->assertNotFalse( strpos( $data['signature_details'][0]['path'], ABSPATH ) );
		$this->assertEquals( 'http://', substr( $data['signature_details'][0]['url'], 0, 7 ) );
		$this->assertEquals( 75, $data['signature_details'][0]['width'] );
		$this->assertEquals( 45, $data['signature_details'][0]['height'] );

		/*
		 * Basic Signature
		 * Deprecated
		 */
		$this->assertEquals( $response, $data['signature'][0] );
	}

	/**
	 * Check the survey likert field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_survey_likert_fields() {
		$likert = $this->form_data['survey']['likert'];

		/*
		 * Single-row Likert
		 */
		$this->assertArrayHasKey( 'col', $likert[26] );
		$this->assertArrayHasKey( 'row', $likert[26] );

		$this->assertEquals( 5, count( $likert[26]['col'] ) );
		$this->assertEquals( 5, count( $likert[26]['row'] ) );

		$this->assertArrayHasKey( 'Strongly disagree', $likert[26]['row'] );
		$this->assertArrayHasKey( 'Disagree', $likert[26]['row'] );
		$this->assertArrayHasKey( 'Neutral', $likert[26]['row'] );
		$this->assertArrayHasKey( 'Agree', $likert[26]['row'] );
		$this->assertArrayHasKey( 'Strongly agree', $likert[26]['row'] );

		$this->assertEquals( 'selected', $likert[26]['row']['Strongly disagree'] );

		/*
		 * Multi-Row Likert
		 */
		$this->assertArrayHasKey( 'col', $likert[27] );
		$this->assertArrayHasKey( 'rows', $likert[27] );
		$this->assertArrayNotHasKey( 'row', $likert[27] );

		$this->assertEquals( 5, count( $likert[27]['col'] ) );
		$this->assertEquals( 5, count( $likert[27]['rows'] ) );

		$this->assertArrayHasKey( 'First row', $likert[27]['rows'] );
		$this->assertArrayHasKey( 'Second row', $likert[27]['rows'] );
		$this->assertArrayHasKey( 'Third row', $likert[27]['rows'] );
		$this->assertArrayHasKey( 'Fourth row', $likert[27]['rows'] );
		$this->assertArrayHasKey( 'Fifth row', $likert[27]['rows'] );

		$col_names = [ 'Strongly disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly agree' ];
		foreach ( $likert[27]['rows'] as $cols ) {
			foreach ( $col_names as $name ) {
				$this->assertArrayHasKey( $name, $cols );
			}
		}

		$this->assertEquals( 'selected', $likert[27]['rows']['First row']['Neutral'] );
		$this->assertEquals( 'selected', $likert[27]['rows']['Second row']['Disagree'] );
		$this->assertEquals( 'selected', $likert[27]['rows']['Third row']['Agree'] );
		$this->assertEquals( 'selected', $likert[27]['rows']['Fourth row']['Strongly agree'] );
		$this->assertEquals( 'selected', $likert[27]['rows']['Fifth row']['Strongly agree'] );
	}

	/**
	 * Check the survey rank field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_survey_rank_fields() {
		$rank = $this->form_data['survey']['rank'];

		/*
		 * Test Rank field
		 */
		$this->assertEquals( 'Rank Fourth Choce', $rank[44][0] );
		$this->assertEquals( 'Rank Fifth Choice', $rank[44][1] );
		$this->assertEquals( 'Rank Second Choice', $rank[44][2] );
		$this->assertEquals( 'Rank First Choice', $rank[44][3] );
		$this->assertEquals( 'Rank Third Choice', $rank[44][4] );
	}

	/**
	 * Check the survey rating field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_survey_rating_fields() {
		$rating = $this->form_data['survey']['rating'];

		/*
		 * Test Rating Field
		 */
		$this->assertEquals( 'Pretty good', $rating[45][0] );
	}

	/**
	 * Check the $form_data['product'] array outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_product_data() {
		$products = $this->form_data['products'];

		/*
		 * Run first set of tests
		 */
		$this->assertEquals( 'Product Basic', $products[34]['name'] );
		$this->assertEquals( '$30.00', $products[34]['price'] );
		$this->assertEquals( '30.00', $products[34]['price_unformatted'] );
		$this->assertEquals( '3', $products[34]['quantity'] );
		$this->assertEquals( '180', $products[34]['subtotal'] );
		$this->assertEquals( '$180.00', $products[34]['subtotal_formatted'] );

		$this->assertEquals( 'Product Options for Basic Product', $products[34]['options'][0]['field_label'] );
		$this->assertEquals( 'Option 2', $products[34]['options'][0]['option_name'] );
		$this->assertEquals( 'Product Options for Basic Product: Option 2', $products[34]['options'][0]['option_label'] );
		$this->assertEquals( '30', $products[34]['options'][0]['price'] );
		$this->assertEquals( '$30.00', $products[34]['options'][0]['price_formatted'] );

		/*
		 * Run second set of tests
		 */
		$this->assertEquals( 'DD - Second Choice', $products[35]['name'] );
		// $this->assertEquals('$10.00', $products[35]['price']);  /* this is currently incorrect */
		// $this->assertEquals('10.00', $products[35]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals( '1', $products[35]['quantity'] );
		$this->assertEquals( '10', $products[35]['subtotal'] );
		$this->assertEquals( '$10.00', $products[35]['subtotal_formatted'] );
		$this->assertEquals( 0, count( $products[35]['options'] ) );

		/*
		 * Run third set of tests
		 */
		$this->assertEquals( 'Radio - Second Choice', $products[51]['name'] );
		// $this->assertEquals('$10.00', $products[51]['price']);  /* this is currently incorrect */
		// $this->assertEquals('10.00', $products[51]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals( '1', $products[51]['quantity'] );
		$this->assertEquals( '10', $products[51]['subtotal'] );
		$this->assertEquals( '$10.00', $products[51]['subtotal_formatted'] );
		$this->assertEquals( 0, count( $products[51]['options'] ) );

		/*
		 * Run fourth set of tests
		 */
		$this->assertEquals( 'User Defined Price', $products[52]['name'] );
		$this->assertEquals( '$30.00', $products[52]['price'] );  /* this is currently incorrect */
		$this->assertEquals( '30.00', $products[52]['price_unformatted'] ); /* this is currently incorrect */
		$this->assertEquals( '1', $products[52]['quantity'] );
		$this->assertEquals( '30', $products[52]['subtotal'] );
		$this->assertEquals( '$30.00', $products[52]['subtotal_formatted'] );
		$this->assertEquals( 0, count( $products[52]['options'] ) );

		/*
		 * Run fifth set of tests
		 */
		$this->assertEquals( 'Hidden Price', $products[53]['name'] );
		$this->assertEquals( '$50.00', $products[53]['price'] );  /* this is currently incorrect */
		$this->assertEquals( '50.00', $products[53]['price_unformatted'] ); /* this is currently incorrect */
		$this->assertEquals( '6', $products[53]['quantity'] );
		$this->assertEquals( '300', $products[53]['subtotal'] );
		$this->assertEquals( '$300.00', $products[53]['subtotal_formatted'] );
		$this->assertEquals( 0, count( $products[53]['options'] ) );

		/*
		 * Run sixth set of tests
		 */
		$this->assertEquals( 'Calculation Price', $products[54]['name'] );
		$this->assertEquals( '$40.00', $products[54]['price'] );  /* this is currently incorrect */
		$this->assertEquals( '40.00', $products[54]['price_unformatted'] ); /* this is currently incorrect */
		$this->assertEquals( '5', $products[54]['quantity'] );
		$this->assertEquals( '300.25', $products[54]['subtotal'] );
		$this->assertEquals( '$300.25', $products[54]['subtotal_formatted'] );

		$this->assertEquals( 'Option for Calculation Price', $products[54]['options'][0]['field_label'] );
		$this->assertEquals( 'Cal - Option 1', $products[54]['options'][0]['option_name'] );
		$this->assertEquals( 'Option for Calculation Price: Cal - Option 1', $products[54]['options'][0]['option_label'] );
		$this->assertEquals( '7.95', $products[54]['options'][0]['price'] );
		$this->assertEquals( '$7.95', $products[54]['options'][0]['price_formatted'] );

		$this->assertEquals( 'Option for Calculation Price', $products[54]['options'][1]['field_label'] );
		$this->assertEquals( 'Cal - Option 2', $products[54]['options'][1]['option_name'] );
		$this->assertEquals( 'Option for Calculation Price: Cal - Option 2', $products[54]['options'][1]['option_label'] );
		$this->assertEquals( '12.1', $products[54]['options'][1]['price'] );
		$this->assertEquals( '$12.10', $products[54]['options'][1]['price_formatted'] );
	}

	/**
	 * Check the $form_data['products_totals'] outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_product_totals() {
		$totals = $this->form_data['products_totals'];

		$this->assertEquals( 830.25, $totals['subtotal'] );
		$this->assertEquals( 30, $totals['shipping'] );
		$this->assertEquals( 860.25, $totals['total'] );
		$this->assertEquals( '$30.00', $totals['shipping_formatted'] );
		$this->assertEquals( '$830.25', $totals['subtotal_formatted'] );
		$this->assertEquals( '$860.25', $totals['total_formatted'] );
	}

	/**
	 * Check the $form_data['quiz'] key exists
	 *
	 * @since 4.0
	 */
	public function test_quiz_exists() {
		$this->assertArrayHasKey( 'quiz', $this->form_data );
	}

	/**
	 * Check the $form_data['quiz']['config'] contains the correct information
	 *
	 * @since 4.0
	 */
	public function test_quiz_config() {
		$base = $this->form_data['quiz'];

		$this->assertArrayHasKey( 'config', $base );

		$data = $base['config'];

		$this->assertArrayHasKey( 'grading', $data );
		$this->assertArrayHasKey( 'passPercent', $data );
		$this->assertArrayHasKey( 'grades', $data );

		$this->assertEquals( 'letter', $data['grading'] );
		$this->assertEquals( '50', $data['passPercent'] );

		$this->assertEquals( 5, count( $data['grades'] ) );

		$this->assertEquals( 'A', $data['grades'][0]['text'] );
		$this->assertEquals( '90', $data['grades'][0]['value'] );

		$this->assertEquals( 'B', $data['grades'][1]['text'] );
		$this->assertEquals( '80', $data['grades'][1]['value'] );

		$this->assertEquals( 'C', $data['grades'][2]['text'] );
		$this->assertEquals( '70', $data['grades'][2]['value'] );

		$this->assertEquals( 'D', $data['grades'][3]['text'] );
		$this->assertEquals( '60', $data['grades'][3]['value'] );

		$this->assertEquals( 'E', $data['grades'][4]['text'] );
		$this->assertEquals( '0', $data['grades'][4]['value'] );
	}

	/**
	 * Check the $form_data['quiz']['results'] key
	 *
	 * @since 4.0
	 */
	public function test_quiz_results() {
		$base = $this->form_data['quiz'];

		$this->assertArrayHasKey( 'results', $base );

		$data = $base['results'];

		$this->assertArrayHasKey( 'score', $data );
		$this->assertArrayHasKey( 'percent', $data );
		$this->assertArrayHasKey( 'is_pass', $data );
		$this->assertArrayHasKey( 'grade', $data );
	}

	/**
	 * Check the $form_data['quiz']['global'] key
	 *
	 * @since 4.0
	 */
	public function test_quiz_globals() {
		$base = $this->form_data['quiz'];

		$this->assertArrayHasKey( 'global', $base );

		$data = $base['global'];

		$this->assertEquals( 7, $data['entry_count'] );
		$this->assertEquals( 17, $data['sum'] );
		$this->assertEquals( 43, $data['pass_rate'] );

		$this->assertEquals( 1, $data['score_frequencies'][0] );
		$this->assertEquals( 2, $data['score_frequencies'][1] );
		$this->assertEquals( 1, $data['score_frequencies'][2] );
		$this->assertEquals( 1, $data['score_frequencies'][3] );
		$this->assertEquals( 0, $data['score_frequencies'][4] );
		$this->assertEquals( 2, $data['score_frequencies'][5] );

		$this->assertEquals( 2, $data['grade_frequencies']['A'] );
		$this->assertEquals( 0, $data['grade_frequencies']['B'] );
		$this->assertEquals( 0, $data['grade_frequencies']['C'] );
		$this->assertEquals( 1, $data['grade_frequencies']['D'] );
		$this->assertEquals( 4, $data['grade_frequencies']['E'] );

		$field = $data['field_data'][24];

		$this->assertEquals( 3, $field['misc']['correct'] );
		$this->assertEquals( 'Quiz Dropdown', $field['misc']['label'] );
		$this->assertEquals( 'Quiz Dropdown - First Choice', $field['misc']['correct_option_name'][0] );
		$this->assertEquals( 3, $field['Quiz Dropdown - First Choice'] );
		$this->assertEquals( 1, $field['Quiz Dropdown - Second Choice'] );
		$this->assertEquals( 2, $field['Quiz Dropdown - Third Choice'] );

		$field = $data['field_data'][42];
		$this->assertEquals( 2, $field['misc']['correct'] );
		$this->assertEquals( 'Quiz Radio', $field['misc']['label'] );
		$this->assertEquals( 'Quiz Radio - Second Choice', $field['misc']['correct_option_name'][0] );
		$this->assertEquals( 4, $field['Quiz Radio - First Choice'] );
		$this->assertEquals( 2, $field['Quiz Radio - Second Choice'] );
		$this->assertEquals( 0, $field['Quiz Radio - Third Choice'] );

		$field = $data['field_data'][43];
		$this->assertEquals( 2, $field['misc']['correct'] );
		$this->assertEquals( 'Quiz Checkbox', $field['misc']['label'] );
		$this->assertEquals( 'Quiz Checkbox - Second Choice', $field['misc']['correct_option_name'][0] );
		$this->assertEquals( 'Quiz Checkbox - Third Choice', $field['misc']['correct_option_name'][1] );
		$this->assertEquals( 1, $field['Quiz Checkbox - First Choice'] );
		$this->assertEquals( 6, $field['Quiz Checkbox - Second Choice'] );
		$this->assertEquals( 3, $field['Quiz Checkbox - Third Choice'] );
	}

	/**
	 * Check the $form_data['survey'] key exists
	 *
	 * @since 4.0
	 */
	public function test_survey_key() {
		$this->assertArrayHasKey( 'survey', $this->form_data );
		$this->assertArrayHasKey( 'global', $this->form_data['survey'] );
		$this->assertArrayHasKey( 'likert', $this->form_data['survey'] );
		$this->assertArrayHasKey( 'rank', $this->form_data['survey'] );
		$this->assertArrayHasKey( 'rating', $this->form_data['survey'] );
	}

	/**
	 * Check the global survey data correct
	 *
	 * @since 4.0
	 */
	public function test_survey_global_data() {
		$data = $this->form_data['survey']['global'];

		$this->assertEquals( 7, $data['entry_count'] );

		/*
		 * Test individual likert survey field values
		 */
		$likert = $data['field_data'][26];

		$this->assertEquals( 1, $likert['Strongly disagree'] );
		$this->assertEquals( 2, $likert['Disagree'] );
		$this->assertEquals( 0, $likert['Neutral'] );
		$this->assertEquals( 0, $likert['Agree'] );
		$this->assertEquals( 1, $likert['Strongly agree'] );

		/*
		 * Test Multi Likert Survey Field Value
		 */
		$likert = $data['field_data'][27]['First row'];

		$this->assertEquals( 1, $likert['Strongly disagree'] );
		$this->assertEquals( 0, $likert['Disagree'] );
		$this->assertEquals( 2, $likert['Neutral'] );
		$this->assertEquals( 1, $likert['Agree'] );
		$this->assertEquals( 0, $likert['Strongly agree'] );

		$likert = $data['field_data'][27]['Second row'];

		$this->assertEquals( 1, $likert['Strongly disagree'] );
		$this->assertEquals( 1, $likert['Disagree'] );
		$this->assertEquals( 2, $likert['Neutral'] );
		$this->assertEquals( 0, $likert['Agree'] );
		$this->assertEquals( 0, $likert['Strongly agree'] );

		$likert = $data['field_data'][27]['Third row'];

		$this->assertEquals( 1, $likert['Strongly disagree'] );
		$this->assertEquals( 0, $likert['Disagree'] );
		$this->assertEquals( 1, $likert['Neutral'] );
		$this->assertEquals( 1, $likert['Agree'] );
		$this->assertEquals( 1, $likert['Strongly agree'] );

		/*
		 * Test survey ranking
		 */
		$ranking = $data['field_data'][44];

		$this->assertEquals( 28, $ranking['Rank First Choice'] );
		$this->assertEquals( 27, $ranking['Rank Second Choice'] );
		$this->assertEquals( 15, $ranking['Rank Third Choice'] );
		$this->assertEquals( 20, $ranking['Rank Fourth Choce'] );
		$this->assertEquals( 15, $ranking['Rank Fifth Choice'] );

		/*
		 * Test Rating
		 */
		$rating = $data['field_data'][45];

		$this->assertEquals( 0, $rating['Terrible'] );
		$this->assertEquals( 1, $rating['Not so great'] );
		$this->assertEquals( 0, $rating['Neutral'] );
		$this->assertEquals( 2, $rating['Pretty good'] );
		$this->assertEquals( 1, $rating['Excellent'] );

		/*
		 * Test Checkboxes
		 */
		$checkboxes = $data['field_data'][47];

		$this->assertEquals( 2, $checkboxes['Check - First Choice'] );
		$this->assertEquals( 4, $checkboxes['Check - Second Choice'] );
		$this->assertEquals( 3, $checkboxes['Check - Third Choice'] );
	}

	/**
	 * Check the global likert data correct
	 *
	 * @since 4.0
	 */
	public function test_survey_likert_data() {
		$data = $this->form_data['survey']['likert'];

		/*
		 * Test Basic Likert
		 */
		$likert = $data[26];

		$columns = [ 'Strongly disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly agree' ];

		foreach ( $likert['col'] as $col ) {
			$this->assertTrue( in_array( $col, $columns, true ) );
		}

		/* test row */
		foreach ( $columns as $col ) {
			$this->assertTrue( array_key_exists( $col, $likert['row'] ) );
		}

		/*
		 * Test Multirow likert
		 */
		$likert = $data[27];

		foreach ( $likert['col'] as $col ) {
			$this->assertTrue( in_array( $col, $columns, true ) );
		}

		/* test row */
		foreach ( $likert['rows'] as $row ) {
			foreach ( $columns as $col ) {
				$this->assertTrue( array_key_exists( $col, $row ) );
			}
		}
	}

	/**
	 * Check the global rank data correct
	 *
	 * @since 4.0
	 */
	public function test_survey_rank_data() {
		$data = $this->form_data['survey']['rank'];

		$rank = $data['44'];

		$this->assertEquals( 'Rank Fourth Choce', $rank[0] );
		$this->assertEquals( 'Rank Fifth Choice', $rank[1] );
		$this->assertEquals( 'Rank Second Choice', $rank[2] );
		$this->assertEquals( 'Rank First Choice', $rank[3] );
		$this->assertEquals( 'Rank Third Choice', $rank[4] );
	}

	/**
	 * Check the global rating data correct
	 *
	 * @since 4.0
	 */
	public function test_survey_rating_data() {
		$data = $this->form_data['survey']['rating'];

		$this->assertTrue( in_array( 'Pretty good', $data[45], true ) );
	}

	/**
	 * Check the poll data key
	 *
	 * @since 4.0
	 */
	public function test_poll_data() {
		$base = $this->form_data;

		$this->assertArrayHasKey( 'poll', $base );
		$this->assertArrayHasKey( 'global', $base['poll'] );

		$data = $base['poll']['global'];

		$this->assertEquals( 7, $data['entry_count'] );

		/*
		 * Test first poll field
		 */
		$field = $data['field_data'][22];

		$this->assertEquals( 'Poll Field - Drop Down', $field['misc']['label'] );
		$this->assertEquals( 3, $field['Poll Dropdown - First Choice'] );
		$this->assertEquals( 1, $field['Poll Dropdown - Second Choice'] );
		$this->assertEquals( 0, $field['Poll Dropdown - Third Choice'] );

		/*
		 * Test second poll field
		 */
		$field = $data['field_data'][23];

		$this->assertEquals( 'Poll Field - Radio Buttons', $field['misc']['label'] );
		$this->assertEquals( 1, $field['Poll Radio - First Choice'] );
		$this->assertEquals( 3, $field['Poll Radio - Second Choice'] );
		$this->assertEquals( 2, $field['Poll Radio - Third Choice'] );

		/*
		 * Test third poll field
		 */
		$field = $data['field_data'][41];

		$this->assertEquals( 'Poll Field - Checkboxes', $field['misc']['label'] );
		$this->assertEquals( 3, $field['Poll Check First Choice'] );
		$this->assertEquals( 4, $field['Poll Check Second Choice'] );
		$this->assertEquals( 3, $field['Poll Check Third Choice'] );
	}

	/**
	 * Ensure the empty fields in the $form_data array produce the expected results
	 *
	 * @since 4.0
	 */
	public function test_empty_fields() {
		$entry     = $this->entries[6];
		$form_data = GFPDFEntryDetail::lead_detail_grid_array( $this->form, $entry );

		$this->assertEquals( '', $form_data['field'][1] );
		$this->assertEquals( '', $form_data['field'][2] );
		$this->assertEquals( '', $form_data['field'][4] );
		$this->assertEquals( '', $form_data['field'][5] );
		$this->assertEquals( '', $form_data['field'][6] );
		$this->assertEquals( '', $form_data['field'][7] );
		$this->assertEquals( '', $form_data['field'][11]['prefix'] );
		$this->assertEquals( '', $form_data['field'][11]['first'] );
		$this->assertEquals( '', $form_data['field'][11]['middle'] );
		$this->assertEquals( '', $form_data['field'][11]['last'] );
		$this->assertEquals( '', $form_data['field'][11]['suffix'] );
		$this->assertEquals( '', $form_data['field'][12] );
		$this->assertEquals( '', $form_data['field'][13] );
		$this->assertEquals( '', $form_data['field'][14] );
		$this->assertEquals( '', $form_data['field'][15]['street'] );
		$this->assertEquals( '', $form_data['field'][15]['street2'] );
		$this->assertEquals( '', $form_data['field'][15]['city'] );
		$this->assertEquals( '', $form_data['field'][15]['state'] );
		$this->assertEquals( '', $form_data['field'][15]['zip'] );
		$this->assertEquals( '', $form_data['field'][15]['country'] );
		$this->assertEquals( '', $form_data['field'][16] );
		$this->assertEquals( '', $form_data['field'][17] );

		$this->assertTrue( is_array( $form_data['field'][18] ) );
		$this->assertEquals( 0, count( $form_data['field'][18] ) );

		$this->assertTrue( is_array( $form_data['field'][24] ) );
		$this->assertEquals( 0, count( $form_data['field'][24] ) );

		$this->assertTrue( is_array( $form_data['field'][42] ) );
		$this->assertEquals( 0, count( $form_data['field'][42] ) );

		$this->assertTrue( is_array( $form_data['field'][43] ) );
		$this->assertEquals( 0, count( $form_data['field'][43] ) );

		$this->assertTrue( is_array( $form_data['field'][78] ) );
		$this->assertEquals( 0, count( $form_data['field'][78] ) );

		$this->assertTrue( is_array( $form_data['field'][81] ) );
		$this->assertEquals( 0, count( $form_data['field'][81] ) );

		$this->assertEquals( '', $form_data['field'][22] );
		$this->assertEquals( '', $form_data['field'][23] );
		$this->assertEquals( '', $form_data['field'][41] );
		$this->assertEquals( '', $form_data['field'][46] );
		$this->assertEquals( '', $form_data['field'][47] );
		$this->assertEquals( '', $form_data['field'][48] );
		$this->assertEquals( '', $form_data['field'][49] );
		$this->assertEquals( '', $form_data['field'][28] );
		$this->assertEquals( '', $form_data['field'][29] );
		$this->assertEquals( '', $form_data['field'][30] );
		$this->assertEquals( '', $form_data['field'][32] );
		$this->assertEquals( '', $form_data['field'][33] );
		$this->assertEquals( '', $form_data['field'][34] );
		$this->assertEquals( '', $form_data['field'][51] );
		$this->assertEquals( '', $form_data['field'][52] );
		$this->assertEquals( '', $form_data['field'][53] );
		$this->assertEquals( '', $form_data['field'][54] );
		$this->assertEquals( '', $form_data['field'][36] );

		$this->assertTrue( is_array( $form_data['field'][38] ) );
		$this->assertEquals( 0, count( $form_data['field'][38] ) );

		$this->assertEquals( '', $form_data['list'][20] );
		$this->assertEquals( '', $form_data['list'][21] );
	}

	/**
	 * Ensure the Product data calculations are correct when using Euros (or similar comma/decimal switched currency)
	 */
	public function test_euro_product_data() {
		$json            = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/all-form-euro-product-entry.json' ) ), true );
		$json['form_id'] = $this->form['id'];
		$entry_id        = GFAPI::add_entry( $json );
		$entry           = GFAPI::get_entry( $entry_id );
		$form_data       = GFPDFEntryDetail::lead_detail_grid_array( $this->form['id'], $entry );
		$products        = $form_data['products'];
		$totals          = $form_data['products_totals'];

		$this->assertEquals( '30,00 &#8364;', $products[34]['price'] );
		$this->assertEquals( 30, $products[34]['price_unformatted'] );
		$this->assertEquals( '180,00 &#8364;', $products[34]['subtotal_formatted'] );
		$this->assertEquals( 180, $products[34]['subtotal'] );

		$this->assertEquals( '40,00 &#8364;', $products[54]['price'] );
		$this->assertEquals( 40, $products[54]['price_unformatted'] );
		$this->assertEquals( '300,25 &#8364;', $products[54]['subtotal_formatted'] );
		$this->assertEquals( 300.25, $products[54]['subtotal'] );

		$this->assertEquals( 7.95, $products[54]['options'][0]['price'] );
		$this->assertEquals( '7,95 &#8364;', $products[54]['options'][0]['price_formatted'] );

		$this->assertEquals( 830.25, $totals['subtotal'] );
		$this->assertEquals( '830,25 &#8364;', $totals['subtotal_formatted'] );
		$this->assertEquals( 30, $totals['shipping'] );
		$this->assertEquals( '30,00 &#8364;', $totals['shipping_formatted'] );
		$this->assertEquals( 860.25, $totals['total'] );
		$this->assertEquals( '860,25 &#8364;', $totals['total_formatted'] );
	}

	/**
	 * Test the Gravity Forms Consent field form data
	 */
	public function test_consent_field_data() {
		$form  = $GLOBALS['GFPDF_Test']->form['repeater-consent-form'];
		$entry =  $GLOBALS['GFPDF_Test']->entries['repeater-consent-form'][0];

		$form_id          = GFAPI::add_form( $form );
		$entry['form_id'] = $form_id;
		$entry_id         = GFAPI::add_entry( $entry );

		$form_data = GPDFAPI::get_form_data( $entry_id );

		$this->assertEquals( 1, $form_data['field'][19]['value'] );
		$this->assertEquals( 'I agree to the privacy policy.', $form_data['field'][19]['label'] );
		$this->assertEquals( "<p>This is the consent description text.</p>\n", $form_data['field'][19]['description'] );
	}

	/**
	 * Test the Gravity Forms Repeater field form data
	 */
	public function test_repeater_field_data() {
		$form  = $GLOBALS['GFPDF_Test']->form['repeater-consent-form'];
		$entry =  $GLOBALS['GFPDF_Test']->entries['repeater-consent-form'][0];

		$form_id          = GFAPI::add_form( $form );
		$entry['form_id'] = $form_id;
		$entry_id         = GFAPI::add_entry( $entry );

		$form_data = GPDFAPI::get_form_data( $entry_id );

		$this->assertEquals( 'Simon', $form_data['repeater'][999][0][15]['first'] );
		$this->assertEquals( 'Wiseman', $form_data['repeater'][999][0][15]['last'] );
		$this->assertEquals( 'simon@test.com', $form_data['repeater'][999][0][16] );

		$this->assertEquals( 'Builder', $form_data['repeater'][999][0][99][0][200] );
		$this->assertEquals( '5', $form_data['repeater'][999][0][99][0][201] );

		$this->assertEquals( 'www.test.com', $form_data['repeater'][999][0][99][0][88][0][202] );
		$this->assertEquals( 'www.test1.com', $form_data['repeater'][999][0][99][0][88][1][202] );
		$this->assertEquals( 'www.test2.com', $form_data['repeater'][999][0][99][0][88][2][202] );

		$this->assertEquals( 'Painter', $form_data['repeater'][999][0][99][1][200] );
		$this->assertEquals( '3', $form_data['repeater'][999][0][99][1][201] );

		$this->assertEquals( 'Geoff', $form_data['repeater'][999][1][15]['first'] );
		$this->assertEquals( 'Simpson', $form_data['repeater'][999][1][15]['last'] );
		$this->assertEquals( 'geoff@test.com', $form_data['repeater'][999][1][16] );

		$this->assertEquals( 'Bricklayer', $form_data['repeater'][999][1][99][0][200] );
		$this->assertEquals( '10', $form_data['repeater'][999][1][99][0][201] );

		$this->assertEquals( 'www.test.com', $form_data['repeater'][999][1][99][0][88][0][202] );
		$this->assertEquals( 'www.test2.com', $form_data['repeater'][999][1][99][0][88][1][202] );
	}

	/**
	 * Test if the section title shows correctly for a repeater field
	 *
	 * @since 6.4
	 */
	public function test_repeater_maybe_show_section_title() {
		$form  = $GLOBALS['GFPDF_Test']->form['repeater-empty-form'];
		$entry = $GLOBALS['GFPDF_Test']->entries['repeater-empty-form'][0];

		/** @var \GF_Field_Repeater $repeater_field */
		$repeater_field = new \GF_Field_Repeater( $form['fields'][1]['fields'][2]['fields'][2]['fields'][0] );
		$repeater       = new Field_Repeater( $repeater_field, $entry, GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );

		/* Overide $values to customize tests. */
		$this->assertNotTrue( $repeater->maybe_show_section_title( false, $repeater->field, [ '', null ] ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( true, $repeater->field, [ '', '', null ] ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( true, $repeater->field, null ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( false, $repeater->field, null ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( false, $repeater->field, [ false, null ] ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( false, $repeater->field, false ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( true, $repeater->field, false ) );


		$repeater = new Field_Repeater( $repeater_field, $entry, GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );
		$this->assertTrue( $repeater->maybe_show_section_title( false, $repeater->field, [ 'test.url', 'test2.url', '' ] ) );
		$this->assertTrue( $repeater->maybe_show_section_title( false, $repeater->field, [ 'test.url', '' ] ) );
		$this->assertTrue( $repeater->maybe_show_section_title( false, $repeater->field, [ 'test.url', '', 'test2.url' ] ) );
		$this->assertTrue( $repeater->maybe_show_section_title( false, $repeater->field, [ null, null, '', false, 'test.url' ] ) );
		$this->assertTrue( $repeater->maybe_show_section_title( false, $repeater->field, true ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( true, $repeater->field, [ 'test.url', 'test2.url' ] ) );

	}
}
