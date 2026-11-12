<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Model\Model_Install;
use GFPDF\Model\Model_Pdf_Cache;
use GFPDF\Model\Model_System_Report;
use GFPDF\Statics\Deprecation;
use GFPDF\Tests\Concerns\AccessesPrivateState;
use GFPDF\Tests\Concerns\CopiesFontFixtures;
use GFPDF\Tests\Concerns\CreatesLegacyDownloadUrls;
use GFPDF\Tests\Concerns\CreatesLegacyTemplates;
use GFPDF\Tests\Concerns\RecordsFontFailures;
use GFPDF\Tests\Concerns\Seamed_Font_Downloader;
use GFPDF\Tests\Concerns\StubsFontSources;
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

	use AccessesPrivateState;
	use CreatesLegacyDownloadUrls;
	use CopiesFontFixtures;
	use CreatesLegacyTemplates;
	use RecordsFontFailures;
	use StubsFontSources;

	public function set_up(): void {
		parent::set_up();

		add_filter( 'pre_http_request', [ $this, 'get_public_dir_api_response' ] );
	}

	public function tear_down(): void {
		remove_filter( 'pre_http_request', [ $this, 'get_public_dir_api_response' ] );

		/* Null falls back to the registered models, which are what bootstrap injected */
		$report = \GPDFAPI::get_mvc_class( 'Model_System_Report' );
		$this->set_private( $report, 'font_downloader', null );
		$this->set_private( $report, 'required_fonts', null );

		$this->remove_font_fixtures();
		$this->delete_font_failures();

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
		$this->assertArrayHasKey( 'folder_permissions', $this->get_report_section( 'directories', $system_report ) );
		$this->assertArrayHasKey( 'mpdf_temp_folder_location', $this->get_report_section( 'directories', $system_report ) );

		$this->assertArrayHasKey( 'pdf_entry_list_action', $this->get_report_section( 'global', $system_report ) );
		$this->assertArrayHasKey( 'background_processing_enabled', $this->get_report_section( 'global', $system_report ) );
		$this->assertArrayHasKey( 'debug_mode_enabled', $this->get_report_section( 'global', $system_report ) );

		$this->assertArrayHasKey( 'user_restrictions', $this->get_report_section( 'security', $system_report ) );
		$this->assertArrayHasKey( 'logged_out_timeout', $this->get_report_section( 'security', $system_report ) );
	}

	public function test_system_report_lists_the_required_fonts_installed_and_the_substitution_fonts() {
		\GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->set_languages( [ 'th' ] );

		$directories = $this->report_with_font_sources( $this->stub_font_sources() );

		$this->assertSame( 'core: 0 of 4 required files installed', $directories['core_fonts_installed']['value'] );
		$this->assertSame( 'Core Fonts installed', $directories['core_fonts_installed']['label_export'] );
		$this->assertSame( 'arimo, gfpdf-dejavu-symbols, dejavusanscondensed, freesans, garuda, khmeros, padaukbook, kaputaunicode, abyssinicasil, sun-exta', $directories['substitution_fonts']['value'] );
	}

	public function test_system_report_flags_a_missing_core_font_manifest() {
		$directories = $this->report_with_font_sources( $this->font_sources_without_a_manifest() );

		$this->assertSame( 'core: manifest missing', $directories['core_fonts_installed']['value'] );
	}

	/**
	 * The report's directories section, built with the font sources given
	 */
	private function report_with_font_sources( \GFPDF\Model\Model_Font_Sources $sources ): array {
		$report   = \GPDFAPI::get_mvc_class( 'Model_System_Report' );
		$injected = [
			'font_sources'   => $sources,
			'required_fonts' => new \GFPDF\Model\Model_Required_Fonts( $sources, \GPDFAPI::get_options_class(), \GPDFAPI::get_log_class() ),
		];
		$original = [];

		/* The registered report was given the shared instances at bootstrap */
		foreach ( $injected as $name => $model ) {
			$original[ $name ] = $this->get_private( $report, $name );
			$this->set_private( $report, $name, $model );
		}

		try {
			return $this->get_report_section( 'directories', apply_filters( 'gform_system_report', [] ) );
		} finally {
			foreach ( $original as $name => $model ) {
				$this->set_private( $report, $name, $model );
			}
		}
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
		$this->assertArrayNotHasKey( 'orphan_row', $this->get_report_section( 'cache', $system_report ) );
		$this->assertCount( 5, $system_report[0]['tables'] );
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
		$this->assertArrayHasKey( Deprecation::GROUP_UNSUPPORTED, $named );
		$this->assertArrayHasKey( 'directories', $named );

		/* And the section is still in the report */
		$this->assertArrayHasKey( 'legacy_templates', $this->get_report_section( Deprecation::GROUP_UNSUPPORTED, $system_report ) );

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

	/**
	 * @since 6.18.0
	 */
	public function test_folder_permissions_tests_the_folders_when_the_report_is_built() {
		$gfpdf = $this->gfpdf();

		/* A folder that failed the last check, and has been fixed since */
		update_option( Model_Install::UNWRITABLE_FOLDERS, [ '/srv/fixed-since/' => time() ] );

		$row = $this->get_report_section( 'directories' )['folder_permissions'];
		$this->assertSame( 'Writable', $row['value_export'] );
		$this->assertSame( [], get_option( Model_Install::UNWRITABLE_FOLDERS ) );

		$dir         = $gfpdf->data->template_tmp_location;
		$directories = $this->get_directories_with_unwritable( [ $dir ] );
		$row         = $directories['folder_permissions'];

		$this->assertSame( 'Not writable', $directories['temp_folder_permission']['value_export'] );
		$this->assertSame( 'Not writable: ' . $dir, $row['value_export'] );
		$this->assertStringContainsString( 'Not writable', $row['value'] );
	}

	public function test_temp_folder_permission_reads_an_mpdf_cache_outside_tmp() {
		$gfpdf = $this->gfpdf();
		$saved = $gfpdf->data->mpdf_tmp_location;

		$gfpdf->data->mpdf_tmp_location = '/srv/pdf-tmp';

		try {
			$row = $this->get_directories_with_unwritable( [ '/srv/pdf-tmp/mpdf' ] )['temp_folder_permission'];
		} finally {
			$gfpdf->data->mpdf_tmp_location = $saved;
		}

		$this->assertSame( 'Not writable', $row['value_export'] );
	}

	/**
	 * Get the Directories section with the folder check failing for the given folders
	 *
	 * @param string[] $folders
	 */
	protected function get_directories_with_unwritable( array $folders ): array {
		$gfpdf     = $this->gfpdf();
		$installer = $gfpdf->singleton->get_class( 'Model_Install' );

		/* The registry keys classes by their short name, so the mock takes the installer's place */
		$failing = $this->getMockBuilder( Model_Install::class )
			->disableOriginalConstructor()
			->setMockClassName( 'Model_Install' )
			->onlyMethods( [ 'create_folder_structures' ] )
			->getMock();
		$failing->method( 'create_folder_structures' )->willReturn( $folders );
		$gfpdf->singleton->add_class( $failing );

		try {
			return $this->get_report_section( 'directories' );
		} finally {
			$gfpdf->singleton->add_class( $installer );
		}
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
	 * Get the items of the Unsupported section, which is only present when something removed is still in use
	 */
	protected function get_unsupported_features(): array {
		return $this->get_report_section( Deprecation::GROUP_UNSUPPORTED );
	}

	public function test_system_report_has_no_deprecated_features_section_by_default() {
		$system_report = apply_filters( 'gform_system_report', [] );

		$this->assertCount( 5, $system_report[0]['tables'] );
	}

	public function test_system_report_legacy_template() {
		$this->assertArrayNotHasKey( 'legacy_templates', $this->get_unsupported_features() );

		$legacy_path = $this->create_legacy_template();

		$items = $this->get_unsupported_features();
		$this->assertArrayHasKey( 'legacy_templates', $items );
		$this->assertStringContainsString( 'my-legacy-template.php', $items['legacy_templates']['value_export'] );

		/* Every row is handed to Gravity Forms as a failure, so it draws the cross and the message itself */
		$this->assertFalse( $items['legacy_templates']['is_valid'] );

		$this->delete_legacy_templates( $legacy_path );
	}

	public function test_system_report_business_plus_template_has_a_row_of_its_own() {
		$this->assertArrayNotHasKey( 'business_plus_templates', $this->get_unsupported_features() );

		$path = $this->create_legacy_template( 'my-business-plus-template.php', 'gfpdfe_business_plus::initilise( $pdf_name );' );

		$items = $this->get_unsupported_features();
		$this->assertSame( 'my-business-plus-template.php (not configured on a form)', $items['business_plus_templates']['value_export'] );

		/* It upgrades differently to a plain v3 template, so it doesn't share that row or its guide */
		$this->assertArrayNotHasKey( 'legacy_templates', $items );
		$this->assertStringContainsString( '#business-plus--tier-2-template-upgrade-guide', $items['business_plus_templates']['validation_message_export'] );

		$this->delete_legacy_templates( $path );
	}

	public function test_system_report_legacy_endpoint() {
		$this->assertArrayNotHasKey( 'legacy_endpoint', $this->get_unsupported_features() );

		$form_id = $this->create_form_with_legacy_url();

		/* 7.0 removed the endpoint, so the URLs a form still hands out are reported as unsupported, not deprecated */
		$items = $this->get_unsupported_features();
		$this->assertArrayHasKey( 'legacy_endpoint', $items );
		$this->assertStringContainsString( (string) $form_id, $items['legacy_endpoint']['value_export'] );
		$this->assertFalse( $items['legacy_endpoint']['is_valid'] );
	}

	public function test_system_report_ignores_the_advanced_templating_setting() {
		$this->create_form_with_advanced_templating();

		/* The template file is what's reported, so a Core template isn't listed for how a PDF is configured */
		$this->assertArrayNotHasKey( 'legacy_templates', $this->get_unsupported_features() );
		$this->assertArrayNotHasKey( 'business_plus_templates', $this->get_unsupported_features() );
	}

	public function test_system_report_removed_filters() {
		$this->assertArrayNotHasKey( 'deprecated_filters', $this->get_unsupported_features() );

		$callback = static function ( $name ) {
			return $name;
		};

		add_filter( 'gfpdfe_pdf_filename', $callback );
		Deprecation::flush_cache();

		$items = $this->get_unsupported_features();
		$this->assertArrayHasKey( 'deprecated_filters', $items );
		$this->assertStringContainsString( 'gfpdfe_pdf_filename', $items['deprecated_filters']['value_export'] );

		remove_filter( 'gfpdfe_pdf_filename', $callback );
	}

	public function test_system_report_tools_tab_hooks() {
		$this->assertArrayNotHasKey( 'tools_tab_hooks', $this->get_report_section( Deprecation::GROUP_DEPRECATED ) );

		add_filter( 'gfpdf_settings_tools', '__return_empty_array' );
		Deprecation::flush_cache();

		$items = $this->get_report_section( Deprecation::GROUP_DEPRECATED );
		$this->assertArrayHasKey( 'tools_tab_hooks', $items );
		/* A WordPress function can't be traced to a plugin or theme */
		$this->assertSame( 'gfpdf_settings_tools has 1 listener (custom code)', $items['tools_tab_hooks']['value_export'] );

		remove_filter( 'gfpdf_settings_tools', '__return_empty_array' );
	}

	public function test_deprecated_hook_listeners_are_named_by_their_source() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$closure = static function ( $value ) {
			return $value;
		};

		add_filter( 'gfpdf_rtl', '__return_false' );
		add_filter( 'gfpdf_rtl', $closure, 20 );
		Deprecation::flush_cache();

		/* This test lives inside the Gravity PDF plugin directory, so the closure is named after it */
		$expected = 'gfpdf_rtl has 2 listeners (custom code, Gravity PDF)';

		$this->assertSame( $expected, $this->get_unsupported_features()['deprecated_filters']['value_export'] );
		$this->assertStringStartsWith( $expected, apply_filters( 'debug_information', [] )['gravity-pdf-unsupported']['fields']['deprecated_filters']['value'] );
		$this->assertStringContainsString( esc_html( $expected ), call_user_func( $this->get_site_health_test()['test'] )['description'] );

		remove_filter( 'gfpdf_rtl', '__return_false' );
		remove_filter( 'gfpdf_rtl', $closure, 20 );
	}

	/**
	 * Get the Site Health test the controller registers
	 */
	protected function get_site_health_test( string $id = 'gravity_pdf_deprecated_features' ): array {
		$tests = apply_filters( 'site_status_tests', [ 'direct' => [] ] );

		return $tests['direct'][ $id ] ?? [];
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

		/* The removed-feature group has its own panel, and reports nothing on a site that isn't using one */
		$this->assertSame( 'None detected', $info['gravity-pdf-unsupported']['fields']['unsupported']['value'] );

		/* The group still heads its own panel, but the intro only makes sense against a list of detections */
		$this->assertStringContainsString( '<h4>Unsupported Features</h4>', $info['gravity-pdf-unsupported']['description'] );
		$this->assertStringNotContainsString( '<p>', $info['gravity-pdf-unsupported']['description'] );

		$this->assertSame( 'None detected', $info['gravity-pdf-deprecated']['fields']['deprecated']['value'] );
	}

	public function test_debug_information_is_gated_on_the_gravity_forms_capability() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$info = apply_filters( 'debug_information', [] );

		$this->assertArrayNotHasKey( 'gravity-pdf-unsupported', $info );
	}

	public function test_debug_information_reports_each_signal() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$form_id = $this->create_form_with_legacy_url();

		/* The endpoint is removed in 7.0, so the URLs still pointing at it report under Unsupported */
		$fields = apply_filters( 'debug_information', [] )['gravity-pdf-unsupported']['fields'];

		$this->assertArrayNotHasKey( 'unsupported', $fields );
		$this->assertSame( 'Legacy Download URLs', $fields['legacy_endpoint']['label'] );
		$this->assertStringContainsString( (string) $form_id, $fields['legacy_endpoint']['value'] );
	}

	public function test_every_surface_lists_a_template_by_file_name() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$legacy_path = $this->create_legacy_template();

		/* Templates live in one directory, which the report states of its own, so no surface repeats the path */
		$items = $this->get_unsupported_features();
		$this->assertSame( 'my-legacy-template.php (not configured on a form)', $items['legacy_templates']['value_export'] );

		$fields = apply_filters( 'debug_information', [] )['gravity-pdf-unsupported']['fields'];
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

		$items = $this->get_unsupported_features();
		$this->assertSame( $expected, $items['legacy_templates']['value_export'] );

		/* The display surfaces link each form to its PDF settings, which is where the template is changed */
		$this->assertStringContainsString( 'subview=PDF', $items['legacy_templates']['value'] );
		$this->assertStringContainsString( sprintf( '>%d</a>', $form_id ), $items['legacy_templates']['value'] );

		$fields = apply_filters( 'debug_information', [] )['gravity-pdf-unsupported']['fields'];
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

		/* The endpoint is already gone, so the test escalates past 'recommended' */
		$this->assertSame( 'critical', $result['status'] );
		$this->assertStringContainsString( 'has been removed', $result['label'] );
		$this->assertStringContainsString( '<h4>Unsupported</h4>', $result['description'] );
		$this->assertStringNotContainsString( '<h4>Deprecated</h4>', $result['description'] );
		$this->assertStringContainsString( 'Legacy Download URLs', $result['description'] );
		$this->assertStringContainsString( (string) $form_id, $result['description'] );
		$this->assertStringContainsString( 'page=gf_system_status#' . Model_System_Report::SECTION_ANCHOR, $result['actions'] );
	}

	/**
	 * The notice and the Site Health test both link into the middle of a long report, so the section is anchored
	 */
	public function test_the_report_section_can_be_linked_to_directly() {
		$system_report = apply_filters( 'gform_system_report', [] );

		$this->assertStringContainsString( 'id="' . Model_System_Report::SECTION_ANCHOR . '"', $system_report[0]['title'] );
		$this->assertStringContainsString( 'Gravity PDF Environment', $system_report[0]['title'] );

		$this->assertStringContainsString( 'page=gf_system_status#' . Model_System_Report::SECTION_ANCHOR, Model_System_Report::get_report_url() );
	}

	public function test_system_report_pdf_cache() {
		$this->gfpdf()->options->update_option( 'pdf_cache', 'No' );
		$this->gfpdf()->options->update_option( 'cache_duration', 6 );

		$items = $this->get_report_section( 'cache' );

		$this->assertSame( 'No', $items['pdf_cache']['value_export'] );
		$this->assertSame( '6 hour(s)', $items['cache_duration']['value_export'] );
		$this->assertSame( 'Never', $items['cache_cleanup']['value_export'] );
		$this->assertArrayNotHasKey( 'is_valid', $items['cache_cleanup'] );

		update_option(
			Model_Pdf_Cache::SWEEP_STATE_OPTION,
			[
				'last_slice_at'    => time(),
				'last_complete_at' => time() - HOUR_IN_SECONDS,
				'last_pass'        => [
					'mode'         => 'expire',
					'started'      => time() - HOUR_IN_SECONDS,
					'files_reaped' => 3,
					'bytes_reaped' => 3072,
					'files_left'   => 2,
					'bytes_left'   => 2048,
				],
			]
		);

		$items = $this->get_report_section( 'cache' );

		$this->assertStringContainsString( 'Removed 3 file(s) (3072 bytes) and kept 2 (2048 bytes)', $items['cache_cleanup']['value_export'] );
		$this->assertStringContainsString( '1 hour ago. Removed 3 file(s) (3 KB) and kept 2 (2 KB).', $items['cache_cleanup']['value'] );
	}

	public function test_the_pdf_cache_row_and_site_health_warn_when_cron_is_not_cleaning_the_cache() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$test = apply_filters( 'site_status_tests', [ 'direct' => [] ] )['direct']['gravity_pdf_cache_cleanup']['test'];

		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
		wp_schedule_event( time() - 2 * HOUR_IN_SECONDS, 'hourly', 'gfpdf_cleanup_tmp_dir' );
		update_option( Model_Pdf_Cache::SWEEP_STATE_OPTION, [ 'last_slice_at' => time() - 2 * DAY_IN_SECONDS ] );

		/* Nothing cached, nothing to warn about */
		$this->assertArrayNotHasKey( 'is_valid', $this->get_report_section( 'cache' )['cache_cleanup'] );
		$this->assertSame( 'good', call_user_func( $test )['status'] );

		$entry_dir = $this->gfpdf()->data->template_tmp_location . ( is_multisite() ? get_current_blog_id() . '/' : '' ) . 'cache/e999999/';
		wp_mkdir_p( $entry_dir );

		try {
			$this->assertFalse( $this->get_report_section( 'cache' )['cache_cleanup']['is_valid'] );
			$this->assertSame( 'recommended', call_user_func( $test )['status'] );

			/* A sweep ran recently, but the hourly event is overdue */
			update_option( Model_Pdf_Cache::SWEEP_STATE_OPTION, [ 'last_slice_at' => time() ] );
			$this->assertSame( 'recommended', call_user_func( $test )['status'] );

			wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'gfpdf_cleanup_tmp_dir' );
			$this->assertArrayNotHasKey( 'is_valid', $this->get_report_section( 'cache' )['cache_cleanup'] );
			$this->assertSame( 'good', call_user_func( $test )['status'] );
		} finally {
			rmdir( $entry_dir );
		}
	}

	public function test_core_fonts_failed_lists_given_up_files_even_after_the_notice_is_dismissed() {
		update_option( 'date_format', 'Y-m-d' );
		$this->set_font_failures(
			[
				'Garuda.ttf'   => $this->failure( strtotime( '2026-11-03 12:00:00 UTC' ) ),
				'FreeSans.ttf' => $this->failure(),
			]
		);

		$this->assertSame( 'Core Fonts failed', $this->report_row( 'core_fonts_failed' )['label_export'] );
		$this->assertSame( 'Garuda.ttf (gave up 2026-11-03)', $this->report_row( 'core_fonts_failed' )['value'] );

		\GPDFAPI::get_mvc_class( 'Model_Actions' )->dismiss_failed_core_fonts();

		$this->assertFalse( \GPDFAPI::get_mvc_class( 'Model_Actions' )->has_failed_core_fonts() );
		$this->assertSame( 'Garuda.ttf (gave up 2026-11-03)', $this->report_row( 'core_fonts_failed' )['value'] );
	}

	public function test_core_fonts_failed_and_the_notice_clear_once_the_font_is_on_disk() {
		$this->list_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );

		$this->assertSame( 'None', $this->report_row( 'core_fonts_failed' )['value'] );
		$this->assertFalse( \GPDFAPI::get_mvc_class( 'Model_Actions' )->has_failed_core_fonts() );
	}

	public function test_the_font_rows_can_be_linked_to() {
		$this->use_report_downloader( [] );

		$this->assertStringContainsString( 'id="' . Model_System_Report::FONTS_FAILED_ANCHOR . '"', $this->report_row( 'core_fonts_failed' )['label'] );
		$this->assertStringContainsString( 'id="' . Model_System_Report::FONT_DOWNLOADS_ANCHOR . '"', $this->report_row( 'core_font_downloads' )['label'] );
	}

	public function test_core_fonts_failed_says_none_without_a_given_up_file() {
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure() ] );

		$this->assertSame( 'None', $this->report_row( 'core_fonts_failed' )['value'] );
	}

	public function test_core_font_downloads_reads_the_downloaders_status() {
		$this->use_report_downloader( [ 'Garuda.ttf' ] );

		$this->assertSame( 'Core Font downloads', $this->report_row( 'core_font_downloads' )['label_export'] );
		$this->assertSame( 'On', $this->report_row( 'core_font_downloads' )['value'] );

		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$this->assertSame( 'Off (gfpdf_auto_install_fonts filter)', $this->report_row( 'core_font_downloads' )['value'] );
	}

	public function test_the_core_fonts_site_health_test_is_gated_on_the_gravity_forms_capability() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->assertSame( [], $this->get_site_health_test( 'gravity_pdf_core_fonts' ) );
	}

	public function test_the_core_fonts_site_health_test_is_good_with_every_required_file_on_disk() {
		$this->list_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		$this->use_report_downloader( [ 'Garuda.ttf' ] );

		/* A file that gave up but has since been copied in by hand no longer counts */
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$result = $this->run_core_fonts_test();

		$this->assertSame( 'good', $result['status'] );
		$this->assertSame( 'gravity_pdf_core_fonts', $result['test'] );
	}

	public function test_the_core_fonts_site_health_test_is_good_while_missing_files_wait_to_download() {
		$this->use_report_downloader( [ 'Garuda.ttf' ] );
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure() ] );

		$this->assertSame( 'good', $this->run_core_fonts_test()['status'] );
	}

	public function test_the_core_font_health_only_scans_the_forms_when_a_file_could_be_reported() {
		$this->use_report_downloader( [ 'Garuda.ttf' ] );
		$report = \GPDFAPI::get_mvc_class( 'Model_System_Report' );

		$this->assertSame( [], $report->get_core_font_health()['missing'] );

		add_filter( 'gfpdf_auto_install_fonts', '__return_true' );

		$this->assertSame( [ 'Garuda.ttf' ], $report->get_core_font_health()['missing'] );
	}

	public function test_the_core_fonts_site_health_test_is_critical_for_a_file_that_gave_up_on_another_fonts_file_of_a_known_size() {
		$this->put_same_size_font_at( 'DejaVuSerifCondensed.ttf', 'DejaVuSerifCondensed.ttf' );
		$this->use_report_downloader( [ 'DejaVuSerifCondensed.ttf' ] );
		$this->set_font_failures( [ 'DejaVuSerifCondensed.ttf' => $this->conflict_failure( $this->font_path( 'DejaVuSerifCondensed.ttf' ) ) ] );

		$result = $this->run_core_fonts_test();

		$this->assertSame( 'critical', $result['status'] );
		$this->assertStringContainsString( '<code>DejaVuSerifCondensed.ttf</code></li>', $result['description'] );
	}

	public function test_the_core_fonts_site_health_test_is_critical_for_a_file_that_gave_up() {
		$this->use_report_downloader( [ 'Garuda.ttf', 'FreeSans.ttf' ] );
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );

		$result = $this->run_core_fonts_test();

		$this->assertSame( 'critical', $result['status'] );
		$this->assertSame( 'Gravity PDF could not download required fonts', $result['label'] );
		$this->assertStringContainsString( 'render PDFs. Until the fonts are installed', $result['description'] );
		$this->assertStringContainsString( 'may not display.</p><p>Fonts that failed to download:</p><ul', $result['description'] );
		$this->assertStringContainsString( '<code>Garuda.ttf</code></li>', $result['description'] );
		$this->assertStringNotContainsString( 'turned off', $result['description'] );
		$this->assertStringContainsString( 'page=gf_system_status#' . Model_System_Report::FONTS_FAILED_ANCHOR, $result['actions'] );
		$this->assertStringNotContainsString( 'gfpdf_auto_install_fonts', $result['actions'] );

		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$this->assertSame( 'critical', $this->run_core_fonts_test()['status'] );
	}

	public function test_the_core_fonts_site_health_test_recommends_action_for_missing_files_while_downloads_are_off() {
		$this->use_report_downloader( [ 'Garuda.ttf' ] );
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$result = $this->run_core_fonts_test();

		$this->assertSame( 'recommended', $result['status'] );
		$this->assertSame( 'Some core fonts your PDFs need are missing', $result['label'] );
		$this->assertStringContainsString( 'turned off by <code>gfpdf_auto_install_fonts</code> filter. These fonts are missing:</p><ul', $result['description'] );
		$this->assertStringContainsString( '<code>Garuda.ttf</code></li>', $result['description'] );
		$this->assertStringContainsString( 'render PDFs. Until the fonts are installed', $result['description'] );
		$this->assertStringContainsString( 'page=gf_system_status#' . Model_System_Report::FONT_DOWNLOADS_ANCHOR, $result['actions'] );
		$this->assertStringContainsString( 'gfpdf_auto_install_fonts', $result['actions'] );
	}

	/**
	 * Give the registered report a downloader whose required set is the files given
	 *
	 * @param string[] $files
	 */
	private function use_report_downloader( array $files ): void {
		$report     = \GPDFAPI::get_mvc_class( 'Model_System_Report' );
		$downloader = new Seamed_Font_Downloader( \GPDFAPI::get_log_class(), [ 'required' => $files ] );

		$this->set_private( $report, 'font_downloader', $downloader );
		$this->set_private( $report, 'required_fonts', $downloader->get_required_fonts() );
	}

	/**
	 * Run the core fonts Site Health test as an administrator
	 */
	private function run_core_fonts_test(): array {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		return call_user_func( $this->get_site_health_test( 'gravity_pdf_core_fonts' )['test'] );
	}

	/**
	 * A row of the report's directories section
	 */
	private function report_row( string $key ): array {
		return $this->get_report_section( 'directories' )[ $key ];
	}
}
