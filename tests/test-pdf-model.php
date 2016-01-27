<?php 

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

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

class Test_PDFModel extends WP_UnitTestCase {
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
		$forms[0] = json_decode('{"title":"Simple Form Testing","description":"","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"id":1,"label":"Name","adminLabel":"","type":"name","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":1.3,"label":"First","name":""},{"id":1.6,"label":"Last","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":2,"label":"Address","adminLabel":"","type":"address","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":2.1,"label":"Street Address","name":""},{"id":2.2,"label":"Address Line 2","name":""},{"id":2.3,"label":"City","name":""},{"id":2.4,"label":"State \/ Province","name":""},{"id":2.5,"label":"ZIP \/ Postal Code","name":""},{"id":2.6,"label":"Country","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":3,"label":"Email","adminLabel":"","type":"email","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":4,"label":"Phone","adminLabel":"","type":"phone","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":5,"label":"Untitled","adminLabel":"","type":"select","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":6,"label":"Untitled","adminLabel":"","type":"multiselect","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":7,"label":"Untitled","adminLabel":"","type":"textarea","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"}],"id":47,"useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"notifications":{"5414ff2b70018":{"id":"5414ff2b70018","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}"},"5414ff5a5a28a":{"isActive":true,"id":"5414ff5a5a28a","name":"User Notification","event":"form_submission","to":"3","toType":"field","bcc":"","subject":"Email Notification","message":"User Notification","from":"{admin_email}","fromName":"","replyTo":"","routing":null,"conditionalLogic":null,"disableAutoformat":""}},"confirmations":{"5414ff2b752f0":{"id":"5414ff2b752f0","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"is_active":"1","date_created":"2014-09-14 02:36:27","is_trash":"0"}', true);
		$forms[1] = json_decode('{"title":"Disable Form","description":"","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"id":1,"label":"Name","adminLabel":"","type":"name","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":1.3,"label":"First","name":""},{"id":1.6,"label":"Last","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":2,"label":"Address","adminLabel":"","type":"address","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":2.1,"label":"Street Address","name":""},{"id":2.2,"label":"Address Line 2","name":""},{"id":2.3,"label":"City","name":""},{"id":2.4,"label":"State \/ Province","name":""},{"id":2.5,"label":"ZIP \/ Postal Code","name":""},{"id":2.6,"label":"Country","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":3,"label":"Email","adminLabel":"","type":"email","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":4,"label":"Phone","adminLabel":"","type":"phone","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":5,"label":"Untitled","adminLabel":"","type":"select","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":6,"label":"Untitled","adminLabel":"","type":"multiselect","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":7,"label":"Untitled","adminLabel":"","type":"textarea","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"}],"id":47,"useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"notifications":{"5414ff2b70018":{"id":"5414ff2b70018","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}"},"5414ff5a5a28a":{"isActive":true,"id":"5414ff5a5a28a","name":"User Notification","event":"form_submission","to":"3","toType":"field","bcc":"","subject":"Email Notification","message":"User Notification","from":"{admin_email}","fromName":"","replyTo":"","routing":null,"conditionalLogic":null,"disableAutoformat":""}},"confirmations":{"5414ff2b752f0":{"id":"5414ff2b752f0","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"is_active":"0","date_created":"2014-09-14 02:36:27","is_trash":"0"}', true);

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
		$entries = json_decode('[{"id":"453","form_id":"47","date_created":"2014-09-14 02:47:14","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"My","1.6":"Name","5":"First Choice","2.1":"","2.2":"","2.3":"","2.4":"","2.5":"","2.6":"","3":"","4":"","6":"","7":""},{"id":"452","form_id":"47","date_created":"2014-09-14 02:47:06","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"First","1.6":"Last","2.1":"12 Alister St","2.3":"Ali","2.4":"State","2.5":"2678","2.6":"Barbados","3":"my@test.com","4":"(345)445-4566","5":"Second Choice","6":"First Choice,Second Choice,Third Choice","2.2":"","7":""},{"id":"451","form_id":"47","date_created":"2014-09-14 02:46:35","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"Jake","1.6":"Jackson","2.1":"123 Fake St","2.2":"Line 2","2.3":"City","2.4":"State","2.5":"2441","2.6":"Albania","3":"test@test.com","4":"(123)123-1234","5":"Third Choice","6":"Second Choice,Third Choice","7":"This is paragraph test!"}]', true);
		
		$results = GFAPI::add_entries($entries, $this->form_id[0]);

		/* test we get the correct results */
		$this->assertEquals(true, is_array($results));

		$this->entries = $results;
	}	

	public function setupConfiguration()
	{
		global $gfpdf;
		$gfpdf = new GFPDF_Core();  		

		$gfpdf->configuration = array(
				array( 
				'form_id'       => 1, 
				'template'      => 'example-template.php',
				'filename'      => 'testform.pdf',
				'notifications' => 'Admin Notification',
				),
				
				array( 
				'form_id'       => 1, 
				'template'      => 'example-template-2.php',
				'filename'      => 'seconddoc.pdf',
				'notifications' => 'User Notification',
				),													
				
				array( 
				'form_id'       => 1, 
				'template'      => 'example-template-3.php',
				'filename'      => 'thirddoc.pdf',
				'notifications' => 'User Notification',
				),											
				
				array( 
				'form_id'       => 1, 
				'template'      => 'example-template-4.php',
				'filename'      => 'fourthdoc.pdf',
				'notifications' => true
				),		
				
				array( 
				'form_id'       => 1, 
				'template'      => 'example-template-5.php',
				'filename'      => 'fifthdoc.pdf',
				'notifications' => array('Admin Notification', 'User Notification'),
				),	

				array(
					'form_id' => 2,
					'template' => 'test-template.php',
					'filename' => 'My Filename.pdf',
					'notifications' => true,
					'premium' => true,
					'dpi' => 300,
					'pdfa1b' => true,
					'pdfx1a' => true,
					'rtl' => true,
					'pdf_size' => 'A5',
					'orientation' => 'landscape',
					'pdf_privileges' => array('print','copy', 'modify'),
					'pdf_password' => 'test123',
					'pdf_master_password' => 'masterpass123'
				),	

				array(
					'form_id' => 3,					
				),


		);		

		/* reenable the configuration */
		$gfpdf->disabled = false;

		/* reset the index */
		$gfpdf->index = array();

		 foreach($gfpdf->configuration as &$node)
		 {
		 	$node = $this->merge_defaults($node);
		 }	

		/* init config index */
		$this->set_form_pdfs();			
	}


	public function set_form_pdfs()
	{
		global $gfpdf;

		foreach($gfpdf->configuration as $key => $config)
		{			
			if(!is_array($config['form_id']))
			{
				$gfpdf->assign_index($config['form_id'], $key);
			}
			else
			{
				foreach($config['form_id'] as $id)
				{
					$gfpdf->assign_index($id, $key);
				}
			}
			
		}
	}

	public function merge_defaults($config)
	{
		global $gf_pdf_default_configuration;

		/*
		 * If the default settings are set we'll merge them into the configuration index
		 */
		if(is_array($gf_pdf_default_configuration) && sizeof($gf_pdf_default_configuration) > 0)
		{
			$config = array_replace_recursive($gf_pdf_default_configuration, $config);
		}
		
		return $config;		
	}	


	/*
	 * Begin Unit Tests 
	 */

	/**
	 * 
	 * @group pdf-model
	 */
	public function test_check_major_compatibility()
	{
		global $gfpdfe_data;

		/*
		 * Run test 1
		 */
		$this->reset_major_comp_test();
		$this->AssertEquals(true, GFPDF_Core_Model::check_major_compatibility());

		/*
		 * Run test 2
		 */
		$gfpdfe_data->wp_is_compatible = false;
		$this->AssertEquals(false, GFPDF_Core_Model::check_major_compatibility());
		$this->assertEquals(10,   has_action('after_plugin_row_' . GF_PDF_EXTENDED_PLUGIN_BASENAME, array('GFPDF_Notices', 'display_wp_compatibility_error')));

		/*
		 * Run test 3
		 */
		$this->reset_major_comp_test();
		$gfpdfe_data->php_version_compatible = false;
		$this->AssertEquals(false, GFPDF_Core_Model::check_major_compatibility());
		$this->assertEquals(10,   has_action('after_plugin_row_' . GF_PDF_EXTENDED_PLUGIN_BASENAME, array('GFPDF_Notices', 'display_pdf_compatibility_error')));

		/*
		 * Run test 4
		 */
		$this->reset_major_comp_test();
		$gfpdfe_data->gf_is_compatible = false;
		$this->AssertEquals(false, GFPDF_Core_Model::check_major_compatibility());
		$this->assertEquals(10,   has_action('after_plugin_row_' . GF_PDF_EXTENDED_PLUGIN_BASENAME, array('GFPDF_Notices', 'display_compatibility_error')));		
	}

	/**
	 * 
	 * @group pdf-model
	 */
	private function reset_major_comp_test()
	{
		global $gfpdfe_data;

		$gfpdfe_data->wp_is_compatible       = true;
		$gfpdfe_data->php_version_compatible = true;
		$gfpdfe_data->gf_is_compatible       = true;		
	}

	/**
	 * 
	 * @group pdf-model
	 */
	public function test_is_fully_installed()
	{
		 global $gfpdfe_data;

		 /*
		  * Run initial test 
		  */
		 $this->reset_fully_installed();
		 $this->AssertEquals(true, GFPDF_Core_Model::is_fully_installed());

		 /*
		  * Run second test 
		  */
		 $gfpdfe_data->wp_is_compatible = false;
		 $this->AssertEquals(false, GFPDF_Core_Model::is_fully_installed());

		 /*
		  * Run third test 
		  */
		 $this->reset_fully_installed();
		 $gfpdfe_data->fresh_install = true;
		 $this->AssertEquals(false, GFPDF_Core_Model::is_fully_installed());

		 /*
		  * Run fourth test 
		  */
		 $this->reset_fully_installed();
		 $gfpdfe_data->allow_initilisation = false;
		 $this->AssertEquals(false, GFPDF_Core_Model::is_fully_installed());

		 /*
		  * Restart for later tests 
		  */
		 $this->reset_fully_installed();

	}

	/**
	 * 
	 * @group pdf-model
	 */ 
	private function reset_fully_installed()
	{
		global $gfpdfe_data;

		$this->reset_major_comp_test();
		$gfpdfe_data->fresh_install = false;
		$gfpdfe_data->allow_initialisation = true;
	}

	/**
	 * 
	 * @group pdf-model
	 */
	public function test_validate_entry_ids()
	{
		/*
		 * Set up data 
		 */
		$form_id  = $this->form_id[0];
		$lead_ids = array($this->entries[0]);
		$ip       = '144.131.91.23';

		/*
		 * Run tests 
		 */
		$this->AssertEquals(1, sizeof(GFPDF_Core_Model::validate_entry_ids($lead_ids, $form_id, $ip, 0)));

		/*
		 * Run test 2
		 */
		$lead_ids = $this->entries;
		$this->AssertEquals(3, sizeof(GFPDF_Core_Model::validate_entry_ids($lead_ids, $form_id, $ip, 0)));

		/*
		 * Run test 3
		 */
		$lead_ids = array(234, 213, 12);
		$this->AssertEquals(0, sizeof(GFPDF_Core_Model::validate_entry_ids($lead_ids, $form_id, $ip, 0)));

		/*
		 * Run test 4
		 */
		$ip = '120.12.30.64';
		$lead_ids = $this->entries;
		wp_set_current_user(1);

		$this->AssertEquals(3, sizeof(GFPDF_Core_Model::validate_entry_ids($lead_ids, $form_id, $ip, 0)));

		/*
		 * Run test 5
		 */
		wp_set_current_user(2);
		$this->AssertEquals(0, sizeof(GFPDF_Core_Model::validate_entry_ids($lead_ids, $form_id, $ip, 0)));

	}

	/**
	 * Test our notification function 
	 * @group pdf
	 * @group pdf-model
	 */
	public function test_gfpdfe_create_and_attach_pdf()
	{
		global $gfpdf;
		$gfpdf->render = new PDFRender();

		$form         = GFAPI::get_form($this->form_id[0]);
		$entry        = GFAPI::get_entry($this->entries[0]);	
		$notification = array(
			'name' => 'Admin Notification',
			'attachments' => array(),
		);

		/*
		 * Run first test 
		 */
		$test1 = GFPDF_Core_Model::gfpdfe_create_and_attach_pdf($notification, $form, $entry);
		$this->AssertEquals(3, sizeof($test1['attachments']));

		/*
		 * Run second test 
		 */
		$notification['name'] = 'User Notification';
		$test2 = GFPDF_Core_Model::gfpdfe_create_and_attach_pdf($notification, $form, $entry);
		$this->AssertEquals(4, sizeof($test2['attachments']));

		/*
		 * Run third test 
		 */
		$form['notifications']['5414ff2b70019'] = array(
			'name'  => 'Bunny Notification',
			'event' => 'form_submission',
		);
		$notification['name'] = 'Bunny Notification';
		$test3 = GFPDF_Core_Model::gfpdfe_create_and_attach_pdf($notification, $form, $entry);
		$this->AssertEquals(1, sizeof($test3['attachments']));		

		/*
		 * Run fourth test 
		 */
		$notification['name'] = 'Bugs Bunny Notification';
		$test3 = GFPDF_Core_Model::gfpdfe_create_and_attach_pdf($notification, $form, $entry);
		$this->AssertEquals(0, sizeof($test3['attachments']));

	}


	/**
	 * Test that the correct IP is returned by the function 
	 * @param  String $ip  The test IP address 
	 * @param  String $var The $_SERVER array key 
	 *
	 * @group pdf-model
	 * @dataProvider provider_notifications_list
	 */
	public function test_check_notification($notification_name, $notifications, $expected)
	{		
		$this->assertEquals($expected, GFPDF_Core_Model::check_notification($notification_name, $notifications));
	}

	/**
	 * The data provider for the test_check_notification() function 	 
	 */
	public function provider_notifications_list()
	{
		return array(
			array(
				'Admin Notification',
				array('User Notification', 'Admin Notification', 'Other'),
				true,
			),

			array(
				'User Notification',
				array('User Notification', 'Admin Notification', 'Other'),
				true,
			),		

			array(
				array('User Notification', 'Admin Notification'),
				array('User Notification', 'Admin Notification', 'Other'),
				true,
			),		

			array(
				array('Tenth Notification', 'Sixth Notification'),
				array('User Notification', 'Admin Notification', 'Other'),
				false,
			),

			array(
				'Notification',
				array(),
				false,
			),										

			array(
				array('User Notification', 'Admin Notification'),
				array(),
				false,
			),	

			array(
				array('Item Notification', 'Wilson', 'Sysmic Notification'),
				array('User Notification', 'Admin Notification', 'Other'),
				false,
			),						
		);
	}		

	/**
	 * 
	 * @group pdf-model
	 */
	public function test_get_notifications_name()
	{
		$form = GFAPI::get_form($this->form_id[0]);
		$notifications = GFPDF_Core_Model::get_notifications_name($form);
		
		/*
		 * Run assertions 
		 */
		$this->AssertEquals(true, is_array($notifications));
		$this->AssertEquals(2, sizeof($notifications));
		$this->AssertEquals(true, in_array('Admin Notification', $notifications));
		$this->AssertEquals(true, in_array('User Notification', $notifications));
		$this->AssertEquals(false, in_array('Notification', $notifications));
	}

	/**
	 * 
	 * @group pdf-model
	 */
	public function test_get_form_notifications()
	{
		global $gfpdf;

		/*
		 * Set up our configuration
		 */
		$config = $gfpdf->get_config(1);
		$form   = GFAPI::get_form($this->form_id[0]);

		/*
		 * Set up our data
		 */
		$notifications   = array();
		$notifications[] = GFPDF_Core_Model::get_form_notifications($form, $config[0]);
		$notifications[] = GFPDF_Core_Model::get_form_notifications($form, $config[1]);
		$notifications[] = GFPDF_Core_Model::get_form_notifications($form, $config[2]);
		$notifications[] = GFPDF_Core_Model::get_form_notifications($form, $config[3]);	
		$notifications[] = GFPDF_Core_Model::get_form_notifications($form, $config[4]);	

		/*
		 * Run our tests 
		 */
		$this->assertEquals(1, sizeof($notifications[0]));
		$this->AssertEquals(true, in_array('Admin Notification', $notifications[0]));
		$this->AssertEquals(false, in_array('User Notification', $notifications[0]));

		$this->AssertEquals(1, sizeof($notifications[1]), 1);
		$this->AssertEquals(false, in_array('Admin Notification', $notifications[1]));
		$this->AssertEquals(true, in_array('User Notification', $notifications[1]));

		$this->AssertEquals(1, sizeof($notifications[2]), 1);
		$this->AssertEquals(false, in_array('Admin Notification', $notifications[2]));
		$this->AssertEquals(true, in_array('User Notification', $notifications[2]));

		$this->AssertEquals(2, sizeof($notifications[3]), 2);
		$this->AssertEquals(true, in_array('Admin Notification', $notifications[3]));
		$this->AssertEquals(true, in_array('User Notification', $notifications[3]));	

		$this->AssertEquals(2, sizeof($notifications[4]), 2);
		$this->AssertEquals(true, in_array('Admin Notification', $notifications[4]));
		$this->AssertEquals(true, in_array('User Notification', $notifications[4]));				

	}

	/**
	 * 
	 * @group pdf-model
	 */
	public function test_generate_pdf_parameters()
	{
		global $gfpdf;

		/*
		 * Set up first data batch 
		 */
		$template = (isset($gfpdf->configuration[5]['template'])) ? $gfpdf->configuration[5]['template'] : '';
		$arg      = GFPDF_Core_Model::generate_pdf_parameters(5, $this->form_id[0], 453, $template);

		/*
		 * Run tests
		 */
		$this->assertEquals('My Filename.pdf', $arg['pdfname']);
		$this->assertEquals('test-template.php', $arg['template']);
		$this->assertEquals('A5', $arg['pdf_size']);
		$this->assertEquals('landscape', $arg['orientation']);
		$this->assertEquals(false, $arg['security']);
		$this->assertEquals(3, sizeof($arg['pdf_privileges']));
		$this->assertEquals('test123', $arg['pdf_password']);
		$this->assertEquals('masterpass123', $arg['pdf_master_password']);
		$this->assertEquals(true, $arg['rtl']);
		$this->assertEquals(true, $arg['premium']);
		$this->assertEquals(300, $arg['dpi']);
		$this->assertEquals(true, $arg['pdfa1b']);
		$this->assertEquals(true, $arg['pdfx1a']);


		/*
		 * Set up second data branch
		 */
		$template = (isset($gfpdf->configuration[6]['template'])) ? $gfpdf->configuration[6]['template'] : '';
		$arg      = GFPDF_Core_Model::generate_pdf_parameters(6, $this->form_id[0], 453, $template);

		/*
		 * Run tests
		 */
		$this->assertEquals('form-1-entry-453.pdf', $arg['pdfname']);
		$this->assertEquals('default-template.php', $arg['template']);
		$this->assertEquals('A4', $arg['pdf_size']);
		$this->assertEquals('portrait', $arg['orientation']);
		$this->assertEquals(false, $arg['security']);
		$this->assertEquals(0, sizeof($arg['pdf_privileges']));
		$this->assertEquals('', $arg['pdf_password']);
		$this->assertEquals('', $arg['pdf_master_password']);
		$this->assertEquals(false, $arg['rtl']);
		$this->assertEquals(false, $arg['premium']);
		$this->assertEquals('', $arg['dpi']);
		$this->assertEquals(false, $arg['pdfa1b']);
		$this->assertEquals(false, $arg['pdfx1a']);


		/*
		 * Set up third data branch test
		 */
		 global $gf_pdf_default_configuration;
 
 		 /*
 		  * Reset the default config values and recalculate them
 		  */
		 $gf_pdf_default_configuration = array(
				'template'    => 'default-template-no-style.php',
				'pdf_size'    => 'A6',
				'orientation' => 'landscape',
				'premium'     => true,
				'dpi'         => 300,			
		 ); 

		 $this->setupConfiguration();

		$template = (isset($gfpdf->configuration[6]['template'])) ? $gfpdf->configuration[6]['template'] : '';
		$arg      = GFPDF_Core_Model::generate_pdf_parameters(6, $this->form_id[0], 1, $template);		 

		/*
		 * Run our tests 
		 */
		$this->AssertEquals('form-1-entry-1.pdf', $arg['pdfname']);
		$this->AssertEquals('default-template-no-style.php', $arg['template']);
		$this->AssertEquals('A6', $arg['pdf_size']);
		$this->AssertEquals('landscape', $arg['orientation']);
		$this->AssertEquals(false, $arg['security']);
		$this->AssertEquals(0, sizeof($arg['pdf_privileges']));
		$this->AssertEquals('', $arg['pdf_password']);
		$this->AssertEquals('', $arg['pdf_master_password']);
		$this->AssertEquals(false, $arg['rtl']);
		$this->AssertEquals(true, $arg['premium']);
		$this->AssertEquals(300, $arg['dpi']);
		$this->AssertEquals(false, $arg['pdfa1b']);
		$this->AssertEquals(false, $arg['pdfx1a']);		
	}

	/**
	 * 
	 * @group pdf-model
	 */
	public function test_valid_gravity_forms()
	{
		$this->AssertEquals(false, GFPDF_Core_Model::valid_gravity_forms());
		
		/*
		 * Test active form
		 */
		$_POST['gform_submit'] = $this->form_id[0];
		$this->AssertEquals(true, GFPDF_Core_Model::valid_gravity_forms());		

		/*
		 * Test inactive form
		 */
		$_POST['gform_submit'] = $this->form_id[1];
		$this->AssertEquals(true, GFPDF_Core_Model::valid_gravity_forms());		

		/*
		 * Test non-existant form
		 */
		$_POST['gform_submit'] = 5;
		$this->AssertEquals(false, GFPDF_Core_Model::valid_gravity_forms());		
	}

}

