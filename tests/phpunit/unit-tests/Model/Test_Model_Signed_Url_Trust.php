<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GF_Field_Name;
use GF_Field_Text;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group shortcodes
 */
class Test_Model_Signed_Url_Trust extends WP_UnitTestCase {

	/**
	 * @var Model_Signed_Url_Trust
	 */
	private $trust;

	public function set_up() {
		parent::set_up();
		$this->trust = new Model_Signed_Url_Trust();
	}

	public function test_each_grant_is_used_once() {
		$this->assertFalse( $this->trust->consume( 'a' ) );

		$this->trust->grant( 'a' );
		$this->trust->grant( 'a' );
		$this->assertTrue( $this->trust->consume( 'a' ) );
		$this->assertTrue( $this->trust->consume( 'a' ) );
		$this->assertFalse( $this->trust->consume( 'a' ) );
	}

	public function test_run_granted_removes_an_unused_grant_and_keeps_earlier_ones() {
		$this->assertSame( 'x', $this->trust->run_granted( 'a', function () { return 'x'; } ) );
		$this->assertFalse( $this->trust->consume( 'a' ) );

		$this->trust->grant( 'a' );
		$this->trust->run_granted( 'a', function () { return $this->trust->consume( 'a' ); } );
		$this->assertTrue( $this->trust->consume( 'a' ) );
		$this->assertFalse( $this->trust->consume( 'a' ) );
	}

	public function test_run_untrusted() {
		$this->assertFalse( $this->trust->is_untrusted() );
		$this->assertTrue( $this->trust->run_untrusted( [ $this->trust, 'is_untrusted' ] ) );
		$this->assertFalse( $this->trust->is_untrusted() );
	}

	public function test_only_an_administrative_field_holding_its_default_is_trusted() {
		$field = new GF_Field_Text( [ 'id' => 1, 'visibility' => 'administrative', 'defaultValue' => 'default' ] );

		$this->assertTrue( $this->trust->is_trusted_field_value( $field, [ '1' => 'default' ] ) );
		$this->assertFalse( $this->trust->is_trusted_field_value( $field, [ '1' => 'posted' ] ) );

		$field->visibility = 'visible';
		$this->assertFalse( $this->trust->is_trusted_field_value( $field, [ '1' => 'default' ] ) );
	}

	public function test_every_input_of_an_administrative_field_must_hold_its_default() {
		$field = new GF_Field_Name( [
			'id'         => 2,
			'visibility' => 'administrative',
			'inputs'     => [
				[ 'id' => '2.3', 'defaultValue' => '[First]' ],
				[ 'id' => '2.6', 'defaultValue' => 'Last' ],
			],
		] );

		$this->assertTrue( $this->trust->is_trusted_field_value( $field, [ '2.3' => '[First]', '2.6' => 'Last' ] ) );
		$this->assertFalse( $this->trust->is_trusted_field_value( $field, [ '2.3' => '[First]', '2.6' => 'Posted' ] ) );

		$form = [ 'fields' => [ $field ] ];

		/* Only the values holding a tag are returned */
		$this->assertSame( [ '[First]' ], $this->trust->get_trusted_field_values( $form, [ '2.3' => '[First]', '2.6' => 'Last' ] ) );
		$this->assertSame( [], $this->trust->get_trusted_field_values( $form, [ '2.3' => '[First]', '2.6' => 'Posted' ] ) );
	}

	public function test_a_default_is_recomputed_for_the_current_request() {
		$field = new GF_Field_Text( [ 'id' => 1, 'visibility' => 'administrative', 'defaultValue' => '[gravitypdf id="1"] {Name:1}' ] );

		/* Tags Gravity Forms leaves for later match the default whoever renders the PDF */
		$this->assertTrue( $this->trust->is_trusted_field_value( $field, [ '1' => '[gravitypdf id="1"] {Name:1}' ] ) );

		/* Gravity Forms resolves request tags like {user:...} as the entry saves, and again for the comparison */
		$field->defaultValue = '{user:display_name} [gravitypdf id="1"]';
		$saved_by            = $this->factory->user->create( [ 'display_name' => 'Saved By' ] );
		$rendered_by         = $this->factory->user->create( [ 'display_name' => 'Rendered By' ] );
		$entry               = [ '1' => 'Saved By [gravitypdf id="1"]' ];

		wp_set_current_user( $saved_by );
		$this->assertTrue( $this->trust->is_trusted_field_value( $field, $entry ) );

		/* So the value is untrusted when the PDF renders for someone else */
		wp_set_current_user( $rendered_by );
		$this->assertFalse( $this->trust->is_trusted_field_value( $field, $entry ) );
	}
}
