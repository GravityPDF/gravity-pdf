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
│   ├── CleansFilesystem.php
│   └── UsesFactory.php
├── integration/                      ← new location, mirrors src/ 1:1
│   ├── TestCase.php
│   ├── AjaxTestCase.php
│   └── Statics/
│       ├── Test_Cache.php
│       └── Test_kses.php
└── unit-tests/                       ← legacy location, drained subdir-by-subdir
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

## Choosing a base class

| Need | Base class |
| :--- | :--- |
| Standard integration test (DB, hooks, options) | `\GFPDF\Tests\Integration\TestCase` |
| Test that dispatches a `wp_ajax_*` action via `_handleAjax()` | `\GFPDF\Tests\Integration\AjaxTestCase` |

Both extend WordPress's stock test cases and `use \GFPDF\Tests\Concerns\HasGfpdfFixtures`,
which provides:

- `$this->form( 'all-form-fields' )` — shared form fixture loaded once per suite.
- `$this->entry( 'all-form-fields', 0 )` — shared entry fixture.
- `$this->gfpdf()` — the `GFPDF\Router` DI container (same as the `$gfpdf` global).
- `$this->assertFixturesIntact()` — automatically called from `set_up()` to catch
  cross-test fixture mutation. Override `set_up()` only if you call
  `parent::set_up()`.

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
  `model`, `helper`, `ajax`, `slow-pdf-processes`) so contributors can run a slice.

## Fixture access

`tools/phpunit/bootstrap.php::create_stubs()` loads seven JSON forms and five
batches of entries into `$GLOBALS['GFPDF_Test']` once per suite. Use the trait
accessors rather than the global directly — same source, but failures point
to the missing key instead of throwing an undefined-index notice.

**Never call `GFAPI::add_form()` / `GFAPI::add_entry()` in a test body.**
Phase 3 of the refactor enforces this; use the existing factory
(`GF_UnitTest_Factory` at `tools/phpunit/gravityforms-factory.php`) via the
`UsesFactory` trait when you need a per-test form/entry.

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
