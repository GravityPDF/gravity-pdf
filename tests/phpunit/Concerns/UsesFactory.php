<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

use GF_UnitTest_Factory;

/**
 * Exposes the existing GF_UnitTest_Factory (tools/phpunit/gravityforms-factory.php).
 * Used in place of direct GFAPI::add_form() / add_entry() calls in test bodies.
 */
trait UsesFactory {

	/** @var GF_UnitTest_Factory|null */
	private $gfpdf_factory;

	/**
	 * @return GF_UnitTest_Factory
	 */
	protected function gf_factory() {
		if ( null === $this->gfpdf_factory ) {
			$this->gfpdf_factory = new GF_UnitTest_Factory();
		}

		return $this->gfpdf_factory;
	}
}
