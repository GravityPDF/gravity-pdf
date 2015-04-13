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

class PDF_Licensing extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();

        /* Load our plugin functions */
        GFPDF_InstallUpdater::check_filesystem_api();
        GFPDF_InstallUpdater::maybe_deploy();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test the hooks used to execute plugin
     * @since 3.8
     * @group licensing
     */
    public function test_exists()
    {
        $this->assertTrue(class_exists('GFPDF_Plugin_Updater'));
    }

    /**
     * Test licensing hooks set
     * @since 3.8
     * @group licensing
     */
    public function test_hooks() {
        global $gfpdfe_data;

        $this->assertEquals(10, has_action('gfpdfe_addons', array($gfpdfe_data->license_model, 'init')));
    }

    private function &setup_addon_data($key = 'e87fb917b253d967d99fbd4237105b74', $type = 'inactive') {
        $addon = array(
            'id'              => 'pdf_overlay_development_toolkit',
            'license_key'     => $key,
            'license_expires' => '2016-04-13 04:46:09',
            'license_status'  => $type,
        );        

        $model = new GFPDF_License_Model();

        $licensing = array(
            'key'     => $addon['license_key'],
            'status'  => $addon['license_status'],
            'expires' => $addon['license_expires'],
        );

        /* set up the database */
        $model->update_license_information($licensing, $addon);

        /* verify the database */
        $this->assertEquals($addon['license_key'], get_option(sprintf('gfpdfe_addon_%s_license', $addon['id'])));
        $this->assertEquals($addon['license_expires'], get_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id'])));
        $this->assertEquals($addon['license_status'], get_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id'])));

        return $addon;
    }

    /**
     * Test the is_new_license() function in our licensing model
     * @since 3.8
     * @group licensing
     */
    public function test_is_new_license() {
        $model = new GFPDF_License_Model();

        /* set up temp add on */
        $addon = $this->setup_addon_data();

        $model->is_new_license('test', $addon);

        $this->assertEquals('e87fb917b253d967d99fbd4237105b74', $addon['license_key']);
        $this->assertEquals('', $addon['license_expires']);
        $this->assertEquals('', $addon['license_status']);
        $this->assertEquals('e87fb917b253d967d99fbd4237105b74', get_option(sprintf('gfpdfe_addon_%s_license', $addon['id'])));
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id'])));
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id'])));

        /* reset add on */
        $addon = $this->setup_addon_data();

        $model->is_new_license('', $addon);

        $this->assertEquals('', $addon['license_key']);
        $this->assertEquals('', $addon['license_expires']);
        $this->assertEquals('', $addon['license_status']);
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license', $addon['id'])));
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id'])));
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id'])));

        /* reset add on */
        $addon = $this->setup_addon_data('');

        $this->assertTrue($model->is_new_license('', $addon));        
    }

    /**
     * [test_addon description]
     * @since 3.8
     * @group licensing
     */
    public function test_addon() {
        $gfpdfe_data;

        $this->assertTrue(is_array($gfpdfe_data->addon));

        $gfpdfe_data->addon[] = 1;
        $gfpdfe_data->addon[] = 2;

        $this->assertEquals(2, sizeof($gfpdfe_data->addon));        
    }

    public function test_check_license() {
        /* check the expires and status keys get updated */
    }

    public function test_do_deactivate_license_key() {
        /* check status gets updated */
    }

    public function test_do_license_key_status_check() {

    }

    public function test_show_renewal_notice_on_plugin_page() {

    }    

    public function test_update_license_information() {
        /* create a dataprovider to test */
    }




}
