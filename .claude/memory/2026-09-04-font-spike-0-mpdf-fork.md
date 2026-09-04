# Spike 0 — mPDF fork branch + #2161 extension

Plan: `.claude/plans/2026-08-26-remove-core-font-installer.md` §5 Phase 0 spike 0, §9.18.
Date: 2026-09-04. Pushed: `GravityPDF/mpdf:gravitypdf-7.0` @ `a1becec`, and the rebase force-pushed to
`jakejackson1/mpdf:decouple-fonts` — mpdf/mpdf#2161 now reports `MERGEABLE` with 7 commits.

## Rebase

`jakejackson1/mpdf:decouple-fonts` (2 commits, base `4c807ef`) rebased onto `389e19e`. One conflict, in
`composer.json`: upstream added `ext-imagick` to `suggest` after the PR's base, the PR replaced the same block
with the three font-bundle suggests. Resolved by keeping both. No other conflict across 248 files.

## Baseline: #2161 leaves mPDF's own suite red

| Branch | Result |
|---|---|
| upstream `development` @ `389e19e` | OK (975 tests) |
| \+ #2161 rebased | 15 errors, 41 failures / 987 tests |
| \+ #2161 rebased \+ `mpdf/font-bundle-all` in `require-dev` | OK (987 tests) |

`packages/*` is declared as a `path` repository but nothing requires it, so a plain `composer install` registers
no fonts and mPDF drops into core-font mode. The fix is one `require-dev` line — filed as its own commit, since
it is #2161's bug rather than ours, and the fork's CI is unusable without it.

## Extension commits (all five belong in the PR)

1. **Line-break dictionaries as package data** — `FontRegistrationInterface::getLineBreakDictionaries()`,
   `FontRegistration` default `[]`, `Mpdf::$lineBreakDictionaries` merged in `initFontRegistry()` with the same
   first-wins precedence as `fontdata`, `Otl::seaLineBreaking()` early return, and `linebrdict{T,K,L}.dat` moved
   into `packages/{Garuda,Khmer-OS,Dhyana}/fonts/`. Merged alongside `getFonts()`, **not** behind the
   `autoloadConfig` gate — a dictionary is font data, not one of the four config lists that flag names.
2. **`FontRegistry` fallback** — logs and registers nothing when `composer.lock` is absent instead of throwing.
   Takes an optional third `$logger` constructor arg (untyped: mPDF still supports PHP 5.6, and a
   `LoggerInterface $logger = null` hint is a deprecation on PHP 8.4+).
3. **`fontFileFinder` container-resolvable** in `ServiceFactory::getServices()`.
4. **`mpdf/font-bundle-all` in `require-dev`** (above).
5. **Default font sorted first in `fontdata`** — see the open question below.

Fork-only, on `gravitypdf-7.0` alone and deliberately last so the PR branch is a clean prefix:
`packages export-ignore` in `.gitattributes`. Verified: `git archive` of the branch contains no `packages/`,
no `ttfonts/` and no `linebrdict*.dat`.

Final state: **OK (994 tests)**, `composer cs` clean.

## Verified behaviour

Thai render (`font-family: garuda`, `autoScriptToLang` + `autoLangToFont` + `useSubstitutions`, empty `fontDir`
and `fontdata`, layers only):

- Garuda package registered → `lineBreakDictionaries = ['T' => …/packages/Garuda/fonts/linebrdictT.dat]`,
  18,393-byte PDF, **0 notices**.
- Same font registered with no dictionary → `lineBreakDictionaries = []`, 18,349-byte PDF (no U+200B word
  breaks), **0 notices**. This is the plugin's state before the `southeast-asian` pack lands.

## Corrections to the plan

- **§5 spike 1 cannot assert `$mpdf->fontDir`.** It is `private` (`Mpdf.php:290`) and `Strict::__get()` throws
  on any undeclared read. Assert through reflection, or through the `fontFileFinder` the layers configure.
- **§4.1 / §9.18's "`initFontRegistry()` re-keys the map as `['gfpdf-arimo' => …] + $fontdata`"** is written as
  if mPDF knows a plugin font key. Implemented generically instead: the entry named by `$config['default_font']`
  is moved to the front of `fontdata` after the merge. Same outcome for us (`Helper_PDF` passes
  `default_font => 'gfpdf-arimo'`, §4.8) and it is something upstream can take. **Confirm this reading.**

## Plugin side (Phase 1a, committed)

`composer.json` gains the `vcs` repository entry and requires `dev-gravitypdf-7.0`; the fork also carries
`extra.branch-alias` (`dev-gravitypdf-7.0` → `8.x-dev`) so dependents resolving by version constraint still work.

- `vendor/mpdf/mpdf` drops from ~110 MB to **4.9 MB**. `packages/` is export-ignored and Composer does not read a
  dependency's own `path` repositories, so nothing pulls the font packages in.
- `vendor_prefixed/` contains no `ttfonts/` and no `linebrdict*.dat`.
- `GFPDF_Vendor\Mpdf\Fonts\{FontRegistry,FontRegistration,FontRegistrationInterface}` and
  `GFPDF_Vendor\Mpdf\Language\LanguageToFontRegistry` all resolve; `getLineBreakDictionaries()` survives scoping.
- `Test_Vendor_Prefixing` passes (11 tests); the **full PHP suite is green** (1637 tests, 46 skipped).

The suite stays green only because `HasGfpdfFixtures::copy_test_fonts()` puts fonts in the uploads fonts dir and
`add_unregistered_fonts_to_mPDF()` globs them — the fork empties `FontVariables`, so nothing else registers a
font. A release build between 1a and 1c would emit core-font-only PDFs, which is what Phase 1c fixes.

**Further plan correction:** §5 Phase 1a describes itself as "`composer.json`/`composer.lock` + regenerated
`vendor_prefixed/` … so the vendor churn stays out of the review". There is no vendor churn — `vendor_prefixed/`
holds one tracked file (`.gitkeep`) and is generated by `composer prefix` at install time. 1a is a two-file diff,
which weakens the case for splitting it from 1b/1c at all.

### wp-env trap

`composer update` recreates `vendor/gravity/gravityforms`, which breaks the container's bind mount for it —
PHPUnit then dies in bootstrap on `Failed opening required '.../gravityforms/gravityforms.php'`. Restart the
environment (`yarn wp-env:integration start`) after any Composer run that touches `vendor/gravity`.

## Still open

- The `getId()` question for `FontRegistry::add()` (§9.23) has not been put to upstream.
- Spike 0's remaining ask, "confirm `fontFileFinder` is still the seam on the fork", is satisfied by the
  container change, but no plugin-side `FontFileFinder` subclass exists yet (Phase 1c).
