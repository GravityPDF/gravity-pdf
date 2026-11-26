<?php
declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_View_System_Report
 *
 * @package GFPDF\View
 *
 * @group   view
 * @group   system-report
 */
class Test_View_System_Report extends TestCase {
	/**
	 * @var View_System_Report
	 */
	protected $view;

	public function set_up(): void {
		parent::set_up();

		$this->view = new View_System_Report();
	}

	public function test_memory_limit_success() {
		$this->setExpectedDeprecated( View_System_Report::class . '::memory_limit_markup' );

		$this->assertStringContainsString( 'Unlimited', $this->view->memory_limit_markup( -1 ) );
		$this->assertStringContainsString( '128MB', $this->view->memory_limit_markup( 128 ) );

		$output = $this->view->memory_limit_markup( 96 );

		$this->assertStringContainsString( '96MB', $output );
		$this->assertStringContainsString( 'you have at least 128MB', $output );
	}

	public function test_temp_folder_protected_success() {
		$this->setExpectedDeprecated( View_System_Report::class . '::get_temp_folder_protected' );

		$this->assertStringNotContainsString( 'temporary directory is publicly accessible', $this->view->get_temp_folder_protected( true ) );
		$this->assertStringContainsString( 'temporary directory is publicly accessible', $this->view->get_temp_folder_protected( false ) );
	}

	public function test_allow_url_fopen_success() {
		$this->setExpectedDeprecated( View_System_Report::class . '::get_allow_url_fopen' );

		$this->assertStringNotContainsString( 'is disabled', $this->view->get_allow_url_fopen( true ) );
		$this->assertStringContainsString( 'is disabled', $this->view->get_allow_url_fopen( false ) );
	}

	public function test_the_combined_deprecated_features_test_still_works() {
		$this->setExpectedDeprecated( View_System_Report::class . '::get_deprecated_features_test' );

		$this->assertSame( 'good', $this->view->get_deprecated_features_test( [] )['status'] );
	}

	public function test_the_per_group_debug_information_still_works() {
		$this->setExpectedDeprecated( View_System_Report::class . '::get_deprecated_debug_information' );

		$this->assertArrayHasKey( 'gravity-pdf-unsupported', $this->view->get_deprecated_debug_information( [] ) );
	}

	public function test_a_report_row_test_shows_the_fix_only_when_the_check_fails() {
		$test = [
			'status' => 'critical',
			'good'   => 'Fine',
			'bad'    => 'Broken',
		];

		$pass = $this->view->get_report_row_test( 'gravity_pdf_check', $test, [ 'label' => 'Check', 'value' => 'On', 'is_valid' => true ] );
		$fail = $this->view->get_report_row_test( 'gravity_pdf_check', $test, [ 'label' => 'Check', 'value' => 'Off', 'is_valid' => false, 'validation_message' => 'Turn it on.' ] );

		$this->assertSame( [ 'good', 'Fine', '<p>Check: On</p>', '' ], [ $pass['status'], $pass['label'], $pass['description'], $pass['actions'] ] );
		$this->assertSame( [ 'critical', 'Broken', '<p>Check: Off</p><p>Turn it on.</p>' ], [ $fail['status'], $fail['label'], $fail['description'] ] );
		$this->assertStringContainsString( 'page=gf_system_status', $fail['actions'] );
	}

	public function test_the_debug_information_marks_each_check_as_the_copied_report_does() {
		$report = [
			[
				'tables' => [
					[
						'items' => [
							[ 'label_export' => 'PDF Memory Limit', 'value_export' => '64MB', 'is_valid' => false ],
							[ 'label_export' => 'allow_url_fopen', 'value_export' => 'On', 'is_valid' => true ],
						],
					],
					[
						'items' => [
							[ 'label_export' => 'Entry View', 'value_export' => 'View (default)' ],
						],
					],
				],
			],
		];

		$this->assertSame(
			[
				'pdf_memory_limit' => [ 'label' => 'PDF Memory Limit', 'value' => '64MB ✘' ],
				'allow_url_fopen'  => [ 'label' => 'allow_url_fopen', 'value' => 'On ✔' ],
				'entry_view'       => [ 'label' => 'Entry View', 'value' => 'View (default)' ],
			],
			$this->view->get_report_debug_information( $report )['gravity-pdf']['fields']
		);
	}

	public function test_get_icon() {
		$this->setExpectedDeprecated( View_System_Report::class . '::get_icon' );

		$this->assertStringContainsString( 'yes', $this->view->get_icon( true ) );
		$this->assertStringContainsString( 'error', $this->view->get_icon( false ) );
	}

	public function test_maybe_get_active_icon() {
		$this->setExpectedDeprecated( View_System_Report::class . '::maybe_get_active_icon' );

		$this->assertStringContainsString( 'yes', $this->view->maybe_get_active_icon( true ) );
		$this->assertEmpty( $this->view->maybe_get_active_icon( false ) );
	}

	public function test_the_deprecated_folder_and_template_messages_still_render() {
		$this->setExpectedDeprecated( View_System_Report::class . '::get_folder_permissions' );
		$this->setExpectedDeprecated( View_System_Report::class . '::get_template_check_message' );
		$this->setExpectedDeprecated( View_System_Report::class . '::get_template_upgrade_message' );

		$this->assertStringContainsString( '<code>/srv/fonts/</code>', $this->view->get_folder_permissions( [ '/srv/fonts/' ] ) );
		$this->assertStringContainsString( 'zadani.php version 1.0 is out of date', $this->view->get_template_check_message( 'zadani.php', '1.0', '2.0' )['value_export'] );
		$this->assertStringContainsString( 'Learn how to update', $this->view->get_template_upgrade_message() );
	}

	public function test_a_failing_check_shows_the_fix_on_screen_only() {
		$row = $this->view->get_check( [ 'label' => 'Check' ], 'check passed', $this->view->get_fix( 'Fix it <now>.', 'https://example.com/?a=1&b=2', 'How' ) );

		$this->assertFalse( $row['is_valid'] );
		$this->assertSame( 'Fix it &lt;now&gt;. <a href="https://example.com/?a=1&#038;b=2" style="white-space: nowrap">How</a>', $row['validation_message'] );
		$this->assertSame( '', $row['validation_message_export'] );
	}

	public function test_a_passing_check_keeps_its_summary() {
		$row = $this->view->get_check( [ 'label' => 'Check' ], 'check <passed>' );

		$this->assertTrue( $row['is_valid'] );
		$this->assertSame( 'check &lt;passed&gt;', $row['summary'] );
		$this->assertArrayNotHasKey( 'validation_message', $row );
	}

	public function test_a_failing_rows_fix_starts_a_line_of_its_own() {
		$rows = $this->view->get_failed_rows(
			[
				[ 'value' => '64MB', 'value_export' => '64MB', 'is_valid' => false, 'validation_message' => 'Fix it.' ],
				[ 'value' => 'list.php', 'is_valid' => false, 'validation_message' => 'Fix it.' ],
				[ 'value' => 'a.php<br>', 'is_valid' => false, 'validation_message' => 'Fix it.' ],
				[ 'value' => 'b.php<hr />', 'is_valid' => false, 'validation_message' => 'Fix it.' ],
				[ 'value' => 'c.php<p><a href="#">Guide</a></p>', 'is_valid' => false, 'validation_message' => 'Fix it.' ],
				[ 'value' => 'Off', 'is_valid' => false ],
				[ 'value' => 'On', 'is_valid' => true, 'validation_message' => 'Fix it.' ],
			]
		);

		$this->assertSame( [ '64MB<br>', 'list.php<br>', 'a.php<br>', 'b.php<hr />', 'c.php<p><a href="#">Guide</a></p>', 'Off', 'On' ], array_column( $rows, 'value' ) );
		$this->assertSame( [ '64MB', 'list.php' ], array_column( array_slice( $rows, 0, 2 ), 'value_export' ) );
		$this->assertArrayNotHasKey( 'value_export', $rows[2] );
	}

	public function test_get_code_lines() {
		$this->assertSame( '<code>a.ttf</code><br><code>&lt;b&gt;.ttf</code>', $this->view->get_code_lines( [ 'a.ttf', '<b>.ttf' ] ) );
	}
}
