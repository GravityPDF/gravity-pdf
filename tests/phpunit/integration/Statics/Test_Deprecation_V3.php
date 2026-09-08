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
	 * A Business Plus template hands itself to the Advanced Templating add-on, which a plain v3 template never does
	 */
	public function test_legacy_templates_are_split_by_whether_they_call_the_addon() {
		$standard      = $this->create_legacy_template( 'my-standard-template.php' );
		$business_plus = $this->create_legacy_template( 'my-business-plus-template.php', 'gfpdfe_business_plus::initilise( $pdf_name );' );

		$this->assertSame( [ $business_plus ], array_keys( Deprecation_V3::get_business_plus_templates() ) );
		$this->assertArrayHasKey( $standard, Deprecation_V3::get_legacy_templates() );
		$this->assertArrayNotHasKey( $business_plus, Deprecation_V3::get_legacy_templates() );

		$this->delete_legacy_templates( $standard, $business_plus );
	}

	/**
	 * PHP resolves the call whatever case it is written in, so the file is read the same way
	 */
	public function test_the_addon_call_is_matched_regardless_of_case() {
		$path = $this->create_legacy_template( 'my-shouty-template.php', 'GFPDFE_Business_Plus::Initilise( $pdf_name );' );

		$this->assertArrayHasKey( $path, Deprecation_V3::get_business_plus_templates() );

		$this->delete_legacy_templates( $path );
	}

	/**
	 * Touching `$mpdf` is not what makes a template Business Plus: the add-on's own boilerplate never does it, and
	 * plenty of plain v3 templates reach the engine through a filter instead
	 */
	public function test_a_template_using_mpdf_alone_is_not_business_plus() {
		$path = $this->create_legacy_template( 'my-mpdf-template.php', '$mpdf->AddPage();' );

		$this->assertSame( [], Deprecation_V3::get_business_plus_templates() );
		$this->assertArrayHasKey( $path, Deprecation_V3::get_legacy_templates() );

		$this->delete_legacy_templates( $path );
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
		Deprecation::flush_cache();

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

		/* 7.0 removed everything the v3 provider detects, hooks included, so nothing here is merely deprecated */
		foreach ( $features as $feature ) {
			$this->assertSame( Deprecation::GROUP_UNSUPPORTED, $feature['group'] );
		}
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

		/* Only `gf_pdf=1` routed to the legacy endpoint, so anything else is a false positive */
		$this->assertSame( [], Deprecation_V3::get_legacy_download_urls() );
	}

	public function test_get_legacy_download_urls_skips_trashed_forms() {
		$form_id = $this->create_form_with_legacy_url();

		$this->assertSame( [ $form_id ], Deprecation_V3::get_legacy_download_urls() );

		/* A trashed form isn't in the user's form list, so there's nothing for them to act on */
		\GFAPI::delete_form( $form_id );

		$this->assertSame( [], Deprecation_V3::get_legacy_download_urls() );
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
	 * Nothing in core listens to these any more, so every callback the detector finds belongs to a third party
	 */
	public function test_get_active_deprecated_filters_reports_nothing_on_a_clean_install() {
		$this->assertSame( [], Deprecation_V3::get_active_deprecated_filters() );
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
