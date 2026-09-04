# Phase 0 spikes 0, 1, 3, 4 — fork, bundled fonts, release size, script detection

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

### `getId()` landed (§9.23 resolved)

`FontRegistry::add()` now keys by an overridable `FontRegistrationInterface::getId()`, defaulting to
`get_class($this)` in the base — commit `1afecbc`, on the PR and merged into the fork. **So §9.23's optional
collapse is available:** `Package_Bundled` and `Package_Installed` can become one `Package` class constructed
with `($id, $directory, $fonts, $backup_subs, $substitution, $aliases, $bmp, $language_to_font, $dictionaries)`.
Behaviour is identical either way; only the Phase 1 file list shrinks.

Fork now at `d433c5e` (merge of `decouple-fonts`), pinned in the plugin's `composer.lock`. PR is `MERGEABLE`
with 8 commits. A revised PR description is drafted in
`.claude/plans/2026-09-04-mpdf-2161-upstream-feedback.md` for manual posting — nothing has been posted.

---

# Spike 1 — the two layers against the forked mPDF

Harness: `tmp/font-bundle-spike/spike.php`, run through wp-env so the **prefixed** vendor is what is exercised.
Two `FontRegistration` subclasses stand in for `Package_Bundled` / `Package_Installed`; `fontDir => []`,
`fontdata => []`, `autoScriptToLang`, `autoLangToFont`, `useSubstitutions` per Appendix A.

`fontDir` assembles from the layers alone, bundled first, exactly as §4.1 predicts:

```
[0] .../gravity-pdf/fonts
[1] .../uploads-fonts
available_unifonts[0] = gfpdf-arimo
backupSubsFont        = gfpdf-arimo, gfpdf-dejavu-symbols
sans_fonts[0]         = gfpdf-arimo
```

Every render below produced **zero notices** and `onlyCoreFonts === false`:

| Case | Fonts loaded |
|---|---|
| Latin / Greek / Cyrillic / Vietnamese / Hebrew, with `<b>`, `<i>`, `<b><i>` | all four Arimo faces |
| Consent ✔/✖ (normal, and `PDFA => true`) | `gfpdf-arimo`, `gfpdf-dejavu-symbols` |
| Unknown `font-family`, `font-family: Arial`, `font-family: serif` | `gfpdf-arimo` |
| Arabic + kanji with no pack | `gfpdf-arimo` (substitution boxes, as expected) |
| `gfpdf-dejavu-symbols` (R only) with `<b>`/`<i>` | no error |

`pdffonts` on the output confirms §4.2's requirement — every face embeds as **CID TrueType**, subsetted, with a
unicode map, and **no ZapfDingbats appears in either tick PDF**:

```
MPDFAA+Arimo-Regular     CID TrueType  Identity-H  yes yes yes
MPDFAA+DejaVuSans        CID TrueType  Identity-H  yes yes yes
```

Precedence, all three confirmed:

- **Bundled beats installed** for a key both layers claim. `add()` prepends and bundled is added last, so it is
  read first. This matches §2.4 ("among packages the last added wins") and §4.2 ("no downloaded file replaces a
  bundled one") — but note §4.1's table describes the installed layer as carrying "every row", which reads as if
  installed would win. It does not, and that is the intended behaviour.
- Config `fontdata` beats both layers.
- The default font still heads `available_unifonts` whatever `mpdf_font_data` returned first, and the add-on's
  own key stays registered.

# Spike 3 — release build size (both budgets met)

`fonts/` is committed at the plugin root: four dehinted Arimo statics, `DejaVuSansSymbols.ttf`, both licence
texts, `README.md`, `index.html`. `tools/release/build.sh` picks it up automatically — the zip is built from
`git archive HEAD`, and no `.gitattributes` rule excludes it.

| Measure | Value | Budget | |
|---|---|---|---|
| `fonts/` compressed in the zip | 863,403 B | ≤ 900,000 B | **PASS** |
| Total zip | 5,156,617 B (5.16 MB) | ≤ 5.6 MB | **PASS** |
| Net change vs 6.16.0 (4,707,366 B) | **+449,251 B (+0.45 MB)** | plan projected +0.87 MB | under |

The zip contains no `linebrdict*`, no `ttfonts/` and no `packages/`. The plan's +0.87 MB estimate was
pessimistic because the line-break dictionaries left with the fork.

**Dehinting.** `fontTools.subset --no-hinting` also drops 60 unreachable glyphs per face, which would make the
faces a subset — §4.2 says they are not subsetted. Use a direct dehint instead (drop `cvt `/`fpgm`/`prep`/`gasp`,
clear each glyph's instructions, zero `maxp.maxSizeOfInstructions`); the script is recorded in `fonts/README.md`.
Verified per face: glyph count and cmap unchanged, and every outline byte-identical to upstream via a
`RecordingPen` comparison.

# Spike 4 — `Script_Detector` cost (target < 2 ms)

Harness: `tmp/script-detector-spike/`. `gate.php` is the generated negative character class — 95 ranges,
1,432 bytes, built from the union of the Arimo faces' common cmap (3,010 codepoints, identical across all four)
and the symbol supplement (1,095), for 4,001 covered codepoints. Only text tripping the gate is walked through
the prefixed `Ucdn::get_script()` → `ScriptToLanguage::getLanguageByScript()`.

| Case | Time | Detected |
|---|---|---|
| Pure Latin, 200 fields (14,678 chars) | **0.029 ms** | — |
| Latin + Greek + Cyrillic | 0.044 ms | — |
| Latin + ✔ ✖ | 0.034 ms | — |
| One Arabic field | 0.036 ms | `und-Arab` |
| One kanji field | 0.046 ms | `und-Hans`, `ja` |
| One Thai field | 0.051 ms | `th` |
| Arabic + kanji + Thai + Hindi | 0.043 ms | `und-Arab`, `und-Hans`, `th`, `hi` |
| Every field non-Latin (worst case) | 0.531 ms | `und-Hans`, `ja` |

Roughly 70× under budget on the ordinary case, 4× on the worst. The `\p{…}` alternation fallback §4.3 keeps in
reserve is not needed. The kanji row also demonstrates §2.4's Han → `und-Hans` mapping first-hand, which is what
the default-document-language setting exists to override.

The gate must be generated, not hand-written: Arimo has **zero** codepoints in U+FB50–FDFF (Arabic presentation
forms) and none in halfwidth Katakana, both of which a hand-approximation would likely treat as covered.

## Still open

- Spike 0's "confirm `fontFileFinder` is still the seam on the fork" is satisfied by the container change, but no
  plugin-side `FontFileFinder` subclass exists yet (Phase 1c).
- The generated gate's committed location and format are not specified by the plan. The spike emits
  `gate.php` returning a regex string; `fonts/` beside the faces it is derived from is the obvious home.
- Remaining Phase 0 spikes: 5 (Noto replacements), 6 (pack split), 7 (file hosting), 8 (Google pipeline dry run).
