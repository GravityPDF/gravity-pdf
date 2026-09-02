<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Statics\Deprecation;
use GFPDF\Tests\Concerns\CreatesLegacyDownloadUrls;
use GFPDF\Tests\Concerns\CreatesLegacyTemplates;
use GFPDF\Tests\Integration\TestCase;

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
class Test_Controller_System_Report extends TestCase {

	use CreatesLegacyDownloadUrls;
	use CreatesLegacyTemplates;

	public function set_up(): void {
		parent::set_up();

		add_filter( 'pre_http_request', [ $this, 'get_public_dir_api_response' ] );
	}

	public function tear_down(): void {
		remove_filter( 'pre_http_request', [ $this, 'get_public_dir_api_response' ] );

		parent::tear_down();
	}

	/**
	 * Override API request to speed up unit test
	 *
	 * @return array
	 */
	public function get_public_dir_api_response() {
		return [
			'response' => [ 'code' => 200 ],
			'body'     => 'failed-if-read',
		];
	}

	public function test_system_report_php() {
		/* Test that our data is spliced into the correct location in the array */
		$system_report = apply_filters( 'gform_system_report', [ [] ] );

		$this->assertArrayHasKey( 'memory', $this->get_report_section( 'php', $system_report ) );
		$this->assertArrayHasKey( 'allow_url_fopen', $this->get_report_section( 'php', $system_report ) );
		$this->assertArrayHasKey( 'default_charset', $this->get_report_section( 'php', $system_report ) );
		$this->assertArrayHasKey( 'internal_encoding', $this->get_report_section( 'php', $system_report ) );

		$this->assertArrayHasKey( 'pdf_working_directory', $this->get_report_section( 'directories', $system_report ) );
		$this->assertArrayHasKey( 'pdf_working_directory_url', $this->get_report_section( 'directories', $system_report ) );
		$this->assertArrayHasKey( 'font_folder_location', $this->get_report_section( 'directories', $system_report ) );
		$this->assertArrayHasKey( 'temp_folder_location', $this->get_report_section( 'directories', $system_report ) );
		$this->assertArrayHasKey( 'temp_folder_permission', $this->get_report_section( 'directories', $system_report ) );
		$this->assertArrayHasKey( 'temp_folder_protected', $this->get_report_section( 'directories', $system_report ) );
		$this->assertArrayHasKey( 'mpdf_temp_folder_location', $this->get_report_section( 'directories', $system_report ) );

		$this->assertArrayHasKey( 'pdf_entry_list_action', $this->get_report_section( 'global', $system_report ) );
		$this->assertArrayHasKey( 'background_processing_enabled', $this->get_report_section( 'global', $system_report ) );
		$this->assertArrayHasKey( 'debug_mode_enabled', $this->get_report_section( 'global', $system_report ) );

		$this->assertArrayHasKey( 'user_restrictions', $this->get_report_section( 'security', $system_report ) );
		$this->assertArrayHasKey( 'logged_out_timeout', $this->get_report_section( 'security', $system_report ) );
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
	 * Get the items of one Gravity PDF report section, by the ID the section declares
	 *
	 * Looked up by name so a section added or dropped above doesn't move every assertion below it.
	 */
	protected function get_report_section( string $id, ?array $system_report = null ): array {
		$system_report = $system_report ?? apply_filters( 'gform_system_report', [] );

		foreach ( $system_report as $section ) {
			foreach ( $section['tables'] ?? [] as $table ) {
				if ( ( $table['id'] ?? '' ) === $id ) {
					return $table['items'];
				}
			}
		}

		return [];
	}

	/**
	 * Get the items of the Deprecated section, which is only present when something is detected
	 */
	protected function get_deprecated_features(): array {
		return $this->get_report_section( Deprecation::GROUP_DEPRECATED );
	}

	public function test_system_report_has_no_deprecated_features_section_by_default() {
		$system_report = apply_filters( 'gform_system_report', [] );

		$this->assertCount( 4, $system_report[0]['tables'] );
	}

	public function test_system_report_legacy_template() {
		$this->assertArrayNotHasKey( 'legacy_templates', $this->get_deprecated_features() );

		$legacy_path = $this->create_legacy_template();

		$items = $this->get_deprecated_features();
		$this->assertArrayHasKey( 'legacy_templates', $items );
		$this->assertStringContainsString( 'my-legacy-template.php', $items['legacy_templates']['value_export'] );

		/* Every row is handed to Gravity Forms as a failure, so it draws the cross and the message itself */
		$this->assertFalse( $items['legacy_templates']['is_valid'] );

		$this->delete_legacy_templates( $legacy_path );
	}

	public function test_system_report_business_plus_template_has_a_row_of_its_own() {
		$this->assertArrayNotHasKey( 'business_plus_templates', $this->get_deprecated_features() );

		$path = $this->create_legacy_template( 'my-business-plus-template.php', '$mpdf->AddPage();' );

		$items = $this->get_deprecated_features();
		$this->assertSame( 'my-business-plus-template.php (not configured on a form)', $items['business_plus_templates']['value_export'] );

		/* It upgrades differently to a plain v3 template, so it doesn't share that row or its guide */
		$this->assertArrayNotHasKey( 'legacy_templates', $items );
		$this->assertStringContainsString( '#business-plus--tier-2-template-upgrade-guide', $items['business_plus_templates']['validation_message_export'] );

		$this->delete_legacy_templates( $path );
	}

	public function test_system_report_legacy_endpoint() {
		$this->assertArrayNotHasKey( 'legacy_endpoint', $this->get_deprecated_features() );

		$form_id = $this->create_form_with_legacy_url();

		$items = $this->get_deprecated_features();
		$this->assertArrayHasKey( 'legacy_endpoint', $items );
		$this->assertStringContainsString( (string) $form_id, $items['legacy_endpoint']['value_export'] );
		$this->assertFalse( $items['legacy_endpoint']['is_valid'] );
	}

	public function test_system_report_ignores_the_advanced_templating_setting() {
		$this->create_form_with_advanced_templating();

		/* The template file is what's reported, so a Core template isn't listed for how a PDF is configured */
		$this->assertArrayNotHasKey( 'legacy_templates', $this->get_deprecated_features() );
		$this->assertArrayNotHasKey( 'business_plus_templates', $this->get_deprecated_features() );
	}

	public function test_system_report_deprecated_filters() {
		$this->assertArrayNotHasKey( 'deprecated_filters', $this->get_deprecated_features() );

		$callback = static function ( $name ) {
			return $name;
		};

		add_filter( 'gfpdfe_pdf_filename', $callback );
		Deprecation::flush_cache();

		$items = $this->get_deprecated_features();
		$this->assertArrayHasKey( 'deprecated_filters', $items );
		$this->assertStringContainsString( 'gfpdfe_pdf_filename', $items['deprecated_filters']['value_export'] );

		remove_filter( 'gfpdfe_pdf_filename', $callback );
	}

	/**
	 * Get the Site Health test the controller registers
	 */
	protected function get_site_health_test(): array {
		$tests = apply_filters( 'site_status_tests', [ 'direct' => [] ] );

		return $tests['direct']['gravity_pdf_deprecated_features'] ?? [];
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

	public function test_debug_information_reports_each_signal() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$form_id = $this->create_form_with_legacy_url();

		$fields = apply_filters( 'debug_information', [] )['gravity-pdf-deprecated']['fields'];

		$this->assertArrayNotHasKey( 'deprecated', $fields );
		$this->assertSame( 'Legacy Download URLs', $fields['legacy_endpoint']['label'] );
		$this->assertStringContainsString( (string) $form_id, $fields['legacy_endpoint']['value'] );
	}

	public function test_every_surface_lists_a_template_by_file_name() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$legacy_path = $this->create_legacy_template();

		/* Templates live in one directory, which the report states of its own, so no surface repeats the path */
		$items = $this->get_deprecated_features();
		$this->assertSame( 'my-legacy-template.php (not configured on a form)', $items['legacy_templates']['value_export'] );

		$fields = apply_filters( 'debug_information', [] )['gravity-pdf-deprecated']['fields'];
		$this->assertStringContainsString( 'my-legacy-template.php', $fields['legacy_templates']['value'] );
		$this->assertStringNotContainsString( 'PDF_EXTENDED_TEMPLATES', $fields['legacy_templates']['value'] );


		$result = call_user_func( $this->get_site_health_test()['test'] );
		$this->assertStringContainsString( 'my-legacy-template.php', $result['description'] );
		$this->assertStringNotContainsString( 'PDF_EXTENDED_TEMPLATES', $result['description'] );

		$this->delete_legacy_templates( $legacy_path );
	}

	/**
	 * The file name alone leaves the reader to search every form for it, so each surface names the forms too
	 */
	public function test_every_surface_names_the_forms_a_legacy_template_is_configured_on() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$legacy_path = $this->create_legacy_template( 'my-configured-template.php' );
		$form_id     = (int) $this->gf_factory()->form->create();

		$this->gf_factory()->pdf->set_form_id( $form_id )->create( [ 'template' => 'my-configured-template' ] );

		$expected = sprintf( 'my-configured-template.php (form ID %d)', $form_id );

		$items = $this->get_deprecated_features();
		$this->assertSame( $expected, $items['legacy_templates']['value_export'] );

		/* The display surfaces link each form to its PDF settings, which is where the template is changed */
		$this->assertStringContainsString( 'subview=PDF', $items['legacy_templates']['value'] );
		$this->assertStringContainsString( sprintf( '>%d</a>', $form_id ), $items['legacy_templates']['value'] );

		$fields = apply_filters( 'debug_information', [] )['gravity-pdf-deprecated']['fields'];
		$this->assertStringContainsString( $expected, $fields['legacy_templates']['value'] );

		$result = call_user_func( $this->get_site_health_test()['test'] );
		$this->assertStringContainsString( sprintf( '>%d</a>', $form_id ), $result['description'] );

		$this->delete_legacy_templates( $legacy_path );
	}

	/**
	 * The report detects live, so it leaves the admin notices a fresh record on the way past — a site that has
	 * fixed everything stops being told about it without waiting for the next release
	 */
	public function test_the_report_records_what_it_detects_for_the_notices() {
		$this->create_form_with_legacy_url();

		apply_filters( 'gform_system_report', [] );

		$this->assertSame( [ 'legacy_endpoint' ], Deprecation::get_detected_features() );
	}

	public function test_site_health_test_reports_each_signal() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$form_id = $this->create_form_with_legacy_url();

		$result = call_user_func( $this->get_site_health_test()['test'] );

		$this->assertSame( 'recommended', $result['status'] );
		$this->assertStringContainsString( '<h4>Deprecated</h4>', $result['description'] );
		$this->assertStringNotContainsString( '<h4>Unsupported</h4>', $result['description'] );
		$this->assertStringContainsString( 'Legacy Download URLs', $result['description'] );
		$this->assertStringContainsString( (string) $form_id, $result['description'] );
		$this->assertStringContainsString( 'page=gf_system_status', $result['actions'] );
	}
}
