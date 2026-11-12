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
	 * Override API request to speed up unit test, answering as a protected temporary folder does
	 *
	 * @return array
	 */
	public function get_public_dir_api_response() {
		return [
			'response' => [ 'code' => 403 ],
			'body'     => '',
		];
	}

	public function test_the_report_has_a_checks_and_a_configuration_table() {
		$tables = apply_filters( 'gform_system_report', [] )[0]['tables'];

		$this->assertSame( [ 'checks', 'configuration' ], array_column( $tables, 'id' ) );
		$this->assertSame( [ 'Gravity PDF - Checks', 'Gravity PDF - Configuration' ], array_column( $tables, 'title_export' ) );
		$this->assertSame(
			[ 'PDF Working Directory', 'Folders', 'Font Installer', 'Entry View', 'Background Processing', 'Debug Mode', 'User Restriction', 'Logged Out Timeout', 'PDF Cache', 'PHP Charset / Encoding', 'Substitution Fonts' ],
			array_column( $tables[1]['items'], 'label_export' )
		);
	}

	public function test_a_clean_site_passes_every_check_in_one_row() {
		$checks = $this->get_table( 'checks' );
		$screen = array_values(
			array_filter(
				$checks,
				static function ( $row ) {
					return empty( $row['export_only'] );
				}
			)
		);

		$this->assertCount( 1, $screen );
		$this->assertSame( 'All 9 checks passed', $screen[0]['label'] );
		$this->assertTrue( $screen[0]['is_valid'] );
		$this->assertStringStartsWith( 'no unsupported features · no deprecated features · PDF memory ', $screen[0]['value'] );
		$this->assertSame(
			[ 'Unsupported Features', 'Deprecated Features', 'PDF Memory Limit', 'allow_url_fopen', 'Folder Permissions', 'Temporary Folder Protected', 'Core Fonts', 'Outdated Templates', 'PDF Cache Cleanup' ],
			array_column( $checks, 'label_export' )
		);
		$this->assertSame( [ 'None', 'None' ], array_column( array_slice( $checks, 0, 2 ), 'value_export' ) );
	}

	public function test_the_copied_report_marks_unchanged_settings_as_default() {
		$configuration = array_column( $this->get_table( 'configuration' ), 'value_export', 'label_export' );

		$this->assertStringEndsWith( 'mPDF: ' . ( is_multisite() ? wp_normalize_path( $this->gfpdf()->data->template_location ) : '' ) . 'tmp/mpdf/ (default)', $configuration['Folders'] );
		$this->assertSame( 'View (default)', $configuration['Entry View'] );
		$this->assertSame( 'Off (default)', $configuration['Background Processing'] );
		$this->assertSame( 'Off (default)', $configuration['Debug Mode'] );
		$this->assertSame( 'gravityforms_view_entries (default)', $configuration['User Restriction'] );
		$this->assertSame( '20 minutes (default)', $configuration['Logged Out Timeout'] );
		$this->assertSame( 'On, 12 hours (default)', $configuration['PDF Cache'] );
	}

	public function test_the_copied_report_leaves_default_off_changed_settings() {
		$options = $this->gfpdf()->options;
		$options->update_option( 'default_action', 'Download' );
		$options->update_option( 'background_processing', 'Yes' );
		$options->update_option( 'debug_mode', 'Yes' );
		$options->update_option( 'admin_capabilities', [ 'gravityforms_view_entries', 'gravityforms_edit_entries' ] );
		$options->update_option( 'logged_out_timeout', 1 );
		$options->update_option( 'cache_duration', 24 );

		$configuration = array_column( $this->get_table( 'configuration' ), null, 'label_export' );

		$this->assertSame( 'Download', $configuration['Entry View']['value_export'] );
		$this->assertSame( 'On', $configuration['Background Processing']['value_export'] );
		$this->assertSame( 'On', $configuration['Debug Mode']['value_export'] );
		$this->assertSame( 'gravityforms_view_entries, gravityforms_edit_entries', $configuration['User Restriction']['value_export'] );
		$this->assertSame( 'gravityforms_view_entries<br>gravityforms_edit_entries', $configuration['User Restriction']['value'] );
		$this->assertSame( '1 minute', $configuration['Logged Out Timeout']['value_export'] );
		$this->assertSame( 'On, 24 hours', $configuration['PDF Cache']['value_export'] );
	}

	public function test_the_folders_row_is_relative_to_the_working_directory_while_every_folder_is_inside_it() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single site only' );
		}

		$folders = $this->get_sections()['directories']['folders'];

		$this->assertStringStartsWith( 'Relative to the PDF Working Directory:<br>Fonts: <code>fonts/</code>', $folders['value'] );
		$this->assertStringContainsString( 'mPDF: <code>tmp/mpdf/</code>', $folders['value'] );
	}

	public function test_the_folders_row_shows_each_folder_in_full_on_a_multisite_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only' );
		}

		$folders = $this->get_sections()['directories']['folders'];

		$this->assertStringStartsWith( 'Fonts: <code>' . esc_html( wp_normalize_path( $this->gfpdf()->data->template_font_location ) ) . '</code>', $folders['value'] );
	}

	public function test_the_folders_row_shows_each_folder_in_full_once_one_moves_out() {
		$gfpdf = $this->gfpdf();
		$saved = $gfpdf->data->mpdf_tmp_location;

		$gfpdf->data->mpdf_tmp_location = '/srv/pdf-tmp';

		try {
			$folders = $this->get_sections()['directories']['folders'];
		} finally {
			$gfpdf->data->mpdf_tmp_location = $saved;
		}

		$this->assertStringNotContainsString( 'Relative to', $folders['value'] );
		$this->assertStringContainsString( 'mPDF: <code>/srv/pdf-tmp/mpdf/</code>', $folders['value'] );
		$this->assertStringStartsWith( 'Fonts: ' . wp_normalize_path( $gfpdf->data->template_font_location ), $folders['value_export'] );
		$this->assertStringEndsWith( 'mPDF: /srv/pdf-tmp/mpdf/', $folders['value_export'] );
	}

	public function test_the_substitution_fonts_are_only_in_the_copied_report() {
		\GPDFAPI::get_options_class()->update_option( 'default_font', 'arimo' );
		$this->set_languages( [ 'th' ] );

		$directories = $this->report_with_font_sources( $this->stub_font_sources() );

		$this->assertTrue( $directories['substitution_fonts']['export_only'] );
		$this->assertSame( 'arimo, gfpdf-dejavu-symbols, dejavusanscondensed, freesans, garuda, khmeros, padaukbook, kaputaunicode, abyssinicasil, sun-exta', $directories['substitution_fonts']['value'] );
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
			return $this->get_sections()['directories'];
		} finally {
			foreach ( $original as $name => $model ) {
				$this->set_private( $report, $name, $model );
			}
		}
	}

	public function test_system_report_outdated_template() {
		$this->assertSame( 'None', array_column( $this->get_table( 'checks' ), 'value_export', 'label_export' )['Outdated Templates'] );

		$override_path = $this->create_outdated_override();

		try {
			$row    = $this->get_sections()['directories']['outdated_templates'];
			$checks = array_column( $this->get_table( 'checks' ), 'value', 'label_export' );
		} finally {
			@unlink( $override_path );
		}

		$this->assertFalse( $row['is_valid'] );
		$this->assertStringEndsNotWith( '<br>', $checks['Outdated Templates'] );
		$this->assertStringStartsWith( 'zadani.php 1.5.2', $row['value_export'] );
		$this->assertStringStartsWith( '<code>zadani.php</code> 1.5.2', $row['value'] );
		$this->assertStringStartsWith( 'Update these template overrides. <a href=', $row['validation_message'] );
		$this->assertSame( '', $row['validation_message_export'] );
	}

	public function test_add_on_rows_go_to_checks_or_the_end_of_configuration() {
		$this->setExpectedDeprecated( 'gfpdf_system_status_report_items' );

		$override_path = $this->create_outdated_override();

		$positional = static function ( $items ) {
			$items[1]['outdated_templates']['value']        .= '/gpdf-leo.php version 2.1.0 is out of date.<hr>';
			$items[1]['outdated_templates']['value_export'] .= "/gpdf-leo.php version 2.1.0 is out of date.\n";

			return $items;
		};

		$named = static function ( $items ) {
			$items['directories']['gv_outdated_templates'] = [
				'label' => 'Outdated GravityView Templates',
				'value' => 'list.php',
			];

			$items['global']['add_on_row'] = [
				'label' => 'An Add-on Row',
				'value' => 'Unchanged',
			];

			return $items;
		};

		add_filter( 'gfpdf_system_status_report_items', $positional );
		add_filter( 'gfpdf_system_status_report_sections', $named );

		try {
			$checks        = $this->get_table( 'checks' );
			$configuration = $this->get_table( 'configuration' );
		} finally {
			remove_filter( 'gfpdf_system_status_report_items', $positional );
			remove_filter( 'gfpdf_system_status_report_sections', $named );
			@unlink( $override_path );
		}

		$this->assertSame( [ 'Outdated Templates', 'Outdated GravityView Templates', '8 of 10 checks passed' ], array_column( array_slice( $checks, 0, 3 ), 'label' ) );
		$this->assertMatchesRegularExpression( '#^<code>zadani.php</code> [^<]+<br>/gpdf-leo.php#', $checks[0]['value'] );
		$this->assertStringContainsString( "\n/gpdf-leo.php", $checks[0]['value_export'] );
		$this->assertSame( 'An Add-on Row', end( $configuration )['label'] );
	}

	/**
	 * Copy the zadani template into the working directory, a version behind
	 */
	private function create_outdated_override(): string {
		$path     = \GPDFAPI::get_data_class()->template_location . 'zadani.php';
		$template = file_get_contents( PDF_PLUGIN_DIR . 'src/templates/zadani.php' );

		file_put_contents( $path, preg_replace( '/Version: (.+?)/', 'Version: 1.5.2', $template ) );

		return $path;
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
			unset( $items[0]['charset'] );

			/* No section has ever answered to this, so there is nowhere for it to land */
			$items[4] = [ 'orphan_row' => [ 'label' => 'Orphan Row' ] ];

			return $items;
		};

		add_filter( 'gfpdf_system_status_report_items', $callback );

		$sections = $this->get_sections();

		remove_filter( 'gfpdf_system_status_report_items', $callback );

		$this->assertArrayHasKey( 'row_0', $sections['php'] );
		$this->assertArrayHasKey( 'row_1', $sections['directories'] );
		$this->assertArrayHasKey( 'row_2', $sections['global'] );
		$this->assertArrayHasKey( 'row_3', $sections['security'] );

		$this->assertArrayNotHasKey( 'charset', $sections['php'] );
		$this->assertArrayNotHasKey( 'orphan_row', $sections['cache'] );
		$this->assertArrayNotHasKey( 4, $sections );
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

		/* And the feature is still in the report */
		$this->assertContains( 'Legacy Templates (Unsupported)', array_column( $system_report[0]['tables'][0]['items'], 'label_export' ) );

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

		$configuration = $this->get_table( 'configuration' );

		remove_filter( 'gfpdf_system_status_report_sections', $callback );

		$this->assertSame( 'From the named filter', end( $configuration )['value'] );
	}

	/**
	 * @since 6.18.0
	 */
	public function test_folder_permissions_tests_the_folders_when_the_report_is_built() {
		$gfpdf = $this->gfpdf();

		/* A folder that failed the last check, and has been fixed since */
		update_option( Model_Install::UNWRITABLE_FOLDERS, [ '/srv/fixed-since/' => time() ] );

		$row = $this->get_sections()['directories']['folder_permissions'];
		$this->assertSame( 'Writable', $row['value_export'] );
		$this->assertTrue( $row['is_valid'] );
		$this->assertSame( [], get_option( Model_Install::UNWRITABLE_FOLDERS ) );

		$dir = $gfpdf->data->template_tmp_location;
		$row = $this->get_directories_with_unwritable( [ $dir, '/srv/pdf-tmp/mpdf' ] )['folder_permissions'];

		$this->assertFalse( $row['is_valid'] );
		$this->assertSame( 'Not writable: ' . $dir . ', /srv/pdf-tmp/mpdf', $row['value_export'] );
		$this->assertSame( 'Not writable:<br><code>' . esc_html( $dir ) . '</code><br><code>/srv/pdf-tmp/mpdf</code>', $row['value'] );
		$this->assertSame( 'Ask your web host to make these folders writable.', $row['validation_message'] );
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
			return $this->get_sections()['directories'];
		} finally {
			$gfpdf->singleton->add_class( $installer );
		}
	}

	/**
	 * The report sections as both filters leave them, keyed by section and row
	 */
	protected function get_sections(): array {
		$sections = [];
		$capture  = static function ( $items ) use ( &$sections ) {
			$sections = $items;

			return $items;
		};

		add_filter( 'gfpdf_system_status_report_sections', $capture, PHP_INT_MAX );
		apply_filters( 'gform_system_report', [] );
		remove_filter( 'gfpdf_system_status_report_sections', $capture, PHP_INT_MAX );

		return $sections;
	}

	/**
	 * The rows of the Checks or Configuration table
	 */
	protected function get_table( string $id ): array {
		foreach ( apply_filters( 'gform_system_report', [] )[0]['tables'] as $table ) {
			if ( $table['id'] === $id ) {
				return $table['items'];
			}
		}

		return [];
	}

	/**
	 * Get the rows of the Unsupported section, which only lists features when something removed is still in use
	 */
	protected function get_unsupported_features(): array {
		return $this->get_sections()[ Deprecation::GROUP_UNSUPPORTED ];
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
		$this->assertStringContainsString( '#business-plus--tier-2-template-upgrade-guide', $items['business_plus_templates']['validation_message'] );

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
		$this->assertArrayNotHasKey( 'tools_tab_hooks', $this->get_sections()[ Deprecation::GROUP_DEPRECATED ] );

		add_filter( 'gfpdf_settings_tools', '__return_empty_array' );
		Deprecation::flush_cache();

		$items = $this->get_sections()[ Deprecation::GROUP_DEPRECATED ];
		$this->assertArrayHasKey( 'tools_tab_hooks', $items );
		$this->assertSame( 'gfpdf_settings_tools has 1 listener', $items['tools_tab_hooks']['value_export'] );
		$this->assertSame( 'Tools Tab Actions and Filters (Deprecated)', $items['tools_tab_hooks']['label_export'] );
		$this->assertStringStartsWith( 'Will be removed in Gravity PDF 7.0. <a href=', $items['tools_tab_hooks']['validation_message'] );

		remove_filter( 'gfpdf_settings_tools', '__return_empty_array' );
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

		$items = $this->get_sections()['cache'];

		$this->assertSame( 'Off', $items['pdf_cache']['value_export'] );
		$this->assertSame( 'Never run', $items['cache_cleanup']['value_export'] );
		$this->assertTrue( $items['cache_cleanup']['is_valid'] );

		$this->gfpdf()->options->update_option( 'pdf_cache', 'Yes' );
		$this->gfpdf()->options->update_option( 'cache_duration', 6 );

		update_option(
			Model_Pdf_Cache::SWEEP_STATE_OPTION,
			[
				'last_slice_at'    => time(),
				'last_complete_at' => strtotime( '2026-11-11 08:00:00 UTC' ),
				'last_pass'        => [
					'mode'         => 'expire',
					'started'      => time() - HOUR_IN_SECONDS,
					'files_reaped' => 3,
					'bytes_reaped' => 3 * MB_IN_BYTES,
					'files_left'   => 1,
					'bytes_left'   => 1536,
				],
			]
		);

		$items = $this->get_sections()['cache'];

		$this->assertSame( 'On, 6 hours', $items['pdf_cache']['value_export'] );
		$this->assertSame( '2026-11-11 08:00:00 UTC. Removed 3 files (3.00 MB), kept 1 (0.00 MB).', $items['cache_cleanup']['value_export'] );
		$this->assertStringStartsWith( 'Last ran ', $items['cache_cleanup']['value'] );
	}

	public function test_the_pdf_cache_row_and_site_health_warn_when_cron_is_not_cleaning_the_cache() {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$test = apply_filters( 'site_status_tests', [ 'direct' => [] ] )['direct']['gravity_pdf_cache_cleanup']['test'];

		wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
		wp_schedule_event( time() - 2 * HOUR_IN_SECONDS, 'hourly', 'gfpdf_cleanup_tmp_dir' );
		update_option( Model_Pdf_Cache::SWEEP_STATE_OPTION, [ 'last_slice_at' => time() - 2 * DAY_IN_SECONDS ] );

		/* Nothing cached, nothing to warn about */
		$this->assertTrue( $this->get_sections()['cache']['cache_cleanup']['is_valid'] );
		$this->assertSame( 'good', call_user_func( $test )['status'] );

		$entry_dir = $this->gfpdf()->data->template_tmp_location . ( is_multisite() ? get_current_blog_id() . '/' : '' ) . 'cache/e999999/';
		wp_mkdir_p( $entry_dir );

		try {
			$this->assertFalse( $this->get_sections()['cache']['cache_cleanup']['is_valid'] );
			$this->assertSame( 'recommended', call_user_func( $test )['status'] );

			/* A sweep ran recently, but the hourly event is overdue */
			update_option( Model_Pdf_Cache::SWEEP_STATE_OPTION, [ 'last_slice_at' => time() ] );
			$this->assertSame( 'recommended', call_user_func( $test )['status'] );

			wp_clear_scheduled_hook( 'gfpdf_cleanup_tmp_dir' );
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'gfpdf_cleanup_tmp_dir' );
			$this->assertTrue( $this->get_sections()['cache']['cache_cleanup']['is_valid'] );
			$this->assertSame( 'good', call_user_func( $test )['status'] );
		} finally {
			rmdir( $entry_dir );
		}
	}

	public function test_core_fonts_lists_given_up_files_even_after_the_notice_is_dismissed() {
		$this->use_report_downloader( [ 'Garuda.ttf', 'FreeSans.ttf' ] );
		$this->set_font_failures(
			[
				'Garuda.ttf'   => $this->failure( strtotime( '2026-11-03 12:00:00 UTC' ) ),
				'FreeSans.ttf' => $this->failure(),
			]
		);

		$row = $this->report_row( 'core_fonts' );

		$this->assertFalse( $row['is_valid'] );
		$this->assertSame( '1 failed: Garuda.ttf (gave up 2026-11-03)', $row['value_export'] );
		$this->assertSame( '1 failed:<br><code>Garuda.ttf</code>', $row['value'] );
		$this->assertStringStartsWith( 'Some characters may not display in PDFs. <a href="https://docs.gravitypdf.com/users/core-pdf-fonts">', $row['validation_message'] );

		\GPDFAPI::get_mvc_class( 'Model_Actions' )->dismiss_failed_core_fonts();

		$this->assertFalse( \GPDFAPI::get_mvc_class( 'Model_Actions' )->has_failed_core_fonts() );
		$this->assertSame( '1 failed: Garuda.ttf (gave up 2026-11-03)', $this->report_row( 'core_fonts' )['value_export'] );
	}

	public function test_core_fonts_lists_missing_files_while_the_font_installer_is_off() {
		$this->use_report_downloader( [ 'Garuda.ttf' ] );
		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$row = $this->report_row( 'core_fonts' );

		$this->assertFalse( $row['is_valid'] );
		$this->assertSame( '1 missing: Garuda.ttf', $row['value_export'] );
		$this->assertStringStartsWith( 'The Font Installer is turned off by the gfpdf_auto_install_fonts filter.', $row['validation_message'] );
	}

	public function test_core_fonts_and_the_notice_clear_once_the_font_is_on_disk() {
		$this->list_fixture_as( 'Chewy.ttf', 'Garuda.ttf' );
		$this->copy_fixture( 'Chewy.ttf', 'Garuda.ttf' );
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure( time() ) ] );

		$this->assertSame( 'None failed', $this->report_row( 'core_fonts' )['value_export'] );
		$this->assertTrue( $this->report_row( 'core_fonts' )['is_valid'] );
		$this->assertFalse( \GPDFAPI::get_mvc_class( 'Model_Actions' )->has_failed_core_fonts() );
	}

	public function test_the_font_rows_can_be_linked_to() {
		$this->use_report_downloader( [] );

		$this->assertStringContainsString( 'id="' . Model_System_Report::FONTS_FAILED_ANCHOR . '"', $this->report_row( 'core_fonts' )['label'] );
		$this->assertStringContainsString( 'id="' . Model_System_Report::FONT_DOWNLOADS_ANCHOR . '"', $this->report_row( 'core_font_downloads' )['label'] );
	}

	public function test_core_fonts_says_none_failed_without_a_given_up_file() {
		$this->use_report_downloader( [ 'Garuda.ttf' ] );
		$this->set_font_failures( [ 'Garuda.ttf' => $this->failure() ] );

		$this->assertSame( 'None failed', $this->report_row( 'core_fonts' )['value_export'] );
	}

	public function test_the_font_installer_row_reads_the_downloaders_status() {
		$this->use_report_downloader( [ 'Garuda.ttf' ] );

		$this->assertSame( 'Font Installer', $this->report_row( 'core_font_downloads' )['label_export'] );
		$this->assertSame( 'On', $this->report_row( 'core_font_downloads' )['value'] );
		$this->assertSame( 'On (default)', $this->report_row( 'core_font_downloads' )['value_export'] );

		add_filter( 'gfpdf_auto_install_fonts', '__return_false' );

		$this->assertSame( 'Off (gfpdf_auto_install_fonts filter)', $this->report_row( 'core_font_downloads' )['value_export'] );
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
		return $this->get_sections()['directories'][ $key ];
	}
}
