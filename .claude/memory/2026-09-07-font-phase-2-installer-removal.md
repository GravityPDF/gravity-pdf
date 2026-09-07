# Phase 2 — the core font installer is gone (2026-09-07)

Plan: `.claude/plans/2026-08-26-remove-core-font-installer.md` §4.12 + Phase 2. PR #1722 (draft), branch
`feature/remove-core-font-installer`.

## What went

`Controller_Save_Core_Fonts` and its bootstrap wiring; the nine `coreFont*` localised strings; the whole React tree
(`components/CoreFonts/*`, `actions/coreFonts.js`, `reducers/coreFontReducer.js`, `sagas/coreFonts.js`,
`api/coreFonts.js`, `router/coreFontRouter.js`, `bootstrap/coreFontBootstrap.js`, `_core-fonts.scss`) and every
wiring point; `build/payload/core-fonts.json`, `tools/release/json-payload.sh` and the orphaned `prebuild:core-fonts`
npm script; 7 Jest specs, `Test_Controller_Save_Core_Fonts`, `Test_Model_Custom_Fonts_Ajax`; the two E2E stubs.

`Model_Custom_Fonts::matches_core_font_id()` retired — `has_unique_font_id()` asks `Font_Repository` directly. Its
`Helper_Abstract_Options` dependency was then dead, so the **constructor signature changed** to
`__construct( Font_Repository $repository )`. Three call sites (bootstrap, `api.php`, two test files).

## The thing worth remembering: step 1c had to land here

`build/payload/core-fonts.json` is the ONLY record of what the 6.x installer could write, and Phase 2 deletes it.
So `src/Helper/Fonts/Legacy_Installer_Files.php` was generated in the same commit. Two things the plan didn't
anticipate, both discovered by looking at the data:

1. **70 files are only 39 families.** `dejavusanscondensed` is four files under one mPDF key. A per-file map would
   have adopted them as four rows and broken every template naming that key. The constant is
   `font_key → { use_otl, use_kashida, faces: { R|B|I|BI → { name, blob, size } } }`.
2. **The manifest's hash is a git blob SHA-1, not SHA-256.** `sha1( "blob <size>\0" . contents )` — reproduce it
   with `git hash-object <file>`. A sha256 would have meant re-downloading 80 MB today, attesting to that download
   rather than to what shipped.

**Where the 6.x font keys came from:** upstream `Mpdf\Config\FontVariables` at `70b90cc~1` in the `GravityPDF/mpdf`
fork — the revision before the font-package split. The fork's own `FontVariables` has `'fontdata' => []` (PR #2161
decoupled it), and 10 basenames belong to families the fork's packages replaced, so the packages don't have them
either. If this map ever needs regenerating, that revision is the source. Its font keys are **double-quoted**
(`"dejavusanscondensed" => [`) while the face roles are single-quoted — a single-quote-only regex silently returns
zero matches.

**Free test corroboration:** `tools/phpunit/data/fonts/{DejaVuSans,DejaVuSans-Bold,DejaVuSansCondensed,DejaVuSerifCondensed}.ttf`
are byte-identical to their manifest entries (`git hash-object` matches). `Test_Legacy_Font_Adopter` uses them, so
the real hashes are exercised and the generated map is independently corroborated.

## Ordering in `ensure_ready()`

migration → **adopter** → loose importer. The importer excludes every manifest basename outright, so a file failing
its hash check is left alone rather than imported under a filename key.

`entry` (the Phase 3 pack linkage) is deliberately absent from the map: every adopted family lands
`source = imported` under its frozen 6.x key, which is the correct end state for an upgraded site anyway — spike 5's
Noto swaps are only supposed to reach fresh pack installs.

## Two shapes worth reusing

`Font_Population_Pass` — `Font_Repository` holds an ordered array of passes rather than a nullable field + setter +
null check per collaborator. Phase 3 adds catalogue adoption and pack installs to the same list. Ordering lives in
`bootstrap.php`, where the order is chosen.

`Registry::mpdf_font_config( LanguageToFontRegistry )` — the seven mPDF keys that decide which font a run of text
gets. **Two places construct mPDF**: `Helper_PDF::begin_pdf()` and the v3 `mPDF` shim in `deprecated.php`. The shim
had already drifted (no `languageToFont`/`autoScriptToLang`/`useKerning`) before anyone noticed, because the two
config arrays were unrelated literals. Any new font-resolution key goes in that method, not in `begin_pdf()`.

## Test-harness traps hit here

- `Test_Font_Schema`'s DDL **commits the transaction**, so a case that changes `gfpdf_db_version` must be declared
  *after* the case asserting the bootstrap's state. Order in that file is load-bearing.
- `update_option()` compares against the **cached** value, so an option deleted earlier in a test cannot be
  restored by writing it back once a DDL commit has made the deletion permanent — set a stale value instead of
  deleting.
- `ensure_ready()` no-ops in every suite (the PHPUnit bootstrap pre-creates the tables and writes the version), so
  anything inside it is uncovered unless a test deliberately moves the version backwards.

## Still open

- Fork PRs #34 (OtlDump) and #36 (`fonttrans` alias collapse) awaiting merge; the plugin re-pins after #36 and
  `Test_Bundled_Render` can then assert `helvetica` on `fonttrans` directly.
- CI's PHP 7.4 cell fails repo-wide on Debian bullseye apt rot (404 on `libc-dev-bin_2.31-13+deb11u14`), not on
  this change.
