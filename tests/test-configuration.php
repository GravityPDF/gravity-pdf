<?php

/*
    This file is part of Gravity PDF.

    Gravity PDF is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Gravity PDF is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Gravity PDF. If not, see <http://www.gnu.org/licenses/>.
*/

class Test_PDFConfiguration extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();		

		GFPDF_InstallUpdater::check_filesystem_api();
		GFPDF_InstallUpdater::maybe_deploy();	

		global $gfpdf;
		$gfpdf = new GFPDF_Core();  

		$gfpdf->configuration = array(
				array( 
				'form_id'                      => 1, 
				'template'                     => 'example-template.php',
				'filename'                     => 'testform.pdf',
				),
				
				array( 
				'form_id'                      => 1, 
				'template'                     => 'example-template-2.php',
				'filename'                     => 'seconddoc.pdf',
				),			
				
				array( 
				'form_id'                      => 2, 
				'template'                     => 'default-template.php',
				'filename'					   => 'third-pdf.pdf',
				),	

				array( 
				'form_id'                      => 2, 
				'template'                     => 'default-template.php',
				'filename'					   => 'fourth-pdf.pdf',
				),			

				array( 
				'form_id'                      => 2, 
				'template'                     => 'default-template.php',
				'filename'					   => 'fifth-pdf.pdf',
				),								
				
				array( 
				'form_id'                      => 4, 
				'template'                     => 'my-custom-template.php',
				'security'                     => true, 
				'pdf_master_password'          => 'admin password', 				
				),				
				
				array( 
				'form_id'                      => 5, 
				'template'                     => 'default-template-no-style.php',
				'default-show-html'            => true,
				'default-show-empty'           => false,
				'default-show-page-names'      => true,			
				),		
				
				array( 
				'form_id'                      => 6, 
				'template'                     => 'default-template-no-style.php',
				'default-show-html'            => true,
				'default-show-empty'           => true,
				'default-show-page-names'      => true,			
				'default-show-section-content' => true,
				),		

				array( 
				'form_id'                      => array(7,8,9,10),
				'template'                     => 'default-template-no-style.php',
				'default-show-html'            => true,
				'default-show-empty'           => true,
				'default-show-page-names'      => true,			
				'default-show-section-content' => true,
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
	 * @group configuration
	 */
	public function test_get_config()
	{
		global $gfpdf;

		$form1_config_nodes = $gfpdf->get_form_configuration(1);
		$form2_config_nodes = $gfpdf->get_form_configuration(2);
		$form3_config_nodes = $gfpdf->get_form_configuration(3);
		$form4_config_nodes = $gfpdf->get_form_configuration(4);
		$form5_config_nodes = $gfpdf->get_form_configuration(5);
		$form6_config_nodes = $gfpdf->get_form_configuration(6);

		/* Check the config array sizes are correct */
		$this->assertEquals(sizeof($form1_config_nodes), 2);
		$this->assertEquals(sizeof($form2_config_nodes), 3);
		$this->assertEquals($form3_config_nodes, false);

		/* Check that our configuration data matches */

		/*
		 * Review form 1 
		 */
		$this->assertEquals(current($form1_config_nodes)['template'], 'example-template.php');
		$this->assertEquals(current($form1_config_nodes)['filename'], 'testform.pdf');
		next($form1_config_nodes);
		$this->assertEquals(current($form1_config_nodes)['filename'], 'seconddoc.pdf');

		/*
		 * Review form 2
		 */
		$this->assertEquals(current($form2_config_nodes)['template'], 'default-template.php');

		/*
		 * Review form 4
		 */
		$this->assertTrue(current($form4_config_nodes)['security']);
		$this->assertEquals(current($form4_config_nodes)['pdf_master_password'], 'admin password');

		/*
		 * Review form 5
		 */
		$this->assertTrue(current($form5_config_nodes)['default-show-html']);
		$this->assertFalse(current($form5_config_nodes)['default-show-empty']);
		$this->assertTrue(current($form5_config_nodes)['default-show-page-names']);	

		/*
		 * Review form 6
		 */
		$this->assertTrue(current($form6_config_nodes)['default-show-html']);	
		$this->assertTrue(current($form6_config_nodes)['default-show-empty']);	
		$this->assertTrue(current($form6_config_nodes)['default-show-page-names']);	
		$this->assertTrue(current($form6_config_nodes)['default-show-section-content']);	
	}

	/**
	 * Check that the config index (stored by form ID) is being correctly populated 
	 * @group configuration
	 */
	public function test_config_index()
	{
		global $gfpdf;

		$this->assertEquals(sizeof($gfpdf->get_config(1)), 2);
		$this->assertEquals(sizeof($gfpdf->get_config(2)), 3);		
		$this->assertFalse($gfpdf->get_config(3));
		$this->assertEquals(sizeof($gfpdf->get_config(4)), 1);
		$this->assertEquals(sizeof($gfpdf->get_config(6)), 1);
	}

	/**
	 * Check that the template default values are being stored correctly 
	 * @group configuration
	 */
	public function test_default_config_data()
	{
		global $gfpdf;

		$form1 = $gfpdf->get_config_data(1);
		$form2 = $gfpdf->get_config_data(2);
		$form5 = $gfpdf->get_config_data(5);
		$form6 = $gfpdf->get_config_data(6);

		$this->assertEquals($form1['html_field'], false);
		$this->assertEquals($form1['empty_field'], false);
		$this->assertEquals($form1['page_names'], false);
		$this->assertEquals($form1['section_content'], false);	

		$this->assertEquals($form2['html_field'], false);
		$this->assertEquals($form2['empty_field'], false);
		$this->assertEquals($form2['page_names'], false);	
		$this->assertEquals($form2['section_content'], false);	

		$this->assertEquals($form5['html_field'], true);
		$this->assertEquals($form5['empty_field'], false);
		$this->assertEquals($form5['page_names'], true);			
		$this->assertEquals($form5['section_content'], false);	

		$this->assertEquals($form6['html_field'], true);
		$this->assertEquals($form6['empty_field'], true);
		$this->assertEquals($form6['page_names'], true);			
		$this->assertEquals($form6['section_content'], true);	
	}

	/**
	 * Check that our configuration data is being pulled correctly when referenced by an ID
	 * @group configuration
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

		$_GET['aid'] = 1;
		$form2 = $gfpdf->pull_config_data(2);
		$this->assertEquals($form2['filename'], 'third-pdf.pdf');

		$_GET['aid'] = 2;
		$form2 = $gfpdf->pull_config_data(2);
		$this->assertEquals($form2['filename'], 'fourth-pdf.pdf');		

		unset($_GET['aid']);
		$form3 = $gfpdf->pull_config_data(3);
		$this->assertFalse($form3);

		$form4 = $gfpdf->pull_config_data(4);
		$this->assertEquals($form4['pdf_master_password'], 'admin password');
	}

	/**
	 * Test that the correct template is being pulled from the configuration
	 * @group configuration
	 */
	public function test_get_template()
	{
		global $gfpdf;


		$this->assertEquals($gfpdf->get_template(1), 'example-template.php');				
		
		$_GET['template'] = 'example-template-2.php';
		$this->assertEquals($gfpdf->get_template(1), 'example-template-2.php');
	}

	/**
	 * Test the get_aid($config_id, $form_id) property
	 * @group configuration
	 */
	public function test_get_aid()
	{
		global $gfpdf;

		$this->assertEquals($gfpdf->get_aid(0, 1), 1);
		$this->assertEquals($gfpdf->get_aid(1, 1), 2);

		$this->assertEquals($gfpdf->get_aid(2, 2), 1);
		$this->assertEquals($gfpdf->get_aid(3, 2), 2);
		$this->assertEquals($gfpdf->get_aid(4, 2), 3);		
	}	

	/**
	 * Test the index_lookup($config_id, $form_id = false) property 
	 * @group configuration
	 */
	public function test_index_lookup()
	{
		global $gfpdf;

		$this->assertEquals(sizeof($gfpdf->index_lookup(1)), 1);
		$this->assertEquals(sizeof($gfpdf->index_lookup(3)), 1);
		$this->assertEquals(sizeof($gfpdf->index_lookup(8)), 4);

		$this->assertEquals($gfpdf->index_lookup(2,2), 0);
		$this->assertEquals($gfpdf->index_lookup(3,2), 1);
	}

	/**
	 * Write a stub to test a number of filename options 
	 * We won't be testing form or lead merge tag data at this stage 
	 * Just the basic naming convention (see common functions tests for full naming tests)
	 *
	 * @group configuration
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
	 * @group configuration
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

