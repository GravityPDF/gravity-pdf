<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use GFPDF\Tests\Integration\TestCase;


/**
 * @group   helper
 * @group   helper-form
 */
class Test_Helper_Form extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'gravityform-1', 'all-form-fields' ], [ 'gravityform-1', 'all-form-fields' ] );
	}

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

	public function test_process_tags_keeps_a_tag_a_merged_value_adds_as_text(): void {
		$form       = $this->form( 'all-form-fields' );
		$entry      = $this->entry( 'all-form-fields' );
		$entry['1'] = '{admin_email} {Email:17} {:17}';

		$text = $this->gform->process_tags( 'Name: {Single Line Text:1} Form: {form_title}', $form, $entry );

		$this->assertStringContainsString( 'Form: ' . $form['title'], $text );
		$this->assertSame( $text, \GFCommon::replace_variables( $text, $form, $entry, false, false, false ) );
	}

	public function test_process_tags_keeps_a_tag_a_custom_merge_tag_adds_as_text(): void {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );
		$echo  = function ( $text, $form, $entry ) {
			$text = str_replace( '{test_echo}', 'Echo {admin_email} [caption]', $text );

			return strpos( $text, '{test_nested}' ) === false ? $text : str_replace( '{test_nested}', \GFCommon::replace_variables( '{form_title} {test_echo}', $form, $entry, false, false, false ), $text );
		};

		add_filter( 'gform_replace_merge_tags', $echo, 10, 3 );
		try {
			$text  = $this->gform->process_tags( '{test_echo} | {test_nested} | {form_title} {unknown} <style>.a{color:red}</style>', $form, $entry );
			$again = \GFCommon::replace_variables( $text, $form, $entry, false, false, false );
		} finally {
			remove_filter( 'gform_replace_merge_tags', $echo );
		}

		$this->assertSame( 'Echo {admin_email} [caption] | ' . $form['title'] . ' Echo {admin_email} [caption] | ' . $form['title'] . ' {unknown} <style>.a{color:red}</style>', str_replace( "\u{FEFF}", '', $text ) );
		$this->assertSame( $text, $again );
	}

	/**
	 * @dataProvider provider_plain_text_values
	 */
	public function test_process_tags_leaves_a_merged_value_as_is_for_plain_text( string $value ): void {
		$form       = $this->form( 'all-form-fields' );
		$entry      = $this->entry( 'all-form-fields' );
		$entry['1'] = $value;

		/* As the filename and passwords do */
		$text = wp_specialchars_decode( $this->gform->process_tags( 'Name: {Single Line Text:1}', $form, $entry, false ), ENT_QUOTES );

		$this->assertSame( 'Name: ' . $value, $text );
	}

	public function provider_plain_text_values(): array {
		return [
			'merge tag'                      => [ 'Report {Draft}: final' ],
			'brackets, backslash, shortcode' => [ 'pw[1]\\x [gravitypdf id="a"]' ],
		];
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
	 * Uses a freshly-created entry so the shared gravityform-1 fixture is not mutated,
	 * and deletes it in a finally block so GFAPI's non-transactional tables don't
	 * accumulate orphans across runs.
	 */
	public function test_update_entry_persists_change(): void {
		$fixture_form = $this->form( 'gravityform-1' );
		$entry_id     = $this->gf_factory()->entry->create( [ 'form_id' => $fixture_form['id'], 'ip' => '127.0.0.1' ] );

		try {
			$entry        = $this->gform->get_entry( $entry_id );
			$entry['ip']  = '10.0.0.1';
			$this->gform->update_entry( $entry );

			$updated = $this->gform->get_entry( $entry_id );
			$this->assertSame( '10.0.0.1', $updated['ip'] );
		} finally {
			\GFAPI::delete_entry( $entry_id );
		}
	}
}
