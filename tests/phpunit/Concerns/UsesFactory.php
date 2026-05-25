<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

use GF_UnitTest_Factory;

/**
 * Exposes the existing GF_UnitTest_Factory (tools/phpunit/gravityforms-factory.php).
 *
 * Phase 3 of the PHPUnit refactor adopts this trait to replace inline
 * GFAPI::add_form() / add_entry() calls in test bodies. Not used in Phase 1.
 */
trait UsesFactory {

	/**
	 * @var GF_UnitTest_Factory|null Cached per-test instance.
	 */
	private $gfpdf_factory;

	/**
	 * Lazy accessor for the Gravity Forms unit-test factory.
	 *
	 * @return GF_UnitTest_Factory
	 */
	protected function factory() {
		if ( null === $this->gfpdf_factory ) {
			$this->gfpdf_factory = new GF_UnitTest_Factory();
		}

		return $this->gfpdf_factory;
	}
}
