=== Gravity PDF ===
Contributors: blue-liquid-designs
Plugin URI: https://gravitypdf.com/
Donate link: https://gravitypdf.com/donate-to-plugin/
Tags: gravity, forms, pdf, automation, attachment, email
Requires at least: 4.8
Tested up to: 4.9
Stable tag: 5.0.0-RC1
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl.txt

Automatically generate, email and download PDF documents with Gravity Forms and Gravity PDF.

== Description ==

**Gravity PDF is the ultimate solution for generating digital PDF documents using Gravity Forms and WordPress.**

https://www.youtube.com/watch?v=z8zKKrjmNjY

The plugin ships with four highly-customisable PDF templates perfectly suited for displaying your user’s data. Within seconds you can personalise the documents with your company logo, change the font, size, colour and the paper size. If the templates don't suit, [have one tailor made just for you](https://gravitypdf.com/integration-services/) or [roll your own](https://gravitypdf.com/documentation/v5/developer-start-customising/).

> Digital document management with WordPress and Gravity Forms just became a breeze!

= Feature =

* There’s no third-party APIs needed when generating your PDFs. That means no chance of third-party data breaches, no monthly fees or rate limits. You control the software and the documents it generates.
* We support all languages, including complex symbol-based languages like Chinese and Japanese, as well as Right to Left (RTL) written languages such as Arabic and Hebrew.
* Automatically email your PDF when a user completes a form. Have it emailed to people in your organisation, the user, or both. You can also conditionally generate and email the PDF.
* Using Gravity Forms developer-licensed payment add-ons – like PayPal, Authorize.net or Stripe – you can restrict access to the PDF until after a payment is captured.
* [Protecting your user’s sensitive information is at the heart of Gravity PDF](https://gravitypdf.com/documentation/v5/user-pdf-security/). The plugin’s security settings give you granular control over who has access to the PDFs generated.
* Our [JavaScript-powered font manager](https://gravitypdf.com/documentation/v5/user-custom-fonts/) allows you to install and use your favourite fonts. Now you can keep in line with your corporate style guide, or create beautiful PDF typography.
* [The documentation](https://gravitypdf.com/documentation/v5/user-installation/) has everything from basic install instructions to advanced developer how-to guides. Our friendly team is also on hand to [provide FREE general support](https://gravitypdf.com/support/).
* PHP, HTML and CSS come easy? [You’ll find creating your own PDF templates a breeze](https://gravitypdf.com/documentation/v5/developer-start-customising/). If not, [we offer PDF design services](https://gravitypdf.com/integration-services/) tailored just for you. We can even auto-fill existing PDFs!

= Premium Extensions and Templates =

[Unlock more features for Gravity PDF with one of our premium extensions](https://gravitypdf.com/extension-shop/). If one of the free PDF templates aren't working for you, [try a premium template instead](https://gravitypdf.com/template-shop/). All purchases have a 7-day 100% money back guarantee.

= Requirements =

Gravity PDF can be run on most shared web hosting without any issues. It requires **PHP 5.6+** (PHP 7.0+ recommended) and at least 64MB of WP Memory (128MB+ recommended). You'll also need to be running WordPress 4.4+ and have [Gravity Forms 2.3.1+](https://rocketgenius.pxf.io/c/1211356/445235/7938) (affiliate link).

If you aren't sure Gravity PDF will meet your needs (and haven't got a Gravity Forms license yet) you can [try out the software via our demo site](https://demo.gravitypdf.com).

= Documentation & Support =

[We have extensive documentation on using Gravity PDF](https://gravitypdf.com/documentation/v5/five-minute-install/), and our friendly support team provides [FREE basic support via our website](https://gravitypdf.com/support/#contact-support) (we also check the WordPress.org forums but submitting a ticket via GravityPDF.com will get a faster response).

= Custom PDF Integration =

We offer **comprehensive PDF integration services** and do all the PDF development and integration into Gravity Forms for you. You tell us what you want and our friendly and experienced developers will design, develop and install custom PDF templates tailor specifically for you. We can even auto-fill your existing PDF documents. [Find out more at GravityPDF.com](https://gravitypdf.com/integration-services/).

= Contribute =

All development for Gravity PDF [is handled via GitHub](https://github.com/GravityPDF/gravity-pdf/). Opening new issues and submitting pull requests are welcome.

[Our public roadmap is available on Trello](https://trello.com/b/60YGv1J3/roadmap). We'd love it if you vote and comment on your favourite ideas.

You can also keep up to date with Gravity PDF by [subscribing to our newsletter](https://gravitypdf.com/#signup-top), [following us on Twitter](https://twitter.com/gravitypdf) or [liking us on Facebook](https://www.facebook.com/gravitypdf).

Also, if you enjoy using the software [we'd love it if you could give us a review!](https://wordpress.org/support/view/plugin-reviews/gravity-forms-pdf-extended)

*Note: When Gravity Forms isn't installed and you activate Gravity PDF we display a notice that includes an affiliate link to their website.*

== Installation ==

[You'll find detailed installation instructions on GravityPDF.com](https://gravitypdf.com/documentation/v5/user-installation/).

== Screenshots ==

1. Our on-boarding experience will have you up and running in 5 minutes flat.
2. Set up the global PDF settings then head straight to configuring your first PDF.
3. Control the default paper size, PDF template and font/size/colour.
4. Advanced security options give you granular control of PDF access.
5. Tools like the font manager and custom PDF installer are readily accessible.
6. Our JavaScript-powered font manager will make using custom fonts a breeze.
7. A snapshot of your form’s PDF setup.
8. When adding a new PDF all the important settings are up front in the “General” tab.
9. Override the default appearance settings on a per-PDF basis.
10. Each template has its own PDF settings for greater control of the look and feel of your document.
11. Header and Footer support is built-in.
12. Advanced format and security settings can be applied to individual PDFs.
13. PDFs can be accessed from the Gravity Forms entry list page.
14. They also appear on the individual entry pages for easy access.
15. Zadani is a minimalist business-style template that will generate a well-spaced document great for printing.
16. Rubix uses stylish containers to create an aesthetically pleasing design.
17. Focus Gravity providing a classic layout which epitomises Gravity Forms Print Preview. It’s the familiar layout you’ve come to love.
18. Blank Slate provides a print-friendly template focusing solely on the user-submitted data.

== Changelog ==

= 5.0.0-RC1 =
* Breaking Change: Bump minimum version of Gravity Forms from 1.9 to 2.3.1+
* Breaking Change: Bump WordPress minimum version from 4.4 to 4.8+
* Breaking Change: Bump the PHP minimum version from 5.4 to 5.6+
* Breaking Change: Decouple the fonts from the plugin.

* Feature: Option to enable background Process PDFs during form submission and while resending notifications. Requires background tasks are enabled [GH#713]
* Feature: Include a Core Font Downloader in the PDF Tools to install all core PDF fonts during the initial installation [GH#709]
* Feature: Updated ReactJS to v16 which uses MIT license [GH#701]
* Feature: Add PHP7.2 Support [GH#716]
* Feature: Polyfill older browsers to support our modern Javascript [GH#729]
* Feature: Remove "Common Problems" link from PDF Help page and include "Common Questions" [GH#752]

* Dev: Update all Packagist-managed JS files to the latest version [GH#701]
* Dev: Upgrade Mpdf to version 7.1 (accessed directly via `\Mpdf\Mpdf`)
* Dev: Conditionally run `Model_PDF::maybe_save_pdf()` when Background Processing disabled [GH#713]
* Dev: Use wp_enqueue_editor() to load up the WP Editor assets [GH#754]
* Dev: Include file/line number when PDF error is thrown [GH#803]
* Dev: Remove the legacy /resources/ directory

* Bug: Fix Chosen Drop Down display issue when WordPress using RTL display [GH#698]

See [CHANGELOG.txt](https://github.com/GravityPDF/gravity-pdf/blob/master/CHANGELOG.txt) for v4 and v3 changelog history.

== Upgrade Notice ==

= 5.0.0 =
WARNING: Breaking changes! New minimum versions: PHP5.6+, WordPress 4.8+, Gravity Forms 2.3.1+.

= 4.2.0 =
WARNING: The minimum WordPress version supported is now 4.4.

= 4.0.4 =
This patch fixes a PDF security by-passing issue. If you use the PDF Security settings update immediately.

= 4.0.3 =
The core PDF templates have been updated to version 1.1. If you've previously run the Custom Template Setup make sure you run it again to take advantage of the changes.

= 4.0 =
**WARNING**: This major release is not 100% backwards compatibile with v3. Review our upgrade guide AND do a full backup before proceeding with the update (https://goo.gl/htd6CK).
