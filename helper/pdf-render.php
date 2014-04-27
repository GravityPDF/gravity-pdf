<?php


class PDFRender
	{
	/**
	 * Outputs a PDF entry from a Gravity Form
	 * var $form_id integer: The form id
	 * var $lead_id integer: The entry id
	 * var $output string: either view, save or download
	 * save will save a copy of the PDF to the server using the PDF_SAVE_LOCATION constant
	 * var $return boolean: if set to true 
	 it will return the path of the saved PDF
	 * var $template string: if you want to use multiple PDF templates - name of the template file
	 * var $pdfname string: allows you to pass a custom PDF name to the generator e.g. 'Application Form.pdf' (ensure .pdf is appended to the filename)
	 * var $fpdf boolean: custom hook to allow the FPDF engine to generate PDFs instead of DOMPDF. Premium Paid Feature.
	 */
	public function PDF_Generator($form_id, $lead_id, $arguments = array())
	{
		/* 
		 * Because we merged the create and attach functions we need a measure to only run this function once per session per lead id. 
		 */
		static $pdf_creator = array();	

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
		 * Check if the PDF exists and if this function has already run this season 
		 */	
		if(in_array($lead_id, $pdf_creator) && file_exists(PDF_SAVE_LOCATION.$id.'/'. $filename))
		{			
			/* 
			 * Don't generate a new PDF, use the existing one 
			 */
			return PDF_SAVE_LOCATION.$id.'/'. $filename;	
		}
		
		/*
		 * Add lead to PDF creation tracker
		 */
		$pdf_creator[] = $lead_id;

		/*
		 * Add filter before we load the template file so we can stop the main process
		 * Used in premium plugins
		 * return true to cancel, otherwise run.
		 */	 
		 $return = apply_filters('gfpdfe_pre_load_template', $form_id, $lead_id, $template, $id, $output, $filename, $arguments);

		if($return !== true)
		{
			/*
			 * Get the tempalte HTML file
			 */
			$entry = $this->load_entry_data($form_id, $lead_id, $template);					

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
				return $this->PDF_processing($entry, $filename, $id, $output, $arguments);
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
	private function load_entry_data($form_id, $lead_id, $template)
	{
		/* set up contstants for gravity forms to use so we can override the security on the printed version */		
		if(file_exists(PDF_TEMPLATE_LOCATION.$template))
		{	
			return PDF_Common::get_html_template(PDF_TEMPLATE_LOCATION.$template);
		}
		else
		{
			/*
			 * Check if template file exists in the plugin's core template folder
			 */
			if(file_exists(PDF_PLUGIN_DIR."templates/" . $template))
			{
				return PDF_Common::get_html_template(PDF_PLUGIN_DIR."templates/" . $template);
			}
			/*
			 * If template not found then we will resort to the default template.
			 */			
			else
			{
				return PDF_Common::get_html_template(PDF_PLUGIN_DIR."templates/" . PDFGenerator::$default['template']);
			}
		}		
	}

	
	/**
	 * Creates the PDF and does a specific output (see PDF_Generator function above for $output variable types)
	 */
	public function PDF_processing($html, $filename, $id, $output = 'view', $arguments)
	{
		/* 
		 * DOMPDF replaced with mPDF in v3.0.0 
		 * Check which version of mpdf we are calling
		 * Full, Lite or Tiny
		 */
		 if(!class_exists('mPDF'))
		 {
			 if(PDF_ENABLE_MPDF_TINY === true)
			 {
					include PDF_PLUGIN_DIR .'/mPDF/mpdf-extra-lite.php';			 
			 }
			 elseif(PDF_ENABLE_MPDF_LITE === true)
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
		 * Display PDF is full-page mode which allows the entire PDF page to be viewed
		 * Normally PDF is zoomed right in.
		 */
		$mpdf->SetDisplayMode('fullpage');				
		
		/*
		 * Automatically detect fonts and substitue as needed
		 */
		$mpdf->SetAutoFont(AUTOFONT_ALL);
		$mpdf->useSubstitutions = true;
		
		/*
		 * Set Creator Meta Data
		 */
		
		$mpdf->SetCreator('Gravity Forms PDF Extended v'. PDF_EXTENDED_VERSION.'. http://gravityformspdfextended.com');	

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
		 if($arguments['security'] === true)
		 {
				$password = (strlen($arguments['pdf_password']) > 0) ? $arguments['pdf_password'] : '';
				$master_password = (strlen($arguments['pdf_password']) > 0) ? $arguments['pdf_password'] : null;
				$pdf_privileges = (is_array($arguments['pdf_privileges'])) ? $arguments['pdf_privileges'] : array();	
				
				$mpdf->SetProtection($pdf_privileges, $password, $master_password, 128);											
		 }
		 	 
		 
		/* load HTML block */
		$mpdf->WriteHTML($html);			
		
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
				$pdf = $mpdf->Output('', 'S');
				return $this->savePDF($pdf, $filename, $id);				 
			break;
		}
	}
	 
	
	/**
	 * Creates the PDF and does a specific output (see PDF_Generator function above for $output variable types)
	 * var $dompdf Object
	 */
	 public function savePDF($pdf, $filename, $id) 
	 {			
		/* create unique folder for PDFs */
		if(!is_dir(PDF_SAVE_LOCATION.$id))
		{
			if(!mkdir(PDF_SAVE_LOCATION.$id))
			{
				trigger_error('Could not create PDF folder in '. PDF_SAVE_LOCATION.$id, E_USER_WARNING);				
				return;
			}
		}	
		
		$pdf_save = PDF_SAVE_LOCATION.$id.'/'. $filename;			
				
		if(!file_put_contents($pdf_save, $pdf))
		{
			trigger_error('Could not save PDF to '. $pdf_save, E_USER_WARNING);
			return;
		}
		return $pdf_save;
	}
}

