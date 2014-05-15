<?php

/**
 * Don't give direct access to the template
 */ 
if(!class_exists("RGForms")){
    return;
}

/** 
 * Set up the form ID and lead ID
 * Form ID and Lead ID can be set by passing it to the URL - ?fid=1&lid=10
 */
 PDF_Common::setup_ids();

/**
 * Load the form data to pass to our PDF generating function 
 */
$form = RGFormsModel::get_form_meta($form_id);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>  
    <link rel='stylesheet' href='<?php echo PDF_PLUGIN_URL .'initialisation/template.css'; ?>' type='text/css' />
    <?php 
        /* 
         * Create your own stylesheet or use the <style></style> tags to add or modify styles  
         * The plugin stylesheet is overridden every update      
         */
    ?>
    <title>Gravity Forms PDF Extended</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
    <body>
        <?php   

        foreach($lead_ids as $lead_id) {

            $lead = RGFormsModel::get_lead($lead_id);
            $form_data = GFPDFEntryDetail::lead_detail_grid_array($form, $lead);
            
            /*
             * Add &data=1 when viewing the PDF via the admin area to view the $form_data array
             */
            PDF_Common::view_data($form_data);              
                        
            /*
             * Store your form fields from the $form_data array into variables here
             * To see your entire $form_data array, view your PDF via the admin area and add &data=1 to the url
             * 
             * For an example of accessing $form_data fields see http://gravityformspdfextended.com/documentation-v3-x-x/templates/getting-started/
             *
             * Alternatively, as of v3.4.0 you can use merge tags (except {allfields}) in your templates. 
             * Just add merge tags to your HTML and they'll be parsed before generating the PDF.    
             *       
             */                                 

            ?>

            <?php 
            	/*
            	 * The initial page size is set using the 'page_size' configuration option
            	 * The default is A4 Portrait
            	 * See Configuration page for more details on page_size : http://gravityformspdfextended.com/documentation-v3-x-x/configuration-options-examples/
            	 */
            ?>                   
                       
		   <p>This should print on an A4 (portrait) sheet</p>
		   
		   <!-- using pagebreak we can set the next page's size. In this case, it's A4 landscape -->
		   <pagebreak sheet-size="A4-L" />
		   <p>This page appears after the A4 portrait sheet and should print on an A4 (landscape) sheet</p>
		   <h1>mPDF Page Sizes</h1>
		   <h3>Changing page (sheet) sizes within the document</h3>   

		   <!-- change next page to A5 landscape -->
		   <pagebreak sheet-size="A5-L" />
		   
		   <p>This should print on an A5 (landscape) sheet</p>
		   <h1>mPDF Page Sizes</h1>
		   <h3>Changing page (sheet) sizes within the document</h3>   

		<?php } ?>
	</body>
</html>