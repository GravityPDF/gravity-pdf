<?php

namespace GFPDF\Tests;

use GFPDF\Model\Model_Form_Settings;

use GFAPI;
use GFForms;

use WP_Ajax_UnitTestCase;
use WPAjaxDieStopException;
use WPAjaxDieContinueException;



/**
 * Test Gravity AJAX Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

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

/**
*
* Tests all Ajax calls
* For speed, non ajax calls of class-ajax.php are tested in test-ajax-others.php
* Ajax tests are not marked risky when run in separate processes and wp_debug
* disabled. But, this makes tests slow so non ajax calls are kept separate
*
* @group ajax
* @runTestsInSeparateProcesses
*
*/
class Test_PDF_Ajax extends WP_Ajax_UnitTestCase
{

    /**
     * The Gravity Form ID assigned to the imported form
     * @var Integer
     * @since 4.0
     */
    public $form_id;

    /**
     * The Gravity Form PDF Settings ID loaded into the $form
     * @var Integer
     * @since 4.0
     */
    public $pid = '555ad84787d7e';

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        parent::setUp();

        /* Remove temporary tables which causes problems with GF */
        remove_all_filters( 'query', 10 );
        GFForms::setup_database();
        
        $this->import_form();
    }

    /**
     * Load the JSON data and import it into Gravity Forms
     * @since 4.0
     */
    private function import_form() {
        $json = json_decode(file_get_contents( dirname( __FILE__ ) . '/json/form-settings.json' ), true);
        $this->form_id = GFAPI::add_form($json);
    }

    /**
     * Test our Gravity Forms PDF Settings configuration state change
     * @since 4.0
     * @todo test correct permissions
     */
    public function test_change_state_pdf_setting() {
        global $gfpdf;

        /* set up our data provider */
        $model = new Model_Form_Settings( $gfpdf->form, $gfpdf->log, $gfpdf->data, $gfpdf->options, $gfpdf->misc, $gfpdf->notices );

        /* set up our post data and role */
        $this->_setRole( 'administrator' );
        $_POST['fid'] = 0;
        $_POST['pid'] = $this->pid;

        /**
         * Check for nonce failure
         */
        try {
            $this->_handleAjax( 'gfpdf_change_state' );
        } catch ( WPAjaxDieStopException $e ) {
            /* do nothing (error expected) */
        }

        $this->assertEquals( '401', $e->getMessage() );

        /**
         * Check for update failure (invalid form ID)
         */
        $_POST['nonce'] = wp_create_nonce("gfpdf_state_nonce_{$_POST['fid']}_{$_POST['pid']}");

        try {
            $this->_handleAjax( 'gfpdf_change_state' );
        } catch ( WPAjaxDieStopException $e ) {
            /* do nothing (error expected) */
        }

        $this->assertEquals( '500', $e->getMessage() );

        /**
         * Set up a real response
         */
        $_POST['fid']   = $this->form_id;
        $_POST['nonce'] = wp_create_nonce("gfpdf_state_nonce_{$_POST['fid']}_{$_POST['pid']}");

        try {
            $this->_handleAjax( 'gfpdf_change_state' );
        } catch ( WPAjaxDieContinueException $e ) {
            /* do nothing (error expected) */
        }

        /* Get the response */
        $response = json_decode( $this->_last_response, true );

        /* Test the response is accurate */
        $this->assertArrayHasKey('state', $response);
        $this->assertArrayHasKey('src', $response);
        $this->assertEquals('Inactive', $response['state']);

        /* Test the function performed correctly */
        unset( $gfpdf->data->form_settings );
        $pdf   = $model->get_pdf($this->form_id, $this->pid);
        $this->assertFalse($pdf['active']);

        /* reset the last response */
        $this->_last_response = '';

        /**
         * Reverse the process
         */
        try {
            $this->_handleAjax( 'gfpdf_change_state' );
        } catch ( WPAjaxDieContinueException $e ) {
            /* do nothing (error expected) */
        }

        /* Get the response */
        $response = json_decode( $this->_last_response, true );

        /* Test the response is accurate */
        $this->assertArrayHasKey('state', $response);
        $this->assertArrayHasKey('src', $response);
        $this->assertEquals('Active', $response['state']);

        /* Test the function performed correctly */
        unset( $gfpdf->data->form_settings );
        $pdf = $model->get_pdf($this->form_id, $this->pid);
        $this->assertTrue($pdf['active']);
    }

    /**
     * Test our Gravity Forms PDF Settings configuration duplication functionality
     * @since 4.0
     * @todo test correct permissions
     */
    public function test_duplicate_gf_pdf_settings() {
        global $gfpdf;

        /* set up our data provider */
        $model = new Model_Form_Settings( $gfpdf->form, $gfpdf->log, $gfpdf->data, $gfpdf->options, $gfpdf->misc, $gfpdf->notices );

        /* set up our post data and role */
        $this->_setRole( 'administrator' );
        $_POST['fid'] = 0;
        $_POST['pid'] = $this->pid;

        /**
         * Check for nonce failure
         */
        try {
            $this->_handleAjax( 'gfpdf_list_duplicate' );
        } catch ( WPAjaxDieStopException $e ) {
            /* do nothing (error expected) */
        }

        $this->assertEquals( '401', $e->getMessage() );

        /**
         * Check for update failure (invalid form ID)
         */
        $_POST['nonce'] = wp_create_nonce("gfpdf_duplicate_nonce_{$_POST['fid']}_{$_POST['pid']}");

        try {
            $this->_handleAjax( 'gfpdf_list_duplicate' );
        } catch ( WPAjaxDieStopException $e ) {
            /* do nothing (error expected) */
        }

        $this->assertEquals( '500', $e->getMessage() );

        /**
         * Set up a real response
         */
        $_POST['fid']   = $this->form_id;
        $_POST['nonce'] = wp_create_nonce("gfpdf_duplicate_nonce_{$_POST['fid']}_{$_POST['pid']}");

        try {
            $this->_handleAjax( 'gfpdf_list_duplicate' );
        } catch ( WPAjaxDieContinueException $e ) {
            /* do nothing (error expected) */
        }

        /* Get the response */
        $response = json_decode( $this->_last_response, true );

        /* Test the response is accurate */
        $this->assertArrayHasKey('msg', $response);
        $this->assertArrayHasKey('pid', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('dup_nonce', $response);
        $this->assertArrayHasKey('del_nonce', $response);
        $this->assertArrayHasKey('state_nonce', $response);
        

        /* Test the function performed correctly */
        unset( $gfpdf->data->form_settings );
        $pdf1   = $model->get_pdf($this->form_id, $this->pid);
        $pdf2   = $model->get_pdf($this->form_id, $response['pid']);
        
        $this->assertEquals($pdf1['name'] . ' (copy)', $pdf2['name']);
        $this->assertEquals($pdf1['template'], $pdf2['template']);
        $this->assertEquals($pdf1['filename'], $pdf2['filename']);

        /* reset the last response */
        $this->_last_response = '';
    }

    /**
     * Test our Gravity Forms PDF Settings configuration duplication functionality
     * @since 4.0
     * @todo test correct permissions
     */
    public function test_delete_gf_pdf_setting() {
        global $gfpdf;

        /* set up our data provider */
        $model = new Model_Form_Settings( $gfpdf->form, $gfpdf->log, $gfpdf->data, $gfpdf->options, $gfpdf->misc, $gfpdf->notices );

        /* test configuration exists already */
        $pdf   = $model->get_pdf($this->form_id, $this->pid);
        $this->assertEquals('My First PDF Template', $pdf['name']);

        /* set up our post data and role */
        $this->_setRole( 'administrator' );
        $_POST['fid'] = 0;
        $_POST['pid'] = $this->pid;

        /**
         * Check for nonce failure
         */
        try {
            $this->_handleAjax( 'gfpdf_list_delete' );
        } catch ( WPAjaxDieStopException $e ) {
            /* do nothing (error expected) */
        }

        $this->assertEquals( '401', $e->getMessage() );

        /**
         * Check for update failure (invalid form ID)
         */
        $_POST['nonce'] = wp_create_nonce("gfpdf_delete_nonce_{$_POST['fid']}_{$_POST['pid']}");

        try {
            $this->_handleAjax( 'gfpdf_list_delete' );
        } catch ( WPAjaxDieStopException $e ) {
            /* do nothing (error expected) */
        }

        $this->assertEquals( '500', $e->getMessage() );

        /**
         * Set up a real response
         */
        $_POST['fid']   = $this->form_id;
        $_POST['nonce'] = wp_create_nonce("gfpdf_delete_nonce_{$_POST['fid']}_{$_POST['pid']}");

        try {
            $this->_handleAjax( 'gfpdf_list_delete' );
        } catch ( WPAjaxDieContinueException $e ) {
            /* do nothing (error expected) */
        }

        /* Get the response */
        $response = json_decode( $this->_last_response, true );

        /* Test the response is accurate */
        $this->assertArrayHasKey('msg', $response);
        
        /* Test the function performed correctly */
        unset( $gfpdf->data->form_settings );
        $pdf   = $model->get_pdf($this->form_id, $this->pid);
        $this->assertTrue( is_wp_error($pdf) );
    }
}
