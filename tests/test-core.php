<?php

class Test_PDFCore extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();		

		/* Load our plugin functions */
		GFPDF_InstallUpdater::check_filesystem_api();
		GFPDF_InstallUpdater::maybe_deploy();		
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_helperFiles() {		
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/data.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/notices.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/pdf-configuration-indexer.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/installation-update-manager.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/pdf-common.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/pdf-render.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'helper/pdf-entry-detail.php' );

	}

	public function test_coreFiles() {
		$this->assertFileExists( PDF_PLUGIN_DIR . 'pdf-settings.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'model/pdf.php' );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'model/settings.php' );
	}	

	public function test_gravityforms_exists() {		
		$this->assertTrue(class_exists('GFForms'));
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

		$this->assertEquals('direct', $access_type);

		global $gfpdfe_data;
		$this->assertTrue($gfpdfe_data->automated);
	}

	public function test_fully_installed() {
		$this->assertTrue(GFPDF_Core_Model::is_fully_installed());
	}

	public function test_configuration_file() {
		global $gfpdfe_data, $gfpdf;
		$this->assertFileExists($gfpdfe_data->template_site_location . 'configuration.php');				
		
		$this->assertEquals($gfpdf->disabled, false);						
	}	

	public function test_template_directory() {
		global $gfpdfe_data;

		$this->assertFileExists($gfpdfe_data->template_site_location . 'default-template.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'default-template-no-style.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'default-template-two-rows.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-template.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-basic-html01.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-backgrounds-and-borders02.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-images03.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-tables04.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-float-and-positioning05.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-header-and-footer_06.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-advanced-headers_07.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-different-page-size_08.php');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'example-watermark09.php');			

		$this->assertFileExists($gfpdfe_data->template_site_location . 'output');			
		$this->assertFileExists($gfpdfe_data->template_site_location . 'fonts');			
	}
			
	
}

