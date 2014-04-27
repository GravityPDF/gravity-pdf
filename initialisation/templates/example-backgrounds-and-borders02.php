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
    
    <link rel='stylesheet' href='<?php echo $stylesheet_location; ?>' type='text/css' />
    <title>Gravity Forms PDF Extended</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
	
		body {
			background: linear-gradient(bottom, #0b91c2, #FFFFFF 65%);
		}
		.gradient {
			border:0.1mm solid #220044; 
			background: #f0f2ff linear-gradient(top, #e1e1e1 65%, #c7c7c7);
		}
		.radialgradient {
			border:0.1mm solid #220044; 
			background: #f0f2ff radial-gradient(center circle, #00FFFF, #FFFF00)
			margin: auto;
		}
		.rounded {
			border:0.1mm solid #220044; 
			background: #f0f2ff linear-gradient(top, #c7cdde 65%, #f0f2ff);
			border-radius: 2mm;
			background-clip: border-box;
		}
		h4 {
			font-family: sans;
			font-weight: bold;
			margin-top: 1em;
			margin-bottom: 0.5em;
		}
		div {
			padding:1em; 
			margin-bottom: 1em;
			text-align:left; 
		}
		.example pre {
			background-color: #d5d5d5; 
			margin: 1em 1cm;
			padding: 0 0.3cm;
		}
		
		pre { text-align:left }
		pre.code { font-family: monospace }
		
	</style>

</head>
	<body>
        <?php	

        foreach($lead_ids as $lead_id) {

            $lead = RGFormsModel::get_lead($lead_id);
            do_action("gform_print_entry_header", $form, $lead);
            $form_data = GFPDFEntryDetail::lead_detail_grid_array($form, $lead);
			/*
			 * Add &data=1 when viewing the PDF via the admin area to view the $form_data array
			 */
			PDF_Common::view_data($form_data);				
						
			/* get all the form values */
			/*$date_created		= $form_data['date_created'];
			
			$first_name 		= $form_data['1.Name']['first'];
			$last_name 			= $form_data['1.Name']['last'];*/			
		
			/* format the template */						
			?>            
          
           	<img src="<?php echo PDF_PLUGIN_DIR; ?>/resources/images/gravityformspdfextended.jpg" width="311" height="110"  />
           
           



<h1>Backgrounds & Borders</h1>

<div style="border:0.1mm solid #220044; padding:1em 2em; background-color:#ffffcc; ">
<h4>Page background</h4>
<div class="gradient">
The background colour can be set by CSS styles on the &lt;body&gt; tag. This will set the background for the whole page. In this document, the background has been set as a gradient (see below).
</div>

<h4>Background Gradients</h4>
<div class="gradient">
Background can be set as a linear or radial gradient between two colours. The background has been set on this &lt;div&gt; element to a linear gradient. CSS style used here is:<br />
<span style="font-family: mono; font-size: 9pt;">background-gradient: linear #c7cdde #f0f2ff 0 1 0 0.5;</span><br />
The four numbers are coordinates in the form (x1, y1, x2, y2) which defines the gradient vector. x and y are values from 0 to 1, where  1 represents the height or width of the box as it is printed.
<br />
<br />
Background gradients can be set on all block elements e.g. P, DIV, H1-H6, as well as on BODY.
</div>
<div class="radialgradient">
The background has been set on this &lt;div&gt; element to a radial gradient. CSS style used here is:<br />
<span style="font-family: mono; font-size: 9pt;">background-gradient: radial #00FFFF #FFFF00 0.5 0.5 0.5 0.5 0.65;</span><br />
The five numbers are coordinates in the form (x1, y1, x2, y2, r) where (x1, y1) is the starting point of the gradient with color1, 
(x2, y2) is the center of the circle with color2, and r is the radius of the circle.
(x1, y1) should be inside the circle, otherwise some areas will not be defined.
<br />
<br />
Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec mattis lacus ac purus feugiat semper. Donec aliquet nunc odio, vitae pellentesque diam. Pellentesque sed velit lacus. Duis quis dui quis sem consectetur sollicitudin. Cras dolor quam, dapibus et pretium sit amet, elementum vel arcu. Duis rhoncus facilisis erat nec mattis. In hac habitasse platea dictumst. Vivamus hendrerit sem in justo aliquet a pellentesque lorem scelerisque. Suspendisse a augue sed urna rhoncus elementum. Aliquam erat volutpat. 
</div>

<h4>Background Images</h4>
<div style="border:0.1mm solid #880000; background: transparent url(<?php echo PDF_PLUGIN_DIR; ?>resources/images/bg.jpg) repeat fixed right top; background-color:#ccffff; ">
The CSS properties background-image, background-position, and background-repeat are supported as defined in CSS2, as well as the shorthand form "background".
<br />
The background has been set on this &lt;div&gt; element to:<br />
<span style="font-family: mono; font-size: 9pt;">background: transparent url(\'bg.jpg\') repeat fixed right top;</span><br />
Background gradients can be set on all block elements e.g. P, DIV, H1-H6, as well as on BODY.
</div>

<h4>Rounded Borders</h4>
<div class="rounded">
Rounded corners to borders can be added using border-radius as defined in the draft spec. of <a href="http://www.w3.org/TR/2008/WD-css3-background-20080910/#layering">CSS3</a>. <br />

The two length values of the border-*-radius properties define the radii of a quarter ellipse that defines the shape of the corner of the outer border edge.
The first value is the horizontal radius. <br />
<span style="font-family: mono; font-size: 9pt;">border-top-left-radius: 55pt 25pt;</span>  55pt is radius of curve from top end of left border starting to go round to the top.<br />

If the second length is omitted it is equal to the first (and the corner is thus a quarter circle). If either length is zero, the corner is square, not rounded.<br />

The border-radius shorthand sets all four border-*-radius properties. If values are given before and after a slash, then the values before the slash set the horizontal radius and the values after the slash set the vertical radius. If there is no slash, then the values set both radii equally. The four values for each radii are given in the order top-left, top-right, bottom-right, bottom-left. If bottom-left is omitted it is the same as top-right. If bottom-right is omitted it is the same as top-left. If top-right is omitted it is the same as top-left.
</div>
<div class="rounded">
<span style="font-family: mono; font-size: 9pt;">border-radius: 4em;</span><br />

would be equivalent to<br />

<span style="font-family: mono; font-size: 9pt;">border-top-left-radius:     4em;<br />
border-top-right-radius:    4em;<br />
border-bottom-right-radius: 4em;<br />
border-bottom-left-radius:  4em;</span><br />
<br />
and<br />
<span style="font-family: mono; font-size: 9pt;">border-radius: 2em 1em 4em / 0.5em 3em;</span><br />
would be equivalent to<br />
<span style="font-family: mono; font-size: 9pt;">border-top-left-radius:     2em 0.5em;<br />
border-top-right-radius:    1em 3em;<br />
border-bottom-right-radius: 4em 0.5em;<br />
border-bottom-left-radius:  1em 3em;</span>
</div>

</div>
            
         
            <?php
        }

        ?>
	</body>
</html>