<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GF_Field_Name;
use GF_Field_Text;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group shortcodes
 */
class Test_Model_Signed_Url_Trust extends TestCase {

	/**
	 * @var Model_Signed_Url_Trust
	 */
	private $trust;

	public function set_up(): void {
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

	public function test_resolving_defaults() {
		/* An unmatched stop can't go below zero */
		$this->trust->stop_resolving_defaults();
		$this->trust->start_resolving_defaults();
		$this->assertTrue( $this->trust->is_resolving_defaults() );
		$this->trust->stop_resolving_defaults();
		$this->assertFalse( $this->trust->is_resolving_defaults() );
	}

	public function test_only_the_tags_in_an_administrative_default_are_trusted() {
		$field = new GF_Field_Text( [ 'id' => 1, 'visibility' => 'administrative', 'defaultValue' => 'Hi {Name:1} [gravitypdf id="1" text="{entry_id}"] {user:display_name}' ] );

		/* A shortcode is kept whole, with the merge tags in it */
		$this->assertSame( [ '{Name:1}', '[gravitypdf id="1" text="{entry_id}"]', '{user:display_name}' ], $this->trust->get_default_tags( $field ) );

		$field->defaultValue = 'No tags';
		$this->assertSame( [], $this->trust->get_default_tags( $field ) );

		$field->defaultValue = '[gravitypdf id="1"]';
		$field->visibility   = 'visible';
		$this->assertSame( [], $this->trust->get_default_tags( $field ) );
		$this->assertSame( [], $this->trust->get_default_tags( null ) );
	}

	public function test_every_input_default_of_an_administrative_field_is_read() {
		$field = new GF_Field_Name( [
			'id'         => 2,
			'visibility' => 'administrative',
			'inputs'     => [
				[ 'id' => '2.3', 'defaultValue' => '[gravitypdf id="1"] [unregistered]' ],
				[ 'id' => '2.6', 'defaultValue' => 'Last {ip}' ],
				[ 'id' => '2.8' ],
			],
		] );

		/* Only a registered shortcode is a tag */
		$this->assertSame( [ '[gravitypdf id="1"]', '{ip}' ], $this->trust->get_default_tags( $field ) );
	}

	public function test_the_default_tags_an_entry_still_holds_are_trusted() {
		$field = new GF_Field_Text( [ 'id' => 1, 'visibility' => 'administrative', 'defaultValue' => '{user:display_name} [gravitypdf id="1"] {Name:1}' ] );
		$form  = [ 'fields' => [ $field ] ];

		/* {user:display_name} resolved on save */
		$this->assertSame( [ '[gravitypdf id="1"]', '{Name:1}' ], $this->trust->get_trusted_field_tags( $form, [ '1' => 'Jake [gravitypdf id="1"] {Name:1}' ] ) );

		/* A tag that isn't in the default never is */
		$this->assertSame( [ '{Name:1}' ], $this->trust->get_trusted_field_tags( $form, [ '1' => '[gravitypdf id="1" entry="5" signed="1"] {Name:1}' ] ) );
		$this->assertSame( [], $this->trust->get_trusted_field_tags( $form, [ '1' => '' ] ) );
	}
}
