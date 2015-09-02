Gravity PDF
==========================

Gravity PDF is a Wordpress plugin that allows you to save/view/download a PDF from the front- and back-end, and automate PDF creation on form submission.

Find out more at [https://gravitypdf.com](http://gravitypdf.com/).


# Documentation

All documentation can be found at [https://gravitypdf.com](http://gravitypdf.com/documentation/).

# Branches

## master

The master branch contains the latest stable release of the software. It's the same version [avaliable in the Wordpress repository](https://wordpress.org/plugins/gravity-forms-pdf-extended/).

## beta

If we are currently running a beta edition of the software, this version will be avalible in the beta branch. This is a stable development version *but should not be used in a production environment*.

## development

This is our work in progress, bleeding edge edition of the software. Consider this an alpha edition.

# Pull Requests

Pull requests are always welcome.

#### Run Unit Tests

The plugin uses PHPUnit as part of development process. Installing the testing environment is best done using a flavor of Vagrant (try [Varying Vagrant Vagrants](https://github.com/Varying-Vagrant-Vagrants/VVV)).

1. From your terminal SSH into your Vagrant box using the `vagrant ssh` command
2. `cd` into the root of your Gravity PDF directory
3. Run `bash tests/bin/install.sh gravitypdf_test root root localhost` where `root root` is substituted for your mysql username and password (VVV users can run the command as is).
4. Upon success you can run `phpunit`
