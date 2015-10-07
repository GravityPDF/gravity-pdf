<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Shortcodes;
use GFPDF\Model\Model_Shortcodes;
use GFPDF\View\View_Shortcodes;

use WP_UnitTestCase;

/**
 * Test Gravity PDF Shortcode functionality
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
 * Test the model / view / controller for the Shortcode MVC
 * @since 4.0
 * @group shortcode
 */
class Test_Shortcode extends WP_UnitTestCase
{
    /**
     * Our Controller
     * @var Object
     * @since 4.0
     */
    public $controller;

    /**
     * Our Model
     * @var Object
     * @since 4.0
     */
    public $model;

    /**
     * Our View
     * @var Object
     * @since 4.0
     */
    public $view;

    /**
     * The WP Unit Test Set up function
     * @since 4.0
     */
    public function setUp() {
        global $gfpdf;

        /* run parent method */
        parent::setUp();

        /* Setup our test classes */
        $this->model = new Model_Shortcodes( $gfpdf->form, $gfpdf->log, $gfpdf->options );
        $this->view  = new View_Shortcodes( array() );

        $this->controller = new Controller_Shortcodes( $this->model, $this->view, $gfpdf->log );
        $this->controller->init();
    }

    /**
     * Test the appropriate filters are set up
     * @since 4.0
     */
    public function test_filters() {
        $this->assertEquals( 10, has_filter( 'gform_confirmation', array( $this->model, 'gravitypdf_confirmation' ) ) );
        $this->assertEquals( 10, has_filter( 'gform_notification', array( $this->model, 'gravitypdf_notification' ) ) );
        $this->assertEquals( 10, has_filter( 'gform_admin_pre_render', array( $this->model, 'gravitypdf_redirect_confirmation' ) ) );
    }

    /**
     * Test the appropriate shortcodes are set up
     * @since 4.0
     */
    public function test_shortcodes() {
        global $shortcode_tags;

        /* Check shortcode not set up */
        $this->assertTrue( isset( $shortcode_tags['gravitypdf']) );
    }

    /**
     * Test the gravitypdf shortcodes render as expected
     * @since 4.0
     */
    public function test_gravitypdf_shortcode() {

        $entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

        /* Test for a failed result */
        $this->assertEquals( '', $this->model->gravitypdf( array() ) );

        /* Authorise the current user */
        $user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
        $this->assertInternalType( 'integer', $user_id );
        wp_set_current_user( $user_id );

        /* Test for error */
        $this->assertNotFalse( strpos( $this->model->gravitypdf( array( 'entry' => $entry['id'] ) ), '<pre class="gravitypdf-error">' ) );
        $this->assertNotFalse( strpos( $this->model->gravitypdf( array( 'entry' => $entry['id'], 'id' => '555ad84787d7e' ) ), '<pre class="gravitypdf-error">' ) ); /* conditional logic error */

        /* Test for actual result */
        $this->assertNotFalse( strpos( $this->model->gravitypdf( array( 'entry' => $entry['id'], 'id' => '556690c67856b' ) ), 'Download PDF' ) );

        /* Test for configured results */
        $this->assertNotFalse( strpos( $this->model->gravitypdf( array( 'entry' => $entry['id'], 'id' => '556690c67856b', 'text' => 'View PDF' ) ), 'View PDF' ) );
        $this->assertFalse( strpos( $this->model->gravitypdf( array( 'entry' => $entry['id'], 'id' => '556690c67856b', 'type' => 'view' ) ), 'action=download' ) );
        $this->assertNotFalse( strpos( $this->model->gravitypdf( array( 'entry' => $entry['id'], 'id' => '556690c67856b' ) ), 'action=download' ) );
        $this->assertNotFalse( strpos( $this->model->gravitypdf( array( 'entry' => $entry['id'], 'id' => '556690c67856b', 'classes' => 'my-pdf-download-link' ) ), 'my-pdf-download-link' ) );

        /* Test for entry URL loading */
        $_GET['lid'] = $entry['id'];
        $this->assertNotFalse( strpos( $this->model->gravitypdf( array('id' => '556690c67856b' ) ), 'Download PDF' ) );

        $_GET['lid'] = '5000';
        $this->assertNotFalse( strpos( $this->model->gravitypdf( array('id' => '556690c67856b' ) ), '<pre class="gravitypdf-error">' ) );
    }

    /**
     * Test we're correctly handling the Gravity Forms text confirmation method and including the entry ID
     * @since 4.0
     */
    public function test_gravitypdf_confirmation() {
        
        /* Setup test data */
        $confirmation = 'Thanks for getting in touch. [gravitypdf id="555ad84787d7e"]';
        $form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
        $lead = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

        /* Check our entry ID is being automatically added */
        $results = $this->model->gravitypdf_confirmation( $confirmation, $form, $lead );
        $this->assertNotFalse( strpos( $results, '[gravitypdf id="555ad84787d7e" entry="'. $lead['id'] . '"]' ) );

        /* Check we don't modify the ID when it already exists */
        $confirmation = 'Thanks for getting in touch. [gravitypdf id="555ad84787d7e" entry="5000"]';
        $results = $this->model->gravitypdf_confirmation( $confirmation, $form, $lead );
        $this->assertNotFalse( strpos( $results, '[gravitypdf id="555ad84787d7e" entry="5000"]' ) );

        /* Check we pass when confirmation is an array */
        $results = $this->model->gravitypdf_confirmation( array( 'data' ), $form, $lead );
        $this->assertEquals( 'data', $results[0] );
    }

    /**
     * Test we're correctly handling the Gravity Forms notifications method and including the entry ID
     * @since 4.0
     */
    public function test_gravitypdf_notification() {
        
        /* Setup test data */
        $notification = array();
        $notification['message'] = 'Thanks for getting in touch. [gravitypdf id="555ad84787d7e"]';
        $form = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
        $lead = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

        /* Check our entry ID is being automatically added */
        $results = $this->model->gravitypdf_notification( $notification, $form, $lead );
        $this->assertNotFalse( strpos( $results, '[gravitypdf id="555ad84787d7e" entry="'. $lead['id'] . '"]' ) );

        /* Check we don't modify the ID when it already exists */
        $notification['message']  = 'Thanks for getting in touch. [gravitypdf id="555ad84787d7e" entry="5000"]';
        $results = $this->model->gravitypdf_notification( $notification, $form, $lead );
        $this->assertNotFalse( strpos( $results, '[gravitypdf id="555ad84787d7e" entry="5000"]' ) );

        /* Check we pass when the message key doesn't exist */
        $results = $this->model->gravitypdf_notification( 'Test', $form, $lead );
        $this->assertEquals( 'Test', $results );
    }

    /**
     * Test we can correctly update shortcode attributes easily
     * @since 4.0
     */
    public function test_add_shortcode_attr() {

        /* Setup our test data */
        $content = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/shortcode-data.json' ) ), true );

        $shortcodes = $this->model->get_shortcode_information( 'gravitypdf', $content );

        $code1 = $shortcodes[0];
        $code2 = $shortcodes[1];
        $code3 = $shortcodes[2];

        /* Check we can correctly replace attributes in code 1 */
        $results = $this->model->add_shortcode_attr( $code1, 'text', 'Get My PDF' );
        $this->assertEquals( '[gravitypdf text="Get My PDF"]', $results['shortcode'] );
        $this->assertEquals( ' text="Get My PDF"', $results['attr_raw'] );
        $this->assertEquals( 'Get My PDF', $results['attr']['text'] );

        $results = $this->model->add_shortcode_attr( $code2, 'class', 'class_here' );
        $this->assertEquals( '[gravitypdf id="1231241221" text="View PDF" type="view" class="class_here"]', $results['shortcode'] );
        $this->assertEquals( ' id="1231241221" text="View PDF" type="view" class="class_here"', $results['attr_raw'] );
        $this->assertEquals( 'class_here', $results['attr']['class'] );

        $results = $this->model->add_shortcode_attr( $code2, 'type', 'download' );
        $this->assertEquals( '[gravitypdf id="1231241221" text="View PDF" type="download"]', $results['shortcode'] );
        $this->assertEquals( ' id="1231241221" text="View PDF" type="download"', $results['attr_raw'] );
        $this->assertEquals( 'download', $results['attr']['type'] );

        $results = $this->model->add_shortcode_attr( $code3, 'entry', '{entry_id}' );
        $this->assertEquals( '[gravitypdf id="78454" classes="my-custom-class" entry="{entry_id}"]', $results['shortcode'] );
        $this->assertEquals( ' id="78454" classes="my-custom-class" entry="{entry_id}"', $results['attr_raw'] );
        $this->assertEquals( '{entry_id}', $results['attr']['entry'] );
    }

    /**
     * Verify we're replacing a shortcode with the correct URL for the redirect confirmation
     * @since 4.0
     */
    public function test_gravitypdf_redirect_confirmation() {
        global $wp_rewrite;

        /* Process fancy permalinks */
        $old_permalink_structure = get_option( 'permalink_structure' );
        $wp_rewrite->set_permalink_structure( '/%postname%/' );
        flush_rewrite_rules();

        /* Setup our redirect confirmation value */
        $_POST['form_confirmation_url'] = '[gravitypdf id="555ad84787d7e"]';

        /* Run the test */
        $this->model->gravitypdf_redirect_confirmation( '' );
        $this->assertEquals( home_url() . '/pdf/555ad84787d7e/{entry_id}/download/', $_POST['form_confirmation_url'] );

        /* Check for viewing URL */
        $_POST['form_confirmation_url'] = '[gravitypdf id="555ad84787d7e" type="view"]';

        $this->model->gravitypdf_redirect_confirmation( '' );
        $this->assertEquals( home_url() . '/pdf/555ad84787d7e/{entry_id}/', $_POST['form_confirmation_url'] );

        $wp_rewrite->set_permalink_structure( $old_permalink_structure );
        flush_rewrite_rules();

        /* Run the test */
        $_POST['form_confirmation_url'] = '[gravitypdf id="555ad84787d7e"]';
        $this->model->gravitypdf_redirect_confirmation( '' );
        $this->assertEquals( home_url() . '/?gpdf=1&pid=555ad84787d7e&lid={entry_id}&action=download', $_POST['form_confirmation_url'] );

        /* Check for viewing URL */
        $_POST['form_confirmation_url'] = '[gravitypdf id="555ad84787d7e" type="view"]';

        $this->model->gravitypdf_redirect_confirmation( '' );
        $this->assertEquals( home_url() . '/?gpdf=1&pid=555ad84787d7e&lid={entry_id}', $_POST['form_confirmation_url'] );

    }

    /**
     * Verify we can return a parsed version of the shortcode information
     * @since 4.0
     */
    public function test_get_shortcode_information() {
        $content = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/shortcode-data.json' ) ), true );

        $shortcodes = $this->model->get_shortcode_information( 'gravitypdf', $content );

        /* Test the results */
        $this->assertSame( 4, sizeof( $shortcodes ) );

        $this->assertEquals( '[gravitypdf]', $shortcodes[0]['shortcode'] );
        $this->assertEquals( '', $shortcodes[0]['attr_raw'] );
        $this->assertEquals( '', $shortcodes[0]['attr'] );

        $this->assertEquals( '[gravitypdf id="1231241221" text="View PDF" type="view"]', $shortcodes[1]['shortcode'] );
        $this->assertEquals( ' id="1231241221" text="View PDF" type="view"', $shortcodes[1]['attr_raw'] );
        $this->assertEquals( '1231241221', $shortcodes[1]['attr']['id'] );
        $this->assertEquals( 'View PDF', $shortcodes[1]['attr']['text'] );
        $this->assertEquals( 'view', $shortcodes[1]['attr']['type'] );

        $this->assertEquals( '[gravitypdf id="78454" classes="my-custom-class" entry="2000"]', $shortcodes[2]['shortcode'] );
        $this->assertEquals( ' id="78454" classes="my-custom-class" entry="2000"', $shortcodes[2]['attr_raw'] );
        $this->assertEquals( '78454', $shortcodes[2]['attr']['id'] );
        $this->assertEquals( 'my-custom-class', $shortcodes[2]['attr']['classes'] );
        $this->assertEquals( '2000', $shortcodes[2]['attr']['entry'] );

        $this->assertEquals( '[gravitypdf id="afawfjoa420204" classes="my-custom-class-2" entry="3000" text="View PDF" type="view"]', $shortcodes[3]['shortcode'] );
        $this->assertEquals( ' id="afawfjoa420204" classes="my-custom-class-2" entry="3000" text="View PDF" type="view"', $shortcodes[3]['attr_raw'] );
        $this->assertEquals( 'afawfjoa420204', $shortcodes[3]['attr']['id'] );
        $this->assertEquals( 'my-custom-class-2', $shortcodes[3]['attr']['classes'] );
        $this->assertEquals( '3000', $shortcodes[3]['attr']['entry'] );
        $this->assertEquals( 'View PDF', $shortcodes[3]['attr']['text'] );
        $this->assertEquals( 'view', $shortcodes[3]['attr']['type'] );
    }

    /**
     * Check our no entry ID view displays correctly
     * @since 4.0
     */
    public function test_no_entry_id() {
        $this->assertNotFalse( strpos( $this->view->no_entry_id(), 'No Gravity Form entry ID' ) );
    }

    /**
     * Check our invalid PDF view displays correctly
     * @since 4.0
     */
    public function test_invalid_pdf_config() {
        $this->assertNotFalse( strpos( $this->view->invalid_pdf_config(), 'Could not get Gravity PDF configuration' ) );
    }

    /**
     * Check our inactive PDF view displays correctly
     * @since 4.0
     */
    public function test_pdf_not_active() {
        $this->assertNotFalse( strpos( $this->view->pdf_not_active(), 'PDF link not displayed because PDF is inactive.' ) );
    }

    /**
     * Check our failed conditional logic view displays correctly
     * @since 4.0
     */
    public function test_conditional_logic_not_met() {
        $this->assertNotFalse( strpos( $this->view->conditional_logic_not_met(), 'PDF link not displayed because conditional logic requirements have not been met.' ) );
    }

    /**
     * Check our display GF shortcode view displays correctly
     * @since 4.0
     */
    public function test_display_gravitypdf_shortcode() {
        $this->assertNotFalse( strpos( $this->view->display_gravitypdf_shortcode(), '<a href="' ) );
    }
}
