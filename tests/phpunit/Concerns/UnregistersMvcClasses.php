<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use ReflectionProperty;

/**
 * Runs a callback while GPDFAPI::get_mvc_class() returns false for a class, as when an add-on unhooks it
 */
trait UnregistersMvcClasses {

	/**
	 * @return mixed The callback's return value
	 */
	protected function without_mvc_class( string $name, callable $callback ) {
		global $gfpdf;

		$original  = $gfpdf->singleton;
		$singleton = clone $original;

		$classes = new ReflectionProperty( $singleton, 'classes' );
		if ( PHP_VERSION_ID < 80100 ) {
			$classes->setAccessible( true );
		}
		$registered = $classes->getValue( $singleton );
		unset( $registered[ $name ] );
		$classes->setValue( $singleton, $registered );

		$gfpdf->singleton = $singleton;

		try {
			return $callback();
		} finally {
			$gfpdf->singleton = $original;
		}
	}
}
