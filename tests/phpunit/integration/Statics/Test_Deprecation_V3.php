<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Tests\Concerns\CreatesLegacyDownloadUrls;
use GFPDF\Tests\Concerns\CreatesLegacyTemplates;
use GFPDF\Tests\Concerns\ResetsDetectedFeatures;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group     statics
 */
class Test_Deprecation_V3 extends TestCase {

	use CreatesLegacyDownloadUrls;
	use CreatesLegacyTemplates;
	use ResetsDetectedFeatures;

	public function set_up(): void {
		parent::set_up();

		$this->reset_detected_features();
	}

	/**
	 * Passes either way on Gravity Forms 2.9, which still declares the class; it is 3.0 the shim exists for
	 */
	public function test_restore_v3_form_class_puts_back_the_class_v3_templates_guard_on() {
		Deprecation_V3::restore_v3_form_class();

		$this->assertTrue( class_exists( 'RGForms' ) );
	}

	/**
	 * A Business Plus template drives the PDF engine itself, which is the one thing a plain v3 template never does
	 */
	public function test_legacy_templates_are_split_by_whether_they_drive_the_pdf_engine() {
		$standard      = $this->create_legacy_template( 'my-standard-template.php' );
		$business_plus = $this->create_legacy_template( 'my-business-plus-template.php', '$mpdf->AddPage();' );

		$this->assertSame( [ $business_plus ], array_keys( Deprecation_V3::get_business_plus_templates() ) );
		$this->assertArrayHasKey( $standard, Deprecation_V3::get_legacy_templates() );
		$this->assertArrayNotHasKey( $business_plus, Deprecation_V3::get_legacy_templates() );

		$this->delete_legacy_templates( $standard, $business_plus );
	}

	/**
	 * A report naming the file alone leaves the reader to search every form for it, so the forms selecting it
	 * travel with it
	 */
	public function test_legacy_templates_are_reported_with_the_forms_they_are_configured_on() {
		$used   = $this->create_legacy_template( 'my-used-template.php' );
		$unused = $this->create_legacy_template( 'my-unused-template.php' );

		$form_id = (int) $this->gf_factory()->form->create();

		/* Two PDFs on the one template is still the one form to fix */
		$this->gf_factory()->pdf->set_form_id( $form_id )->create( [ 'template' => 'my-used-template' ] );
		$this->gf_factory()->pdf->set_form_id( $form_id )->create( [ 'template' => 'my-used-template' ] );

		$templates = Deprecation_V3::get_legacy_templates();

		$this->assertSame( [ $form_id ], $templates[ $used ] );
		$this->assertSame( [], $templates[ $unused ] );

		$this->delete_legacy_templates( $used, $unused );
	}

	/**
	 * The `LIKE` narrowing the scan only gets it to the rows worth reading, so what a PDF actually selects has to
	 * settle which template a form is reported against
	 */
	public function test_a_template_named_elsewhere_in_a_form_is_not_reported_against_it() {
		$path = $this->create_legacy_template( 'my-mentioned-template.php' );
		$form = \GFAPI::get_form( $this->gf_factory()->form->create() );

		$form['description'] = 'Grab the my-mentioned-template.php file from the downloads page';
		\GFAPI::update_form( $form );

		/* The PDF is what puts the form in front of the scan at all, and it selects something else */
		$this->gf_factory()->pdf->set_form_id( (int) $form['id'] )->create( [ 'template' => 'zadani' ] );

		$this->assertSame( [], Deprecation_V3::get_legacy_templates()[ $path ] );

		$this->delete_legacy_templates( $path );
	}

	public function test_a_trashed_form_is_not_reported_against_a_template() {
		$path    = $this->create_legacy_template( 'my-trashed-form-template.php' );
		$form_id = (int) $this->gf_factory()->form->create();

		$this->gf_factory()->pdf->set_form_id( $form_id )->create( [ 'template' => 'my-trashed-form-template' ] );

		$this->assertSame( [ $form_id ], Deprecation_V3::get_legacy_templates()[ $path ] );

		/* A trashed form isn't in the user's form list, so there's nothing for them to act on */
		\GFAPI::delete_form( $form_id );
		Deprecation_V3::flush_cache();

		$this->assertSame( [], Deprecation_V3::get_legacy_templates()[ $path ] );

		$this->delete_legacy_templates( $path );
	}

	public function test_the_v3_features_are_registered() {
		$features = Deprecation::get_features();

		$this->assertSame(
			[ 'legacy_templates', 'business_plus_templates', 'legacy_endpoint', 'deprecated_filters' ],
			array_keys( $features )
		);

		$this->assertSame( Deprecation_V3::REMOVED_IN, $features['legacy_templates']['removed_in'] );
		$this->assertSame( Deprecation::GROUP_DEPRECATED, $features['legacy_templates']['group'] );
	}

	public function test_get_legacy_download_urls_searches_the_whole_form() {
		$this->assertSame( [], Deprecation_V3::get_legacy_download_urls() );

		$form_ids = [
			$this->create_form_with_legacy_url( 'form' ),
			$this->create_form_with_legacy_url( 'confirmations' ),
			$this->create_form_with_legacy_url( 'notifications' ),
		];

		sort( $form_ids );

		$this->assertSame( $form_ids, Deprecation_V3::get_legacy_download_urls() );

		/* A form that has never handed out a legacy URL isn't reported */
		$this->gf_factory()->form->create();

		$this->assertSame( $form_ids, Deprecation_V3::get_legacy_download_urls() );
	}

	public function test_get_legacy_download_urls_ignores_a_partial_marker() {
		$this->create_form_with_legacy_url( 'form', 'gf_pdf=0' );

		/* Only `gf_pdf=1` routes to the legacy endpoint, so anything else is a false positive */
		$this->assertSame( [], Deprecation_V3::get_legacy_download_urls() );
	}

	public function test_get_legacy_download_urls_includes_the_recorded_forms() {
		$scanned  = $this->create_form_with_legacy_url();
		$recorded = (int) $this->gf_factory()->form->create();

		Deprecation_V3::record_legacy_endpoint_usage( $recorded );

		/* A URL served for a form that never handed one out lives somewhere the scan can't see */
		$this->assertSame( [ $recorded ], Deprecation_V3::get_recorded_legacy_endpoint_usage() );
		$this->assertSame( [ min( $scanned, $recorded ), max( $scanned, $recorded ) ], Deprecation_V3::get_legacy_download_urls() );

		/* A form found by both sources is only reported once */
		Deprecation_V3::record_legacy_endpoint_usage( $scanned );

		$this->assertCount( 2, Deprecation_V3::get_legacy_download_urls() );
	}

	public function test_record_legacy_endpoint_usage_writes_once_a_day_per_form() {
		$form_id = (int) $this->gf_factory()->form->create();

		Deprecation_V3::record_legacy_endpoint_usage( $form_id );

		/* Written from the front end but read on three admin paths, so it stays out of the autoloaded set */
		$this->assertArrayNotHasKey( Deprecation_V3::LEGACY_ENDPOINT_OPTION, wp_load_alloptions() );

		$writes = 0;
		add_action(
			'update_option_' . Deprecation_V3::LEGACY_ENDPOINT_OPTION,
			function () use ( &$writes ) {
				++$writes;
			}
		);

		/* Every later request that day reads the record and leaves it alone */
		Deprecation_V3::record_legacy_endpoint_usage( $form_id );

		$this->assertSame( 0, $writes );
		$this->assertSame( [ $form_id ], Deprecation_V3::get_recorded_legacy_endpoint_usage() );

		/* A link still being followed a day later refreshes the record, so it never reaches the expiry */
		$stale = time() - Deprecation_V3::LEGACY_ENDPOINT_REFRESH - 1;
		update_option( Deprecation_V3::LEGACY_ENDPOINT_OPTION, [ $form_id => $stale ], false );

		Deprecation_V3::record_legacy_endpoint_usage( $form_id );

		$this->assertGreaterThan( $stale, get_option( Deprecation_V3::LEGACY_ENDPOINT_OPTION )[ $form_id ] );
	}

	public function test_get_recorded_legacy_endpoint_usage_expires_a_quiet_form() {
		$form_id = (int) $this->gf_factory()->form->create();

		Deprecation_V3::record_legacy_endpoint_usage( $form_id );

		$this->assertSame( [ $form_id ], Deprecation_V3::get_recorded_legacy_endpoint_usage() );

		/* Nobody has followed a legacy URL for this form in a month, so it's no longer evidence one exists */
		update_option( Deprecation_V3::LEGACY_ENDPOINT_OPTION, [ $form_id => time() - Deprecation_V3::LEGACY_ENDPOINT_TTL - 1 ], false );

		$this->assertSame( [], Deprecation_V3::get_recorded_legacy_endpoint_usage() );
		$this->assertSame( [], Deprecation_V3::get_legacy_download_urls() );

		/* The expired record is flushed on the way out rather than left for the uninstaller */
		$this->assertSame( [], get_option( Deprecation_V3::LEGACY_ENDPOINT_OPTION ) );

		/* The next legacy URL served for the form puts it back */
		Deprecation_V3::record_legacy_endpoint_usage( $form_id );

		$this->assertSame( [ $form_id ], Deprecation_V3::get_recorded_legacy_endpoint_usage() );
	}

	public function test_get_legacy_download_urls_skips_trashed_recorded_forms() {
		$form_id = (int) $this->gf_factory()->form->create();

		Deprecation_V3::record_legacy_endpoint_usage( $form_id );

		$this->assertSame( [ $form_id ], Deprecation_V3::get_legacy_download_urls() );

		/* A trashed form isn't in the user's form list, so there's nothing for them to act on */
		\GFAPI::delete_form( $form_id );

		$this->assertSame( [], Deprecation_V3::get_legacy_download_urls() );
	}

	public function test_get_legacy_download_urls_skips_trashed_forms() {
		$form_id = $this->create_form_with_legacy_url();

		$this->assertSame( [ $form_id ], Deprecation_V3::get_legacy_download_urls() );

		/* A trashed form isn't in the user's form list, so there's nothing for them to act on */
		\GFAPI::delete_form( $form_id );

		$this->assertSame( [], Deprecation_V3::get_legacy_download_urls() );
	}

	public function test_get_active_deprecated_filters_ignores_our_own_callback() {
		$this->assertNotFalse( has_filter( 'gfpdfe_pre_load_template', Deprecation_V3::INTERNAL_FILTER_CALLBACK ) );
		$this->assertArrayNotHasKey( 'gfpdfe_pre_load_template', Deprecation_V3::get_active_deprecated_filters() );
	}

	public function test_get_active_deprecated_filters_includes_dynamic_hooks() {
		$callback = '__return_true';

		add_filter( 'gfpdfe_pdf_template_10', $callback );
		add_filter( 'gfpdf_rtl', $callback );

		$active = Deprecation_V3::get_active_deprecated_filters();

		$this->assertSame( 1, $active['gfpdfe_pdf_template_10'] );
		$this->assertSame( 1, $active['gfpdf_rtl'] );

		remove_filter( 'gfpdfe_pdf_template_10', $callback );
		remove_filter( 'gfpdf_rtl', $callback );
	}

	public function test_get_active_deprecated_filters_includes_the_legacy_hooks() {
		$callback = '__return_true';

		/* These carry the gfpdf_legacy_ prefix rather than gfpdfe_, so only the map can find them */
		add_filter( 'gfpdf_legacy_save_path', $callback );
		add_action( 'gfpdf_legacy_pre_view_or_download_pdf', $callback );

		$active = Deprecation_V3::get_active_deprecated_filters();

		$this->assertSame( 1, $active['gfpdf_legacy_save_path'] );
		$this->assertSame( 1, $active['gfpdf_legacy_pre_view_or_download_pdf'] );

		remove_filter( 'gfpdf_legacy_save_path', $callback );
		remove_action( 'gfpdf_legacy_pre_view_or_download_pdf', $callback );
	}

	/**
	 * The detection reads template files, not PDF settings, so a Core template stays out of the report no matter
	 * how a PDF using it is configured
	 */
	public function test_the_advanced_templating_setting_alone_does_not_report_a_template() {
		$this->create_form_with_advanced_templating();

		$this->assertArrayNotHasKey( PDF_PLUGIN_DIR . 'src/templates/zadani.php', Deprecation_V3::get_legacy_templates() );
		$this->assertSame( [], Deprecation_V3::get_business_plus_templates() );
	}
}
