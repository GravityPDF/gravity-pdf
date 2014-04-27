<?php

/**
 * If the template is being loaded directy we'll call the Wordpress Core 
 * Used when attempting to debug the template
 */ 
if(!class_exists("RGForms")){
   return;
}

/** 
 * Set up the form ID and lead ID, as well as we want page breaks displayed. 
 * Form ID and Lead ID can be set by passing it to the URL - ?fid=1&lid=10
 */
 PDF_Common::setup_ids();
 
 global $gfpdf;
 $configuration_data = $gfpdf->get_config_data($form_id);
 
 $show_html_fields = (isset($configuration_data['default-show-html']) && $configuration_data['default-show-html'] == 1) ? true : false;
 $show_empty_fields = (isset($configuration_data['default-show-empty']) && $configuration_data['default-show-empty']  == 1) ? true : false; 
 $show_page_names = (isset($configuration_data['default-show-page-names']) && $configuration_data['default-show-page-names']  == 1) ? true : false;    

/**
 * Load the form data, including the custom style sheet which looks in the plugin's theme folder before defaulting back to the plugin's file.
 */
$form = RGFormsModel::get_form_meta($form_id);
$stylesheet_location = (file_exists(PDF_TEMPLATE_LOCATION.'template.css')) ? PDF_TEMPLATE_URL_LOCATION.'template.css' : PDF_PLUGIN_URL .'styles/template.css' ;

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <link rel='stylesheet' href='<?php echo GFCommon::get_base_url(); ?>/css/print.css' type='text/css' />
    <link rel='stylesheet' href='<?php echo $stylesheet_location; ?>' type='text/css' />
    <title>Gravity Forms PDF Extended</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
	<body>
    	<div class="no-style">
			<?php
    
            foreach($lead_ids as $lead_id) {
                $lead = RGFormsModel::get_lead($lead_id);
                GFPDFEntryDetail::lead_detail_grid($form, $lead, $show_empty_fields, $show_html_fields, $show_page_names);
            }
    
            ?>
        </div>
	</body>
</html>