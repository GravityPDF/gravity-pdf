<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Helper\Helper_Singleton;
use GFPDF\Tests\Concerns\AssertsSnapshots;
use GPDFAPI;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use WP_UnitTestCase;

/**
 * Snapshots the API add-ons depend on: GPDFAPI, the core classes first-party add-ons use, the Router and the
 * get_mvc_class() keys. Members inherited from vendor or PHP classes are left out, so an mPDF upgrade can't churn it.
 *
 * @group snapshot
 */
class Test_Public_Api_Contract extends WP_UnitTestCase {

	use AssertsSnapshots;

	/* Classes first-party add-ons extend, implement or use: protected members are frozen too */
	private const EXTENDED = [
		\GFPDF\Helper\Fields\Field_Checkbox::class,
		\GFPDF\Helper\Fields\Field_Form::class,
		\GFPDF\Helper\Fields\Field_Multiselect::class,
		\GFPDF\Helper\Fields\Field_Option::class,
		\GFPDF\Helper\Fields\Field_Product::class,
		\GFPDF\Helper\Fields\Field_Radio::class,
		\GFPDF\Helper\Fields\Field_Select::class,
		\GFPDF\Helper\Fields\Field_Shipping::class,
		\GFPDF\Helper\Helper_Abstract_Addon::class,
		\GFPDF\Helper\Helper_Abstract_Config_Settings::class,
		\GFPDF\Helper\Helper_Abstract_Fields::class,
		\GFPDF\Helper\Helper_Abstract_Pdf_Shortcode::class,
		\GFPDF\Helper\Helper_Interface_Actions::class,
		\GFPDF\Helper\Helper_Interface_Config::class,
		\GFPDF\Helper\Helper_Interface_Extension_Settings::class,
		\GFPDF\Helper\Helper_Interface_Field_Pdf_Config::class,
		\GFPDF\Helper\Helper_Interface_Filters::class,
		\GFPDF\Helper\Helper_Interface_Setup_TearDown::class,
		\GFPDF\Helper\Helper_Trait_Logger::class,
		\GFPDF\Helper\Mpdf\Mpdf::class,
	];

	/* Classes first-party add-ons only call, plus the Router and the get_mvc_class() targets: public members only */
	private const CALLED = [
		GPDFAPI::class,
		\GFPDF\Controller\Controller_Shortcodes::class,
		\GFPDF\Exceptions\GravityPdfException::class,
		\GFPDF\Exceptions\GravityPdfShortcodeEntryIdException::class,
		\GFPDF\Exceptions\GravityPdfShortcodePdfConditionalLogicFailedException::class,
		\GFPDF\Exceptions\GravityPdfShortcodePdfConfigNotFoundException::class,
		\GFPDF\Exceptions\GravityPdfShortcodePdfInactiveException::class,
		\GFPDF\Helper\Fields\Field_Products::class,
		\GFPDF\Helper\Helper_Abstract_Options::class,
		\GFPDF\Helper\Helper_Data::class,
		\GFPDF\Helper\Helper_Form::class,
		\GFPDF\Helper\Helper_Logger::class,
		\GFPDF\Helper\Helper_Misc::class,
		\GFPDF\Helper\Helper_Mpdf::class,
		\GFPDF\Helper\Helper_Notices::class,
		\GFPDF\Helper\Helper_PDF::class,
		\GFPDF\Helper\Helper_Singleton::class,
		\GFPDF\Helper\Helper_Templates::class,
		\GFPDF\Helper\Helper_Url_Signer::class,
		\GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater::class,
		\GFPDF\Model\Model_Form_Settings::class,
		\GFPDF\Model\Model_Install::class,
		\GFPDF\Model\Model_PDF::class,
		\GFPDF\Model\Model_Shortcodes::class,
		\GFPDF\Router::class,
		\GFPDF\Statics\Kses::class,
	];

	public function test_public_api_matches_snapshot(): void {
		$lines = [];
		foreach ( array_merge( self::EXTENDED, self::CALLED ) as $class ) {
			array_push( $lines, ...$this->describe_class( new ReflectionClass( $class ), in_array( $class, self::EXTENDED, true ) ) );
			$lines[] = '';
		}

		$this->assertMatchesSnapshot( 'public-api.txt', implode( "\n", $lines ) );
	}

	public function test_mvc_class_keys_match_snapshot(): void {
		$property = new ReflectionProperty( Helper_Singleton::class, 'classes' );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		$lines = [];
		foreach ( $property->getValue( $GLOBALS['gfpdf']->singleton ) as $key => $instance ) {
			$lines[] = $key . ' => ' . get_class( $instance );
		}
		sort( $lines, SORT_STRING );

		$this->assertMatchesSnapshot( 'mvc-classes.txt', implode( "\n", $lines ) . "\n" );
	}

	/**
	 * @return list<string>
	 */
	private function describe_class( ReflectionClass $class, bool $extended ): array {
		$visible = $extended ? ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED : ReflectionMethod::IS_PUBLIC;

		if ( $class->isInterface() ) {
			$kind = 'interface';
		} elseif ( $class->isTrait() ) {
			$kind = 'trait';
		} else {
			$kind = 'class';
		}

		$modifiers = array_filter( [ $class->isAbstract() && ! $class->isInterface() ? 'abstract' : '', $class->isFinal() ? 'final' : '', $kind ] );
		$head      = implode( ' ', $modifiers ) . ' ' . $class->getName();

		$parent = $class->getParentClass();
		if ( $parent ) {
			$head .= ' extends ' . $parent->getName();
		}

		// PHP 8 adds Stringable to any class with __toString(), so PHP 7.4 would render a different line
		$interfaces = array_values( array_diff( $class->getInterfaceNames(), [ 'Stringable' ] ) );
		sort( $interfaces, SORT_STRING );
		if ( $interfaces ) {
			$head .= ( $class->isInterface() ? ' extends ' : ' implements ' ) . implode( ', ', $interfaces );
		}

		$members = [];

		foreach ( $class->getReflectionConstants() as $constant ) {
			if ( ( $constant->isPublic() || ( $extended && $constant->isProtected() ) ) && $this->is_own( $constant->getDeclaringClass() ) ) {
				$members[] = '    const ' . $constant->getName() . ' = ' . $this->export( $constant->getValue() );
			}
		}

		foreach ( $class->getProperties( $visible ) as $property ) {
			if ( $this->is_own( $property->getDeclaringClass() ) ) {
				$modifiers = array_filter( [ $property->isPublic() ? 'public' : 'protected', $property->isStatic() ? 'static' : '', $this->type( $property->getType() ) ] );
				$members[] = '    ' . implode( ' ', $modifiers ) . ' $' . $property->getName();
			}
		}

		foreach ( $class->getMethods( $visible ) as $method ) {
			if ( $this->is_own( $method->getDeclaringClass() ) ) {
				$members[] = '    ' . $this->describe_method( $method );
			}
		}

		sort( $members, SORT_STRING );
		array_unshift( $members, $head );

		return $members;
	}

	private function describe_method( ReflectionMethod $method ): string {
		$modifiers = array_filter(
			[
				$method->isAbstract() ? 'abstract' : '',
				$method->isFinal() ? 'final' : '',
				$method->isPublic() ? 'public' : 'protected',
				$method->isStatic() ? 'static' : '',
			]
		);

		$params = array_map( [ $this, 'describe_parameter' ], $method->getParameters() );
		$return = $this->type( $method->getReturnType() );

		return implode( ' ', $modifiers ) . ' function ' . ( $method->returnsReference() ? '&' : '' ) . $method->getName()
			. '(' . implode( ', ', $params ) . ')' . ( $return !== '' ? ': ' . $return : '' );
	}

	private function describe_parameter( ReflectionParameter $param ): string {
		$type = $this->type( $param->getType() );
		$out  = ( $type !== '' ? $type . ' ' : '' )
			. ( $param->isPassedByReference() ? '&' : '' )
			. ( $param->isVariadic() ? '...' : '' )
			. '$' . $param->getName();

		if ( $param->isDefaultValueAvailable() ) {
			$out .= ' = ' . ( $param->isDefaultValueConstant() ? $param->getDefaultValueConstantName() : $this->export( $param->getDefaultValue() ) );
		}

		return $out;
	}

	/* Built by hand because (string) ReflectionNamedType drops the leading `?` on PHP 7.4 */
	private function type( ?ReflectionType $type ): string {
		if ( $type === null ) {
			return '';
		}

		if ( $type instanceof ReflectionNamedType ) {
			return ( $type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '' ) . $type->getName();
		}

		return (string) $type;
	}

	private function is_own( ReflectionClass $class ): bool {
		return $class->getName() === GPDFAPI::class || strpos( $class->getName(), 'GFPDF\\' ) === 0;
	}

	/**
	 * @param mixed $value
	 */
	private function export( $value ): string {
		if ( $value === null ) {
			return 'null';
		}

		if ( $value === [] ) {
			return '[]';
		}

		return (string) preg_replace( '/\s+/', ' ', var_export( $value, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
	}
}
