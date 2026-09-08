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

New: `src/Fonts/{Font_Source,Font_Sources,Catalog_Repository,Font_Downloader,Catalog_Sync}.php`,
`src/Controller/Controller_Font_Catalog.php`, the `gravitypdf_font_catalog` table in `Font_Schema`,
`GPDF_FONTS_URL` and `GPDF_TRUST_KEYS` in `pdf.php`, the `MocksHttpRequests` and `HasCatalogRows` test traits, and
the matching `Router::get_*()` accessors.

**`GPDF_TRUST_KEYS` ships as an empty array pending the real key** (see [[font-signing-keypair]] if that gets its
own note). While it is empty every sync of a built-in source fails with `font_no_trust_keys` — verification fails
closed and never degrades to origin trust, which a test pins. The keys are a constructor argument rather than read
from the constant inside `Catalog_Sync`, so the suite signs its own roots with a throwaway `sodium_crypto_sign_keypair()`
and exercises the real verification path.

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
- **A test helper named `entry()` fatals the entire PHPUnit run, silently** (hit twice in one session — read this
  before naming a helper). `HasGfpdfFixtures::entry( $key,
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

**Signature design points worth not re-deriving:**

- The context prefix (`gravitypdf/fonts/v1\n`) is prepended by the *verifier*, never carried in the signature file,
  so the same key can sign other artefacts later and a signature can never verify across purposes. Pinned by a test
  that signs the identical bytes under `gravitypdf/templates/v1\n` and expects a refusal.
- `generated` is a **monotonic floor kept per root**, in its own site option keyed by `md5( root_url )` — the
  per-source `gfpdf_font_catalog_root` record has no room for it and the root URL is the record's configuration,
  not state. A server-side rollback therefore means re-signing the old tree with a newer timestamp.
- The future check (7 days) exists so a forged root signed with a *leaked* key cannot shove the floor years forward
  and lock every legitimate root out afterwards.
- `REPLACE INTO` would be the natural-looking upsert and is wrong: it deletes and re-inserts, taking the status
  columns with it. `INSERT … ON DUPLICATE KEY UPDATE` naming index columns only is what keeps an in-flight install's
  phase. Neuter-tested.

**`adopt()` is built** (`Font_Repository::adopt( Catalog_Repository )`, called from `replace_source()` when the
replaced source carries coverage entries). It looks under `{fonts dir}/{source}/{entry}/` — where an install writes
and therefore where a hand-placed file must go. The 6.x installer's *flat* files are `Legacy_Font_Adopter`'s and are
rows before this runs; a test pins that a flat file is not what adoption looks at, so the two passes cannot compete
for the same bytes. `Catalog_Sync` takes the repository as a **callable**, because the two are built from each
other's direction and a constructor reference either way would be a cycle.

**Cross-checked against the update-server side (GravityPDF/gravitypdf-update-server#100, `feat/fonts-r2-store`) on
2026-09-09 — no discrepancies:**

- `files_base` is **omitted** by the publisher, exactly as §4.3 Hosting requires, and the checker's assertion 8
  exists to catch one appearing or naming a foreign host. `Catalog_Repository::url_for()` building
  `{root}/files/{remote_path}` from the *registered record's* root is correct and must stay that way.
- Key layout matches what `Catalog_Sync` requests: `v1/index.json`, `v1/index.json.sig` (base64 of a 64-byte
  signature), `v1/sources/{source}-{sha256}.json`, `v1/entries/{source}/{entry}-{sha256}.json`,
  `v1/files/{remote_path}`. The root is `{ schema, generated, sources: { id: { sha256, size } } }`.
- **The signing is not in that PR.** The publisher uploads an already-signed tree and the checker only asserts the
  `.sig` is 64 base64 bytes and no older than the index — it has no public key. So both halves of the signature
  contract live in *this* repo: `Catalog_Sync::SIGNATURE_CONTEXT` and the not-yet-built
  `tools/release/font-release.mjs`. They cannot drift through another repo, but nothing outside this one will catch
  it if they drift from each other.
- Both `fonts.gravitypdf.com` and `fonts-staging.gravitypdf.com` are already provisioned, so the 3a gate needs the
  real ~7,800-object publish run, not infrastructure.

**`Rest_Font_Sources` is built** — `GET /fonts/sources`, `GET /fonts/sources/{source}`,
`GET /fonts/sources/{source}/{entry}`, `POST /fonts/sources/sync` in `src/Rest/`. The install/delete entry routes
and `GET /fonts/status` are 3b/3c, since they need `Install_Queue`. Two things to keep true:

- **`entry_json` must never reach the wire.** `prepare_row()` unsets it and `data`; a browse would otherwise drag a
  LONGTEXT per card through the response, and the entry object is the installer's business.
- **`sync` being a reserved source id is load-bearing.** Dropping the unknown-source guard makes
  `GET /fonts/sources/sync` answer from the `{source}` route rather than 404 — a neuter caught exactly that, so the
  reservation is not decoration.

REST tests must mock HTTP for the sync route (`MocksHttpRequests`); the first version reached the real network,
which is flaky and slow. `Test_Rest` starts anonymous, so every test sets its own user.

Still to build in 3a: upgrade step 2, the shipped seed index, and `tools/release/font-release.mjs` (which must sign
with `Catalog_Sync::SIGNATURE_CONTEXT` — nothing outside this repo verifies the signature, so the two halves can
only be kept in step here). The **merge gate is not ours**: 3a does not
land until `npm run check:fonts staging` passes ten assertions against the staging bucket, which needs the
update-server repo's publisher and its secrets.
