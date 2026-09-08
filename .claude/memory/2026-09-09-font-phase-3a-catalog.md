---
name: font-phase-3a-catalog
description: Phase 3a of the 7.0 font rewrite — the catalog table, source registry and catalog repository; dbDelta's silent column skip, and the two open items the sync half still needs
metadata:
  type: project
---

Phase 3a of `.claude/plans/2026-08-26-remove-core-font-installer.md`, started 2026-09-09 on
`feature/font-catalog-sources`, stacked on [[font-phase-2-installer-removal]] (PR #1722) while that is open. The
**read half** of the data layer is built and inert — nothing syncs yet, so the table stays empty and no route reads
it. PHPUnit 1,753 single-site / 1,753 multisite, PHPCS and the 7.4 compatibility sniff clean.

New: `src/Fonts/{Font_Source,Font_Sources,Catalog_Repository,Font_Downloader}.php`, the `gravitypdf_font_catalog`
table in `Font_Schema`, `GPDF_FONTS_URL` in `pdf.php`, the `MocksHttpRequests` test trait, and
`Router::get_font_sources()` / `::get_catalog_repository()` / `::get_font_downloader()`.

**`Font_Downloader` splits across 3a and 3b**, resolving §5's assignment of the whole class to 3b (3a cannot sync
without it). Split by return type, not phase: 3a owns `fetch()`, the in-memory metadata read where the
non-negotiable rules live (`https` only, `sslverify` hard-coded `true`, `redirection => 0`, 5 s connect timeout, the
four-version User-Agent, byte ceiling applied before *and* after the request). 3b adds the streamed file half —
`.part` per attempt, `Accept-Encoding: identity`, disk-space check, attempts — so `MAX_FILE_BYTES` lands there.

**Things that bit, and would bite again:**

- **dbDelta drops a column line it cannot parse and says nothing.** The table is created, `get_missing_tables()` is
  empty, and every existing schema case stays green while the column is simply absent. This table introduces
  `position`, `always`, `size`, `files` and `error` — all of which read like keywords. All five create fine, but
  nothing in the suite would have said so, hence
  `Test_Font_Schema::test_every_declared_column_reaches_the_database()`: it parses each `CREATE TABLE`'s own column
  lines and compares them to `DESCRIBE`, 49 assertions over the three tables. Add a column, get it checked free.
- **`Font_Schema::VERSION` has to move even for a pure dbDelta change.** `is_current()` short-circuits `ensure()`
  before the missing-table check runs, so a site holding `7.0.0` from Phase 1b would never create the new table.
  Bumped to `7.0.1`. It is a schema version, not the plugin's.
- **A test helper named `entry()` fatals the entire PHPUnit run, silently.** `HasGfpdfFixtures::entry( $key,
  $index = 0 )` is inherited through `GFPDF\Tests\Integration\TestCase`, so declaring `entry( array $overrides = [] ):
  array` in a test class is an incompatible-signature fatal at class load. PHPUnit loads every test file before
  printing its banner, so one bad file kills runs filtered to unrelated classes, with no output and no PHP error log
  entry — just exit 255. See [[reference-wpenv-phpunit-255]]; `yarn wp-env … run` also swallows the output, so
  bisect with `docker exec`.
- **`Test_Bundled_Render` never ran on PHP 7.4.** It reaches the protected `Helper_PDF::begin_pdf()` through
  reflection without `setAccessible( true )`, which only became unnecessary in 8.1 — all ten cases errored on the
  7.4 job while passing on 8.3 and 8.5. A Phase 2 bug, found by CI rather than locally because the wp-env container
  is 8.5. Fixed on PR #1722 itself. Three other suites already guard on `PHP_VERSION_ID < 80100`; copy that.
- **The PHP floor is 7.4, not 7.3.** `.claude/CLAUDE.md` says 7.3; `tools/phpcs/config-php-compatibility.xml` sets
  `testVersion 7.4-` and says composer.json is the reason. No typed properties in the font package.

**Two deviations from §4.3, both recorded in the plan file:**

1. `Catalog_Repository::preview_urls()` takes an optional `fonts` map. The spec wants one preview face per file
   `fonts` names, but `search()` deliberately does not select `entry_json`, so a list row cannot know its roles. It
   uses the decoded map when the caller has one and falls back to Regular-only from the `font_keys` column when not.
2. `set_status()` takes a `$bump` flag rather than special-casing `missing_scripts`. The render path's union-only
   write passes `false`, so the one status writer does not grow a per-column cache policy.

**One open item before the sync half:**

- **`african` and `americas` pack labels are inferred.** §4.3's table never states them; `get_translations()`
  currently has "African scripts" and "Americas". A wrong guess degrades to the index's own English string rather
  than breaking, but `font-release.mjs` must publish whatever these settle on.

Still to build in 3a: `Catalog_Sync` (signed root, monotonic `generated`, `replace_source()`, the seed, the
`catalog_sync` lock), the `gfpdf_font_catalog_root` option record, `Font_Repository::adopt()`, `Rest_Font_Sources`,
upgrade step 2, `GPDF_TRUST_KEYS`, and `tools/release/font-release.mjs`. The **merge gate is not ours**: 3a does not
land until `npm run check:fonts staging` passes ten assertions against the staging bucket, which needs the
update-server repo's publisher and its secrets.
