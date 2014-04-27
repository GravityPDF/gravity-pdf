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

			<!-- 
				Watermark content must have characters properly encoded: < = &lt; > = &gt; & = &amp; ' = &#39; or " = &quot;
				Best to use htmlspecialchars('Content', ENT_QUOTES) 
				Setting content to blank will clear the watermark
			 -->  	   
          	
          	<watermarktext content="<?php echo htmlspecialchars("DRAFT'S", ENT_QUOTES); ?>" alpha="0.1" />

          	<img src="<?php echo PDF_PLUGIN_DIR ?>resources/images/gravityformspdfextended.jpg" width="311" height="110"  />

			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam sollicitudin, magna et interdum hendrerit, est libero convallis odio, quis dapibus augue risus condimentum nisl. Quisque in orci dui. Pellentesque volutpat, nibh non eleifend fringilla, massa risus blandit neque, vestibulum adipiscing elit purus in tortor. Ut mauris libero, commodo eu suscipit vel, sagittis nec erat. Maecenas at eros facilisis, vestibulum felis sit amet, pellentesque nunc. Mauris accumsan leo a gravida tincidunt. Aliquam congue, leo vitae consequat tempor, arcu arcu dapibus ipsum, sit amet faucibus sem nulla nec lorem.</p>
			<p>Vivamus eu neque ac tortor fringilla malesuada. Nullam sed orci non erat vehicula volutpat eu sed velit. Donec gravida lacus ut tortor facilisis vestibulum. Vivamus venenatis hendrerit neque et porttitor. Aenean ultricies quis nibh nec euismod. Aliquam aliquam erat eget erat blandit sollicitudin. Curabitur non quam eget neque dignissim posuere.</p>
			<p>Nullam euismod venenatis eleifend. Donec et iaculis velit. Sed in magna sit amet felis egestas sagittis id ut velit. Morbi eleifend dictum interdum. Vestibulum convallis rutrum erat id lacinia. Pellentesque vulputate porta vehicula. Curabitur sagittis vel diam at placerat. Aliquam auctor diam sit amet risus convallis dignissim. Donec fermentum quam porttitor porta sagittis. Maecenas tristique turpis enim, eget convallis neque bibendum vitae. Ut iaculis lacus at rutrum ultrices. Suspendisse ut urna vitae purus eleifend laoreet non sit amet sem. </p>

			<pagebreak />
						
			<watermarktext content="<?php echo htmlspecialchars("PRIVATE", ENT_QUOTES); ?>" alpha="0.5" />

			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam sollicitudin, magna et interdum hendrerit, est libero convallis odio, quis dapibus augue risus condimentum nisl. Quisque in orci dui. Pellentesque volutpat, nibh non eleifend fringilla, massa risus blandit neque, vestibulum adipiscing elit purus in tortor. Ut mauris libero, commodo eu suscipit vel, sagittis nec erat. Maecenas at eros facilisis, vestibulum felis sit amet, pellentesque nunc. Mauris accumsan leo a gravida tincidunt. Aliquam congue, leo vitae consequat tempor, arcu arcu dapibus ipsum, sit amet faucibus sem nulla nec lorem.</p>
			<p>Vivamus eu neque ac tortor fringilla malesuada. Nullam sed orci non erat vehicula volutpat eu sed velit. Donec gravida lacus ut tortor facilisis vestibulum. Vivamus venenatis hendrerit neque et porttitor. Aenean ultricies quis nibh nec euismod. Aliquam aliquam erat eget erat blandit sollicitudin. Curabitur non quam eget neque dignissim posuere.</p>
			<p>Nullam euismod venenatis eleifend. Donec et iaculis velit. Sed in magna sit amet felis egestas sagittis id ut velit. Morbi eleifend dictum interdum. Vestibulum convallis rutrum erat id lacinia. Pellentesque vulputate porta vehicula. Curabitur sagittis vel diam at placerat. Aliquam auctor diam sit amet risus convallis dignissim. Donec fermentum quam porttitor porta sagittis. Maecenas tristique turpis enim, eget convallis neque bibendum vitae. Ut iaculis lacus at rutrum ultrices. Suspendisse ut urna vitae purus eleifend laoreet non sit amet sem. </p>


			<pagebreak />

			<watermarktext content="" />

			<p>Water mark turned off</p>

			<pagebreak />
			<!-- 
				Let's look at the image watermark now.
				It accepts two additional arguments to <watermarktext>: size and position
				And 'content' is substituted for 'src' : the link to the image

				Size Options:
				D: default i.e. original size of image - may depend on img_dpi
				P: Resize to fit the full page size, keeping aspect ratio
				F: Resize to fit the print-area (frame) respecting current page margins, keeping aspect ratio
				INT: Resize to full page size minus a margin set by this integer in millimeters, keeping aspect ratio
				2 comma-separated numbers ($width, $height): Specify a size; units in millimeters
				DEFAULT: "D"

				Position options:
				P: Centred on the whole page area
				F: Centred on the page print-area (frame) respecting page margins
				2 comma-separated numbers ($x, $y): Specify a position; units in millimeters
				DEFAULT: "P"				

			-->

			<watermarkimage src="<?php echo PDF_PLUGIN_DIR ?>resources/images/gravityformspdfextended.jpg" alpha="0.1" size="D" position="P" />
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam sollicitudin, magna et interdum hendrerit, est libero convallis odio, quis dapibus augue risus condimentum nisl. Quisque in orci dui. Pellentesque volutpat, nibh non eleifend fringilla, massa risus blandit neque, vestibulum adipiscing elit purus in tortor. Ut mauris libero, commodo eu suscipit vel, sagittis nec erat. Maecenas at eros facilisis, vestibulum felis sit amet, pellentesque nunc. Mauris accumsan leo a gravida tincidunt. Aliquam congue, leo vitae consequat tempor, arcu arcu dapibus ipsum, sit amet faucibus sem nulla nec lorem.</p>
			<p>Vivamus eu neque ac tortor fringilla malesuada. Nullam sed orci non erat vehicula volutpat eu sed velit. Donec gravida lacus ut tortor facilisis vestibulum. Vivamus venenatis hendrerit neque et porttitor. Aenean ultricies quis nibh nec euismod. Aliquam aliquam erat eget erat blandit sollicitudin. Curabitur non quam eget neque dignissim posuere.</p>
			<p>Nullam euismod venenatis eleifend. Donec et iaculis velit. Sed in magna sit amet felis egestas sagittis id ut velit. Morbi eleifend dictum interdum. Vestibulum convallis rutrum erat id lacinia. Pellentesque vulputate porta vehicula. Curabitur sagittis vel diam at placerat. Aliquam auctor diam sit amet risus convallis dignissim. Donec fermentum quam porttitor porta sagittis. Maecenas tristique turpis enim, eget convallis neque bibendum vitae. Ut iaculis lacus at rutrum ultrices. Suspendisse ut urna vitae purus eleifend laoreet non sit amet sem. </p>

			<pagebreak />

			<watermarkimage src="<?php echo PDF_PLUGIN_DIR ?>resources/images/gravityformspdfextended.jpg" alpha="0.5" size="P" position="F" />
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam sollicitudin, magna et interdum hendrerit, est libero convallis odio, quis dapibus augue risus condimentum nisl. Quisque in orci dui. Pellentesque volutpat, nibh non eleifend fringilla, massa risus blandit neque, vestibulum adipiscing elit purus in tortor. Ut mauris libero, commodo eu suscipit vel, sagittis nec erat. Maecenas at eros facilisis, vestibulum felis sit amet, pellentesque nunc. Mauris accumsan leo a gravida tincidunt. Aliquam congue, leo vitae consequat tempor, arcu arcu dapibus ipsum, sit amet faucibus sem nulla nec lorem.</p>
			<p>Vivamus eu neque ac tortor fringilla malesuada. Nullam sed orci non erat vehicula volutpat eu sed velit. Donec gravida lacus ut tortor facilisis vestibulum. Vivamus venenatis hendrerit neque et porttitor. Aenean ultricies quis nibh nec euismod. Aliquam aliquam erat eget erat blandit sollicitudin. Curabitur non quam eget neque dignissim posuere.</p>
			<p>Nullam euismod venenatis eleifend. Donec et iaculis velit. Sed in magna sit amet felis egestas sagittis id ut velit. Morbi eleifend dictum interdum. Vestibulum convallis rutrum erat id lacinia. Pellentesque vulputate porta vehicula. Curabitur sagittis vel diam at placerat. Aliquam auctor diam sit amet risus convallis dignissim. Donec fermentum quam porttitor porta sagittis. Maecenas tristique turpis enim, eget convallis neque bibendum vitae. Ut iaculis lacus at rutrum ultrices. Suspendisse ut urna vitae purus eleifend laoreet non sit amet sem. </p>			

			<pagebreak />

			<watermarkimage src="<?php echo PDF_PLUGIN_DIR ?>resources/images/gravityformspdfextended.jpg" alpha="0.1" size="D" position="1,1" />
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam sollicitudin, magna et interdum hendrerit, est libero convallis odio, quis dapibus augue risus condimentum nisl. Quisque in orci dui. Pellentesque volutpat, nibh non eleifend fringilla, massa risus blandit neque, vestibulum adipiscing elit purus in tortor. Ut mauris libero, commodo eu suscipit vel, sagittis nec erat. Maecenas at eros facilisis, vestibulum felis sit amet, pellentesque nunc. Mauris accumsan leo a gravida tincidunt. Aliquam congue, leo vitae consequat tempor, arcu arcu dapibus ipsum, sit amet faucibus sem nulla nec lorem.</p>
			<p>Vivamus eu neque ac tortor fringilla malesuada. Nullam sed orci non erat vehicula volutpat eu sed velit. Donec gravida lacus ut tortor facilisis vestibulum. Vivamus venenatis hendrerit neque et porttitor. Aenean ultricies quis nibh nec euismod. Aliquam aliquam erat eget erat blandit sollicitudin. Curabitur non quam eget neque dignissim posuere.</p>
			<p>Nullam euismod venenatis eleifend. Donec et iaculis velit. Sed in magna sit amet felis egestas sagittis id ut velit. Morbi eleifend dictum interdum. Vestibulum convallis rutrum erat id lacinia. Pellentesque vulputate porta vehicula. Curabitur sagittis vel diam at placerat. Aliquam auctor diam sit amet risus convallis dignissim. Donec fermentum quam porttitor porta sagittis. Maecenas tristique turpis enim, eget convallis neque bibendum vitae. Ut iaculis lacus at rutrum ultrices. Suspendisse ut urna vitae purus eleifend laoreet non sit amet sem. </p>			

			<pagebreak />

			<watermarkimage src="<?php echo PDF_PLUGIN_DIR ?>resources/images/gravityformspdfextended.jpg" alpha="0.5" size="50, 50" position="P" />
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam sollicitudin, magna et interdum hendrerit, est libero convallis odio, quis dapibus augue risus condimentum nisl. Quisque in orci dui. Pellentesque volutpat, nibh non eleifend fringilla, massa risus blandit neque, vestibulum adipiscing elit purus in tortor. Ut mauris libero, commodo eu suscipit vel, sagittis nec erat. Maecenas at eros facilisis, vestibulum felis sit amet, pellentesque nunc. Mauris accumsan leo a gravida tincidunt. Aliquam congue, leo vitae consequat tempor, arcu arcu dapibus ipsum, sit amet faucibus sem nulla nec lorem.</p>
			<p>Vivamus eu neque ac tortor fringilla malesuada. Nullam sed orci non erat vehicula volutpat eu sed velit. Donec gravida lacus ut tortor facilisis vestibulum. Vivamus venenatis hendrerit neque et porttitor. Aenean ultricies quis nibh nec euismod. Aliquam aliquam erat eget erat blandit sollicitudin. Curabitur non quam eget neque dignissim posuere.</p>
			<p>Nullam euismod venenatis eleifend. Donec et iaculis velit. Sed in magna sit amet felis egestas sagittis id ut velit. Morbi eleifend dictum interdum. Vestibulum convallis rutrum erat id lacinia. Pellentesque vulputate porta vehicula. Curabitur sagittis vel diam at placerat. Aliquam auctor diam sit amet risus convallis dignissim. Donec fermentum quam porttitor porta sagittis. Maecenas tristique turpis enim, eget convallis neque bibendum vitae. Ut iaculis lacus at rutrum ultrices. Suspendisse ut urna vitae purus eleifend laoreet non sit amet sem. </p>			

        <?php } ?>
	</body>
</html>