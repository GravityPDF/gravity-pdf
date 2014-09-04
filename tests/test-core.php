<?php

class PDFCore extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();		

		/* Load our plugin functions */
		GFPDF_Core::fully_loaded_admin();	

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
		$this->assertEquals(class_exists('GFForms'), true);
	}

	public function test_pdfextended_exists() {
		$this->assertEquals(class_exists('GFPDF_Core'), true);
		$this->assertEquals(class_exists('PDFGenerator'), true);
		$this->assertEquals(class_exists('PDF_Common'), true);	

		$this->assertEquals(class_exists('PDFRender'), true);
		$this->assertEquals(class_exists('GFPDFEntryDetail'), true);
	}

	public function test_gravityform_compatible() {
		global $gfpdfe_data;
		$this->assertEquals($gfpdfe_data->gf_is_compatible, true);
	}

	public function test_wp_compatible() {
		global $gfpdfe_data;
		$this->assertEquals($gfpdfe_data->wp_is_compatible, true);
	}

	public function test_mb_string_installed() {
		global $gfpdfe_data;
		$this->assertEquals($gfpdfe_data->mb_string_installed, true);
	}		

	public function test_gd_installed() {
		global $gfpdfe_data;
		$this->assertEquals($gfpdfe_data->gd_installed, true);
	}	

	public function test_ram_compatible() {
		global $gfpdfe_data;		
		
		$this->assertEquals($gfpdfe_data->ram_compatible, true);		
	}	

	public function test_major_compatibility() {
		$model = GFPDF_Core::$model;

		$this->assertEquals($model->check_major_compatibility(), true);
	}

	public function test_write_access() {
		$access_type = get_filesystem_method();		

		$this->assertEquals($access_type, 'direct');

		global $gfpdfe_data;
		$this->assertEquals($gfpdfe_data->automated, true);
	}

	public function test_fully_installed() {
		$this->assertEquals(GFPDF_Core_Model::is_fully_installed(), true);
	}
			
	
}

