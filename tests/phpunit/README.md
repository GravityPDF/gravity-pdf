# Gravity PDF — PHPUnit Test Suite

This directory holds the plugin's PHPUnit integration tests, run inside the
`wp-env` Docker container via `yarn test:php` / `yarn test:php:multisite`.

## Layout

```
tests/phpunit/
├── README.md                         ← you are here
├── Concerns/                         ← shared traits (NOT discovered by PHPUnit)
│   ├── HasGfpdfFixtures.php
│   └── UsesFactory.php
└── integration/                      ← mirrors src/ 1:1
    ├── TestCase.php
    ├── AjaxTestCase.php
    ├── Controller/
    ├── Exceptions/
    ├── Helper/                        ← nests Fields/, Fonts/, Licensing/, Log/, Mpdf/
    ├── Model/
    ├── Rest/
    ├── Statics/
    └── View/
```

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
- `$this->form( 'all-form-fields' )` — form fixture (per-class cache).
- `$this->entry( 'all-form-fields', 0 )` — entry fixture.
- `$this->gfpdf()` — the `GFPDF\Router` DI container (same as the `$gfpdf` global).

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

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures(
			[ 'all-form-fields' ],   // forms to create
			[ 'all-form-fields' ]    // entry sets to create against those forms
		);
	}

	public function test_get_hash() {
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

## Fixtures

A *fixture* is a JSON snapshot of a Gravity Forms form (plus optional sample
entries) that the suite imports into the database for the lifetime of a test
class.

### What a fixture file contains

**Form JSON** (`tools/phpunit/data/forms/<key>.json`) — a single Gravity Forms
form object: top-level `id`, `title`, `fields[]`, `notifications`,
`confirmations`, plus the Gravity PDF-specific `gfpdf_form_settings` map
keyed by PDF ID (this is where `template`, `filename`, `conditional` etc.
live for each PDF the form publishes). Imported via the factory's
`import_fixture_and_get( "$key.json" )`. The DB gets a fresh row with a new
auto-increment `id` — your test reads the live form back from
`$this->form( $key )`.

**Entry JSON** (`tools/phpunit/data/entries/<filename>.json`) — a JSON array of
entry rows. Each row is a flat associative array keyed by field-input ID
(`'1.3' => 'Jane'`, `'4' => 'jane@example.org'`) plus the meta columns
Gravity Forms expects (`form_id`, `date_created`, `currency`, `ip`,
`payment_status`, etc.). `form_id` is overwritten at import time with the
just-created form's ID, so the file's stored `form_id` is irrelevant.

### Where the data lives

```
tools/phpunit/data/
├── forms/         ← <form-key>.json — one form per file
├── entries/       ← <entry-key>.json — JSON array of entries for one form
├── fonts/         ← TTF/OTF used by render tests
├── images/        ← image attachments referenced from fixture entries
└── pdf/           ← reference PDFs for byte-compare tests
```

Form-key → entry-filename mapping lives in `HasGfpdfFixtures::$entry_filenames`
(the historical bootstrap mixed `-entries.json` and `-entry.json` suffixes, so
the map is the source of truth — your form key and entry filename do not need
to match).

### Available keys

| Key | Entries | What it represents |
| :--- | :---: | :--- |
| `all-form-fields` | 7 | Every field type GF and its add-ons ship (56 fields: address, checkbox, fileupload, list, poll, quiz, signature, survey, product, …) with 4 PDFs configured. The default fixture for render / field-output coverage. |
| `form-settings` | — | Small form (7 fields) with 3 PDFs configured but no entries; for PDF form-settings CRUD and UI tests where entry data is irrelevant. |
| `gravityform-1` | 3 | Plain contact-style form (name / email / phone / address / textarea, no PDFs configured) with 3 sample submissions; for tests that need form+entry data without PDF config. |
| `non-group-products-form` | 1 | Products form whose Product fields sit outside an Option group (regression fixture for issue #1418). |
| `repeater-empty-form` | 1 | Form with a Repeater field whose sample entry has zero child rows; exercises the empty-repeater render path. |
| `repeater-consent-form` | 1 | Form with a Consent field nested inside a Repeater; exercises consent-in-repeater output. |

### Reading fixtures from a test

After `static::load_fixtures( [ 'foo' ], [ 'foo' ] )` runs in
`set_up_before_class()` (see [Writing a new test](#writing-a-new-test) for the
full skeleton), any test method can access them via:

```php
$this->form( 'foo' );          // form array
$this->entry( 'foo' );         // first entry (index 0)
$this->entry( 'foo', 2 );      // entry by index
$this->entries( 'foo' );       // all entries
```

`load_fixtures()` caches per-class and is cleaned up by the base
`tear_down_after_class()`. An entry key must have its parent form key in the
same call — loading `entries: [ 'foo' ]` without `forms: [ 'foo' ]` throws.

### Adding a new fixture

1. **Export the form from Gravity Forms** (Forms → Import/Export → Export Forms,
   single form). Drop the JSON into `tools/phpunit/data/forms/<key>.json` —
   strip the outer array wrapper if present so the file is a single form
   object, not `[ { ... } ]`.
2. **(Optional) add entries**: write or export a JSON array of entry rows into
   `tools/phpunit/data/entries/<key>-entries.json`. If the filename doesn't
   match `<form-key>-entries.json` or `<form-key>-entry.json`, add a row to
   `HasGfpdfFixtures::$entry_filenames` mapping form key → filename.
3. **Reference it** from `set_up_before_class()`:
   `static::load_fixtures( [ 'mykey' ], [ 'mykey' ] )`.
4. Update the "Available keys" table above.

Sanity-check the wiring with `yarn test:php -- --filter Test_Fixtures_Loader` —
that's the infrastructure self-test for `load_fixtures`.

### Per-test forms/entries

For one-off forms or entries that only one test needs, use the factory directly
— **never call `GFAPI::add_form()` / `GFAPI::add_entry()` from a test body**:

```php
$form_id          = $this->gf_factory()->form->create( [], $form );
$entry['form_id'] = $form_id;
$entry_id         = $this->gf_factory()->entry->create( $entry );
```

Prefer class-scoped via `load_fixtures()` when more than one test in the class
needs the same data; per-test only when the test mutates the fixture.

### Font fixtures for real mPDF renders

Render tests that exercise mPDF need the test TTFs copied into the active fonts
directory. Use `HasGfpdfFixtures::copy_test_fonts()` /  `remove_test_fonts()`
from `set_up_before_class()` / `tear_down_after_class()`. See `Model/Test_PDF.php`
and `Controller/Test_Controller_PDF.php` for usage.

## The "test if non-trivial" rule

Skip a `Test_*.php` for a class that is:
1. Under 30 lines of code, **and**
2. Has no methods of its own (only inherited), **and**
3. Has no constructor logic.

The 11 classes under `src/Exceptions/` are covered by a single
`integration/Exceptions/Test_Exception_Hierarchy.php` smoke test, not 11
individual files.

## Running

```bash
yarn wp-env:integration start         # one-time per session
yarn test:php                         # full suite
yarn test:php -- --filter Test_Cache  # single class
yarn test:php -- --group statics      # group
yarn test:php:multisite               # WP multisite mode
```
