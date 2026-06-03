<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Class-scoped form/entry fixtures plus ergonomic accessors.
 *
 * Each test class declares the fixtures it needs in set_up_before_class() via
 * load_fixtures(), reads them via $this->form() / $this->entry() / $this->entries(),
 * and inherits cleanup via tear_down_after_class() (wired in TestCase / AjaxTestCase).
 */
trait HasGfpdfFixtures {

	/**
	 * Form key → entry-fixture filename. The original bootstrap mixed
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
	 *
	 * Protected (not private) so subclasses can patch entries after load_fixtures
	 * — e.g. Test_Form_Data rewrites file-upload URLs to match the per-class form's
	 * upload directory before tests run.
	 */
	protected static $fixture_caches = [];

	/**
	 * Shared GF_UnitTest_Factory used by load_fixtures(). The factory
	 * constructor reads tools/phpunit/data/forms/standard.json from disk; caching
	 * the instance avoids repeating that for every class that loads fixtures.
	 */
	private static $shared_gf_factory;

	/**
	 * Class-scoped fixture loader. Call from set_up_before_class().
	 *
	 * @param string[] $forms   Form keys; each loads tools/phpunit/data/forms/<key>.json.
	 * @param string[] $entries Entry-set keys; the parent form must be in $forms
	 *                          (entries are created against the just-loaded form's ID).
	 */
	protected static function load_fixtures( array $forms = [], array $entries = [] ) {
		if ( null === self::$shared_gf_factory ) {
			self::$shared_gf_factory = new \GF_UnitTest_Factory();
		}
		$factory = self::$shared_gf_factory;
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
	 * Returns the form fixture stored under $key (declared via load_fixtures).
	 *
	 * @param string $key Form key.
	 *
	 * @return array
	 */
	protected function form( $key ) {
		$cache = self::$fixture_caches[ static::class ]['forms'] ?? [];
		if ( ! isset( $cache[ $key ] ) ) {
			$available = implode( ', ', array_keys( $cache ) ) ?: '(none)';
			$this->fail( "Form fixture '$key' is not loaded. Available in " . static::class . ": $available" );
		}

		return $cache[ $key ];
	}

	/**
	 * Returns one of the entry fixtures stored under $key.
	 *
	 * @param string $key   Entry-set key (same key as the parent form).
	 * @param int    $index Zero-based index into the entry list.
	 *
	 * @return array
	 */
	protected function entry( $key, $index = 0 ) {
		$cache = self::$fixture_caches[ static::class ]['entries'] ?? [];
		if ( ! isset( $cache[ $key ][ $index ] ) ) {
			$this->fail( "Entry fixture '$key'[$index] is not loaded in " . static::class . '.' );
		}

		return $cache[ $key ][ $index ];
	}

	/**
	 * Returns the full entry list for $key (for foreach/array_column use).
	 *
	 * @param string $key Entry-set key.
	 *
	 * @return array[]
	 */
	protected function entries( $key ) {
		$cache = self::$fixture_caches[ static::class ]['entries'] ?? [];
		if ( ! isset( $cache[ $key ] ) ) {
			$this->fail( "Entry fixture set '$key' is not loaded in " . static::class . '.' );
		}

		return $cache[ $key ];
	}

	/** Chewy.ttf is owned by font-admin tests (Test_Api) and must not be pre-copied. */
	private static $render_fonts = [
		'DejaVuSans.ttf',
		'DejaVuSans-Bold.ttf',
		'DejaVuSansCondensed.ttf',
		'DejaVuSerifCondensed.ttf',
	];

	/** Call from set_up_before_class(); pair with remove_test_fonts() in tear_down_after_class(). */
	protected static function copy_test_fonts() {
		global $gfpdf;
		foreach ( self::$render_fonts as $font ) {
			@copy(
				PDF_PLUGIN_DIR . '/tools/phpunit/data/fonts/' . $font,
				$gfpdf->data->template_font_location . $font
			);
		}
	}

	protected static function remove_test_fonts() {
		global $gfpdf;
		foreach ( self::$render_fonts as $font ) {
			@unlink( $gfpdf->data->template_font_location . $font );
		}
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
	 * Returns the first field with the given type from a fixture form.
	 *
	 * For predicates beyond a plain type match (Likert's inputType/inputs,
	 * Fileupload's multipleFiles, lookup-by-id) keep the loop inline — this
	 * helper deliberately only covers the type-only case.
	 *
	 * @param string $type Field type, e.g. 'phone', 'address'.
	 * @param string $key  Fixture key (must be declared via load_fixtures()).
	 *
	 * @return \GF_Field
	 */
	protected function field_from_fixture( $type, $key = 'all-form-fields' ) {
		foreach ( $this->form( $key )['fields'] as $field ) {
			if ( $field->type === $type ) {
				return $field;
			}
		}

		$this->fail( "No field of type '$type' found in fixture '$key'." );
	}

	/**
	 * Loads a fixture form + first entry and wires the form's PDF settings into
	 * $gfpdf->data->form_settings. Pre-clears form_settings so prior tests in the
	 * class can't bleed in.
	 *
	 * @param string $key Fixture key (must be declared via load_fixtures()).
	 *
	 * @return array{form: array, entry: array}
	 */
	protected function form_and_entry( $key = 'all-form-fields' ) {
		$form  = $this->form( $key );
		$entry = $this->entry( $key );

		$gfpdf                                     = $this->gfpdf();
		$gfpdf->data->form_settings                = [];
		$gfpdf->data->form_settings[ $form['id'] ] = $form['gfpdf_form_settings'];

		return [
			'form'  => $form,
			'entry' => $entry,
		];
	}
}
