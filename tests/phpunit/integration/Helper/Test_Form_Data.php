<?php

declare( strict_types=1 );

namespace GFPDF\Helper;
use GFAPI;
use GFPDF\Helper\Fields\Field_Repeater;
use GFPDFEntryDetail;
use GPDFAPI;
use GFPDF\Tests\Integration\TestCase;

/**
 * Test our custom template $form_data array
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the Gravity Forms functionality we rely on in Gravity PDF
 *
 * @since 4.0
 * @group form-data
 */
class Test_Form_Data extends TestCase {

	/** Form ID created in set_up_before_class for the test_field_number_currency test. Defaulted so tear_down_after_class is safe on early failure. */
	private static int $number_fields_form_id = 0;

	/** Per-test entry IDs that need cleanup (one-off entries created during tests). */
	private array $created_entry_ids = [];

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures(
			[ 'all-form-fields', 'repeater-consent-form', 'repeater-empty-form' ],
			[ 'all-form-fields', 'repeater-consent-form', 'repeater-empty-form' ]
		);

		// The all-form-fields entry JSON hardcodes upload URLs against form_id=1's
		// upload directory (gravity_forms/1-<wp_hash(1)>/…). Rewrite each entry's
		// file/post-image fields to point at the per-class form's upload dir so
		// test_upload_field and test_post_fields resolve secured URLs correctly.
		static::rewrite_upload_paths_for_all_form_fields();

		/* number-fields is only used by test_field_number_currency; one shared form is fine. */
		$number_fields_json          = json_decode(
			trim( file_get_contents( PDF_PLUGIN_DIR . '/tools/phpunit/data/forms/number-fields.json' ) ),
			true
		);
		self::$number_fields_form_id = ( new \GF_UnitTest_Factory() )->form->create( [], $number_fields_json );
	}

	public static function tear_down_after_class(): void {
		if ( self::$number_fields_form_id ) {
			\GFAPI::delete_form( self::$number_fields_form_id );
		}

		parent::tear_down_after_class();
	}

	public function tear_down(): void {
		foreach ( $this->created_entry_ids as $id ) {
			\GFAPI::delete_entry( $id );
		}
		$this->created_entry_ids = [];

		parent::tear_down();
	}

	private static function rewrite_upload_paths_for_all_form_fields() {
		$form_id = self::$fixture_caches[ static::class ]['forms']['all-form-fields']['id'];
		$slug    = '1-' . wp_hash( 1 );
		$replace = $form_id . '-' . wp_hash( $form_id );

		// Multi-file fields store a JSON-encoded array of URLs (with escaped slashes),
		// so str_replace needs to match the bare slug — that covers both `gravity_forms/<slug>`
		// and `gravity_forms\/<slug>` without coupling to either escape form.
		foreach ( self::$fixture_caches[ static::class ]['entries']['all-form-fields'] as $i => $entry ) {
			$updated = false;
			foreach ( $entry as $field_id => $value ) {
				if ( is_string( $value ) && strpos( $value, $slug ) !== false ) {
					$entry[ $field_id ] = str_replace( $slug, $replace, $value );
					$updated            = true;
				}
			}
			if ( $updated ) {
				\GFAPI::update_entry( $entry );
				self::$fixture_caches[ static::class ]['entries']['all-form-fields'][ $i ] = $entry;
			}
		}
	}
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
	public function set_up(): void {
		parent::set_up();

		$this->setup_stubs();
	}

	/**
	 * Create our testing data
	 *
	 * @since 4.0
	 */
	private function setup_stubs() {
		$this->form      = $this->form( 'all-form-fields' );
		$this->entries   = $this->entries( 'all-form-fields' );
		$this->form_data = \GPDFAPI::get_form_data( $this->entries[0]['id'] );
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
		$this->assertSame( $this->form( 'all-form-fields' )['title'], $data['form_title'] );
		$this->assertSame( 'This is the form description...', $data['form_description'] );
		$this->assertArrayHasKey( 'pages', $data );

		$date_dmy = '20/1/2015';
		$date_mdy = '1/20/2015';

		$this->assertSame( $date_dmy, $data['date_created'] );
		$this->assertSame( $date_mdy, $data['date_created_usa'] );
	}

	/**
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

		$this->assertSame( '124.183.82.7', $data['misc']['ip'] );
		$this->assertSame( 'active', $data['misc']['status'] );
		$this->assertSame( '1', $data['misc']['created_by'] );
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
		$this->assertSame( 'This is the multi select box description', $data['field_descriptions'][4] );
		$this->assertSame( 'Name Description', $data['field_descriptions'][11] );
	}

	/**
	 * Check the $form_data['field'] key exists
	 *
	 * @since 4.0
	 */
	public function test_field() {
		$data = $this->form_data;

		$this->assertArrayHasKey( 'field', $data );
		$this->assertIsArray( $data['field'] );
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
		$this->assertSame( $response, $field[1] );
		$this->assertSame( $response, $field['1.Single Line Text'] );
		$this->assertSame( $response, $field['Single Line Text'] );
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
		$this->assertSame( $response, $field[2] );
		$this->assertSame( $response, $field['2.Paragraph Text'] );
		$this->assertSame( $response, $field['Paragraph Text'] );
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
		$this->assertSame( $response, $field[3] );
		$this->assertSame( $response, $field['3.Drop Down'] );
		$this->assertSame( $response, $field['Drop Down'] );

		$response = 'Option 3';
		$this->assertSame( $response, $field['3_name'] );
		$this->assertSame( $response, $field['3.Drop Down_name'] );
		$this->assertSame( $response, $field['Drop Down_name'] );
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
		$this->assertContains( $response, $field[4] );
		$this->assertContains( $response, $field['4.Multi Select Box'] );
		$this->assertContains( $response, $field['Multi Select Box'] );

		$response = 'Multi Select Second Choice';
		$this->assertContains( $response, $field['4_name'] );
		$this->assertContains( $response, $field['4.Multi Select Box_name'] );
		$this->assertContains( $response, $field['Multi Select Box_name'] );

		$this->assertCount( 2, $field[4] );
		$this->assertCount( 2, $field['4_name'] );
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
		$this->assertSame( $response, $field[5] );
		$this->assertSame( $response, $field['5.Number'] );
		$this->assertSame( $response, $field['Number'] );
	}

	/**
	 * Test that number fields will use the local currency set in the entry
	 */
	public function test_field_number_currency() {
		/* Form is shared across tests (set_up_before_class); only the entries are per-test. */
		$form_id = self::$number_fields_form_id;

		$entry_id                  = $this->gf_factory()->entry->create([
				'form_id'  => $form_id,
				'currency' => 'EUR',
				'1'        => 1000.10,
				'2'        => 2000.10,
				'3'        => 3000.10,
			]);
		$this->created_entry_ids[] = $entry_id;

		$form_data = GPDFAPI::get_form_data( $entry_id );

		$this->assertSame( '1000.1', $form_data['field'][1] );
		$this->assertSame( '2000,1', $form_data['field'][2] );
		$this->assertSame( '3.000,10 &#8364;', $form_data['field'][3] );

		$entry_id                  = $this->gf_factory()->entry->create([
				'form_id'  => $form_id,
				'currency' => 'AUD',
				'1'        => 1000.10,
				'2'        => 2000.10,
				'3'        => 3000.10,
			]);
		$this->created_entry_ids[] = $entry_id;

		$form_data = GPDFAPI::get_form_data( $entry_id );

		$this->assertSame( '1000.1', $form_data['field'][1] );
		$this->assertSame( '2000,1', $form_data['field'][2] );
		$this->assertSame( '$ 3,000.10', $form_data['field'][3] );
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
		$this->assertContains( $response, $field[6] );
		$this->assertContains( $response, $field['6.Checkbox'] );
		$this->assertContains( $response, $field['Checkbox'] );

		$response = 'Checkbox Choice 2 Text';
		$this->assertContains( $response, $field['6_name'] );
		$this->assertContains( $response, $field['6.Checkbox_name'] );
		$this->assertContains( $response, $field['Checkbox_name'] );

		$response = 'Checkbox Choice 3';
		$this->assertContains( $response, $field[6] );
		$this->assertContains( $response, $field['6.Checkbox'] );
		$this->assertContains( $response, $field['Checkbox'] );

		$response = 'Checkbox Choice 3 Text';
		$this->assertContains( $response, $field['6_name'] );
		$this->assertContains( $response, $field['6.Checkbox_name'] );
		$this->assertContains( $response, $field['Checkbox_name'] );

		$this->assertCount( 2, $field[6] );
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
		$this->assertSame( $response, $field[7] );
		$this->assertSame( $response, $field['7.Radio Button'] );
		$this->assertSame( $response, $field['Radio Button'] );

		$response = 'Radio Second Choice Name';
		$this->assertSame( $response, $field['7_name'] );
		$this->assertSame( $response, $field['7.Radio Button_name'] );
		$this->assertSame( $response, $field['Radio Button_name'] );
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
		$this->assertSame( $response, $field[8] );
		$this->assertSame( $response, $field['8.Hidden Field'] );
		$this->assertSame( $response, $field['Hidden Field'] );
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
		$this->assertSame( 'Mr.', $field[11]['prefix'] );
		$this->assertSame( 'Jake', $field[11]['first'] );
		$this->assertSame( 'Middle', $field[11]['middle'] );
		$this->assertSame( 'Jackson', $field[11]['last'] );
		$this->assertSame( 'MD', $field[11]['suffix'] );

		$this->assertSame( 'Mr.', $field['11.Name']['prefix'] );
		$this->assertSame( 'Jake', $field['11.Name']['first'] );
		$this->assertSame( 'Middle', $field['11.Name']['middle'] );
		$this->assertSame( 'Jackson', $field['11.Name']['last'] );
		$this->assertSame( 'MD', $field['11.Name']['suffix'] );

		$this->assertSame( 'Mr.', $field['Name']['prefix'] );
		$this->assertSame( 'Jake', $field['Name']['first'] );
		$this->assertSame( 'Middle', $field['Name']['middle'] );
		$this->assertSame( 'Jackson', $field['Name']['last'] );
		$this->assertSame( 'MD', $field['Name']['suffix'] );
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
		$this->assertSame( $response, $field[12] );
		$this->assertSame( $response, $field['12.Date'] );
		$this->assertSame( $response, $field['Date'] );
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

		$this->assertSame( $response, $field[13] );
		$this->assertSame( $response, $field['13.Time'] );
		$this->assertSame( $response, $field['Time'] );
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
		$this->assertSame( $response, $field[14] );
		$this->assertSame( $response, $field['14.Phone'] );
		$this->assertSame( $response, $field['Phone'] );
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
		$this->assertSame( '12 Address St', $field[15]['street'] );
		$this->assertSame( 'Line 2', $field[15]['street2'] );
		$this->assertSame( 'Cityville', $field[15]['city'] );
		$this->assertSame( 'Statesman', $field[15]['state'] );
		$this->assertSame( '5000', $field[15]['zip'] );
		$this->assertSame( 'Chad', $field[15]['country'] );

		$this->assertSame( '12 Address St', $field['15.Address']['street'] );
		$this->assertSame( 'Line 2', $field['15.Address']['street2'] );
		$this->assertSame( 'Cityville', $field['15.Address']['city'] );
		$this->assertSame( 'Statesman', $field['15.Address']['state'] );
		$this->assertSame( '5000', $field['15.Address']['zip'] );
		$this->assertSame( 'Chad', $field['15.Address']['country'] );

		$this->assertSame( '12 Address St', $field['Address']['street'] );
		$this->assertSame( 'Line 2', $field['Address']['street2'] );
		$this->assertSame( 'Cityville', $field['Address']['city'] );
		$this->assertSame( 'Statesman', $field['Address']['state'] );
		$this->assertSame( '5000', $field['Address']['zip'] );
		$this->assertSame( 'Chad', $field['Address']['country'] );
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
		$this->assertSame( $response, $field[16] );
		$this->assertSame( $response, $field['16.Website'] );
		$this->assertSame( $response, $field['Website'] );
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
		$this->assertSame( $response, $field[17] );
		$this->assertSame( $response, $field['17.Email'] );
		$this->assertSame( $response, $field['Email'] );
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
		$this->assertStringContainsString( $response , $field[20] );
		$this->assertStringContainsString( $response , $field['20.Basic List'] );
		$this->assertStringContainsString( $response , $field['Basic List'] );

		$response = '<table autosize="1"';
		$this->assertStringContainsString( $response , $field[21] );
		$this->assertStringContainsString( $response , $field['21.Extended List'] );
		$this->assertStringContainsString( $response , $field['Extended List'] );
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
		$this->assertSame( $response, $field[22] );
		$this->assertSame( $response, $field['22.Poll Field - Drop Down_name'] );

		$response = 'Poll Radio - Second Choice';
		$this->assertSame( $response, $field[23] );
		$this->assertSame( $response, $field['23.Poll Field - Radio Buttons_name'] );

		$this->assertIsArray( $field[41][0] );
		$this->assertContains( 'Poll Check First Choice', $field[41][0] );
		$this->assertContains( 'Poll Check Second Choice', $field[41][0] );
		$this->assertContains( 'Poll Check Third Choice', $field[41][0] );

		$this->assertIsArray( $field['41.Poll Field - Checkboxes'][0] );
		$this->assertContains( 'Poll Check First Choice', $field['41.Poll Field - Checkboxes'][0] );
		$this->assertContains( 'Poll Check Second Choice', $field['41.Poll Field - Checkboxes'][0] );
		$this->assertContains( 'Poll Check Third Choice', $field['41.Poll Field - Checkboxes'][0] );

		$this->assertIsArray( $field['Poll Field - Checkboxes'][0] );
		$this->assertContains( 'Poll Check First Choice', $field['Poll Field - Checkboxes'][0] );
		$this->assertContains( 'Poll Check Second Choice', $field['Poll Field - Checkboxes'][0] );
		$this->assertContains( 'Poll Check Third Choice', $field['Poll Field - Checkboxes'][0] );
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

		$this->assertSame( $response, $field[24]['text'] );
		$this->assertSame( $response, $field['24.Quiz Dropdown_name']['text'] );

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
		$this->assertSame( $response, $field[46] );
		$this->assertSame( $response, $field['46.Radio Survey Field_name'] );

		/*
		 * Run checkbox survey test
		 */
		$this->assertCount( 2, array_filter( $field[47][0] ) );
		$this->assertCount( 2, array_filter( $field['47.Checkbox Survey Field'][0] ) );
		$this->assertCount( 2, array_filter( $field['Checkbox Survey Field'][0] ) );

		$this->assertSame( 'Check - First Choice', $field[47][0]['47.1'] );
		$this->assertSame( 'Check - Second Choice', $field[47][0]['47.2'] );

		$this->assertSame( 'Check - First Choice', $field['47.Checkbox Survey Field'][0]['47.1'] );
		$this->assertSame( 'Check - Second Choice', $field['47.Checkbox Survey Field'][0]['47.2'] );

		$this->assertSame( 'Check - First Choice', $field['Checkbox Survey Field'][0]['47.1'] );
		$this->assertSame( 'Check - Second Choice', $field['Checkbox Survey Field'][0]['47.2'] );

		/*
		 * Run single line survey
		 */
		$response = 'Survey Field Single Line Response';
		$this->assertSame( $response, $field[48] );
		$this->assertSame( $response, $field['48.Single Line Survey Field'] );
		$this->assertSame( $response, $field['Single Line Survey Field'] );

		/*
		 * Run paragraph test
		 */
		$response = 'Paragraph survey field response...';
		$this->assertSame( $response, $field[49] );
		$this->assertSame( $response, $field['49.Paragraph Survey Field'] );
		$this->assertSame( $response, $field['Paragraph Survey Field'] );

		/*
		 * Run Dropdown Test
		 */
		$response = 'DropDown - Second Choice';
		$this->assertSame( $response, $field[50] );
		$this->assertSame( $response, $field['50.DropDown Survey Field_name'] );
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
		$this->assertSame( $response, $field[28] );
		$this->assertSame( $response, $field['28.Post Title'] );
		$this->assertSame( $response, $field['Post Title'] );

		/*
		 * Post Excerpt
		 */
		$response = 'My Post Excerpt';
		$this->assertSame( $response, $field[29] );
		$this->assertSame( $response, $field['29.Post Excerpt'] );
		$this->assertSame( $response, $field['Post Excerpt'] );

		/*
		 * Post Tags
		 */
		$response = 'tag1, tag2, tag3';
		$this->assertSame( $response, $field[30] );
		$this->assertSame( $response, $field['30.Post Tags'] );
		$this->assertSame( $response, $field['Post Tags'] );

		/*
		 * Post Category
		 */
		$response = '30';
		$this->assertSame( $response, $field[31] );
		$this->assertSame( $response, $field['31.Post Category'] );
		$this->assertSame( $response, $field['Post Category'] );

		$response = 'Test Category 2';
		$this->assertSame( $response, $field['31.Post Category_name'] );
		$this->assertSame( $response, $field['31_name'] );

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
			$this->assertSame( $title, $field[ $key ]['title'] );
			$this->assertSame( $caption, $field[ $key ]['caption'] );
			$this->assertSame( $desc, $field[ $key ]['description'] );
		}

		/*
		 * Post Custom Field
		 */
		$response = 'post_custom_field';
		$this->assertSame( $response, $field[33] );
		$this->assertSame( $response, $field['33.Post Custom Field'] );
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
		$this->assertSame( $response, $field[35] );
		$this->assertSame( $response, $field['35.Product Name - Drop Down'] );
		$this->assertSame( $response, $field['Product Name - Drop Down'] );
		$this->assertSame( $response, $field['35.Product Name - Drop Down_name'] );
		$this->assertSame( $response, $field['Product Name - Drop Down_name'] );
		$this->assertSame( $response, $field['35_name'] );

		/*
		 * Product Radio Button
		 */
		$response = 'Radio - Second Choice ($10.00)';
		$this->assertSame( $response, $field[51] );
		$this->assertSame( $response, $field['51.Product Name - Radio Buttons'] );
		$this->assertSame( $response, $field['Product Name - Radio Buttons'] );
		$this->assertSame( $response, $field['51.Product Name - Radio Buttons_name'] );
		$this->assertSame( $response, $field['51_name'] );
		$this->assertSame( $response, $field['Product Name - Radio Buttons_name'] );

		/*
		 * Product Option Single
		 */
		$response = 'Option 2';
		$this->assertSame( $response, $field[37] );
		$this->assertSame( $response, $field['37.Product Options for Basic Product'] );
		$this->assertSame( $response, $field['Product Options for Basic Product'] );
		$this->assertSame( $response, $field['37.Product Options for Basic Product_name'] );
		$this->assertSame( $response, $field['37_name'] );
		$this->assertSame( $response, $field['Product Options for Basic Product_name'] );

		$response = '30';

		$this->assertSame( $response, $field['37.Product Options for Basic Product_value'] );
		$this->assertSame( $response, $field['37_value'] );
		$this->assertSame( $response, $field['Product Options for Basic Product_value'] );

		/*
		 * Product Shipping Basic
		 */
		$response = 'Regular ($30.00)';
		$this->assertSame( $response, $field[39] );
		$this->assertSame( $response, $field['39.Shipping'] );
		$this->assertSame( $response, $field['Shipping'] );
		$this->assertSame( $response, $field['39.Shipping_name'] );
		$this->assertSame( $response, $field['39_name'] );
		$this->assertSame( $response, $field['Shipping_name'] );

		/*
		 * Quantity
		 */
		$response = '6';
		$this->assertSame( $response, $field[36] );
		$this->assertSame( $response, $field['36.Quantity Field for Hidden Price'] );
		$this->assertSame( $response, $field['Quantity Field for Hidden Price'] );
		$this->assertSame( $response, $field['36.Quantity Field for Hidden Price_name'] );
		$this->assertSame( $response, $field['36_name'] );
		$this->assertSame( $response, $field['Quantity Field for Hidden Price_name'] );
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

		$this->assertSame( $response, trim( $data['html'][0] ) );
		$this->assertSame( $response, trim( $data['html_id'][9] ) );

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
		$this->assertCount( 2, $lists );
		$this->assertCount( 3, $lists[20] );
		$this->assertCount( 2, $lists[21] );
		$this->assertCount( 3, $lists[21][0] );
		$this->assertCount( 3, $lists[21][1] );

		/*
		 * Check the basic list content
		 */
		$this->assertSame( 'List Item Response 1', $lists[20][0] );
		$this->assertSame( 'List Item Response 2', $lists[20][1] );
		$this->assertSame( 'List Item Response 3', $lists[20][2] );

		/*
		 * Check the multirow list content
		 */
		$this->assertSame( 'List Response Col 1', $lists[21][0]['Column 1'] );
		$this->assertSame( 'List Response Col 2', $lists[21][0]['Column 2'] );
		$this->assertSame( 'List Response Col 3', $lists[21][0]['Column 3'] );

		$this->assertSame( 'List Response #2 Col 1', $lists[21][1]['Column 1'] );
		$this->assertSame( 'List Response #2 Col 2', $lists[21][1]['Column 2'] );
		$this->assertSame( 'List Response #2 Col 3', $lists[21][1]['Column 3'] );
	}

	/**
	 * Check the signature field outputs the correct information
	 *
	 * @since 4.0
	 */
	public function test_signature_blocks() {
		$data = $this->form_data;

		$response = '<img src="http://example.org/wp-content/uploads/gravity_forms/signatures/54bdac4ed24af5.01502579.png" alt="Signature" width="75" />';

		/*
		 * Standard Signature Array
		 */
		$this->assertSame( $response, $data['signature_details_id'][25]['img'] );
		$this->assertStringContainsString( ABSPATH, $data['signature_details_id'][25]['path'] );
		$this->assertSame( 'http://', substr( $data['signature_details_id'][25]['url'], 0, 7 ) );
		$this->assertSame( 75, $data['signature_details_id'][25]['width'] );
		$this->assertSame( 45, $data['signature_details_id'][25]['height'] );

		/*
		 * Old Signature that doesn't index by ID
		 * Deprecated
		 */
		$this->assertSame( $response, $data['signature_details'][0]['img'] );
		$this->assertStringContainsString( ABSPATH, $data['signature_details'][0]['path'] );
		$this->assertSame( 'http://', substr( $data['signature_details'][0]['url'], 0, 7 ) );
		$this->assertSame( 75, $data['signature_details'][0]['width'] );
		$this->assertSame( 45, $data['signature_details'][0]['height'] );

		/*
		 * Basic Signature
		 * Deprecated
		 */
		$this->assertSame( $response, $data['signature'][0] );
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

		$this->assertCount( 5, $likert[26]['col'] );
		$this->assertCount( 5, $likert[26]['row'] );

		$this->assertArrayHasKey( 'Strongly disagree', $likert[26]['row'] );
		$this->assertArrayHasKey( 'Disagree', $likert[26]['row'] );
		$this->assertArrayHasKey( 'Neutral', $likert[26]['row'] );
		$this->assertArrayHasKey( 'Agree', $likert[26]['row'] );
		$this->assertArrayHasKey( 'Strongly agree', $likert[26]['row'] );

		$this->assertSame( 'selected', $likert[26]['row']['Strongly disagree'] );

		/*
		 * Multi-Row Likert
		 */
		$this->assertArrayHasKey( 'col', $likert[27] );
		$this->assertArrayHasKey( 'rows', $likert[27] );
		$this->assertArrayNotHasKey( 'row', $likert[27] );

		$this->assertCount( 5, $likert[27]['col'] );
		$this->assertCount( 5, $likert[27]['rows'] );

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

		$this->assertSame( 'selected', $likert[27]['rows']['First row']['Neutral'] );
		$this->assertSame( 'selected', $likert[27]['rows']['Second row']['Disagree'] );
		$this->assertSame( 'selected', $likert[27]['rows']['Third row']['Agree'] );
		$this->assertSame( 'selected', $likert[27]['rows']['Fourth row']['Strongly agree'] );
		$this->assertSame( 'selected', $likert[27]['rows']['Fifth row']['Strongly agree'] );
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
		$this->assertSame( 'Rank Fourth Choce', $rank[44][0] );
		$this->assertSame( 'Rank Fifth Choice', $rank[44][1] );
		$this->assertSame( 'Rank Second Choice', $rank[44][2] );
		$this->assertSame( 'Rank First Choice', $rank[44][3] );
		$this->assertSame( 'Rank Third Choice', $rank[44][4] );
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
		$this->assertSame( 'Pretty good', $rating[45][0] );
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
		$this->assertSame( 'Product Basic', $products[34]['name'] );
		$this->assertSame( '$30.00', $products[34]['price'] );
		$this->assertEquals( '30.00', $products[34]['price_unformatted'] );
		$this->assertSame( '3', $products[34]['quantity'] );
		$this->assertEquals( '180', $products[34]['subtotal'] );
		$this->assertSame( '$180.00', $products[34]['subtotal_formatted'] );

		$this->assertSame( 'Product Options for Basic Product', $products[34]['options'][0]['field_label'] );
		$this->assertSame( 'Option 2', $products[34]['options'][0]['option_name'] );
		$this->assertSame( 'Product Options for Basic Product: Option 2', $products[34]['options'][0]['option_label'] );
		$this->assertSame( '30', $products[34]['options'][0]['price'] );
		$this->assertSame( '$30.00', $products[34]['options'][0]['price_formatted'] );

		/*
		 * Run second set of tests
		 */
		$this->assertSame( 'DD - Second Choice', $products[35]['name'] );
		// $this->assertSame( '$10.00', $products[35]['price']);  /* this is currently incorrect */
		// $this->assertSame( '10.00', $products[35]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals( '1', $products[35]['quantity'] );
		$this->assertEquals( '10', $products[35]['subtotal'] );
		$this->assertSame( '$10.00', $products[35]['subtotal_formatted'] );
		$this->assertCount( 0, $products[35]['options'] );

		/*
		 * Run third set of tests
		 */
		$this->assertSame( 'Radio - Second Choice', $products[51]['name'] );
		// $this->assertSame( '$10.00', $products[51]['price']);  /* this is currently incorrect */
		// $this->assertSame( '10.00', $products[51]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals( '1', $products[51]['quantity'] );
		$this->assertEquals( '10', $products[51]['subtotal'] );
		$this->assertSame( '$10.00', $products[51]['subtotal_formatted'] );
		$this->assertCount( 0, $products[51]['options'] );

		/*
		 * Run fourth set of tests
		 */
		$this->assertSame( 'User Defined Price', $products[52]['name'] );
		$this->assertSame( '$30.00', $products[52]['price'] );  /* this is currently incorrect */
		$this->assertEquals( '30.00', $products[52]['price_unformatted'] ); /* this is currently incorrect */
		$this->assertEquals( '1', $products[52]['quantity'] );
		$this->assertEquals( '30', $products[52]['subtotal'] );
		$this->assertSame( '$30.00', $products[52]['subtotal_formatted'] );
		$this->assertCount( 0, $products[52]['options'] );

		/*
		 * Run fifth set of tests
		 */
		$this->assertSame( 'Hidden Price', $products[53]['name'] );
		$this->assertSame( '$50.00', $products[53]['price'] );  /* this is currently incorrect */
		$this->assertEquals( '50.00', $products[53]['price_unformatted'] ); /* this is currently incorrect */
		$this->assertEquals( '6', $products[53]['quantity'] );
		$this->assertEquals( '300', $products[53]['subtotal'] );
		$this->assertSame( '$300.00', $products[53]['subtotal_formatted'] );
		$this->assertCount( 0, $products[53]['options'] );

		/*
		 * Run sixth set of tests
		 */
		$this->assertSame( 'Calculation Price', $products[54]['name'] );
		$this->assertSame( '$40.00', $products[54]['price'] );  /* this is currently incorrect */
		$this->assertEquals( '40.00', $products[54]['price_unformatted'] ); /* this is currently incorrect */
		$this->assertEquals( '5', $products[54]['quantity'] );
		$this->assertEquals( '300.25', $products[54]['subtotal'] );
		$this->assertSame( '$300.25', $products[54]['subtotal_formatted'] );

		$this->assertSame( 'Option for Calculation Price', $products[54]['options'][0]['field_label'] );
		$this->assertSame( 'Cal - Option 1', $products[54]['options'][0]['option_name'] );
		$this->assertSame( 'Option for Calculation Price: Cal - Option 1', $products[54]['options'][0]['option_label'] );
		$this->assertEquals( '7.95', $products[54]['options'][0]['price'] );
		$this->assertSame( '$7.95', $products[54]['options'][0]['price_formatted'] );

		$this->assertSame( 'Option for Calculation Price', $products[54]['options'][1]['field_label'] );
		$this->assertSame( 'Cal - Option 2', $products[54]['options'][1]['option_name'] );
		$this->assertSame( 'Option for Calculation Price: Cal - Option 2', $products[54]['options'][1]['option_label'] );
		$this->assertSame( '12.1', $products[54]['options'][1]['price'] );
		$this->assertSame( '$12.10', $products[54]['options'][1]['price_formatted'] );
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
		$this->assertSame( '$30.00', $totals['shipping_formatted'] );
		$this->assertSame( '$830.25', $totals['subtotal_formatted'] );
		$this->assertSame( '$860.25', $totals['total_formatted'] );
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

		$this->assertSame( 'letter', $data['grading'] );
		$this->assertSame( '50', $data['passPercent'] );

		$this->assertCount( 5, $data['grades'] );

		$this->assertSame( 'A', $data['grades'][0]['text'] );
		$this->assertEquals( '90', $data['grades'][0]['value'] );

		$this->assertSame( 'B', $data['grades'][1]['text'] );
		$this->assertEquals( '80', $data['grades'][1]['value'] );

		$this->assertSame( 'C', $data['grades'][2]['text'] );
		$this->assertEquals( '70', $data['grades'][2]['value'] );

		$this->assertSame( 'D', $data['grades'][3]['text'] );
		$this->assertEquals( '60', $data['grades'][3]['value'] );

		$this->assertSame( 'E', $data['grades'][4]['text'] );
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
	 * Check the $form_data['quiz']['global'] post-processing in Model_PDF::get_quiz_overall_data().
	 *
	 * Vendor-only fields (sum, pass_rate, score_frequencies, grade_frequencies, misc.correct)
	 * are produced by GFQuiz::results_calculation() and are not stubbed, so they are not
	 * asserted here.
	 *
	 * @since 4.0
	 */
	public function test_quiz_globals() {
		$base = $this->form_data['quiz'];

		$this->assertArrayHasKey( 'global', $base );

		$data = $base['global'];

		$this->assertSame( 7, $data['entry_count'] );

		$field = $data['field_data'][24];
		$this->assertSame( 'Quiz Dropdown', $field['misc']['label'] );
		$this->assertSame( 'Quiz Dropdown - First Choice', $field['misc']['correct_option_name'][0] );
		$this->assertSame( 3, $field['Quiz Dropdown - First Choice'] );
		$this->assertSame( 1, $field['Quiz Dropdown - Second Choice'] );
		$this->assertSame( 2, $field['Quiz Dropdown - Third Choice'] );

		$field = $data['field_data'][42];
		$this->assertSame( 'Quiz Radio', $field['misc']['label'] );
		$this->assertSame( 'Quiz Radio - Second Choice', $field['misc']['correct_option_name'][0] );
		$this->assertSame( 4, $field['Quiz Radio - First Choice'] );
		$this->assertSame( 2, $field['Quiz Radio - Second Choice'] );
		$this->assertSame( 0, $field['Quiz Radio - Third Choice'] );

		$field = $data['field_data'][43];
		$this->assertSame( 'Quiz Checkbox', $field['misc']['label'] );
		$this->assertSame( 'Quiz Checkbox - Second Choice', $field['misc']['correct_option_name'][0] );
		$this->assertSame( 'Quiz Checkbox - Third Choice', $field['misc']['correct_option_name'][1] );
		$this->assertSame( 1, $field['Quiz Checkbox - First Choice'] );
		$this->assertSame( 6, $field['Quiz Checkbox - Second Choice'] );
		$this->assertSame( 3, $field['Quiz Checkbox - Third Choice'] );
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

		$this->assertSame( 7, $data['entry_count'] );

		/*
		 * Test individual likert survey field values
		 */
		$likert = $data['field_data'][26];

		$this->assertSame( 1, $likert['Strongly disagree'] );
		$this->assertSame( 2, $likert['Disagree'] );
		$this->assertSame( 0, $likert['Neutral'] );
		$this->assertSame( 0, $likert['Agree'] );
		$this->assertSame( 1, $likert['Strongly agree'] );

		/*
		 * Test Multi Likert Survey Field Value
		 */
		$likert = $data['field_data'][27]['First row'];

		$this->assertSame( 1, $likert['Strongly disagree'] );
		$this->assertSame( 0, $likert['Disagree'] );
		$this->assertSame( 2, $likert['Neutral'] );
		$this->assertSame( 1, $likert['Agree'] );
		$this->assertSame( 0, $likert['Strongly agree'] );

		$likert = $data['field_data'][27]['Second row'];

		$this->assertSame( 1, $likert['Strongly disagree'] );
		$this->assertSame( 1, $likert['Disagree'] );
		$this->assertSame( 2, $likert['Neutral'] );
		$this->assertSame( 0, $likert['Agree'] );
		$this->assertSame( 0, $likert['Strongly agree'] );

		$likert = $data['field_data'][27]['Third row'];

		$this->assertSame( 1, $likert['Strongly disagree'] );
		$this->assertSame( 0, $likert['Disagree'] );
		$this->assertSame( 1, $likert['Neutral'] );
		$this->assertSame( 1, $likert['Agree'] );
		$this->assertSame( 1, $likert['Strongly agree'] );

		/*
		 * Test survey ranking
		 */
		$ranking = $data['field_data'][44];

		$this->assertSame( 28, $ranking['Rank First Choice'] );
		$this->assertSame( 27, $ranking['Rank Second Choice'] );
		$this->assertSame( 15, $ranking['Rank Third Choice'] );
		$this->assertSame( 20, $ranking['Rank Fourth Choce'] );
		$this->assertSame( 15, $ranking['Rank Fifth Choice'] );

		/*
		 * Test Rating
		 */
		$rating = $data['field_data'][45];

		$this->assertSame( 0, $rating['Terrible'] );
		$this->assertSame( 1, $rating['Not so great'] );
		$this->assertSame( 0, $rating['Neutral'] );
		$this->assertSame( 2, $rating['Pretty good'] );
		$this->assertSame( 1, $rating['Excellent'] );

		/*
		 * Test Checkboxes
		 */
		$checkboxes = $data['field_data'][47];

		$this->assertSame( 2, $checkboxes['Check - First Choice'] );
		$this->assertSame( 4, $checkboxes['Check - Second Choice'] );
		$this->assertSame( 3, $checkboxes['Check - Third Choice'] );
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
			$this->assertContains( $col, $columns );
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
			$this->assertContains( $col, $columns );
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

		$this->assertSame( 'Rank Fourth Choce', $rank[0] );
		$this->assertSame( 'Rank Fifth Choice', $rank[1] );
		$this->assertSame( 'Rank Second Choice', $rank[2] );
		$this->assertSame( 'Rank First Choice', $rank[3] );
		$this->assertSame( 'Rank Third Choice', $rank[4] );
	}

	/**
	 * Check the global rating data correct
	 *
	 * @since 4.0
	 */
	public function test_survey_rating_data() {
		$data = $this->form_data['survey']['rating'];

		$this->assertContains( 'Pretty good', $data[45] );
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

		$this->assertSame( 7, $data['entry_count'] );

		/*
		 * Test first poll field
		 */
		$field = $data['field_data'][22];

		$this->assertSame( 'Poll Field - Drop Down', $field['misc']['label'] );
		$this->assertSame( 3, $field['Poll Dropdown - First Choice'] );
		$this->assertSame( 1, $field['Poll Dropdown - Second Choice'] );
		$this->assertSame( 0, $field['Poll Dropdown - Third Choice'] );

		/*
		 * Test second poll field
		 */
		$field = $data['field_data'][23];

		$this->assertSame( 'Poll Field - Radio Buttons', $field['misc']['label'] );
		$this->assertSame( 1, $field['Poll Radio - First Choice'] );
		$this->assertSame( 3, $field['Poll Radio - Second Choice'] );
		$this->assertSame( 2, $field['Poll Radio - Third Choice'] );

		/*
		 * Test third poll field
		 */
		$field = $data['field_data'][41];

		$this->assertSame( 'Poll Field - Checkboxes', $field['misc']['label'] );
		$this->assertSame( 3, $field['Poll Check First Choice'] );
		$this->assertSame( 4, $field['Poll Check Second Choice'] );
		$this->assertSame( 3, $field['Poll Check Third Choice'] );
	}

	/**
	 * Ensure the empty fields in the $form_data array produce the expected results
	 *
	 * @since 4.0
	 */
	public function test_empty_fields() {
		$entry     = $this->entries[6];
		$form_data = GFPDFEntryDetail::lead_detail_grid_array( $this->form, $entry );

		$this->assertSame( '', $form_data['field'][1] );
		$this->assertSame( '', $form_data['field'][2] );
		$this->assertSame( '', $form_data['field'][4] );
		$this->assertSame( '', $form_data['field'][5] );
		$this->assertSame( '', $form_data['field'][6] );
		$this->assertSame( '', $form_data['field'][7] );
		$this->assertSame( '', $form_data['field'][11]['prefix'] );
		$this->assertSame( '', $form_data['field'][11]['first'] );
		$this->assertSame( '', $form_data['field'][11]['middle'] );
		$this->assertSame( '', $form_data['field'][11]['last'] );
		$this->assertSame( '', $form_data['field'][11]['suffix'] );
		$this->assertSame( '', $form_data['field'][12] );
		$this->assertSame( '', $form_data['field'][13] );
		$this->assertSame( '', $form_data['field'][14] );
		$this->assertSame( '', $form_data['field'][15]['street'] );
		$this->assertSame( '', $form_data['field'][15]['street2'] );
		$this->assertSame( '', $form_data['field'][15]['city'] );
		$this->assertSame( '', $form_data['field'][15]['state'] );
		$this->assertSame( '', $form_data['field'][15]['zip'] );
		$this->assertSame( '', $form_data['field'][15]['country'] );
		$this->assertSame( '', $form_data['field'][16] );
		$this->assertSame( '', $form_data['field'][17] );

		$this->assertIsArray( $form_data['field'][18] );
		$this->assertCount( 0, $form_data['field'][18] );

		$this->assertIsArray( $form_data['field'][24] );
		$this->assertCount( 0, $form_data['field'][24] );

		$this->assertIsArray( $form_data['field'][42] );
		$this->assertCount( 0, $form_data['field'][42] );

		$this->assertIsArray( $form_data['field'][43] );
		$this->assertCount( 0, $form_data['field'][43] );

		$this->assertIsArray( $form_data['field'][78] );
		$this->assertCount( 0, $form_data['field'][78] );

		$this->assertIsArray( $form_data['field'][81] );
		$this->assertCount( 0, $form_data['field'][81] );

		$this->assertSame( '', $form_data['field'][22] );
		$this->assertSame( '', $form_data['field'][23] );
		$this->assertSame( '', $form_data['field'][41] );
		$this->assertSame( '', $form_data['field'][46] );
		$this->assertSame( '', $form_data['field'][47] );
		$this->assertSame( '', $form_data['field'][48] );
		$this->assertSame( '', $form_data['field'][49] );
		$this->assertSame( '', $form_data['field'][28] );
		$this->assertSame( '', $form_data['field'][29] );
		$this->assertSame( '', $form_data['field'][30] );
		$this->assertSame( '', $form_data['field'][32] );
		$this->assertSame( '', $form_data['field'][33] );
		$this->assertSame( '', $form_data['field'][34] );
		$this->assertSame( '', $form_data['field'][51] );
		$this->assertSame( '', $form_data['field'][52] );
		$this->assertSame( '', $form_data['field'][53] );
		$this->assertSame( '', $form_data['field'][54] );
		$this->assertSame( '', $form_data['field'][36] );

		$this->assertIsArray( $form_data['field'][38] );
		$this->assertCount( 0, $form_data['field'][38] );

		$this->assertSame( '', $form_data['list'][20] );
		$this->assertSame( '', $form_data['list'][21] );
	}

	/**
	 * Ensure the Product data calculations are correct when using Euros (or similar comma/decimal switched currency)
	 */
	public function test_euro_product_data() {
		$json                      = json_decode( trim( file_get_contents( PDF_PLUGIN_DIR . '/tools/phpunit/data/entries/all-form-euro-product-entry.json' ) ), true );
		$json['form_id']           = $this->form['id'];
		$entry_id                  = $this->gf_factory()->entry->create($json);
		$this->created_entry_ids[] = $entry_id;
		$entry                     = GFAPI::get_entry( $entry_id );
		$form_data       = GFPDFEntryDetail::lead_detail_grid_array( $this->form['id'], $entry );
		$products        = $form_data['products'];
		$totals          = $form_data['products_totals'];

		$this->assertSame( '30,00 &#8364;', $products[34]['price'] );
		$this->assertEquals( 30, $products[34]['price_unformatted'] );
		$this->assertSame( '180,00 &#8364;', $products[34]['subtotal_formatted'] );
		$this->assertEquals( 180, $products[34]['subtotal'] );

		$this->assertSame( '40,00 &#8364;', $products[54]['price'] );
		$this->assertEquals( 40, $products[54]['price_unformatted'] );
		$this->assertSame( '300,25 &#8364;', $products[54]['subtotal_formatted'] );
		$this->assertEquals( 300.25, $products[54]['subtotal'] );

		$this->assertEquals( 7.95, $products[54]['options'][0]['price'] );
		$this->assertSame( '7,95 &#8364;', $products[54]['options'][0]['price_formatted'] );

		$this->assertEquals( 830.25, $totals['subtotal'] );
		$this->assertSame( '830,25 &#8364;', $totals['subtotal_formatted'] );
		$this->assertEquals( 30, $totals['shipping'] );
		$this->assertSame( '30,00 &#8364;', $totals['shipping_formatted'] );
		$this->assertEquals( 860.25, $totals['total'] );
		$this->assertSame( '860,25 &#8364;', $totals['total_formatted'] );
	}

	/**
	 * Test the Gravity Forms Consent field form data
	 */
	public function test_consent_field_data() {
		/* Fixture entry is already in the DB from load_fixtures(); use its ID directly. */
		$entry_id  = $this->entry( 'repeater-consent-form' )['id'];
		$form_data = GPDFAPI::get_form_data( $entry_id );

		$this->assertEquals( 1, $form_data['field'][19]['value'] );
		$this->assertSame( 'I agree to the privacy policy.', $form_data['field'][19]['label'] );
		$this->assertSame( "<p>This is the consent description text.</p>\n", $form_data['field'][19]['description'] );
	}

	/**
	 * Test the Gravity Forms Repeater field form data
	 */
	public function test_repeater_field_data() {
		$entry_id  = $this->entry( 'repeater-consent-form' )['id'];
		$form_data = GPDFAPI::get_form_data( $entry_id );

		$this->assertSame( 'Simon', $form_data['repeater'][999][0][15]['first'] );
		$this->assertSame( 'Wiseman', $form_data['repeater'][999][0][15]['last'] );
		$this->assertSame( 'simon@test.com', $form_data['repeater'][999][0][16] );

		$this->assertSame( 'Builder', $form_data['repeater'][999][0][99][0][200] );
		$this->assertSame( '5', $form_data['repeater'][999][0][99][0][201] );

		$this->assertSame( 'www.test.com', $form_data['repeater'][999][0][99][0][88][0][202] );
		$this->assertSame( 'www.test1.com', $form_data['repeater'][999][0][99][0][88][1][202] );
		$this->assertSame( 'www.test2.com', $form_data['repeater'][999][0][99][0][88][2][202] );

		$this->assertSame( 'Painter', $form_data['repeater'][999][0][99][1][200] );
		$this->assertSame( '3', $form_data['repeater'][999][0][99][1][201] );

		$this->assertSame( 'Geoff', $form_data['repeater'][999][1][15]['first'] );
		$this->assertSame( 'Simpson', $form_data['repeater'][999][1][15]['last'] );
		$this->assertSame( 'geoff@test.com', $form_data['repeater'][999][1][16] );

		$this->assertSame( 'Bricklayer', $form_data['repeater'][999][1][99][0][200] );
		$this->assertSame( '10', $form_data['repeater'][999][1][99][0][201] );

		$this->assertSame( 'www.test.com', $form_data['repeater'][999][1][99][0][88][0][202] );
		$this->assertSame( 'www.test2.com', $form_data['repeater'][999][1][99][0][88][1][202] );
	}

	/**
	 * Test if the section title shows correctly for a repeater field
	 *
	 * @since 6.4
	 */
	public function test_repeater_maybe_show_section_title() {
		$form  = $this->form( 'repeater-empty-form' );
		$entry = $this->entry( 'repeater-empty-form' );

		/** @var \GF_Field_Repeater $repeater_field */
		$repeater_field = new \GF_Field_Repeater( $form['fields'][1]['fields'][2]['fields'][2]['fields'][0] );
		$repeater       = new Field_Repeater( $repeater_field, $entry, GPDFAPI::get_form_class(), GPDFAPI::get_misc_class() );

		/* Overide $values to customize tests. */
		$this->assertNotTrue( $repeater->maybe_show_section_title( false, $repeater->field, [ '', null ] ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( true, $repeater->field, [ '', '', null ] ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( true, $repeater->field, null ) );
		$this->assertNotTrue( $repeater->maybe_show_section_title( false, $repeater->field, null ) );
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
