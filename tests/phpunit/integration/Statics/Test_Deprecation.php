<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Helper\Helper_Interface_Deprecated_Features;
use GFPDF\Tests\Concerns\CreatesLegacyDownloadUrls;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group     statics
 */
class Test_Deprecation extends TestCase {

	use CreatesLegacyDownloadUrls;

	public function test_apply_filters_skips_hooks_without_a_listener() {
		$this->assertSame( 'my-pdf', Deprecation::apply_filters( 'gfpdfe_pdf_filename', [ 'my-pdf' ] ) );
	}

	public function test_apply_filters_warns_when_a_listener_is_attached() {
		$this->setExpectedDeprecated( 'gfpdfe_pdf_filename' );

		$callback = static function () {
			return 'filtered-pdf';
		};

		add_filter( 'gfpdfe_pdf_filename', $callback );

		$this->assertSame( 'filtered-pdf', Deprecation::apply_filters( 'gfpdfe_pdf_filename', [ 'my-pdf' ] ) );

		remove_filter( 'gfpdfe_pdf_filename', $callback );
	}

	public function test_get_signals_omits_empty_signals() {
		$this->create_form_with_legacy_url();

		$this->assertSame( [ 'legacy_endpoint' ], array_keys( Deprecation::get_signals() ) );
	}

	/**
	 * Nothing here knows about the v3 layer, so a later round of removals only has to register itself
	 */
	public function test_a_provider_supplies_its_own_features() {
		Fake_Deprecated_Features::$detections = [];

		$this->assertSame( [ 'fake_feature', 'fake_gone_feature' ], array_keys( Fake_Deprecation::get_features() ) );

		/* Each feature names the release it goes in, rather than sharing one with everything else */
		$this->assertSame( '7.1', Fake_Deprecation::get_feature( 'fake_feature' )['removed_in'] );

		/* A feature the site doesn't use isn't reported */
		$this->assertSame( [], Fake_Deprecation::get_signals() );

		Fake_Deprecated_Features::$detections = [ 'a detection' ];
		Fake_Deprecation::flush_cache();

		$signals = Fake_Deprecation::get_signals();

		$this->assertSame(
			[
				'fake_feature'      => [ 'a detection' ],
				'fake_gone_feature' => [ 'a detection' ],
			],
			$signals
		);

		/* A provider declaring both groups gets both back, in the order the engine reports them */
		$this->assertSame(
			[ Deprecation::GROUP_UNSUPPORTED, Deprecation::GROUP_DEPRECATED ],
			array_keys( Fake_Deprecation::group_signals( $signals ) )
		);
		$this->assertSame(
			[ Deprecation::GROUP_UNSUPPORTED, Deprecation::GROUP_DEPRECATED ],
			Fake_Deprecation::get_groups()
		);

		/* The v3 provider declares one group today, so no surface carries the other around empty */
		$this->assertSame( [ Deprecation::GROUP_DEPRECATED ], Deprecation::get_groups() );

		Fake_Deprecated_Features::$detections = [];
	}

	/**
	 * The uninstaller asks the registry, so a provider's storage goes with it without touching Model_Uninstall
	 */
	public function test_delete_stored_data_removes_what_each_provider_stores() {
		update_option( Fake_Deprecated_Features::OPTION, [ 1 ], false );
		update_option( Deprecation_V3::LEGACY_ENDPOINT_OPTION, [ 1 => time() ], false );

		Fake_Deprecation::delete_stored_data();

		$this->assertFalse( get_option( Fake_Deprecated_Features::OPTION ) );

		/* The registry is swapped out, so the v3 option is untouched */
		$this->assertNotFalse( get_option( Deprecation_V3::LEGACY_ENDPOINT_OPTION ) );

		Deprecation::delete_stored_data();

		$this->assertFalse( get_option( Deprecation_V3::LEGACY_ENDPOINT_OPTION ) );
	}

	public function test_get_feature_is_empty_for_an_unregistered_id() {
		$this->assertSame( [], Deprecation::get_feature( 'not-a-feature' ) );
	}
}

/**
 * A round of removals that has nothing to do with the v3 layer
 */
class Fake_Deprecated_Features implements Helper_Interface_Deprecated_Features {

	const OPTION = 'gfpdf_fake_feature_usage';

	/**
	 * @var array What the feature's detector reports
	 */
	public static $detections = [];

	public static function get_features(): array {
		return [
			'fake_feature'     => [
				'label'      => 'Fake Feature',
				'group'      => Deprecation::GROUP_DEPRECATED,
				'removed_in' => '7.1',
				'url'        => 'https://example.org/upgrade/fake-feature/',
				'detect'     => [ static::class, 'detect' ],
			],

			'fake_gone_feature' => [
				'label'      => 'Fake Gone Feature',
				'group'      => Deprecation::GROUP_UNSUPPORTED,
				'removed_in' => '7.1',
				'url'        => 'https://example.org/upgrade/fake-gone-feature/',
				'detect'     => [ static::class, 'detect' ],
			],
		];
	}

	public static function detect(): array {
		return static::$detections;
	}

	public static function get_stored_options(): array {
		return [ self::OPTION ];
	}

	public static function flush_cache(): void {
	}
}

/**
 * Swaps the registry out for the fake provider, which is all registering a new one amounts to
 */
class Fake_Deprecation extends Deprecation {

	protected static function get_providers(): array {
		return [ Fake_Deprecated_Features::class ];
	}
}
