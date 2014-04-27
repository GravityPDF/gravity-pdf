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

    <?php 
        /*
         * Using the @page method we can set the headers and footers
         * This method is more reliable that the <sethtmlpageheaders> method
         * See mPDF documentation for more details
         * http://mpdf1.com/manual/index.php?tid=307&searchstring=@page
         */
    ?>
    <style>
        @page {
            header: html_myHTMLHeader1;
            footer: html_myFooter1;
        }
    </style>
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
           
            <!-- Defines the headers/footers -->
            <!-- Headers and footers set with CSS @page method above -->

            <htmlpageheader name="myHTMLHeader1">
                <table width="100%" style="border-bottom: 1px solid #000000; vertical-align: bottom; font-family: serif; font-size: 9pt; color: #000088;"><tr>
                <td width="50%">Left header p <span style="font-size:14pt;">{PAGENO}</span></td>
                <td width="50%" style="text-align: right;"><span style="font-weight: bold;">myHTMLHeader1</span></td>
                </tr></table>
            </htmlpageheader>
            
            <htmlpagefooter name="myFooter1">
            <table width="100%" style="vertical-align: bottom; font-family: serif; font-size: 8pt;
                color: #000000; font-weight: bold; font-style: italic;"><tr>
                <td width="33%"><span style="font-weight: bold; font-style: italic;">{DATE j-m-Y}</span></td>
                <td width="33%" align="center" style="font-weight: bold; font-style: italic;">{PAGENO}/{nbpg}</td>
                <td width="33%" style="text-align: right; ">My document</td>
                </tr></table>
            </htmlpagefooter>
                   
            <div>
            	<img src="<?php echo PDF_PLUGIN_DIR; ?>resources/images/gravityformspdfextended.jpg" width="311" height="110"  />
                <h2>Basic Headers</h2>
                    
                <p>Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p>
                <pagebreak />
                
                <h2>Basic Headers</h2>
                <p>Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p>
                
                <pagebreak />
                
                <h2>Basic Headers</h2>
                <p>Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p>
            </div>

        <?php } ?>
	</body>
</html>