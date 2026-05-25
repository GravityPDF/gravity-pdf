# PHPUnit Test Suite Baseline

Captured: 2026-05-25 (Phase 0 of [`.claude/plans/2026-05-25-phpunit-tests-refactor.md`](../../.claude/plans/2026-05-25-phpunit-tests-refactor.md)).

This file is the reference point every later phase compares against. Treat the numbers below as the line that must not regress (test count, coverage), and the runtime as the budget later phases should match within ±10%.

## Runtime baseline (live wp-env:integration, no xdebug, no coverage)

| Metric | Value |
| --- | --- |
| Test count | **1119** |
| Assertions | **4088** |
| Skipped | 8 (multisite-only tests, expected) |
| Sum of test-case times | **38.46s** |
| Wall-clock (incl. PHPUnit boot) | **41.69s** |
| Wrapper-inclusive (yarn + docker exec) | 42.63s |

Slowest 15 tests (live JUnit, `tmp/junit/phpunit-integration.xml`):

| Time | Test |
| ---: | :--- |
| 4.032s | `Test_PDF_Ajax::test_ajax_process_uploaded_template` |
| 3.570s | `Test_PDF_Ajax::test_render_template_fields` |
| 3.428s | `Test_PDF_Ajax::test_delete_gf_pdf_setting` |
| 3.161s | `Test_PDF_Ajax::test_ajax_process_license_deactivation` |
| 3.153s | `Test_PDF_Ajax::test_ajax_process_build_template_options_html` |
| 3.125s | `Test_PDF_Ajax::test_duplicate_gf_pdf_settings` |
| 3.120s | `Test_PDF_Ajax::test_ajax_save_core_font` |
| 3.098s | `Test_PDF_Ajax::test_ajax_process_delete_template` |
| 1.366s | `Test_EDD_SL_Plugin_Updater::test_check_update_already_exists` |
| 1.192s | `Test_Request::test_send_request_status_error` |
| 1.008s | `Test_Url_Signer::test_expiration_failure` (intentional `sleep(1)`) |
| 0.766s | `Test_Request::test_send_request_success` |
| 0.641s | `Test_Slow_PDF_Processes::test_process_legacy_pdf_endpoint` |
| 0.496s | `Test_Slow_PDF_Processes::test_process_pdf_endpoint` |
| 0.455s | `Test_Slow_PDF_Processes::test_generate_and_save_pdf` |

**Headline finding**: the 9 `Test_PDF_Ajax` tests account for **26.70s — 69% of total suite runtime**. Phase 2's split of `test-ajax.php` into focused `*_Ajax.php` files (one per target Model) is the highest-leverage perf work in the refactor.

## Per-namespace runtime breakdown

| Namespace | Time | Tests | Avg/test |
| :--- | ---: | ---: | ---: |
| `GFPDF\Tests\` (root `test-*.php`) | 33.58s | 753 | 44.6ms |
| `GFPDF\Helper\` | 3.90s | 102 | 38.2ms |
| `GFPDF\Controller\` | 0.81s | 31 | 26.0ms |
| `GFPDF\Model\` | 0.15s | 138 | 1.1ms |
| `GFPDF\Statics\` | 0.03s | 90 | 0.3ms |
| `GFPDF\View\` | 0.00s | 5 | 0.3ms |

`Helper/` subdivision:

| Sub-namespace | Time | Tests |
| :--- | ---: | ---: |
| `Helper/Mpdf` | 2.13s | 8 |
| `Helper/Licensing` | 1.42s | 19 |
| `Helper/Fields` | 0.33s | 49 |
| `Helper/Log` | 0.01s | 20 |
| `Helper/(top-level)` | 0.01s | 5 |
| `Helper/Fonts` | 0.00s | 1 |

Heaviest cross-cutting files (root `test-*.php`, sorted by total time):

| File | Time | Tests |
| :--- | ---: | ---: |
| `Test_PDF_Ajax` | 26.70s | 9 |
| `Test_Slow_PDF_Processes` | 3.76s | 18 |
| `Test_Url_Signer` | 1.02s | 23 |
| `Test_Rest_Form_Settings` | 0.64s | 31 |
| `Test_PDF` | 0.24s | 75 |
| `Test_Options_API` | 0.11s | 81 |
| `Test_Helper_Misc` | 0.03s | 73 |

(Full file-level breakdown reproducible from `tmp/junit/phpunit-integration.xml` via the methodology section below.)

## Multisite runtime baseline

Source: `tmp/junit/phpunit-multisite.xml`, captured via `yarn test:php:multisite`.

| Metric | Value |
| --- | --- |
| Test count | **1119** (same surface, different bootstrap) |
| Assertions | 4119 |
| Skipped | 1 (non-multisite-only test, expected) |
| Sum of test-case times | **38.64s** |
| Wall-clock | 41.81s |

## Coverage baseline (live wp-env:integration, xdebug coverage mode, PHP 8.5)

Source: `tmp/coverage/report-xml/baseline.xml` (Clover format, 845 KB, 208 files).

| `src/` subdirectory | Files | Statements covered / total | Line coverage | Phase 4 priority |
| :--- | ---: | :--- | ---: | :--- |
| `src/Rest/` | 2 | 546 / 588 | **92.86%** | Already strong — no new work |
| `src/Helper/` (top level) | 39 | 3368 / 4026 | **83.66%** | Mixed — abstracts critical, fill targeted gaps |
| `src/Statics/` | 4 | 287 / 347 | **82.71%** | Fill `Debug.php`, `Queue_Callbacks.php` |
| `src/Helper/Fonts/` | 5 | 31 / 38 | **81.58%** | Already strong |
| `src/Controller/` | 19 | 896 / 1100 | **81.45%** | High — 11 of 19 still without dedicated tests |
| `src/Model/` | 11 | 1938 / 2385 | **81.26%** | High — `Model_PDF` characterization (per plan §"Critical-class characterization tests") |
| `src/Helper/Mpdf/` | 3 | 33 / 44 | **75.00%** | Low — small surface |
| `src/Helper/Fields/` | 60 | 1296 / 1784 | **72.65%** | High — sparse coverage across ~60 field handlers |
| `src/Helper/Log/` | 3 | 118 / 170 | **69.41%** | Medium |
| `src/View/` | 35 | 646 / 1052 | **61.41%** | Out of scope — mostly HTML partials |
| `src/Helper/Licensing/` | 1 | 168 / 298 | **56.38%** | Medium — large untested branches |
| `src/` root (`bootstrap.php`, `autoload.php`, `deprecated.php`) | 3 | 290 / 557 | **52.06%** | Low — bootstrap has activation paths hard to cover |
| `src/templates/` | 9 | 97 / 307 | **31.60%** | Out of scope — PDF templates, not code-under-test |
| `src/Exceptions/` | 11 | 5 / 22 | **22.73%** | Per-plan single hierarchy smoke test |
| Plugin root (`pdf.php`, `api.php`, `gravity-pdf-updater.php`) | 3 | 209 / 289 | **72.32%** | Mixed |
| **OVERALL** | **208** | **9928 / 13007** | **76.33%** | — |

The **76.33%** overall was the CI gate enforced by `tools/phpunit/coverage-gate.php` at the end of Phase 0. See the Phase 4 revision below for the current floor.

Coverage runtime overhead is modest in xdebug `coverage` mode: 47s (vs 38s without) — only ~24% slower.

> **Important methodology note** — `yarn test:php --coverage-clover=...` consistently fails on this codebase with `RecursiveDirectoryIterator::__construct(.../src/templates): Failed to open directory` when invoked through the yarn wrapper, even when `src/templates/` exists and is readable. **Invoking `vendor/bin/phpunit` directly inside the container works.** Suspected cause is a working-directory resolution quirk in PHPUnit 9.6 + Xdebug 3 coverage when the config path is absolute. Phase 4 switched `.github/workflows/phpunit.tests.yml` to the direct-phpunit form and changed the coverage cell's wp-env startup from `--xdebug=debug` (no-op for coverage) to `--xdebug=coverage`.

## Phase 4 revision (2026-05-25)

After Phase 4 closed the bulk of the coverage gap — characterization tests for Exceptions, Statics, 11 untested controllers, 5 model gaps, 49 helpers (including all `Helper/Fields/`), Views, and the `Helper/Log` + `Helper/Mpdf` mirror work — re-running the same methodology yields:

| Metric | Phase 0 | Phase 4 | Δ |
| --- | ---: | ---: | ---: |
| Test count | 1119 | **1424** | +305 |
| Assertions | 4088 | **21314** | +17226 |
| Wall-clock (coverage mode) | 47s | **42s** | −5s |
| Wall-clock (no coverage) | 41.7s | **31.7s** | −10s |

| `src/` subdirectory | Files | Stmts covered / total | Line coverage | Δ vs Phase 0 |
| :--- | ---: | :--- | ---: | ---: |
| `src/Statics/` | 4 | 327 / 347 | **94.24%** | +11.53 pp |
| `src/Helper/Mpdf/` | 3 | 41 / 44 | **93.18%** | +18.18 pp |
| `src/Rest/` | 2 | 546 / 588 | **92.86%** | 0 |
| `src/Helper/Fields/` | 60 | 1541 / 1783 | **86.43%** | +13.78 pp |
| `src/Controller/` | 19 | 937 / 1100 | **85.18%** | +3.73 pp |
| `src/Helper/` (top level) | 39 | 3386 / 4026 | **84.10%** | +0.44 pp |
| `src/Model/` | 11 | 1975 / 2385 | **82.81%** | +1.55 pp |
| `src/Helper/Fonts/` | 5 | 31 / 38 | **81.58%** | 0 |
| Plugin root | 3 | 209 / 289 | **72.32%** | 0 |
| `src/Helper/Log/` | 3 | 118 / 170 | **69.41%** | 0 |
| `src/View/` | 35 | 686 / 1054 | **65.09%** | +3.68 pp |
| `src/Helper/Licensing/` | 1 | 168 / 298 | **56.38%** | 0 |
| `src/` root | 3 | 290 / 557 | **52.06%** | 0 |
| `src/Exceptions/` | 11 | 11 / 22 | **50.00%** | +27.27 pp |
| `src/templates/` | 9 | 97 / 307 | **31.60%** | 0 |
| **OVERALL** | **208** | **10363 / 13008** | **79.67%** | **+3.34 pp** |

The CI gate in `tools/phpunit/coverage-gate.php` was ratcheted to **79.67%** at the close of Phase 4. The follow-up pass below raised it to **80.06%**.

## Phase 4 follow-up (2026-05-25)

Critical-class gap-fill pass on `Helper/Licensing/EDD_SL_Plugin_Updater`, `Helper_Abstract_Addon`, and `Model_PDF`:

| Metric | Phase 4 | Follow-up | Δ |
| --- | ---: | ---: | ---: |
| Test count | 1424 | **1471** | +47 |
| Assertions | 21314 | **21970** | +656 |
| Wall-clock (coverage mode) | 42s | 55s | +13s |
| Wall-clock (no coverage) | 31.7s | 38.2s | +6.5s |

Coverage-mode wall-clock grew because the new tests exercise hot paths under xdebug instrumentation; the no-coverage delta is the truer signal of suite weight.

| `src/` subdirectory | Files | Stmts covered / total | Line coverage | Δ vs Phase 4 |
| :--- | ---: | :--- | ---: | ---: |
| `src/Helper/Licensing/` | 1 | 176 / 298 | **59.06%** | +2.68 pp |
| `src/Helper/` (top level) | 39 | 3428 / 4026 | **85.15%** | +1.05 pp |
| `src/Model/` | 11 | 1976 / 2385 | **82.85%** | +0.04 pp |
| **OVERALL** | **208** | **10414 / 13008** | **80.06%** | **+0.39 pp** |

All other subdirectories unchanged.

The CI gate was ratcheted to **80.06%** at the close of the follow-up. See follow-up #2 below for a recalibration to **79.95%** after observing run-to-run xdebug variance.

Remaining gaps after the follow-up pass:

- **Helper/Licensing** (40.9 pp gap, 122 statements) — remaining uncovered code is `get_version_from_remote` failure modes that require deeper mocking, and `show_changelog`'s `install_plugin_information()` path (calls `exit;`).
- **Model_PDF** still has ~17% uncovered, mostly in seldom-exercised paths like `get_quiz_results`/`get_poll_results`/`get_survey_results` add-on integration branches.
- **Helper/Log** (30.6 pp gap, 52 statements) — `Log/Logger::get_monolog()` has the PSR-Log v2/v3 detection branches; `MonoLoggerPsrLog2And3` itself cannot be exercised at runtime in this test env (see commit `b2cce9ed`).
- **src/ root** (47.9 pp gap, 267 statements) — `bootstrap.php` activation paths are genuinely hard to characterize without rewriting the bootstrap as a class.
- **View** (34.9 pp gap, 368 statements) — most remaining uncovered Views are HTML-partial paths; out of scope per the plan.
- **Exceptions** (50.0 pp gap, 11 statements) — the hierarchy test pins inheritance for all subclasses but doesn't construct each one. Tiny absolute gap; not worth a dedicated pass.

## Phase 4 follow-up #2 (2026-05-25)

Second gap-fill pass on `Helper/Licensing/EDD_SL_Plugin_Updater` — 8 new tests covering the `show_changelog` permission-denial `wp_die` path, `get_version_from_remote` WP_Error branch, `request_recently_failed` non-numeric value branch, direct `log_failed_request`, the explicit-cache-key paths in `get_cached_version_info`/`set_version_info_cache`/`delete_version_info_cache`, and `get_repo_api_data`'s cached-return branch.

| Metric | Follow-up | Follow-up #2 | Δ |
| --- | ---: | ---: | ---: |
| Test count | 1471 | **1479** | +8 |
| Assertions | 21970 | **22078** | +108 |

| `src/` subdirectory | Files | Stmts covered / total | Line coverage | Δ vs Follow-up |
| :--- | ---: | :--- | ---: | ---: |
| `src/Helper/Licensing/` | 1 | 179 / 298 | **60.07%** | +1.01 pp |
| **OVERALL** | **208** | **10406 / 13008** | **80.00%** | -0.06 pp (see note) |

**Note on the overall delta:** the previous follow-up's 80.06% measurement was at the high end of natural xdebug coverage variance. Re-measuring the same source/tests pre-this-change yields **79.97%** (3 statement drift in Helper top-level + Statics), and three runs with the new tests applied land at 80.00–80.01%. The Helper/Licensing **+3 statements / +1.01 pp** is reproducible; the overall floor sits ~0.05 pp below the previously reported figure once natural variance is accounted for.

The CI gate in `tools/phpunit/coverage-gate.php` is recalibrated to **79.95%** — a ~0.05 pp safety margin below the worst observed run with the new tests included, still well above the Phase 4 baseline of 79.67%. See follow-up #3 below for a further ratchet to **80.25%** after a third gap-fill pass.

## Phase 4 follow-up #3 (2026-05-25)

Third gap-fill pass targeting the remaining items on the follow-up #2 punch list: `Model_PDF` quiz/poll/survey add-on integration branches, `Helper/Log/Logger` `setup_gravityforms_logging` early-return + ERROR-level branches, and `src/bootstrap.php` plugin-meta/admin-message/asset-registration paths.

| Metric | Follow-up #2 | Follow-up #3 | Δ |
| --- | ---: | ---: | ---: |
| Test count | 1479 | **1500** | +21 |
| Assertions | 22078 | **22368** | +290 |

| `src/` subdirectory | Files | Stmts covered / total | Line coverage | Δ vs Follow-up #2 |
| :--- | ---: | :--- | ---: | ---: |
| `src/` root | 3 | 336 / 557 | **60.32%** | +8.26 pp |
| `src/Model/` | 11 | 1977 / 2385 | **82.89%** | +0.04 pp |
| `src/Helper/Log/` | 3 | 119 / 170 | **70.00%** | +0.59 pp |
| **OVERALL** | **208** | **10456 / 13008** | **80.38%** | **+0.38 pp** |

The biggest single gain is `src/bootstrap.php` (+46 statements): tests for `plugin_action_links`, `plugin_row_meta`, `add_body_class`, `tinymce_styles`, `register_assets`, `get_config_data`, and `add_admin_messages` exercise paths previously reachable only via real WordPress page loads.

`Model_PDF` and `Helper/Log/Logger` gains are smaller in absolute terms because their remaining uncovered branches require third-party dependencies (Gravity Forms add-on data sources, PSR-Log v2/v3 libraries) that aren't loaded in this test bootstrap — see the standing note about `MonoLoggerPsrLog2And3` (commit `b2cce9ed`).

Two consecutive coverage runs land at 80.37–80.38% (2-statement drift in `Statics/`). The CI gate is ratcheted to **80.25%** — ~0.10 pp safety margin below the worst observed run, still well above the previous floor of 79.95%. See follow-up #4 below for a methodology fix that surfaces an additional ~1.20 pp of coverage that was being measured but discarded.

## Phase 4 follow-up #4 (2026-05-25) — measurement methodology

The Phase 0–follow-up #3 baselines all measured **single-site PHPUnit coverage only**, even though the project ships a separate multisite PHPUnit suite (`tools/phpunit/config-multisite.xml`) that the integration job also runs. Tests that guard with `markTestSkipped( ! is_multisite() )` (the 7 `test_show_update_notification_*` cases, the multisite-only branch of `is_non_active_multisite`, and the `set_version_info_cache` multisite skip) execute under the multisite suite but never contributed to the coverage-gate measurement.

Resolution:

1. `tools/phpunit/coverage-gate.php` and `tools/phpunit/coverage-baseline.php` now accept multiple Clover paths and **union per-line counts** (a line is "covered" if any input has `count > 0`). Helper extracted to `tools/phpunit/coverage-merge-lib.php`.
2. `.github/workflows/phpunit.tests.yml` runs `phpunit -c tools/phpunit/config-multisite.xml --coverage-clover=...` alongside the single-site coverage step and passes both clovers to the gate.
3. Local reproduction: see the updated methodology section below.

| `src/` subdirectory | Single-site only | Single + multisite (union) | Δ |
| :--- | ---: | ---: | ---: |
| `Helper/Licensing/` | 60.07% | **96.31%** | +36.24 pp |
| `Model/` | 82.89% | **84.07%** | +1.18 pp |
| `Helper/` (top level) | 84.92% | **85.17%** | +0.25 pp |
| `Statics/` | 93.66% | **94.52%** | +0.86 pp |
| `Helper/Log/` | 70.00% | **73.91%** | +3.91 pp |
| `Controller/` | 85.18% | **85.27%** | +0.09 pp |
| `Helper/Mpdf/` | 93.18% | 93.18% | 0 |
| `Helper/Fields/` | 86.43% | 86.43% | 0 |
| `Helper/Fonts/` | 81.58% | 81.58% | 0 |
| **OVERALL** | 80.37% | **81.57%** | **+1.20 pp** |

The bulk of the gain is `show_update_notification` in `EDD_SL_Plugin_Updater` — 7 multisite-only tests that were always pinning behavior, just not measured.

Remaining genuine `Helper/Licensing` gaps after the union (11 statements):

- `check_update` non-object-input branch (`L80`) — easy add later.
- `show_changelog` happy path (`L495–L510`) — calls `install_plugin_information()` → `exit;`, untestable without process forking.

The CI gate is ratcheted to **81.45%** — ~0.10 pp safety margin below the merged measurement, well above the previous floor of 80.25%.

**Statement-total drift note:** the union baseline reports **12 998** total statements vs single-site's **13 008**. The merge script uses the first input's `<file><line type="stmt">` set as the canonical statement list; lines that xdebug instruments as statements in one bootstrap but as different element types in the other contribute small drift. Worth knowing when comparing absolute statement counts across follow-ups; the ratios are stable.

## Playwright (e2e) baseline

| Metric | Value (local 2026-05-25, post `yarn build` + `composer install`) |
| --- | --- |
| Passed | 87 |
| Failed | 0 |
| Did not run | 0 |
| Wall-clock | 2.2m (135s) |

Local run is fully green. The earlier captured run (6/60/21 in 8.4m) failed because `dist/` and `vendor/` were not built — Playwright requires both before the plugin will boot.

For runtime regression detection prefer the sharded GitHub Actions Playwright workflow (`.github/workflows/playwright-e2e.yml`, 4-way `--shard`) — local wall-clock varies by machine. Phase 2 changes touch fixture bootstrap; before merging Phase 2 verify Playwright remains green in CI.

## Methodology — how to reproduce

The numbers above are captured from a **live** wp-env run, not from `.phpunit.result.cache`. The cache file is stale almost the moment it lands; for regression detection, always re-run.

Boot the environments once per session:

```bash
yarn wp-env:integration start    # port 8701, used by yarn test:php
yarn wp-env:e2e start            # port 8702, used by yarn test:e2e
```

### Runtime + JUnit

```bash
yarn test:php \
  --do-not-cache-result \
  --verbose \
  --log-junit=/var/www/html/wp-content/plugins/gravity-pdf/tmp/junit/phpunit-integration.xml
```

The Docker container mounts the plugin directory, so `tmp/junit/phpunit-integration.xml` appears on the host. Parse it with `xml.etree.ElementTree` to recompute the per-namespace and slowest-test tables above.

### Multisite

```bash
yarn test:php:multisite \
  --verbose \
  --log-junit=/var/www/html/wp-content/plugins/gravity-pdf/tmp/junit/phpunit-multisite.xml
```

### Coverage (requires xdebug `coverage` mode — restart wp-env first)

```bash
# Note: --xdebug=debug is NOT enough; coverage needs xdebug.mode=coverage.
yarn wp-env:integration start --xdebug=coverage

# The yarn wrapper produces a "RecursiveDirectoryIterator on src/templates"
# failure under xdebug 3 coverage — invoke phpunit directly. Run both
# single-site and multisite under coverage to capture multisite-only tests.
yarn wp-env:integration run wordpress bash -c '
  cd /var/www/html/wp-content/plugins/gravity-pdf &&
  vendor/bin/phpunit \
    -c tools/phpunit/config.xml \
    --do-not-cache-result \
    --coverage-clover=tmp/coverage/report-xml/baseline.xml \
    --log-junit=tmp/junit/phpunit-coverage.xml &&
  vendor/bin/phpunit \
    -c tools/phpunit/config-multisite.xml \
    --do-not-cache-result \
    --coverage-clover=tmp/coverage/report-xml/multisite.xml \
    --log-junit=tmp/junit/phpunit-coverage-ms.xml
'

# Pass both clovers to the gate + breakdown — they union per-line.
php tools/phpunit/coverage-gate.php \
  tmp/coverage/report-xml/baseline.xml \
  tmp/coverage/report-xml/multisite.xml

php tools/phpunit/coverage-baseline.php \
  tmp/coverage/report-xml/baseline.xml \
  tmp/coverage/report-xml/multisite.xml
```

Per-`src/`-subdir coverage is extracted from the Clover XML — every `<file>` element has a `name` attribute (the full absolute path, despite the attribute name) and a `<metrics>` child with `statements`/`coveredstatements`/`elements`/`coveredelements`. Group by the first path segment under `src/`, with `Helper/<sub>` broken out one level deeper. Run `php tools/phpunit/coverage-baseline.php` to regenerate the per-subdir table above from the Clover XML.

### Playwright

```bash
yarn test:e2e
```

### Comparing a future run against this baseline

After running any of the above, diff the new JUnit/clover against the artifacts uploaded by CI (workflow `.github/workflows/phpunit.tests.yml`, artifacts `phpunit-junit-php8.3` and `phpunit-coverage-clover`). A regression is:

- Runtime: more than +10% on the sum of test-case times.
- Test count: any decrease (a moved test is still a test).
- Coverage: any decrease in overall line coverage, or any decrease in per-`src/`-subdir coverage by more than 1 percentage point.

If a phase intentionally trades runtime for clarity (e.g., splitting `test-ajax.php` adds per-class setup overhead), record the new baseline here in a "phase N revision" section rather than relaxing the gate.
