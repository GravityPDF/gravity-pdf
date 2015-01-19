<?php 

/**
 * Run unit tests on the GFPDFEntryDetail /**
 * We will concentrate on the $form_data array and ensuring 
 * we unit test every field to verify the output is correct 
 */

class Test_EntryDetails extends WP_UnitTestCase {
	private $form_id = array();
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
		$this->setupConfiguration();
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
		//$forms[0] = json_decode('{"title":"Simple Form Testing","description":"","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"id":1,"label":"Name","adminLabel":"","type":"name","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":1.3,"label":"First","name":""},{"id":1.6,"label":"Last","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":2,"label":"Address","adminLabel":"","type":"address","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":2.1,"label":"Street Address","name":""},{"id":2.2,"label":"Address Line 2","name":""},{"id":2.3,"label":"City","name":""},{"id":2.4,"label":"State \/ Province","name":""},{"id":2.5,"label":"ZIP \/ Postal Code","name":""},{"id":2.6,"label":"Country","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":3,"label":"Email","adminLabel":"","type":"email","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":4,"label":"Phone","adminLabel":"","type":"phone","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":5,"label":"Untitled","adminLabel":"","type":"select","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":6,"label":"Untitled","adminLabel":"","type":"multiselect","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":7,"label":"Untitled","adminLabel":"","type":"textarea","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"}],"id":47,"useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"notifications":{"5414ff2b70018":{"id":"5414ff2b70018","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}"},"5414ff5a5a28a":{"isActive":true,"id":"5414ff5a5a28a","name":"User Notification","event":"form_submission","to":"3","toType":"field","bcc":"","subject":"Email Notification","message":"User Notification","from":"{admin_email}","fromName":"","replyTo":"","routing":null,"conditionalLogic":null,"disableAutoformat":""}},"confirmations":{"5414ff2b752f0":{"id":"5414ff2b752f0","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"is_active":"1","date_created":"2014-09-14 02:36:27","is_trash":"0"}', true);


		foreach($forms as $id => $form)
		{
			$results = GFAPI::add_form($form);

			/* test the form was correctly added to the database */
			$this->assertInternalType('int', $results);		
			$this->form_id[$id] = $results;		
		}
	}


	public function create_entries()
	{
		//$entries = json_decode('[{"id":"453","form_id":"47","date_created":"2014-09-14 02:47:14","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"My","1.6":"Name","5":"First Choice","2.1":"","2.2":"","2.3":"","2.4":"","2.5":"","2.6":"","3":"","4":"","6":"","7":""},{"id":"452","form_id":"47","date_created":"2014-09-14 02:47:06","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"First","1.6":"Last","2.1":"12 Alister St","2.3":"Ali","2.4":"State","2.5":"2678","2.6":"Barbados","3":"my@test.com","4":"(345)445-4566","5":"Second Choice","6":"First Choice,Second Choice,Third Choice","2.2":"","7":""},{"id":"451","form_id":"47","date_created":"2014-09-14 02:46:35","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"Jake","1.6":"Jackson","2.1":"123 Fake St","2.2":"Line 2","2.3":"City","2.4":"State","2.5":"2441","2.6":"Albania","3":"test@test.com","4":"(123)123-1234","5":"Third Choice","6":"Second Choice,Third Choice","7":"This is paragraph test!"}]', true);
		
		$results = GFAPI::add_entries($entries, $this->form_id[0]);

		/* test we get the correct results */
		$this->assertEquals(true, is_array($results));

		$this->entries = $results;
	}

}