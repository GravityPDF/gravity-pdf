# Project Memory Index

- [PR Description Format](2026-05-28-pr-description-format.md) — Human-friendly Summary/Try it/Test plan up top; collapse AI-dense detail and attribution into `<details>More info</details>`
- [Link Fixed Issues in PRs](2026-05-28-pr-link-fixed-issues.md) — Before `gh pr create`, find issues the PR resolves and add `Closes #N` lines to the Summary
- [Font spikes 0/1/3/4](2026-09-04-font-spike-0-mpdf-fork.md) — fork + #2161 extended (incl. getId, so §9.23 collapses to one Package class); Arimo+DejaVu bundled and rendering; zip 5.16MB; detect() 0.029ms; four plan corrections
- [Font spike 5](2026-09-07-font-spike-5-noto-replacements.md) — mPDF's OTL parser (GSUB 5/3 + MarkGlyphSets) blocks 4 of 7 Noto swaps and gates the whole google source; Aboriginal/UnBatang/Sun-ExtA replace, Sun-ExtB has none; popular-serif is entirely RFN-blocked and dehinting conflicts with RFN
