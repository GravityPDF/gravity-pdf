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
          
			<img src="<?php echo PDF_PLUGIN_DIR; ?>resources/images/gravityformspdfextended.jpg" width="311" height="110"  />           
			<h2>Basic HTML Example</h2>
			This file demonstrates most of the HTML elements.
			<h3>Heading 3</h3>
			<h4>Heading 4</h4>
			<h5>Heading 5</h5>
			<h6>Heading 6</h6>
			<p>P: Nulla felis erat, imperdiet eu, ullamcorper non, nonummy quis, elit. Suspendisse potenti. Ut a eros at ligula vehicula pretium. Maecenas feugiat pede vel risus. Nulla et lectus. Fusce eleifend neque sit amet erat. Integer consectetuer nulla non orci. Morbi feugiat pulvinar dolor. Cras odio. Donec mattis, nisi id euismod auctor, neque metus pellentesque risus, at eleifend lacus sapien et risus. Phasellus metus. Phasellus feugiat, lectus ac aliquam molestie, leo lacus tincidunt turpis, vel aliquam quam odio et sapien. Mauris ante pede, auctor ac, suscipit quis, malesuada sed, nulla. Integer sit amet odio sit amet lectus luctus euismod. Donec et nulla. Sed quis orci. </p>
			<hr />
			<div><img src="<?php echo PDF_PLUGIN_DIR; ?>resources/images/tiger.wmf" style="float:right;">DIV: Proin aliquet lorem id felis. Curabitur vel libero at mauris nonummy tincidunt. Donec imperdiet. Vestibulum sem sem, lacinia vel, molestie et, laoreet eget, urna. Curabitur viverra faucibus pede. Morbi lobortis. Donec dapibus. Donec tempus. Ut arcu enim, rhoncus ac, venenatis eu, porttitor mollis, dui. Sed vitae risus. In elementum sem placerat dui. Nam tristique eros in nisl. Nulla cursus sapien non quam porta porttitor. Quisque dictum ipsum ornare tortor. Fusce ornare tempus enim. </div>
			<div><img src="<?php echo PDF_PLUGIN_DIR; ?>resources/images/klematis.jpg" style="opacity: 0.5; float: left;" />DIV: Proin aliquet lorem id felis. Curabitur vel libero at mauris nonummy tincidunt. Donec imperdiet. Vestibulum sem sem, lacinia vel, molestie et, laoreet eget, urna. Curabitur viverra faucibus pede. Morbi lobortis. Donec dapibus. Donec tempus. Ut arcu enim, rhoncus ac, venenatis eu, porttitor mollis, dui. Sed vitae risus. In elementum sem placerat dui. Nam tristique eros in nisl. Nulla cursus sapien non quam porta porttitor. Quisque dictum ipsum ornare tortor. Fusce ornare tempus enim. </div>
			<blockquote>Blockquote: Maecenas arcu justo, malesuada eu, dapibus ac, adipiscing vitae, turpis. Fusce mollis. Aliquam egestas. In purus dolor, facilisis at, fermentum nec, molestie et, metus. Maecenas arcu justo, malesuada eu, dapibus ac, adipiscing vitae, turpis. Fusce mollis. Aliquam egestas. In purus dolor, facilisis at, fermentum nec, molestie et, metus.</blockquote>
			<address>Address: Vestibulum feugiat, orci at imperdiet tincidunt, mauris erat facilisis urna, sagittis ultricies dui nisl et lectus. Sed lacinia, lectus vitae dictum sodales, elit ipsum ultrices orci, non euismod arcu diam non metus.</address>
			<pre>PRE: Cum sociis natoque penatibus et magnis dis parturient montes, 
						nascetur ridiculus mus. In suscipit turpis vitae odio. Integer convallis 
						dui at metus. Fusce magna. Sed sed lectus vitae enim tempor cursus. Cras 
						sed, posuere et, urna. Quisque ut leo. Aliquam interdum hendrerit tortor. 
						Vestibulum elit. Vestibulum et arcu at diam mattis commodo. Nam ipsum sem, 
						ultricies at, rutrum sit amet, posuere nec, velit. Sed molestie mollis dui.</pre>
			<div><a href="#top">Hyperlink (&lt;a&gt;)</a></div>
			<div><a href="http://www.gravityformspdfextended.com">Hyperlink (&lt;a&gt;)</a></div>
			<div>Styles - <tt>tt(teletype)</tt> <i>italic</i> <b>bold</b> <big>big</big> <small>small</small> <em>emphasis</em> <strong>strong</strong> <br />new lines<br>
				<code>code</code> <samp>sample</samp> <kbd>keyboard</kbd> <var>variable</var> <cite>citation</cite> <abbr>abbr.</abbr> <acronym>ACRONYM</acronym> <sup>sup</sup> <sub>sub</sub> <strike>strike</strike> <s>strike-s</s> <u>underline</u> <del>delete</del> <ins>insert</ins> <q>To be or not to be</q> <font face="sans-serif" color="#880000" size="5">font changing face, size and color</font>
			</div>
			<p style="font-size:15pt; color:#440066">Paragraph using the in-line style to determine the font-size (15pt) and colour</p>
			<h3>Testing BIG, SMALL, UNDERLINE, STRIKETHROUGH, FONT color, ACRONYM, SUPERSCRIPT and SUBSCRIPT</h3>
			<p>This is <s>strikethrough</s> in <b><s>block</s></b> and <small>small <s>strikethrough</s> in <i>small span</i></small> and <big>big <s>strikethrough</s> in big span</big> and then <u>underline and <s>strikethrough and <sup>sup</sup></s></u> but out of span again but <font color="#000088">blue</font> font and <acronym>ACRONYM</acronym> text</p>
			<p>This is a <font color="#008800">green reference<sup>32-47</sup></font> and <u>underlined reference<sup>32-47</sup></u> then reference<sub>32-47</sub> and <u>underlined reference<sub>32-47</sub></u> then <s>Strikethrough reference<sup>32-47</sup></s> and <s>strikethrough reference<sub>32-47</sub></s></p>
			<p><big>Repeated in <u>BIG</u>: This is reference<sup>32-47</sup> and <u>underlined reference<sup>32-47</sup></u> then reference<sub>32-47</sub> and <u>underlined reference<sub>32-47</sub></u> but out of span again but <font color="#000088">blue</font> font and <acronym>ACRONYM</acronym> text</big></p>
			<p><small>Repeated in small: This is reference<sup>32-47</sup> and <u>underlined reference<sup>32-47</sup></u> then reference<sub>32-47</sub> and <u>underlined reference<sub>32-47</sub></u> but out of span again but <font color="#000088">blue</font> font and <acronym>ACRONYM</acronym> text</small></p>
			<p>The above repeated, but starting with a paragraph with font-size specified (7pt)</p>
			<p style="font-size:7pt;">This is <s>strikethrough</s> in block and <small>small <s>strikethrough</s> in small span</small> and then <u>underline</u> but out of span again but <font color="#000088">blue</font> font and <acronym>ACRONYM</acronym> text</p>
			<p style="font-size:7pt;">This is <s>strikethrough</s> in block and <big>big <s>strikethrough</s> in big span</big> and then <u>underline</u> but out of span again but <font color="#000088">blue</font> font and <acronym>ACRONYM</acronym> text</p>
			<p style="font-size:7pt;">This is reference<sup>32-47</sup> and <u>underlined reference<sup>32-47</sup></u> then reference<sub>32-47</sub> and <u>underlined reference<sub>32-47</sub></u> then <s>Strikethrough reference<sup>32-47</sup></s> and <s>strikethrough reference<sub>32-47</sub></s></p>
			<p><small>This tests <u>underline</u> and <s>strikethrough</s> when they are <s><u>used together</u></s> as they both use text-decoration</small></p>
			<p><small>Repeated in small: This is reference<sup>32-47</sup> and <u>underlined reference<sup>32-47</sup></u> then reference<sub>32-47</sub> and <u>underlined reference<sub>32-47</sub></u> but out of span again but <font color="#000088">blue</font> font and <acronym>ACRONYM</acronym> text</small></p>
			<p style="font-size:7pt;"><big>Repeated in BIG but with font-size set to 7pt by in-line css: This is reference<sup>32-47</sup> and <u>underlined reference<sup>32-47</sup></u> then reference<sub>32-47</sub> and <u>underlined reference<sub>32-47</sub></u> but out of span again but <font color="#000088">blue</font> font and <acronym>ACRONYM</acronym> text</big></p>
			<ol>
				<li>Item <b><u>1</u></b></li>
				<li>Item 2<sup>32</sup></li>
				<li><small>Item</small> 3</li>
				<li>
					Praesent pharetra nulla in turpis. Sed ipsum nulla, sodales nec, vulputate in, scelerisque vitae, magna. Sed egestas justo nec ipsum. Nulla facilisi. Praesent sit amet pede quis metus aliquet vulputate. Donec luctus. Cras euismod tellus vel leo. 
					<ul>
						<li>Praesent pharetra nulla in turpis. Sed ipsum nulla, sodales nec, vulputate in, scelerisque vitae, magna. Sed egestas justo nec ipsum. Nulla facilisi. Praesent sit amet pede quis metus aliquet vulputate. Donec luctus. Cras euismod tellus vel leo. </li>
						<li>
							Subitem 2
							<ul>
								<li>
									Level 3 subitem
								</li>
							</ul>
						</li>
					</ul>
				</li>
				<li>Item 5</li>
			</ol>
			<dl>
				<dt>Definition list</dt>
				<dd>List defined by DL, DD and DT tags</dd>
			</dl>
			<p>Sed bibendum. Nunc eleifend ornare velit. Sed consectetuer urna in erat. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Mauris sodales semper metus. Maecenas justo libero, pretium at, malesuada eu, mollis et, arcu. Ut suscipit pede in nulla. Praesent elementum, dolor ac fringilla posuere, elit libero rutrum massa, vel tincidunt dui tellus a ante. Sed aliquet euismod dolor. Vestibulum sed dui. Duis lobortis hendrerit quam. Donec tempus orci ut libero. Pellentesque suscipit malesuada nisi. </p>
			<table border="1">
				<thead>
					<tr>
						<th>Data</th>
						<td>Data</td>
						<td>Data</td>
						<td>Data<br />2nd line</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th>More Data</th>
						<td>More Data</td>
						<td>More Data</td>
						<td>Data<br />2nd line</td>
					</tr>
					<tr>
						<th>Data</th>
						<td>Data</td>
						<td>Data</td>
						<td>Data<br />2nd line</td>
					</tr>
					<tr>
						<th>Data</th>
						<td>Data</td>
						<td>Data</td>
						<td>Data<br />2nd line</td>
					</tr>
				</tbody>
			</table>
			<p>Praesent pharetra nulla in turpis. Sed ipsum nulla, sodales nec, vulputate in, scelerisque vitae, magna. Sed egestas justo nec ipsum. Nulla facilisi. Praesent sit amet pede quis metus aliquet vulputate. Donec luctus. Cras euismod tellus vel leo. Cras tellus. Fusce aliquet. Curabitur tincidunt viverra ligula. Fusce eget erat. Donec pede. Vestibulum id felis. Phasellus tincidunt ligula non pede. Morbi turpis. In vitae dui non erat placerat malesuada. Mauris adipiscing congue ante. Proin at erat. Aliquam mattis. </p>
  
            <?php
        }

        ?>
	</body>
</html>