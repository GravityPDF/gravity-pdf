---
name: font-spike-6-pack-split
description: Phase 0 spike 6 — pack sizes recomputed from the fork packages, the CJK four-way split, and why Noto Sans SC cannot render Japanese
metadata:
  type: project
---

Phase 0 spike 6 of `.claude/plans/2026-08-26-remove-core-font-installer.md`, run 2026-09-07. Follows
[[font-spike-5-noto-replacements]], whose swaps changed the inputs. Final table is §4.3; decision is §9.4.

## The plan's pack sizes were 6.x figures, not 7.0 ones

§4.3's sizes were the old `build/payload/core-fonts.json` set — one face per family, as 6.x's core-font installer
shipped them. 7.0 builds packs from the **fork's font packages**, which carry the full face sets. Recomputed by
parsing every `packages/*/Registration.php` `getFonts()` and summing the referenced files (decimal MB, matching the
plan's convention):

| pack | MB | | pack | MB |
|---|---|---|---|---|
| cjk (pre-split) | **28.23** | | popular-sans | 5.27 |
| cjk-ext-b | 17.63 | | arabic | 5.01 |
| indic | 13.55 | | southeast-asian | 4.61 |
| korean | 12.45 | | popular-cursive | 1.77 |
| ancient-scripts | 11.54 | | popular-mono | 1.04 |
| chinese-simplified | 10.60 | | emoji | 0.88 |
| dejavu | 9.16 | | african | 0.62 |
| chinese-traditional | 7.15 | | americas | 0.19 |
| japanese | 5.77 | | hebrew-syriac | 0.18 |
| popular-serif | 5.56 | | barcode | 0.02 |

`serif-mono` (tinos, cousine) is not in the fork — it comes from `google/fonts`, so spike 8 sizes it.

**Only `cjk` breached the 15 MB rule.** None of the brief's other split candidates came close: `indic` is 13.55 MB
(not the 14.5 the plan carried), so the FreeFont-vs-single-script split is unnecessary; `arabic`, `southeast-asian`
and `ancient-scripts` are all well under.

## Two qualifications the 15 MB rule needed

1. **A pack cannot be smaller than its largest single font.** The brief's BMP/SIP split leaves Sun-ExtB alone at
   17.63 MB — one file, unsplittable, still over the threshold. It ships as `cjk-ext-b` regardless. Side benefit:
   Sun-ExtB is the only font left in the pre-release legal-posture assessment, so isolating it means it could be
   dropped without touching CJK BMP support.
2. **The per-language CJK question resolved the opposite way to how the brief posed it.** The brief asked whether a
   per-language subset ≤ 7 MB is *viable* as a size optimisation. It is not merely viable — it is **required for
   correctness**.

## Noto Sans SC cannot render Japanese, and the plan assumed it could

§4.4 said `locl` picks each language's forms "in fonts that carry them (the `cjk` pack's Noto Sans SC does)". It
carries `locl`, but its GSUB `ScriptList` declares only the **`ZHS`** language system — there is no `JAN`, so a `ja`
run has no Japanese forms to select. Noto Sans JP declares `JAN` plus `jp78`/`jp83`/`jp90`/`nlck`.

Confirmed on the outlines rather than the tags alone: comparing glyph pen digests between SC and JP, **12 of 13
sampled kanji differ** (直 骨 今 化 令 海 角 喝 勇 類 収 図; only 者 matched). Both fonts are Source Han Sans
derivatives, so a coordinate difference *is* a regional form difference, not a design difference. TC is not a
substitute either — it lacks 収 and 図 outright.

In-script coverage and inline capability (the 12 MB cap, below):

| font | bytes | URO | Ext A | kana | langsys |
|---|---|---|---|---|---|
| Noto Sans SC | 10,595,932 | 99.9% | 99.8% | 93/96 + 96/96 | ZHS |
| Noto Sans TC | 7,149,180 | 73.3% | 8.7% | same | (TC build) |
| Noto Sans JP | 5,766,884 | 60.7% | 3.1% | same | JAN |

JP's 60.7% of URO is comfortably above JIS X 0208's ~6,355 kanji, so it is complete for ordinary Japanese.

## Detection works because mPDF's script tags are not all `und-*`

`ScriptToLanguage.php:114-122` emits plain language codes where a script implies one: Hiragana **and** Katakana →
`ja`, Hangul → `ko`, Cherokee → `chr`, Canadian Aboriginal → `cr`. The plan described the detector's output as an
"`und-*` set", which was imprecise and now matters:

- **Japanese self-resolves** — any kana emits `ja` → the `japanese` pack. Real Japanese text is essentially never
  kanji-only, so this is the normal path.
- **Han alone emits `und-Hans` unconditionally** (`SCRIPT_HAN => 'und-Hans'` regardless of the actual characters),
  so a kanji-only or Traditional document detects as Simplified. `chinese-simplified` is the right fallback: it is
  the only one of the three covering all of Han, so every character renders — only the regional forms are wrong.
- **Traditional Chinese is not distinguishable from Simplified by script.** It is reached through the locale and
  settings triggers (`zh-TW` / `zh-HK`), never through content detection. Recorded so the gap is not read as a bug.

## The dangling `sip-ext` across entries is safe — measured, not assumed

`notosanssc` declares `'sip-ext' => 'sun-extb'`, and `initFontRegistry()` sets `backupSIPFont` from it whether or
not `cjk-ext-b` is installed. `AddFont()` **throws** `Font "sun-extb" is not supported` when `fontdata` has no row
(`Mpdf.php:3950`), and the substitution path at `Mpdf.php:26040` does call `SetFont($this->backupSIPFont, …)` — so
this looked like a fatal waiting to happen.

It is not. Rendering U+20000 and U+2A6B2 through `notosanssc` with `cjk-ext-b` absent produced a clean one-page PDF
embedding Noto Sans SC alone; with it present, the same document embedded Sun-ExtB too. The path bails before
`SetFont()`. Degradation is absent glyphs, never a fatal — but it is one branch away, so §6 pins it with a test.

**Harness trap:** `new FontRegistry()` with no arguments **auto-loads every font package listed in `composer.lock`**,
so `$reg->add(...)` appends to an already-populated registry. A first run "proved" the no-Ext-B case safe while
silently embedding Sun-ExtB — both PDFs were byte-identical. Pass the classes explicitly (`new FontRegistry([$reg])`)
to isolate. Gravity PDF is unaffected in production: it passes its own `Package_*` layers.

## Inline cap raised 7 MB → 12 MB

Decided 2026-09-07 alongside the split. At 7 MB only Japanese would have been inline-capable; at 12 MB
(12,582,912 B) **every CJK and Korean `R` face except Sun-ExtB is** — Noto Sans SC 10.60, TC 7.15, KR 6.22, JP 5.77.
Only Plane 2 supplements fall to background installs with the Adobe-CJK overlay. The 10 s `request_multiple()`
window is unchanged and remains the real protection for a slow host: a fetch that misses it falls through to
background rather than stalling the submitter.
