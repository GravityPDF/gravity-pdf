Gravity PDF
==========================

[![Build Status](https://travis-ci.org/GravityPDF/gravity-pdf.svg?branch=development)](https://travis-ci.org/GravityPDF/gravity-pdf) [![codecov](https://codecov.io/gh/GravityPDF/gravity-pdf/branch/development/graph/badge.svg)](https://codecov.io/gh/GravityPDF/gravity-pdf)

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

The Docker setup will create a fully functionality development environment preconfigured for Gravity PDF. 

1. Clone the repository using `git clone https://github.com/GravityPDF/gravity-pdf/` from the terminal
1. Copy and rename `.env.example` to `.env`, then replace `00000000000000000000000000000000` with a valid Gravity Forms license key 
1. Run `yarn prebuild` and `yarn build:production`
1. Start Docker Desktop (or run `docker-machine start && eval $(docker-machine env)` if using Docker Toolbox)
1. Run `yarn env:install`, then wait for the Docker environment to start-up
1. If successful, you will now have a fully-functional development environment setup with Gravity Forms and Gravity PDF. The URL and login details will be shown in the terminal.

If you shutdown Docker and want to fire up the environment later, use `yarn env:start`. You can also reset the database back to its original state with `yarn env:reset` 

## Setup without Docker

If you would rather use your own development environment, you can build Gravity PDF using the following commands. 

1. Clone the repository using `git clone https://github.com/GravityPDF/gravity-pdf/`
1. Run `yarn prebuild` and `yarn build:production`
1. Run `composer install`

## Linting

To lint your JS code, use `yarn lint:js`. 

To lint your PHP code with Docker use:
```
yarn env docker-run php composer compat
yarn env docker-run php composer lint
yarn env docker-run php composer lint:errors
```

To automatically fix PHP lint errors, use `yarn env docker-run php composer format`

If you aren't using docker, omit `yarn env docker-run php` from the beginning of each command.

## Automated Tests

The automated test suite can only be run using Docker. 

## E2E

Useful commands include:

```
yarn test:e2e
yarn test:e2e:headless
```

## PHPUnit

To succesfully run all PHPUnit tests you will need an Elite Gravity Forms license so the Survey, Poll, and Quiz plugins can be automatically installed and downloaded. To install these plugins, run `yarn env:install:phpunit`. 

You can then run full suite of tests using `yarn test:php` or `yarn test:php:multisite`

## JavaScript

The JavaScript unit tests can be run with `yarn test:js`

## Building JS

If you are making changes to any of the JavaScript, run `yarn build:watch` to ensure it automatically gets built when you make changes on the file system. 

# Documentation

All documentation can be found at [https://gravitypdf.com/documentation/](https://gravitypdf.com/documentation/).

# Contributions

You are more than welcome to contribute to Gravity PDF but we recommend you [open a new issue on GitHub](https://github.com/GravityPDF/gravity-pdf/issues) and discuss your use-case before creating a pull request.

There are a few guidelines that need to be followed to ensure a smooth pull request. These include:

1. Adhere to the existing code standard which follows [WordPress standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/), with the exception of Yoda conditionals.
1. All PRs must be to the `development` branch.
1. Modifications of the existing codebase must pass unit tests.
1. Additions to the plugin must have appropriate E2E/unit tests written.
1. PRs that don't pass existing unit testing or do not have the proper tests to accompany the changes will not be merged.

If you are uncertain whether your PR meets all these requirements, don't worry! If there are problems our friendly team will guide you in the right direction.