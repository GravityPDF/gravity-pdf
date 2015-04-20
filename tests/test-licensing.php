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
    public function test_hooks()
    {
        global $gfpdfe_data;

        $this->assertEquals(10, has_action('gfpdfe_addons', array($gfpdfe_data->license_model, 'init')));
    }

    /**
     * Setup our faux $addon information for use in our test suite
     * Also run unit tests to ensure our database correctly sets the licensing information
     * @param  string $key  The license key that should be set
     * @param  string $type The license status
     * @return array        The finalised $addon array
     */
    private function &setup_addon_data($key = 'e87fb917b253d967d99fbd4237105b74', $type = 'inactive')
    {
        $addon = array(
            'name'            => 'PDF Overlay Development Toolkit',
            'id'              => 'pdf_overlay_development_toolkit',
            'license_key'     => $key,
            'license_expires' => '2015-04-13 04:46:09',
            'license_status'  => $type,
            'basename'        => 'test',
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
     * Setup our mock API caller so that we never contact the third-party API
     * You can pass in your own object which the mock up will return or we'll provide a valid or invalid response 
     * @param  object  $testObject If you want to return a specific response pass it in as an object here 
     * @param  boolean $error      Use this option if you want to return an error response for testing
     * @return object  The mock object is returned
     */
    private function mock_api_call($testObject = null, $error = false)
    {
        /* mock the 'call_api' function */
        $mock = $this->getMockBuilder('GFPDF_License_Model')
                     ->setMethods(array('call_api'))
                     ->getMock();

        /* is the user don't have a specific object they want returned ... */
        if (!$testObject) {
            if (!$error) {
                /* set up a valid response */
                $testObject = new stdClass();
                $testObject->license = 'valid';
                $testObject->expires = '2017-04-13 04:46:09';
            } else {
                /* set up a error response */
                $testObject = new stdClass();
                $testObject->error = 'missing';
                $testObject->expires = '';
            }
        }

        /* ensure the method returns the valid response */
        $mock->method('call_api')
             ->willReturn($testObject);

        return $mock;
    }

    /**
     * Test the is_new_license() function in our licensing model
     * @since 3.8
     * @group licensing
     */
    public function test_is_new_license()
    {
        $model = new GFPDF_License_Model();

        /* set up temp add on */
        $addon = $this->setup_addon_data();

        /* run our method */
        $model->is_new_license('test', $addon);

        /* do our assertions */
        $this->assertEquals('e87fb917b253d967d99fbd4237105b74', $addon['license_key']);
        $this->assertEquals('', $addon['license_expires']);
        $this->assertEquals('', $addon['license_status']);
        $this->assertEquals('e87fb917b253d967d99fbd4237105b74', get_option(sprintf('gfpdfe_addon_%s_license', $addon['id'])));
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id'])));
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id'])));

        /* reset add on */
        $addon = $this->setup_addon_data();

        /* run our method */
        $model->is_new_license('', $addon);

        /* run our assertions */
        $this->assertEquals('', $addon['license_key']);
        $this->assertEquals('', $addon['license_expires']);
        $this->assertEquals('', $addon['license_status']);
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license', $addon['id'])));
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id'])));
        $this->assertEquals('', get_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id'])));

        /* reset add on */
        $addon = $this->setup_addon_data('');

        /* run our assertions again */
        $this->assertTrue($model->is_new_license('', $addon));
    }

    /**
     * Check our addon array information is being correctly added to our data class 
     * @since 3.8
     * @group licensing
     */
    public function test_addon()
    {
        global $gfpdfe_data;

        $this->assertTrue(is_array($gfpdfe_data->addon));

        $gfpdfe_data->addon[] = 1;
        $gfpdfe_data->addon[] = 2;

        $this->assertEquals(2, sizeof($gfpdfe_data->addon));
    }

    /**
     * Check that our license activation method produces the correct results 
     * @since 3.8
     * @group licensing
     */
    public function test_activate_license()
    {
        /* set up our addon data */
        $addon = $this->setup_addon_data();

        /* generate a successfull API call mock */
        $mock = $this->mock_api_call();

        /**
         * run our basic assertion
         */
        $this->assertFalse($mock->activate_license('', $addon));
        $this->assertTrue($mock->activate_license($addon['license_key'], $addon));

        /* Check our license information updates correctly */
        $this->assertEquals('2017-04-13 04:46:09', $addon['license_expires']);
        $this->assertEquals('valid', $addon['license_status']);

        /**
         * Ensure an error is thrown correctly
         */
        $mock = $this->mock_api_call(null, true);

        /* call our error mock */
        $results = $mock->activate_license($addon['license_key'], $addon);

        /* check the results are accurate */
        $this->assertTrue(is_wp_error($results));
        $this->assertEquals('license_validation_error', $results->get_error_code());

        /* Check our license information updates correctly */
        $this->assertEquals('', $addon['license_expires']);
        $this->assertEquals('missing', $addon['license_status']);

        /**
         * Test if API cannot be reached
         * We'll need to reset the mock for this
         */
        $testObject = new WP_Error('unreachable', 'Cannot contact API');

        /* generate our mock based on $testObject */
        $mock = $this->mock_api_call($testObject);

        /* run the query */
        $results = $mock->activate_license($addon['license_key'], $addon);

        /* check results return correctly */
        $this->assertTrue(is_wp_error($results));
        $this->assertEquals('unreachable', $results->get_error_code());
    }

    /**
     * Check that our license deactivation method produces the correct results 
     * @since 3.8
     * @group licensing
     */
    public function test_do_deactivate_license_key()
    {
        /* check status gets updated */
        /* set up our addon data */
        $addon = $this->setup_addon_data();

        $testObject = new stdClass();
        $testObject->license = 'deactivated';

        /* generate a successfull API call mock */
        $mock = $this->mock_api_call($testObject);

        /**
         * check that function won't run if license key isn't passed
         */
        $key1 = array();
        $key2 = array('license_key' => '');

        $this->assertFalse($mock->do_deactivate_license_key($key1));
        $this->assertFalse($mock->do_deactivate_license_key($key2));

        /**
         * check method correctly executes
         */
        $mock->do_deactivate_license_key($addon);
        $this->assertEquals('deactivated', $addon['license_status']);

        /**
         * check errors are correctly thrown
         */

        /* reset mock object and addon */
        $addon = $this->setup_addon_data();
        $testObject->license = 'not-deactivated';
        $mock = $this->mock_api_call($testObject);

        /* run our error call */
        $results = $mock->do_deactivate_license_key($addon);

        /* run assertions */
        $this->assertTrue(is_wp_error($results));
        $this->assertEquals('edd_api_deactivation_error', $results->get_error_code());

        /**
         * Test if API cannot be reached
         * We'll need to reset the mock for this
         */
        $testObject = new WP_Error('unreachable', 'Cannot contact API');

        /* generate our mock based on $testObject */
        $mock = $this->mock_api_call($testObject);

        /* run the query */
        $results = $mock->do_deactivate_license_key($addon);

        /* check results return correctly */
        $this->assertTrue(is_wp_error($results));
        $this->assertEquals('unreachable', $results->get_error_code());
    }

    /**
     * Check that our license key status check method produces the correct results 
     * @since 3.8
     * @group licensing
     */
    public function test_do_license_key_status_check()
    {
        /* set up our addon data */
        $addon = $this->setup_addon_data();

        /**
         * generate a successfull API call mock
         */
        $mock = $this->mock_api_call();

        /**
         * check that function won't run if license key isn't passed
         */
        $key1 = array();
        $key2 = array('license_key' => '');

        $this->assertFalse($mock->do_license_key_status_check($key1));
        $this->assertFalse($mock->do_license_key_status_check($key2));

        /**
         * check method correctly executes
         */
        $mock->do_license_key_status_check($addon);

        /* Check our license information updates correctly */
        $this->assertEquals('2017-04-13 04:46:09', $addon['license_expires']);
        $this->assertEquals('valid', $addon['license_status']);

        /**
         * generate an error API call mock
         */
        $mock = $this->mock_api_call(null, true);

         /* call our error mock */
        $results = $mock->do_license_key_status_check($addon);

        /* check the results are accurate */
        $this->assertEquals('', $addon['license_expires']);
        $this->assertEquals('missing', $addon['license_status']);

        /**
         * Test if API cannot be reached
         * We'll need to reset the mock for this
         */
        $testObject = new WP_Error('unreachable', 'Cannot contact API');

        /* generate our mock based on $testObject */
        $mock = $this->mock_api_call($testObject);

        /* run the query */
        $results = $mock->do_license_key_status_check($addon);

        /* check results return correctly */
        $this->assertTrue(is_wp_error($results));
        $this->assertEquals('unreachable', $results->get_error_code());
    }

    /**
     * Test that our renewal notice is correctly applied
     * @var string $expires The date string when the license is set to expire 
     * @var boolean/integer $expected The expected results from has_action()
     * @since 3.8
     * @group licensing
     * @dataProvider renewal_notice_data_provider
     */
    public function test_show_renewal_notice_on_plugin_page($expires, $expected)
    {
        global $gfpdfe_data;

        /* set up add on testing */
        $addon = $this->setup_addon_data();
        $addon['license_expires'] = $expires;

        /* assign test addon to renewal notice */
        $gfpdfe_data->addon = array($addon);

        /* load class */
        $model = new GFPDF_License_Model();

        /* run function */
        $this->invokeMethod($model, 'show_renewal_notice_on_plugin_page');

        /* test results */
        $this->assertEquals($expected, has_action('after_plugin_row_'.$addon['basename'], array('GFPDF_Notices', 'display_plugin_renewal_notice')));

        /* reset */
        remove_action('after_plugin_row_'.$addon['basename'], array('GFPDF_Notices', 'display_plugin_renewal_notice'));
    }

    /**
     * The data provider for the renewal notice
     * @return array The data to be tested
     * @since 3.8
     */
    public function renewal_notice_data_provider()
    {
        return array(
            array(date('Y-m-d'), 10),
            array(date('Y-m-d', strtotime('-1 day')), 10),
            array(date('Y-m-d', strtotime('+1 day')), 10),
            array(date('Y-m-d', strtotime('-5 days')), 10),
            array(date('Y-m-d', strtotime('+5 days')), 10),
            array(date('Y-m-d', strtotime('+25 days')), 10),
            array(date('Y-m-d', strtotime('+40 days')), false),
            array(date('Y-m-d', strtotime('-1 months')), 10),
            array(date('Y-m-d', strtotime('-6 months')), 10),
            array(date('Y-m-d', strtotime('+1 month')), 10),
            array(date('Y-m-d', strtotime('+1 month 1 day')), false),
            array(date('Y-m-d', strtotime('+2 months')), false),
            array(date('Y-m-d', strtotime('+7 months')), false),
            array(date('Y-m-d', strtotime('+1 year')), false),
            array(date('Y-m-d', strtotime('+2 year')), false),
        );
    }

    /**
     * Check that our $addon array and database are correctly being updated 
     * and stay in sync when changes are made
     * @var array The license information to update
     * @since 3.8
     * @group licensing
     * @dataProvider update_license_data_provider
     */
    public function test_update_license_information($data)
    {
        /* set up add on testing */
        $addon = $this->setup_addon_data();

        /* load class */
        $model = new GFPDF_License_Model();

        /* run our function */
        $model->update_license_information($data, $addon);

        /* run our assertions */
        if (isset($data['key'])) {
            $this->assertEquals($data['key'], $addon['license_key']);
            $this->assertEquals($data['key'], get_option(sprintf('gfpdfe_addon_%s_license', $addon['id'])));
        } else {
            $this->assertEquals($addon['license_key'], get_option(sprintf('gfpdfe_addon_%s_license', $addon['id'])));
        }

        if (isset($data['status'])) {
            $this->assertEquals($data['status'], $addon['license_status']);
            $this->assertEquals($data['status'], get_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id'])));
        } else {
            $this->assertEquals($addon['license_status'], get_option(sprintf('gfpdfe_addon_%s_license_status', $addon['id'])));
        }

        if (isset($data['expires'])) {
            $this->assertEquals($data['expires'], $addon['license_expires']);
            $this->assertEquals($data['expires'], get_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id'])));
        } else {
            $this->assertEquals($addon['license_expires'], get_option(sprintf('gfpdfe_addon_%s_license_expires', $addon['id'])));
        }
    }

    /**
     * [update_license_data_provider description]
     * @return array The data to be tested
     * @since 3.8
     */
    public function update_license_data_provider()
    {
        return array(
            array(
                array(
                    'key'     => '12345',
                    'status'  => 'valid',
                    'expires' => '2015-05-10',
                ),
            ),

            array(
                array(
                    'key'     => '',
                    'status'  => '',
                    'expires' => '',
                ),
            ),

            array(
                array(
                    'key'     => '12345',
                ),
            ),

            array(
                array(
                    'status'     => 'invalid',
                ),
            ),

            array(
                array(
                    'expires'     => '2015-08-15',
                ),
            ),

            array(
                array(
                    'status'  => 'valid',
                    'expires' => '',
                ),
            ),
        );
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
