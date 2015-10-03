Gravity PDF
==========================

[![Build Status](https://travis-ci.org/GravityPDF/gravity-forms-pdf-extended.svg?branch=premium)](https://travis-ci.org/GravityPDF/gravity-forms-pdf-extended) [![Coverage Status](https://coveralls.io/repos/GravityPDF/gravity-forms-pdf-extended/badge.svg?branch=premium&service=github)](https://coveralls.io/github/GravityPDF/gravity-forms-pdf-extended?branch=premium)

Gravity PDF is a WordPress plugin that allows you to save/view/download a PDF from the front- and back-end, and automate PDF creation on form submission.

Find out more at [https://gravitypdf.com](http://gravitypdf.com/).

# Installation

1. Clone the repository using `git clone https://github.com/GravityPDF/gravity-forms-pdf-extended/`
1. Install Composer (skip this step if already installed). Here are instructions [for the Linux / Mac installation](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) and the [Windows installation](https://getcomposer.org/doc/00-intro.md#installation-windows).
1. Open your terminal / command prompt to the Gravity PDF root directory and run `composer install`.
1. Copy the plugin to your WordPress plugin directory (if not there already) and active through your WordPress admin area.

# Documentation

All documentation can be found at [https://gravitypdf.com](http://gravitypdf.com/documentation/).

# Contributions
You are more than welcome to contribute to Gravity PDF but we recommend you [open a new issue on GitHub](https://github.com/GravityPDF/gravity-forms-pdf-extended/issues) and discuss your use-case before creating a pull request. We reserve the right to merge or reject the pull request at our discretion. Any additions to the plugin must have appropriate unit tests written. Modifications of the existing code base must pass all unit tests. Pull requests that don't pass existing unit testing or do not have the proper tests to accompany the changes will be rejected.

#### Run Unit Tests

The plugin uses PHPUnit as part of development process. Installing the testing environment is best done using a flavour of Vagrant (try [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV)).

1. From your terminal SSH into your Vagrant box using the `vagrant ssh` command
2. `cd` into the root of your Gravity PDF directory
3. Run `bash tests/bin/install.sh gravitypdf_test root root localhost` where `root root` is substituted for your mysql username and password (VVV users can run the command as is).
4. Upon success you can run `phpunit`, or if phpunit is not installed globally run `vendor/bin/phpunit`

__If you want to generate a code coverage report__ you can run the following `phpunit --coverage-html "./tmp/coverage"` and a report will be generated in the `/tmp/coverage/` subdirectory of the Gravity PDF plugin.
