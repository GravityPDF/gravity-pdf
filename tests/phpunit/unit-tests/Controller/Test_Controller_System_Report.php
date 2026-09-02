<?php

namespace GFPDF\Controller;

use GFPDF\Model\Model_System_Report;
use GFPDF\Statics\Deprecation;
use GFPDF\Tests\Concerns\CreatesLegacyTemplates;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_System_Report
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   system-report
 */
class Test_Controller_System_Report extends WP_UnitTestCase {

	use CreatesLegacyTemplates;

	public function set_up(): void {
		parent::set_up();

		/* Deprecation holds its detections for the request; a test process is many requests' worth of site state */
		Deprecation::flush_cache();
	}

	public function test_system_report_php() {
		$system_report = apply_filters( 'gform_system_report', [ [] ] );

		/* Seeded with an existing section, which our report is appended after rather than replacing */
		$this->assertCount( 2, $system_report );
		$this->assertSame( [], $system_report[0] );

		$php         = $this->get_report_section( 'php', $system_report );
		$directories = $this->get_report_section( 'directories', $system_report );
		$global      = $this->get_report_section( 'global', $system_report );
		$security    = $this->get_report_section( 'security', $system_report );

		$this->assertArrayHasKey( 'memory', $php );
		$this->assertArrayHasKey( 'allow_url_fopen', $php );
		$this->assertArrayHasKey( 'default_charset', $php );
		$this->assertArrayHasKey( 'internal_encoding', $php );

		$this->assertArrayHasKey( 'pdf_working_directory', $directories );
		$this->assertArrayHasKey( 'pdf_working_directory_url', $directories );
		$this->assertArrayHasKey( 'font_folder_location', $directories );
		$this->assertArrayHasKey( 'temp_folder_location', $directories );
		$this->assertArrayHasKey( 'temp_folder_permission', $directories );
		$this->assertArrayHasKey( 'temp_folder_protected', $directories );
		$this->assertArrayHasKey( 'mpdf_temp_folder_location', $directories );

		$this->assertArrayHasKey( 'pdf_entry_list_action', $global );
		$this->assertArrayHasKey( 'background_processing_enabled', $global );
		$this->assertArrayHasKey( 'debug_mode_enabled', $global );

		$this->assertArrayHasKey( 'user_restrictions', $security );
		$this->assertArrayHasKey( 'logged_out_timeout', $security );
	}

	public function test_system_report_outdated_template() {
		/* verify no outdated template info */
		$system_report = apply_filters( 'gform_system_report', [] );
		$this->assertArrayNotHasKey( 'outdated_templates', $this->get_report_section( 'directories', $system_report ) );

		/* copy core template to override location and adjust version number, then verify outdated message is included */
		$data          = \GPDFAPI::get_data_class();
		$override_path = $data->template_location . 'zadani.php';

		$template = file_get_contents( PDF_PLUGIN_DIR . 'src/templates/zadani.php' );
		file_put_contents( $override_path, preg_replace( '/Version: (.+?)/', 'Version: 1.5.2', $template ) );

		$system_report = apply_filters( 'gform_system_report', [] );
		$this->assertArrayHasKey( 'outdated_templates', $this->get_report_section( 'directories', $system_report ) );

		@unlink( $override_path );
	}

	/**
	 * The report was keyed by position until 6.17.0, so an add-on written against it names an index, not a section
	 */
	public function test_the_positional_report_items_filter_reaches_the_section_each_index_stood_for() {
		$this->setExpectedDeprecated( 'gfpdf_system_status_report_items' );

		$callback = static function ( $items ) {
			foreach ( array_keys( $items ) as $index ) {
				$items[ $index ][ 'row_' . $index ] = [
					'label' => 'Third Party Row',
					'value' => (string) $index,
				];
			}

			/* What the filter takes out has to stay out, so the read-back replaces the section rather than merging it */
			unset( $items[0]['default_charset'] );

			/* No section has ever answered to this, so there is nowhere for it to land */
			$items[4] = [ 'orphan_row' => [ 'label' => 'Orphan Row' ] ];

			return $items;
		};

		add_filter( 'gfpdf_system_status_report_items', $callback );

		$system_report = apply_filters( 'gform_system_report', [] );

		remove_filter( 'gfpdf_system_status_report_items', $callback );

		$this->assertArrayHasKey( 'row_0', $this->get_report_section( 'php', $system_report ) );
		$this->assertArrayHasKey( 'row_1', $this->get_report_section( 'directories', $system_report ) );
		$this->assertArrayHasKey( 'row_2', $this->get_report_section( 'global', $system_report ) );
		$this->assertArrayHasKey( 'row_3', $this->get_report_section( 'security', $system_report ) );

		$this->assertArrayNotHasKey( 'default_charset', $this->get_report_section( 'php', $system_report ) );
		$this->assertCount( 4, $system_report[0]['tables'] );
	}

	/**
	 * A section added since is held back from the positional filter, since numbering it renumbers the four below it
	 */
	public function test_the_positional_report_items_filter_is_held_to_the_four_sections_it_knew() {
		$this->setExpectedDeprecated( 'gfpdf_system_status_report_items' );

		$path       = $this->create_legacy_template();
		$positional = [];
		$named      = [];

		$capture_positional = static function ( $items ) use ( &$positional ) {
			$positional = $items;

			return $items;
		};

		$capture_named = static function ( $items ) use ( &$named ) {
			$named = $items;

			return $items;
		};

		add_filter( 'gfpdf_system_status_report_items', $capture_positional );
		add_filter( 'gfpdf_system_status_report_sections', $capture_named );

		$system_report = apply_filters( 'gform_system_report', [] );

		remove_filter( 'gfpdf_system_status_report_items', $capture_positional );
		remove_filter( 'gfpdf_system_status_report_sections', $capture_named );

		$this->assertSame( [ 0, 1, 2, 3 ], array_keys( $positional ) );

		/* The replacement is keyed by name, so the section the positional filter never saw is in it */
		$this->assertArrayHasKey( Deprecation::GROUP_DEPRECATED, $named );
		$this->assertArrayHasKey( 'directories', $named );

		/* And the section is still in the report */
		$this->assertArrayHasKey( 'legacy_templates', $this->get_report_section( Deprecation::GROUP_DEPRECATED, $system_report ) );

		$this->delete_legacy_templates( $path );
	}

	/**
	 * Naming the section is what a section added or dropped above it no longer moves
	 */
	public function test_the_report_sections_filter_adds_a_row_by_section_name() {
		$callback = static function ( $items ) {
			$items['directories']['third_party_row'] = [
				'label' => 'Third Party Row',
				'value' => 'From the named filter',
			];

			return $items;
		};

		add_filter( 'gfpdf_system_status_report_sections', $callback );

		$system_report = apply_filters( 'gform_system_report', [] );

		remove_filter( 'gfpdf_system_status_report_sections', $callback );

		$this->assertArrayHasKey( 'third_party_row', $this->get_report_section( 'directories', $system_report ) );
	}

	public function test_system_report_has_no_deprecated_features_section_by_default() {
		$system_report = apply_filters( 'gform_system_report', [] );

		$this->assertCount( 4, $system_report[0]['tables'] );
	}

	public function test_site_health_test_is_registered() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$test = $this->get_site_health_test();

		$this->assertIsCallable( $test['test'] );
		$this->assertSame( 'good', call_user_func( $test['test'] )['status'] );
	}

	public function test_site_health_test_is_gated_on_the_gravity_forms_capability() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertSame( [], $this->get_site_health_test() );
	}

	public function test_debug_information_reports_a_clean_site() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$info = apply_filters( 'debug_information', [] );

		$this->assertSame( 'None detected', $info['gravity-pdf-deprecated']['fields']['deprecated']['value'] );

		/* No registered feature belongs to the other group, so it isn't carried around empty */
		$this->assertArrayNotHasKey( 'gravity-pdf-unsupported', $info );
	}

	public function test_debug_information_is_gated_on_the_gravity_forms_capability() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$info = apply_filters( 'debug_information', [] );

		$this->assertArrayNotHasKey( 'gravity-pdf-deprecated', $info );
	}

	/**
	 * The notice and the Site Health test both link into the middle of a long report, so the section is anchored
	 */
	public function test_the_report_section_can_be_linked_to_directly() {
		$system_report = apply_filters( 'gform_system_report', [ [] ] );

		$this->assertStringContainsString( 'id="' . Model_System_Report::SECTION_ANCHOR . '"', $system_report[1]['title'] );
		$this->assertStringContainsString( 'Gravity PDF Environment', $system_report[1]['title'] );

		$this->assertStringContainsString( 'page=gf_system_status#' . Model_System_Report::SECTION_ANCHOR, Model_System_Report::get_report_url() );
	}

	/**
	 * Get the Site Health test the controller registers
	 */
	protected function get_site_health_test(): array {
		$tests = apply_filters( 'site_status_tests', [ 'direct' => [] ] );

		return $tests['direct']['gravity_pdf_deprecated_features'] ?? [];
	}

	/**
	 * Get the items of one Gravity PDF report section, by the ID the section declares
	 *
	 * Looked up by name so a section added or dropped above doesn't move every assertion below it.
	 */
	protected function get_report_section( string $id, array $system_report ): array {
		foreach ( $system_report as $section ) {
			foreach ( $section['tables'] ?? [] as $table ) {
				if ( ( $table['id'] ?? '' ) === $id ) {
					return $table['items'];
				}
			}
		}

		return [];
	}
}
