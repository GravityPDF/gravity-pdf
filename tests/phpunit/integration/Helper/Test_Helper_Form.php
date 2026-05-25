<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group   helper
 * @group   helper-form
 */
class Test_Helper_Form extends TestCase {

	private Helper_Form $gform;

	public function set_up(): void {
		parent::set_up();

		$this->gform = new Helper_Form();
	}

	/**
	 * get_form() returns a form array for an existing form ID.
	 */
	public function test_get_form_returns_form_array(): void {
		$fixture = $this->form( 'gravityform-1' );
		$result  = $this->gform->get_form( $fixture['id'] );

		$this->assertIsArray( $result );
		$this->assertSame( $fixture['id'], $result['id'] );
		$this->assertArrayHasKey( 'fields', $result );
	}

	/**
	 * get_form() returns null for a non-existent form ID.
	 */
	public function test_get_form_returns_null_for_missing_id(): void {
		$result = $this->gform->get_form( 999999 );

		$this->assertNull( $result );
	}

	/**
	 * get_forms() returns a non-empty array that includes the known fixture forms.
	 */
	public function test_get_forms_returns_all_active_forms(): void {
		$forms = $this->gform->get_forms();

		$this->assertIsArray( $forms );
		$this->assertNotEmpty( $forms );

		$ids = array_column( $forms, 'id' );
		$this->assertContains( $this->form( 'gravityform-1' )['id'], $ids );
	}

	/**
	 * get_entry() returns an entry array for a known entry.
	 */
	public function test_get_entry_returns_entry_array(): void {
		$fixture = $this->entry( 'gravityform-1' );
		$result  = $this->gform->get_entry( $fixture['id'] );

		$this->assertIsArray( $result );
		$this->assertSame( (string) $fixture['id'], $result['id'] );
	}

	/**
	 * process_tags() resolves a form-title merge tag to the correct title.
	 */
	public function test_process_tags_resolves_form_title_merge_tag(): void {
		$fixture_form  = $this->form( 'gravityform-1' );
		$fixture_entry = $this->entry( 'gravityform-1' );

		$result = $this->gform->process_tags( '{form_title}', $fixture_form, $fixture_entry );

		$this->assertSame( $fixture_form['title'], $result );
	}

	/**
	 * get_version() returns a non-empty string matching a semver-like pattern.
	 */
	public function test_get_version_returns_semver_string(): void {
		$version = $this->gform->get_version();

		$this->assertIsString( $version );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+/', $version );
	}

	/**
	 * update_entry() persists a changed field value.
	 *
	 * Uses a freshly-created entry so the shared gravityform-1 fixture is not mutated.
	 */
	public function test_update_entry_persists_change(): void {
		$fixture_form = $this->form( 'gravityform-1' );
		$entry_id     = $this->gf_factory()->entry->create( [ 'form_id' => $fixture_form['id'], 'ip' => '127.0.0.1' ] );
		$entry        = $this->gform->get_entry( $entry_id );

		$entry['ip'] = '10.0.0.1';
		$this->gform->update_entry( $entry );

		$updated = $this->gform->get_entry( $entry_id );
		$this->assertSame( '10.0.0.1', $updated['ip'] );
	}
}
