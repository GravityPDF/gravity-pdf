<?php

class Test_PDFConfiguration extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();		

		/* Load our plugin functions */
		GFPDF_Core::fully_loaded_admin();	

		/* remove the configuration file */		
		global $gfpdfe_data;

		if(file_exists($gfpdfe_data->template_site_location . 'configuration.php'))			
			unlink($gfpdfe_data->template_site_location . 'configuration.php');

		global $gfpdf;
		$gfpdf = new GFPDF_Core();  

		$gfpdf->configuration = array(
			array( 
				'form_id' => 1, 
				'template' => 'example-template.php',
				'filename' => 'testform.pdf',
			),

			array( 
				'form_id' => 1, 
				'template' => 'example-template-2.php',
				'filename' => 'seconddoc.pdf',
			),			

			array( 
				'form_id' => 2, 
				'template' => 'default-template.php',
			),			

			array( 
				'form_id' => 4, 
				'template' => 'my-custom-template.php',
			  	'security' => true, 
			  	'pdf_master_password' => 'admin password', 				
			),				

			array( 
				'form_id' => 5, 
				'template' => 'default-template-no-style.php',
			  	'default-show-html' => true,
			  	'default-show-empty' => false,
			  	'default-show-page-names' => true,			
			),							
		);

		/* reenable the configuration */
		$gfpdf->disabled = false;

		/* reset the index */
		$gfpdf->index = array();

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

	public function tearDown() {
		parent::tearDown();
	}	

	/**
	 * Check if the configuration nodes are being stored correctly 	 
	 */
	public function test_get_config()
	{
		global $gfpdf;

		$form1_config_nodes = $gfpdf->get_form_configuration(1);
		$form2_config_nodes = $gfpdf->get_form_configuration(2);
		$form3_config_nodes = $gfpdf->get_form_configuration(3);
		$form4_config_nodes = $gfpdf->get_form_configuration(4);
		$form5_config_nodes = $gfpdf->get_form_configuration(5);

		/* Check the config array sizes are correct */
		$this->assertEquals(sizeof($form1_config_nodes), 2);
		$this->assertEquals(sizeof($form2_config_nodes), 1);
		$this->assertEquals($form3_config_nodes, false);

		/* Check that our configuration data matches */
		$this->assertEquals($form1_config_nodes[0]['template'], 'example-template.php');
		$this->assertEquals($form1_config_nodes[0]['filename'], 'testform.pdf');
		$this->assertEquals($form1_config_nodes[1]['filename'], 'seconddoc.pdf');

		$this->assertEquals($form2_config_nodes[0]['template'], 'default-template.php');

		$this->assertTrue($form4_config_nodes[0]['security']);
		$this->assertEquals($form4_config_nodes[0]['pdf_master_password'], 'admin password');

		$this->assertTrue($form5_config_nodes[0]['default-show-html']);
		$this->assertFalse($form5_config_nodes[0]['default-show-empty']);
		$this->assertTrue($form5_config_nodes[0]['default-show-page-names']);	
	}

	/*
	 * Check that the config index (stored by form ID) is being correctly populated 
	 */
	public function test_config_index()
	{
		global $gfpdf;

		$this->assertEquals(sizeof($gfpdf->get_config(1)), 2);
		$this->assertEquals(sizeof($gfpdf->get_config(2)), 1);
		$this->assertFalse($gfpdf->get_config(3));
	}

	/*
	 * Check that the template default values are being stored correctly 
	 */
	public function test_default_config_data()
	{
		global $gfpdf;

		$form1 = $gfpdf->get_default_config_data(1);
		$form2 = $gfpdf->get_default_config_data(2);
		$form5 = $gfpdf->get_default_config_data(5);

		$this->assertFalse($form1['html_field'], false);
		$this->assertFalse($form1['empty_field'], false);
		$this->assertFalse($form1['page_names'], false);

		$this->assertFalse($form2['html_field'], false);
		$this->assertFalse($form2['empty_field'], false);
		$this->assertFalse($form2['page_names'], false);	

		$this->assertTrue($form5['html_field'], true);
		$this->assertFalse($form5['empty_field'], false);
		$this->assertTrue($form5['page_names'], true);			
	}

	/*
	 * Check that our configuration data is being pulled correctly when referenced by an ID
	 */
	public function test_pull_config_data()
	{
		global $gfpdf;

		$_GET['aid'] = 1;
		$form1 = $gfpdf->pull_config_data(1);
		$this->assertEquals($form1['filename'], 'testform.pdf');

		$_GET['aid'] = 2;
		$form1 = $gfpdf->pull_config_data(1);		
		$this->assertEquals($form1['filename'], 'seconddoc.pdf');

		unset($_GET['aid']);
		$form3 = $gfpdf->pull_config_data(3);
		$this->assertFalse($form3);

		$form4 = $gfpdf->pull_config_data(4);
		$this->assertEquals($form4['pdf_master_password'], 'admin password');
	}

	/*
	 * Test that the correct template is being pulled from the configuration
	 */
	public function test_get_template()
	{
		global $gfpdf;

		/*
		 * Test that if the defaults template is disabled that no template is returned
		 */
		define('GFPDF_SET_DEFAULT_TEMPLATE', false);
		$this->assertFalse($gfpdf->get_template(10));

		$this->assertEquals($gfpdf->get_template(1), 'example-template.php');

		$form1_results = $gfpdf->get_template(1,true);
		$this->assertEquals(sizeof($form1_results), 2);
		$this->assertEquals($form1_results[1]['template'], 'example-template-2.php');
		$this->assertEquals($form1_results[1]['filename'], 'seconddoc.pdf');		
	}	

	/**
	 * TODO 	 
	 */
	public function test_get_pdf_name()
	{

	}

	/**
	 * Write a stub to test a number of filename options 
	 * We won't be testing form or lead merge tag data at this stage 
	 * Just the basic naming convention (see common functions tests for full naming tests)
	 *
	 * @dataProvider provider_test_pdf_name
	 */
	public function test_pdf_name($test, $result)
	{
		$this->assertEquals(PDF_Common::validate_pdf_name($test), $result);
	}

	public function provider_test_pdf_name()
	{
		return array(
			array('This is my cool pdf name.pdf', 'This is my cool pdf name.pdf'),
			array("I'm using // a-bunc_&of@192 Weird <characters>.pdf", "I'm using -- a-bunc_&of@192 Weird -characters-.pdf"),
			array('My document name', 'My document name.pdf'),
			array('Naming.convension.pdf', 'Naming_convension.pdf'),
			array('|H@xor|.pdf', '-H@xor-.pdf'),
			array('A lot "of" *symbols* in:this \one.pdf', 'A lot -of- -symbols- in-this -one.pdf'),
			array('Can I have a question?.pdf', 'Can I have a question-.pdf'),
		);
	}

	/**
	 * Test if an array of test data will pass or fail
	 *
	 * @dataProvider provider_test_privileges
	 */
	public function test_validate_privileges($test, $expectedResults)
	{
		global $gfpdf;

		$validated_results = array_values($gfpdf->validate_privileges($test));

		$this->assertEquals($validated_results, array_values($expectedResults));
	}

	public function provider_test_privileges()
	{		
		return array( 
			array(array('copy', 'print', 'modify', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-highres'), array('copy', 'print', 'modify', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-highres')),
			array(array('data', 'copy', 'print', 'food', 'modify', 'monkey-business', 'annot-forms', 'indéfini', 'fill-forms', 'extract', 'assemble', 'print-highres'), array('copy', 'print', 'modify', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-highres')),
			array(array('con caracteres', 'modify', 'extract', '123'), array('modify', 'extract')),
			array('food', array()),
			array(20, array()),
			array(true, array()),
		);
	}
	
}

