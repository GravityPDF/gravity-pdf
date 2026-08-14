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

E2E tests live in `tests/playwright/` and run against a single wp-env instance on port 8702. Permalink mode is flipped per Playwright project group via `tools/playwright/global-setup.ts` (which shells out to `wp-env run cli wp option update permalink_structure`):
- `core` project — runs `core/*` and `permalinks/*` tests under plain permalinks, less whatever `core-isolated` claims.
- `core-isolated` project — specs whose fixtures change site-wide state the rest of the suite can observe (currently `core/system-status/*`, which raises an admin notice on every page the other snapshots are taken on). Depends on `core`, so nothing else is in flight while their state is live. `test.describe.configure({ mode: 'serial' })` only orders a single file and is not enough on its own.
- `core-with-permalinks` project — re-runs `permalinks/*` tests under `/%postname%/` permalinks. Depends on `core-isolated` so it runs serially after the plain-permalink pass completes.

Filters only narrow the projects you name: a project pulled in as a `dependency` runs in full regardless of `--grep` or a file filter. So a bare `yarn test:e2e -- <filter>` still runs everything, while `npx playwright test --config=tools/playwright/config.ts --project=core <filter>` runs just that slice plus `setup-core`. `--no-deps` isolates a project completely, but skipping `setup-core` leaves no storage state, so anything using `requestUtils` fails on auth.

In CI, Playwright is sharded 4-ways via `--shard=N/4` (see `.github/workflows/playwright-e2e.yml`); each shard runs all setup projects against its own wp-env instance and executes its slice of the consumer projects' tests.

Artifacts (screenshots, traces) are written to `tmp/artifacts/`.

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
