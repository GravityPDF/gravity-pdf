<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Tests\Concerns\HasGfpdfFixtures;
use GFPDF\Tests\Concerns\UsesFactory;
use WP_UnitTestCase;

abstract class TestCase extends WP_UnitTestCase {

	use HasGfpdfFixtures;
	use UsesFactory;

	public function set_up(): void {
		parent::set_up();
		$this->gfpdf()->data->form_settings = [];

		/*
		 * The gfpdf_settings_user_data transient is written by Test_Options_API
		 * and a few others. Single-site relies on the WP test transaction to
		 * roll it back, but a persistent object cache (Redis/Memcache) bypasses
		 * that transaction and the value survives across tests, causing
		 * is_gfpdf_page()-driven branches to misbehave. Promote the clean-up
		 * that Test_PDF / Test_Model_Pdf already do ad-hoc to a blanket reset.
		 */
		delete_transient( 'gfpdf_settings_user_data' );

		/* Deprecation holds its detections for the request; a test process is many requests' worth of site state */
		\GFPDF\Statics\Deprecation::flush_cache();
	}

	public static function tear_down_after_class(): void {
		static::cleanup_class_fixtures();
		parent::tear_down_after_class();
	}
}
