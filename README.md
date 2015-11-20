Gravity PDF
==========================

[![Build Status](https://travis-ci.org/GravityPDF/gravity-forms-pdf-extended.svg?branch=beta)](https://travis-ci.org/GravityPDF/gravity-forms-pdf-extended)

Gravity PDF is a WordPress plugin that allows you to save/view/download a PDF from the front- and back-end, and automate PDF creation on form submission.

Find out more at [https://gravitypdf.com](http://gravitypdf.com/).

# About

This Git repository is for developers who want to contribute to Gravity PDF. **Don't use it in production**. For production use, [download the Gravity PDF from the WordPress Plugin Directory](https://wordpress.org/plugins/gravity-forms-pdf-extended/).

# Installation

1. Clone the repository using `git clone https://github.com/GravityPDF/gravity-forms-pdf-extended/`
1. Install Composer (skip this step if already installed). Here are instructions [for the Linux / Mac installation](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) and the [Windows installation](https://getcomposer.org/doc/00-intro.md#installation-windows).
1. Open your terminal / command prompt to the Gravity PDF root directory and run `composer install`.
1. You'll need to ensure `WP_DEBUG` is set to `true` in your `wp-config.php` file so the appropriate assets are loaded correctly. Alternatively, if you have NodeJS installed you can run `npm install` followed by `gulp`.
1. Copy the plugin to your WordPress plugin directory (if not there already) and active through your WordPress admin area.

# Documentation

All documentation can be found at [https://gravitypdf.com](http://gravitypdf.com/documentation/).

# Contributions

You are more than welcome to contribute to Gravity PDF but we recommend you [open a new issue on GitHub](https://github.com/GravityPDF/gravity-pdf/issues) and discuss your use-case before creating a pull request.

There are a few rules that need to be followed to ensure a smooth pull request. These include:

1. All Pull Requests must be to the `development` branch
1. Modifications of the existing code base must pass all unit tests
1. Any additions to the plugin must have appropriate unit tests written
1. Adhere to the existing code standard which mostly follows [WordPress standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)
1. Once our team is happy with the PR we'll ask you to squash your branch into a single commit, rebase it onto the development branch and push the changes to GitHub. This ensures we keep a clean Git history.
1. Pull requests that don't pass existing unit testing or do not have the proper tests to accompany the changes will not be merged.

#### Run Unit Tests

The plugin uses PHPUnit as part of the development process. Installing the testing environment is best done using a flavour of Vagrant (try [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV)).

1. From your terminal SSH into your Vagrant box using the `vagrant ssh` command
2. `cd` into the root of your Gravity PDF directory
3. Run `bash tests/bin/install.sh gravitypdf_test root root localhost` where `root root` is substituted for your mysql username and password (VVV users can run the command as is).
4. Upon success you can run `vendor/bin/phpunit`, `vendor/bin/phpunit --group ajax` and `vendor/bin/phpunit --group slow-pdf-processes`.

__If you want to generate a code coverage report__ you can run the following `phpunit --coverage-html "./tmp/coverage"` and a report will be generated in the `/tmp/coverage/` subdirectory of the Gravity PDF plugin.
