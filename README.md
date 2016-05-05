Gravity PDF
==========================

[![Build Status](https://travis-ci.org/GravityPDF/gravity-pdf.svg?branch=development)](https://travis-ci.org/GravityPDF/gravity-pdf)

Gravity PDF is a GPLv2-licensed WordPress plugin that allows you to automatically generate, email and download PDF documents using the popular form-builder plugin, [Gravity Forms](https://goo.gl/lsBOef). Find out more about Gravity PDF at [https://gravitypdf.com](https://gravitypdf.com/).

# About

This Git repository is for developers who want to contribute to Gravity PDF. **Don't use it in production**. For production use, [download Gravity PDF from the WordPress Plugin Directory](https://wordpress.org/plugins/gravity-forms-pdf-extended/).

The `development` branch is considered our bleeding edge branch, with all new changes pushed to it. The `master` branch is our latest stable version of Gravity PDF.

# Installation

1. Clone the repository using `git clone https://github.com/GravityPDF/gravity-pdf/`
1. Open your terminal / command prompt to the Gravity PDF root directory and run `composer install`. If you don't have Composer installed, here are instructions [for Linux / Mac installation](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) and [Windows](https://getcomposer.org/doc/00-intro.md#installation-windows).
1. You'll need to ensure `WP_DEBUG` is set to `true` in your `wp-config.php` file so the appropriate assets are loaded. Alternatively, if you have [Node.js](https://nodejs.org/en/) and [Gulp](http://gulpjs.com/) installed you can run `npm install && gulp` from the command line.
1. Copy the plugin to your WordPress plugin directory (if not there already) and active through your WordPress admin area.

# Documentation

All documentation can be found at [https://gravitypdf.com/documentation/](https://gravitypdf.com/documentation/).

# Contributions

You are more than welcome to contribute to Gravity PDF but we recommend you [open a new issue on GitHub](https://github.com/GravityPDF/gravity-pdf/issues) and discuss your use-case before creating a pull request.

There are a few guidelines that need to be followed to ensure a smooth pull request. These include:

1. Adhere to the existing code standard which follows [WordPress standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/).
1. All PRs must be to the `development` branch.
1. Modifications of the existing codebase must pass all unit tests.
1. Any additions to the plugin must have appropriate unit tests written.
1. PRs that don't pass existing unit testing or do not have the proper tests to accompany the changes will not be merged.
1. Once our team is happy with the PR we'll ask you to squash your branch into a single commit, rebase it onto the development branch and push the changes to GitHub. This ensures we keep a clean Git history.

If you are uncertain whether your PR meets all these requirements, don't worry! If there are problems our friendly team will guide you in the right direction.

#### Run Unit Tests

The plugin uses PHPUnit as part of the development process. Installing the testing environment is best done using a flavour of Vagrant (try [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV)).

1. From your terminal SSH into your Vagrant box using the `vagrant ssh` command
2. `cd` into the root of your Gravity PDF directory
3. Run `bash tests/bin/install.sh gravitypdf_test root root localhost` where `root root` is substituted for your mysql username and password (VVV users can run the command as is).
4. Upon success you can run `vendor/bin/phpunit`, `vendor/bin/phpunit --group ajax` and `vendor/bin/phpunit --group slow-pdf-processes`.