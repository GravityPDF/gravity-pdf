<?php 

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class PDF_Actions_And_Filters extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();	

		/* Load our plugin functions */
		GFPDF_InstallUpdater::check_filesystem_api();
		GFPDF_InstallUpdater::maybe_deploy();		
	}

	public function tearDown() {
		parent::tearDown();
	}				

	/**
	 * Test the hooks used to execute plugin 
	 * @group hooks
	 */
	public function test_actions_hook() {		
		$this->assertEquals(10,   has_action('init', array('GFPDF_Core', 'pdf_init')));
		$this->assertEquals(9999, has_action('admin_init', array('GFPDF_Core', 'fully_loaded_admin')));

		$this->assertEquals(10, has_action('wp_ajax_support_request', array('GFPDF_Settings_Model', 'gfpdf_support_request')));		

		$this->assertEquals(10, has_action('gform_entries_first_column_actions', array('GFPDF_Core_Model', 'pdf_link')));
		$this->assertEquals(10, has_action('gform_entry_info', array('GFPDF_Core_Model', 'detail_pdf_link')));
		$this->assertEquals(10, has_action('wp', array('GFPDF_Core_Model', 'process_exterior_pages')));			
		$this->assertEquals(10, has_action('gform_after_submission', array('GFPDF_Core_Model', 'gfpdfe_save_pdf')));				
		
	}

	/**
	 * Test the filters used to execute plugin 
	 * @group hooks
	 */
	public function test_filter_hooks() {	

		$this->assertEquals(10, has_filter('gfpdfe_pdf_template', array('PDF_Common', 'do_mergetags')));
		$this->assertEquals(10, has_filter('gfpdfe_pdf_template', 'do_shortcode'));

		$this->assertEquals(100, has_filter('gform_notification', array('GFPDF_Core_Model', 'gfpdfe_create_and_attach_pdf')));				
	}

	
}