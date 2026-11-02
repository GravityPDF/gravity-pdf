<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use GFPDF_Vendor\Mpdf\Language\LanguageToFontInterface;

/**
 * A language mapper that answers every language with the same value
 */
class FakeLanguageToFont implements LanguageToFontInterface {

	/**
	 * @var mixed
	 */
	public $returned;

	/**
	 * @param mixed $returned
	 */
	public function __construct( $returned ) {
		$this->returned = $returned;
	}

	public function getLanguageOptions( $llcc, $adobeCJK ) {
		return $this->returned;
	}
}
