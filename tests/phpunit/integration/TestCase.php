<?php

declare(strict_types=1);

namespace GFPDF\Tests\Integration;

use GFPDF\Tests\Concerns\HasGfpdfFixtures;
use GFPDF\Tests\Concerns\UsesFactory;
use WP_UnitTestCase;

abstract class TestCase extends WP_UnitTestCase {

	use HasGfpdfFixtures;
	use UsesFactory;

	public function set_up() {
		parent::set_up();
		$this->gfpdf()->data->form_settings = [];
	}

	public static function tear_down_after_class() {
		static::cleanup_class_fixtures();
		parent::tear_down_after_class();
	}
}
