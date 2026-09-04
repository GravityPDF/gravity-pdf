# Spike 0 — mPDF fork branch + #2161 extension

Plan: `.claude/plans/2026-08-26-remove-core-font-installer.md` §5 Phase 0 spike 0, §9.18.
Date: 2026-09-04. Work done locally in a scratch clone; **nothing pushed yet**.

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

## Still open

- Nothing is pushed. `GravityPDF/mpdf` still has only `development`; the PR still shows `CONFLICTING`.
- Plugin-side `composer.json` fork switch + `composer prefix` + `Test_Vendor_Prefixing` are blocked on the
  push — a `vcs` repository entry needs the branch to exist on the remote.
- The `getId()` question for `FontRegistry::add()` (§9.23) has not been put to upstream.
