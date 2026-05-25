<?php

declare(strict_types=1);

namespace GFPDF\Tests\Integration;

use GFPDF\Tests\Concerns\HasGfpdfFixtures;
use WP_UnitTestCase;

/**
 * Default base for non-AJAX integration tests.
 *
 * Use AjaxTestCase instead when the test exercises a wp_ajax_* action.
 */
abstract class TestCase extends WP_UnitTestCase {

	use HasGfpdfFixtures;

	public function set_up() {
		parent::set_up();
		$this->assertFixturesIntact();
	}
}
