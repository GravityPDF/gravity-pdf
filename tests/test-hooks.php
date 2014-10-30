<?php 

class stubCurrentScreen
{
	function in_admin()
	{
		return true;
	}
}

class PDF_Actions_And_Filters extends WP_UnitTestCase {
	public function setUp() {

		parent::setUp();	

		/* Load our plugin functions */
		GFPDF_Core::fully_loaded_admin();	

		/*
		 * Admin init was restricted to Gravity Form-only pages 
		 */		
		global $gfpdf;
		$gfpdf = new GFPDF_Core();  		
	}

	public function tearDown() {
		parent::tearDown();
	}				

	public function test_actions_hook() {
		

		$this->assertEquals(10,   has_action('init', array('GFPDF_Core', 'pdf_init')));
		$this->assertEquals(9999, has_action('admin_init', array('GFPDF_Core', 'fully_loaded_admin')));

		$this->assertEquals(10, has_action('wp_ajax_support_request', array('GFPDF_Settings_Model', 'gfpdf_support_request')));		

		$this->assertEquals(10, has_action('gform_entries_first_column_actions', array('GFPDF_Core_Model', 'pdf_link')));
		$this->assertEquals(10, has_action('gform_entry_info', array('GFPDF_Core_Model', 'detail_pdf_link')));
		$this->assertEquals(10, has_action('wp', array('GFPDF_Core_Model', 'process_exterior_pages')));			
		$this->assertEquals(10, has_action('gform_after_submission', array('GFPDF_Core_Model', 'gfpdfe_save_pdf')));				
		
	}

	public function test_filter_hooks() {	

		$this->assertEquals(10, has_filter('gfpdfe_pdf_template', array('PDF_Common', 'do_mergetags')));
		$this->assertEquals(10, has_filter('gfpdfe_pdf_template', 'do_shortcode'));

		$this->assertEquals(100, has_filter('gform_notification', array('GFPDF_Core_Model', 'gfpdfe_create_and_attach_pdf')));				
	}

	
}