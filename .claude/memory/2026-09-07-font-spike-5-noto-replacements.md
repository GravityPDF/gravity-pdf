---
name: font-spike-5-noto-replacements
description: Phase 0 spike 5 — Noto replacements for the licensing-flagged fonts, the mPDF OTL parser gate that blocks four of them, and the popular-pack licence pass
metadata:
  type: project
---

Phase 0 spike 5 of `.claude/plans/2026-08-26-remove-core-font-installer.md`, run 2026-09-07 on branch
`feature/remove-core-font-installer`. Harness in the session scratchpad (`spike5/render.php`, `cover2.py`,
`instance.py`, `popular.py`, `scan.py`).

**Method.** Each candidate renders in its own PHP 8.5 process against the *prefixed* fork
(`GFPDF_Vendor\Mpdf`) at `memory_limit=256M`, registered through a single `FontRegistration` layer with
`fontDir => []` / `fontdata => []`, carrying the `useOTL` flag its entry file would declare. Coverage is the
cmap intersection restricted to the target script's blocks — a whole-font cmap diff is meaningless here
because the legacy fonts are pan-Unicode and the Noto faces are single-script.
`'mode' => 'c'` must **not** be passed: it forces core fonts, every PDF comes back ~1,340 B, and every case
"passes" without embedding anything.

## The four flagged pairs that fail: mPDF's OTL parser, not the fonts

`TTFontFile` throws while *parsing* the layout tables, so the failure is independent of which OTL features
are asked for — `useOTL` 0x01, 0x02, 0x04 and 0xFF all throw identically, and only `useOTL => 0` renders
(unshaped, which is useless for these scripts).

| Font | Exception |
|---|---|
| Noto Sans Sinhala | `Font "..." contains MarkGlyphSets which is not supported` (GDEF `UseMarkFilteringSet`, `TTFontFile.php:2976`) |
| Noto Sans Tai Tham | `GPOS Lookup Type 5, Format 3 not supported (ttfontsuni.php).` |
| Noto Sans Myanmar | same |
| Noto Sans Sundanese | same |

The "GPOS" in that message is **wrong upstream**: line 1794 sits inside `_getGSUBtables()`, so it is GSUB
LookupType 5 Format 3 — coverage-based contextual substitution, which fontmake/feaLib emit routinely.
mPDF handles formats 1 and 2 only.

**This is a gate on the whole `google` source, not on these four fonts.** Any family whose entry file
declares a non-zero `useOTL` has to be render-checked before it can be catalogued — spike 8 must add that
check, and a family that fails has no fallback other than `useOTL => 0`.

## Both blockers can be worked around (investigated 2026-09-07)

Two independent blockers, two different fixes. None of these Noto faces declares a Reserved Font Name
(`Copyright 2022 The Noto Project Authors`, OFL 1.1), so modifying them is permitted.

### GSUB 5/3 — rewrite as 6/3 in the pipeline

mPDF supports chaining contextual substitution (LookupType 6) in **all three** formats, and a type-5
format-3 subtable is exactly a type-6 format-3 subtable with zero backtrack and zero lookahead. So the fix is
a structural rewrite, not a loss of features: for each GSUB lookup whose subtables are all Context Format 3,
swap each for a `ChainContextSubst` Format 3 carrying the same input coverages and the same
`SubstLookupRecord`s, and set the lookup (or its Extension wrapper) to type 6. ~30 lines of fontTools, kept
at `tmp/chainify.py`. Affected lookups: Tai Tham 1, Sundanese 2, Myanmar 4. No lookup in any of the three
mixes formats, so converting whole lookups is safe.

Lossless: `hb-shape` returns identical glyph and cluster output for original and converted across every test
string. All three then load and render at `useOTL => 0xFF` where they previously threw.

What it actually buys, measured as pixels differing between `useOTL` 0 and 0xFF on the same converted font
at 150 dpi:

| Font | Δpx | mPDF shaper | Reading |
|---|---|---|---|
| Noto Sans Myanmar | 1,949 | `M` (mym2), and the font declares `mym2` | Matches the `hb-view` reference — prefixed `ေ`, correct medials. **A real win.** |
| Noto Sans Tai Tham | 203 | `E` (SEA); font declares only `DFLT`, which mPDF falls back to | Close to the reference, not certified cluster by cluster |
| Noto Sans Sundanese | 23 | none — falls to the default shaper | Generic GSUB features only |
| *control:* FreeSans Devanagari | 1,669 | `I` (Indic) | Confirms the measurement is sound |

mPDF's shaper list is `Otl.php:232-267`: Indic, Arabic, Khmer, Thai, Lao, Sinhala, Myanmar, and an "SEA"
shaper covering New Tai Lue, Cham and Tai Tham. Sundanese is in none of them.

### MarkGlyphSets (Sinhala) — two lines in the fork, no font surgery

Noto Sans Sinhala has **no** 5/3 subtables; its blocker is 12 lookups carrying `UseMarkFilteringSet`. mPDF
throws in two places — `TTFontFile::_getGSUBignoreString()` (`:2976`) and `Otl::_getGCOMignoreString()`
(`:4496`) — and in *both* the throw sits immediately above a complete implementation the author left
unreachable, commented "Not tested yet" and "Change also in ttfontsuni.php". Replacing each throw with
`$ignoreflag = $flag;` makes Noto Sans Sinhala render, matching the `hb-view` reference including the
`ශ්‍රී` conjunct, and mPDF's own suite stays green. **Split out on 2026-09-07 as GravityPDF/mpdf#1**, against
our own `gravitypdf` branch — it is unrelated to font packages, so it came off the upstream PR (`9af3710`
removed from `decouple-fonts`, reverted on the fork as `46df585`) and the fork branch carries it only once
that PR merges. The change: the two throws removed, a guard kept for a filtering set GDEF does not define,
and two tests — one per throw — against a 7.5 KB subset of Noto Sans Sinhala at
`tests/data/ttf/NotoSansSinhala-Subset.ttf`, both of which fail without it. mPDF's suite is 1,000 tests green
and `composer cs` is clean.

### Harness trap worth remembering

mPDF caches parsed font metrics in `tempDir` keyed by the **font key** — not the file path, not the `useOTL`
value. Reusing one `tempDir` across runs silently serves the first run's cache: an entire comparison pass
reported 0 differing pixels for every script *including the Devanagari control* purely because of it. Give
every render in a comparison its own `tempDir`.

## Decisions (§9.3)

| Legacy | Replacement | In-script coverage | Render | Size | Decision |
|---|---|---|---|---|---|
| Aboriginal Sans (GPLv3, no exception) | Noto Sans Canadian Aboriginal | 710/710 of U+1400–167F + U+18B0–18FF | OK, `useOTL => 0` | 94,888 + 93,656 B | **Replace** |
| Kaputa (Sinhala) | Noto Sans Sinhala | 80/80, +31 | **FAIL** | — | Keep as today |
| Lanna Alif (Tai Tham) | Noto Sans Tai Tham | 127/127 | **FAIL** | — | Keep as today |
| Zawgyi One (no licence) | Noto Sans Myanmar | 132/132, +91 | **FAIL** | — | Keep as today |
| Sundanese Unicode (GPLv3, no exception) | Noto Sans Sundanese | 55/55, +17 | **FAIL** | — | Keep as today |
| UnBatang (GPLv2, no exception) | Noto Sans KR | 11,522/11,522 Hangul + Jamo | OK, peak 26 MB | 12.45 MB R+B vs 12.95 MB | **Replace** |
| Sun-ExtA (source/licence not found) | Noto Sans SC | 27,506/27,506 of URO + Ext A | OK, peak 26 MB | 10.60 MB vs 22.99 MB (−54%) | **Replace** |
| Sun-ExtB | — | Noto Sans SC covers 54 of 42,711 Ext B codepoints | — | — | **No replacement; keep** |

Notes on the two that replace cleanly:

- **Korean.** UnBatang is a *serif* (Batang), so the faithful match is Noto Serif KR — but that is 28.25 MB
  for R+B against UnBatang's 12.95 MB. Noto Sans KR is size-neutral and is the recommendation; the style
  change is a product call, not a technical one.
- **CJK.** Noto Sans JP and TC are language-subset builds covering only 47% and 58% of URO + Ext A, so
  neither replaces Sun-ExtA — **SC alone does**, at 100%. Ext B has no Noto equivalent in `google/fonts`.
- Both CJK families are VF-only in `google/fonts` and were instanced with `fontTools.varLib.instancer`
  (`updateFontNames=True`), peak RSS 163–279 MB per instance — a build-time cost, comfortably under a
  1 GB CI cap. Their OFL declares Reserved Font Name **'Source'** (Noto CJK derives from Source Han Sans),
  not 'Noto', so instancing needs no rename: the modified version is still "Noto Sans SC".
- Dehinting the instanced CJK statics saves **48 bytes** — the Noto CJK VFs carry no TrueType hinting, so
  the §4.2 dehint step is a no-op for them.

Open item: **Aboriginal Serif** sits in the same package under the same GPLv3-no-exception licence and the
plan does not name it. Noto has no serif Canadian Aboriginal, so it has no replacement either way.

## Popular packs (§4.3) — licence pass and sizes

**A first pass of this looked only at `github.com/google/fonts` and concluded that Lora, Merriweather,
Playfair Display and Dancing Script were blocked — VF-only in that repo, each declaring a Reserved Font Name,
so instancing would make a Modified Version that may not keep the name. That conclusion was wrong.** The repo
is not the only upstream distribution: a family's own download manifest,
`https://fonts.google.com/download/list?family=<Family>` (JSON after a `)]}'` prefix), lists `fileRefs`
pointing at `fonts.gstatic.com`, and for these families it serves ready-made `static/*.ttf` under the same
OFL. Those are **Google's own statics, not our instances**, so no RFN question arises and nothing is blocked.

Verified: the served statics carry the same version string and exactly the same cmap as the repo VF —
Lora 3.008 (778 cps), Playfair Display 1.203 (659), Merriweather 2.100 (1,423), Dancing Script 2.001 (559),
100% coverage in every case. All four render in the forked mPDF at `useOTL` 0 **and** 0xFF.

**Source precedence for §4.3 step 2**, in order:

1. upstream `static/` in `google/fonts` when the family has one;
2. otherwise the family's Google Fonts download manifest;
3. `fontTools.varLib.instancer` only when neither exists — and then an RFN still blocks it.

Order 1 before 2 matters. **Lato** is the counter-example: the repo ships v2.015 statics with 2,196
codepoints, while the manifest serves an old v1.104 "Western+Polish" build with **263** — 75 KB a face
against 656 KB, but nine tenths of the character set gone. Everywhere else the two agreed.

| Pack | Family | Source | Faces | Size |
|---|---|---|---|---|
| sans | lato | repo statics (v2.015) | 4 | 2.73 MB |
| sans | roboto | manifest | 4 | 651 KB |
| sans | opensans | manifest | 4 | 535 KB |
| sans | montserrat | manifest | 4 | 1.35 MB |
| | | | | **5.27 MB** / 5,265,448 B |
| serif | lora | manifest | 4 | 547 KB |
| serif | merriweather | manifest (`Merriweather_24pt-*`) | 4 | 4.07 MB |
| serif | playfairdisplay | manifest | 4 | 744 KB |
| | | | | **5.56 MB** / 5,563,248 B |
| mono | robotomono | manifest | 4 | 365 KB |
| mono | jetbrainsmono | manifest | 4 | 466 KB |
| mono | inconsolata | repo statics | 2 | 213 KB |
| | | | | **1.04 MB** / 1,043,612 B |
| cursive | dancingscript | manifest | 2 | 163 KB |
| cursive | pacifico | repo static | 1 | 329 KB |
| cursive | caveat | manifest | 2 | 514 KB |
| cursive | greatvibes | repo static | 1 | 458 KB |
| cursive | homemadeapple | repo static (Apache) | 1 | 110 KB |
| cursive | permanentmarker | repo static (Apache) | 1 | 75 KB |
| cursive | rocksalt | repo static (Apache) | 1 | 124 KB |
| | | | | **1.77 MB** / 1,772,820 B |

**The four packs stand as the plan specifies them — no swaps are needed.** Two notes: Merriweather is 77% of
`popular-serif` on its own (≈ 1 MB a face), and it now carries an optical-size axis, so its statics are named
`Merriweather_24pt-*` / `Merriweather_120pt-*`; 24pt is the text default and the pipeline needs a rule for
picking one, since §4.3 step 2 says optical-size axes are not instanced.

**Dehinting still collides with Reserved Font Names.** Dehinting is a modification, so an RFN family ships
byte-identical to upstream or not at all. Measured: Lato −26 to −28% a face (≈ 690 KB over the four),
instanced Roboto −17%, instances of VFs that never carried hinting ≈ 0% (Montserrat −1.2%, JetBrains Mono
−0.0%). Exempt RFN families from the dehint step rather than drop them.

**The popular families should declare `useOTL => 0`.** Lato's statics predate GDEF and mPDF refuses OTL
outright (`does not include OTL tables (or at least not a GDEF table)`) — true of both the repo and the
manifest build; instanced Montserrat at `0xFF` produces an `Undefined array key` warning storm from
`TTFontFile.php:2819`. Lora, Playfair Display, Merriweather, Roboto, JetBrains Mono and Dancing Script are
fine at `0xFF`, but none of these Latin families needs shaping and mPDF's `kern`-table kerning is independent
of `useOTL`.

## Legal Signing already depends on five cursive families

`src/View/html/PDF/add_legalsigning_styles.php:66-85` — the styles Gravity PDF injects for the Legal Signing
for Gravity Forms field — sets `font-family` for **Caveat, Dancing Script, Homemade Apple, Permanent Marker**
and **Rock Salt**, one per text-signature style the user picks. Dancing Script and Caveat are in
`popular-cursive`; **Homemade Apple, Permanent Marker and Rock Salt are in no pack in the plan**, so a
signature in one of those three falls back to `cursive` unless the site owner installs the font by hand.

**Decided 2026-09-07: all three join `popular-cursive`**, which now names seven families and covers every
signature style the add-on offers. They are Regular-only with no variable font, and unlike the rest of the
pack they sit under `apache/` in `google/fonts` with a plain static in the repo, so source precedence rule 1
applies and no manifest lookup is needed: Homemade Apple 110,004 B, Permanent Marker 74,632 B, Rock Salt
124,372 B — 309,008 B for the three, taking the pack to 1,772,820 B. Apache 2.0 has no Reserved Font Name, so
the dehint step is allowed here: Rock Salt −12.5%, Homemade Apple −0.5%, Permanent Marker −0.8%. All three
render in the forked mPDF at `useOTL => 0`, dehinted and not; at 0xFF they fail the same way Lato does
(`does not include OTL tables (or at least not a GDEF table)`), so their entries must declare `useOTL => 0`.
