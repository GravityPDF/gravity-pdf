<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Shares one Gravity Forms factory across the tests in a class.
 */
trait UsesFactory {

	/**
	 * @var \GF_UnitTest_Factory
	 */
	protected $gf_factory;

	protected function gf_factory(): \GF_UnitTest_Factory {
		return $this->gf_factory ?? ( $this->gf_factory = new \GF_UnitTest_Factory() );
	}
}
