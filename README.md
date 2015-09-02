Gravity PDF
==========================

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
