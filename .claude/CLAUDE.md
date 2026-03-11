# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Gravity PDF is a WordPress plugin that generates PDF documents from Gravity Forms submissions. It has a PHP MVC backend and a React/Redux frontend for the admin UI.

## Commands

### JavaScript

```bash
yarn dev          # Start webpack dev server with hot reload
yarn build        # Production webpack build
yarn test:js      # Run Jest unit tests
yarn test:js -- tests/js-unit/react/sagas/fontManager.test.js  # Run single test file
yarn test:js -- --testNamePattern="test name"                   # Run single test by name
yarn lint:js      # ESLint check
yarn lint:css     # Sass/CSS lint check
yarn format       # Auto-fix JS/CSS/PHP formatting
```

### PHP

```bash
npm run test:php                           # Run PHPUnit in Docker (wp-env required)
npm run test:php -- --filter TestClassName # Run single test class
npm run test:php -- --filter testMethod    # Run single test method
npm run test:php:multisite                 # Run multisite PHPUnit tests
composer lint                              # PHPCS check
composer lint:fix                          # PHPCS auto-fix
```

PHP tests run inside a Docker container via `wp-env` — you cannot run PHPUnit directly. Start the environment first with `yarn wp-env start`.

### Environment

```bash
yarn wp-env start   # Start Docker WordPress dev environment
yarn wp-env stop    # Stop Docker environment
yarn start          # Start environment + hot reload dev server
```

## Architecture

### PHP Backend

The plugin follows an MVC pattern bootstrapped by the `Router` class in `src/bootstrap.php`, which acts as the dependency injection container. Entry point is `pdf.php` → `src/bootstrap.php`.

- **`src/Controller/`** — Request handlers (18+ controllers: forms, PDF generation, settings, fonts, templates, activation, etc.)
- **`src/Model/`** — Business logic (PDF rendering via mPDF, settings management, merge tags, templates)
- **`src/View/`** — Admin UI rendering; HTML templates live in `src/templates/`
- **`src/Helper/`** — 47+ helpers including abstract base classes for options, fields, forms, logging, and fonts
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
- **JS tests**: `tests/js-unit/` mirrors React source structure. Uses Jest + Enzyme. Coverage threshold: 75%.
- **E2E tests**: `tests/e2e/` uses TestCafe against a running WordPress instance.

### Key Constraints

- PRs must target the `development` branch (not `main`)
- Minimum PHP 7.3 compatibility required
- jQuery is an external (provided by WordPress); never bundle it
- Run `composer prefix` after adding new Composer dependencies to namespace them via php-scoper