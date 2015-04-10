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

class PDF_Install_Update_Manager_Tests extends WP_UnitTestCase
{
    private $tmp;

    public function setUp()
    {
        parent::setUp();

        /* Load our plugin functions */
        GFPDF_InstallUpdater::check_filesystem_api();
        GFPDF_InstallUpdater::maybe_deploy();

        /* set up tmp variable */
        $this->tmp = ABSPATH . 'tmp/';
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->cleanup($this->tmp);
        $this->cleanup(ABSPATH . 'tmp2/');
    }

    /**
     * Clean up and then set up our
     * folder moving testing suite
     */
    private function setup_move_folder_test()
    {
        if(file_exists($this->tmp)) {
            $this->cleanup($this->tmp);
        }

        mkdir($this->tmp);
        mkdir($this->tmp.'test1');
        mkdir($this->tmp.'test2');
        mkdir($this->tmp.'/test2/test3');

        touch($this->tmp.'file1');
        touch($this->tmp.'file2');
        touch($this->tmp.'test1/file3');
        touch($this->tmp.'test2/file4');
        touch($this->tmp.'test2/file5');
        touch($this->tmp.'test2/test3/file6');

        $this->verify_folder_structure($this->tmp);
    }

    /**
     * Clean up our folder move testing suite
     * We don't care if the files don't exist where they should be so
     * we suppress errors
     */
    private function cleanup($path)
    {
        @unlink($path.'file1');
        @unlink($path.'file2');
        @unlink($path.'test1/file3');
        @unlink($path.'test2/file4');
        @unlink($path.'test2/file5');
        @unlink($path.'test2/test3/file6');
        @unlink($path.'extrafile');

        @rmdir($path.'/test2/test3');
        @rmdir($path.'test2');
        @rmdir($path.'test1');
        @rmdir($path);
    }

    /**
     * Create test for the GFPDF_InstallUpdater::pdf_extended_copy_directory() function
     * @group install-update
     */
    public function test_move_folder_system()
    {
        $this->setup_move_folder_test();

        /*
         * Setup and run our moving folder function 
         */
        $move_to = ABSPATH . 'tmp2/';

        GFPDF_InstallUpdater::pdf_extended_copy_directory($this->tmp, $move_to);

        /*
         * Run our first test
         */
        $this->verify_folder_structure($move_to);

        /*
         * Override the existing folder and test for newly added file 
         */
        touch($this->tmp . 'extrafile');
        GFPDF_InstallUpdater::pdf_extended_copy_directory($this->tmp, $move_to, true, true);

        /* Run our second test batch */
        $this->verify_folder_structure($move_to);
        $this->assertTrue(is_file($move_to . 'extrafile'));
        unlink($move_to . 'extrafile');
        unlink($this->tmp . 'extrafile');

        /*
         * Delete the source directory when doing the move 
         */
        GFPDF_InstallUpdater::pdf_extended_copy_directory($this->tmp, $move_to, true, true, true);
        $this->verify_folder_structure($move_to);

        $this->assertFalse(is_dir($this->tmp));
        $this->setup_move_folder_test();

        /*
         * Create permissions problems that prevent the files being moved 
         */
        $move_to = '/tmp2';
        $this->assertFalse(GFPDF_InstallUpdater::pdf_extended_copy_directory($this->tmp, $move_to));

    }

    private function verify_folder_structure($path)
    {
        $this->assertTrue(is_dir($path));
        $this->assertTrue(is_dir($path.'test1'));
        $this->assertTrue(is_dir($path.'test2'));
        $this->assertTrue(is_dir($path.'test2/test3'));

        $this->assertTrue(is_file($path.'file1'));
        $this->assertTrue(is_file($path.'file2'));
        $this->assertTrue(is_file($path.'test1/file3'));
        $this->assertTrue(is_file($path.'test2/file4'));
        $this->assertTrue(is_file($path.'test2/file5'));
        $this->assertTrue(is_file($path.'test2/test3/file6'));
    }
}
