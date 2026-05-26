# Gravity PDF — PHPUnit Test Suite

This directory holds the plugin's PHPUnit integration tests, run inside the
`wp-env` Docker container via `yarn test:php` / `yarn test:php:multisite`.

The suite is mid-refactor; see [`.claude/plans/2026-05-25-phpunit-tests-refactor.md`](../../.claude/plans/2026-05-25-phpunit-tests-refactor.md)
for the phase plan and [`COVERAGE_BASELINE.md`](COVERAGE_BASELINE.md) for the
runtime + coverage baseline every PR is compared against.

## Layout

```
tests/phpunit/
├── COVERAGE_BASELINE.md
├── README.md                         ← you are here
├── Concerns/                         ← shared traits (NOT discovered by PHPUnit)
│   ├── HasGfpdfFixtures.php
│   └── UsesFactory.php
└── integration/                      ← mirrors src/ 1:1
    ├── TestCase.php
    ├── AjaxTestCase.php
    ├── Controller/
    ├── Helper/
    ├── Model/
    ├── Rest/
    ├── Statics/
    └── View/
```

`unit-tests/` and `integration/` co-exist during Phases 1–2. Both directories
are listed in `tools/phpunit/config.xml`. Phase 2 ends with `unit-tests/`
empty and the config entry removed.

## Naming convention

| Source file | Test file |
| :--- | :--- |
| `src/Statics/Cache.php` | `tests/phpunit/integration/Statics/Test_Cache.php` |
| `src/Model/Model_PDF.php` | `tests/phpunit/integration/Model/Test_Model_PDF.php` |
| `src/Controller/Controller_Settings.php` | `tests/phpunit/integration/Controller/Test_Controller_Settings.php` |

One `Test_<ClassName>.php` per non-trivial `src/` class, at the matching path.
Class name = `Test_<ClassName>`; method names use `test_` snake_case to keep
`phpunit --filter test_something` searches predictable.

## Base class

| Need | Base class |
| :--- | :--- |
| Standard integration test | `\GFPDF\Tests\Integration\TestCase` |
| Test that dispatches a `wp_ajax_*` action via `_handleAjax()` | `\GFPDF\Tests\Integration\AjaxTestCase` |

Both extend the WordPress stock test cases and `use` two traits:

`\GFPDF\Tests\Concerns\HasGfpdfFixtures` provides:
- `static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] )` — class-scoped
  loader. Call from `set_up_before_class()` to declare the forms/entries this
  test class needs. Forms+entries are created via the factory once per class
  and cleaned up in `tear_down_after_class()`.
- `$this->form( 'all-form-fields' )` — form fixture (per-class cache; falls back
  to the legacy `$GLOBALS['GFPDF_Test']` global until the migration is complete).
- `$this->entry( 'all-form-fields', 0 )` — entry fixture, same lookup order.
- `$this->gfpdf()` — the `GFPDF\Router` DI container (same as the `$gfpdf` global).
- `$this->assertFixturesIntact()` — automatically called from `set_up()` to catch
  cross-test mutation of the legacy global. Override `set_up()` only if you call
  `parent::set_up()`. Removed when the legacy global is removed (Phase D of the
  fixtures-to-factory migration).

`\GFPDF\Tests\Concerns\UsesFactory` provides:
- `$this->gf_factory()` — returns the `GF_UnitTest_Factory` (`tools/phpunit/gravityforms-factory.php`).
  Use this for per-test forms/entries. Named `gf_factory()` to avoid colliding
  with `WP_UnitTestCase::factory()` (the static WP factory accessed via
  `self::factory()->user->create()` etc.).

## Writing a new test

```php
<?php

declare(strict_types=1);

namespace GFPDF\Statics;

use GFPDF\Tests\Integration\TestCase;

/**
 * @group statics
 */
class Test_Cache extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		static::load_fixtures(
			[ 'all-form-fields' ],   // forms to create
			[ 'all-form-fields' ]    // entry sets to create against those forms
		);
	}

	public function test_hash_is_stable() {
		$form  = $this->form( 'all-form-fields' );
		$entry = $this->entry( 'all-form-fields' );

		$this->assertSame(
			Cache::get_hash( $form, $entry, [] ),
			Cache::get_hash( $form, $entry, [] )
		);
	}
}
```

Conventions:

- `declare(strict_types=1);` — required.
- Namespace matches the class under test (`GFPDF\Statics`, `GFPDF\Model`, etc.).
- `@group` annotation — every test class should have at least one (e.g. `controller`,
  `model`, `helper`, `ajax`) so contributors can run a slice.

## Fixture access

Each test class declares the fixtures it needs in `set_up_before_class()` via
`static::load_fixtures( $form_keys, $entry_keys )`. The loader creates them via
the factory, stores them in a per-class cache, and the base class's
`tear_down_after_class()` deletes them when the class finishes.

Available fixture keys (each loads `tools/phpunit/data/forms/<key>.json`):

| Key | Entries available |
| :--- | :--- |
| `all-form-fields` | 7 |
| `form-settings` | — |
| `gravityform-1` | 3 |
| `non-group-products-form` | 1 |
| `repeater-empty-form` | 1 |
| `repeater-consent-form` | 1 |

**Legacy fallback (temporary):** the accessors also fall back to
`$GLOBALS['GFPDF_Test']` populated by `tools/phpunit/bootstrap.php::create_stubs()`,
which loads all seven forms + five entry batches once per suite. This is the
coexistence shim for the fixtures-to-factory migration; it is removed in
Phase D once every file has been converted to `load_fixtures()`. New tests
should not rely on it.

**Never call `GFAPI::add_form()` / `GFAPI::add_entry()` directly from a test body.**
Use the factory for ad-hoc per-test forms/entries:

```php
$form_id  = $this->gf_factory()->form->create( [], $form );
$entry['form_id'] = $form_id;
$entry_id = $this->gf_factory()->entry->create( $entry );
```

**Class-scoped vs instance-scoped:** prefer class-scoped via `load_fixtures()`. Use
instance-scoped (per-test) only when the test mutates the fixture. See
`Model/Test_Slow_PDF_Processes.php` for an example of class-scoped expensive setup
(font copies).

## The "test if non-trivial" rule

Skip a `Test_*.php` for a class that is:
1. Under 30 lines of code, **and**
2. Has no methods of its own (only inherited), **and**
3. Has no constructor logic.

The 11 classes under `src/Exceptions/` are covered by a single
`integration/Exceptions/Test_Exception_Hierarchy.php` smoke test, not 11
individual files. Phase 4 of the refactor lands that test.

## Running

```bash
yarn wp-env:integration start         # one-time per session
yarn test:php                         # full suite
yarn test:php -- --filter Test_Cache  # single class
yarn test:php -- --group statics      # group
yarn test:php:multisite               # WP multisite mode
```

See top-level `CLAUDE.md` and `COVERAGE_BASELINE.md` for the runtime budget
and the methodology for regenerating baseline timings.
