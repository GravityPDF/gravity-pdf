<?php

declare(strict_types=1);

namespace GFPDF\Tests\Integration;

/**
 * Infrastructure self-test for HasGfpdfFixtures::load_fixtures() and the per-class
 * fixture cache. Not a mirror of a src/ class — it pins the Phase A migration
 * scaffolding so a regression in load_fixtures/cleanup_class_fixtures fails
 * loudly before bulk Phase C sweeps depend on it.
 *
 * @group fixtures-loader
 */
class Test_Fixtures_Loader extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures(
			[ 'gravityform-1' ],
			[ 'gravityform-1' ]
		);
	}

	public function test_form_accessor_returns_per_class_form_not_legacy_global() {
		$form = $this->form( 'gravityform-1' );

		$this->assertNotSame(
			$GLOBALS['GFPDF_Test']->form['gravityform-1']['id'],
			$form['id']
		);
		// Field count proves the same JSON was loaded (GFAPI rewrites titles with
		// `(1)` suffixes to dedupe, so the title is unreliable for shape checks).
		$this->assertSame(
			count( $GLOBALS['GFPDF_Test']->form['gravityform-1']['fields'] ),
			count( $form['fields'] )
		);
	}

	public function test_entry_accessor_returns_entries_linked_to_loaded_form() {
		$entry = $this->entry( 'gravityform-1', 0 );

		$this->assertSame(
			$this->form( 'gravityform-1' )['id'],
			(int) $entry['form_id']
		);
	}

	public function test_unloaded_key_falls_back_to_legacy_global() {
		$this->assertSame(
			$GLOBALS['GFPDF_Test']->form['form-settings']['id'],
			$this->form( 'form-settings' )['id']
		);
	}
}
