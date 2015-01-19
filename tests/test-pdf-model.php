<?php 

class Test_PDFModel extends WP_UnitTestCase {
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
		$form = json_decode('{"title":"Simple Form Testing","description":"","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"id":1,"label":"Name","adminLabel":"","type":"name","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":1.3,"label":"First","name":""},{"id":1.6,"label":"Last","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":2,"label":"Address","adminLabel":"","type":"address","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":2.1,"label":"Street Address","name":""},{"id":2.2,"label":"Address Line 2","name":""},{"id":2.3,"label":"City","name":""},{"id":2.4,"label":"State \/ Province","name":""},{"id":2.5,"label":"ZIP \/ Postal Code","name":""},{"id":2.6,"label":"Country","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":3,"label":"Email","adminLabel":"","type":"email","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":4,"label":"Phone","adminLabel":"","type":"phone","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":5,"label":"Untitled","adminLabel":"","type":"select","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":6,"label":"Untitled","adminLabel":"","type":"multiselect","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":7,"label":"Untitled","adminLabel":"","type":"textarea","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"}],"id":47,"useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"notifications":{"5414ff2b70018":{"id":"5414ff2b70018","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}"},"5414ff5a5a28a":{"isActive":true,"id":"5414ff5a5a28a","name":"User Notification","event":"form_submission","to":"3","toType":"field","bcc":"","subject":"Email Notification","message":"User Notification","from":"{admin_email}","fromName":"","replyTo":"","routing":null,"conditionalLogic":null,"disableAutoformat":""}},"confirmations":{"5414ff2b752f0":{"id":"5414ff2b752f0","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"is_active":"1","date_created":"2014-09-14 02:36:27","is_trash":"0"}', true);

		$results = GFAPI::add_form($form);

		/* test the form was correctly added to the database */
		$this->assertInternalType("int", $results);		
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

	public function test_process_exterior_pages()
	{
		//wp_set_current_user(1);
	}

	public function test_do_notification()
	{

	}

	/**
	 * Test that the correct IP is returned by the function 
	 * @param  String $ip  The test IP address 
	 * @param  String $var The $_SERVER array key 
	 * 
	 * @dataProvider provider_notifications_list
	 */
	public function test_check_notification($notification_name, $notifications, $expected)
	{		
		$this->assertEquals(GFPDF_Core_Model::check_notification($notification_name, $notifications), $expected);
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

	public function test_get_notifications_name()
	{
		$form = GFAPI::get_form(1);
		$notifications = GFPDF_Core_Model::get_notifications_name('form_submission', $form);	
		
		/*
		 * Run assertions 
		 */
		$this->AssertEquals(is_array($notifications), true);
		$this->AssertEquals(sizeof($notifications), 2);
		$this->AssertEquals(in_array('Admin Notification', $notifications), true);
		$this->AssertEquals(in_array('User Notification', $notifications), true);
		$this->AssertEquals(in_array('Notification', $notifications), false);
	}

	public function test_get_form_notifications()
	{
		global $gfpdf;

		/*
		 * Set up our configuration
		 */
		$config = $gfpdf->get_config(1);
		$form   = GFAPI::get_form(1);

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
		$this->AssertEquals(sizeof($notifications[0]), 1);
		$this->AssertEquals(in_array('Admin Notification', $notifications[0]), true);
		$this->AssertEquals(in_array('User Notification', $notifications[0]), false);

		$this->AssertEquals(sizeof($notifications[1]), 1);
		$this->AssertEquals(in_array('Admin Notification', $notifications[1]), false);
		$this->AssertEquals(in_array('User Notification', $notifications[1]), true);

		$this->AssertEquals(sizeof($notifications[2]), 1);
		$this->AssertEquals(in_array('Admin Notification', $notifications[2]), false);
		$this->AssertEquals(in_array('User Notification', $notifications[2]), true);

		$this->AssertEquals(sizeof($notifications[3]), 2);
		$this->AssertEquals(in_array('Admin Notification', $notifications[3]), true);
		$this->AssertEquals(in_array('User Notification', $notifications[3]), true);	

		$this->AssertEquals(sizeof($notifications[4]), 2);
		$this->AssertEquals(in_array('Admin Notification', $notifications[4]), true);
		$this->AssertEquals(in_array('User Notification', $notifications[4]), true);				

	}

	public function test_generate_pdf_parameters()
	{
		global $gfpdf;

		/*
		 * Set up first data batch 
		 */
		$template = (isset($gfpdf->configuration[5]['template'])) ? $gfpdf->configuration[5]['template'] : '';
		$arg      = GFPDF_Core_Model::generate_pdf_parameters(5, 1, 1, $template);

		/*
		 * Run tests
		 */
		$this->AssertEquals($arg['pdfname'], 'My Filename.pdf');
		$this->AssertEquals($arg['template'], 'test-template.php');
		$this->AssertEquals($arg['pdf_size'], 'A5');
		$this->AssertEquals($arg['orientation'], 'landscape');
		$this->AssertEquals($arg['security'], false);
		$this->AssertEquals(sizeof($arg['pdf_privileges']), 3);
		$this->AssertEquals($arg['pdf_password'], 'test123');
		$this->AssertEquals($arg['pdf_master_password'], 'masterpass123');
		$this->AssertEquals($arg['rtl'], true);
		$this->AssertEquals($arg['premium'], true);
		$this->AssertEquals($arg['dpi'], 300);
		$this->AssertEquals($arg['pdfa1b'], true);
		$this->AssertEquals($arg['pdfx1a'], true);


		/*
		 * Set up second data branch
		 */
		$template = (isset($gfpdf->configuration[6]['template'])) ? $gfpdf->configuration[6]['template'] : '';
		$arg      = GFPDF_Core_Model::generate_pdf_parameters(6, 1, 1, $template);

		/*
		 * Run tests
		 */
		$this->AssertEquals($arg['pdfname'], 'form-1-entry-1.pdf');
		$this->AssertEquals($arg['template'], 'default-template.php');
		$this->AssertEquals($arg['pdf_size'], 'A4');
		$this->AssertEquals($arg['orientation'], 'portrait');
		$this->AssertEquals($arg['security'], false);
		$this->AssertEquals(sizeof($arg['pdf_privileges']), 0);
		$this->AssertEquals($arg['pdf_password'], '');
		$this->AssertEquals($arg['pdf_master_password'], '');
		$this->AssertEquals($arg['rtl'], false);
		$this->AssertEquals($arg['premium'], false);
		$this->AssertEquals($arg['dpi'], '');
		$this->AssertEquals($arg['pdfa1b'], false);
		$this->AssertEquals($arg['pdfx1a'], false);


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
		$arg      = GFPDF_Core_Model::generate_pdf_parameters(6, 1, 1, $template);		 

		/*
		 * Run our tests 
		 */
		$this->AssertEquals($arg['pdfname'], 'form-1-entry-1.pdf');
		$this->AssertEquals($arg['template'], 'default-template-no-style.php');
		$this->AssertEquals($arg['pdf_size'], 'A6');
		$this->AssertEquals($arg['orientation'], 'landscape');
		$this->AssertEquals($arg['security'], false);
		$this->AssertEquals(sizeof($arg['pdf_privileges']), 0);
		$this->AssertEquals($arg['pdf_password'], '');
		$this->AssertEquals($arg['pdf_master_password'], '');
		$this->AssertEquals($arg['rtl'], false);
		$this->AssertEquals($arg['premium'], true);
		$this->AssertEquals($arg['dpi'], 300);
		$this->AssertEquals($arg['pdfa1b'], false);
		$this->AssertEquals($arg['pdfx1a'], false);		
	}

	public function test_check_configuration()
	{

	}

}

