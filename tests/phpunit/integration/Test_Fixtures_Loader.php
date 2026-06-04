<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

/**
 * Infrastructure self-test for HasGfpdfFixtures::load_fixtures and the per-class
 * fixture cache. Not a mirror of a src/ class — pins the fixture-loading machinery
 * so a regression here fails loudly before downstream classes inherit it.
 *
 * @group fixtures-loader
 */
class Test_Fixtures_Loader extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures(
			[ 'gravityform-1' ],
			[ 'gravityform-1' ]
		);
	}

	public function test_form_accessor_returns_loaded_fixture() {
		$form = $this->form( 'gravityform-1' );

		$this->assertIsInt( $form['id'] );
		$this->assertGreaterThan( 0, $form['id'] );
		$this->assertNotEmpty( $form['fields'] );
	}

	public function test_entry_accessor_returns_entries_linked_to_loaded_form() {
		$entry = $this->entry( 'gravityform-1' );

		$this->assertSame(
			$this->form( 'gravityform-1' )['id'],
			(int) $entry['form_id']
		);
	}

	public function test_entries_accessor_returns_full_list() {
		$entries = $this->entries( 'gravityform-1' );

		$this->assertCount( 3, $entries );
		$this->assertSame( $entries[0]['id'], $this->entry( 'gravityform-1', 0 )['id'] );
	}

	public function test_unloaded_form_key_fails_with_helpful_message() {
		try {
			$this->form( 'not-loaded' );
			$this->fail( 'Expected fail() on unloaded key' );
		} catch ( \PHPUnit\Framework\AssertionFailedError $e ) {
			$this->assertStringContainsString( "Form fixture 'not-loaded' is not loaded", $e->getMessage() );
			$this->assertStringContainsString( 'gravityform-1', $e->getMessage() );
		}
	}
}
