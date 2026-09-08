---
name: font-phase-1c-registry
description: Phase 1c of the 7.0 font rewrite — the mPDF font registry, the loose-font importer, deleting the core-font nag, and the fork bug it surfaced
metadata:
  type: project
---

Phase 1c of `.claude/plans/2026-08-26-remove-core-font-installer.md`, built 2026-09-07 on
`feature/remove-core-font-installer`, on top of [[font-phase-1b-tables]]. mPDF's font wiring now comes from the
font registry rather than a hard-coded core-font list and a per-render directory glob.

New: `src/Fonts/{Registry,Package,Language_To_Font,Loose_Font_Importer}.php`. `Helper_PDF::begin_pdf()`
builds the whole font config from `Registry`; `Helper_Abstract_Options::get_installed_fonts()` returns
`Registry::get_grouped_fonts()`.

**Things that bit, and would bite again:**

- **`tools/mu-plugins/mpdf.php` forced `mode => 'c'` on the whole test suite.** Until it was deleted, no test had
  ever proved a real font was embedded — every render was core-fonts-only. Anything asserting on rendered fonts
  before that deletion was asserting on nothing. `Test_Bundled_Render` is the replacement guard.
- **mPDF's `initFontRegistry()` ran `array_unique()` over `fonttrans`.** That property is a map keyed by *alias*,
  and `array_unique()` compares values, so `['arial' => 'x', 'helvetica' => 'x']` kept `arial` alone and
  `font-family: helvetica` silently stopped resolving. Fixed as **`GravityPDF/mpdf#36`** (27/27 green). The plugin
  asserts both aliases on the package until the fork merge is re-pinned.
- **`FontFileFinder` joins paths as `$directory . '/' . $name`**, so a package's `getDirectory()` must NOT carry a
  trailing slash.
- **`fontDir` must be an array now that the layers append to it.** The public `gfpdf_mpdf_class_config` filter has
  long been handed a bare string — our own `Test_Rest_Download_Pdf` did it — which was harmless while nothing
  appended, and now fatals in `AddFontDirectory()`. `begin_pdf()` normalises it rather than fataling on an add-on's
  behalf.
- **`Mpdf::$fontDir` is `private`**, so a test must reflect against `GFPDF_Vendor\Mpdf\Mpdf::class`, not the
  plugin's subclass.
- **The language map's precedence is installed-beats-bundled**, and among installed rows the *earlier* row wins
  (catalogue `position` order). Writing it the other way round is easy and silently wrong.
- **Deleting the 6.x core-font list broke five PHPUnit cases and two Playwright specs** that were asserting on
  `dejavusans`/`mph2bdamase` — i.e. on the frozen list, not on behaviour. Expected fallout, worth budgeting for.
- **One Playwright failure is pre-existing**: `Page confirmation` times out inside
  `@wordpress/e2e-test-utils-playwright`'s `setPreferences`. Verified by stashing all of 1c and seeing it fail
  identically. It touches no font code.
- **CI's PHP 7.4 cell is broken by Debian rot, not by us**: wp-env's bullseye image fails
  `apt-get install $PHPIZE_DEPS` with a 404 on `libc-dev-bin_2.31-13+deb11u14`. Affects every PR on the repo.

`§9.23`'s collapse was taken — one `Package` class per layer rather than `Package_Bundled`/`Package_Installed`,
since `getId()` landed on the fork. `Loose_Font_Importer` validates through the shipped `FileInfo`, so it does not
need the still-outstanding `LocalFileInfo` prerequisite PR.

Next: Phase 2 (delete the installer, §4.12 sweep including the orphaned React core-font bundle).
