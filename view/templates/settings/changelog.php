<?php

 /*
  Template: Changelog
  Module: Settings Page
  */

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

  /*
   <li>Don't run if the correct class isn't present
   */
  if(!class_exists('GFPDF_Settings_Model'))
  {
	 exit;
  }

  ?>

    <h2><?php _e('Changelog'); ?></h2>

    <p><strong>Current Version: <?php echo PDF_EXTENDED_VERSION; ?></strong></p>

  <h3><?php echo PDF_EXTENDED_VERSION; ?></h3>
    <ul>
        <li>Feature - Add support for Gravity Forms 2.0 Rich Text Editor field</li>
        <li>Feature - Hide Gravity PDF major upgrade prompts when compatibility checks fail</li>
    </ul>

<h3>3.7.7</h3>
  <ul>
      <li>Bug - Ensure 'gfpdf_post_pdf_save' action gets triggered for all PDFs when resending notifications</li>
      <li>Housekeeping - Remove compress.php from mPDF package (unneeded)</li>
  </ul>

<h3>3.7.6</h3>
  <ul>
    <li>Bug - Added full support for all Gravity Forms notification events (includes Payment Complete, Payment Refund, Payment Failed, Payment Pending ect)</li>
    <li>Bug - Resolve mPDF PHP7 image parsing error due to a change in variable order parsing.</li>
  </ul>


  <h3>3.7.5</h3>
  <ul>
    <li>Tweak mPDF package to be PHP7 compatible.</li>
  </ul>

  <h3>3.7.4</h3>
  <ul>
    <li>Housekpeeing - Revert patch made in last update as Gravity Forms 1.9.9 fixes the issue internally.</li>
  </ul>

  <h3>3.7.3</h3>
  <ul>
    <li>Bug - Gravity Forms 1.9 didn't automatically nl2br paragraph text mergetags. Fixed this issue in custom PDF templates.</li>
  </ul>

  <h3>3.7.2</h3>

  <ul>
    <li>Bug - Updated $form_data['date_created'], $form_data['date_created_usa'], $form_data['misc']['date_time'], $form_data['misc']['time_24hr'] and $form_data['misc']['time_12hr'] to factor in the website's timezone settings. </li>
  </ul>


  <h3>3.7.1</h3>
  <ul>
    <li>Housekeeping - Allow control over signature width in default template using the 'gfpdfe_signature_width' filter</li>
    <li>Housekeeping - Add better error checking when migrating PDF template folder</li>
    <li>Housekeeping - Add unit testing to the directory migration function</li>
    <li>Bug - Fixed backwards-compatiiblity PHP error when viewing custom PDF templates on Gravity Forms 1.8.3 or below.</li>
    <li>Bug - Ensure checkbox field names are included in the $form_data array</li>
  </ul>

  <h3>3.7.0</h3>
<ul>
<li>Feature - Added 'default-show-section-content' configuration option. You can now display the section break content in the default template. If this option is enabled and the section break is empty it will still be displayed on the PDF. Note: Existing installations will have to reinitialise their templates with the 'Reinstall Default and Example Templates' option enabled.</li>
<li>Feature - Added hooks 'gfpdfe_template_location' and 'gfpdfe_template_location_uri' to change PDF template location</li>
<li>Housekeeping - Migrate your template and configuration files. As of Gravity PDF 3.7 we'll be dropping the 'site_name' folder for single WordPress installs and changing the multisite install directory to the site ID.</li>
<li>Housekeeping - Added $form_data['html_id'] key which has the HTML fields added by their ID (much like the signature_details_id key).</li>
<li>Housekeeping - Add large number of unit tests </li>
<li>Housekeeping - Derestrict certain pages software loads on.</li>
<li>Housekeeping - Split up PDF viewing security components into smaller chunks (easier to unit test)</li>
<li>Housekeeping - Remove CLI-checking override in RAM settings</li>
<li>Housekeeping - Included directory paths by default on the system status page</li>
<li>Housekeeping - Updated configuration.php examples to include new default config option and refined the copy</li>
<li>Bug - Fixed issue initialising plugin when memory limit was set to -1 (unlimited)</li>
<li>Bug - Fix Multisite migration problem where if an error was thrown for one of the sub sites it caused all of the sites to show an error (even if they were successful)</li>
<li>Bug - Fix typo in example-template.php file</li>
<li>Bug - Fix up notices in custom templates when using poll/survey/quiz add ons.</li>
<li>Bug - Fix up notice in custom template when the form description is empty</li>
<li>Bug - Fix up notices in mPDF template when using headers/footers</li>
<li>Bug - Fix up error in PDF when signature field wasn't filled in</li>
</ul>

  <h3>3.6.0</h3>
<ul>
<li>Feature - Added support for Gravity Form's sub-field 'middle' name  (1.9Beta)</li>
<li>Feature - Patch mPDF with full :nth-child support on TD and TR table cells</li>
<li>Feature - Added $form_data['products_totals']['subtotal'] key (total price without shipping costs added)</li>
<li>Feature - Added formated money to all product fields in the $form_data array</li>
<li>Feature - Default templates: only show fields who's conditional logic is true. Perfect when used with 'default-show-html'</li>
<li>Housekeeping - Move PDF_EXTENDED_TEMPLATES folder to the /wp-content/upload/ directory. Get more info about the move (see <a href="https://developer.gravitypdf.com/news/migrating-template-directory-means/">https://developer.gravitypdf.com/news/migrating-template-directory-means/</a>)</li>
<li>Housekeeping - Refined when admin resources are loaded</li>
<li>Housekeeping - Fixed typo during initial initialisation</li>
<li>Housekeeping - Switched icons back to FontAwesome which is shipped by default with Gravity Forms</li>
<li>Housekeeping - Display full path to mPDF tmp directory when there are issues writing to it</li>
<li>Housekeeping - Update example-header-and-footer_06.php and example-advanced-headers_07.php to better reflect current mPDF features</li>
<li>Bug - Fixed issue pulling the correct configuration when multiple nodes were assigned to multiple forms</li>
<li>Bug - Fixed number field formatting issue which always rounded to two decimal places</li>
<li>Bug - Fixed JS namespace issue with WordPress Leads plugin</li>
<li>Bug - Fixed error initialising fonts / backing up PDF_EXTENDED_TEMPLATES directory when using the glob() function</li>
<li>Bug - Fix issue with PHP 5.0 and 5.1 array_replace_recursive function when used with an array inside the $gf_pdf_config array</li>
<li>Bug - Fixed fatal error when logged in user attempts to view PDF they don't have access to</li>
<li>Bug - Fixed issue in $form_data array where single-column list items where being returned as an array and not a HTML list.</li>
<li>Bug - Prevent unauthorised users auto-initialising the software or migrating the templates folder</li>
</ul>


  <h3>3.5.10</h3>
  <ul>
    <li>Bug - Fix issue saving and sending blank PDFs due to security fix</li>
  </ul>
  <h3>3.5.11</h3>
  <ul>
    <li>Bug - Fix security issue which gave unauthorised users access to Gravity Form entires</li>
  </ul>

  <h3>3.5.10</h3>
  <ul>
    <li>Housekeeping - Include individual scoring for Gravity Form Survey Likert field in the $form_data['survey'] array</li>
    <li>Bug - Fix fatal error when Gravity Forms isn't activated, but Gravity PDF is.</li>
  </ul>

  <h3>3.5.9</h3>
  <ul>
    <li>Bug - Rollback recent changes that introduced the GFAPI as introduces errors for older versions of Gravity Forms. Will reintroduce in next major release and increase the minimum Gravity Forms version.</li>
  </ul>

  <h3>3.5.8</h3>
  <ul>
    <li>Bug - Fixed issue affected some users where a depreciated function was causing a fatal error </li>
  </ul>

  <h3>3.5.7</h3>
  <ul>
    <li>Bug - Fixed issue where the PDF settings page was blank for some users</li>
  </ul>


  <h3>3.5.6</h3>
  <ul>
    <li>Bug - Fixed issue with last release that affected checks to see if Gravity Forms has submitting</li>
    <li>Bug - Fixed fatal error with servers using PHP5.2 or lower</li>
    <li>Bug - Fixed E_NOTICE for replacement array_replace_recursive() function in PHP5.2 or lower</li>
    <li>Bug - Fixed issue with AJAX spinner showing when submitting support request</li>
  </ul>

  <h3>3.5.5</h3>
  <ul>
    <li>Housekeeping - Include French translation (thanks to Marie-Aude Koiransky-Ballouk)</li>
    <li>Housekeeping - Wrap 'Initialise Fonts' text in translation ready _e() function</li>
    <li>Housekeeping - Tidy up System Status CSS styles to accomidate translation text lengths</li>
    <li>Housekeeping - Fix E_NOTICE when viewing entry details page when form has no PDF configuration</li>
    <li>Bug - Fixed load_plugin_textdomain which was incorrectly called.</li>
    <li>Bug - Correctly check if the plugin is loaded correctly before letting the PDF class fully load</li>
  </ul>

  <h3>3.5.4</h3>
  <ul>
    <li>Bug - Fixed issue with incorrect PDF name showing on the entry details page</li>
    <li>Bug - Fixed issue with custom fonts being inaccessible without manually reinstalling after upgrading.</li>
    <li>Housekeeping - Added in two new filters to modify the $mpdf object. 'gfpdfe_mpdf_class' and 'gfpdfe_mpdf_class_pre_render' (replaces the gfpdfe_pre_render_pdf filter).</li>
  </ul>


<h3>3.5.3</h3>

<ul>
  <li>Bug - Mergetags braces ({}) were being encoded before conversion</li>
  <li>Bug - Fixed issue with empty string being passed to array filter</li>
  <li>Housekeeping - Enabled mergetag usage in the pdf_password and pdf_master_password configuration options</li>
  <li>Housekeeping - Correctly call $wpdb->prepare so the variables in are in the second argument</li>
</ul>

<h3>3.5.2</h3>
<ul>
  <li>Bug - Initialisation folder .htaccess file was preventing template.css from being loaded by the default templates.</li>
</ul>

<h3>3.5.1</h3>

<ul>
  <li>Bug - Fixed issue using core fonts Arial/Helvetica, Times/Times New Roman and Courier.</li>
  <li>Bug - Fixed display issues for multiple PDFs on the details admin entry page</li>
  <li>Housekeeping - Made the details entry page PDF view consistent for single or multiple PDFs</li>
  <li>Housekeeping - Ensured all javascript files are minified and are correctly being used</li>
  <li>Housekeeping - Remove legacy notices from mPDF package</li>
</ul>

<h3>3.5.0</h3>

    <ul>
      <li>Feature - No longer need to reinitialise every time the software is updated. </li>
      <li>Feature - Add auto-initialiser on initial installation for sites that have direct write access to their server files</li>
      <li>Feature - Add auto-initialiser on initial installation across entire multisite network for sites who have direct write access to their server files. </li>
      <li>Feature - Add auto-PDF_EXTENDED_TEMPLATES theme syncer for sites that have direct write access to their server files</li>
      <li>Feature - Correctly added language support. The .PO file is located in the /language/ folder if anyone would like to do a translation.</li>

      <li>Housekeeping - Restrict initialisation process to 64MB or greater to counter problems with users reporting a 'white screen' when running in a low-RAM environment.</li>
      <li>Housekeeping - Refractor the admin notices code</li>
      <li>Housekeeping - Create responsive PDF settings page</li>
      <li>Housekeeping - Minify CSS and Javascript files </li>
      <li>Housekeeping - Remove FontAwesome fonts from package and use Wordpress' build-in 'dashicons'</li>
      <li>Housekeeping - Refine action and error messages </li>
      <li>Housekeeping - Update initialisation tab copy for both pre- and post- initialisation</li>
      <li>Housekeeping - Use Gravity Forms get_ip() function instead of custom function</li>
      <li>Housekeeping - The in-built support form uses SSL once again (disabled in the past due to some servers being unable to verify the certificate). </li>
      <li>Bug - When testing write permissions, file_exist() is throwing false positives for some users which would generate a warning when unlink() is called. Hide warning using '@'. </li>
    </ul>


    <h3>3.4.1</h3>
    <ul>
      <li>Bug - Fix typo that effected sites running PHP5.2 or below.</li>
    </ul>

    <h3>3.4.0.3</h3>
    <ul>
      <li>Bug - Define array_replace_recursive() if it doesn't exist, as it is PHP 5.3 only. </li>
    </ul>

    <h3>3.4.0.2</h3>
    <ul>
      <li>Housekeeping - Wrapped the View PDF and Download buttons in correct language functions - _e()</li>
      <li>Bug - Fix problem displaying the signature field</li>
      <li>Bug - Fix notice errors with new 'save' PDF hook</li>
    </ul>

    <h3>3.4.0.1</h3>
    <ul>
      <li>Housekeeping - Add commas on the last line of every config node in the configuration.php file</li>
      <li>Housekeeping - Fix up initialisation error messages</li>
      <li>Bug - Fix up mPDF bugs - soft hyphens, watermarks over SVG images, inline CSS bug</li>
    </ul>


    <h3>3.4.0</h3>
    <ul>
      <li>Feature - Added auto-print prompt ability when you add &amp;print=1 to the PDF URL (see https://developer.gravitypdf.com/documentation/display-pdf-in-browser/ for details)</li>
      <li>Feature - Added ability to rotate absolute positioned text 180 degrees (previously only 90 and -90). Note: feature in beta</li>
      <li>Feature - Backup all template files that are overridden when initialising to a folder inside PDF_EXTENDED_TEMPLATES</li>
      <li>Feature - Added SSH initialisation support</li>
      <li>Feature - Allow MERGETAGS to be used in all PDF templates, including default template (but only in the HTML field).</li>
      <li>Feature - Updated mPDF to 3.7.1</li>
      <li>Feature - Enable text/image watermark support. Added new example template example-watermark09.php showing off its usage (see https://developer.gravitypdf.com/documentation/watermarks-pdf-template-example/)</li>
      <li>Feature - Added full survey, poll and quiz support to both the default template and $form_data (see https://developer.gravitypdf.com/documentation/accessing-survey-poll-quiz-data/)</li>
      <li>Feature - Shortcodes will now be processed in all templates, including default template (but only in the HTML field).</li>
      <li>Feature - Added 'save' configuration option so PDFs are saved to the local disk when 'notifications' aren't enabled.</li>
      <li>Feature - Added 'dpi' configuration option to modify the PDF image DPI. Default 96dpi. Use 300dpi for printing.</li>
      <li>Feature - Added PDF/A1-b compliance option. Enable with 'pdfa1b' => true. See http://mpdf1.com/manual/index.php?tid=420&searchstring=pdf/a1-b for more details.</li>
      <li>Feature - Added PDF/X1-a compliance option. Enable with 'pdfx1a' => true. See http://mpdf1.com/manual/index.php?tid=481&searchstring=pdf/x-1a for more details.</li>
      <li>Feature - Added new constant option 'PDF_REPACK_FONT' which when enabled may improve function with some PostScript printers (disabled by default). Existing sites will need to add  define('PDF_REPACK_FONT', true); to the bottom of their configuration.php file.</li>
      <li>Feature - Added a sleuth of new hooks and filters for developers. See https://developer.gravitypdf.com/documentation/filters-and-hooks/ for examples.</li>
      <li>Feature - Added $form_data['form_description'] key to $form_data array</li>
      <li>Feature - Update $form_data['products'] array key to field ID</li>
      <li>Feature - Added survey Likert output function for custom templates (much like the product table function). It can be used with the following command 'echo GFPDFEntryDetails::get_likert($form, $lead, $field_id);' where $field_id is substituted for the form field ID.</li>
      <li>Feature - Added field descriptions to the $form_data array under the $form_data['field_descriptions'] key.</li>
      <li>Feature - Added pre and post PDF generation filters and actions to pdf-render.php. These include gfpdfe_pre_render_pdf, gfpdfe_pdf_output_type, gfpdfe_pdf_filename and gfpdf_post_pdf_save.</li>
      <li>Feature: $form_data['signature'] et al. keys now contain the signature width and height attributes</li>

      <li>Housekeeping - Ensure the form and lead IDs are correctly passed throughout the render functions.</li>
      <li>Housekeeping - Update settings page link to match new Gravity Forms URL structure</li>
      <li>Housekeeping - Check if $lead['gfsurvey_score'] exists before assigning to $form_data array</li>
      <li>Housekeeping - Removed table and font checksum debugging from mPDF when WP_DEBUG enabled as they produced inaccurate results.</li>
      <li>Housekeeping - Fixed up mPDF logging location when WP_DEBUG enabled. Files now stored in wp-content/themes/Active_Theme_Folder/PDF_EXTENDED_TEMPLATES/output/ folder.</li>
      <li>Housekeeping - Removed API logging locally when WP_DEBUG is enabled.</li>
      <li>Housekeeping - Increase API timeout interval as some overseas users reported timeout issues</li>
      <li>Housekeeping - Modified mPDF functions Image() and purify_utf8_text() to validate the input data so we don't have to do it every time through the template.</li>
      <li>Housekeeping - Added ability to not re-deploy every update (not enabled this release as template files were all updated)</li>
      <li>Housekeeping - Additional checks on load to see if any of the required file/folder structure is missing. If so, re-initilise.</li>
      <li>Housekeeping - Save resources and turn off automatic rtl identification. Users must set the RTL option when configuring form</li>
      <li>Housekeeping - Turn off mPDFs packTableData setting, decreasing processing time when working with large tables.</li>
      <li>Housekeeping - $gf_pdf_default_configuration options now merge down into existing PDF nodes, instead of applying to only unassigned forms. $gf_pdf_config settings override any in $gf_pdf_default_configuration</li>
      <li>Housekeeping - Center aligned Survey Likery field results</li>
      <li>Housekeeping - Partially refactored the pdf-entry-detail.php code</li>
      <li>Housekeeping - All default and example templates have been tidied. This won't affect custom templates.</li>
      <li>Housekeeping - Set the gform_notification order number to 100 which will prevent other functions (example snippets from Gravity Forms, for instance) from overridding the attached PDF.</li>
      <li>Housekeeping - Fix spelling mistake on initialising fonts</li>
      <li>Housekeeping - Remove wpautop() function from Gravity Form HTML output, which was applied before rendering and was messing up the HTML markup.</li>
      <li>Housekeeping - Remove empty list rows from the $form_data['list'] array in single and multi-column lists.</li>
      <li>Housekeeping - Apply same CSS styles (padding, border and line height) to HTML fields as done to form values in default templates</li>
      <li>Housekeeping - Replaced arbitrary wrapper IDs in the default templates with the actual field ID</li>

      <li>Bug - Fixed signature rendering issue when custom signature size was being used</li>
      <li>Bug - Fixed static error types in helper/install-update-manager.php file.</li>
      <li>Bug - Fixed redeployment error message which wasn't showing correctly</li>
      <li>Bug - Fixed issue with PDF not attaching to notification using Paypal's delayed notification feature</li>
      <li>Bug - Fixed strict standard warning about calling GFPDF_Settings::settings_page();</li>
      <li>Bug - Fixed strict standard warning about calling GFPDFEntryDetail::pdf_get_lead_field_display();</li>
      <li>Bug - Fixed issue with Gravity Form Post Category field causing fatal error generating PDF</li>
      <li>Bug - Fixed number field formatting issue when displaying on PDF.</li>
      <li>Bug - Do additional check for PHP's MB_String regex functions before initialising ti prevent errors after initialising</li>
      <li>Bug - Fixed problem with multiple nodes assigned to a form using the same template</li>
      <li>Bug - Fixed path to fallback templates when not found</li>
      <li>Bug - Fixed problem with master password setting to user password</li>
    </ul>

    <h3>3.3.4</h3>
    <ul>
    	<li>Bug - Fixed issue linking to PDF from front end</li>
        <li>Housekeeping - Removed autoredirect to initialisation page</li>
    </ul>
    <h3>3.3.3</h3>
    <ul>
    	<li>Bug - Correctly call javascript to control admin area 'View PDFs' drop down</li>
        <li>Bug - Some users still reported incorrect RAM. Convert MB/KB/GB values to M/K/G as per the PHP documentation.</li>
        <li>Housekeeping - Show initilisation prompt on all admin area pages instead of only on the Gravity Forms pages</li>
    </ul>

 	<h3>3.3.2.1</h3>
    <ul>
    	<li>Bug - Incorrectly showing assigned RAM to website</li>
    </ul>

 	<h3>3.3.2</h3>
    <ul>
    	<li>Bug - Some hosts reported SSL certificate errors when using the support API. Disabled HTTPS for further investigation. Using hash-based verification for authentication.</li>
    	<li>Housekeeping - Forgot to disable API debug feature after completing beta</li>
    </ul>

    <h3>3.3.1</h3>
    <ul>
    	<li>Bug - $form_data['list'] was mapped using an incremental key instead of via the field ID</li>
    </ul>

    <h3>3.3.0</h3>
    <ul>
      <li>Feature - Overhauled  the initialisation process so that the software better reviews the host for  potential problems before initialisation. This should help debug issues and  make users aware there could be a problem <strong>before</strong> they begin using the software.</li>
      <li>Feature - Overhauled the settings page to make it easier to access features of the software</li>
      <li>Feature - Added a Support tab to the settings page which allows users to securely (over HTTPS) submit a support ticket to the Gravity PDF support desk</li>
      <li>Feature - Changed select, multiselect and radio fields so that the default templates use the name rather than the value. $form_data now also includes the name and values for all these fields.</li>
      <li>Feature - $form_data now includes all miscellaneous lead information in the $form_data['misc'] array.</li>
      <li>Feature - $form_data now contains 24 and 12 hour time of entry submission.</li>
      <li>Feature - Added localisation support</li>
      <li>Compatibility - Added new multi-upload support which was added in Gravity Forms 1.8.</li>
      <li>Bug - Added 'aid' parametre to the PDF url when multiple configuration nodes present on a single form</li>
      <li>Bug - Fixed issue when Gravity Forms in No Conflict Mode</li>
      <li>Bug - Font config.php's array keys now in lower case</li>
      <li>Housekeeping - Moved all initialisation files to a folder called 'initialisation'.</li>
      <li>Housekeeping - Renamed the configuration.php file in the plugin folder to configuration.php.example to alleviate confusion for developers who unwittingly modify the plugin configuration file instead of the file in their active theme's PDF_EXTENDED_TEMPLATES folder.</li>
      <li>Housekeeping - Updated the plugin file system to a more MVC-style approach, with model and view folders.</li>
      <li>Housekeeping - Removed ability to directly access default and example template files.</li>
      <li>Housekeeping - Fixed PHP notices in default templates related to the default template-only configuration options</li>
      <li>Housekeeping - Update core styles to match Wordpress 3.8/Gravity Forms 1.8.</li>
      <li>Housekeeping - Updated header/footer examples to use @page in example.</li>

    </ul>

    <h3>3.2.0</h3>
    <ul>
      <li>Feature - Can now view multiple PDFs assigned to a single form via the admin area. Note: You must provide a unique 'filename' parameter in configuration.php for multiple PDFs assigned to a single form. </li>
      <li>Feature - You can exclude a field from the default templates using the class name 'exclude'. See our <a rel="nofollow" href="https://gravitypdf.com/#faqs">FAQ topic</a> for more details.</li>
      <li>Bug - Fixed issue viewing own PDF entry when logged in as anything lower than editor.</li>
      <li>Bug - Fixed data return bug in pdf-entry-details.php that was preventing all data returning correctly.</li>
      <li>Bug - Fixed PHP Warning when using products with no options</li>
      <li>Bug - Fixed issue with invalid characters being added to the PDF filename. Most notably the date mergetag.</li>
      <li>Bug - Limit filename length to 150 characters which should work on the majority of web servers.</li>
      <li>Bug - Fixed problem sending duplicate PDF when using mass resend notification feature</li>
      <li>Depreciated - Removed GF_FORM_ID and GF_LEAD_ID constants which were used in v2.x.x of the software. Ensure you follow <a rel="nofollow" href="https://developer.gravitypdf.com/news/version-2-3-migration-guide/">v2.x.x upgrade guide</a> to your templates before upgrading.</li>
    </ul>

    <h3>3.1.4</h3>
    <ul>
      <li>Bug - Fixed issue with plugin breaking website's when the Gravity Forms plugin wasn't activated.</li>
      <li>Housekeeping - The plugin now only supports Gravity Forms 1.7 or higher and WordPress 3.5 or higher.</li>
      <li>Housekeeping - PDF template files can no longer be accessed directly. Instead, add &amp;html=1 to the end of your URL when viewing a PDF.</li>
      <li>Extension - Added additional filters to allow the lead ID and notifications to be overridden.</li>
    </ul>

    <h3>3.1.3</h3>
    <ul>
      <li>Feature - Added signature_details_id to $form_data array which maps a signatures field ID to the array.</li>
      <li>Extension - Added pre-PDF generator filter for use with extensions.</li>
      <li>Bug - Fixed issue with quotes in entry data breaking custom templates.</li>
      <li>Bug - Fixed issue with the plugin not correctly using the new default configuration template, if set.</li>
      <li>Bug - Fixed issue with signature not being removed correctly when only testing with file_exists(). Added second is_dir() test.</li>
      <li>Bug - Fixed issue with empty signature field not displaying when option 'default-show-empty' is set.</li>
      <li>Bug - Fixed initialisation prompt issue when the MPDF package wasn't unpacked.</li>
    </ul>

    <h3>3.1.2</h3>
    <ul>
      <li>Feature - Added list array, file path, form ID and lead ID to $form_data array in custom templates</li>
      <li>Bug - Fixed initialisation prompt issue when updating plugin</li>
      <li>Bug - Fixed window.open issue which prevented a new window from opening when viewing a PDF in the admin area</li>
      <li>Bug - Fixed issue with product dropdown and radio button data showing the value instead of the name field.</li>
      <li>Bug - Fixed incorrect URL pointing to signature in $form_data</li>
    </ul>

    <h3>3.1.1</h3>
    <ul>
      <li>Bug - Users whose server only supports FTP file manipulation using the WP_Filesystem API moved the files into the wrong directory due to FTP usually being rooted to the WordPress home directory. To fix this the plugin attempts to determine the FTP directory, otherwise assumes it is the WP base directory. </li>
      <li>Bug - Initialisation error message was being called but the success message was also showing. </li>
    </ul>
    <h3>3.1.0</h3>
    <ul>
      <li>Feature - Added defaults to configuration.php which allows users to define the default PDF settings for all Gravity Forms. See the <a rel="nofollow" href="https://developer.gravitypdf.com/documentation/getting-started-with-gravity-pdf-configuration/">installation and configuration documentation</a> for more details. </li>
      <li>Feature - Added three new configuration options 'default-show-html', 'default-show-empty' and 'default-show-page-names' which allow different display options to the three default templates. See the <a rel="nofollow" href="https://developer.gravitypdf.com/documentation/configuration-options-examples/#default-template">installation and configuration documentation</a> for more details.</li>
      <li>Feature - Added filter hooks 'gfpdfe_pdf_name' and 'gfpdfe_template' which allows developers to further modify a PDF name and template file, respectively, outside of the configuration.php. This is useful if you have a special case naming convention based on user input. See <a rel="nofollow" href="https://developer.gravitypdf.com/documentation/filters-and-hooks/">https://developer.gravitypdf.com/documentation/filters-and-hooks/</a> for more details about using these filters.</li>
      <li>Feature - Custom font support. Any .ttf font file added to the PDF_EXTENDED_TEMPLATES/fonts/ folder will be automatically installed once the plugin has been initialised. Users also have the option to just initialise the fonts via the settings page. See the <a rel="nofollow" href="https://developer.gravitypdf.com/documentation/language-support/#install-custom-fonts">font/language documentation </a> for details.</li>
      <li>Compatability - Use Gravity Forms get_upload_root() and get_upload_url_root() instead of hard coding the signature upload directory in pdf-entry-detail.php</li>
      <li>Compatability - Changed depreciated functions get_themes() and get_theme() to wp_get_theme() (added in WordPress v3.4). </li>
      <li>Compatability - The plugin now needs to be initialised on fresh installation and upgrade. This allows us to use the WP_Filesystem API for file manipulation.</li>
      <li>Compatability - Automatic copying of PDF_EXTENDED_TEMPLATES folder on a theme change was removed in favour of a user prompt. This allows us to take advantage of the WP_Filesystem API.</li>
      <li>Compatability - Added WordPress compatibility checker (minimum now 3.4 or higher).</li>
      <li>Bug - Removed ZipArchive in favour of WordPress's WP_Filesystem API unzip_file() command. Some users reported the plugin would stop their entire website working if this extension wasn't installed.</li>
      <li>Bug - Fixed Gravity Forms compatibility checker which wouldn't return the correct response.</li>
      <li>Bug - Fixed minor bug in pdf.php when using static call 'self' in add_filter hook. Changed to class name.</li>
      <li>Bug - Removed PHP notice about $even variable not being defined in pdf-entry-detail.php</li>
      <li>Bug - Prevent code from continuing to excecute after sending header redirect.</li>
    </ul>
    <h3>3.0.2</h3>
    <ul>
      <li>Backwards Compatibility - While PHP 5.3 has was released a number of years ago it seems a number of hosts do not currently offer this version to their clients. In the interest of backwards compatibility we've re-written the plugin to again work with PHP 5+.</li>
      <li>Signature / Image Display Bug - All URLs have been converted to a path so images should now display correctly in PDF.</li>
    </ul>
    <h3>3.0.1</h3>
    <ul>
      <li>Bug - Fixed issue that caused website to become unresponsive when Gravity Forms was disabled or upgraded</li>
      <li>Bug - New HTML fields weren't being displayed in $form_data array</li>
      <li>Feature - Options for default templates to disable HTML fields or empty fields (or both)</li>
    </ul>
    <h3>3.0.0</h3>
    <p>As of Gravity PDF v3.0.0 we have removed the DOMPDF package from our plugin and integrated the more advanced mPDF system. Along with a new HTML to PDF generator, we've rewritten the entire plugin's base code to make it more user friendly to both hobbyists and rock star web developers. Configuration time is cut in half and advanced features like adding security features is now accessible to users who have little experience with PHP.</p>
    <p>New Features include:</p>
    <ul>
      <li>Language Support - almost all languages are supported including RTL (right to left) languages like Arabic and Hebrew and CJK languages - Chinese, Japanese and Korean.</li>
      <li>HTML Page Numbering</li>
      <li>Odd and even paging with mirrored margins (most commonly used in printing).</li>
      <li>Nested Tables</li>
      <li>Text-justification and hyphenation</li>
      <li>Table of Contents</li>
      <li>Index</li>
      <li>Bookmarks</li>
      <li>Watermarks</li>
      <li>Password protection</li>
      <li>UTF-8 encoded HTML</li>
      <li>Better system resource handling</li>
    </ul>
    <p>A new HTML to PDF package wasn't the only change to this edition of the software. We have rewritten the entire configuration system and made it super easy to get the software up and running.</p>
    <p>Users will no longer place code in their active theme's functions.php file. Instead, configuration will happen in a new file called configuration.php, inside the PDF_EXTENDED_TEMPLATES folder (in your active theme).</p>
    <p>Other changes include
      <li>Improved security - further restrictions were placed on non-administrators viewing template files.
      <li>$form_data array tidied up - images won't be wrapped in anchor tags.</p>
    <p>For more details <a rel="nofollow" href="https://developer.gravitypdf.info/">view the 3.x.x online documentation</a>.</p>
    <h3>2.2.3</h3>
    <ul>
      <li>Bug - Fixed mb_string error in the updated DOMPDF package.</li>
    </ul>
    <h3>2.2.2</h3>
    <ul>
      <li>DOMPDF - We updated to the latest version of DOMPDF - DOMPDF 0.6.0 beta 3.</li>
      <li>DOMPDF - We've enabled font subsetting by default which should help limit the increased PDF size when using DejaVu Sans (or any other font). </li>
    </ul>
    <h3>2.2.1</h3>
    <ul>
      <li>Bug - Fixed HTML error which caused list items to distort on PDF</li>
    </ul>
    <h3>2.2.0</h3>
    <ul>
      <li>Compatibility - Ensure compatibility with Gravity Forms 1.7. We've updated the functions.php code and remove gform_user_notification_attachments and gform_admin_notification_attachments hooks which are now depreciated. Functions gform_pdf_create and gform_add_attachment have been removed and replaced with gfpdfe_create_and_attach_pdf(). See upgrade documentation for details.</li>
      <li>Enhancement - Added deployment code switch so the template redeployment feature can be turned on and off. This release doesn't require redeployment.</li>
      <li>Enhancement - PDF_Generator() variables were getting long and complex so the third variable is now an array which will pass all the optional arguments. The new 1.7 compatible functions.php code includes this method by default. For backwards compatibility the function will still work with the variable structure prior to 2.2.0.</li>
      <li>Bug - Fixed error generated by legacy code in the function PDF_processing() which is located in render_to_pdf.php.</li>
      <li>Bug - Images and stylesheets will now try and be accessed with a local path instead of a URL. It fixes problem where some hosts were preventing read access from a URL. No template changes are required.</li>
    </ul>
    <h3>2.1.1</h3>
    <ul>
      <li>Bug - Signatures stopped displaying after 2.1.0 update. Fixed issue. </li>
      <li>Bug - First time install code now won't execute if already have configuration variables in database</li>
    </ul>
    <h3>2.1.0</h3>
    <ul>
      <li>Feature - Product table can now be accessed directly through custom templates by running GFPDFEntryDetail::product_table($form, $lead);. See documentation for more details.</li>
      <li>Feature - Update screen will ask you if you want to deploy new template files, instead of overriding your modified versions.</li>
      <li>Feature - Product subtotal, shipping and total have been added to $form_data['field'] array to make it easier to work with product details in the custom template.</li>
      <li>Feature - Added two new default template files. One displays field and name in two rows (like you see when viewing an entry in the admin area) and the other removes all styling. See documentation on use.</li>
      <li>Security - Tightened PDF template security so that custom templates couldn't be automatically generated by just anyone. Now only logged in users with the correct privileges and the user who submitted the form (matched against IP) can auto generate a PDF. See documentation on usage.</li>
      <li>Depreciated - Removed form data that was added directly to the $form_data array instead of $form_data['field'] array. Users upgrading will need to update their custom templates if not using field data from the $form_data[�field'] array. If using $form_data['field'] in your custom template this won't affect you.</li>
      <li>Bug - Fixed problem with default template not showing and displaying a timeout error. Removed table tags and replaced with divs that are styled appropriately.</li>
      <li>Bug - The new plugin theme folder will successfully create when upgrading. You won't have to deactivate and reactivate to get it working.</li>
      <li>Bug - some installs had plugins that included the function mb_string which is also included in DOMPDF. DOMPDF will now check if the function exists before creating it.</li>
      <li>Bug - Remove empty signature field from the default template.</li>
      <li>Bug - fixed problem with redirecting to login screen even when logged in while accessing template file through the browser window directly.</li>
      <li>Bug - fixed error where sample template would reimport itself automatically even after deleting it. Will now only reimport if any important changes to template need to be viewed straight after an update.</li>
      <li>Bug - Moved render_to_pdf.php constants to pdf.php so we can use the constants in the core files. Was previously generating an error.</li>
      <li>Housekeeping - Cleaned up core template files, moved functions into classes and added more in-file documentation.</li>
      <li>Housekeeping - moved install/upgrade code from pdf.php to installation-update-manager.php</li>
      <li>Housekeeping - changed pdf-entry-detail.php class name from GFEntryDetail to GFPDFEntryDetail to remove compatibility problems with Gravity Forms.</li>
      <li>Housekeeping - created pdf-settings.php file to house the settings page code.</li>
    </ul>
    <h3>2.0.1</h3>
    <ul>
      <li>Fixed Signature bug when checking if image file exists using URL instead of filesystem path</li>
      <li>Fixed PHP Constants Notice </li>
    </ul>
    <h3>2.0.0</h3>
    <ul>
      <li>Moved templates to active theme folder to prevent custom themes being removed on upgrade</li>
      <li>Allow PDFs to be saved using a custom name</li>
      <li>Fixed WP_Error bug when image/css file cannot be found</li>
      <li>Upgraded to latest version of DOMPDF</li>
      <li>Removed auto-load form bug which would see multiple instances of the example form loaded</li>
      <li>Created a number of constants to allow easier developer modification</li>
      <li>Plugin/Support moved to dedicated website.</li>
      <li>Pro/Business package offers the ability to write fields on an existing PDF.</li>
    </ul>
    <h3>1.2.3</h3>
    <ul>
      <li>Fixed $wpdb-&gt;prepare error</li>
    </ul>
    <h3>1.2.2</h3>
    <ul>
      <li>Fixed bug with tempalte shipping method MERGETAGS</li>
      <li>Fixed bug where attachment wasn't being sent</li>
      <li>Fixed problem when all_url_fopen was turned off on server and failed to retreive remote images. Now uses WP_HTTP class.</li>
    </ul>
    <h3>1.2.1</h3>
    <ul>
      <li>Fixed path to custom css file included in PDF template </li>
    </ul>
    <h3>1.2.0</h3>
    <ul>
      <li>Template files moved to the plugin's template folder</li>
      <li>Sample Form installed so developers have a working example to modify</li>
      <li>Fixed bug when using WordPress in another directory to the site</li>
    </ul>
    <h3>1.1.0</h3>
    <ul>
      <li>Now compatible with Gravity Forms Signature Add-On</li>
      <li>Moved the field data functions out side of the Gravity Forms core so users can freely style their form information (located in pdf-entry-detail.php)</li>
      <li>Simplified the field data output</li>
      <li>Fixed bug when using product information</li>
    </ul>
    <h3>1.0.0</h3>
    <ul>
      <li>First release.</li>
    </ul>

