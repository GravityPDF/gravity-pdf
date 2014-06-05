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

			<!-- New to v3.4.0, watermarking is now enabled. See example-watermark09.php for more details -->          
          	<watermarktext content="DRAFT" alpha="0.1" />

			<!-- There's much better server compatibility if you use the absolute path to the image as oppose to a URL. -->
			<img src="<?php echo PDF_PLUGIN_DIR ?>resources/images/gravityformspdfextended.jpg" width="311" height="110"  />
           
           
           <!-- let's build our body content -->
           <div class="body_copy">
            
	            <p class="whom_concern_intro">Dear User,</p>

				<p class="body_text">Gravity Forms PDF Extended allows you to directly access Gravity Form field data so you can create custom PDFs like this one.
				If you haven't reviewed our five part installation and configuration guide yet, we recommend <a target="_blank" href="http://gravityformspdfextended.com/documentation-v3-x-x/standard-configuration/">taking a look</a>.
				There's also a <a target="_blank" href="http://gravityformspdfextended.com/documentation-v3-x-x/configuration-options-examples/">large number of configuration options</a> that can be applied to any PDF.</p>
				</p>

				<p class="body_text">Now you've got an understanding on configuring the software, let's take a look at custom templates. 
				To start with, you'll want to copy and rename one of the <em>example-</em> template files in your active theme's PDF_EXTENDED_TEMPLATES directory. </p>				

				<p class="body_text">Once copied, go to your Wordpress Dashboard and navigate to Forms -> Entries and click the View PDF button on one of your entries. If you haven't assigned a custom template to the form, the default-template.php should open. 
				 If you change <em>default-template.php</em> in the URL to the new template you just made it will generate a PDF based off that file.</p>
				<p class="body_text">Once you have your new template file open in your browser window you can access the $form_data array (which contains all the Gravity Form entry data) by appending the URL with &amp;data=1. <strong>Note: this only works if you copied a template file with example- in the name.</strong></p>
				<p><strong>Example:</strong> http://www.yourdomain.com/?gf_pdf=1&amp;fid=2&amp;lid=6&amp;template=new-example-template.php&amp;data=1             </p>
				
				<p>For more information about custom templates  <a target="_blank" href="http://gravityformspdfextended.com/documentation-v3-x-x/templates/getting-started/">review the plugin's documentation</a><br /><br />
	            </p>
				<p class="signature">
	                Jake Jackson<br />
	                <img src="<?php echo PDF_PLUGIN_DIR ?>resources/images/signature.png" alt="Signature" width="100" height="60" /><br />
	                Developer, Gravity Forms PDF Extended<br />
	                <a target="_blank" href="http://www.gravityformspdfextended.com">www.gravityformspdfextended.com</a>
	            </p>
           
           </div>          
         
        <?php } ?>
	</body>
</html>