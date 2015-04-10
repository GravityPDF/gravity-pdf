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
}
