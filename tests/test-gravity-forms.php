<?php 

/**
 * PDF Extended integrates directly with Gravity Forms 
 * Breakages can occur if the plugin starts returning results which 
 * differ from what we expected. 
 *
 * This class will test all the functions we use in the software to 
 * ensure we maintain compatibility.
 */
class Test_GravityForms extends WP_UnitTestCase {
	private $form_id = false;
	private $entries = array();

	public function setUp() {		
        
		/*
		 * Replace set up with cut down version 
		 * so we don't use 'temporary' tables in MySQL
		 */
		$this->cut_down_setup();

		/* initialise GF tables */
		GFForms::setup(true);			

		/* Load our plugin functions */
		GFPDF_InstallUpdater::check_filesystem_api();
		GFPDF_InstallUpdater::maybe_deploy();				

		/* create GF data */
		$this->create_form_and_entries();
	}

	public function cut_down_setup()
	{
        global $wpdb;

        /*
         * Do DB logic 
         */
        $wpdb->suppress_errors = false;
        $wpdb->show_errors = true;
        $wpdb->db_connect();
        $wpdb->query( 'SET autocommit = 0;' );
        $wpdb->query( 'START TRANSACTION;' );		
	}

	public function tearDown() {		
		parent::tearDown();		

        /*
         * Uninstall Gravity Forms
         */
        RGFormsModel::drop_tables();		
	}	

	public function create_form_and_entries() {
		$this->create_forms();
		$this->create_entries();
	}	

	public function create_forms()
	{
		$form = json_decode('{"title":"Simple Form Testing","description":"","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"id":1,"label":"Name","adminLabel":"","type":"name","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":1.3,"label":"First","name":""},{"id":1.6,"label":"Last","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":2,"label":"Address","adminLabel":"","type":"address","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":2.1,"label":"Street Address","name":""},{"id":2.2,"label":"Address Line 2","name":""},{"id":2.3,"label":"City","name":""},{"id":2.4,"label":"State \/ Province","name":""},{"id":2.5,"label":"ZIP \/ Postal Code","name":""},{"id":2.6,"label":"Country","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":3,"label":"Email","adminLabel":"","type":"email","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":4,"label":"Phone","adminLabel":"","type":"phone","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":5,"label":"Untitled","adminLabel":"","type":"select","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":6,"label":"Untitled","adminLabel":"","type":"multiselect","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":7,"label":"Untitled","adminLabel":"","type":"textarea","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"}],"id":47,"useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"notifications":{"5414ff2b70018":{"id":"5414ff2b70018","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}"},"5414ff5a5a28a":{"isActive":true,"id":"5414ff5a5a28a","name":"User Notification","event":"form_submission","to":"3","toType":"field","bcc":"","subject":"Email Notification","message":"User Notification","from":"{admin_email}","fromName":"","replyTo":"","routing":null,"conditionalLogic":null,"disableAutoformat":""}},"confirmations":{"5414ff2b752f0":{"id":"5414ff2b752f0","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"is_active":"1","date_created":"2014-09-14 02:36:27","is_trash":"0"}', true);

		$results = GFAPI::add_form($form);

		/* test the form was correctly added to the database */
		$this->assertInternalType('int', $results);		
		$this->form_id = $results;		
	}


	public function create_entries()
	{
		$entries = json_decode('[{"id":"453","form_id":"47","date_created":"2014-09-14 02:47:14","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"My","1.6":"Name","5":"First Choice","2.1":"","2.2":"","2.3":"","2.4":"","2.5":"","2.6":"","3":"","4":"","6":"","7":""},{"id":"452","form_id":"47","date_created":"2014-09-14 02:47:06","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"First","1.6":"Last","2.1":"12 Alister St","2.3":"Ali","2.4":"State","2.5":"2678","2.6":"Barbados","3":"my@test.com","4":"(345)445-4566","5":"Second Choice","6":"First Choice,Second Choice,Third Choice","2.2":"","7":""},{"id":"451","form_id":"47","date_created":"2014-09-14 02:46:35","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"Jake","1.6":"Jackson","2.1":"123 Fake St","2.2":"Line 2","2.3":"City","2.4":"State","2.5":"2441","2.6":"Albania","3":"test@test.com","4":"(123)123-1234","5":"Third Choice","6":"Second Choice,Third Choice","7":"This is paragraph test!"}]', true);
		
		$results = GFAPI::add_entries($entries, $this->form_id);

		/* test we get the correct results */
		$this->assertEquals(true, is_array($results));

		$this->entries = $results;
	}

	public function test_core_classes()
	{
		$this->assertTrue(true, class_exists('GFCommon'));
		$this->assertTrue(true, class_exists('GFFormsModel'));
		$this->assertTrue(true, class_exists('GFEntryDetail'));
		$this->assertTrue(true, class_exists('GFFormDisplay'));		
	}

	/*
	 * Check that GFAPI::get_form(); outputs correctly 
	 */
	public function test_get_forms()
	{
		$form = GFAPI::get_form($this->form_id);

		/*
		 * Check the basics 
		 * Title is there, field number is correct
		 */
		$this->assertEquals('Simple Form Testing', $form['title']);
		$this->assertEquals(true, is_array($form['fields']));
		$this->assertEquals(7, sizeof($form['fields']));
		$this->assertEquals(1, $form['is_active']);

		/*
		 * Run through each field type and ensure the correct data is present 
		 */
		foreach($form['fields'] as $field)
		{
			switch($field['type'])
			{
				case 'name':
					$this->assertEquals($field['inputs'][0]['id'], $field['id'] . '.3');
					$this->assertEquals($field['inputs'][1]['id'], $field['id'] . '.6');
				break;

				case 'address':
					$this->assertEquals($field['inputs'][0]['id'], $field['id'] . '.1');
					$this->assertEquals($field['inputs'][1]['id'], $field['id'] . '.2');
					$this->assertEquals($field['inputs'][2]['id'], $field['id'] . '.3');
					$this->assertEquals($field['inputs'][3]['id'], $field['id'] . '.4');
					$this->assertEquals($field['inputs'][4]['id'], $field['id'] . '.5');
					$this->assertEquals($field['inputs'][5]['id'], $field['id'] . '.6');
				break;

				case 'email':
					$this->assertEquals(3, $field['id']);
				break;

				case 'phone':
					$this->assertEquals(4, $field['id']);
					$this->assertEquals('standard', $field['phoneFormat']);
				break;

				case 'select':
				case 'multiselect':
					$this->assertEquals(3, sizeof($field['choices']));
				break;

				case 'textarea':
					$this->assertEquals(7, $field['id']);
				break;
			}
		}

		/*
		 * Run through the notifications 
		 */
		$this->assertEquals(2, sizeof($form['notifications']));
		
		$form['notifications'] = array_values($form['notifications']);

		$this->assertEquals('Admin Notification', $form['notifications'][0]['name']);
		$this->assertEquals('User Notification', $form['notifications'][1]['name']);
		
	}

	/*
	 * Test that GFAPI::get_entry() outputs correctly 
	 */
	public function test_get_entry()
	{
		$entry = GFAPI::get_entry($this->entries[0]);

		$valid_entries = array(
			'id', 'form_id', 'date_created', 'is_starred', 'is_read', 'ip', 'source_url', 'post_id', 'currency', 'payment_status', 'payment_date', 'transaction_id', 'payment_amount', 'payment_method', 'is_fulfilled', 'created_by', 'transaction_type', 'user_agent', 'status'
		);

		foreach($valid_entries as $v)
		{
			$this->assertEquals(array_key_exists($v, $entry), true);	
		}
		
		$this->assertEquals('My', $entry['1.3']);
		$this->assertEquals('Name', $entry['1.6']);
		$this->assertEquals('First Choice', $entry[5]);
		
		$entry = GFAPI::get_entry($this->entries[1]);

		$this->assertEquals('First', $entry['1.3']);
		$this->assertEquals('Last', $entry['1.6']);
		$this->assertEquals('12 Alister St', $entry['2.1']);
		$this->assertEquals('Ali', $entry['2.3']);
		$this->assertEquals('State', $entry['2.4']);
		$this->assertEquals('2678', $entry['2.5']);
		$this->assertEquals('Barbados', $entry['2.6']);
		$this->assertEquals('my@test.com', $entry['3']);
		$this->assertEquals('(345)445-4566', $entry['4']);
		$this->assertEquals('Second Choice', $entry['5']);
		$this->assertEquals('First Choice,Second Choice,Third Choice', $entry['6']);

		$entry = GFAPI::get_entry($this->entries[2]);

		$this->assertEquals('Jake', $entry['1.3']);
		$this->assertEquals('Jackson', $entry['1.6']);
		$this->assertEquals('123 Fake St', $entry['2.1']);
		$this->assertEquals('Line 2', $entry['2.2']);
		$this->assertEquals('City', $entry['2.3']);
		$this->assertEquals('State', $entry['2.4']);
		$this->assertEquals('2441', $entry['2.5']);
		$this->assertEquals('Albania', $entry['2.6']);
		$this->assertEquals('test@test.com', $entry['3']);
		$this->assertEquals('(123)123-1234', $entry['4']);
		$this->assertEquals('Third Choice', $entry['5']);
		$this->assertEquals('Second Choice,Third Choice', $entry['6']);		
		$this->assertEquals('This is paragraph test!', $entry['7']);		

		
	}

	/*
	 * Test GF replace variables function (merge tags)
	 * i.e GFCommon::replace_variables 
	 */ 
	public function test_replace_variables()
	{

	}

	/*
	 * Test Gravity Form user privlages 
	 * i.e GFCommon::current_user_can_any("gravityforms_view_entries") 
	 */ 
	public function test_gf_privs()
	{
		/* create user using WP Unit Factory functions */
	}

	/**
	 * Test that the correct IP is returned by the function 
	 * @param  String $ip  The test IP address 
	 * @param  String $var The $_SERVER array key 
	 * 
	 * @dataProvider provider_ip_testing
	 */
	public function run_ip_test($ip, $var)
	{
		$_SERVER[$var] = $ip;
		$this->assertEquals($ip, GFFormsModel::get_ip());
		unset($_SERVER[$var]);
	}

	/**
	 * The data provider for the run_ip_test() function 	 
	 */
	public function provider_ip_testing()
	{
		return array(
			array('5.120.2.1', 'HTTP_CLIENT_IP'),
			array('6.10.3.9', 'HTTP_X_FORWARDED_FOR'),
			array('7.60.126.3', 'REMOTE_ADDR'),
			array('240.24.12.44,5.120.2.1', 'HTTP_CLIENT_IP'),
			array('10.17.54.234,6.10.3.9', 'HTTP_X_FORWARDED_FOR'),
			array('7.60.126.3,65.4.69.129', 'REMOTE_ADDR'),			
		);
	}	

	/* 
	 * Test that GFCommon::$version will produce 
	 * the expected result. 
	 */
	public function test_gf_version()
	{
		$version = GFCommon::$version;

		/* which the version number is a string before we try to match it */
		$this->assertEquals(true, is_string($version));

		/* do a final test to match the version number according to a set standard */
		$this->assertRegExp('/^(?:(\d+)\.)?(?:(\d+)\.)?(\*|\d+)$/', $version);
	}		
}