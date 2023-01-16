<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_Shortcodes;
use GFPDF\Helper\Helper_Url_Signer;
use GFPDF\Model\Model_Shortcodes;
use GFPDF\View\View_Shortcodes;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Shortcode functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the model / view / controller for the Shortcode MVC
 *
 * @since 4.0
 * @group shortcodes
 */
class Test_Shortcode extends WP_UnitTestCase {

	/**
	 * Our Controller
	 *
	 * @var Controller_Shortcodes
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Model
	 *
	 * @var Model_Shortcodes
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our View
	 *
	 * @var View_Shortcodes
	 *
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		/* Setup our test classes */
		$this->model = new Model_Shortcodes( $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->misc, new Helper_Url_Signer() );
		$this->view  = new View_Shortcodes( [] );

		$this->controller = new Controller_Shortcodes( $this->model, $this->view, $gfpdf->log );
		$this->controller->init();

		$options                = $gfpdf->options;
		$settings               = $options->get_settings();
		$settings['debug_mode'] = 'Yes';
		$options->update_settings( $settings );
	}

	/**
	 * Test the appropriate filters are set up
	 *
	 * @since 4.0
	 */
	public function test_filters() {
		$this->assertEquals(
			10,
			has_filter(
				'gform_admin_pre_render',
				[
					$this->model,
					'gravitypdf_redirect_confirmation',
				]
			)
		);
	}

	/**
	 * Test the appropriate shortcodes are set up
	 *
	 * @since 4.0
	 */
	public function test_shortcodes() {
		global $shortcode_tags;

		/* Check shortcode not set up */
		$this->assertTrue( isset( $shortcode_tags['gravitypdf'] ) );
	}

	/**
	 * Test the gravitypdf shortcodes render as expected
	 *
	 * @since 4.0
	 */
	public function test_gravitypdf_shortcode() {

		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		/* Test for a failed result */
		$this->assertEquals( '', $this->model->process( [] ) );

		/* Authorise the current user */
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );

		/* Test for error */
		$this->assertStringContainsString( '<pre class="gravitypdf-error">', $this->model->process( [ 'entry' => $entry['id'] ] ) );
		$this->assertStringContainsString(
			'<pre class="gravitypdf-error">',
			$this->model->process(
				[
					'entry' => $entry['id'],
					'id'    => '555ad84787d7e',
				]
			)
		); /* conditional logic error */

		/* Test for actual result */
		$this->assertStringContainsString(
			'Download PDF',
			$this->model->process(
				[
					'entry' => $entry['id'],
					'id'    => '556690c67856b',
				]
			)
		);

		$this->assertStringContainsString(
			'href="',
			$this->model->process(
				[
					'entry' => $entry['id'],
					'id'    => '556690c67856b',
				]
			)
		);

		/* Test for configured results */
		$this->assertStringContainsString(
			'View PDF',
			$this->model->process(
				[
					'entry' => $entry['id'],
					'id'    => '556690c67856b',
					'text'  => 'View PDF',
				]
			)
		);
		$this->assertStringNotContainsString(
			'action=download',
			$this->model->process(
				[
					'entry' => $entry['id'],
					'id'    => '556690c67856b',
					'type'  => 'view',
				]
			)
		);
		$this->assertStringContainsString(
			'action=download',
			$this->model->process(
				[
					'entry' => $entry['id'],
					'id'    => '556690c67856b',
				]
			)
		);
		$this->assertStringContainsString(
			'my-pdf-download-link',
			$this->model->process(
				[
					'entry'   => $entry['id'],
					'id'      => '556690c67856b',
					'classes' => 'my-pdf-download-link',
				]
			)
		);

		/* Test our print attribute works as intended */
		$this->assertStringNotContainsString(
			'print=1',
			$this->model->process(
				[
					'entry' => $entry['id'],
					'id'    => '556690c67856b',
				]
			)
		);

		$this->assertStringContainsString(
			'print=1',
			$this->model->process(
				[
					'entry' => $entry['id'],
					'id'    => '556690c67856b',
					'print' => 'true',
				]
			),
		);

		/* Test for raw URL */
		$url = $this->model->process(
			[
				'entry' => $entry['id'],
				'id'    => '556690c67856b',
				'raw'   => '1',
			]
		);

		$this->assertStringContainsString( '?gpdf=1&pid=556690c67856b&lid=1&action=download', $url );
		$this->assertStringNotContainsString( '<a href=', $url );
		$this->assertStringNotContainsString( 'Download PDF', $url );

		/* Test for signed URL */
		$url1 = $this->model->process(
			[
				'entry'  => $entry['id'],
				'id'     => '556690c67856b',
				'signed' => '1',
			]
		);

		$this->assertStringContainsString( '&#038;signature=', $url1 );
		$this->assertStringContainsString( '&#038;expires=', $url1 );

		/* Test signed URL expiry */
		parse_str(
			parse_url(
				$this->model->process(
					[
						'entry'  => $entry['id'],
						'id'     => '556690c67856b',
						'signed' => '1',
						'raw'    => '1',
					]
				),
				PHP_URL_QUERY
			),
			$url
		);

		$this->assertGreaterThan( strtotime( '+19 minutes' ), $url['expires'] );
		$this->assertLessThan( strtotime( '+21 minutes' ), $url['expires'] );

		parse_str(
			parse_url(
				$this->model->process(
					[
						'entry'   => $entry['id'],
						'id'      => '556690c67856b',
						'signed'  => '1',
						'expires' => '1 day',
						'raw'     => '1',
					]
				),
				PHP_URL_QUERY
			),
			$url
		);

		$this->assertGreaterThan( strtotime( '+23 hours' ), $url['expires'] );
		$this->assertLessThan( strtotime( '+25 hours' ), $url['expires'] );

		/* Test for entry URL loading */
		$_GET['lid'] = $entry['id'];
		$this->assertStringContainsString( 'Download PDF', $this->model->process( [ 'id' => '556690c67856b' ] ) );

		$_GET['lid'] = '5000';
		$this->assertStringContainsString( '<pre class="gravitypdf-error">', $this->model->process( [ 'id' => '556690c67856b' ] ) );

		/* Test we get no error when they are disabled globally */
		global $gfpdf;
		$options                = $gfpdf->options;
		$settings               = $options->get_settings();
		$settings['debug_mode'] = 'No';
		$options->update_settings( $settings );

		$this->assertStringNotContainsString( '<pre class="gravitypdf-error">', $this->model->process( [ 'id' => '556690c67856b' ] ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Test we're correctly handling the Gravity Forms text confirmation method and including the entry ID
	 *
	 * @since 4.0
	 */
	public function test_gravitypdf_confirmation() {

		/* Setup test data */
		$confirmation         = 'Thanks for getting in touch. [gravitypdf id="555ad84787d7e"]';
		$form                 = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$form['confirmation'] = $form['confirmations']['54bca34973cdd'];
		$lead                 = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		/* Check our entry ID is being automatically added */
		$results = $this->model->gravitypdf_confirmation( $confirmation, $form, $lead );
		$this->assertStringContainsString( '[gravitypdf id="555ad84787d7e" entry="' . $lead['id'] . '"]', $results );

		/* Check we don't modify the ID when it already exists */
		$confirmation = 'Thanks for getting in touch. [gravitypdf id="555ad84787d7e" entry="5000"]';
		$results      = $this->model->gravitypdf_confirmation( $confirmation, $form, $lead );
		$this->assertStringContainsString( '[gravitypdf id="555ad84787d7e" entry="5000"]', $results );

		/* Check we pass when confirmation is not a message */
		$form['confirmation']['type'] = 'redirect';
		$results                      = $this->model->gravitypdf_confirmation( [ 'data' ], $form, $lead );
		$this->assertEquals( 'data', $results[0] );
	}

	/**
	 * Test we're correctly handling the Gravity Forms notifications method and including the entry ID
	 *
	 * @since 4.0
	 */
	public function test_gravitypdf_notification() {

		/* Setup test data */
		$notification            = [];
		$notification['message'] = 'Thanks for getting in touch. [gravitypdf id="555ad84787d7e"]';
		$form                    = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$lead                    = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		/* Check our entry ID is being automatically added */
		$results = $this->model->gravitypdf_notification( $notification, $form, $lead );
		$this->assertStringContainsString( '[gravitypdf id="555ad84787d7e" entry="' . $lead['id'] . '"]', $results['message'] );

		/* Check we don't modify the ID when it already exists */
		$notification['message'] = 'Thanks for getting in touch. [gravitypdf id="555ad84787d7e" entry="5000"]';
		$results                 = $this->model->gravitypdf_notification( $notification, $form, $lead );
		$this->assertStringContainsString( '[gravitypdf id="555ad84787d7e" entry="5000"]', $results['message'] );

		/* Check we pass when the message key doesn't exist */
		$results = $this->model->gravitypdf_notification( 'Test', $form, $lead );
		$this->assertEquals( 'Test', $results );
	}

	/**
	 * Test we can correctly update shortcode attributes easily
	 *
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
	 *
	 * @since 4.0
	 */
	public function test_gravitypdf_redirect_confirmation() {
		global $wp_rewrite;

		$_POST['confirmation_id'] = '';

		/* Process fancy permalinks */
		$old_permalink_structure = get_option( 'permalink_structure' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();

		/* Setup our redirect confirmation value */
		$_POST['_gform_setting_url'] = '[gravitypdf id="555ad84787d7e"]';

		/* Run the test */
		$this->model->gravitypdf_redirect_confirmation( [ 'id' => 1 ] );
		$this->assertEquals( '[gravitypdf id="555ad84787d7e" entry="{entry_id}" raw="1"]', $_POST['_gform_setting_url'] );

		/* Check for viewing URL */
		$_POST['_gform_setting_url'] = '[gravitypdf id="555ad84787d7e" type="view"]';

		$this->model->gravitypdf_redirect_confirmation( [ 'id' => 1 ] );
		$this->assertEquals( '[gravitypdf id="555ad84787d7e" type="view" entry="{entry_id}" raw="1"]', $_POST['_gform_setting_url'] );

		$wp_rewrite->set_permalink_structure( $old_permalink_structure );
		flush_rewrite_rules();
	}

	/**
	 * @since 5.1
	 */
	public function test_gravitypdf_redirect_confirmation_shortcode_processing() {

		$form                        = $GLOBALS['GFPDF_Test']->form['all-form-fields'];
		$form['confirmation']        = $form['confirmations']['54bca34973cdd'];
		$form['confirmation']['url'] = '[gravitypdf id="556690c67856b" entry="{entry_id}" raw="1"]';

		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];

		$this->assertTrue( $this->model->gravitypdf_redirect_confirmation_shortcode_processing( true, $form, $entry ) );

		$form['confirmation']['type'] = 'redirect';
		$confirmation                 = [ 'redirect' => '' ];
		$results                      = $this->model->gravitypdf_redirect_confirmation_shortcode_processing( $confirmation, $form, $entry );
		$this->assertStringContainsString( '?gpdf=1&pid=556690c67856b&lid=1&action=download', $results['redirect'] );

		$form['confirmation']['url'] = '[gravitypdf id="556690c67856b" entry="{entry_id}" raw="1" signed="1"]';
		$results                     = $this->model->gravitypdf_redirect_confirmation_shortcode_processing( $confirmation, $form, $entry );
		$this->assertStringContainsString( '?gpdf=1&pid=556690c67856b&lid=1&action=download', $results['redirect'] );
		$this->assertStringContainsString( '&signature=', $results['redirect'] );
		$this->assertStringContainsString( '&expires=', $results['redirect'] );
	}

	/**
	 * Verify we can return a parsed version of the shortcode information
	 *
	 * @since 4.0
	 */
	public function test_get_shortcode_information() {
		$content = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/json/shortcode-data.json' ) ), true );

		$this->assertCount( 0, $this->model->get_shortcode_information( 'gravitypdf', [] ) );

		$shortcodes = $this->model->get_shortcode_information( 'gravitypdf', $content );

		/* Test the results */
		$this->assertCount( 4, $shortcodes );

		$this->assertEquals( '[gravitypdf]', $shortcodes[0]['shortcode'] );
		$this->assertEquals( '', $shortcodes[0]['attr_raw'] );
		$this->assertEquals( [], $shortcodes[0]['attr'] );

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
	 *
	 * @since 4.0
	 */
	public function test_no_entry_id() {
		$this->assertStringContainsString( 'No Gravity Form entry ID', $this->view->no_entry_id() );
	}

	/**
	 * Check our invalid PDF view displays correctly
	 *
	 * @since 4.0
	 */
	public function test_invalid_pdf_config() {
		$this->assertStringContainsString( 'Could not get Gravity PDF configuration', $this->view->invalid_pdf_config() );
	}

	/**
	 * Check our inactive PDF view displays correctly
	 *
	 * @since 4.0
	 */
	public function test_pdf_not_active() {
		$this->assertStringContainsString( 'PDF link not displayed because PDF is inactive.', $this->view->pdf_not_active() );
	}

	/**
	 * Check our failed conditional logic view displays correctly
	 *
	 * @since 4.0
	 */
	public function test_conditional_logic_not_met() {
		$this->assertStringContainsString( 'PDF link not displayed because conditional logic requirements have not been met.', $this->view->conditional_logic_not_met() );
	}

	/**
	 * Check our display GF shortcode view displays correctly
	 *
	 * @since 4.0
	 */
	public function test_display_gravitypdf_shortcode() {
		$this->assertStringContainsString(
			'href="',
			$this->view->display_gravitypdf_shortcode(
				[
					'url'     => '',
					'type'    => '',
					'class'   => '',
					'classes' => '',
					'text'    => '',
				]
			)
		);
	}
}
