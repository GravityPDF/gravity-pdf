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
           
            <!-- defines the headers/footers - this must occur before the headers/footers are set -->
            
            <htmlpageheader name="myHTMLHeader1">
            <table width="100%" style="border-bottom: 1px solid #000000; vertical-align: bottom; font-family: serif; font-size: 9pt; color: #000088;"><tr>
            <td width="50%">Left header p <span style="font-size:14pt;">{PAGENO}</span></td>
            <td width="50%" style="text-align: right;"><span style="font-weight: bold;">myHTMLHeader1</span></td>
            </tr></table>
            </htmlpageheader>
            
            <htmlpageheader name="myHTMLHeader1Even">
            <table width="100%" style="border-bottom: 1px solid #000000; vertical-align: bottom; font-family: serif; font-size: 9pt; color: #000088;"><tr>
            <td width="50%"><span style="font-weight: bold;">myHTMLHeader1Even</span></td>
            <td width="50%" style="text-align: right;">Inner header p <span style="font-size:14pt;">{PAGENO}</span></td>
            </tr></table>
            </htmlpageheader>
            
            <htmlpageheader name="myHTMLHeader2">
            <table width="100%" style="border-bottom: 1px solid #880000; vertical-align: bottom; font-family: sans; font-size: 9pt; color: #880000;"><tr>
            <td width="50%">myHTMLHeader2 p.<span style="font-size:14pt;">{PAGENO}</span></td>
            <td width="50%" style="text-align: right;"><span style="font-weight: bold;">myHTMLHeader2</span></td>
            </tr></table>
            </htmlpageheader>
            
            <htmlpageheader name="myHTMLHeader2Even">
            <table width="100%" style="border-bottom: 1px solid #880000; vertical-align: bottom; font-family: sans; font-size: 9pt; color: #880000;"><tr>
            <td width="50%"><span style="font-weight: bold;">myHTMLHeader2Even</span></td>
            <td width="50%" style="text-align: right;">Inner header p <span style="font-size:14pt;">{PAGENO}</span></td>
            </tr></table>
            </htmlpageheader>
            
            
            <!-- set the headers/footers - they will occur from here on in the document -->
            
            <sethtmlpageheader name="myHTMLHeader1" page="O" value="on" show-this-page="1" />
            <sethtmlpageheader name="myHTMLHeader1Even" page="E" value="on" />
           
            <div>
            	<img src="<?php echo PDF_PLUGIN_DIR; ?>/resources/images/gravityformspdfextended.jpg" width="311" height="110"  />
                <h2>Advanced Headers</h2>
                    
                <p>Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p>
                <pagebreak />
                
                <h2>Advanced Headers</h2>
                <p>Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p>
                
                <!-- Note the html_ prefix when referencing an HTML header using one of the pagebreaks -->
                <pagebreak odd-header-name="html_myHTMLHeader2" odd-header-value="1" even-header-name="html_myHTMLHeader2Even" even-header-value="1" />
                
                <h2>Advanced Headers</h2>
                <p>Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p>
            </div>

            <?php
                /*
                 * For more detail see mPDF documentation
                 * <sethtmlpageheader> : http://mpdf1.com/manual/index.php?tid=179
                 * <sethtmlpagefooter> : http://mpdf1.com/manual/index.php?tid=180
                 * <pagebreak> : http://mpdf1.com/manual/index.php?tid=110
                 */
            ?>

        <?php } ?>
	</body>
</html>