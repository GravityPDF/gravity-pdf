<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration\Exceptions;

use DomainException;
use Exception;
use GFPDF\Exceptions\GravityPdfDatabaseUpdateException;
use GFPDF\Exceptions\GravityPdfDomainException;
use GFPDF\Exceptions\GravityPdfException;
use GFPDF\Exceptions\GravityPdfFontNotFoundException;
use GFPDF\Exceptions\GravityPdfIdException;
use GFPDF\Exceptions\GravityPdfModelNotUpdatedException;
use GFPDF\Exceptions\GravityPdfRuntimeException;
use GFPDF\Exceptions\GravityPdfShortcodeEntryIdException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConditionalLogicFailedException;
use GFPDF\Exceptions\GravityPdfShortcodePdfConfigNotFoundException;
use GFPDF\Exceptions\GravityPdfShortcodePdfInactiveException;
use GFPDF\Tests\Integration\TestCase;
use RuntimeException;

/**
 * @package GFPDF\Exceptions
 *
 * @group   exceptions
 */
class Test_Exception_Hierarchy extends TestCase {

	/**
	 * @dataProvider provider_hierarchy
	 */
	public function test_extends_expected_parent( string $class, string $parent ) {
		$this->assertTrue(
			is_subclass_of( $class, $parent ),
			"$class must extend $parent"
		);
	}

	/**
	 * @dataProvider provider_hierarchy
	 */
	public function test_constructor_passes_message_and_code( string $class ) {
		$instance = new $class( 'msg', 42 );

		$this->assertSame( 'msg', $instance->getMessage() );
		$this->assertSame( 42, $instance->getCode() );
	}

	public function provider_hierarchy(): array {
		return [
			'GravityPdfException → Exception'                              => [ GravityPdfException::class, Exception::class ],
			'GravityPdfRuntimeException → RuntimeException'                => [ GravityPdfRuntimeException::class, RuntimeException::class ],
			'GravityPdfDomainException → DomainException'                  => [ GravityPdfDomainException::class, DomainException::class ],
			'GravityPdfDatabaseUpdateException → GravityPdfRuntimeException' => [ GravityPdfDatabaseUpdateException::class, GravityPdfRuntimeException::class ],
			'GravityPdfFontNotFoundException → GravityPdfDomainException'  => [ GravityPdfFontNotFoundException::class, GravityPdfDomainException::class ],
			'GravityPdfIdException → GravityPdfException'                  => [ GravityPdfIdException::class, GravityPdfException::class ],
			'GravityPdfModelNotUpdatedException → GravityPdfException'     => [ GravityPdfModelNotUpdatedException::class, GravityPdfException::class ],
			'GravityPdfShortcodeEntryIdException → GravityPdfException'    => [ GravityPdfShortcodeEntryIdException::class, GravityPdfException::class ],
			'GravityPdfShortcodePdfConditionalLogicFailedException → GravityPdfException' => [ GravityPdfShortcodePdfConditionalLogicFailedException::class, GravityPdfException::class ],
			'GravityPdfShortcodePdfConfigNotFoundException → GravityPdfException' => [ GravityPdfShortcodePdfConfigNotFoundException::class, GravityPdfException::class ],
			'GravityPdfShortcodePdfInactiveException → GravityPdfException' => [ GravityPdfShortcodePdfInactiveException::class, GravityPdfException::class ],
		];
	}
}
