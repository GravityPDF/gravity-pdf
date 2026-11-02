<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

use ReflectionProperty;

/**
 * Reads and writes a property tests can't reach otherwise
 */
trait AccessesPrivateState {

	/**
	 * @param string $class The class declaring the property, if not the object's own
	 *
	 * @return mixed
	 */
	protected function get_private( object $object, string $name, string $class = '' ) {
		return $this->private_property( $object, $name, $class )->getValue( $object );
	}

	/**
	 * @param mixed  $value
	 * @param string $class The class declaring the property, if not the object's own
	 */
	protected function set_private( object $object, string $name, $value, string $class = '' ): void {
		$this->private_property( $object, $name, $class )->setValue( $object, $value );
	}

	private function private_property( object $object, string $name, string $class ): ReflectionProperty {
		$property = new ReflectionProperty( $class !== '' ? $class : get_class( $object ), $name );
		if ( PHP_VERSION_ID < 80100 ) {
			$property->setAccessible( true );
		}

		return $property;
	}
}
