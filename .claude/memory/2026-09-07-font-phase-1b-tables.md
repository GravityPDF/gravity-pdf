---
name: font-phase-1b-tables
description: Phase 1b of the 7.0 font rewrite — the three font tables, the repository, the 6.x migration, and the traps found building them
metadata:
  type: project
---

Phase 1b of `.claude/plans/2026-08-26-remove-core-font-installer.md` built 2026-09-07 on
`feature/remove-core-font-installer`. Font records move out of the `custom_fonts` key of the autoloaded
`gfpdf_settings` blob into `{prefix}gravitypdf_font` / `_font_file` (+ `_font_site` on multisite).

New: `src/Helper/Fonts/{Font_Schema,Font_Repository,Font_Migration,Font_Lock}.php`.
`Model_Custom_Fonts` is now a façade over `Font_Repository` with its 6.x array shape intact.

**Things that bit, and would bite again:**

- **The test bootstrap must create the tables before WP_UnitTestCase installs its filters.** Those filters turn
  `CREATE TABLE` into a TEMPORARY table, and a temporary table *shadows the real one for the connection* — so a
  mid-test `ensure_ready()` reads empty shadows and its `SHOW TABLES` verify misfires. `tools/phpunit/bootstrap.php`
  creates them for real and writes `gfpdf_db_version`, which makes `ensure_ready()` a no-op in every test.
  `GPDFAPI` is **not** available that early (it is wired after `muplugins_loaded`); pass a
  `\GFPDF_Vendor\Psr\Log\NullLogger` instead.
- **`ensure_ready()` must mark itself ready before running the migration.** The migration reads through `all()`,
  which calls `ensure_ready()` again. It does not deadlock — `all()` ignores the return value — but it runs a
  second pointless `dbDelta` inside the lock.
- **Order is rows-then-unlink, everywhere.** `Controller_Custom_Fonts` used to `@unlink` a face and *then* update
  the record. The shared delete path skips any path a surviving file row still records (two installs of one entry
  share files), so unlinking first means the guard sees the row and refuses. Both controller call sites now defer
  to `Model_Custom_Fonts`.
- **The plugin's `LoggerInterface` is `GFPDF_Vendor\Psr\Log\LoggerInterface`**, not the bare PSR one.
- **Three existing tests were asserting on the *store*, not the contract.** `Test_Model_Custom_Fonts` round-tripped
  an arbitrary `name` key (only possible because the 6.x store was a free-form array); `Test_Options_API` ×2 and
  `Test_PDF::test_register_custom_font_data_with_mPDF` seeded `custom_fonts` directly. Real behaviour change:
  **keys outside the documented 6.x shape no longer survive a round trip.**
- **A negative test that passes with the fix reverted pins nothing.** The first re-entrancy test did exactly that;
  checked by reverting, then the claim in both the test and the code comment was corrected rather than kept.

**`/simplify` pass (four agents) changed the shape in five places, all applied:** the legacy
`regular|bold|italics|bolditalics → R|B|I|BI` map and the "6.x path → file row" builder moved onto
`Font_Repository` (`LEGACY_FACE_ROLES`, `build_file_rows()`) — they were duplicated in `Font_Migration` and the
façade, and the migration is a one-time class the permanent façade should not depend on; `Font_Migration` lost its
`$font_dir` constructor argument with them. `Font_Schema::mark_current()` is now the one place that writes the
version stamp (both copies on multisite), used by `ensure_ready()` and the test bootstrap. `all()` memoises the
decoded rows against the change stamp, and passes the stamp into `apply_site_visibility()` instead of re-reading
it — a multisite `all()` was costing up to four object-cache round trips for the two the docblock promises.
`matches_custom_font_id()` and `matches_reserved_font_id()` now go through `Font_Repository::get()` /
`is_key_reserved()` rather than rebuilding the whole legacy-shaped list, or keeping a second reserved-key list that
could disagree with `RESERVED_KEYS`.

Deliberate deferrals, recorded in the plan: `Health_Issue` on a failed `dbDelta` → Phase 3; `gfpdf_health_report` /
`gfpdf_font_catalog_root` uninstall lines → the phases that create them; `Font_Schema::get_migrations()` is empty
because 7.0 is the create-from-scratch version.

Next: 1c (the `Package_*` layers, `Registry`, `Loose_Font_Importer`, deleting the render-time glob and the
installer nag). See [[font-core-font-installer-removal]].
