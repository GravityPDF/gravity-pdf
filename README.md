Gravity PDF
==========================

[![Build Status](https://travis-ci.org/GravityPDF/gravity-forms-pdf-extended.svg?branch=premium)](https://travis-ci.org/GravityPDF/gravity-forms-pdf-extended) [![Coverage Status](https://coveralls.io/repos/GravityPDF/gravity-forms-pdf-extended/badge.svg?branch=premium&service=github)](https://coveralls.io/github/GravityPDF/gravity-forms-pdf-extended?branch=premium)

Gravity PDF is a Wordpress plugin that allows you to save/view/download a PDF from the front- and back-end, and automate PDF creation on form submission.

Find out more at [https://gravitypdf.com](http://gravitypdf.com/).


# Documentation

All documentation can be found at [https://gravitypdf.com](http://gravitypdf.com/documentation/).


#### Run Unit Tests

The plugin uses PHPUnit as part of development process. Installing the testing environment is best done using a flavor of Vagrant (try [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV)).

1. From your terminal SSH into your Vagrant box using the `vagrant ssh` command
2. `cd` into the root of your Gravity PDF directory
3. Run `bash tests/bin/install.sh gravitypdf_test root root localhost` where `root root` is substituted for your mysql username and password (VVV users can run the command as is).
4. Upon success you can run `phpunit`

__If you want to generate a code coverage report__ you can run the following `phpunit --coverage-html "./tmp/coverage"` and then a report will be generated in the `/tmp/coverage/` subdirectory of the Gravity PDF plugin.
