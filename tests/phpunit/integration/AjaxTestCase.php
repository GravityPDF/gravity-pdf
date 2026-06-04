<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Tests\Concerns\HasGfpdfFixtures;
use GFPDF\Tests\Concerns\UsesFactory;
use WP_Ajax_UnitTestCase;

abstract class AjaxTestCase extends WP_Ajax_UnitTestCase {

	use HasGfpdfFixtures;
	use UsesFactory;

	public function set_up(): void {
		parent::set_up();

		/*
		 * Parent strips these in set_up_before_class() but WP_UnitTestCase::tear_down()
		 * restores hooks from a snapshot that still has them. With WP_HTTP_BLOCK_EXTERNAL
		 * on (see wp-tests-config.php) leaving them in place makes wp_version_check()
		 * trigger_error() on the blocked request, which PHPUnit escalates to a test error.
		 */
		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );

		$this->gfpdf()->data->form_settings = [];
		delete_transient( 'gfpdf_settings_user_data' );
	}

	public static function tear_down_after_class(): void {
		static::cleanup_class_fixtures();
		parent::tear_down_after_class();
	}
}
