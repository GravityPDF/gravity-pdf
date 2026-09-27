## Working agreements

- Plans → `.claude/plans/YYYY-MM-DD-<topic>.md`
- Project memory → `.claude/memory/YYYY-MM-DD-<topic>.md` (also indexed in `.claude/memory/MEMORY.md`)

@.claude/memory/MEMORY.md

## Project Overview

Gravity PDF is a WordPress plugin that generates PDF documents from Gravity Forms submissions. It has a PHP MVC backend and a React/Redux frontend for the admin UI.

## Commands

### JavaScript

```bash
yarn dev              # Start webpack dev server with hot reload
yarn dev:build        # One-shot webpack build without watching
yarn build            # Production webpack build
yarn test:js          # Run Jest unit tests
yarn test:js:watch    # Run Jest in watch mode
yarn test:js -- tests/js-unit/react/sagas/fontManager.test.js  # Run single test file
yarn test:js -- --testNamePattern="test name"                   # Run single test by name
yarn lint:js          # ESLint check
yarn lint:js --fix    # Auto-fix ESLint errors (e.g. JSDoc alignment)
yarn lint:css         # Sass/CSS lint check
yarn format           # Auto-fix JS/CSS/PHP formatting
```

### PHP

PHP tests run inside a Docker container via `wp-env` — you cannot run PHPUnit directly. Start the environment first with `yarn wp-env:integration start`.

```bash
yarn test:php                           # Run PHPUnit in Docker
yarn test:php -- --filter TestClassName # Run single test class
yarn test:php -- --filter testMethod    # Run single test method
yarn test:php:multisite                 # Run multisite PHPUnit tests
composer lint                           # PHPCS check
composer lint:fix                       # PHPCS auto-fix
```

### E2E Tests (Playwright)

```bash
yarn wp-env:e2e start    # Start dedicated E2E environment (port 8702)
yarn test:e2e            # Run all Playwright tests (headless)
yarn test:e2e:debug      # Open Playwright UI for interactive debugging
```

E2E tests live in `tests/playwright/` and run against a single wp-env instance on port 8702, in one Playwright invocation with every test in parallel (`fullyParallel`, 4 workers). The `setup-core` project (`tools/playwright/global-setup.ts`) logs in and puts the site on `/%postname%/` permalinks, then:
- `core` project — runs `core/*` and `permalinks/*` tests, sending `X-GPDF-E2E-Permalinks: plain` so its requests see plain permalinks.
- `core-with-permalinks` project — re-runs `permalinks/*` tests on the site's pretty permalinks.

Tests share the site, so they must not change what the others can see. Seed with unique names (`createForm()` suffixes titles), and scope site-wide state to a test's own requests with the `X-GPDF-E2E-*` headers that `tools/mu-plugins/gravitypdf.php` reads (`page.setExtraHTTPHeaders()`), rather than saving a global setting: Debug Mode and the deprecation notices work this way. `test.describe.configure({ mode: 'serial' })` only orders a single file.

Seed what a test isn't about over REST rather than through the admin UI: `pdf.addPdf(formId, name, settings)` posts to the plugin's own `/gravity-pdf/v1/form/{id}` endpoint (which fills in the defaults), and `pdf.updateForm(formId, withConfirmation({...}))` sets the default confirmation. Keep the UI where it's under test, or where saving it runs plugin code (a redirect confirmation's shortcode gains `entry`/`raw` as it saves). Specs that neither depend on permalinks nor can share the site with a copy of themselves (e.g. `core/managers/template-manager.spec.ts`, which uploads a fixed template over admin-ajax) belong under `core/`, not `permalinks/`.

In CI, Playwright is sharded 4-ways via `yarn test:e2e -- --shard=N/4` (see the `e2e-playwright` job in `.github/workflows/tests.yml`), each shard against its own wp-env instance. CI records a trace on the first retry rather than on every test (the trace alone was a third of a short test's run time); locally a trace is kept for any failure. `--no-deps` skips the setup project, which leaves no storage state, so anything using `requestUtils` fails on auth.

Artifacts (screenshots, traces) are written to `tmp/artifacts/`.

#### Visual regression (Lost Pixel)

`snapshot(page, testinfo, targets)` in `tools/playwright/utils/snapshot.ts` waits for the page to settle, then writes a PNG of just the area the `targets` locators cover (the section under test, not the whole page) to `tmp/visual/current/`, named `<project>-<spec>-<test title>.png`. Capture is on in CI and off locally; set `VISUAL=1` or `VISUAL=0` to override. In CI each shard uploads its shots, and the `visual-regression` job runs `yarn visual:compare`, which diffs them against the baselines committed in `tests/playwright/visual-baselines/`. The job fails on more than 1% drift or on a shot with no baseline, and uploads the diffs as `visual-regression-diffs`.

Baselines only hold on the Linux CI runner, because macOS anti-aliasing differs, so never commit PNGs captured locally. To accept a visual change or seed new shots, add the `update-visual-baselines` label to the PR alongside `run-tests`. The `visual-regression` job then replaces the baselines with that run's shots, reverts any that changed only by capture noise (`yarn visual:prune`) and pushes a `[skip ci]` commit to the branch. After that, remove the label and re-run the workflow to confirm the compare is green.

### Environment

```bash
yarn wp-env start                 # Dev environment (port 8700)
yarn wp-env:integration start     # PHP test environment (port 8701)
yarn wp-env:e2e start             # E2E test environment (port 8702)
yarn wp-env stop                  # Stop the default dev environment
yarn start                        # Dev environment + hot reload dev server
```

## Architecture

### PHP Backend

The plugin follows an MVC pattern bootstrapped by the `Router` class in `src/bootstrap.php`, which acts as the dependency injection container. Entry point is `pdf.php` → `src/bootstrap.php`.

- **`src/Controller/`** — Request handlers (forms, PDF generation, settings, fonts, templates, activation, etc.)
- **`src/Model/`** — Business logic (PDF rendering via mPDF, settings management, merge tags, templates)
- **`src/View/`** — Admin UI rendering; HTML templates live in `src/templates/`
- **`src/Helper/`** — Abstract base classes for options, fields, forms, logging, and fonts
- **`vendor_prefixed/`** — Composer dependencies namespaced via `php-scoper` to avoid conflicts with other plugins

Namespacing: all plugin code is under the `GFPDF\` namespace with PSR-4 autoloading.

### JavaScript Frontend

Three webpack bundles built from distinct entry points:

| Bundle | Entry | Purpose |
|--------|-------|---------|
| `app.bundle.min.js` | `src/assets/js/react/gfpdf-main.js` | React app: font manager, template manager, core fonts UI |
| `gfpdf-entries.min.js` | `src/assets/js/legacy/gfpdf-entries.js` | Legacy jQuery entry page UI |
| `admin.min.js` | `src/assets/js/admin/bootstrap.js` | Admin settings page handlers |

The React app uses Redux for state with Redux-Saga for all async side effects. Each feature area has its own reducers, sagas, actions, and API module under `src/assets/js/react/`.

Legacy jQuery code coexists with the React app; they are separate bundles and do not share state.

### Data Flow

- **PDF generation**: Form submission → WordPress hooks → `Model_PDF` → mPDF → output file
- **Admin settings**: Options stored in WP options table → REST API → React/Redux store → UI
- **Sagas**: React components dispatch actions → sagas intercept → call `src/assets/js/react/api/` modules → WordPress REST endpoints → update Redux store

### Testing

- **PHP tests**: `tests/phpunit/` mirrors `src/` structure. Extends `WP_UnitTestCase`. Mock data in `tests/phpunit/unit-tests/Mocks/`.
- **JS tests**: `tests/js-unit/` mirrors React source structure. Uses Jest + Enzyme. Coverage threshold: 75% (branches/functions/lines/statements).
- **E2E tests (Playwright)**: `yarn test:e2e` — config at `tools/playwright/config.ts`. Use `yarn test:e2e:debug` for the interactive UI mode.

## Key Constraints

- PRs must target the `development` branch (not `main`)
- Each PR should contain a single commit
- Minimum PHP 7.3 compatibility required
- jQuery is an external (provided by WordPress); never bundle it
- Run `composer prefix` after adding new Composer dependencies to namespace them via php-scoper
