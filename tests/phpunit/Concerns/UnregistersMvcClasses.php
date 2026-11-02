<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use ReflectionProperty;

/**
 * Runs a callback with a class missing from GPDFAPI::get_mvc_class(), as when an add-on unhooks it, or with another
 * object in its place
 */
trait UnregistersMvcClasses {

	/**
	 * @return mixed The callback's return value
	 */
	protected function without_mvc_class( string $name, callable $callback ) {
		return $this->with_mvc_class( $name, null, $callback );
	}

	/**
	 * @param object|null $replacement Null unregisters the class
	 *
	 * @return mixed The callback's return value
	 */
	protected function with_mvc_class( string $name, $replacement, callable $callback ) {
		global $gfpdf;

		$original  = $gfpdf->singleton;
		$singleton = clone $original;

		$classes = new ReflectionProperty( $singleton, 'classes' );
		if ( PHP_VERSION_ID < 80100 ) {
			$classes->setAccessible( true );
		}
		$registered = $classes->getValue( $singleton );
		if ( $replacement === null ) {
			unset( $registered[ $name ] );
		} else {
			$registered[ $name ] = $replacement;
		}
		$classes->setValue( $singleton, $registered );

		$gfpdf->singleton = $singleton;

		try {
			return $callback();
		} finally {
			$gfpdf->singleton = $original;
		}
	}
}
