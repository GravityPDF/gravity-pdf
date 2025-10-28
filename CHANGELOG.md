# Changelog

## 6.14.0
* 🎉 Feature: Rotate Gravity PDF log files when Gravity Forms logging is enabled. This prevents the log file getting to large.
* 🎉 Feature: Add support for using v1, v2, and v3 of the PSR/Log Composer library with Gravity PDF
* 🐞Bug: Fix PHP error if a third-party plugin loads PSR/Log v2 or v3

## 6.13.5
* 🐞Bug: Ensure background queue uses correct entry data when resending notifications
* 🐞Bug: Prevent plugins corrupting PDF data when viewing/downloading (via output buffer)

## 6.13.4
* 🐞Bug: Resolve PDF View/Download issue if both Event Espresso and LifterLMS plugin are installed 

## 6.13.3
* 🔒 Security: Remove the mPDF and Gravity PDF version numbers in the PDF metadata
* 🐞Bug: Resolve PHP error in 6.13.2 upgrade routine if the temporary PDF directory has been incorrectly set to a shared system folder
* 🐞Bug: Resolve PHP error if the `page` or `subview` admin URL parameters are arrays

## 6.13.2
* 🐞 Bug: Fix plugin build issue preventing the mPDF cache filesystem fix (6.13.0) from working
* 🧹 Housekeeping: Add upgrade routine to reset the temporary directory permissions

## 6.13.1
* 🐞 Bug: Only enable image PDF debugging when both `WP_DEBUG` and `WP_DEBUG_DISPLAY` constants are set to true 

## 6.13.0
* 🔒 Security: Switch from cURL to wp_safe_remote_get() when getting remote assets for PDFs (eg. images, CSS)
* 🔒 Security: Cleanup routine will only allow directories created and managed by Gravity PDF to be deleted
* 🐞 Bug: Ensure mPDF cache honors filesystem permissions when creating new folders
* 🐞 Bug: Don't create unnecessary ttfontdata directory in mPDF temporary directory
* 🐞 Bug: Fix PHP notices when displaying a message identifying which plugin is the non-canonical version
* 🐞 Bug: Prevent fatal error when a really old versions of GP Populate Anything is installed
* 🧹 Housekeeping: Remove mPDF temporary directory cleanup routine. Now handled directly by Gravity PDF Cron task.
* 🧹 Housekeeping: Add `gfpdf_remote_request_args` filter to let developers modify the PDF remote request configuration
* 🧹 Housekeeping: Add `gfpdf_mpdf_class_container` filter to let developers replace the `httpClient` class used by mPDF

## 6.12.6
* 🐞 Bug: Add additional guards for expected value when displaying File Upload field in PDFs (prevents PHP notice)
* 🐞 Bug: Cleanup Background Processing queue when setting is toggled on/off
* 🐞 Bug: Add additional guards for expected value when displaying List field in PDFs (prevents PHP notice if the first row in a list is empty)
* 🐞 Bug: Ensure Gravity PDF system status information is in English when copied to clipboard
* 🐞 Bug: Fix fatal error when Gravity Forms logging is turned on, but the directory where log files are saved is not writable by the web server
* 🐞 Bug: Resolve memory problem generating Core PDFs if a Rich Text Paragraph field contains more than 10+ classes (elements with more than 8 classes will have extras removed)
* 🐞 Bug: Fix 'translations loaded too early' PHP notice if any plugin requirements aren't met
* 🐞 Bug: Pass the filtered 'use_value' and 'use_admin_label' arguments when determining if the product table is empty
* 🧹 Housekeeping: Move Gravity PDF system status information to the bottom of the report

## 6.12.5.1
* 🧹 Housekeeping: Update version number in readme.txt file

## 6.12.5
* 🐞 Bug: Fix slow PDF Background Processing queue after a retry delay was added to the background processing library in Gravity Forms 2.9.7+
* 🧹 Housekeeping: Update PDF Background Processing queue to be compatible with Gravity Forms 2.9.7+ background processing library update
* 🧹 Housekeeping: Fix Background Processing deprecation notice when running Gravity Forms 2.9.7+

## 6.12.4
* 🔒Security: Escape variables in PHP Exceptions
* 🐞 Bug: Improve PDF column support when Gravity Forms includes a spacer
* 🐞 Bug: Fix display of Website field when it isn't filled in and *Show Empty Fields* is enabled
* 🧹 Housekeeping: Mark as compatible with WP 6.7
* 🧹 Housekeeping: Update PHP dependencies

## 6.12.3
* 🐞 Bug: Resolve PHP error when a license has not been activated for a Gravity PDF extension
* 🐞 Bug: Resolve PHP error when all plugin dependencies are not met
* 🧹 Housekeeping: Open canonical plugin upgrade link in new window

## 6.12.2
* 🔒Security: Fix bug that caused the 'Restrict Owner' PDF setting to be ignored when it was enabled
* 🧹 Housekeeping: Adjust canonical plugin notice
* 🧹 Housekeeping: Add canonical plugin check on system report
* 🧹 Housekeeping: Log license check API calls (canonical only)
* 🐞 Bug: Fix PHP Notices in admin area

## 6.12.1
* 🧹 Housekeeping: Update translations

## 6.12.0
* 🎉 Feature: Add basic support for Gravity Forms 2.9 Image Choice and Multiple Choice fields (Gravity PDF Core Booster v2.2 can show the images)
* 🎉 Feature: Add support for Digital Signature for Gravity Forms plugin (https://wordpress.org/plugins/digital-signature-for-gravity-forms/)
* 🧹 Housekeeping: Allow approved HTML to be displayed in the PDF for Product and Option field choices
* 🧹 Housekeeping: Add `gfpdf_form_data_products` filter to allow entry pricing information to be modified for the PDF
* 🐞 Bug: Fix column ordering issue in Blank Slate, Focus Gravity, and Rubix when the RTL setting is enabled
* 🐞 Bug: Allow Password and Privileges PDF setting description to be translated

## 6.11.4
* 🐞 Bug: Allow numbers with decimals when saving number fields in the PDF Settings

## 6.11.3
* 🐞 Bug: Fix truncated merge tags in HTML attribute when included in PDF setting Rich Text fields

## 6.11.2
* 🐞 Bug: Resolve race condition by skipping PDF cleanup at the end of form submission process if PDF Background Processing is enabled
* 🐞 Bug: Fix issue where some Notifications with PDFs attached were not being handled in a background task when PDF Background Processing is enabled

## 6.11.1
* 🐞 Bug: Only process enabled notifications during form submission when using PDF Background Processing. Notifications are enabled if they are active and have conditional logic that passes.

## 6.11.0
* 🧹 Housekeeping: Limit pages admin notices are displayed on to reduce notice fatigue
* 🧹 Housekeeping: Add specific check for the PHP extension `Ctype` when the plugin loads
* 🧹 Housekeeping: Tweak admin notice text to make error messages more clear
* 🧹 Housekeeping: Remove downgrade notice to unsupported Gravity PDF v5.0 if minimum system requirements are not met for v6.0
* 🧹 Housekeeping: Improve log messages when creating and validating a Signed PDF URL
* 🧹 Housekeeping: Improve input sanitation for textarea, number, and custom paper size fields when saving the PDF Form Settings
* 🧹 Housekeeping: Improve PDF Background Processing so its compatible with Gravity Forms native async notification feature
* 🧹 Housekeeping: Correctly cleanup PDFs after a PDF Background Processing queue runs
* 🐞 Bug: Enforce a 1pt minimum value for the Default Font Size and Font Size settings
* 🐞 Bug: Self-heal the PDF signing secret key if it becomes invalid
* 🐞 Bug: Self-heal the Global PDF Settings if it becomes invalid
* 🐞 Bug: Prevent the page reloading when selecting a tooltip on PDF settings pages
* 🐞 Bug: Register language files early so startup errors can be translated

## 6.10.2
* 🐞 Bug: Hydrate Nested Forms with Gravity Wiz Populate Anything data

## 6.10.1
* 🐞 Bug: Resolve PHP error when processing shortcode with invalid entry object
* 🐞 Bug: Adhere to conditional logic and exclude CSS class for Page Break fields
* 🐞 Bug: In more situations the Gravity PDF settings will be refreshed before the form meta is saved to the database
* 🧹 Housekeeping: Run temporary directory cleanup routine twice daily and delete files older than 12 hours
* 🧹 Housekeeping: Add gfpdf_system_status_report_items filter for Gravity PDF System Status report details
* 🧹 Housekeeping: Update mPDF to latest version
* 🧹 Housekeeping: Allow supported HTML in field labels when displayed in PDF

## 6.10.0
* 🎉 Feature: Add native support for the Legal Signature and Legal Consent form fields added by the Legal Signing for Gravity Forms plugin

## 6.9.1
* 🔒Security: Disable the Signed URL feature in the [gravitypdf] shortcode when a URL parameter provides the entry ID (e.g. Page Confirmations)
* 🐞 Bug: Gracefully handle invalid conditional logic rules when adding date entry meta support
* 🐞 Bug: Display field for entry metadata PDF conditional rule when there are no form fields compatible with conditional logic
* 🐞 Bug: Ensure the template cache is correctly cleared when PDF Debug Mode is enabled
* 🐞 Bug: Flush the template cache after installing new templates via the PDF Template Manager
* 🐞 Bug: Clear template cache when plugin deactivated
* 🧹 Housekeeping: Small improvement to performance when reading template and font files from disk

## 6.9.0
* 🎉 Feature: Add new conditional logic options to PDFs eg. Payment Status, Date Created, Starred (props: Gravity Wiz)
* 🎉 Feature: Add support for Show HTML Fields, Show Empty Fields, Show Section Break Description, and Enable Conditional Logic PDF settings when displaying Gravity Wiz Nested Forms field
* 🐞 Bug: Fix Form Editor saving problem for Gravity Forms v2.6.*
* 🐞 Bug: Fix Drag and Drop Column layout issue when the GF Styles Pro plugin is enabled
* 🐞 Bug: Fix issue sending PDF URLs with Gravity Wiz Google Sheets
* 🐞 Bug: Improve display of Rich Text Textarea fields by removing top margin on individual paragraphs
* 🐞 Bug: Resolve compatibility issue that corrupted PDFs when using Weglot
* 🧹 Housekeeping: Exclude popular WordPress staging environments from site count when activating Gravity PDF licenses
* 🧹 Housekeeping: Improve Gravity PDF license activation success and error messages
* 🔒Security: Improve security of network requests to Gravity PDF licensing server
* Developer: Add `set_pdf_config( $config )` and `get_pdf_config()` methods to PDF Field classes
* Developer: In the PDF field blacklist, check using the original type and not with `$field->get_input_type()`

## 6.8.0
* 🎉 Feature: Add PDF Download metabox to Gravity Flow Inbox for logged-in users with appropriate capability
* 🎉 Feature: Add AI-generated translations for French, Spanish, Italian, German, Dutch, Russian, and Chinese
* 🔒Security: Only show PDF view/download links on entry list and details page if logged-in user has appropriate capability
* 🧹 Housekeeping: Improve performance on admin pages by caching the list of available templates
* 🧹 Housekeeping: When permalinks are enabled, generate the PDF URL with/without a trailing slash
* 🐞 Bug: Remove whitespace from textarea fields in the PDF settings

## 6.7.4
* 🐞 Bug: Resolve PHP error for specific GravityView / GravityChart combo
* 🐞 Bug: Render supported HTML in labels/choices for the PDF Pricing table
* 🐞 Bug: Fix PHP error while viewing a PDF when running an older version of WordPress (< 5.9) and PHP (< 8.0)

## 6.7.3
* 🐞 Bug: Fix 3rd party conflict when different version of PSR-7 library is loaded

## 6.7.2
* 🐞 Bug: Resolve fatal error when using Gravity Forms Google Analytics Pagination feature with the PDF URL included in the parameters.
* 🧹 Housekeeping: Update PDF library to latest version
* 🧹 Housekeeping: Update help search API details

## 6.7.1
* 🐞 Bug: Improve dependency conflicts with third party plugins who bundle PSR Log v2 or v3
* 🧹 Housekeeping: Use 4xx HTTP Status Codes for non-server related errors when generating PDFs

## 6.7.0
* 🎉 Feature: Add support for multiple PDFs with the same Filename on a single form
* 🎉 Feature: Use secure links for File Upload and Post Image fields in Core and Universal PDFs
* Dev Feature: Include secure links in $form_data array for File Upload and Post Image fields
* 🐞 Bug: Fix backwards compatibility error when running a version of Gravity Forms less that 2.6
* 🐞 Bug: Allow sanitized HTML in the labels of Radio and Checkbox admin settings
* 🐞 Bug: Remain editing current PDF if a hard refresh occurs after adding a new PDF to the form

## 6.6.1
* 🐞 Bug: Prevent PDF settings being override if multiple browser windows are open, and both are updating different settings of the same form concurrently
* 🐞 Bug: Gracefully handle license key deactivation if an error occurs
* 🧹 Housekeeping: Bump WordPress Tested Up To v6.3

## 6.6.0
* 🎉 Feature: Improve display of ungrouped product fields in Core and Universal templates
* Dev Feature: Add `gfpdf_hide_consent_field_if_empty` filter, to remove the Consent field from Core and Universal templates if a user hasn't consented.
* 🐞 Bug: Remove Section Break description container is there is not a description included
* 🐞 Bug: Fix potential PHP error when using GravityView and PDF for GravityView
* 🧹 Housekeeping: Update PHP and JS dependencies

## 6.5.5
* 🐞 Bug: Ensure PDF conditional logic is run through the correct sanitization function upon save
* 🐞 Bug: Ensure Gravity Wiz Populate Anything live merge tags are correctly processed in the $form_data array
* 🐞 Bug: Fix Monolog error when running PHP8.1

## 6.5.4
* 🐞 Bug: Fix duplicate notifications when using PDF Background Processing while looping over GFAPI::submit_form()

## 6.5.3
* 🐞 Bug: Fix HTTP(S) image/stylesheet loading problem in PDFs for SiteGround customers

## 6.5.2
* 🐞 Bug: Fix PHP error when a non-string is passed to the Kses sanitizing class
* 🐞 Bug: Resolve memory problem generating Core PDFs if an HTML element contains more than 10+ classes (field CSS Classes are now truncated to 8 user-defined classes)
* 🐞 Bug: Fix Slim Image Cropper display problems in Core PDFs
* 🧹 Housekeeping: Update mPDF to the latest version
* 🧹 Housekeeping: Update QueryPath to the latest version

## 6.5.1
* 🧹 Housekeeping: Update mPDF to the latest version
* 🐞 Bug: Resolve custom font installation issue for some .ttf files

## 6.5.0
* 🧹 Housekeeping: Update Global Extension Settings UI to be Gravity Forms 2.5 compatible
* 🧹 Housekeeping: Adjust how admin Notices are handled on Gravity PDF pages
* 🧹 Housekeeping: Update JavaScript package bundle
* Dev: Validate template filename when uploading via the PDF Template Manager (A-Za-z0-9_-)
* Dev: Add filterable CSS class names to the PDF links in the admin area
* Dev: Pass context to the `gfpdf_core_template` hook
* Dev: Add `gfpdf_core_template_{form_id}` hook to target specific forms
* 🐞 Bug: Fix undefined `rtl` notice in Core PDF templates
* 🐞 Bug: Fix PHP8.1 notice when saving PDF settings

## 6.4.7
* 🐞 Bug: Resolve blank PDF problem when a large HTML block is processed by mPDF
* 🐞 Bug: Resolve QueryPath deprecation notice about passing null to trim()
* 🧹 Housekeeping: Update mPDF and URL Signer library to latest version

## 6.4.6
* 🐞 Bug: Adjust Nested Forms and Repeater field PDF markup to ensure a unique ID attribute for any HTML tags
* 🐞 Bug: Prevent duplicate grid css classes being added to Nested Forms HTML tags
* 🐞 Bug: Process merge tags in Background Image PDF setting before late escaping in the PDF HTML markup
* 🧹 Housekeeping: Remove initialized message from Gravity PDF logs

## 6.4.5
* 🐞 Bug: Fix image display problem if filename had a space in it
* 🐞 Bug: Fix Background Image display problem on Windows OS
* Developer: Added HTML field content to Repeater Field $form_data array

## 6.4.4
* 🐞 Bug: Resolve HTML encoding issue in PDF when displaying Coupon field in Gravity Wiz eCommerce Perk's Product Table
* 🐞 Bug: Remove coupon line item in PDF when Gravity Wiz eCommerce Perk's Product Table in use
* 🐞 Bug: Resolve duplicate product table displayed in a legacy v3 template
* 🐞 Bug: Resolve PHP notice when displaying product table in legacy v3 template
* 🐞 Bug: Resolve PHP notice when displaying Section Break field in legacy v3 template

## 6.4.3
* 🐞 Bug: Open PDF "view" link in a new browser tab on Entry List page
* 🐞 Bug: Only hide Select field in Core/Universal templates if the saved value is an empty string, not a falsey value
* 🐞 Bug: Prevent PHP notice when displaying Repeater field in PDFs with a Number sub-field
* 🐞 Bug: Prevent HTML attribute content from having their entities decoded if they were previously encoded
* 🐞 Bug: Fix Core/Universal template image display issues on servers running Windows

## 6.4.2
* 🐞 Bug: Allow `data` protocol so Base 64-encoded images can be correctly displayed in Core/Universal templates
* 🐞 Bug: Fix Core Font Installer problem when running older versions of WordPress (5.3 to 5.8)
* 🐞 Bug: Fix fatal return type mismatch error if `safe_style_css` filter has been implemented incorrectly

## 6.4.1
* 🐞 Bug: Fix PDF display issues with the Survey, Poll, Post Category, and Post Custom Fields

## 6.4.0
* Security (Hardening): Move from early escaping to late escaping variables on output
* Security (Hardening): Add additional validation checks to the Core Font installer
* Security (Hardening): Escape text returned from WordPress l10n functions
* Security (Hardening): Escape any and all strings from the Gravity Forms form object, in any context (including PDFs)
* Security (Hardening): Move to earlier sanitizing of user input
* Security (Hardening): Custom PDF template filenames are now limited to the following characters: `A-Za-z0-9_-`
* Security (Hardening): The `?html=1` and `?data=1` developer helper parameters now only work in non-production environments (`WP_ENVIRONMENT_TYPE !== 'production'`), or when Gravity PDF Debug Mode is explicitly enabled
* Security (Hardening): Prevent directory traversal when loading the various Gravity PDF UI components
* Security (Hardening): Change PDF Form Settings capability check from `gravityforms_edit_settings` to `gravityforms_edit_forms`
* Security (Hardening): Change Font Manager CRUD capability check from `gravityforms_view_entries` to `gravityforms_edit_forms`
* Security (Hardening): Switch to `sodium_crypto_secretbox_keygen()` function with modified fallback to `wp_generate_password()` when generating new PDF URL signing secret key
* Security (Hardening): Switch from a Text to Password field for the Gravity PDF License Keys
* Security (Hardening): Update PHP and JavaScript dependencies
* Developer: Added `\GFPDF\Statics\Kses::output($html)` and `\GFPDF\Statics\Kses::parse($html)` methods for use with escaping/sanitizing HTML in PDFs (as an alternative to wp_kses_post()).
* Performance: Register JavaScript in the footer on Gravity PDF admin pages
* Privacy: Added "Get more info" link in the Core Font Installer instructions, and disclaimer to plugin's README.txt installation section
* 🐞 Bug: Fix issue passing PDF URL to Gravity Forms Mailchimp add-on
* 🐞 Bug: Allow hyphen in custom font key when updating or deleting to remain backwards compatible
* 🐞 Bug: Fix PHP8.1 type conversion warning in the template cache when transient's are flushed
* 🐞 Bug: Remove empty repeater sections from Core PDFs when not filled in by the user

## 6.3.1
* 🔒Security: Prevent potential XSS attack by escaping URL returned from add_query_args() on the PDF List or PDF Form Settings pages
* Developer: Apply `gfpdf_current_form_object` filter added in 6.3.0 to the form object in Helper_Abstract_Fields.php using the $type `helper_abstract_fields`.
* 🐞 Bug: Correctly display the file path in the logs when cleaning up PDFs from disk or flushing the mPDF cache
* Performance: reduce I/O operations when flushing the mPDF cache by excluding the top-level directory

## 6.3.0
* 🎉 Feature: Support for mapping PDF URLs to your favorite services using Gravity Forms feeds. This includes (but is not limited to): PayPal, MailChimp, HubSpot, Stripe, Square, ActiveCampaign, Agile CRM, Capsule, CleverReach, Constant Contact, EmailOctopus, Zoho CRM
* 🎉 Feature: Support for the Zapier add-on: PDF URLs can now be passed to your zaps
* 🎉 Feature: Add [gravitypdf] shortcode rendering support to Gravity Wiz's Entry Block perk
* 🧹 Housekeeping: Change "document" icon used for settings menus to the "Gravity PDF" icon
* 🧹 Housekeeping: duplicating PDFs on a form will now have the correct alternating background color in the table
* 🧹 Housekeeping: Process the [gravitypdf] shortcode when merge tags get processed so that it can be used where ever merge tags are supported
* Developer: Add new `gfpdf_current_form_object` filter to manipulate the $form array when processing PDFs
* 🐞 Bug: Fix a race condition when using Background Processing that could see the PDF deleted before being attached to notifications
* 🐞 Bug: Do not strip the backslash character when used in PDF settings

## 6.2.1
* 🐞 Bug: Always generate a new PDF when using the GPDFAPI::create_pdf() method
* 🐞 Bug: Fix fatal error during PDF generation when using the `gform_address_display_format` filter

## 6.2.0
* 🎉 Feature: Add support for Gravity Forms 2.6 (see Housekeeping below)
* 🧹 Housekeeping: Add alternate background color on PDF List page
* 🧹 Housekeeping: Add styles/support for new merge tag selector
* 🧹 Housekeeping: Add styles for Copy to Clipboard shortcode button on PDF List page
* 🧹 Housekeeping: Update help search API to query v6 documentation
* 🐞 Bug: Fix error message display issue on Form PDF add/edit page
* 🐞 Bug: Fix missing styles on multi-PDF view/download menu on Entry List page

## 6.1.1
* 🐞 Bug: Allow number field to show a thousand separator by using the 'gform_include_thousands_sep_pre_format_number' filter.
* 🐞 Bug: Fix PHP Notice when displaying Repeater field caused by processing field's not present in `$form_data['field']` array key
* 🧹 Housekeeping: Add logging to file/directory cleanup method
* 🧹 Housekeeping: Add additional checks and logging when processing background tasks

## 6.1.0
* 🎉 Feature: Add Copy to Clipboard feature for PDF Shortcode on the PDF List page
* 🐞 Bug: Fix empty check on the Radio field so a zero (0) value is not considered empty

## 6.0.3
* 🐞 Bug: Reduce the Focus Gravity template column widths by a fraction to prevent edge-case display issues (props Hiwire Creative)
* 🐞 Bug: Fix Help page results text encoding problems
* 🐞 Bug: Prevent multiple font files being uploaded to a single dropzone
* 🐞 Bug: When checking if a Radio/Select field is empty in the PDF context, only look at the value property.

## 6.0.2
* 🐞 Bug: Fix up 404 link for Outdated Templates in System Status
* 🐞 Bug: Revert vendor aliasing for mPDF and querypath (back to the original namespace) as it caused more problems than is solved. Developers: see https://docs.gravitypdf.com/v6/users/v5-to-v6-migration#changed-namespace-for-composer-packages

## 6.0.1
* 🐞 Bug: When displaying the minimum Gravity Forms version not met error, remove `beta-1` as the minimum to prevent confusion.

## 6.0.0
This major release is designed specifically for Gravity Forms 2.5+ and includes breaking pages that may affect you. You are strongly encouraged to [review the upgrade guide before attempting to update to v6](https://docs.gravitypdf.com/v6/users/v5-to-v6-migration).

## ⚠️BREAKING CHANGES
* New minimum requirements PHP7.3+, WordPress 5.3+, Gravity Forms 2.5+
* Removed Gravity PDF v3 template stylesheet (swap legacy PDF template to Focus Gravity template)
* Removed Gravity PDF v3 to v4 migration code (upgrade to v4/v5 before attempting v6 upgrade)
* (Dev) Moved all vendor (Packagist) packages to new `GFPDF_Vendor/` namespace (BC aliasing for common classes included). Prevents all vendor conflicts with other plugins.
* (Dev) Removed "Setup Custom Templates" tool (manually copy over template files to PDF Working Directory)
* (Dev) Removed `shortname` property from `custom_fonts` global PDF options, and removed the requirement for the `font_name` to be unique (use `id` instead of `shortname`).
* (Dev) Changed the first parameter $font_name in `GPDFAPI::delete_pdf_font()` to $font_id instead. You can no longer delete the font by
 its name, as it is no longer a unique identifier.
* (Dev) Majority of admin user interface markup (UI) changed to suit new GF2.5 UI ()
* (Dev) Renamed `\GFPDF\Helper\Fields\Field_CreditCard` class to `Field_Creditcard`
* (Dev) Change `\GFPDF\Model\Model_Install` __construct signature by removing `Helper_Abstract_Forms` dependancy from the start and adding `Model_Uninstall` at the end
* (Dev) Change `\GFPDF\Model\Model_System_Report` __construct signature by adding new `Helper_Templates` dependancy at the end
* (Dev) Removed `\GFPDF\View\View_Settings::system_status` method. Replaced by `Controller_|Model_|View_System_Report` classes for direct integration with Gravity Forms System Status page
* (Dev) Removed undocumented `gfpdf_entry_detail_pre_container_markup` and `gfpdf_entry_detail_post_container_markup` actions
* (Dev) Adjusted ID from `#tab_pdf` to `#tab_PDF` for container on Global PDF settings page. This ensures both the Global and Form PDF settings use a consistent ID.
* (Dev) Change `\GFPDF\Model\Model_Install` __construct signature by removing `Helper_Abstract_Forms` dependancy from the start and adding `Model_Uninstall` at the end
* (Dev) Change `\GFPDF\Model\Model_System_Report` __construct signature by adding new `Helper_Templates` dependancy at the end
* (Dev) Removed `\GFPDF\View\View_Settings::system_status` method. Replaced by `Controller_|Model_|View_System_Report` classes for direct integration with Gravity Forms System Status page
* (Dev) Removed undocumented `gfpdf_entry_detail_pre_container_markup` and `gfpdf_entry_detail_post_container_markup` actions
* (Dev) Adjusted ID from `#tab_pdf` to `#tab_PDF` for container on Global PDF settings page. This ensures both the Global and Form PDF settings use a consistent ID.
* (Dev) Deprecate Helper_Abstract_Options::get_font_short_name(). No direct replacement as the font 'shortname' has been phased out (using unique ID now).
* (Dev) Updated field description markup to use DIVs instead of SPANs. Matches Gravity Forms RC1
* (Dev) Deprecate these methods from `\GFPDF\Model\Model_Install`: `uninstall_plugin`, `remove_plugin_options`, `remove_plugin_form_settings`, `remove_folder_structure`, `deactivate_plugin`. All moved to `Model_Uninstall`.

## 🎉 NEW FEATURES
* Brand new admin user interface (UI) to seamlessly match the Gravity Forms (GF) 2.5 UI.
* Added support for new GF columns feature in Core PDFs
* Add PDF column support for Gravity Perks Nested Forms
* Added RTL support for new GF columns feature in Core PDFs
* Refreshed Font Manager with better validation, error handling, and automatic support for fonts that include Open Type Layout features.
* Added new PDF metabox on Entry Details page. Includes a better user experience for forms with a lot of PDFs configured.
* Added new merge tag modifiers :download, :print, :signed, :signed:expiry to enhance existing PDF mergetag (modifiers can all be used in conjunction)
* Add PDF URL to Webhook add-on "All Fields" request body
* Add ability to include PDF URLs in Entry exports
* Add Gravity PDF info to Gravity Forms System Status page
* Show plugin downgrade prompt when minimum WordPress, Gravity Forms, or PHP requirements aren't met so users can easily roll back to the latest supported v5.x version.
* Add support for WordPress' native Background Updates
* Add accessibility improvements for keyboard users and screen readers on all Gravity PDF UIs
* Display warning on System Status page when Core template overrides are out of date
* Include Add/Update PDF button below each section on PDF creation page to make it easy to save
* Improve RTL support on admin pages

## 🎉 UX IMPROVEMENTS
* Remove the Always Save PDF setting from the UI.
* Switch all Radio PDF settings to new Toggle setting
* Switch all Multiselect PDF settings to Checkbox field (better accessibility)
* Rename PDF setting "Name" to "Label"
* Replace asterisk `*` to text `(required)` to signify required fields (better accessibility)
* Rename PDF setting "Orientation" to "Paper Orientation"
* Refine PDF settings descriptions.
* Removed Welcome Page
* Move Paper Size global settings below the Template / Font settings
* Removed rich Select functionality (using Chosen) in UI for greater accessibility
* Remove WP Dialog prompts in UI for greater accessibility
* Move Gravity PDF uninstaller from Tools tab to Gravity Forms Uninstall settings page

## 🐛 BUG FIXES
* Ignore `content-type` header API response when running the Core Font installer
* Make all `GFPDFAPI` API class error responses translatable
* Fix PHP8 notice
* Prevent background queue from continuing if retry limit reached on unrecoverable task (like generating the PDF)
* Adjust custom paper size sanitize logic to fix PHP error
* Check for invalid relative date when using Signed PDF URLs and fallback to default timeout
* Fix border display issue in Core Product table
* Show error message in Template Manager when maximum file size limit is reached

## 🧑‍💻 DEVELOPER IMPROVEMENTS
* Rewritten all CSS in SASS
* Add `GPDFAPI::get_entry_pdfs( $entry_id )` method to API. Acts like `GPDFAPI::get_form_pdfs( $form_id )` but filters out any PDFs that don't pass conditional logic checks for the current entry.
* Added `Helper_Abstract_Config_Settings` class which template config files can extend to automatically have the current PDF settings injected into the class.
* Adjusted some logged items to use less severe alerts (when template config file doesn't exist, and when native PDF support for a field doesn't exist)
* Upgrade vendor packages to latest versions
* Remove all backbone.js and underscore.js Font Manager code from Gravity PDF admin pages
* Remove gulp as dependency (build is done using only webpack now)
* Add better error log messages for PDF Merge tag processing
* Pass additional information to the `gfpdf_field_container_class` filter

## 5.4.0
* 🎉 Feature: Prevent update to 6.0 if minimum requirements are not met (including when automatic updates enabled)
* 🎉 Feature: Show/allow any new updates for 5.x if minimum requirements are not met for 6.0

## 5.3.4
* 🔒Security: Resolve XSS issue on PDF List page
* 🔒Security: Resolve authenticated arbitrary PHP file Deletion when using the PDF Template Manager (by default, this affects Administrator accounts only)
* 🧹 Housekeeping: Add gfpdf_container_class_map filter
* 🧹 Housekeeping: Update Monolog to v1.26
* 🧹 Housekeeping: Fix PHP8 deprecation notices
* 🧹 Housekeeping: Remove jQuery deprecation notices
* 🧹 Housekeeping: Downgrade error to a notice when a not-yet supported field is being processed by the PDF
* 🧹 Housekeeping: Bump WordPress Tested To value to 5.7
* 🐞 Bug: Fix Media Library inserter on PDF pages when Gravity Forms No Conflict Mode enabled
* 🐞 Bug: Fix PHP fatal error when logging is enable and the log file cannot be written to
* 🐞 Bug: Fix double spinner randomly showing up when installing and selecting a new PDF template

## 5.3.3
* 🐞 Bug: Fix PHP notice when no valid form or entry passed when processing merge tags
* 🐞 Bug: Make PDF generation background processing task unrecoverable so rest of the queue isn't executed
* 🐞 Bug: always parse Core Font payload as JSON
* 🐞 Bug: fix a PHP 8 notice (note: the plugin is not guaranteed to be 100% PHP 8-compatible at this time)
* 🧹 Housekeeping: adjust log level to 'notice' for optional template configuration file not found
* 🧹 Housekeeping: replace most deprecated jQuery code with new recommendations
* 🧹 Housekeeping: update EDD licensing class to v1.8 for premium add-ons
* 🧹 Housekeeping: update composer-managed dependencies
* 🧹 Housekeeping: Make API error messages translatable

## 5.3.2
* 🐞 Bug: Fix Media Manager so it shows all file types on Gravity PDF pages
* 🐞 Bug: Fix Security PDF settings JS toggle when using translated text
* Dev: Update EDD software licensing class to 1.7.1

## 5.3.1
* Bug/Dev: Prevent composer package `Monolog` version conflict with other plugins by moving to namespace `GFPDF\Vendor\Monolog`

## 5.3.0
* 🎉 Feature: Add support for Gravity Perk Populate Anything plugin
* 🎉 Feature: Add support for Gravity Plus Multi-Currency Selector plugin
* 🎉 Feature: Add `allow_url_fopen` PHP setting check to Gravity Forms and Gravity PDF System Statuses

* 🐞 Bug: Decode special characters for processed mergetags used in PDF Password or Master Password settings
* 🐞 Bug: Fix issue uploading TTF files via the Font Manager
* 🐞 Bug: Fix PHP Notices when processing [gravitypdf] shortcode under specific conditions
* 🐞 Bug: Fix validation issue with signed PDF URLs on sub-directory multisites
* 🐞 Bug: Fix problem displaying PDF Template Upload dropzone for Super Admins on multsite installations

* Dev: Add `gfpdf_pre_uninstall_plugin` and `gfpdf_post_uninstall_plugin` actions
* Dev: Add `gfpdf_field_container_class` filter to swap out the Helper_Field_Container class with your own
* Dev: Add `gfpdf_unfiltered_template_list`, `gfpdf_fallback_template_path_by_id`, `gfpdf_template_config_paths`, and `gfpdf_template_image_paths` filters
* Dev: Rewrite Monolog timezone logic to support both v1 and v2, which places nice with other plugins that use this library

## 5.2.2
* 🐞 Bug: Add additional error handling to Background Processing when a form / entry is deleted
* 🐞 Bug: Adjust logging code to adhere to PSR-3 (forward compatibility with Monolog v2)
* 🐞 Bug: Add fixed width to first column in Chained Select output for Core / Universal PDFs
* 🐞 Bug: Add nofollow attribute to PDF Download Link to prevent attempted indexing
* 🐞 Bug: Disable UI for PDF Template Installer when user doesn't have appropriate capabilities
* 🐞 Bug: Fix font upload issues to Media Library

* Dev: Add additional logging when license activation failure occurs
* Dev: Update dependencies: Monolog 1.25.1 -> 1.25.3, Mpdf 8.0.3 -> 8.0.5

## 5.2.1
* 🐞 Bug: Fix PHP Notice when using Quiz Add-on without a correct answer selected
* 🐞 Bug: Fix image display issues in PDF when URL has a redirect
* 🐞 Bug: Allow HTML in Consent field label (those supported in wp_kses_post)

## 5.2.0
* 🐞 Bug: Prevent Fatal Error on PHP7.2 when using Category field type set to Checkboxes in Core PDFs
* 🐞 Bug: Resolve conflict with SiteGround HTML Minifier when generating PDFs in browser [GH#897] [GH#951]
* 🐞 Bug: Strip PDF page breaks from Header and Footer Rich Text Editor fields [GH#898]
* 🐞 Bug: Conditionally register WP rewrite tags to prevent third party plugin conflicts [GH#892]
* 🐞 Bug: Move noindex,nofollow header to beginning of PDF endpoint processing to prevent PDF errors getting indexed [GH#956]
* 🐞 Bug: Prevent `gfpdf_post_pdf_save` action getting triggered twice during form submission [GH#948]
* 🐞 Bug: Resolve issue with Global PDF Settings not getting updated on the initial save
* 🐞 Bug: Resolve issue displaying Category field in PDF when a category has a commas in the label/value [GH#966]
* 🐞 Bug: Add field fallback support in Core PDFs for third-party custom fields that contain subfields
* 🐞 Bug: Resolve JS error when using Redirect Confirmation with [gravitypdf] shortcode and submitting an AJAX-enabled form [GH#989]
* 🐞 Bug: Adhere to the Description placement setting when displaying the Consent Field in Core PDFs [GH#998]
* 🐞 Bug: Resolve issue setting the PDF image DPI
* 🐞 Bug: Fix display issue on Gravity PDF Getting Started Page [GH#1000]

* Dev: Add End to End Tests for greater quality control [GH#949]
* Dev: Rewrite Help Search in ReactJS [GH#882]
* Dev: Add WordPress Linting Standard to Codebase [GH#887]
* Dev: Add `gfpdf_mpdf_post_init_class` action to be run after the mPDF object is fully initialised [GH#890]
* Dev: Add `gfpdf_mpdf_class_config` filter to allow the mPDF initialization array to be modified
* Dev: Update JS Dependencies [#884]
* Dev: Remove ImmutableJS dependency
* Dev: Upgrade mPDF from 7.0.9 to 8.0.3 and add backwards compat to prevent breaking changes https://github.com/mpdf/mpdf/blob/development/CHANGELOG.md
* Dev: Optimize transient usage [GH#889]
* Dev: Move non-React JS from Gulp to Webpack bundle [GH#918]
* Dev: Split all non-React JS into components [GH#976]
* Dev: Add `gfpdf_pre_pdf_generation_output` action run prior to the PDF being output in the browser
* Dev: Add `gfpdf_pre_pdf_generation_initilise` action run prior to the PDF object creation
* Dev: Add `gfpdf_pre_pdf_list_shortcode_column` and `gfpdf_post_pdf_list_shortcode_column` actions run before and after read-only shortcode on PDF List page
* Dev: Use WP_Rewrite `index` property instead of `root` property when registering PDF permalinks
* Dev: Add pre and post actions for Entry Detail PDF mark-up
* Dev: Include `settings`, `entry_id` and `form_id` to Model_PDF::get_pdf_display_list()
* Dev: Convert PHP loose comparisons `==` to strict comparisons `===` [GH#928]
* Dev: Convert plugin directory names to be PSR-4 compliant for simplier autoloading [#929]
* Dev: Refractor class internals for [gravitypdf] shortcode for easier code reusability [#930]
* Dev: Remove `final` from Helper_Abstract_Addon::get_short_name()
* Dev: Speed up PDF generation time by converting O(n2) loop to O(n) loop [GH#934]
* Dev: Add React Sagas for all ReactJS side effects (eg. API/AJAX calls) [GH#975]
* Dev: Add Lazy Load ReactJS components for improved loading times on Gravity PDF admin pages [GH#938]
* Dev: Add better error logging for Background Processing tasks
* Dev: Refractor Core Font ReactJS code [GH#981]

## 5.1.5
* 🧹 Housekeeping: Add filter `gfpdf_mpdf_post_init_class` to interact with mPDF right after the initial Gravity PDF object setup [GH#890]
* 🐞 Bug: Fix URL rewrite issue with plugins that use `action` GET super global [GH#892]
* 🐞 Bug: Fix conflict with the SG Optimizer plugin's Minify HTML option [GH#897]
* 🐞 Bug: Strip Page Breaks from Headers and Footers to prevent Fatal PHP Error [GH#898]

## 5.1.4
* 🧹 Housekeeping: Upgrade Mpdf from 7.1.8 to 7.1.9 https://github.com/mpdf/mpdf/compare/v7.1.8...v7.1.9
* 🐞 Bug: Ensure correct permissions are set on mPDF tmp directory [GH#874]
* 🐞 Bug: Fix up mPDF tmp directory writable warning [GH#873]
* 🐞 Bug: Add missing core mPDF v7 fonts to Font Selector [GH#877]
* 🐞 Bug: Fix up v3 legacy template notices [GH#875]
* 🐞 Bug: Fix up v3 legacy endpoint entry error [GH#876]

## 5.1.3
* 🧹 Housekeeping: Upgrade Mpdf from 7.1.7 to 7.1.8 https://github.com/mpdf/mpdf/compare/v7.1.7...v7.1.8
* 🧹 Housekeeping: Revert Mpdf tmp path back to Gravity PDF tmp directory (introduced 5.0.2) as Mpdf 7.1.8 resolves font cache issue
* 🐞 Bug: Use WordPress' ca-bundle.crt when making cURL requests with Mpdf to prevent HTTPS issues [GH#861]
* 🐞 Bug: Add `exclude` class support to Nested Form fields [GH#862]

## 5.1.2
* Upgrade Mpdf from 7.1.6 to 7.1.7 https://github.com/mpdf/mpdf/compare/v7.1.6...v7.1.7
* Allow Debug messages to be logged in Gravity PDF log file
* Add log file message when the PDF Temporary Directory check fails
* Ensure backwards compatibility with legacy templates who access Mpdf properties directly
* When sending notifications, ensure PDF settings go through same filters as when viewing / downloading PDFs

## 5.1.1
* 🐞 Bug: Process Merge Tags when displaying Nested Forms in Core / Universal PDFs [GH#849]
* 🐞 Bug: Don't strip `<pagebreak />`, `<barcode />`, `<table autosize="1">`, and `page-break-*` CSS when displaying Rich Text Editor fields in PDF [GH#852]
* 🐞 Bug: Try convert the Background Image URL to a Path for better relability [GH#853]
* 🐞 Bug: Fix Rich Text Editor display issue in PDF Settings when Elementor plugin enabled [GH#854]
* 🐞 Bug: Don't strip `<a>` tag when direct parent of `<img />` in the Core/Universal PDFs Header and Footer Rich Text Editor [GH#855]

## 5.1.0
* 🎉 Feature: Add support for Gravity Forms Repeater Fields in PDFs [GH#833]
* 🎉 Feature: Add support for Gravity Wiz's Nested Forms Perk in PDFs
* 🎉 Feature: Add support for Gravity Forms Consent Field in PDFs [GH#832]
* 🎉 Feature: Add signed-URL authentication to [gravitypdf] shortcode using new "signed" and "expires" attributes [GH#841]
* 🎉 Feature: Add new "raw" attribute to the [gravitypdf] shortcode which will display the raw PDF URL [GH#841]
* 🎉 Feature: Added "Debug Mode" Global PDF Setting which replaces "Shortcode Debug Message", WP_DEBUG settings, and caches the template headers [GH#823]

* Dev Feature: Add `gfpdf_disable_global_addon_data` filter to disable aggregate Survey / Poll / Quiz data in $form_data array (for performance)
* Dev Feature: Add `gfpdf_disable_product_table` filter to disable Product table in PDF [GH#827]
* Dev Feature: Pass additional parameters to the `gfpdf_show_field_value` filter
* Dev Feature: Trigger `gfpdf_template_loaded` JS event after loading new PDF Template settings dynamically
* Dev Feature: Add `gfpdf_field_product_value` filter to change Product table HTML mark-up in PDF

* 🐞 Bug: Enable Image Watermarks in PDF
* 🐞 Bug: Prevent HTML fields getting passed through `wpautop()` [GH#834]
* 🐞 Bug: Test for writability in the mPDF tmp directory and fallback to the Gravity PDF tmp directory if failed [GH#837]
* 🐞 Bug: Fix scheduled licensing status check and display better error if license deactivation fails [GH#838]
* 🐞 Bug: Correctly display the values for multiple Option fields assigned to a single Product when Product Table is ungrouped in PDF [GH#839]
* 🐞 Bug: Disable IP-based authentication when the entry IP matches the server IP [GH#840]

## 5.0.2
* 🐞 Bug: Resolve fatal error on WP Engine due to security in place that prevented mPDF font cache from being saved.

## 5.0.1
* 🐞 Bug: Ensure the mPDF temporary directory is set to the PDF Working Directory `tmp` folder [GH#817]
* 🐞 Bug: Refine the Background Processing description and tooltip text [GH#818]

## 5.0.0
* Breaking Change: Bump minimum version of Gravity Forms from 1.9 to 2.3.1+
* Breaking Change: Bump WordPress minimum version from 4.4 to 4.8+
* Breaking Change: Bump the PHP minimum version from 5.4 to 5.6+
* Breaking Change: Decouple the fonts from the plugin.

* 🎉 Feature: Option to enable background Process PDFs during form submission and while resending notifications. Requires background tasks are enabled [GH#713]
* 🎉 Feature: Include a Core Font Downloader in the PDF Tools to install all core PDF fonts during the initial installation [GH#709]
* 🎉 Feature: Updated ReactJS to v16 which uses MIT license [GH#701]
* 🎉 Feature: Add PHP7.2 Support [GH#716]
* 🎉 Feature: Polyfill older browsers to support our modern Javascript [GH#729]
* 🎉 Feature: Remove "Common Problems" link from PDF Help page and include "Common Questions" [GH#752]

* Dev: Update all Packagist-managed JS files to the latest version [GH#701]
* Dev: Upgrade Mpdf to version 7.1 (accessed directly via `\Mpdf\Mpdf`)
* Dev: Conditionally run `Model_PDF::maybe_save_pdf()` when Background Processing disabled [GH#713]
* Dev: Use wp_enqueue_editor() to load up the WP Editor assets [GH#754]
* Dev: Include file/line number when PDF error is thrown [GH#803]
* Dev: Remove the legacy /resources/ directory

* 🐞 Bug: Fix Chosen Drop Down display issue when WordPress using RTL display [GH#698]
* 🐞 Bug: Fix PHP Notice when Post Image field is blank [GH#805]
* 🐞 Bug: Correct A5 Label so it correctly references 148 x 210mm [GH#811]
* 🐞 Bug: Correct default en_US localization strings [GH#815] (credit Garrett Hyder)

## 4.5.0
* 🎉 Feature: Added full support for the Gravity Wiz Conditional Logic Date Plugin
* 🎉 Feature: Added full support for the Slim Image Cropper for Gravity Forms Plugin
* Dev Feature: Added additional actions that run before and after PDFs are generated.

## 4.4.0
* 🎉 Feature: Add native support for Gravity Forms Chained Select
* 🎉 Feature: Include Gravity Forms add-on conditional logic in PDF Conditional Logic selector
* 🎉 Feature: When the "Show Page Names" PDF setting is enabled, the `pagebreak` CSS class can now be used on Named Pagebreak fields (except the very first one)
* 🎉 Feature: PDF Rich Text fields now utilize the full width of the editor
* Dev Feature: Add $form_data API endpoint
* Dev Feature: Add the $form and $this variables to the `gfpdf_field_value` filter
* Dev Feature: Add `gfpdf_form_data_key_order` filter to allow the re-ordering of the $form_data array
* Dev Feature: Add filter `gfpdf_container_disable_faux_columns` to allow faux columns to be toggled off (useful when using a lot of conditional logic with CSS Ready Classes)
* 🧹 Housekeeping: Update Monolog to latest version
* 🧹 Housekeeping: Instead of generic error, display `You do not have permission to view this PDF` when user failed PDF security checks
* 🧹 Housekeeping: Tweak the Help page to provide more relevant information.
* 🧹 Housekeeping: Reduce the Gravity PDF log file bloat, and add more specific log messages.
* 🧹 Housekeeping: Recursively clean-up the PDF temporary directory
* 🧹 Housekeeping: Limit the registration of PDF settings on Gravity PDF pages, and the admin options.php page
* 🐞 Bug: Prevent multiple calls running when a new template is installed/deleted and then selected
* 🐞 Bug: Pre-process any mergetags for the Checkbox, HTML, Post Content, Radio, Section, Textarea and Terms of Service Gravity Form fields
* 🐞 Bug: Fix individual quantity field $form_data
* 🐞 Bug: Ensure individual product fields (Product, Discount, Shipping, Subtotal, Tax and Total) display an empty value in the $form_data array, when necessary
* 🐞 Bug: Fix PDF Template Manager display issues for WordPress 4.8+
* 🐞 Bug: Adjust Logged out timeout default to 20 minutes to match documentation
* 🐞 Bug: Fix PHP notice when pre-procesing the template settings
* 🐞 Bug: Fix Survey $form_data['survey']['score'] key
* 🐞 Bug: Fix the Gravity Perks E-Commerce Subtotal value in the $form_data array
* 🐞 Bug: Prevent TinyMCE error when selecting a new template and other plugins define a custom TinyMCE plugin
* 🐞 Bug: Adjust PDF Template Upload limit from 5MB to 10MB
* 🐞 Bug: Fix Product field background color issue
* 🐞 Bug: Right-align prices in the product table
* 🐞 Bug: Fix PHP fatal error when PDF cannot be correctly saved to disk

## 4.3.2
* 🐞 Bug: Reverse pricing issue bug fix in 4.3.1 (under some circumstances it cause the incorrect Unit Price to be displayed in product table)
* 🐞 Bug: Fix Unit Price currency issue in the product table when using the Gravity Forms Multi Currency plugin
* 🐞 Bug: Fix empty line-items in the Product table when using the Gravity Wiz E-Commerce add-on with conditional logic

## 4.3.1
* 🐞 Bug: Restrict Gravity PDF JavaScript to the correct PDF pages (GH#693)
* 🐞 Bug: Fix PHP5.2 activation error (GH#697)
* 🐞 Bug: Fix RTL issue with Chosen Select library (GH#698)
* 🐞 Bug: Fix PDF Product table pricing issue by using the pre-calculated price field for the unit price (GH#699)

## 4.3.0
* 🎉 Feature: Add support for Gravity Perks E-Commerce Add-on (GH#671)
* Dev Feature: Add GPDFAPI::get_pdf_fonts() method
* Dev Feature: Add 'gfpdf_pdf_generator_pre_processing' filter
* Dev Feature: Add 'gfpdf_entry_pre_form_data' filter
* Dev Feature: Add Helper_Trait_Logger class to make it easier to inject our logger into new classes (GH#677)
* Dev Enhancement: Include the current object as a 5th parameter to 'gfpdf_pdf_field_content' filter
* Dev Enhancement: Include update message / additonal link helper functions for registered Gravity PDF add-ons (GH#673)
* Dev Enhancement: Update Easy Digital Download Licensing class to version 1.6.14
* Future Feature: After plugin updates, copy shipped Mpdf fonts to PDF Working Directory (preparation for removal of all fonts in future release) (GH#676)
* 🐞 Bug: Strip URL parameters from home_url(), if any, when building PDF URL (GH#674)
* 🐞 Bug: Load the correct PDF Template Configuration file when using 'template' helper param (GH#675)

## 4.2.2
* 🐞 Bug: Fix empty Master Sassword regression introduced in 4.2 (GH#664)
* 🐞 Bug: Fix Javascript errors when plugin translation files used (GH#667)
* 🐞 Bug: Fix PDF Conditional Logic saving problem when using 'Less than' (GH#668)
* 🐞 Bug: Fix PHP Notices when using custom font (GH#669)
* 🐞 Bug: Merge Mpdf upstream patches (includes Chrome Viewer Yellow hover fix)

## 4.2.1
* 🐞 Bug: Fix fatal DateTimeZone error for older versions of PHP (GH#654)

## 4.2.0
* 🎉 Feature: Merge tags and shortcodes are displayed in the PDF for any administrative fields (GH#633)
* 🎉 Feature: New field class 'pagebreak' forces a pagebreak in the PDF (GH#634)
* 🎉 Feature: Instead of the field not showing at all, Gravity Perks Terms of Conditions field now shows the text "Not accepted"
when user hasn't agreed to terms (GH#636)

* Dev Feature: Add premium add-on and licensing infrastructure (GH#619)
* Dev Feature: [gravitypdf] shortcode debug messages can be toggled on and off for users with the 'gravityforms_view_entries' capability (GH#627)
* Dev Feature: Add filter 'gfpdf_field_label' to modify the PDF field labels (GH#621)
* Dev Feature: Add filter 'gfpdf_pdf_field_content' to modify the field markup before content is wrapped in the PDF markup (GH#620)
* Dev Feature: Add filters 'gfpdf_get_pdf_display_list', 'gfpdf_get_pdf_url', 'gfpdf_get_active_pdfs', 'gfpdf_override_pdf_bypass',
'gfpdf_maybe_attach_to_notification', 'gfpdf_maybe_always_save_pdf', 'gfpdf_form_data' and 'gfpdf_preprocess_template_arguments' for
greater control over the core PDF functionality. (GH#622)
* Dev Feature: Fix master password being overridden on PDF save after v3 to v4 migration (GH#624)
* Dev Feature: Allow master password field to be shown in the UI with the 'gfpdf_enable_master_password_field' fitler (GH#624)
* Dev Feature: Swapped 'error' log to 'warning' log when template config file not found (GH#613)
* Dev Feature: Upgrade all NPM modules to latest versions. PDF Template Manager now renders faster (GH#631)
* Dev Feature: Remove hard dependancy on the Helper_Interface_Config interface for the template configuration file (GH#632)
* Dev Feature: Added 'gfpdf_field_middleware' filter to control when a field should be displayed in the core PDF templates (GH#635)
* Dev Feature: Greater access to the Field_Product class internals (GH#642)

* 🐞 Bug: Correctly exit the script when the PDF is downloaded / sent to the browser (GH#610)
* 🐞 Bug: Don't auto-redirect to welcome / update screen on plugin install or upgrade which resolves a cached redirect issue (GH#612)
* 🐞 Bug: Register two PDF endpoints to support both pretty and almost pretty permalinks at the same time (GH#614)
* 🐞 Bug: Fix [gravitypdf] shortcode display error in GravityView when wrapped in another shortcode (GH#628)
* 🐞 Bug: Add support for Gravity Forms 2.3 Merge Tags (GH#643)
* 🐞 Bug: Fix background image relative paths (GH#645)
* 🐞 Bug: Fix GravityView display issue when view is used on the front page (GH#639)
* 🐞 Bug: Don't show selected product options in the product field when not grouping products together in PDF (GH#646)
* 🐞 Bug: Fix edge case that caused PDF settings to be overridden when the form is updated (GH#648)

## 4.1.1
* 🐞 Bug: Add check to see if headers are already sent before trying to redirect to the welcome / update page (GH#601)
* 🐞 Bug: Fixed issue accessing the Advanced Template Manager in Safari browser (GH#603)
* 🐞 Bug: Ensure the Advanced Template Manager notice and error messages have the correct styles in the Form PDF Settings pages (GH#604)
* 🐞 Bug: Fix PDF generation problem using the legacy v3 URL structure (GH#605)

## 4.1.0
* 🎉 Feature: Advanced PDF Template Manager. Upload, View, Select and Delete PDF templates with ease (GH#486)
* 🎉 Feature: Add PDF Mergetags which output PDF URLs and compliment the [gravitypdf] shortcode which output HTML links (GH#404)
* 🎉 Feature: Add four-column CSS Ready Class support to core PDFs. Note: if you have run "Setup Custom Templates" you will need to re-run it to take advantage of this feature (GH#461)
* 🎉 Feature: Added support for the WP External Links plugin (GH#386)
* 🎉 Feature: Added filter to show radio, checkbox, select, multiselect and product field values in core PDF templates (GH#600)
* Enhancement: Gravity PDF Review Notice now only shows up on Gravity Forms pages (#528)
* Enhancement: Convert all strings to American format so they can be correctly translated using Glotpress (GH#525)
* Enhancement: Added Australian, New Zealand and UK language packs (GH#525)
* Enhancement: Add support for Gravity Forms 2.2 Logging Module (GH#596)
* Dev Feature: Added 'Author URI' and 'Tags' headers to PDF template files which get displayed in the Advanced Template Manager (GH#558)
* Dev Feature: Include $this as eighth parameter in 'gfpdf_field_html_value' filter (GH#549)
* Dev Feature: Add 'gfpdf_field_section_break_html' filter to returned Section Field HTML for the PDF (GH#548)
* Dev Feature: Add actions before and after the core template HTML is generated; 'gfpdf_pre_html_fields' and 'gfpdf_post_html_fields' respectively (GH#546)
* Dev Feature: Template PHP Configuration files can impliment setUp and TearDown interfaces which fire when templates are installed or deleted through the Advanced Template Manager (GH#545)
* Dev Feature: Added Font Create and Delete endpoints to API – GPDFAPI::add_pdf_font() and GPDFAPI::delete_pdf_font() (GH#541)
* Dev Feature: Allow Rich Text Editor height to be controlled through the 'size' property when used in template config (GH#540)
* Dev Feature: Allow images in radio buttons using the new `'class' => 'image-radio-buttons'` property when used in template config (GH#539)
* Dev Changes: Use Gravity Forms copy of Chosen JS (GH#563)
* Dev Changes: All production CSS and JS saved to /dest/ directory as part of Advanced Template Manager update
* Dev Changes: Standardized all AJAX Authentication so Nonce and Capability checks are easily checked (GH#538)
* Dev Changes: Rename all instances of "depreciated" with "deprecated" in our files and classes (GH#535)
* Dev Changes: Contact our localized JS data to camelCase (GH#532)
* Dev Changes: Utilized PHP5.4 array syntax in code (GH#521)
* 🐞 Bug: Reset Gravity Forms Merge Tag JS when PDF template changes (GH#551)
* 🐞 Bug: Fix incorrect variable reference to $include_list_styles which uses 'gfpdf_include_list_styles' to change the behaviour (GH#547)
* 🐞 Bug: Fix PHP notice in PDF when no products selected in form (GH#523)
* 🐞 Bug: Fix issue with Gravity PDF update screen showing and not showing at incorrect times (GH#514)
* 🐞 Bug: Fix false positive when checking if the PDF tmp directory is readable (GH#519)
* 🐞 Bug: Fix error when using GLOB_BRACE flag in glob() function (GH#562)
* 🐞 Bug: Remove OTF fonts from being uploaded due to poor support in Mpdf (GH#569)
* 🐞 Bug: Additional PHP7.1 fixes merged from upstream Mpdf package
* 🐞 Bug: Allow TTF file mime type to be correctly detected in WordPress 4.7.3 (GH#571)
* 🐞 Bug: Ensure PDF Delete dialog shows up after being previously 'canceled' (GH#588)
* 🐞 Bug: Ensure duplicate mergetags aren't included after PDF template change (GH#589)
* 🐞 Bug: Fix PHP Notice if there's no active capabilities for a role (GH#590)

## 4.0.6
* Correctly register our PDF link with the WP Rewrite API when "Almost Pretty" permalinks are active (GH#502)
* Correctly process mergetags in password field for Tier 2 PDF templates (GH#503)
* Allow mergetags to be saved in HTML attributes in our Header / Footer settings - DEV NOTE: all Rich Text Editor settings fields should be output with `wp_kses_post( $html )` (GH#492)
* Process mergetags before Header / Footer settings get passed to wp_kses_post() on output (GH#512)
* Renamed `check_wordpress()` method to `is_compatible_wordpress_version()` to prevent false positive using ConfigServer eXploit Scanner (GH#500)
* Explicitly set a forward slash after the home_url() when building PDF links (GH#511)
* Resolve incorrect page numbering in Mpdf's Table of Contents
* Change Helper_Misc->get_contrast() to choose white in more cases (GH#506)

## 4.0.5
* Add support for "Almost Pretty" permalinks for web servers that don't support Mod Rewrite (IIS) (GH#488)
* Add PHP 7.1 support – resolves two string-to-array issues (GH#495)
* Add <p> and <br> tags to Rich Text Paragraph field in PDF – using wpautop() (GH#490)
* Disable product table when enabling the 'individual_products' option in core templates (GH#493)

## 4.0.4
* Prevent Finder (Mac) and Ghostscript viewing / processing password-protected PDFs without a password (GH#467)
* Fix Font Manager display issues for users running a version of WP lower than 4.5 (GH#470)
* Ensure new lines in Header / Footer automatically convert to <p> or <br> tags using wpautop() (GH#472)
* Fix issue in $form_data where Radio / Checkbox fields wouldn't display site-owner entered HTML (GH#415)
* Fixed conflict with Enhanced Media Library plugin (GH#433)
* Fixed issue with encoded characters in saved PDF filename (GH#475)
* Fixed issue where PDF settings would always set to "active" when saved (GH#477)
* Fixed depreciation notice for multisites using WordPress 4.6 (GH#479)
* Apply esc_html() and esc_url() to PDF name and URL in admin area (GH#484)

## 4.0.3
* Fix incorrect product calculations when using decimal comma format eg. 1.000,50 (GH#442)
* Rename $config variable to $html_config in core templates (GH#451)
* Don't chain CSS in our default setters or set fixed font size in templates (GH#446)
* Fix display issues for certain characters with DejaVu Sans font family in PDFs (GH#456)
* Ensure QueryPath produces valid UTF-8 data after processing (GH#452)
* Re-running the Custom Template Setup will override working directory templates with same name (GH#457)
* Fixed legacy Name field PHP warnings (GH#448)
* Replace translations with their escaped function counterparts (GH#463)
* Duplicating PDFs will now be inactive by default (GH#458)
* Tweaked the "Show Page Names" field description (GH#449)

## 4.0.2
* Fixes issue displaying address fields in v4 PDFs (GH#429)
* Fixes internal logging issues and added Gravity Forms 1.1 support (GF#428)
* Fixes notice when form pagination information is not available (GH#437)
* Fixes notice when using GPDFAPI::product_table() on form that had no products (GH#438)
* Fixes caching issue with GravityView Enable Notifications plugin that caused PDF attachment not to be updated (GH#436)

## 4.0.1
* Fixes PHP notice when viewing PDF and Category field is empty (GH#419)
* Fixes PHP notice when viewing PDF and custom font directory is empty (GH#416)
* Fixes Font Manager / Help Search features due to Underscore.js conflict when PHP's deprecated ASP Tags enabled (GH#417)
* Allows radio and checkbox values to show HTML in PDFs (GH#415)
* Fixes PDF letter spacing issue with upper and lower case characters (GH#418)
* Fixes character display problems using core Arial font in PDFs (GH#420)
* Fixes documentation search error on PDF Help tab (GH#424)
* Add additional check when cleaning up TMP directory (GH#427)

## 4.0
* Minimum PHP version changed from PHP 5.2 to PHP 5.4. ENSURE YOUR WEB SERVER IS COMPATIBLE BEFORE UPDATING (Forms -> Settings -> PDF -> System Status)
* Minimum WordPress version changed from 3.9 to 4.2. ENSURE YOU ARE RUNNING THE MINIMUM VERISON OF WP BEFORE UPDATING (Forms -> Settings -> PDF -> System Status)
* Minimum Gravity Forms version changed from 1.8 to 1.9. ENSURE YOU ARE RUNNING THE MINIMUM VERISON OF GRAVITY FORMS BEFORE UPDATING (Forms -> Settings -> PDF -> System Status)
* Maintained backwards compatibility with v3 for 80% of users. Review our migration guide for additional information (https://gravitypdf.com/documentation/v4/v3-to-v4-migration/)
* Created full user interface for managing plugin settings. All settings are now stored in the database
* Overhaul PDF designs that ship with software. Now comes with 4 completely free templates (two are all-new and two are enhanced v3 favorites)
* Added CSS Ready class support in PDFs. Two and three column classes now work in PDF
* Users can apply conditional logic to PDFs via new UI
* Control font, size and color via new UI
* Control paper size of generated PDF via new UI
* Control advanced security settings via new UI
* Control customisable PDF template options via new UI
* Control PDF header / footers via UI
* Control PDF background via UI
* Change PDF format (PDF/A-1b or PDF/X-1a) via UI
* Password Protect PDF via UI and change end-user privilages
* Added [gravitypdf] shortcode to allow users to display PDF links on confirmation pages, notifications, or anywhere else
* Allow user to change the action of the PDF link view in admin area (view or download)
* Added timeout parameter when unauthenticated user who submitted the form (matched by IP) attempts to access PDF. Defaults to 20 minutes
* Added ability to make a PDF "public". This disabled all security precautions on PDF. Use with caution.
* Deprecated configuration.php and created a migration feature which users can run if that file is detected. Removes /output/ directory during migration (where v3 stored PDFs saved to disk).
* Duplicating Gravity Form will also duplicate Gravity PDF settings assigned to that form. Importing / Exporting forms will also include PDF settings
* Better installation and upgrade experience for users with automated redirect to landing page after install / major update (can be disabled in settings)
* Created a font manager so users have a user interface to install and use their favorite fonts. Support for TTF and certain OTF font files
* Allow users to enable Right to Left language support from UI
* Created uninstaller which removes all trace of plugin from website
* Help tab allows users to live search our documentation
* Remove need to initialize the plugin when first installed
* Remove need to initialize fonts when uploaded to our /fonts/ directory
* Cleanup PDFs from disk when finished with them (also cleans up any stay files every 24 hours)
* Detect if our /tmp/ directory is accessible by browser and suggest ways to fix
* Allow all directories in /PDF_EXTENDED_TEMPLATES/ directory to be moved / renamed via filters (including the base directory)
* Create GPDFAPI class to allow devs to easily build ontop of plugin
* Cleaned up PDF template markup so developers can focus soley on their template code without any extra overhead. See our documentation for more details (https://gravitypdf.com/documentation/v4/developer-start-customising/)
* Enhanced PDF templates by allowing an image and configuration class
* Added large number of new actions and filters and provided documentation and examples for them on our website
* Allow developers to add or remove individual security layers via filters
* Updated mPDF from 5.7 to 6.1
* Added support for Gravity Forms Logging plugin
* Added better product data to $form_data['field']
* Added PHPDocs to all classes / methods / functions
* Fix PDF_EXTENDED_TEMPLATES location in legacy Multisite networks (WP3.5 or lower)
* Automatically make $field array available to PDF templates (array of current form fields accessible by field ID)
* Automatically make $settings array available to PDF templates (the current PDF configuration settigns)
* Automatically make $config array available to PDF templates (the initialized template config class - if any)
* Automatically make $form, $entry and $form_data available to PDF templates
* Automatically make $gfpdf object available to PDF templates (the main Gravity PDF object containing all our helper classes)

## 3.7.7
* Bug - Ensure 'gfpdf_post_pdf_save' action gets triggered for all PDFs when resending notifications
* Housekeeping - Remove compress.php from mPDF package (unneeded)

## 3.7.6
* Bug - Added full support for all Gravity Forms notification events (includes Payment Complete, Payment Refund, Payment Failed, Payment Pending ect)
* Bug - Resolve mPDF PHP7 image parsing error due to a change in variable order parsing.

## 3.7.5
* Housekeeping - Tweak mPDF package to be PHP7 compatible.

## 3.7.4
* Housekeeping - Revert patch made in last update as Gravity Forms 1.9.9 fixes the issue internally.

## 3.7.3
* Bug - Gravity Forms 1.9 didn't automatically nl2br paragraph text mergetags. Fixed this issue in custom PDF templates.

## 3.7.2
* Bug - Updated $form_data['date_created'], $form_data['date_created_usa'], $form_data['misc']['date_time'], $form_data['misc']['time_24hr'] and $form_data['misc']['time_12hr'] to factor in the website's timezone settings.

## 3.7.1
* Housekeeping - Allow control over signature width in default template using the 'gfpdfe_signature_width' filter
* Housekeeping - Add better error checking when migrating PDF template folder
* Housekeeping - Add unit testing to the directory migration function
* Bug - Fixed backwards-compatiiblity PHP error when viewing custom PDF templates on Gravity Forms 1.8.3 or below.
* Bug - Ensure checkbox field names are included in the $form_data array

## 3.7.0
* Feature - Added 'default-show-section-content' configuration option. You can now display the section break content in the default template. Note: if this option is enabled and the section break is empty it will still be displayed on the PDF.
* Feature - Added hooks 'gfpdfe_template_location' and 'gfpdfe_template_location_uri' to change PDF template location
* Housekeeping - Migrate your template and configuration files. As of Gravity PDF 3.7 we'll be dropping the 'site_name' folder for single WordPress installs and changing the multisite install directory to the site ID.
* Housekeeping - Added $form_data['html_id'] key which has the HTML fields added by their ID (much like the signature_details_id key).
* Housekeeping - Add large number of unit tests
* Housekeeping - Derestrict certain pages software loads on.
* Housekeeping - Split up PDF viewing security components into smaller chunks (easier to unit test)
* Housekeeping - Remove CLI-checking override in RAM settings
* Housekeeping - Included directory paths by default on the system status page
* Housekeeping - Updated configuration.php examples to include new default config option and refined the copy
* Bug - Fixed issue initializing plugin when memory limit was set to -1 (unlimited)
* Bug - Fix Multisite migration problem where if an error was thrown for one of the sub sites it caused all of the sites to show an error (even if they were successful)
* Bug - Fix typo in example-template.php file
* Bug - Fix up notices in custom templates when using poll/survey/quiz add ons.
* Bug - Fix up notice in custom template when the form description is empty
* Bug - Fix up notices in mPDF template when using headers/footers
* Bug - Fix up error in PDF when signature field wasn't filled in

## 3.6.0
* Feature - Added support for Gravity Form's sub-field middle name  (1.9Beta)
* Feature - Patch mPDF with full :nth-child support on TD and TR table cells
* Feature - Added $form_data[products_totals][subtotal] key (total price without shipping costs added)
* Feature - Added formated money to all product fields in the $form_data array
* Feature - Default templates: only show fields who's conditional logic is true. Perfect when used with default-show-html
* Housekeeping - Move PDF_EXTENDED_TEMPLATES folder to the /wp-content/upload/ directory. Get more info about the move (see http://developer.gravitypdf.com/news/migrating-template-directory-means/)
* Housekeeping - Refined when admin resources are loaded
* Housekeeping - Fixed typo during initial initialization
* Housekeeping - Switched icons back to FontAwesome which is shipped by default with Gravity Forms
* Housekeeping - Display full path to mPDF tmp directory when there are issues writing to it
* Housekeeping - Modified font installation message.
* Housekeeping - Update example-header-and-footer_06.php and example-advanced-headers_07.php to better reflect current mPDF features
* Bug - Fixed issue pulling the correct configuration when multiple nodes were assigned to multiple forms
* Bug - Fixed number field formatting issue which always rounded to two decimal places
* Bug - Fixed JS namespace issue with WordPress Leads plugin
* Bug - Fixed error initializing fonts / backing up PDF_EXTENDED_TEMPLATES directory when using the glob() function
* Bug - Fix issue with PHP 5.0 and 5.1 array_replace_recursive function when used with an array inside the $gf_pdf_config array
* Bug - Fixed fatal error when logged in user attempts to view PDF they don't have access to
* Bug - Fixed issue in $form_data array where single-column list items where being returned as an array and not a HTML list.
* Bug - Prevent unauthorized users auto-initializing the software or migrating the templates folder
* Bug - Fixed up incorrect formatting issue when using custom font name
* Bug - Fixed issue displaying Times New Roman in PDF templates

## 3.5.11.1
* Bug - Fix issue saving and sending blank PDFs due to security fix

## 3.5.11
* Bug - Fix security issue which gave unauthorized users access to Gravity Form entires

## 3.5.10
* Housekeeping - Include individual scoring for Gravity Form Survey Likert field in the $form_data['survey'] array
* Bug - Fix fatal error when Gravity Forms isn't activated, but Gravity PDF is.

## 3.5.9
* Bug - Rollback recent changes that introduced the GFAPI as introduces errors for older versions of Gravity Forms. Will reintroduce in next major release and increase the minimum Gravity Forms version.

## 3.5.8
* Bug - Fixed issue affected some users where a depreciated function was causing a fatal error

## 3.5.7
* Bug - Fixed issue where the PDF settings page was blank for some users

## 3.5.6
* Bug - Fixed issue with last release that affected checks to see if Gravity Forms has submitting
* Bug - Fixed fatal error with servers using PHP5.2 or lower
* Bug - Fixed E_NOTICE for replacement array_replace_recursive() function in PHP5.2 or lower
* Bug - Fixed issue with AJAX spinner showing when submitting support request

## 3.5.5
* Housekeeping - Include French translation (thanks to Marie-Aude Koiransky-Ballouk)
* Housekeeping - Wrap 'Initialize Fonts' text in translation ready _e() function
* Housekeeping - Tidy up System Status CSS styles to accomidate translation text lengths
* Housekeeping - Fix E_NOTICE when viewing entry details page when form has no PDF configuration
* Bug - Fixed load_plugin_textdomain which was incorrectly called.
* Bug - Correctly check if the plugin is loaded correctly before letting the PDF class fully load

## 3.5.4
* Bug - Fixed issue with incorrect PDF name showing on the entry details page
* Bug - Fixed issue with custom fonts being inaccessible without manually reinstalling after upgrading.
* Housekeeping - Added in two new filters to modify the $mpdf object. 'gfpdfe_mpdf_class' and 'gfpdfe_mpdf_class_pre_render' (replaces the gfpdfe_pre_render_pdf filter).

## 3.5.3
* Bug - Mergetags braces ({}) were being encoded before conversion
* Bug - Fixed issue with empty string being passed to array filter
* Housekeeping - Enabled mergetag usage in the pdf_password and pdf_master_password configuration options
* Housekeeping - Correctly call $wpdb->prepare so the variables in are in the second argument

## 3.5.2
* Bug - Initialization folder .htaccess file was preventing template.css from being loaded by the default templates.

## 3.5.1
* Bug - Fixed issue with core fonts Arial/Helvetica, Times/Times New Roman and Courier not displaying in the PDF.
* Bug - Fixed display issues for multiple PDFs on the details admin entry page
* Housekeeping - Made the details entry page PDF view consistent for single or multiple PDFs
* Housekeeping - Ensured all javascript files are minified and are correctly being used
* Housekeeping - Remove legacy notices from mPDF package

## 3.5.0
* Feature - No longer need to reinitialize every time the software is updated.
* Feature - Add auto-initializer on initial installation for sites that have direct write access to their server files
* Feature - Add auto-initializer on initial installation across entire multisite network for sites who have direct write access to their server files.
* Feature - Add auto-PDF_EXTENDED_TEMPLATES theme syncer for sites that have direct write access to their server files
* Feature - Correctly added language support. The .PO file is located in the /language/ folder if anyone would like to do a translation.

* Housekeeping - Restrict initialization process to 64MB or greater to counter problems with users reporting a 'white screen' when running in a low-RAM environment.
* Housekeeping - Refractor the admin notices code
* Housekeeping - Create responsive PDF settings page
* Housekeeping - Minify CSS and Javascript files
* Housekeeping - Remove FontAwesome fonts from package and use Wordpress' build-in 'dashicons'
* Housekeeping - Refine action and error messages
* Housekeeping - Update initialization tab copy for both pre- and post- initialization
* Housekeeping - Use Gravity Forms get_ip() function instead of custom function
* Housekeeping - The in-built support form uses SSL once again (disabled in the past due to some servers being unable to verify the certificate).

* Bug - When testing write permissions, file_exist() is throwing false positives for some users which would generate a warning when unlink() is called. Hide warning using '@'.

## 3.4.1
* Bug - Fix typo that effected sites running PHP5.2 or below.

## 3.4.0.3
* Bug - Define array_replace_recursive() if it doesn't exist, as it is PHP 5.3 only.

## 3.4.0.2
* Housekeeping - Wrapped the View PDF and Download buttons in correct language functions - _e()
* Bug - Fix problem displaying the signature field
* Bug - Fix notice errors with new 'save' PDF hook

## 3.4.0.1
* Housekeeping - Add commas on the last line of every config node in the configuration.php file
* Housekeeping - Fix up initialization error messages
* Bug - Fix up mPDF bugs - soft hyphens, watermarks over SVG images, inline CSS bug

## 3.4.0
* Feature - Added auto-print prompt ability when you add &print=1 to the PDF URL (see https://developer.gravitypdf.com/documentation/display-pdf-in-browser/ for details)
* Feature - Added ability to rotate absolute positioned text 180 degrees (previously only 90 and -90). Note: feature in beta
* Feature - Backup all template files that are overridden when initializing to a folder inside PDF_EXTENDED_TEMPLATES
* Feature - Added SSH initialization support
* Feature - Allow MERGETAGS to be used in all PDF templates, including default template (but only in the HTML field).
* Feature - Updated mPDF to 3.7.1
* Feature - Enable text/image watermark support. Added new example template example-watermark09.php showing off its usage (see http://gravitypdf.com/documentation-v3-x-x/templates/watermarks/)
* Feature - Added full survey, poll and quiz support to both the default template and $form_data (see https://developer.gravitypdf.com/documentation/accessing-survey-poll-quiz-data/)
* Feature - Shortcodes will now be processed in all templates, including default template (but only in the HTML field).
* Feature - Added 'save' configuration option so PDFs are saved to the local disk when 'notifications' aren't enabled.
* Feature - Added 'dpi' configuration option to modify the PDF image DPI. Default 96dpi. Use 300dpi for printing.
* Feature - Added PDF/A1-b compliance option. Enable with 'pdfa1b' => true. See http://mpdf1.com/manual/index.php?tid=420&searchstring=pdf/a1-b for more details.
* Feature - Added PDF/X1-a compliance option. Enable with 'pdfx1a' => true. See http://mpdf1.com/manual/index.php?tid=481&searchstring=pdf/x-1a for more details.
* Feature - Added new constant option 'PDF_REPACK_FONT' which when enabled may improve function with some PostScript printers (disabled by default). Existing sites will need to add  define('PDF_REPACK_FONT', true); to the bottom of their configuration.php file.
* Feature - Added a sleuth of new hooks and filters for developers. See https://developer.gravitypdf.com/documentation/filters-and-hooks/ for examples.
* Feature - Added $form_data['form_description'] key to $form_data array
* Feature - Update $form_data['products'] array key to field ID
* Feature - Added survey Likert output function for custom templates (much like the product table function). It can be used with the following command 'echo GFPDFEntryDetails::get_likert($form, $lead, $field_id);' where $field_id is substituted for the form field ID.
* Feature - Added field descriptions to the $form_data array under the $form_data['field_descriptions'] key.
* Feature - Added pre and post PDF generation filters and actions to pdf-render.php. These include gfpdfe_pre_render_pdf, gfpdfe_pdf_output_type, gfpdfe_pdf_filename and gfpdf_post_pdf_save.
* 🎉 Feature: $form_data['signature'] et al. keys now contain the signature width and height attributes

* Housekeeping - Ensure the form and lead IDs are correctly passed throughout the render functions.
* Housekeeping - Update settings page link to match new Gravity Forms URL structure
* Housekeeping - Check if $lead['gfsurvey_score'] exists before assigning to $form_data array
* Housekeeping - Removed table and font checksum debugging from mPDF when WP_DEBUG enabled as they produced inaccurate results.
* Housekeeping - Fixed up mPDF logging location when WP_DEBUG enabled. Files now stored in wp-content/themes/Active_Theme_Folder/PDF_EXTENDED_TEMPLATES/output/ folder.
* Housekeeping - Removed API logging locally when WP_DEBUG is enabled.
* Housekeeping - Increase API timeout interval as some overseas users reported timeout issues
* Housekeeping - Modified mPDF functions Image() and purify_utf8_text() to validate the input data so we don't have to do it every time through the template.
* Housekeeping - Added ability to not re-deploy every update (not enabled this release as template files were all updated)
* Housekeeping - Additional checks on load to see if any of the required file/folder structure is missing. If so, re-initialize.
* Housekeeping - Save resources and turn off automatic rtl identification. Users must set the RTL option when configuring form
* Housekeeping - Turn off mPDFs packTableData setting, decreasing processing time when working with large tables.
* Housekeeping - $gf_pdf_default_configuration options now merge down into existing PDF nodes, instead of applying to only unassigned forms. $gf_pdf_config settings override any in $gf_pdf_default_configuration
* Housekeeping - Center aligned Survey Likery field results
* Housekeeping - Partially refactored the pdf-entry-detail.php code
* Housekeeping - All default and example templates have been tidied. This won't affect custom templates.
* Housekeeping - Set the gform_notification order number to 100 which will prevent other functions (example snippets from Gravity Forms, for instance) from overridding the attached PDF.
* Housekeeping - Fix spelling mistake on initializing fonts
* Housekeeping - Remove wpautop() function from Gravity Form HTML output, which was applied before rendering and was messing up the HTML markup.
* Housekeeping - Remove empty list rows from the $form_data['list'] array in single and multi-column lists.
* Housekeeping - Apply same CSS styles (padding, border and line height) to HTML fields as done to form values in default templates
* Housekeeping - Replaced arbitrary wrapper IDs in the default templates with the actual field ID

* Bug - Fixed signature rendering issue when custom signature size was being used
* Bug - Fixed static error types in helper/install-update-manager.php file.
* Bug - Fixed redeployment error message which wasn't showing correctly
* Bug - Fixed issue with PDF not attaching to notification using Paypal's delayed notification feature
* Bug - Fixed strict standard warning about calling GFPDF_Settings::settings_page();
* Bug - Fixed strict standard warning about calling GFPDFEntryDetail::pdf_get_lead_field_display();
* Bug - Fixed issue with Gravity Form Post Category field causing fatal error generating PDF
* Bug - Fixed number field formatting issue when displaying on PDF.
* Bug - Do additional check for PHP's MB_String regex functions before initializing to prevent errors after initializing
* Bug - Fixed problem with multiple nodes assigned to a form using the same template
* Bug - Fixed path to fallback templates when not found
* Bug - Fixed problem with master password setting to user password

## 3.3.4
* Bug - Fixed issue linking to PDF from front end
* Housekeeping - Removed autoredirect to initialization page

## 3.3.3
* Bug - Correctly call javascript to control admin area 'View PDFs' drop down
* Bug - Some users still reported incorrect RAM. Convert MB/KB/GB values to M/K/G as per the PHP documentation.
* Housekeeping - Show initilisation prompt on all admin area pages instead of only on the Gravity Forms pages

## 3.3.2.1
* Bug - Incorrectly showing assigned RAM to website

## 3.3.2
* Bug - Some hosts reported SSL certificate errors when using the support API. Disabled HTTPS for further investigation. Using hash-based verification for authentication.
* Housekeeping - Forgot to disable API debug feature after completing beta

## 3.3.1
* Bug - $form_data['list'] was mapped using an incremental key instead of via the field ID

## 3.3.0
* Feature - Overhauled the initialization process so that the software better reviews the host for potential problems before initialization. This should help debug issues and make users aware there could be a problem before they begin using the software.
* Feature - Overhauled the settings page to make it easier to access features of the software
* Feature - Added a Support tab to the settings page which allows users to securely (over HTTPS) submit a support ticket to the Gravity PDF support desk
* Feature - Changed select, multiselect and radio fields so that the default templates use the name rather than the value. $form_data now also includes the name and values for all these fields.
* Feature - $form_data now includes all miscellaneous lead information in the $form_data['misc'] array.
* Feature - $form_data now contains 24 and 12 hour time of entry submission.
* Feature - Added localisation support
* Compatibility - Added new multi-upload support which was added in Gravity Forms 1.8.
* Bug - Added 'aid' parametre to the PDF url when multiple configuration nodes present on a single form
* Bug - Fixed issue when Gravity Forms in No Conflict Mode
* Bug - Font config.php's array keys now in lower case
* Housekeeping - Moved all initialization files to a folder called 'initialization'.
* Housekeeping - Renamed the configuration.php file in the plugin folder to configuration.php.example to alleviate confusion for developers who unwittingly modify the plugin configuration file instead of the file in their active theme's PDF_EXTENDED_TEMPLATES folder.
* Housekeeping - Updated the plugin file system to a more MVC-style approach, with model and view folders.
* Housekeeping - Removed ability to directly access default and example template files.
* Housekeeping - Fixed PHP notices in default templates related to the default template-only configuration options
* Housekeeping - Update core styles to match Wordpress 3.8/Gravity Forms 1.8.
* Housekeeping - Updated header/footer examples to use @page in example.

## 3.2.0
* Feature - Can now view multiple PDFs assigned to a single form via the admin area. Note: You must provide a unique 'filename' parameter in configuration.php for multiple PDFs assigned to a single form.
* Feature - You can exclude a field from the default templates using the class name 'exclude'. See our [FAQ topic](https://gravitypdf.com/#faqs) for more details.
* Bug - Fixed issue viewing own PDF entry when logged in as anything lower than editor.
* Bug - Fixed data return bug in pdf-entry-details.php that was preventing all data returning correctly.
* Bug - Fixed PHP Warning when using products with no options
* Bug - Fixed issue with invalid characters being added to the PDF filename. Most notably the date mergetag.
* Bug - Limit filename length to 150 characters which should work on the majority of web servers.
* Bug - Fixed problem sending duplicate PDF when using mass resend notification feature
* Deprecated - Removed GF_FORM_ID and GF_LEAD_ID constants which were used in v2.x.x of the software. Ensure you follow [v2.x.x upgrade guide](https://developer.gravitypdf.com/news/version-2-3-migration-guide/) to your templates before upgrading.

## 3.1.4
* Bug - Fixed issue with plugin breaking website's when the Gravity Forms plugin wasn't activated.
* Housekeeping - The plugin now only supports Gravity Forms 1.7 or higher and Wordpress 3.5 or higher.
* Housekeeping - PDF template files can no longer be accessed directly. Instead, add &amp;html=1 to the end of your URL when viewing a PDF.
* Extension - Added additional filters to allow the lead ID and notifications to be overridden.

## 3.1.3
* Feature - Added signature_details_id to $form_data array which maps a signatures field ID to the array.
* Extension - Added pre-PDF generator filter for use with extensions.
* Bug - Fixed issue with quotes in entry data breaking custom templates.
* Bug - Fixed issue with the plugin not correctly using the new default configuration template, if set.
* Bug - Fixed issue with signature not being removed correctly when only testing with file_exists(). Added second is_dir() test.
* Bug - Fixed issue with empty signature field not displaying when option 'default-show-empty' is set.
* Bug - Fixed initialization prompt issue when the MPDF package wasn't unpacked.

## 3.1.2
* Feature - Added list array, file path, form ID and lead ID to $form_data array in custom templates
* Bug - Fixed initialization prompt issue when updating plugin
* Bug - Fixed window.open issue which prevented a new window from opening when viewing a PDF in the admin area
* Bug - Fixed issue with product dropdown and radio button data showing the value instead of the name field.
* Bug - Fixed incorrect URL pointing to signature in $form_data

## 3.1.1
* Bug - Users whose server only supports FTP file manipulation using the WP_Filesystem API moved the files into the wrong directory due to FTP usually being rooted to the Wordpress home directory. To fix this the plugin attempts to determine the FTP directory, otherwise assumes it is the WP base directory.
* Bug - Initialization error message was being called but the success message was also showing.

## 3.1.0
* Feature - Added defaults to configuration.php which allows users to define the default PDF settings for all Gravity Forms. See the [installation and configuration documentation](https://developer.gravitypdf.com/documentation/getting-started-with-gravity-pdf-configuration/) for more details.
* Feature - Added three new configuration options 'default-show-html', 'default-show-empty' and 'default-show-page-names' which allow different display options to the three default templates. See the [installation and configuration documentation](http://gravitypdf.com/documentation-v3-x-x/installation-and-configuration/#default-template) for more details.
* Feature - Added filter hooks 'gfpdfe_pdf_name' and 'gfpdfe_template' which allows developers to further modify a PDF name and template file, respectively, outside of the configuration.php. This is useful if you have a special case naming convention based on user input. See [https://developer.gravitypdf.com/documentation/filters-and-hooks/](https://developer.gravitypdf.com/documentation/filters-and-hooks/) for more details about using these filters.
* Feature - Custom font support. Any .ttf font file added to the PDF_EXTENDED_TEMPLATES/fonts/ folder will be automatically installed once the plugin has been initialized. Users also have the option to just initialize the fonts via the settings page. See the [font/language documentation ](https://developer.gravitypdf.com/documentation/language-support/#install-custom-fonts) for details.
* Compatability - Use Gravity Forms get_upload_root() and get_upload_url_root() instead of hard coding the signature upload directory in pdf-entry-detail.php
* Compatability - Changed deprecated functions get_themes() and get_theme() to wp_get_theme() (added in Wordpress v3.4).
* Compatability - The plugin now needs to be initialized on fresh installation and upgrade. This allows us to use the WP_Filesystem API for file manipulation.
* Compatability - Automatic copying of PDF_EXTENDED_TEMPLATES folder on a theme change was removed in favour of a user prompt. This allows us to take advantage of the WP_Filesystem API.
* Compatability - Added Wordpress compatibility checker (minimum now 3.4 or higher).
* Bug - Removed ZipArchive in favour of Wordpress's WP_Filesystem API unzip_file() command. Some users reported the plugin would stop their entire website working if this extension wasn't installed.
* Bug - Fixed Gravity Forms compatibility checker which wouldn't return the correct response.
* Bug - Fixed minor bug in pdf.php when using static call 'self' in add_filter hook. Changed to class name.
* Bug - Removed PHP notice about $even variable not being defined in pdf-entry-detail.php
* Bug - Prevent code from continuing to excecute after sending header redirect.

## 3.0.2
* Backwards Compatibility - While PHP 5.3 has was released a number of years ago it seems a number of hosts do not currently offer this version to their clients. In the interest of backwards compatibility we've re-written the plugin to again work with PHP 5+.
* Signature / Image Display Bug - All URLs have been converted to a path so images should now display correctly in PDF.

## 3.0.1
* Bug - Fixed issue that caused website to become unresponsive when Gravity Forms was disabled or upgraded
* Bug - New HTML fields weren't being displayed in $form_data array
* Feature - Options for default templates to disable HTML fields or empty fields (or both)

## 3.0.0
As of Gravity PDF v3.0.0 we have removed the DOMPDF package from our plugin and integrated the more advanced mPDF system. Along with a new HTML to PDF generator, we've rewritten the entire plugin's base code to make it more user friendly to both hobbyists and rock star web developers. Configuration time is cut in half and advanced features like adding security features is now accessible to users who have little experience with PHP.

New Features include:

* Language Support - almost all languages are supported including RTL (right to left) languages like Arabic and Hebrew and CJK languages - Chinese, Japanese and Korean.
* HTML Page Numbering
* Odd and even paging with mirrored margins (most commonly used in printing).
* Nested Tables
* Text-justification and hyphenation
* Table of Contents
* Index
* Bookmarks
* Watermarks
* Password protection
* UTF-8 encoded HTML
* Better system resource handling

A new HTML to PDF package wasn't the only change to this edition of the software. We have rewritten the entire configuration system and made it super easy to get the software up and running.

Users will no longer place code in their active theme's functions.php file. Instead, configuration will happen in a new file called configuration.php, inside the PDF_EXTENDED_TEMPLATES folder (in your active theme).

Other changes include
* Improved security - further restrictions were placed on non-administrators viewing template files.
* $form_data array tidied up - images won't be wrapped in anchor tags.

For more details [view the 3.x.x online documentation](https://developer.gravitypdf.com/).

## 2.2.3
* Bug - Fixed mb_string error in the updated DOMPDF package.

## 2.2.2
* DOMPDF - We updated to the latest version of DOMPDF - DOMPDF 0.6.0 beta 3.
* DOMPDF - We've enabled font subsetting by default which should help limit the increased PDF size when using DejaVu Sans (or any other font).

## 2.2.1
* Bug - Fixed HTML error which caused list items to distort on PDF

## 2.2.0
* Compatibility - Ensure compatibility with Gravity Forms 1.7. We've updated the functions.php code and remove gform_user_notification_attachments and gform_admin_notification_attachments hooks which are now deprecated. Functions gform_pdf_create and gform_add_attachment have been removed and replaced with gfpdfe_create_and_attach_pdf(). See upgrade documentation for details.
* Enhancement - Added deployment code switch so the template redeployment feature can be turned on and off. This release doesn't require redeployment.
* Enhancement - PDF_Generator() variables were getting long and complex so the third variable is now an array which will pass all the optional arguments. The new 1.7 compatible functions.php code includes this method by default. For backwards compatibility the function will still work with the variable structure prior to 2.2.0.
* Bug - Fixed error generated by legacy code in the function PDF_processing() which is located in render_to_pdf.php.
* Bug - Images and stylesheets will now try and be accessed with a local path instead of a URL. It fixes problem where some hosts were preventing read access from a URL. No template changes are required.

## 2.1.1
* Bug - Signatures stopped displaying after 2.1.0 update. Fixed issue.
* Bug - First time install code now won't execute if already have configuration variables in database

## 2.1.0

* Feature - Product table can now be accessed directly through custom templates by running GFPDFEntryDetail::product_table($form, $lead);. See documentation for more details.
* Feature - Update screen will ask you if you want to deploy new template files, instead of overriding your modified versions.
* Feature - Product subtotal, shipping and total have been added to $form_data['field'] array to make it easier to work with product details in the custom template.
* Feature - Added two new default template files. One displays field and name in two rows (like you see when viewing an entry in the admin area) and the other removes all styling. See documentation on use.
* Security - Tightened PDF template security so that custom templates couldn't be automatically generated by just anyone. Now only logged in users with the correct privileges and the user who submitted the form (matched against IP) can auto generate a PDF. See documentation on usage.
* Deprecated - Removed form data that was added directly to the $form_data array instead of $form_data['field'] array. Users upgrading will need to update their custom templates if not using field data from the $form_data[�field'] array. If using $form_data['field'] in your custom template this won't affect you.
* Bug - Fixed problem with default template not showing and displaying a timeout error. Removed table tags and replaced with divs that are styled appropriately.
* Bug - The new plugin theme folder will successfully create when upgrading. You won't have to deactivate and reactivate to get it working.
* Bug - some installs had plugins that included the function mb_string which is also included in DOMPDF. DOMPDF will now check if the function exists before creating it.
* Bug - Remove empty signature field from the default template.
* Bug - fixed problem with redirecting to login screen even when logged in while accessing template file through the browser window directly.
* Bug - fixed error where sample template would reimport itself automatically even after deleting it. Will now only reimport if any important changes to template need to be viewed straight after an update.
* Bug - Moved render_to_pdf.php constants to pdf.php so we can use the constants in the core files. Was previously generating an error.
* Housekeeping - Cleaned up core template files, moved functions into classes and added more in-file documentation.
* Housekeeping - moved install/upgrade code from pdf.php to installation-update-manager.php
* Housekeeping - changed pdf-entry-detail.php class name from GFEntryDetail to GFPDFEntryDetail to remove compatibility problems with Gravity Forms.
* Housekeeping - created pdf-settings.php file to house the settings page code.

## 2.0.1
* Fixed Signature bug when checking if image file exists using URL instead of filesystem path
* Fixed PHP Constants Notice

## 2.0.0
* Moved templates to active theme folder to prevent custom themes being removed on upgrade
* Allow PDFs to be saved using a custom name
* Fixed WP_Error bug when image/css file cannot be found
* Upgraded to latest version of DOMPDF
* Removed auto-load form bug which would see multiple instances of the example form loaded
* Created a number of constants to allow easier developer modification
* Plugin/Support moved to dedicated website.
* Pro/Business package offers the ability to write fields on an existing PDF.

## 1.2.3
* Fixed $wpdb->prepare error

## 1.2.2
* Fixed bug with tempalte shipping method MERGETAGS
* Fixed bug where attachment wasn't being sent
* Fixed problem when all_url_fopen was turned off on server and failed to retreive remote images. Now uses WP_HTTP class.

## 1.2.1
* Fixed path to custom css file included in PDF template

## 1.2.0
* Template files moved to the plugin's template folder
* Sample Form installed so developers have a working example to modify
* Fixed bug when using WordPress in another directory to the site

## 1.1.0
* Now compatible with Gravity Forms Signature Add-On
* Moved the field data functions out side of the Gravity Forms core so users can freely style their form information (located in pdf-entry-detail.php)
* Simplified the field data output
* Fixed bug when using product information

## 1.0.0
* First release.
