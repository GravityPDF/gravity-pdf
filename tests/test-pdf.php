<?php

namespace GFPDF\Tests;
use GFPDF\Controller\Controller_PDF;
use GFPDF\Model\Model_PDF;
use GFPDF\View\View_PDF;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use WP_Error;
use WP_Rewrite;
use Exception;

/**
 * Test Gravity PDF Endpoint Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
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
 * Test the model / view / controller for the PDF Endpoint functionality
 * @since 4.0
 */
class Test_PDF extends WP_UnitTestCase
{

    /**
     * Our Settings Controller
     * @var Object
     * @since 4.0
     */
    public $controller;

    /**
     * Our Settings Model
     * @var Object
     * @since 4.0
     */
    public $model;

    /**
     * Our Settings View
     * @var Object
     * @since 4.0
     */
    public $view;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {

        /* run parent method */
        parent::setUp();

        /* Setup our test classes */
        $this->model = new Model_PDF();
        $this->view  = new View_PDF(array());

        $this->controller = new Controller_PDF($this->model, $this->view);
        $this->controller->init();

        /* Set up WP Factory so we can use it */
        $this->factory = new WP_UnitTest_Factory();
    }

    /**
     * Check if all the correct actions are applied
     * @since 4.0
     * @group pdf
     */
    public function test_actions() {
        $this->assertSame(10, has_action('init', array($this->controller, 'register_rewrite_rules')));
        $this->assertSame(5, has_action('parse_request', array($this->controller, 'process_legacy_pdf_endpoint')));
        $this->assertSame(10, has_action('parse_request', array($this->controller, 'process_pdf_endpoint')));
    }

    /**
     * Check if all the correct filters are applied
     * @since 4.0
     * @group pdf
     */
    public function test_filters() {
        $this->assertSame(10, has_filter('query_vars', array($this->controller, 'register_rewrite_tags')));

        $this->assertSame(1, has_filter('gfpdf_pdf_middleware', array($this->model, 'middle_logged_out_restriction')));
        $this->assertSame(2, has_filter('gfpdf_pdf_middleware', array($this->model, 'middle_logged_out_timeout')));
        $this->assertSame(3, has_filter('gfpdf_pdf_middleware', array($this->model, 'middle_auth_logged_out_user')));
        $this->assertSame(4, has_filter('gfpdf_pdf_middleware', array($this->model, 'middle_user_capability')));
    }

    /**
     * Check if correct GF entry owner is determined
     * @since 4.0
     * @group pdf
     */
    public function test_is_current_pdf_owner() {
        /* set up a user to test its privilages */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);
        wp_set_current_user($user_id);

        /* Set up a blank entry array */
        $entry = array(
            'created_by' => '',
            'ip'         => '',
        );

        $this->assertFalse($this->model->is_current_pdf_owner($entry));

        /* assign our user ID */
        $entry['created_by'] = $user_id;

        $this->assertTrue($this->model->is_current_pdf_owner($entry));
        $this->assertTrue($this->model->is_current_pdf_owner($entry, 'logged_in'));
        $this->assertFalse($this->model->is_current_pdf_owner($entry, 'logged_out'));

        /* logout and retest */
        wp_set_current_user(0);
        $this->assertFalse($this->model->is_current_pdf_owner($entry));
        $this->assertFalse($this->model->is_current_pdf_owner($entry, 'logged_in'));

        /* Set the IPs */
        $entry['ip'] = '197.64.12.40';
        $_SERVER['HTTP_CLIENT_IP'] = $entry['ip'];

        $this->assertTrue($this->model->is_current_pdf_owner($entry));
        $this->assertTrue($this->model->is_current_pdf_owner($entry, 'logged_out'));
        $this->assertFalse($this->model->is_current_pdf_owner($entry, 'logged_in'));
    }

    /**
     * Check if our logged out restrictions are being applied correctly
     * @since 4.0
     * @group pdf
     */
    public function test_middle_logged_out_restrictions() {
        global $gfpdf;

        /* Disable test and check results */
        $gfpdf->options->update_option('limit_to_admin', 'No');

        $this->assertTrue($this->model->middle_logged_out_restriction(true, '', ''));
        $this->assertTrue(is_wp_error($this->model->middle_logged_out_restriction(new WP_Error(''), '', '')));

        /* Enable our tests */
        $gfpdf->options->update_option('limit_to_admin', 'Yes');

        /* test if we are redirecting */
        try {
            wp_set_current_user(0);
            $this->model->middle_logged_out_restriction(true, '', '');
        } catch (Exception $e) {
            $this->assertEquals('Redirecting', $e->getMessage());
        }

        /* Test if logged in users are ignored */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);
        wp_set_current_user($user_id);
        $this->assertTrue($this->model->middle_logged_out_restriction(true, '', ''));
    }

    /**
     * Check if our logged out timeout restrictions are being applied correctly
     * @since 4.0
     * @group pdf
     */
    public function test_middle_logged_out_timeout() {
        global $gfpdf;

        /* Set up our testing data */
        $entry = array(
            'date_created' => date('Y-m-d H:i:s', strtotime('-32 minutes')),
            'ip'           => '197.64.12.40',
        );
        
        $_SERVER['HTTP_CLIENT_IP'] = $entry['ip'];

        /* Test we get a timeout error */
        $results = $this->model->middle_logged_out_timeout(true, $entry, '');
        $this->assertTrue(is_wp_error($results));
        $this->assertEquals('timeout_expired', $results->get_error_code());

        /* Test we get a auth redirect */
        $entry['created_by'] = 5;

        try {
            $this->model->middle_logged_out_timeout(true, $entry, '');
        } catch (Exception $e) {
            $this->assertEquals('Redirecting', $e->getMessage());
        }

        /* Update timeout settings and check again */
        $gfpdf->options->update_option('logged_out_timeout', '33');
        $this->assertTrue($this->model->middle_logged_out_timeout(true, $entry, ''));

        /* Check if the test should be skipped */
        $_SERVER['HTTP_CLIENT_IP'] = '12.123.123.124';
        $this->assertTrue($this->model->middle_logged_out_timeout(true, $entry, ''));
        $this->assertTrue(is_wp_error($this->model->middle_logged_out_timeout(new WP_Error(), $entry, '')));

        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);
        wp_set_current_user($user_id);
        $this->assertTrue($this->model->middle_logged_out_timeout(true, $entry, ''));
    }

    /**
     * Check if our logged out user has access to our PDF
     * @since 4.0
     * @group pdf
     */
    public function test_middle_auth_logged_out_user() {

         /* Set up our testing data */
        $entry = array(
            'ip'           => '197.64.12.40',
        );

        /* Check for WP Error */
        $this->assertTrue(is_wp_error($this->model->middle_auth_logged_out_user(true, $entry, '')));

        /* Check for redirect */
        $entry['created_by'] = 5;

        try {
            $this->model->middle_auth_logged_out_user(true, $entry, '');
        } catch (Exception $e) {
            $this->assertEquals('Redirecting', $e->getMessage());
        }

        /* Test that the middleware is skipped */
        $_SERVER['HTTP_CLIENT_IP'] = $entry['ip'];
        $this->assertTrue($this->model->middle_auth_logged_out_user(true, $entry, ''));

        unset($_SERVER['HTTP_CLIENT_IP']);
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);
        wp_set_current_user($user_id);
        $this->assertTrue($this->model->middle_auth_logged_out_user(true, $entry, ''));
    }

    /**
     * Check if our logged in user has access to our PDF
     * @since 4.0
     * @group pdf
     */
    public function test_middle_middle_user_capability() {
        global $current_user;

        /* Check for WP Error */
        $this->assertTrue(is_wp_error($this->model->middle_user_capability(new WP_Error(), '', '')));

        /* create subscriber and test access */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);
        wp_set_current_user($user_id);

        /* get the results */
        $results = $this->model->middle_user_capability(true, '', '');

        $this->assertTrue(is_wp_error($results));
        $this->assertEquals('access_denied', $results->get_error_code());

        /* Elevate user to administrator */
        $user = wp_get_current_user();
        $user->remove_role( 'subscriber' );
        $user->add_role( 'administrator' );

        $this->assertTrue($this->model->middle_user_capability(true, '', ''));

        /* Remove elevated user privilages and set the default capability 'gravityforms_view_entries' */
        $user->remove_role( 'administrator' );
        $user->add_role( 'subscriber' );

        /* Double check they have been removed */
        $results = $this->model->middle_user_capability(true, '', '');

        $this->assertTrue(is_wp_error($results));
        $this->assertEquals('access_denied', $results->get_error_code());

        /* Add default capability and test */
        $user->add_cap( 'gravityforms_view_entries' );
        $this->assertTrue($this->model->middle_user_capability(true, '', ''));
    }


}
