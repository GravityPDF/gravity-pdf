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

Scanned `google/fonts@main`. "Blocked" = declares a Reserved Font Name **and** ships no `static/`, so the
only way to get a static TTF is `instancer`, which makes a Modified Version that may not keep the name.

| Pack | Family | Licence | RFN | Statics | Verdict |
|---|---|---|---|---|---|
| sans | roboto | OFL | none | VF-only | instance |
| sans | opensans | OFL | none | VF-only | instance |
| sans | lato | OFL | **Lato** | yes (4 faces, 2.61 MB) | ship unmodified |
| sans | montserrat | OFL | none | VF-only | instance |
| mono | robotomono | OFL | none | VF-only | instance |
| mono | jetbrainsmono | OFL | none | VF-only | instance |
| mono | inconsolata | OFL | none | yes (416 KB) | ship unmodified |
| serif | lora | OFL | **Lora** | VF-only | **blocked** |
| serif | merriweather | OFL | **Merriweather** | VF-only | **blocked** |
| serif | playfairdisplay | OFL | **Playfair Display** | VF-only | **blocked** |
| cursive | dancingscript | OFL | **Dancing Script** | VF-only | **blocked** |
| cursive | pacifico | OFL | none | yes (322 KB) | ship unmodified |
| cursive | caveat | OFL | none | VF-only | instance |
| cursive | greatvibes | OFL | none | yes (447 KB) | ship unmodified |

**`popular-serif` as specified is entirely blocked.** Swaps that clear the bar, all OFL with no RFN:
PT Serif (4 statics, 1.38 MB), Crimson Text (4 statics, 435 KB), Spectral (4 statics, 1.06 MB), Zilla Slab
(4 statics, 1.07 MB); EB Garamond, Literata, Bitter, Vollkorn and Domine are VF-only but RFN-free, so
instanceable. For `popular-cursive`, Dancing Script swaps to Allura (241 KB), Parisienne (60 KB),
Cookie (43 KB), Satisfy (Apache, 47 KB) or Kalam (867 KB).

**Dehinting collides with Reserved Font Names.** Dehinting is a modification, so an RFN family can only ship
byte-identical to upstream. Measured savings: Lato −26 to −28% per face (≈ 690 KB over the four), instanced
Roboto −17%, VF-derived instances that never carried hinting ≈ 0% (Montserrat −1.2%, JetBrains Mono −0.0%).
Recommendation: **exempt RFN families from the dehint step** rather than drop them — the fonts are fetched on
demand from our origin, so ~690 KB on Lato is not worth losing the family.

**The popular families must declare `useOTL => 0`.** Lato's statics predate GDEF and mPDF refuses OTL
outright (`does not include OTL tables (or at least not a GDEF table)`); instanced Montserrat at `0xFF`
produces an `Undefined array key` warning storm from `TTFontFile.php:2819`. Roboto and JetBrains Mono are
fine at `0xFF`, but none of these Latin families needs shaping and mPDF's `kern`-table kerning is
independent of `useOTL`.
