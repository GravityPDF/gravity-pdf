# CLAUDE.md

## Core Principles

### Skills-First Workflow

**EVERY user request follows this sequence:**

Request → Load Skills → Gather Context → Execute

Skills contain critical workflows and protocols not in base context.
Loading them first prevents missing key instructions.

### Context Management Strategy

**Central AI should conserve context to extend pre-compaction capacity**:

- Delegate file explorations and low-lift tasks to sub-agents
- Reserve context for coordination, user communication, and strategic decisions
- For straightforward tasks with clear scope: skip heavy orchestration, execute directly

**Sub-agents should maximize context collection**:

- Sub-agent context windows are temporary
- After execution, unused capacity = wasted opportunity
- Instruct sub-agents to read all relevant files, load skills, and gather examples

### Routing Decision

**Direct Execution**:

- Simple/bounded task with clear scope
- Single-component changes
- Quick fixes and trivial modifications

**Sub-Agent Delegation**:

- Complex/multi-phase implementations
- Tasks requiring specialized domain expertise
- Work that benefits from isolated context

**Master Orchestrator**:

- Ambiguous requirements needing research
- Architectural decisions with wide impact
- Multi-day features requiring session management

### Operational Protocols

#### Agent Coordination

**Parallel** (REQUIRED when applicable):

- Multiple Task tool invocations in single message
- Independent tasks execute simultaneously
- Bash commands run in parallel

**Sequential** (ENFORCE for dependencies):

- Database → API → Frontend
- Research → Planning → Implementation
- Implementation → Testing → Security

#### Quality Self-Checks

Before finalizing code, verify:

- All inputs have validation
- Authentication/authorization checks exist
- All external calls have error handling
- Import paths verified against existing codebase examples

### Coding Best Practices

**Priority Order** (when trade-offs arise):
Correctness > Maintainability > Performance > Brevity

#### Task Complexity Assessment

Before starting, classify:

- **Trivial** (single file, obvious fix) → execute directly
- **Moderate** (2-5 files, clear scope) → brief planning then execute
- **Complex** (architectural impact, ambiguous requirements) → full research first

Match effort to complexity. Don't over-engineer trivial tasks or under-plan complex ones.

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

```bash
yarn test:php                           # Run PHPUnit in Docker (`yarn wp-env:integration start` required)
yarn test:php -- --filter TestClassName # Run single test class
yarn test:php -- --filter testMethod    # Run single test method
yarn test:php:multisite                 # Run multisite PHPUnit tests
composer lint                              # PHPCS check
composer lint:fix                          # PHPCS auto-fix
```

PHP tests run inside a Docker container via `wp-env` — you cannot run PHPUnit directly. Start the environment first with `yarn wp-env:integration start`.

### E2E Tests (Playwright)

```bash
yarn wp-env:e2e start && yarn wp-env:e2e:permalinks start   # Start dedicated E2E environment (port 8702 and 8703)
yarn test:e2e                                               # Run all Playwright tests (headless)
yarn test:e2e:debug                                         # Open Playwright UI for interactive debugging
```

E2E tests live in `tests/playwright/` and are split into two projects:
- `core/` — tests against the standard environment (port 8702, plain permalinks)
- `permalinks/` — tests against an environment with pretty permalinks (port 8703, started automatically)

Artifacts (screenshots, traces) are written to `tmp/artifacts/`.

### Environment

```bash
yarn wp-env start                 # Start dev environment (port 8700)
yarn wp-env:integration start     # Start PHP test environment (port 8701)
yarn wp-env:e2e start             # Start E2E test environment (port 8702)
yarn wp-env:e2e:permalinks start  # Start E2E test environment with pretty permalinks enabled (port 8703)
yarn wp-env stop                  # Stop the default dev environment
yarn start                        # Start dev environment + hot reload dev server
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
- **E2E tests (Playwright)**: `yarn test:e2e` — config at `tools/playwright/config.ts`. Use `yarn test:e2e:playwright` for the interactive UI mode.

### Key Constraints

- PRs must target the `development` branch (not `main`)
- Each PR should contain a single commit
- Minimum PHP 7.3 compatibility required
- jQuery is an external (provided by WordPress); never bundle it
- Run `composer prefix` after adding new Composer dependencies to namespace them via php-scoper
