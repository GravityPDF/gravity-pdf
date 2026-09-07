# Project Memory Index

- [PR Description Format](2026-05-28-pr-description-format.md) — Human-friendly Summary/Try it/Test plan up top; collapse AI-dense detail and attribution into `<details>More info</details>`
- [Link Fixed Issues in PRs](2026-05-28-pr-link-fixed-issues.md) — Before `gh pr create`, find issues the PR resolves and add `Closes #N` lines to the Summary
- [Font spikes 0/1/3/4](2026-09-04-font-spike-0-mpdf-fork.md) — fork + #2161 extended (incl. getId, so §9.23 collapses to one Package class); Arimo+DejaVu bundled and rendering; zip 5.16MB; detect() 0.029ms; four plan corrections
- [Font spike 5](2026-09-07-font-spike-5-noto-replacements.md) — mPDF's OTL parser (GSUB 5/3 + MarkGlyphSets) gates the whole google source; both blockers fixed, so **all seven Noto swaps taken 2026-09-07**, Sun-ExtB alone kept; popular packs stand as specified (Merriweather stays); per-family `useOTL` measured; dehinting conflicts with RFN
- [Font Spike 6: Pack Split](2026-09-07-font-spike-6-pack-split.md) — Pack sizes recomputed from the fork packages; `cjk` splits four ways because Noto Sans SC declares only `ZHS` and cannot render Japanese; inline cap 7 → 12 MB
- [Font Spike 7: Hosting](2026-09-07-font-spike-7-hosting.md) — R2 on a public custom domain as `files_base`; GitHub releases ruled out (signed redirect vs `redirection => 0`, plus the AUP throttle clause); Worker binding isn't edge-cached
- [Font Spike 8: Google pipeline](2026-09-07-font-spike-8-google-pipeline.md) — 2,050 families, true skip list is 1; ~7,800 files is the *variants* set not the default (3,423); GDEF 1.3 silently drops MarkGlyphSets on 80% of VFs (fork PRs #26 + #34, both green)
- [Font Phase 1b: Tables](2026-09-07-font-phase-1b-tables.md) — the three font tables, repository and 6.x migration built; the temporary-table trap, rows-before-unlink ordering, and three tests that asserted on the store
