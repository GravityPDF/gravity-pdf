---
name: font-spike-8-google-pipeline
description: Phase 0 spike 8 — google/fonts family counts, default vs variants sizing, and a GDEF 1.3 parser bug that silently drops MarkGlyphSets on 80% of variable fonts
metadata:
  type: project
---

Phase 0 spike 8 of `.claude/plans/2026-08-26-remove-core-font-installer.md`, run 2026-09-07. Follows
[[font-spike-5-noto-replacements]] and [[font-spike-7-hosting]]. **Counts, sizing and the render gate are done; the
publish-throughput and streamed-download halves are blocked on spike 7's staging store.**

## Method note: no clone

A `--depth 1` clone of `google/fonts` died twice on network (`curl 56 Recv failure` at ~224 MB), and
`--filter=blob:limit=…` does not help because checkout materialises every blob anyway. The **GitHub Trees API**
gives paths *and* blob sizes with no clone: `/repos/google/fonts/git/trees/<sha>?recursive=1` per licence dir
(ofl 17,183 entries, apache 274, cc-by-sa 1,872 — none truncated). Licence text and METADATA came from
`raw.githubusercontent.com` in one parallel `curl --config` run. Two traps: curl's config format needs
`url = "…"` / `output = "…"` keywords, and VF filenames contain `[wght]`, so **`--globoff` is required** or curl
glob-expands them.

**The repo layout is not what the plan assumed.** There is no `static/` subdirectory convention — exactly *one*
family has one. Families carry either bracket-named variable fonts (`Lora[wght].ttf`) or plain statics directly in
the family directory (`Lato-Regular.ttf`). "Upstream `static/`" in §4.3 step 2 should read "plain static faces in
the family directory".

## The three family counts (the exit criterion)

2,050 families across `ofl` / `apache` / `cc-by-sa`:

| | | |
|---|---|---|
| upstream statics — precedence 1 | **1,464** | 71.4% |
| VF-only, no Reserved Font Name — instanceable | **529** | 25.8% |
| VF-only, with an RFN — skip-list candidates | **51** | 2.5% |
| both statics and a VF | 3 | 0.1% |
| no font file at all | 3 | 0.1% |

**The true skip list is one family.** Fetching the Google Fonts download manifest for all 51 RFN families,
**50 serve ready-made statics** — Google's own builds, so no RFN question arises. The single exception is
`ofl/signikasc`, whose manifest answers `{"error": "Unable to find family: Signika SC"}`: it is in the repo but not
published on fonts.google.com, so there is nothing to catalogue anyway. Spike 5's guess that the skip list is
"close to empty" is confirmed at full scale — **0.05% of families**.

## Sizing: the plan's ~7,000 files is the *variants* set, not the default set

Measured from tree blob sizes for the static half; the VF half from `fvar` (30-family sample, mean **6.6** named
instances per upright VF, and 182 of 580 VF families also ship an Italic VF).

| | files | bytes |
|---|---|---|
| four-variant default set — static half (measured) | 1,899 | 1.25 GB |
| four-variant default set — VF half (400/700 × upright/italic) | 1,524 | ~0.19 GB at the median face |
| **default set, whole catalogue** | **3,423** | **~1.44 GB** |
| every weight × italic — static half (measured) | 2,780 | 1.69 GB |
| every weight × italic — VF half (6.6 instances × italic where present) | ~5,029 | ~0.63 GB |
| **variants set, whole catalogue** | **~7,809** | **~2.3 GB** |

So `variants` is **2.28× the default set by file count** — the plan's "roughly doubles to triples" is right, but its
"~7,000 files" describes the *variants* set. The default set is about half that. The ~2.3 GB total also confirms
spike 7's store estimate independently.

**Face sizes are wildly skewed:** mean 657,073 B but **median 124,844 B** — the top 20 faces alone are 26% of the
static half (CJK). Any per-family average is misleading; use the median.

## The instancing render gate: 3 of 10 failed, and two were our bugs

Instanced 10 VF-only families at `wght=400` with `updateFontNames=True`, then rendered each through the fork at
`useOTL => 0xFF` under `memory_limit=256M`. Peak RSS never exceeded 24 MB and no render took over 0.11 s, so the
memory cap is a non-issue. Seven passed immediately. The three failures:

1. **Manrope — no GDEF table at all.** `does not include OTL tables (or at least not a GDEF table)`. Expected, not
   a bug: same class as Lato and the four Regular-only cursive families in spike 5. Catalogue it `useOTL => 0`.
2. **Baloo 2 — `GSUB Lookup Type 5, Format 3 not supported`.** Spike 5's `chainify.py` converts its single such
   lookup and it then renders. **This is the finding that matters for the pipeline:** the 5/3 → 6/3 rewrite is not a
   special case for the four legacy swaps, it is needed by ordinary catalogue families and must run on everything.
3. **Noto Sans Mono — a real parser bug in the fork** (below).

After both fixes: **9 of 10 catalogue at `0xFF`**, the tenth at `0`.

## GDEF 1.3 silently loses MarkGlyphSets — the fork's fix is incomplete

`TTFontFile::_getGDEFtables()` reads the MarkGlyphSetsDef offset **only when `$ver_min == 2`**
(`TTFontFile.php:1245` and again at `:1338`). GDEF 1.3 (`0x00010003`) also carries MarkGlyphSetsDef — it merely adds
an ItemVarStore after it — so for every 1.3 font the sets are never parsed. `$this->MarkGlyphSets` stays empty, and
then:

- `Otl::_getGCOMignoreString()` has an `isset()` guard and **throws** `Font "…" uses mark filtering set 0, which
  GDEF does not define`;
- `TTFontFile.php:2964` has **no such guard** and emits an `Undefined array key 0` warning storm plus a
  `strpos(): Passing null` deprecation.

**All 30 sampled variable fonts are GDEF 1.3, and 24 of 30 (80%) carry mark glyph sets *and* a lookup using
`UseMarkFilteringSet`** — so they silently lose their sets. It only surfaces as a throw when the text exercises the
path (Latin samples like Cairo and Figtree passed while still being affected), which makes it a *silent shaping*
bug most of the time.

This defeats part of the fork's own MarkGlyphSets work: `bbed232` fixed the coverage *offset*, but the *version
gate* still excludes 1.3. **The fix is one condition in two places, `== 2` → `>= 2`**, plus collapsing both
dereference sites onto one `markGlyphSet()` accessor so they fail the same way. **Shipped as
GravityPDF/mpdf#26** (`fix/gdef-13-markglyphsets`, 2026-09-07): 1,104 tests / 2,623 assertions OK, `composer cs`
clean, fixture is an 18 KB subset of an instanced Noto Sans Mono keeping GDEF 1.3, its six sets and the eight
lookups referencing them.

## The same gate is in OtlDump, with two more bugs behind it

`src/OtlDump.php` keeps an independent copy of the GDEF parser and repeats the gate verbatim (`:951`, `:1098`), so
the dumper — the tool you would use to diagnose the bug above — reports zero mark glyph sets for every GDEF 1.3
font. Opening the gate exposed two faults that were unreachable while it was shut: the mark-set coverage tables
were seeked at **absolute** file offsets where they are relative to the MarkGlyphSetsDef table (so even a 1.2 font
parsed as empty sets — `TTFontFile` already read them relative), and `_getGSUBignoreString()` threw
`This font ... contains MarkGlyphSets` **unconditionally** with two unreachable lines after it, the original
upstream throw that `bbed232` removed from `TTFontFile` and `Otl` but not from the dumper. Its per-glyph check was
also inverted. **Shipped as GravityPDF/mpdf#34** (`fix/otldump-gdef-13-markglyphsets`, 27/27 checks): 0 → 6 mark
glyph sets on the #26 fixture.

Two things to know before touching `OtlDump` again. Its class strings are **display labels**
(`formatClassArr()` → `"U+0326, U+0309, …"`), not the `" 0FBA1| 0FBA2"` glyph-ID lists `Otl`/`TTFontFile` use — so
`_checkGSUBignore()` `strpos`es a glyph ID into a label list for *every* flag and is inert by construction; the
fix aligns it with its siblings but does not make it functional. And its full-dump mode (`$mode = null`) is
separately broken on an undeclared `Mpdf::$OTLscript` property, so only `'summary'` mode can be exercised.

**The caching trap from spike 5 bit again, in a new place.** The test appeared to pass with the fix reverted,
because mPDF's default `tempDir` for the test suite is the repo-root `tmp/`, not `tests/Mpdf/tmp/` — clearing the
latter does nothing. Always `find tmp -name '<fontkey>*' -delete` before asserting a font-parsing test fails
without its fix, or the previous run's parse is served back.

## Still open

Publish throughput for ~7,800 files and the streamed 17.63 MB `files/{path}` download both need spike 7's staging
store, which does not exist yet.
