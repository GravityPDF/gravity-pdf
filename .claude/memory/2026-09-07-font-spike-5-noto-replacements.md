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
| Kaputa (Sinhala) | Noto Sans Sinhala | 80/80, +31 | OK **after the fork's MarkGlyphSets + GDEF offset fixes** | 178,804 B R+B | **Replace** (2026-09-07) |
| Lanna Alif (Tai Tham) | Noto Sans Tai Tham | 127/127 | OK **after the 5/3 → 6/3 rewrite** | 233,736 B R+B | **Replace** (2026-09-07; taken on licence-hygiene/maintenance grounds — 203 Δpx, and it costs 97 KB) |
| Zawgyi One (no licence) | Noto Sans Myanmar | 132/132, +91 | OK **after the 5/3 → 6/3 rewrite** | 295,096 B R+B | **Replace** (2026-09-07; strongest of the four — real `mym2` shaping, Unicode instead of Zawgyi) |
| Sundanese Unicode (GPLv3, no exception) | Noto Sans Sundanese | 55/55, +17 | OK **after the 5/3 → 6/3 rewrite** | 22,784 B R+B | **Replace** (2026-09-07; a licence fix — mPDF has no Sundanese shaper, 23 Δpx) |
| UnBatang (GPLv2, no exception) | Noto Sans KR | 11,522/11,522 Hangul + Jamo | OK, peak 26 MB | 12.45 MB R+B vs 12.95 MB | **Replace, R+B** (2026-09-07) |
| Sun-ExtA (source/licence not found) | Noto Sans SC | 27,506/27,506 of URO + Ext A | OK, peak 26 MB | 10.60 MB vs 22.99 MB (−54%) | **Replace** (2026-09-07) |
| Sun-ExtB | — | Noto Sans SC covers 54 of 42,711 Ext B codepoints | — | — | **No replacement; keep** |

Notes on the two that replace cleanly:

- **Korean.** UnBatang is a *serif* (Batang), so the faithful match is Noto Serif KR — but that is 28.25 MB
  for R+B against UnBatang's 12.95 MB. **Decided 2026-09-07: Noto Sans KR, Regular + Bold** — size-neutral, and the
  serif → sans change reaches only *fresh* installs of the `korean` pack, because upgrade step 1c adopts an existing
  site's `UnBatang_0613.ttf` under its frozen `unbatang` key as an `imported` row (§4.8 of the 7.0 plan). Noto Serif
  KR rejected on size.
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

**The `useOTL => 0` recommendation in the first pass was wrong, and was measured on the wrong file.** It rested on
Lato failing (true) and on "instanced Montserrat at `0xFF` produces an `Undefined array key` warning storm" — but the
plan ships Montserrat from Google's *manifest statics*, not an instance. Re-run against the shipped artefacts
(2026-09-07), 44 faces across all four packs, Montserrat did not warn: it **exhausted 512 MB** in
`_getGDEFtables()`, and Open Sans emitted **78,099** notices.

**Root cause — one upstream bug.** `TTFontFile::_getGDEFtables()` reads the MarkGlyphSets coverage offsets and seeks
to them *as absolute file offsets*; per the spec they are ULONGs measured from the start of the MarkGlyphSetsDef
table. Montserrat's are 20/96/106/146, so mPDF parses the font's own table directory as a Coverage table — garbage
that decodes as a format-2 range (OOM) or format 1 with bogus glyph IDs (the notice storm). **Every GDEF 1.2 font has
silently had empty mark glyph sets**, including Noto Sans Sinhala: the fork's merged MarkGlyphSets fix
(GravityPDF/mpdf#1) has been consuming garbage and worked only because the garbage parsed as an empty set.

Correcting the seek unmasks two latent dereferences of a subtable whose entries were *all* filtered out by the Ignore
flags, which therefore never gets a `subs` key: the second-pass loops for LookupTypes 2-4, and the secondary-lookup
scan inside the chaining-context rules (`if (count($Lookup[$lup]['Subtable'][$lus]['subs']))` → `!empty(...)`, four
sites). With all three fixed: mPDF's suite **1016 tests, 2307 assertions, OK**; Sinhala renders; Montserrat and Open
Sans come back clean. Local commit `9dabb6a` on `spike/markglyphsets-offset`, **not yet PR'd**.

**Per-family `useOTL` on the fixed parser** (Δpx = one sample document, all faces, 150 dpi; "shipped" = `useKerning`
false, "kerned" = true):

| Pack | Family | GDEF | GPOS `kern` | Δpx shipped | Δpx kerned | `useOTL` |
|---|---|---|---|---|---|---|
| sans | lato | **none** | unreachable | FAIL | — | `0` forced |
| sans | roboto | Y | Y | 63,372 | 71,214 | `0xFF` |
| sans | opensans | Y | — | 28,177 | 28,177 | `0xFF` |
| sans | montserrat | Y | Y | 6,456 | 61,517 | `0xFF` |
| serif | lora | Y | Y | 28,707 | 76,846 | `0xFF` |
| serif | merriweather | Y | Y | 34,199 | 73,121 | `0xFF` |
| serif | playfairdisplay | Y | Y | 55,649 | 84,987 | `0xFF` |
| mono | robotomono | **none** | no GPOS | FAIL | — | `0` forced |
| mono | jetbrainsmono | Y | — | **0** | **0** | `0` |
| mono | inconsolata | Y | — | **0** | **0** | `0` |
| cursive | dancingscript | Y | Y | 7,388 | 31,695 | `0xFF` |
| cursive | pacifico | Y | Y | 21,039 | 26,371 | `0xFF` |
| cursive | caveat | Y | Y | 37,787 | 37,838 | `0xFF` |
| cursive | greatvibes | Y | Y | 14,807 | 17,642 | `0xFF` |
| cursive | homemadeapple | **none** | no GPOS | FAIL | — | `0` forced |
| cursive | permanentmarker | **none** | legacy `kern` | FAIL | — | `0` forced |
| cursive | rocksalt | **none** | legacy `kern` | FAIL | — | `0` forced |

Ten families take `0xFF`, seven take `0` — five for want of a GDEF table, and the two monospaces because they gain
literally nothing. Parse cost of `0xFF` is negligible (+0.03 s, +6 MB on Merriweather's 853 KB GPOS, once per site).

**Not one popular family ships a legacy `kern` table**; all their kerning is GPOS. So `useOTL => 0` does not merely
forgo shaping, it forecloses kerning — which is why §9.25 (kerning on globally) depends on this table. Lato is the
one case where the GPOS is genuinely dead weight: 210 KB a face, 840 KB of its 2.73 MB, unreachable without a GDEF.
Merriweather's 853 KB a face is the opposite — fully usable at `0xFF`.

**Kerning is a second, independent switch.** `useKerning` is `false` in mPDF's defaults and Gravity PDF never set it,
so no release has ever kerned. It gates *both* paths (`Mpdf.php:4601-4607`), so `useOTL` alone never kerns. Measured
cost on a 15-page submission PDF, warm metrics cache: Arimo 0.44 s → 1.12 s, DejaVu Sans Condensed 0.54 s → 0.77 s;
at 10 pt body text the visual difference is sub-pixel, and the real wins are display sizes and script faces (Great
Vibes' `TA` collision in "AVATAR" only resolves with kerning on). CSS `font-kerning: normal` works per element with
the global off (verified, 20,003 px). **Decided 2026-09-07: on globally** — §9.25.

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

## All seven swaps taken — 2026-09-07

Decided in session and written into the 7.0 plan (§2.4, §4.3 pack table, §4.8 step 1c, §7 back-compat table, §9.3):
**every flagged font is replaced except Sun-ExtB**, which has no Noto covering Plane 2 and is now the only font left
in the pre-release legal-posture assessment. Three consequences the plan did not previously cover, added with them:

1. **The pipeline needs a frozen `REPLACES` map** (old mPDF key → new key). `language_to_font`, `scripts` and
   `languages` are generated from the prefixed `LanguageToFont` class, which is keyed on *mPDF's* font keys — so
   without it `ko` generates to `unbatang`, no pack provides that key, and Korean silently falls through to the
   backup font. A build-time check asserts every generated value is a key some pack registers.
2. **Cherokee leaves the `americas` pack.** Aboriginal Sans covered 85/96 of U+13A0–13FF; Noto Sans Canadian
   Aboriginal covers none of it. Measured: `indic`'s FreeSans covers exactly the same 85/96, and mPDF's own
   `LanguageToFont` already sends Cherokee there — so only the pack's script list changes, not the coverage.
3. **No upgraded site loses a font.** The replaced originals are all `LEGACY_INSTALLER_FILES` basenames, so upgrade
   step 1c adopts each as an `imported` row under its frozen 6.x key with zero network. The swaps are a
   fresh-install-only change — which is also what makes the Myanmar swap safe, since Zawgyi-encoded text would be
   mojibake if re-rendered in Unicode Noto Sans Myanmar.

Also closed the same day: **Aboriginal Serif** (no pack names it, so 7.0 does not ship it and it needs no
replacement); **`popular-serif` keeps Merriweather** despite being 77% of the pack (Source Serif 4 / PT Serif were
the smaller alternatives; the pack is never auto-installed, so only a site that clicks it pays); and the per-family
**`useOTL`** table above is adopted as measured — ten `0xFF`, seven `0`.

Pack sizes recomputed from the fork's font packages, since the plan's figures were 6.x's single-face core-fonts set:
`cjk` 40.6 → 28.2 MB, `korean` 6.9 → 12.45 MB, `southeast-asian` 2.5 → 4.61 MB, `americas` 0.8 → 0.19 MB. The 7 MB
inline cap still admits Korean on first render (Noto Sans KR's `R` is 6.22 MB, against UnBatang's 6.94) and still
excludes the CJK BMP face (Noto Sans SC 10.60 MB).
