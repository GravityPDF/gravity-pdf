<?php

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

class PDFRender
	{
	/**
	 * Outputs a PDF entry from a Gravity Form
	 * var $form_id integer: The form id
	 * var $lead_id integer: The entry id
	 * var $output string: either view, save or download
	 * save will save a copy of the PDF to the server using the $gfpdfe_data->template_save_location variable
	 * var $return boolean: if set to true 
	 * it will return the path of the saved PDF
	 * var $template string: if you want to use multiple PDF templates - name of the template file
	 * var $pdfname string: allows you to pass a custom PDF name to the generator e.g. 'Application Form.pdf' (ensure .pdf is appended to the filename)
	 * var $fpdf boolean: custom hook to allow the FPDF engine to generate PDFs instead of DOMPDF. Premium Paid Feature.
	 */
	public function PDF_Generator($form_id, $lead_id, $arguments = array())
	{
		global $gfpdfe_data;

		/*
		 * Set user-variable to output HTML instead of PDF
		 */		
		 $html = (isset($_GET['html'])) ? (int) $_GET['html'] : 0;

		/*
		 * Join the form and lead IDs together to get the real ID
		 */
		$id = $form_id . $lead_id;
		
		/* 
		 * PDF_Generator was becoming too cluttered so store all the variables in an array 
		 */
		 $filename = $arguments['pdfname'];
		 $template = $arguments['template'];		 
		 $output = (isset($arguments['output']) && strlen($arguments['output']) > 0) ? $arguments['output'] : 'save';

		/*
		 * Add filter before we load the template file so we can stop the main process
		 * Used in premium plugins
		 * return true to cancel, otherwise run.
		 */	 
		 $return = apply_filters("gfpdfe_pre_load_template", $form_id, $lead_id, $template, $id, $output, $filename, $arguments);

		if($return !== true)
		{
			/*
			 * Get the template HTML file
			 * v3.4.0 allows mergetags to be used in PDF templates
			 */
			$raw_entry = $this->load_entry_data($template);
			$entry = apply_filters("gfpdfe_pdf_template_{$form_id}", apply_filters('gfpdfe_pdf_template', $raw_entry, $form_id, $lead_id, $arguments), $lead_id, $arguments);					

			/*
			 * Output HTML version and return if user requested a HTML version
			 */		 
			if($html === 1)
			{
				echo $entry;
				exit;	
			}
		
			/*
			 * If successfully got the entry then run the processor
			 */
			if(strlen($entry) > 0)
			{
				return $this->PDF_processing($entry, $filename, $id, $output, $arguments, $form_id, $lead_id);
			}
	
			return false;
		}
		/*
		 * Used in extensions to return the name of the PDF file when attaching to notifications
		 */
		return apply_filters('gfpdfe_return_pdf_path', $form_id, $lead_id);
	}
	
	/**
	 * Loads the Gravity Form output script (actually the print preview)
	 */
	private function load_entry_data($template)
	{
		global $gfpdfe_data;

		/* set up contstants for gravity forms to use so we can override the security on the printed version */		
		if(file_exists( $gfpdfe_data->template_site_location . $template))
		{		
			return PDF_Common::get_html_template($gfpdfe_data->template_site_location . $template);
		}
		else
		{
			/*
			 * Check if template file exists in the plugin's core template folder
			 */
			if(file_exists( PDF_PLUGIN_DIR . 'templates/' . $template))
			{
				return PDF_Common::get_html_template( PDF_PLUGIN_DIR . 'initialisation/templates/' . $template);
			}
			/*
			 * If template not found then we will resort to the default template.
			 */			
			else
			{				
				return PDF_Common::get_html_template(PDF_PLUGIN_DIR . 'initialisation/templates/' . PDFGenerator::$default['template']);
			}
		}		
	}

	
	/**
	 * Creates the PDF and does a specific output (see PDF_Generator function above for $output variable types)
	 */
	public function PDF_processing($html, $filename, $id, $output = 'view', $arguments, $form_id, $lead_id)
	{
		/* 
		 * DOMPDF replaced with mPDF in v3.0.0 
		 * Check which version of mpdf we are calling
		 * Full, Lite or Tiny
		 */
		 if(!class_exists('mPDF'))
		 {
			 if(defined('PDF_ENABLE_MPDF_TINY') && PDF_ENABLE_MPDF_TINY === true)
			 {
					include PDF_PLUGIN_DIR .'/mPDF/mpdf-extra-lite.php';			 
			 }
			 elseif(defined('PDF_ENABLE_MPDF_LITE') && PDF_ENABLE_MPDF_LITE === true)
			 {
					include PDF_PLUGIN_DIR .'/mPDF/mpdf-lite.php';			 
			 }
			 else
			 {	 		
					include PDF_PLUGIN_DIR .'/mPDF/mpdf.php';
			 }
		 }
		
		/* 
		 * Initialise class and set the paper size and orientation
		 */
		 $paper_size = $arguments['pdf_size'];		
		 
		 if(!is_array($paper_size))
		 {
			 $orientation = ($arguments['orientation'] == 'landscape') ? '-L' : '';
			 $paper_size = $paper_size.$orientation;
		 }
		 else
		 {
		 	$orientation = ($arguments['orientation'] == 'landscape') ? 'L' : 'P';			 			
		 }
		 
		 $mpdf = new mPDF('', $paper_size, 0, '', 15, 15, 16, 16, 9, 9, $orientation);		 

		 /* 
		  * Add filter to allow the $mpdf object to be modified right after the class is created.
		  * Because $mpdf is a class it is automatically passed by reference and doesn't need to be assigned
		  */ 
		 apply_filters('gfpdfe_mpdf_class', $mpdf, $form_id, $lead_id, $arguments, $output, $filename);
		
		/*
		 * Display PDF is full-page mode which allows the entire PDF page to be viewed
		 * Normally PDF is zoomed right in.
		 */
		$mpdf->SetDisplayMode('fullpage');				
		
		/*
		 * Automatically detect fonts and substitue as needed
		 */
		$mpdf->SetAutoFont(AUTOFONT_ALL);
		(defined('PDF_DISABLE_FONT_SUBSTITUTION') && PDF_DISABLE_FONT_SUBSTITUTION === false) ? $mpdf->useSubstitutions = true : $mpdf->useSubstitutions = false;
		
		/*
		 * Set Creator Meta Data
		 */		
		$mpdf->SetCreator('Gravity PDF v' . PDF_EXTENDED_VERSION . '. https://gravitypdf.com');	

		/*
		 * Set PDF DPI if added to configuration node
		 */
		if($arguments['dpi'] !== false)
		{
			/* TEXT DPI dramatically decreases the text size. As text is a vector element of the document we will only be concerned with the image DPI for the moment */
			/*$mpdf->dpi     = $arguments['dpi'];*/
			$mpdf->img_dpi = $arguments['dpi'];
		}

		/*
		 * Set RTL languages at user request
		 */ 
		 if($arguments['rtl'] === true)
		 {
		 	$mpdf->SetDirectionality('rtl');
		 }

		/*
		 * Set up security if user requested
		 */ 
		 if($arguments['security'] === true && $arguments['pdfa1b'] === false && $arguments['pdfx1a'] === false  )
		 {
				$password        = (strlen($arguments['pdf_password']) > 0) ? $arguments['pdf_password'] : '';
				$master_password = (strlen($arguments['pdf_master_password']) > 0) ? $arguments['pdf_master_password'] : null;
				$pdf_privileges  = (is_array($arguments['pdf_privileges'])) ? $arguments['pdf_privileges'] : array();	
				
				$mpdf->SetProtection($pdf_privileges, $password, $master_password, 128);											
		 }

		 /* PDF/A1-b support added in v3.4.0 */
		 if($arguments['pdfa1b'] === true)
		 {
				$mpdf->PDFA     = true;
				$mpdf->PDFAauto = true;
		 }
		 else if($arguments['pdfx1a'] === true)  /* PDF/X-1a support added in v3.4.0 */
		 {
				$mpdf->PDFX     = true;
				$mpdf->PDFXauto = true;
		 }
		 
		 /*
		  * Check if we should auto prompt to print the document on open
		  */
		  if(isset($_GET['print']))
		  {
				$mpdf->SetJS('this.print();');  
		  }
		 	 
		 
		/* load HTML block */
		$mpdf->WriteHTML($html);		

		/*
		 * Allow the $mpdf object to be modified now all the settings have been applied
		 */		
		apply_filters('gfpdfe_mpdf_class_pre_render', $mpdf, $form_id, $lead_id, $arguments, $output, $filename);
		apply_filters('gfpdfe_pre_render_pdf', $mpdf, $form_id, $lead_id, $arguments, $output, $filename); /* left in for backwards compatiblity */

		/*
		 * Add pre-render/save filter so PDF can be manipulated further
		 */			
		$output   = apply_filters('gfpdfe_pdf_output_type', $output);
		$filename = apply_filters('gfpdfe_pdf_filename', $filename);
		
		switch($output)
		{
			case 'download':
				 $mpdf->Output($filename, 'D');
				 exit;
			break;
			
			case 'view':
				 $mpdf->Output($filename, 'I');
				 exit;
			break;
			
			case 'save':
				/*
				 * PDF wasn't writing to file with the F method - http://mpdf1.com/manual/index.php?tid=125
				 * Return as a string and write to file manually
				 */					
				$pdf      = $mpdf->Output('', 'S');
				$filename = $this->savePDF($pdf, $filename, $id);				 

				do_action('gfpdf_post_pdf_save', $form_id, $lead_id, $arguments, $filename);

				return $filename;
			break;
		}
	}
	 
	
	/**
	 * Creates the PDF and does a specific output (see PDF_Generator function above for $output variable types)
	 * var $dompdf Object
	 */
	 public function savePDF($pdf, $filename, $id) 
	 {			
	 	global $gfpdfe_data;

		/* create unique folder for PDFs */
		if(!is_dir( $gfpdfe_data->template_save_location . $id))
		{
			if(!mkdir( $gfpdfe_data->template_save_location . $id))
			{
				trigger_error('Could not create PDF folder in '. $gfpdfe_data->template_save_location . $id, E_USER_WARNING);				
				return;
			}
		}	
		
		$pdf_save = $gfpdfe_data->template_save_location . $id . '/' . $filename;			
				
		if(!file_put_contents($pdf_save, $pdf))
		{
			trigger_error('Could not save PDF to '. $pdf_save, E_USER_WARNING);
			return;
		}		

		return $pdf_save;
	}
}

