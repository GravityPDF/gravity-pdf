# mPDF `page-break-inside: avoid` cluster — done on fork PR #38

Plan: `.claude/plans/2026-09-07-mpdf-issue-triage.md` Tier 3 (all nine rows struck through on 2026-09-09).
Fork checkout: `~/Sites/mpdf-gravitypdf`, branch `mirror/604-avoid-table-background` = the PR #38 head (rebased onto `origin/gravitypdf` 2026-09-10; pre-rebase head kept as local branch `backup/pr38-before-rebase`). Five commits
were added on top of the table-background commit on 2026-09-09, one per root cause, and the PR title and body were
rewritten to cover the cluster. **Marked ready for review 2026-09-10** after the move to
the whole-object snapshot below, then **regrouped into seven topical commits** (mechanism, table backgrounds,
substitution, page-break-after, caption, floats, snapshot document; each green in sequence) and force-pushed; the
16-commit history is kept as local branch `backup/pr38-before-regroup`. The regroup was built from the final tree
with `scratchpad/regroup.py` (hunks classified by regex per file, test methods per topic), not by rebase -i. Not
merged into `gravitypdf` yet; the plugin's `composer.lock` still pins the old SHA.

## The mechanism

A `page-break-inside: avoid` block is laid out once to measure it; `BaseWriter::write()` discards output while
`keep_block_together` is set. If it ran onto another page, `BlockTag::close()` deletes the later pages, resets `y`
and re-parses from `array_i` with `pagebreakavoidchecked`. The starting page is kept. **`AddPage()` switches the
discard off while it runs (`$save_kt`)**, so during the measuring pass it wrote the footer, watermark and page/body
backgrounds onto the starting page, and `_beginpage()` selected the *next* page's `@page` rules, margins and
header/footer. Fix (final form, commit 907c6fe): `BlockTag::open()` takes `Mpdf::getStateSnapshot()` (the TOC
look-ahead's whole-object snapshot) at the point the old curated list was saved, after the parent's pending text is
flushed and any forced page break, gated on the CSS preview `open()` already runs; `close()` restores it whole. An
earlier version of the PR used a curated list (`BlockTag::$pageState`); it is gone.

**Why:** the four candidate patches on the issues only restored `pages[$page]`, which would have dropped the
backgrounds collected before the block (`pageBackgrounds` is flushed and cleared by AddPage) and left `bb_painted`
set on enclosing blocks, so their borders would vanish from the first page.
**How to apply:** anything that makes a keep-together-style rewind goes through `getStateSnapshot()` /
`restoreStateSnapshot()`; do not add `keep_block_together` guards for Mpdf-held state (it is rolled back). Guards
are only for state on other objects (`Form` fields, `TableOfContents::_toc`).

## Which of the nine were something else

- **785** (float inside the block): the float branches cancelled keep-together on `$this->mpdf` but left the enclosing
  block flagged. The cancel is gone: a float inside a kept block is measured with it, and (later the same day) a
  floated block is kept together itself — see "Later additions".
- **322 / the #1131 thread cases** (`<p>` inside `position:absolute` or a footer printed twice): output is *buffered*
  there, not discarded. Gate the measuring pass on `!$this->mpdf->bufferoutput` (`writingHTMLheader/footer` are
  proxies; `processingHeader/Footer`, which BaseWriter tests, are never set anywhere).
- **2075** (`useSubstitutions` duplication): the substitution splices a span into the token array and trims `$e`
  but not `$a[$i]`; the second parse printed the untrimmed token. `SubstituteCharsSIP` already wrote back; the other
  seven splice sites now do.
- **1801** (blank pages before a wide image): not `page-break-inside` at all. `page-break-after: avoid` demands room
  for a second block-height line in `Cell()` (`y + 2h + bottom`) and `finishFlowingBlock()` (`check_h += stackHeight`);
  both now skip when `tMargin + that > PageBreakTrigger`.
- **1666** (caption left behind): keep-with-next, not replay state. Under `use_kwt` the top caption gets
  `keep-with-table="1"` in `AdjustHTML` (folded into the caption-moving regex). Without `use_kwt`, unchanged; the kwt
  buffer path drops block backgrounds, so making it unconditional was judged too risky.

## Tooling that worked without Imagick

- `composer test` **excludes the `snapshot` group**; CI's Snapshot job compares images via Imagick, which is not
  installed locally (`php -m` has gd only). Regenerate the documents by instantiating each `tests/Snapshots/*Test`,
  calling `generatePdf()` and reading the `mpdf` property by reflection; compare old-tree vs new-tree PDFs after
  normalising `/CreationDate`, `/ModDate`, **`/ID [<a> <b>]` (there is a space between the hashes)** and the link
  annotations' **`/M (D:…)`** timestamps. All 22 existing documents were byte-identical old vs new.
- `pdftoppm -r 50 -png` and `pdftotext -layout` (poppler, homebrew) to look at pages and locate text.
- Uncompressed core-font (`mode => 'c'`, `compress = false`) streams are grep-able as `(text) Tj`; TrueType text is
  UTF-16BE, so search for `mb_convert_encoding($text, 'UTF-16BE', 'UTF-8')`. Font programs also match a naive
  `<</Length N>>stream` page regex, so count pages only in core-font documents.
- A block whose bottom padding does not fit at the very foot of a page paints a 3–4 mm top+sides stub at the top of
  the next page. **Pre-existing mPDF behaviour**, unrelated to keep-together; retune fixtures rather than chase it.
- PHP 5.6 is still in the fork's CI matrix: no `??`, no return types.

## Later additions (same day)

- **Floated blocks now honour `page-break-inside: avoid`** (commit "Keep a floated block together when it asks to be").
  A float's close resets `y`/`page` to the float's start, so the keep-together check never saw the block leave
  its page; a block being measured now skips its own float close (all of it is thrown away by the unwind), which
  leaves the cursor where the float ended for the check. The R/L float branches in `close()` are one branch.
  Plain floats are unchanged (they still split at the page foot). The block's start `page`/`y` ride in
  `kt_state` with the rest; `kt_y00`/`kt_p00` on `Mpdf` are declared but no longer written.
- **Whole-object snapshot landed (2026-09-10, commit 907c6fe)**, replacing the curated list; assessment and spike in
  `.claude/plans/2026-09-09-mpdf-keep-together-whole-object-snapshot.md`. Things learned doing it for real:
  - The snapshot must sit where the curated one did (before `blklvl++`, after `printbuffer($textbuffer)` and the
    page-break-before handling), not at the top of `open()`: earlier, and inline text preceding the block in the same
    parent is dragged onto the next page with it (test `testTextBeforeTheBlockInTheSameParentStaysWhereItWas`).
  - `getStateSnapshot()` now skips the loader caches (`fonts`, `FontFiles`, `extraFontSubsets`, `images`,
    `formobjects`, `CurrentFont`; a local list in `getStateSnapshot()`): restoring them decoded every image/font first used
    inside a kept block twice (2000² PNG: 0.24 s → 0.46 s). `restoreStateSnapshot()` rebinds `CurrentFont` by
    `FontFamily . FontStyle` because `SetFont()` early-returns while they match; `Write()` after a restore otherwise
    registers glyphs in the wrong font's subset (`tests/Mpdf/StateSnapshotTest`). WriteHTML happens to rebind, so
    the symptom only shows through the direct API.
  - The `<li>` counter guard on `PAGEBREAKAVOIDCHECKED` had to go (counter is rolled back now); the BaseWriter
    discard, AddPage's `$save_kt`, and the guards on Link/Annotation/Bookmark/IndexEntry/internallink/PaintDivBB/
    DivLn/spendTableBackgrounds/Td+BlockTag background-image are gone. `Form` and `TOC_Entry` guards stay (object
    state); `Tr`, kwt and CssMerger guards are about nesting and were left (a follow-up could drop them).
  - Cost: plain documents unchanged; all-kept 3000 paragraphs +8 %. Follow-up filed as GravityPDF/mpdf#57: since
    the measuring pass now writes real output, accept it when the block did not change page and skip the re-parse
    (drop the Tr/kwt/CssMerger layout guards first, then deal with Form/TOC state).
  - Snapshot docs: `page-break-avoid-state`, `page-break-avoid-floats` (fixtures regenerated) and `rtl` lose 63/48/8
    redundant state ops (`/GS1 gs`, `1.000 g`, `0 Tr`, `BT /F1 11 Tf ET`…) because `pageoutput` is restored rather
    than reset; nothing added. `rtl.pdf` fixture left alone (visually identical).
  - Tooling: `diff` on decompressed streams needs `-a` (UTF-16 text looks binary); `grep`/`diff` are aliased in this
    shell, use `/usr/bin/…` when counting. `default_font` in utf-8 mode is `dejavuserifcondensed`, not dejavusans.
- **Eighth commit (2026-09-10): tests for what a kept block registers beyond its text** (`PageBreakInsideAvoidStateTest`,
  11 cases × moves/stays, and the `page-break-avoid-references` snapshot document), after the user judged the
  snapshot coverage insufficient for the size of the change. Learned on the way: **a leading `<tocpagebreak />`
  shifts pages but mPDF numbers the index and places form fields from before the shift** (pre-existing, same on
  `gravitypdf`; keep TOC out of documents with an index or fields; filed as GravityPDF/mpdf#56); the trait's `pngImage()` has an alpha channel
  and does not tile as a cell background (use `tests/data/img/bg.jpg`, now `backgroundImage()` in the trait); a
  gradient paints with `sh`, an image background with `/Pn scn`; `useActiveForms` is a config key.
- **Ninth commit (2026-09-10): `page-break-avoid-content` snapshot document** (lists in decimal/start, lower-alpha,
  upper-roman, lower-greek, nested disc/circle/square; an inline picture; a table with a header row; on a block
  that stays and one that moves), after the user asked whether "everything goes with it" was actually tested.
  Unit cases for a list, an inline image and a table inside a moving block were offered and not asked for.
- **Tenth and eleventh commits (2026-09-10): unit cases for a list, picture and table in a moving block; form
  fields.** On `gravitypdf` *any* form field inside a kept block throws (`Strict::__get('ktForms')`, the guarded
  branch wrote to an undeclared property). With that gone, radio buttons registered their group kids and submit
  buttons their action outside the field's guard during the measuring pass (dangling `/Kids`, stub entries with
  no page); both now follow the guard (`Form::SetFormButton`, `SetFormSubmit`). `page-break-avoid-forms` snapshot
  document has every field type on a stays/moves pair. poppler's "Unknown font tag 'ZaDb'" on checkboxes is
  noise (poppler regenerates the checkbox appearance under `NeedAppearances` with a `/ZaDb` tag the `/DR` lacks;
  filed as GravityPDF/mpdf#59). A `selected` option IS written (`/V (2)` export value, UTF-16, note the double
  space after `/V`); poppler renders the first option anyway, filed as GravityPDF/mpdf#58.
- **The state document's captioned table never moved** (25 filler lines spilled onto page 4 by
  themselves, the fixture trap again; now 15) and the watermark was one static string. Now `<watermarktext>` names
  each page and each moving block sets the next page's text as it opens, so a watermark the measuring pass drew on
  the page it left would show. `wm.php` in the scratchpad lists the watermark text per page from the streams.
- **All six PR documents reviewed for the fixture trap** (the state and references fixes were then folded back into the commits that introduced those documents, so the PR stays at eleven commits; pre-fold history in local branch `backup/pr38-before-fold`) with `scratchpad/pagefoot.py`
  (`pdftotext -bbox-layout`: per page, where the text ends, mm of body free, first/last line). A moved block
  leaves a foot the page cannot use; a spilled one ends the page full. The references document had the trap
  (17 filler lines → 7); floats, content, forms, table-background were right. Use the script on any new document.
- **table-background document extended** (folded into the table-background commit): a kept block with a red
  table that moves whole to page 2 (the #570 shape, previously only on the state document's last card) and a
  blue table breaking across pages 2–3, painted once per part. `scratchpad/fills.php` counts the fills per page.
- **`table.pdf` fixture change, verified 2026-09-10:** one opaque light-blue fill (page 1, the second table, 32–95 mm
  from the left, 229–256 mm from the top) painted twice on the base and once on the branch, plus whitespace where
  placeholders are spent; poppler at 120 dpi is pixel-identical. The earlier "three pixels of antialiasing" claim
  was not reproducible locally and was removed from the commit message. Compare rasters with `pdftoppm` to PPM and a
  byte diff in python when Imagick is not available.
- **A checkout of the fork older than PR #45 (2026-09-08) hangs in `new Mpdf()` without `composer.lock`**:
  `FontRegistry` walked up from `src/` until it found the (gitignored) lock file and `dirname('/')` is `/`.
  Fixed on `gravitypdf` by #45; copy `composer.lock` into any worktree of an older commit.
- **Snapshot-document comparisons must be like for like**: a build in a worktree with a symlinked `vendor`
  resolves `__DIR__` to the main checkout and loads *its* `src`, and even with a copied `vendor` a worktree build
  differs from a main-checkout build by equal-length bytes outside the page content. Build both sides in
  worktrees (copied `vendor`), normalise the Info object / `/ID` / link `/M` / the checkout path, and diff the
  decompressed content streams (`streams2.php` + `compare4.php` in the 2026-09-09 scratchpad) when bytes differ.
- **PR #38's known snapshot effects on pre-existing documents**: `table.pdf` regenerated on purpose (mpdf#604
  mirror); seven documents (basic-html, list-style-type, paging-css, paging-html, rtl, toc-and-index,
  positioned-html) differ only in whitespace where a table without a background used to leave its
  `___TABLE___BACKGROUNDS` placeholder for `PageWriter` to collapse; `spendTableBackgrounds()` now spends it
  in place. Same set and same op counts on the old base and on the 2026-09-10 rebase.
- **Test-fixture trap:** a page takes ~29 single-line `<p>Filler</p>` paragraphs in core-font mode and ~37 of the
  snapshot's DejaVu lines. "30 filler + block" starts the block on page 2 by itself and exercises nothing. Sweep
  the count and confirm the previous page is left with free lines; prove new tests against the pre-change tree
  (`scratchpad/bench/before` had a usable vendor copy: drop the test files into `tests/Mpdf/` there and run
  `vendor/bin/phpunit --bootstrap vendor/autoload.php`).
- Text in DejaVu is UTF-16 in the stream: grep extracted text (`pdftotext -layout`, split on `\f`) per page, not
  the raw stream, when locating snapshot content.
- **Snapshot-harness improvements filed as GravityPDF/mpdf#55** (2026-09-10): deterministic output mode (fixed
  clock/ID, `exposeVersion` off), byte comparison before Imagick, per-page content-stream diff in artifacts,
  uncompressed fixtures, environment-free fixtures, pinned Ghostscript/Imagick, a `snapshot:update` script.

## #57: the measuring pass is kept when the block stays (2026-09-10, fork PR #60, branch `fix/57-keep-measuring-pass`)

- Four commits: kwt and CssMerger guards dropped; a `StateSnapshot` trait (scalars/arrays, generic) used as-is by
  `Form`/`TableOfContents` and by `Mpdf` (aliased `snapshotOwnState`/`restoreOwnState`), with
  `Mpdf::getStateSnapshot()` composing `form`/`tableOfContents` under those keys so a document snapshot is the
  whole document (the /simplify altitude review moved this out of `BlockTag`, which keeps one `kt_state`); the TOC
  look-ahead dropped its hand-copied `m_TOC`/`tocTocPaintBegun` but must re-pin `_toc = $lookAheadToc` right after
  the restore (the real pass renders from it before its own MovePages); `close()` computes
  `$unwind = kept && page != kt_state.page` at the float-close point and only then restores and re-parses.
  3,000 kept paragraphs, back to back on PHP 8.5: 1.64 s → 0.91 s (plain 0.73 s). Pre-rebuild history in local
  branches `backup/pr57-before-rebuild` (pre-review) and `backup/pr57-reviewed-tree`.
- Follow-ups filed 2026-09-10: **#61** the `y < start.y` movepage proxy (measure once more on a fresh page instead;
  a kept table inside a kept block defeats it like row markers do, reproduced: 8 lines + kept 26-row table after 12
  filler lines is left split while the plain-table twin moves whole), and **#62** `Mpdf::getStateSnapshot()`
  filtering 575 keys per kept block (93 ms vs 41 ms from a cached key list per 3,000; about half the remaining gap).
- The efficiency review agent overwrote `scratchpad/bench.php` with its own micro-benchmark: keep timing scripts
  under a name agents are not told about, or re-check before quoting numbers.
- **The Tr row-marker guard was kept on purpose**, against the issue's step 1: markers force the table to break
  *earlier* than its rows would, so the measured end on page 2 is lower and the `y < start.y` movepage test can
  flip from "move whole" to "leave split" for a block that would fit on one page (reproduced: 8 lines + 20 marked
  rows after 10 filler lines in core-font mode; with the guard dropped the block splits). A block that stays never
  consults the markers (`_tableWrite` reads `pagebreak-before` only when a row fails to fit), so the first pass is
  still exact in the stay case. The kwt equivalent needs the block within one row of a full page, so it was dropped.
- kwt does paint the heading's background colour; the guard's difference is a clipped fill up front vs the
  buffered path's fill later (byte-level, not visual). The unit test compares page streams kept-stays vs unkept.
- Old measuring pass bumped `Form::formCount` without registering, so `/NM (nnnn-5011)` annotation names had
  gaps; now contiguous. That is the only change in the forms/references snapshot documents (bytes, not pixels).
- Snapshot doc `page-break-avoid-layout` (DejaVu): 2 filler lines + 8 kept lines + 24 all-marked rows is the middle
  of the flip window (23–25 rows); calibrated by sweeping rows in a worktree with `Tr.php` swapped.
- A counting subclass's property is itself part of `getStateSnapshot()`: increment *after* `parent::restore…`.
- Fixture generation without Imagick: `require tests/bootstrap.php`, `new $class('testSnapshot')`,
  `generatePdf()`, reflect `outputPdf()`; relative `img/…` needs `SetBasePath(__DIR__ . '/../data')`.

## #61: the blank strip is discounted, no third pass (2026-09-10, fork PR #63, branch `fix/61-gap-corrected-move`)

- The maintainer merged #60, closed #62 (see its comment), and asked for a fill-percentage setting (default 50%,
  0-99) to gate the #61 re-measure. Reviewed against the reproductions: they start at 42/49/56% of the page, so a
  50% default skips four of the six shapes, and fill at open does not predict the outcome anyway. With an honest
  pass the old `y < start.y` test is exact whatever the fill; it only fails when something inside broke early.
- What went in instead: `Mpdf::AddPage()` does `kt_blank += PageBreakTrigger - y` while `keep_block_together`
  (reset to 0 in `BlockTag::open()`, restored by the snapshot on unwind), and `close()` moves when
  `page diff == 1 && y - start.y < kt_blank`. The kept pass's first break lands at 72/79/86% for a kept table vs 99%
  for its plain twin; the corrected estimate is within 2 points of the true fresh-page height on all six shapes.
  The `Tr` marker guard (kept in #60) and its dead `PAGEBREAKAVOIDCHECKED` row check are gone, as are the dead
  `kt_y00`/`kt_p00` fields. 38 existing snapshot docs byte-identical to `gravitypdf`. The /simplify efficiency review
  argued against a third page in the layout doc; the maintainer then asked for a snapshot, so a new doc
  `page-break-avoid-early-break` (fitting block moves whole, taller-than-a-page block split where it stands) was
  added. Sizing gotcha: a table kept together is *shrunk* into the space left on the page (`shrink_tables_to_fit`,
  1.4) before it moves, so a table only slightly too big for the remaining page never breaks early; the fitting
  table has to be more than 1.4x the space left. A sibling doc `page-break-avoid-early-break-no-autosize` sets `shrink_tables_to_fit = 1` (a
  subclass overriding `fittingRows()`/`configure()` hooks on the first doc). Check fixture layouts with `pdftotext`
  per page (form feeds); to build a fixture on the base source, `git checkout <base> -- src`, generate, then
  `git checkout HEAD -- src` (`git stash -- src` is a no-op once the fix is committed).
- Second /simplify pass (four commits on the PR): the blank is credited *after* `AddPage()`'s E/O/NEXT-* condition
  chain, not at entry (a no-op parity call credited a full strip; a NEXT-* call credited the same strip twice via its
  recursion). Snapshot pairs vary through value hooks (`fittingRows()`, `config()` returning the constructor array),
  like `OverWriteSnapshotTest::sourceCompressed()`; a void `configure()` hook was rejected as a second idiom.
- Still approximate (stated in the commit): forced breaks inside a kept block are credited as blank;
  a page changed without `AddPage` (float clearing) adds nothing; content that lays out taller on a fresh page is
  moved then split there; a second early break reaches page 3 and splits. The `== 1` gate could become `> 0` with
  full middle pages added to the sum if two early breaks in one block ever matter.
- Snapshot comparison worktrees must not `cp -R vendor`: the font packages under `vendor/mpdf/font-*` are relative
  symlinks into `packages/` and break when copied (core-font fallback, 15 KB docs, RTL doc fatals). Symlink
  `vendor` to the main tree's instead, and `require tests/bootstrap.php` in the build script. A worktree's
  `vendor/bin/phpunit` then autoloads the main tree's tests (class redeclared), so mutation checks are done by
  `git stash push -- src` in the main tree.
