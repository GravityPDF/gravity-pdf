<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Class-scoped form/entry fixtures plus ergonomic accessors.
 *
 * New pattern (preferred): each test class declares the fixtures it needs in
 * set_up_before_class() via load_fixtures(), reads them via $this->form() /
 * $this->entry(), and inherits cleanup via tear_down_after_class().
 *
 * Legacy pattern (Phase A–C only): the accessors fall back to the shared
 * $GLOBALS['GFPDF_Test'] global populated by tools/phpunit/bootstrap.php. The
 * fallback and assertFixturesIntact() are removed in Phase D once every test
 * file has been migrated to the new pattern.
 */
trait HasGfpdfFixtures {

	/**
	 * Form key → entry-fixture filename. The legacy bootstrap mixes
	 * -entries.json and -entry.json suffixes, so a map is the source of truth.
	 */
	private static $entry_filenames = [
		'all-form-fields'         => 'all-form-fields-entries.json',
		'gravityform-1'           => 'gravityform-1-entries.json',
		'repeater-empty-form'     => 'repeater-empty-entry.json',
		'repeater-consent-form'   => 'repeater-consent-entry.json',
		'non-group-products-form' => 'non-group-products-form-entries.json',
	];

	/**
	 * Per-class fixture cache, keyed by class name (late static binding).
	 *
	 * Shape: [ 'Test_Foo' => [ 'forms' => [ key => array ], 'entries' => [ key => array[] ] ] ].
	 */
	private static $fixture_caches = [];

	/**
	 * Class-scoped fixture loader. Call from set_up_before_class().
	 *
	 * @param string[] $forms   Form keys; each loads tools/phpunit/data/forms/<key>.json.
	 * @param string[] $entries Entry-set keys; the parent form must be in $forms
	 *                          (entries are created against the just-loaded form's ID).
	 */
	protected static function load_fixtures( array $forms = [], array $entries = [] ) {
		$factory = new \GF_UnitTest_Factory();
		$class   = static::class;
		$cache   = self::$fixture_caches[ $class ] ?? [ 'forms' => [], 'entries' => [] ];

		foreach ( $forms as $key ) {
			$cache['forms'][ $key ] = $factory->form->import_fixture_and_get( "$key.json" );
		}

		foreach ( $entries as $key ) {
			if ( ! isset( $cache['forms'][ $key ] ) ) {
				throw new \LogicException( "Cannot load entry set '$key' before its parent form." );
			}
			if ( ! isset( self::$entry_filenames[ $key ] ) ) {
				throw new \LogicException( "No entry fixture mapping for '$key'." );
			}

			$cache['entries'][ $key ] = $factory->entry->import_many_and_get(
				self::$entry_filenames[ $key ],
				$cache['forms'][ $key ]['id']
			);
		}

		self::$fixture_caches[ $class ] = $cache;
	}

	/**
	 * Deletes class-scoped fixtures from the database. Call from tear_down_after_class().
	 *
	 * Forms+entries created by load_fixtures() outlive WP's per-test transaction
	 * (GFAPI writes go to non-transactional tables), so without this each class
	 * leaks its fixtures into subsequent classes.
	 */
	protected static function cleanup_class_fixtures() {
		$class = static::class;
		if ( ! isset( self::$fixture_caches[ $class ] ) ) {
			return;
		}
		$cache = self::$fixture_caches[ $class ];

		foreach ( $cache['entries'] as $entries ) {
			foreach ( $entries as $entry ) {
				\GFAPI::delete_entry( $entry['id'] );
			}
		}
		foreach ( $cache['forms'] as $form ) {
			\GFAPI::delete_form( $form['id'] );
		}
		unset( self::$fixture_caches[ $class ] );
	}

	/**
	 * Returns the form fixture stored under $key. Checks the per-class cache
	 * first; falls back to the legacy global during the migration window.
	 *
	 * @param string $key Form key.
	 *
	 * @return array
	 */
	protected function form( $key ) {
		$cache = self::$fixture_caches[ static::class ]['forms'] ?? [];
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		if ( ! isset( $GLOBALS['GFPDF_Test']->form[ $key ] ) ) {
			$available = implode( ', ', array_keys( (array) $GLOBALS['GFPDF_Test']->form ) );
			$this->fail( "Form fixture '$key' is not loaded. Available: $available" );
		}

		return $GLOBALS['GFPDF_Test']->form[ $key ];
	}

	/**
	 * Returns one of the entry fixtures stored under $key. Same lookup order
	 * as form(): per-class cache first, legacy global fallback.
	 *
	 * @param string $key   Entry-set key (same key as the parent form).
	 * @param int    $index Zero-based index into the entry list.
	 *
	 * @return array
	 */
	protected function entry( $key, $index = 0 ) {
		$cache = self::$fixture_caches[ static::class ]['entries'] ?? [];
		if ( isset( $cache[ $key ][ $index ] ) ) {
			return $cache[ $key ][ $index ];
		}

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
	 * Sentinel check that the legacy shared global is intact. Catches tests that
	 * mutate $GLOBALS['GFPDF_Test'] without restoring it. Removed in Phase D
	 * along with the global itself.
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
