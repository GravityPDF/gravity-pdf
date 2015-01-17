<?php 

class Test_PDFModel extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();		

		/* Load our plugin functions */
		GFPDF_InstallUpdater::check_filesystem_api();
		GFPDF_InstallUpdater::maybe_deploy();				
	}

	public function tearDown() {
		parent::tearDown();
	}	

	public function test_process_exterior_pages()
	{
		//wp_set_current_user(1);
	}

	public function test_do_notification()
	{

	}

	public function test_check_notification()
	{

	}

	public function test_get_notifications_name()
	{

	}

	public function test_get_form_notifications()
	{

	}

	public function test_generate_pdf_parameters()
	{

	}

	public function test_check_configuration()
	{

	}

	public function test_valid_gravity_forms()
	{

	}

	public function test_check_major_compatibility()
	{
		
	}
}

