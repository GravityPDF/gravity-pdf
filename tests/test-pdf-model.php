<?php 

class Test_PDFModel extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();		

		/* Load our plugin functions */
		GFPDF_Core::fully_loaded_admin();	

		global $gfpdfe_data;
		touch($gfpdfe_data->template_site_location . 'configuration.php');

		global $gfpdf;
		$gfpdf = new GFPDF_Core();  		
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

