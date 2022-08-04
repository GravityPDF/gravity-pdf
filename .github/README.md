Gravity PDF
==========================

[![codecov](https://codecov.io/gh/GravityPDF/gravity-pdf/branch/development/graph/badge.svg)](https://codecov.io/gh/GravityPDF/gravity-pdf)

Gravity PDF is a GPLv2-licensed WordPress plugin that allows you to automatically generate, email and download PDF documents using the popular form-builder plugin, [Gravity Forms](https://rocketgenius.pxf.io/c/1211356/445235/7938) (affiliate link). Find out more about Gravity PDF at [https://gravitypdf.com](https://gravitypdf.com/).

# About

This Git repository is for developers who want to contribute to Gravity PDF. **Don't use it in production**. For production use, [download Gravity PDF from the WordPress Plugin Directory](https://wordpress.org/plugins/gravity-forms-pdf-extended/).

The `development` branch is considered our bleeding edge branch, with all new changes pushed to it. The `master` branch is our latest stable version of Gravity PDF.

# Installation

## Prerequisites

* [Git](https://git-scm.com/)
* [Yarn](https://yarnpkg.com/en/docs/install)
* Docker Desktop ([Windows](https://docs.docker.com/docker-for-windows/install/) or [Mac](https://docs.docker.com/docker-for-mac/install/)) / [Docker Toolbox](https://docs.docker.com/toolbox/) or [Composer](https://getcomposer.org/)

## Setup for Docker 

The Docker setup will create a fully functional development environment preconfigured for Gravity PDF. 

1. Clone the repository using `git clone https://github.com/GravityPDF/gravity-pdf/` from the terminal
2. Copy and rename `.env.example` to `.env`, then replace `00000000000000000000000000000000` with a valid Gravity Forms license key 
3. Run `yarn && yarn build:production`
4. Start Docker and then run `yarn env:install` to setup the local development environment
5. Access a local development site at `http://localhost:8888` with the login `admin` and `password`.

If you shut down Docker and want to fire up the environment later, use `yarn wp-env start`. You can reset the database back to its original state with `yarn wp-env clean all`. When all else fails, delete everything and start again with `yarn wp-env destroy`.

[See the WordPress Developer Handbook for more details about managing the docker environment](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/#wp-env-run-container-command).

### Enable X-Debug

X-Debug is enabled by default for step debugging and profiling. If you need to [use other X-Debug modes](https://xdebug.org/docs/all_settings#mode) you can manually start the environment and pass the appropriate modes in: `yarn wp-env start --xdebug=profile,trace,debug`.

**Note: [there is currently an issue that prevents running X-Debug for the PHPUnit/CLI](https://github.com/WordPress/gutenberg/issues/42286).

### Switch PHP Versions

The default version that will be configured is PHP8.0. If you want to change this you can adjust the `phpVersion` value in the `.wp-env.json` file and then stop and start the environment with `yarn wp-env start`.

## Setup without Docker

If you would rather use your own development environment, you can build Gravity PDF using the following commands. 

1. Clone the repository using `git clone https://github.com/GravityPDF/gravity-pdf/`
1. Run `yarn && yarn build:production`
1. Run `composer install`
1. Run `composer run prefix`

## Building JavaScript

If you are making changes to any of the JavaScript or CSS, run `yarn build:dev` to ensure the files automatically gets built when you make changes on the file system.

## Linting

To lint your JS code use `yarn lint:js`, and to try automatically fix it use `yarn lint:js:fix`.

To lint your PHP code, use `composer lint`, and to try automatically fix it use `composer format`.

## Automated Tests

The automated test suite can only be run using Docker. 

### E2E

Useful commands include:

```
yarn test:e2e
yarn test:e2e:headless
```

### PHPUnit

Run full suite of tests using `yarn test:php` or `yarn test:php:multisite`.

### JavaScript

The JavaScript unit tests can be run with `yarn test:js`

# Documentation

All documentation can be found at [https://docs.gravitypdf.com](https://docs.gravitypdf.com).
