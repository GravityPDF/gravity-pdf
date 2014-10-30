<?php

class Test_PDFCore extends WP_UnitTestCase {
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

	public function test_helperFiles() {
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/api.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/data.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/notices.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/pdf-configuration-indexer.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/installation-update-manager.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/pdf-common.php' );

	}

	public function test_coreFiles() {
		$this->assertFileExists( PDF_PLUGIN_DIR . 'pdf-settings.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'model/pdf.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'model/settings.php' );
	}	

	public function test_gravityforms_exists() {		
		$this->assertTrue(class_exists('GFForms'), true);
	}

	public function test_pdfextended_exists() {
		$this->assertTrue(class_exists('GFPDF_Core'));
		$this->assertTrue(class_exists('PDFGenerator'));
		$this->assertTrue(class_exists('PDF_Common'));	

		$this->assertTrue(class_exists('PDFRender'));
		$this->assertTrue(class_exists('GFPDFEntryDetail'));
	}

	public function test_gravityform_compatible() {
		global $gfpdfe_data;
		$this->assertTrue($gfpdfe_data->gf_is_compatible);
	}

	public function test_wp_compatible() {
		global $gfpdfe_data;
		$this->assertTrue($gfpdfe_data->wp_is_compatible);
	}

	public function test_mb_string_installed() {
		global $gfpdfe_data;
		$this->assertTrue($gfpdfe_data->mb_string_installed);
	}		

	public function test_gd_installed() {
		global $gfpdfe_data;
		$this->assertTrue($gfpdfe_data->gd_installed);
	}	

	public function test_ram_compatible() {
		global $gfpdfe_data;		
		
		$this->assertTrue($gfpdfe_data->ram_compatible);		
	}	

	public function test_major_compatibility() {
		$this->assertTrue(GFPDF_Core_Model::check_major_compatibility());
	}

	public function test_write_access() {
		$access_type = get_filesystem_method();		

		$this->assertEquals($access_type, 'direct');

		global $gfpdfe_data;
		$this->assertTrue($gfpdfe_data->automated);
	}

	public function test_fully_installed() {
		$this->assertTrue(GFPDF_Core_Model::is_fully_installed());
	}

	public function test_configuration_file() {
		global $gfpdfe_data, $gfpdf;
		$this->assertEquals(file_exists($gfpdfe_data->template_site_location . 'configuration.php'), true);				
		
		$this->assertEquals($gfpdf->disabled, false);				
		
	}	
			
	
}

