<?php

/* 
 * Gravity Forms PDF Extended Configuration
 * New from v3.0.0. No longer is theme functions.php code required.
 * Important documentation on the configuration found at http://gravityformspdfextended.com/documentation-v3-x-x/installation-and-configuration/
 */
 
/*
 * Added in v3.1.0
 * Users can now assign defaults to forms that aren't configured below. 
 * Note: this will only work if the configuration option GFPDF_SET_DEFAULT_TEMPLATE is set to true (located at the bottom of this file).
 * 
 * Users can use any configuration option like you would for a singular form, including:
 * notifications, template, filename, pdf_size, orientation, security and rtl
 */
 global $gf_pdf_default_configuration;
 
 $gf_pdf_default_configuration = array(
 	'template' => 'default-template.php',
	'pdf_size' => 'A4'	
 ); 
 
 /*
  * ------------------------------------------------------------ 
  * Bare minimum configuration code
  * Usage: Will generate PDF and send to all notifications
  * Remove the comments around the code blocks below to use (/*) 
  * form_id Mixed - Integer or Array. Required. The Gravity Form ID you are assigning the PDF to.
  * notifications Mixed - String, Boolean or Array. 
  */
  /*$gf_pdf_config[] = array(
  	'form_id' => 1, 
    'notifications' => true,
  );*/
  
 /*
  * ------------------------------------------------------------ 
  * Default template specific configuration code
  * As of 3.1.0 uses can now add default-show-html, default-show-empty and default-show-page-names when using any
  * of the default template files (any template prepended with default-)
  * 
  * Usage:
  * 'default-show-html' - This option will display HTMl blocks in your default tempalte. 
  * 'default-show-empty' - All form fields will be displayed in the PDF, regardless of what the user input is.
  * 'default-show-page-names' - If you are using page breaks you can display the page names in the PDF.
  *
  * Remove the comments around the code blocks below to use (/*) 
  */
  
  /*$gf_pdf_config[] = array(
  	'form_id' => 1, 
  	'template' => 'default-template.php',		
  	'default-show-html' => true,
  );*/  
  
  /*$gf_pdf_config[] = array(
  	'form_id' => 1, 
  	'template' => 'default-template.php',	
  	'default-show-empty' => true,
  );*/ 
  
  /*$gf_pdf_config[] = array(
  	'form_id' => 1, 
  	'template' => 'default-template.php',		
  	'default-show-page-names' => true,
  );*/     
  
  /*$gf_pdf_config[] = array(
  	'form_id' => 1, 
  	'template' => 'default-template.php',		
  	'default-show-html' => true,
  	'default-show-empty' => true,
  	'default-show-page-names' => true,
  );*/   
  
 /*
  * ------------------------------------------------------------ 
  * Notification Options
  * notifications Mixed - String, Boolean or Array.   
  * Notifications can be a string like 'Admin Notifications', an array with multiple notification names or true to send to all.
  */
  /*$gf_pdf_config[] = array(
  	'form_id' => 1, 
	  'notifications' => 'Admin Notification', 
  );*/ 
  
  /*$gf_pdf_config[] = array(
  	'form_id' => '1', 
	  'notifications' => array('Admin Notification', 'User Notification'), 
  );*/  
  
 /*
  * ------------------------------------------------------------ 
  * Custom Template
  * Don't want to use a custom template? Just pass the custom template name to the configuration.
  * template String. Default default-template.php. The name of your custom template that's placed in your active theme's PDF_EXTENDED_TEMPLATES folder. 
  * For more information about creating custom templates please see http://gravityformspdfextended.com/documentation-v3-x-x/templates/
  */
  /*$gf_pdf_config[] = array(
  	'form_id' => 1, 
  	'notifications' => 'User Notification', 		
  	'template' => 'example-float-and-positioning05.php', 
  );*/  
  
  /*$gf_pdf_config[] = array(
  	'form_id' => 2, 
  	'notifications' => 'User Notification',
  	'template' => 'example-basic-html01.php', 
  );*/    
    
 /*
  * ------------------------------------------------------------ 
  * Custom File Name
  * Will change the filename of the PDF which is attached
  * As of v3.0.0 merge tags can be used in the file name
  * filename String. Default form-{form_id}-entry-{entry_id}.pdf
  */    
  /*$gf_pdf_config[] = array(
  	'form_id' => 1, 
  	'notifications' => true, 
  	'filename' => 'New PDF Name.pdf', 
  );*/ 
 
  /*$gf_pdf_config[] = array(
  	'form_id' => '1', 
  	'notifications' => true, 
  	'filename' => 'User {Name:1}.pdf', 
  );*/ 
 
 /*
  * ------------------------------------------------------------
  * Custom PDF Size / Orientation
  * PDF Size can be set to the following:
  *
  *	A0 - A10, B0 - B10, C0 - C10
  *	4A0, 2A0, RA0 - RA4, SRA0 - SRA4
  *	Letter, Legal, Executive, Folio
  *	Demy, Royal  
  *
  * Default: A4
  * You can also pass the PDF size as an array, represented in millimetres - array(width, height).
  * 
  * NOTE: By default the orientation is portrait so you only need to add it for landscape PDFs
  */ 
  
  /* Letter-sized Document */
  /*$gf_pdf_config[] = array(
  	'form_id' => 1,  
  	'notifications' => true, 
  		
  	'pdf_size' => 'letter',
  );*/
  
  /* Custom PDF Size */
 /* $gf_pdf_config[] = array(
  	'form_id' => 1, 
  	'notifications' => true,
  	
  	'pdf_size' => array(50, 200),
    );*/  
    
    /* Change orientation */
   /* $gf_pdf_config[] = array(
  	'form_id' => 1, 
  	'notifications' => true,
  	
  	'pdf_size' => 'letter',
  	'orientation' => 'landscape',
  );*/  

 /*
  * ------------------------------------------------------------
  * PDF Security
  * Allows you to password protect your PDF document, place a master password on the document which prevents document tampering and restricts user behaviour. 
  *
  * security Boolean. Default false. If true the security settings will be applied.
  * pdf_password String. Default blank.
  * pdf_privileges Array
  * Assign user privileges to the document. Valid privileges include: copy, print, modify, annot-forms, fill-forms, extract, assemble, print-highres
  * pdf_master_password String. Default random generated. Set a master password on the PDF which stops the PDF from being modified.
  * NOTE: As the document is encrypted in 128-bit print will only allow users to print a low resolution copy.
  *       Use print-highres to print full resolution image.  
  * NOTE: The use of print will only allow low-resolution printing from the document; you must specify print-highres to allow full resolution printing.
  * NOTE: If pdf_master_password is omitted a random one will be generated
  * NOTE: Passing a blank array or not passing anything to pdf_privileges will deny all permissions to the user
  */	  
 
 /*
  * Setting security settings with all values
  */  
  /*$gf_pdf_config[] = array(
   	'form_id' => 1,
  	'notifications' => true,	 
  	 
  	'security' => true, 
  	'pdf_password' => 'myPDFpass', 	
  	'pdf_privileges' => array('copy', 'print', 'modify', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-highres'), 	
  	'pdf_master_password' => 'admin password', 
  );*/
 
  /*
   * Set password to PDF.
   * Deny all permissions to user
   * Random master password will be generated 
   */
   /*$gf_pdf_config[] = array(
   	'form_id' => 1,
  	'notifications' => true,	 
  	 
  	'security' => true, 
  	'pdf_password' => 'myPDFpass',
   );*/
  
  /*
   * No password required to open PDF document.
   * Deny all permissions to user
   * Master password set
   */  
   /*$gf_pdf_config[] = array(
   	'form_id' => 1,
  	'notifications' => true,	 
  	 
  	'security' => true, 
  	'pdf_master_password' => 'admin password', 
   );*/   
  
  /*
   * No password required to open PDF document.
   * User can copy, print and modify PDF
   * Random master password will be generated 
   *
   */  
   /*$gf_pdf_config[] = array(
 	'form_id' => 1,
	'notifications' => true,	 
	 
	'security' => true, 
	'pdf_privileges' => array('copy', 'print', 'modify', 'print-highres'),
   );*/   
   
  /*
  * ------------------------------------------------------------ 
  * Right to Left Language Support
  * We now support RTL languages.
  * rtl Boolean. Default false.
  */  
   /*$gf_pdf_config[] = array(
	  'form_id' => 1, 
	  'notifications' => true,
	
	  'rtl' => true,
   );*/

  
 /*
  * ------------------------------------------------------------ 
  * Multiple Forms
  * If you have multiple forms that use the same PDF template then you can pass the form_id through as an array.
  * WARNING: If using a custom template with this option your secondary forms should be a duplicate of the original and you shouldn't delete the fields
  *          otherwise the custom template won't show correctly. 
  * WARNING: The previous warning also applies to custom PDF names with MERGETAGS
  */
  /*$gf_pdf_config[] = array(
    'form_id' => array(1,5,6), 
    'notifications' => true,
  );*/
    
  /*
  * ------------------------------------------------------------ 
  * Disable Notifications
  * If you don't need to send notifications and just want a custom PDF generated 
  * via the admin area you can forgo the notifications class
  */  
   /*$gf_pdf_config[] = array(
	  'form_id' => 1, 
	  'template' => 'example-template.php',		
   );*/    

  /*
  * ------------------------------------------------------------ 
  * Save PDF to disk
  * Added in v3.4.0 
  * 
  * If you don't want the PDF sent via email but want it saved on the server 
  * use the 'save' option. The default save location is /wp-content/themes/ActiveTheme/PDF_EXTENDED_TEMPLATE/output/
  * Note: you can use 'save' in conjunction with 'notification' without any ill effects
  */  
   /*$gf_pdf_config[] = array(
    'form_id' => 1, 
    'template' => 'example-template.php',
    'save' => true,  
   );*/    

  /*
  * ------------------------------------------------------------ 
  * Set the PDF DPI
  * Added in v3.4.0 
  * 
  * For when you need to change the document's DPI. Usually set to 300 when used in professional printing.
  * The default DPI used in the PDFs are 96.
  * Use the 'dpi' option
  */  
 
   /*$gf_pdf_config[] = array(
    'form_id' => 1, 
    'template' => 'example-template.php',
    'dpi' => 300,  
   );*/    

  /*
  * ------------------------------------------------------------  
  * Set PDF to PDF/A-1b format
  * Added in v3.4.0 
  * 
  * PDF/A1-b is a file format for the long-term archiving of electronic documents.
  * A key element to this reproducibility is the requirement for PDF/A documents to be 100% self-contained.
  *
  * Important: The software is not guaranteed to produce fully PDF/A1-b compliant files in all circumstances. It is the users responsibility to check compliance if this is essential.
  *
  * Usage:
  * We've added the configuration options 'pdfa1b'. 
  *
  * The software will automatically make appropriate changes to your document to ensure it generates a valid PDF/A-1b document however the following items cannot be automatically fixed and are disallowed:
  *
  * 1. Watermarks - text or image - are not permitted (transparency is disallowed so will make text unreadable)  
  * 2. PNG images with alpha channel transparency ('masks' not allowed)
  * 3. Encryption is enabled (the system will automatically remove any security settings)
  *  
  * For more details about generating a PDF/X-1a document see http://mpdf1.com/manual/index.php?tid=420
  */  
 
   /*$gf_pdf_config[] = array(
    'form_id' => 1, 
    'template' => 'example-template.php',
    'pdfa1b' => true,
   );*/    

  /*
  * ------------------------------------------------------------  
  * Set PDF to PDF/X-1a format
  * Added in v3.4.0 
  * 
  * PDF/X-1a is a file format to facilitate printing of electronic documents.
  * Two key elements to this function are the requirement for PDF/X documents to be 100% self-contained, and all images need to be CMYK or spot colors.
  *
  * Important: The software is not guaranteed to produce fully PDF/A1-b compliant files in all circumstances. It is the users responsibility to check compliance if this is essential.
  *
  * Usage:
  * We've added a new configuration options: 'pdfx1a' to turn documents into PDF/X-1a format. 
  *
  * The software will automatically make appropriate changes to your document to ensure it generates a valid PDF/X-1a document however the following items cannot be automatically fixed and are disallowed:
  *
  * 1. Watermarks - text or image - are not permitted (transparency is disallowed so will make text unreadable)  
  * 2. PNG images with alpha channel transparency ('masks' not allowed)
  * 3. Encryption is enabled (the system will automatically remove any security settings)
  *
  * For more details about generating a PDF/X-1a document see http://mpdf1.com/manual/index.php?tid=481
  * 
  */  
 
   /*$gf_pdf_config[] = array(
    'form_id' => 1, 
    'template' => 'example-template.php',
    'pdfx1a' => true,    
   );*/    


 /* --------------------------------------------------------------- 
  * CUSTOM PDF SETUP BELOW. 
  * See http://gravityformspdfextended.com/documentation-v3-x-x/installation-and-configuration/#constants for more details
  */
 
 /*
  * By default, forms that don't have PDFs assigned through the above configuration
  * will automatically use the default template in the admin area.
  * Set to false to disable this feature.
  */ 
 define('GFPDF_SET_DEFAULT_TEMPLATE', true); 
 
 /*
  * MEMORY ISSUES?
  * Try setting the options below to true to help reduce the memory footprint of the package.
  */ 
 define('PDF_ENABLE_MPDF_LITE', false); /* strip out advanced features like advanced table borders, terms and conditions, index, bookmarks and barcodes. */
 define('PDF_ENABLE_MPDF_TINY', false); /* if your tried the lite version and are still having trouble the tiny version includes the bare minimum features. There's no positioning, float, watermark or form support */
 define('PDF_DISABLE_FONT_SUBSTITUTION', false); /* reduced memory by stopping font substitution */
 define('PDF_ENABLE_SIMPLE_TABLES', false); /* disable the advanced table feature and forces all cells to have the same border, background etc. */

 define('PDF_REPACK_FONT', false); /* If enabled, when embedding full TTF font files it will be remade with only core font tables which may improve function with some PostScript printers (GhostScript/GSView) */