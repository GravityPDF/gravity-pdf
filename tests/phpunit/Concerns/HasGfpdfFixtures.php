<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Ergonomic accessors for the shared form/entry fixtures loaded once per
 * suite by tools/phpunit/bootstrap.php into $GLOBALS['GFPDF_Test'].
 *
 * Lives in a trait so a future AJAX base extending WP_Ajax_UnitTestCase can
 * share the same accessors without duplicating them (PHP has no multiple
 * inheritance).
 */
trait HasGfpdfFixtures {

	/**
	 * Returns the shared form fixture stored under $key.
	 *
	 * @param string $key Fixture key as registered by bootstrap.php::create_stubs().
	 *
	 * @return array
	 */
	protected function form( $key ) {
		if ( ! isset( $GLOBALS['GFPDF_Test']->form[ $key ] ) ) {
			$available = implode( ', ', array_keys( (array) $GLOBALS['GFPDF_Test']->form ) );
			$this->fail( "Form fixture '$key' is not loaded. Available: $available" );
		}

		return $GLOBALS['GFPDF_Test']->form[ $key ];
	}

	/**
	 * Returns one of the shared entry fixtures stored under $key.
	 *
	 * @param string $key   Same key as the parent form.
	 * @param int    $index Zero-based index into the entry list.
	 *
	 * @return array
	 */
	protected function entry( $key, $index = 0 ) {
		if ( ! isset( $GLOBALS['GFPDF_Test']->entries[ $key ][ $index ] ) ) {
			$this->fail( "Entry fixture '$key'[$index] is not loaded." );
		}

		return $GLOBALS['GFPDF_Test']->entries[ $key ][ $index ];
	}

	/**
	 * Returns the Gravity PDF Router (DI container).
	 *
	 * @return \GFPDF\Router
	 */
	protected function gfpdf() {
		global $gfpdf;

		return $gfpdf;
	}

	/**
	 * Sentinel check that the shared fixture catalogue is intact.
	 *
	 * Called from each base class's set_up(). Catches the failure mode where
	 * a previous test deleted a shared form/entry from $GLOBALS['GFPDF_Test'].
	 * Deep mutation of fixture contents is not checked — too expensive at
	 * 1100+ tests; this is a fast presence check.
	 */
	protected function assertFixturesIntact() {
		$expected_forms = [
			'all-form-fields',
			'form-settings',
			'gravityform-1',
			'gravityform-2',
			'repeater-empty-form',
			'repeater-consent-form',
			'non-group-products-form',
		];

		$expected_entries = [
			'all-form-fields',
			'gravityform-1',
			'repeater-empty-form',
			'repeater-consent-form',
			'non-group-products-form',
		];

		foreach ( $expected_forms as $key ) {
			$this->assertNotEmpty(
				$GLOBALS['GFPDF_Test']->form[ $key ] ?? null,
				"Shared form fixture '$key' missing — a prior test mutated \$GLOBALS['GFPDF_Test']->form"
			);
		}

		foreach ( $expected_entries as $key ) {
			$this->assertNotEmpty(
				$GLOBALS['GFPDF_Test']->entries[ $key ] ?? null,
				"Shared entry fixture '$key' missing — a prior test mutated \$GLOBALS['GFPDF_Test']->entries"
			);
		}
	}
}
