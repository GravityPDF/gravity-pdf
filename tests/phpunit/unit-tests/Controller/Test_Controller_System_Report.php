<?php

namespace GFPDF\Controller;

use GFPDF\Statics\Deprecation;
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
