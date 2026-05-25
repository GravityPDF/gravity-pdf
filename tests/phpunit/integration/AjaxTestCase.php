<?php

declare(strict_types=1);

namespace GFPDF\Tests\Integration;

use GFPDF\Tests\Concerns\HasGfpdfFixtures;
use WP_Ajax_UnitTestCase;

abstract class AjaxTestCase extends WP_Ajax_UnitTestCase {

	use HasGfpdfFixtures;

	public function set_up() {
		parent::set_up();
		$this->assertFixturesIntact();
	}
}
