<?php

class PDFConfiguration extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();		

		/* Load our plugin functions */
		GFPDF_Core::fully_loaded_admin();	

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
				'template' => 'example-template.php',
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

		/* reset the index */
		$gfpdf->index = array();

		/* init config index */
		$this->set_form_pdfs();					
	}

	private function set_form_pdfs()
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

	public function test_configuration_file() {
		$this->assertEquals(file_exists(PDF_TEMPLATE_LOCATION . 'configuration.php'), true);				

		global $gfpdf;			
		$this->assertEquals($gfpdf->disabled, false);				
		
	}

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

		$this->assertEquals($form4_config_nodes[0]['security'], true);
		$this->assertEquals($form4_config_nodes[0]['pdf_master_password'], 'admin password');

		$this->assertEquals($form5_config_nodes[0]['default-show-html'], true);
		$this->assertEquals($form5_config_nodes[0]['default-show-empty'], false);
		$this->assertEquals($form5_config_nodes[0]['default-show-page-names'], true);	
	}
	
}

