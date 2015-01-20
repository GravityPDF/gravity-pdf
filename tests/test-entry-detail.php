<?php 

/**
 * Run unit tests on the GFPDFEntryDetail /**
 * We will concentrate on the $form_data array and ensuring 
 * we unit test every field to verify the output is correct 
 */

class Test_EntryDetails extends WP_UnitTestCase {
	private $form_id = array();
	private $entries = array();
	private $form_data;

	public function setUp() {		
        
		/*
		 * Replace set up with cut down version 
		 * so we don't use 'temporary' tables in MySQL
		 */
		$this->cut_down_setup();

		/* initialise GF tables */
		GFForms::setup(true);			

		/* Load our plugin functions */
		GFPDF_InstallUpdater::check_filesystem_api();
		GFPDF_InstallUpdater::maybe_deploy();				

		/* create GF data */
		$this->create_form_and_entries();
	}

	private function cut_down_setup()
	{
        global $wpdb;

        /*
         * Do DB logic 
         */
        $wpdb->suppress_errors = false;
        $wpdb->show_errors = true;
        $wpdb->db_connect();
        $wpdb->query( 'SET autocommit = 0;' );
        $wpdb->query( 'START TRANSACTION;' );		
	}

	public function tearDown() {		
		parent::tearDown();		

        /*
         * Uninstall Gravity Forms
         */
        RGFormsModel::drop_tables();		
	}		

	private function create_form_and_entries() {
		$this->create_forms();
		$this->create_entries();
		$this->setup_form_data();
	}	

	private function create_forms()
	{
		$json = <<<EOD
{"title":"ALL FIELDS","description":"This is the form description...","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"type":"text","id":1,"label":"Single Line Text","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the single line text description","cssClass":"exclude","inputType":""},{"type":"textarea","id":2,"label":"Paragraph Text","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the paragraph text description","inputType":""},{"type":"select","id":3,"label":"Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"Option 1","value":"Option 1","isSelected":false,"price":""},{"text":"Option 2","value":"Option 2","isSelected":true,"price":""},{"text":"Option 3","value":"Option 3 Value","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the drop down description","inputType":""},{"type":"multiselect","id":4,"label":"Multi Select Box","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Multi Select Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Multi Select Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the multi select box description","inputType":""},{"type":"number","id":5,"label":"Number","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"numberFormat":"decimal_dot","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","inputType":""},{"type":"checkbox","id":6,"label":"Checkbox","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","choices":[{"text":"Checkbox Choice 1","value":"Checkbox Choice 1","isSelected":false,"price":""},{"text":"Checkbox Choice 2","value":"Checkbox Choice 2","isSelected":false,"price":""},{"text":"Checkbox Choice 3","value":"Checkbox Choice 3","isSelected":false,"price":""}],"inputs":[{"id":"6.1","label":"Checkbox Choice 1","name":""},{"id":"6.2","label":"Checkbox Choice 2","name":""},{"id":"6.3","label":"Checkbox Choice 3","name":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Checkbox description"},{"type":"radio","id":7,"label":"Radio Button","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"Radio First Choice","value":"Radio First Choice","isSelected":false,"price":""},{"text":"Radio Second Choice Name","value":"Radio Second Choice","isSelected":false,"price":""},{"text":"Radio Third Choice","value":"Radio Third Choice","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Radio button description","enableOtherChoice":true},{"type":"hidden","id":8,"label":"Hidden Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"hidden field value"},{"type":"html","id":9,"label":"HTML Block","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"displayOnly":true,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"content":"This is a HTML block"},{"type":"section","id":10,"label":"Section Break","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"displayOnly":true,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Section Break Description"},{"type":"name","id":11,"label":"Name","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","nameFormat":"advanced","inputs":[{"id":"11.2","label":"Prefix","name":"","choices":[{"text":"Mr.","value":"Mr.","isSelected":false,"price":""},{"text":"Mrs.","value":"Mrs.","isSelected":false,"price":""},{"text":"Miss","value":"Miss","isSelected":false,"price":""},{"text":"Ms.","value":"Ms.","isSelected":false,"price":""},{"text":"Dr.","value":"Dr.","isSelected":false,"price":""},{"text":"Prof.","value":"Prof.","isSelected":false,"price":""},{"text":"Rev.","value":"Rev.","isSelected":false,"price":""}],"isHidden":false,"inputType":"radio"},{"id":"11.3","label":"First","name":""},{"id":"11.4","label":"Middle","name":"","isHidden":false},{"id":"11.6","label":"Last","name":""},{"id":"11.8","label":"Suffix","name":"","isHidden":false}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Name Description"},{"type":"date","id":12,"label":"Date","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","dateType":"datepicker","calendarIconType":"none","calendarIconUrl":"","allowsPrepopulate":false,"description":"Date Description","dateFormat":"dmy"},{"type":"time","id":13,"label":"Time","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"13.1","label":"HH","name":""},{"id":"13.2","label":"MM","name":""},{"id":"13.3","label":"AM\/PM","name":""}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"timeFormat":"12"},{"type":"phone","id":14,"label":"Phone","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"address","id":15,"label":"Address","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":15.1,"label":"Street Address","name":""},{"id":15.2,"label":"Address Line 2","name":""},{"id":15.3,"label":"City","name":""},{"id":15.4,"label":"State \/ Province","name":""},{"id":15.5,"label":"ZIP \/ Postal Code","name":""},{"id":15.6,"label":"Country","name":""}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"website","id":16,"label":"Website","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"email","id":17,"label":"Email","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"fileupload","id":18,"label":"File","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"fileupload","id":19,"label":"File","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":true,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"inputType":"","defaultValue":"","description":""},{"type":"list","id":20,"label":"Basic List","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"list","id":21,"label":"Extended List","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":[{"text":"Column 1","value":"Column 1","isSelected":false,"price":""},{"text":"Column 2","value":"Column 2","isSelected":false,"price":""},{"text":"Column 3","value":"Column 3","isSelected":false,"price":""}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"enableColumns":true},{"type":"poll","id":22,"label":"Poll Field - Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"poll_field_type":"select","inputType":"select","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Dropdown - First Choice","value":"gpoll22daaa4947","isSelected":false,"price":""},{"text":"Poll Dropdown - Second Choice","value":"gpoll220a301dd5","isSelected":false,"price":""},{"text":"Poll Dropdown - Third Choice","value":"gpoll22175a8601","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"poll","id":23,"label":"Poll Field - Radio Buttons","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"poll_field_type":"radio","inputType":"radio","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Radio - First Choice","value":"gpoll23517d6a14","isSelected":false,"price":""},{"text":"Poll Radio - Second Choice","value":"gpoll23ee2a7382","isSelected":false,"price":""},{"text":"Poll Radio - Third Choice","value":"gpoll232553ed18","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"poll","id":41,"label":"Poll Field - Checkboxes","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"41.1","label":"Poll Check First Choice","name":""},{"id":"41.2","label":"Poll Check Second Choice","name":""},{"id":"41.3","label":"Poll Check Third Choice","name":""}],"poll_field_type":"checkbox","inputType":"checkbox","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Check First Choice","value":"gpoll23517d6a14","isSelected":false,"price":""},{"text":"Poll Check Second Choice","value":"gpoll23ee2a7382","isSelected":false,"price":""},{"text":"Poll Check Third Choice","value":"gpoll232553ed18","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":24,"label":"Quiz Dropdown","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"gquizFieldType":"select","inputType":"select","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":true,"choices":[{"text":"Quiz Dropdown - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"3"},{"text":"Quiz Dropdown - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"2"},{"text":"Quiz Dropdown - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"1"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":42,"label":"Quiz Radio","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"gquizFieldType":"radio","inputType":"radio","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":false,"choices":[{"text":"Quiz Radio - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"},{"text":"Quiz Radio - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"},{"text":"Quiz Radio - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":43,"label":"Quiz Checkbox","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"43.1","label":"Quiz Checkbox - First Choice","name":""},{"id":"43.2","label":"Quiz Checkbox - Second Choice","name":""},{"id":"43.3","label":"Quiz Checkbox - Third Choice","name":""}],"gquizFieldType":"checkbox","inputType":"checkbox","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":false,"choices":[{"text":"Quiz Checkbox - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"},{"text":"Quiz Checkbox - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"},{"text":"Quiz Checkbox - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"id":25,"label":"Signature","adminLabel":"","type":"signature","isRequired":false,"size":"medium","errorMessage":"","formId":58,"pageNumber":1,"choices":"","inputs":"","conditionalLogic":"","displayOnly":""},{"id":26,"label":"Likert Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Strongly disagree","value":"glikertcol2636762f85","isSelected":false,"score":1},{"text":"Disagree","value":"glikertcol26a40c345c","isSelected":false,"score":2},{"text":"Neutral","value":"glikertcol26114a03dd","isSelected":false,"score":3},{"text":"Agree","value":"glikertcol26d3452ed6","isSelected":false,"score":4},{"text":"Strongly agree","value":"glikertcol2648e6c579","isSelected":false,"score":5}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrowd6ee998c"},{"text":"Second row","value":"glikertrow3ba9477d"},{"text":"Third row","value":"glikertrowbfdd8b2d"},{"text":"Fourth row","value":"glikertrowb042f1a8"},{"text":"Fifth row","value":"glikertrow4770db3e"}],"inputType":"likert","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"id":27,"label":"Likert Survey Field Extended","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"27.1","label":"First row","name":"glikertrowb9cfdef9"},{"id":"27.2","label":"Second row","name":"glikertrow5b6e1800"},{"id":"27.3","label":"Third row","name":"glikertrowa5b5f578"},{"id":"27.4","label":"Fourth row","name":"glikertrow27a6d5ef"},{"id":"27.5","label":"Fifth row","name":"glikertrowd17a261b"}],"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":true,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Strongly disagree","value":"glikertcol27573469fe","isSelected":false,"score":1},{"text":"Disagree","value":"glikertcol27114a23c1","isSelected":false,"score":2},{"text":"Neutral","value":"glikertcol27c274ea49","isSelected":false,"score":3},{"text":"Agree","value":"glikertcol27cb066f8a","isSelected":false,"score":4},{"text":"Strongly agree","value":"glikertcol275bb3ab84","isSelected":false,"score":5}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrowb9cfdef9"},{"text":"Second row","value":"glikertrow5b6e1800"},{"text":"Third row","value":"glikertrowa5b5f578"},{"text":"Fourth row","value":"glikertrow27a6d5ef"},{"text":"Fifth row","value":"glikertrowd17a261b"}],"inputType":"likert","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"id":44,"label":"Rank Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Rank First Choice","value":"grank44ef548e7b","isSelected":false,"price":""},{"text":"Rank Second Choice","value":"grank440c3f3227","isSelected":false,"price":""},{"text":"Rank Third Choice","value":"grank444f7bdfe0","isSelected":false,"price":""},{"text":"Rank Fourth Choce","value":"grank44902be0db","isSelected":false,"price":""},{"text":"Rank Fifth Choice","value":"grank447f27daf1","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"rank","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","formId":58,"pageNumber":1,"conditionalLogic":""},{"id":45,"label":"Rating Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Terrible","value":"grating45ed195d17","isSelected":false,"price":""},{"text":"Not so great","value":"grating4594b2edee","isSelected":false,"price":""},{"text":"Neutral","value":"grating4506fdbb76","isSelected":false,"price":""},{"text":"Pretty good","value":"grating45c8a62ee6","isSelected":false,"price":""},{"text":"Excellent","value":"grating4581a9f8d2","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"rating","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":46,"label":"Radio Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Survay Radio - First Choice","value":"gsurvey46baa416f0","isSelected":false,"price":""},{"text":"Survay Radio - Second Choice","value":"gsurvey4603c12a75","isSelected":false,"price":""},{"text":"Survay Radio - Third Choice","value":"gsurvey4641726850","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"radio","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":47,"label":"Checkbox Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"47.1","label":"Check - First Choice","name":""},{"id":"47.2","label":"Check - Second Choice","name":""},{"id":"47.3","label":"Check - Third Choice","name":""}],"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Check - First Choice","value":"gsurvey47526e8c41","isSelected":false,"price":""},{"text":"Check - Second Choice","value":"gsurvey47b70bdcfd","isSelected":false,"price":""},{"text":"Check - Third Choice","value":"gsurvey47faae3091","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"checkbox","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":48,"label":"Single Line Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":null,"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"text","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":49,"label":"Paragraph Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":null,"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"textarea","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":50,"label":"DropDown Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"DropDown - First Choice","value":"gsurvey50e71aa478","isSelected":false,"price":""},{"text":"DropDown - Second Choice","value":"gsurvey50792465b4","isSelected":false,"price":""},{"text":"DropDown - Third Choice","value":"gsurvey50d4b6b7b1","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"select","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"post_title","id":28,"label":"Post Title","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_excerpt","id":29,"label":"Post Excerpt","adminLabel":"","isRequired":false,"size":"small","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_tags","id":30,"label":"Post Tags","adminLabel":"","isRequired":false,"size":"large","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_category","id":31,"label":"Post Category","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[],"displayAllCategories":true,"inputType":"select","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"post_image","id":32,"label":"Post Image","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"allowedExtensions":"jpg, jpeg, png, gif","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"displayTitle":true,"displayCaption":true,"displayDescription":true},{"type":"post_custom_field","id":33,"label":"Post Custom Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"text","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"postCustomFieldName":"Payer first name","customFieldTemplate":"","customFieldTemplateEnabled":false},{"type":"product","id":34,"label":"Product Basic","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":34.1,"label":"Name","name":""},{"id":34.2,"label":"Price","name":""},{"id":34.3,"label":"Quantity","name":""}],"inputType":"singleproduct","enablePrice":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$30.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"product","id":35,"label":"Product Name - Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"select","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"DD - First Choice","value":"DD - First Choice","isSelected":false,"price":"$5.00"},{"text":"DD - Second Choice","value":"DD - Second Choice","isSelected":false,"price":"$10.00"},{"text":"DD - Third Choice","value":"DD - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":51,"label":"Product Name - Radio Buttons","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"radio","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":52,"label":"User Defined Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"price","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":53,"label":"Hidden Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":53.1,"label":"Name","name":""},{"id":53.2,"label":"Price","name":""},{"id":53.3,"label":"Quantity","name":""}],"inputType":"hiddenproduct","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$50.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":54,"label":"Calculation Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":54.1,"label":"Name","name":""},{"id":54.2,"label":"Price","name":""},{"id":54.3,"label":"Quantity","name":""}],"inputType":"calculation","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"20 + 20","calculationRounding":"","enableCalculation":true,"basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quantity","id":36,"label":"Quantity Field for Hidden Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"number","productField":"53","numberFormat":"decimal_dot","formId":58,"pageNumber":1,"choices":"","inputs":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"option","id":37,"label":"Product Options for Basic Product","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"select","choices":[{"text":"Option 1","value":"Option 1","isSelected":false,"price":"$20.00"},{"text":"Option 2","value":"Option 2","isSelected":false,"price":"$30.00"},{"text":"Option 3","value":"Option 3","isSelected":false,"price":"$40.00"}],"enablePrice":true,"productField":34,"formId":58,"pageNumber":1,"inputs":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"option","id":38,"label":"Option for Calculation Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"checkbox","choices":[{"text":"Cal - Option 1","value":"Cal - Option 1","isSelected":false,"price":"$7.95"},{"text":"Cal - Option 2","value":"Cal - Option 2","isSelected":false,"price":"$12.10"},{"text":"Cal - Option 3","value":"Cal - Option 3","isSelected":false,"price":"$9.05"}],"enablePrice":true,"productField":"54","formId":58,"pageNumber":1,"inputs":[{"id":"38.1","label":"Cal - Option 1","name":""},{"id":"38.2","label":"Cal - Option 2","name":""},{"id":"38.3","label":"Cal - Option 3","name":""}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"shipping","id":39,"label":"Shipping","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"select","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"Express","value":"Express","isSelected":false,"price":"$20.00"},{"text":"Regular","value":"Regular","isSelected":false,"price":"$30.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"total","id":40,"label":"Total","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false}],"id":58,"subLabelPlacement":"below","cssClass":"","enableHoneypot":"","enableAnimation":"","save":{"enabled":"","button":{"type":"link","text":"Save and Continue Later"}},"limitEntries":"","limitEntriesCount":"","limitEntriesPeriod":"","limitEntriesMessage":"","scheduleForm":"","scheduleStart":"","scheduleStartHour":"","scheduleStartMinute":"","scheduleStartAmpm":"","scheduleEnd":"","scheduleEndHour":"","scheduleEndMinute":"","scheduleEndAmpm":"","schedulePendingMessage":"","scheduleMessage":"","requireLogin":"","requireLoginMessage":"","useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"postAuthor":"3","postCategory":"26","postFormat":"0","postStatus":"draft","notifications":{"54bca349732b8":{"id":"54bca349732b8","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}"}},"confirmations":{"54bca34973cdd":{"id":"54bca34973cdd","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"is_active":"1","date_created":"2015-01-19 06:25:13","is_trash":"0"}
EOD;

		$forms[0] = json_decode(trim($json), true);

		foreach($forms as $id => $form)
		{
			$results = GFAPI::add_form($form);			
			$this->form_id[$id] = $results;		
		}
	}


	private function create_entries()
	{		
		$entries = json_decode('[{"form_id":"58","date_created":"2015-01-20 01:15:58","is_starred":0,"is_read":1,"ip":"124.183.82.7","source_url":"http:\/\/example.org\/?gf_page=preview&id=58","post_id":"364","currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko\/20100101 Firefox\/35.0","status":"active","1":"My Single Line Response","2":"My paragraph text response over...\r\n\r\nMultiple lines.","3":"Option 3 Value","4":"Second Choice,Third Choice","5":"50032145","6.2":"Checkbox Choice 2","6.3":"Checkbox Choice 3","7":"Radio Second Choice","8":"hidden field value","11.2":"Mr.","11.3":"Jake","11.4":"Middle","11.6":"Jackson","11.8":"MD","12":"2015-01-01","13":"10:30 am","14":"(555) 678-1210","15.1":"12 Address St","15.2":"Line 2","15.3":"Cityville","15.4":"Statesman","15.5":"5000","15.6":"Chad","16":"https:\/\/gravitypdf.com","17":"support@gravitypdf.com","18":"http:\/\/example.org\/wp-content\/uploads\/gravity_forms\/58-8f4de538fff188d8557c12d830a38810\/2015\/01\/gravityforms-export-2015-01-14.xml","19":"[\"http:\\\/\\\/example.org\\\/wp-content\\\/uploads\\\/gravity_forms\\\/58-8f4de538fff188d8557c12d830a38810\\\/2015\\\/01\\\/CPC-JAKE.docx\",\"http:\\\/\\\/example.org\\\/wp-content\\\/uploads\\\/gravity_forms\\\/58-8f4de538fff188d8557c12d830a38810\\\/2015\\\/01\\\/Tent-Cards.pdf\"]","20":"a:3:{i:0;s:20:\"List Item Response 1\";i:1;s:20:\"List Item Response 2\";i:2;s:20:\"List Item Response 3\";}","21":"a:2:{i:0;a:3:{s:8:\"Column 1\";s:19:\"List Response Col 1\";s:8:\"Column 2\";s:19:\"List Response Col 2\";s:8:\"Column 3\";s:19:\"List Response Col 3\";}i:1;a:3:{s:8:\"Column 1\";s:22:\"List Response #2 Col 1\";s:8:\"Column 2\";s:22:\"List Response #2 Col 2\";s:8:\"Column 3\";s:22:\"List Response #2 Col 3\";}}","22":"gpoll22daaa4947","23":"gpoll23ee2a7382","41.1":"gpoll23517d6a14","41.2":"gpoll23ee2a7382","41.3":"gpoll232553ed18","24":"gquiz240836e68a","42":"gquiz24c91cc7af","43.2":"gquiz240836e68a","25":"54bdac4ed24af5.01502579.png","26":"glikertcol2636762f85","27.1":"glikertrowb9cfdef9:glikertcol27c274ea49","27.2":"glikertrow5b6e1800:glikertcol27114a23c1","27.3":"glikertrowa5b5f578:glikertcol27cb066f8a","27.4":"glikertrow27a6d5ef:glikertcol275bb3ab84","27.5":"glikertrowd17a261b:glikertcol275bb3ab84","44":"grank44902be0db,grank447f27daf1,grank440c3f3227,grank44ef548e7b,grank444f7bdfe0","45":"grating45c8a62ee6","46":"gsurvey46baa416f0","47.1":"gsurvey47526e8c41","47.2":"gsurvey47b70bdcfd","48":"Survey Field Single Line Response","49":"Paragraph survey field response...","50":"gsurvey50792465b4","28":"My Post Title","29":"My Post Excerpt","30":"tag1, tag2, tag3","31":"Test Category 2:30","32":"http:\/\/example.org\/wp-content\/uploads\/gravity_forms\/58-8f4de538fff188d8557c12d830a38810\/2015\/01\/one1-500x381.jpg|:|Post Image Title|:|Post Image caption|:|Post Image Description","33":"post_custom_field","34.1":"Product Basic","34.2":"$30.00","34.3":"3","35":"DD - Second Choice|10","51":"Radio - Second Choice|10","52":"$30.00","53.1":"Hidden Price","53.2":"$50.00","36":"6","37":"Option 2|30","38.1":"Cal - Option 1|7.95","38.2":"Cal - Option 2|12.1","39":"Regular|30","54.1":"Calculation Price","54.2":"$40.00","54.3":"5","40":"860.25","6.1":"","9":"","10":"","43.1":"","43.3":"","47.3":"","53.3":"","38.3":"","gquiz_score":"2","gquiz_percent":"40","gquiz_grade":"E","gquiz_is_pass":"0"}]', true);
		$results = GFAPI::add_entries($entries, $this->form_id[0]);
		$this->entries = $results;
	}

	public function test_form_and_entry()
	{
		/* test the form was correctly added to the database */
		foreach($this->form_id as $results)
		{
			$this->assertInternalType('int', $results);		
		}

		$this->assertEquals(true, is_array($this->entries));
	}

	private function setup_form_data()
	{
		$form            = RGFormsModel::get_form_meta($this->form_id[0]);
		$entry           = RGFormsModel::get_lead($this->entries[0]);
		$this->form_data = GFPDFEntryDetail::lead_detail_grid_array($form, $entry);
	}

	public function test_basic_form_data()
	{
		$data = $this->form_data;

		/*
		 * Run our tests... 
		 */
		$this->assertEquals('ALL FIELDS', $data['form_title']);
		$this->assertEquals('This is the form description...', $data['form_description']);
		$this->assertEquals(true, array_key_exists('pages', $data));

		$date_dmy = date('j/n/Y');
		$date_mdy = date('n/j/Y');

		$this->assertEquals($date_dmy, $data['date_created']);
		$this->assertEquals($date_mdy, $data['date_created_usa']);
	}

	public function test_misc_form_data()
	{
		$data = $this->form_data;

		/*
		 * Run our tests... 
		 */
		$misc_array = array(
			'date_time', 
			'time_24hr', 
			'time_12hr',  
			'is_starred', 
			'is_read', 
			'ip', 
			'source_url', 
			'post_id', 
			'currency', 
			'payment_status', 
			'payment_date', 
			'transaction_id', 
			'payment_amount', 
			'is_fulfilled', 
			'created_by', 
			'transaction_type', 
			'user_agent', 
			'status',
		);

		foreach($misc_array as $key)
		{
			$this->assertEquals(true, array_key_exists($key, $data['misc']));
		}

		$this->assertEquals('124.183.82.7', $data['misc']['ip']);
		$this->assertEquals('active', $data['misc']['status']);
		$this->assertEquals('1', $data['misc']['created_by']);		
	}

	public function test_field_descriptions()
	{
		$data = $this->form_data;

		/*
		 * Run our tests... 
		 */	
		$this->assertEquals(true, array_key_exists('field_descriptions', $data));
		$this->assertEquals('This is the multi select box description', $data['field_descriptions'][4]);
		$this->assertEquals('Name Description', $data['field_descriptions'][11]);
	}

	public function test_field()
	{
		$data = $this->form_data;

		$this->assertEquals(true, array_key_exists('field', $data));
		$this->assertEquals(true, is_array($data['field']));		
	}

	public function test_field_single()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */		
		$response = 'My Single Line Response';
		$this->assertEquals($response, $field[1]);
		$this->assertEquals($response, $field['1.Single Line Text']);
		$this->assertEquals($response, $field['Single Line Text']);
	}	

	public function test_field_paragraph()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */		
		$response = "My paragraph text response over...<br />\r\n<br />\r\nMultiple lines.";
		$this->assertEquals($response, $field[2]);
		$this->assertEquals($response, $field['2.Paragraph Text']);
		$this->assertEquals($response, $field['Paragraph Text']);
	}	

	public function test_dropdown()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */		
		$response = "Option 3 Value";
		$this->assertEquals($response, $field[3]);
		$this->assertEquals($response, $field['3.Drop Down']);
		$this->assertEquals($response, $field['Drop Down']);

		$response = "Option 3";
		$this->assertEquals($response, $field['3_name']);	
		$this->assertEquals($response, $field['3.Drop Down_name']);
		$this->assertEquals($response, $field['Drop Down_name']);
	}	

	public function test_multiselect()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */				
		$response = 'Second Choice';
		$this->assertEquals(true, in_array($response, $field[4]));
		$this->assertEquals(true, in_array($response, $field['4.Multi Select Box']));
		$this->assertEquals(true, in_array($response, $field['Multi Select Box']));
		
		$response = 'Multi Select Second Choice';
		$this->assertEquals(true, in_array($response, $field['4_name']));
		$this->assertEquals(true, in_array($response, $field['4.Multi Select Box_name']));
		$this->assertEquals(true, in_array($response, $field['Multi Select Box_name']));

		$this->assertEquals(2, sizeof($field[4]));
		$this->assertEquals(2, sizeof($field['4_name']));
	}	

	public function test_field_number()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */		
		$response = '50032145';
		$this->assertEquals($response, $field[5]);
		$this->assertEquals($response, $field['5.Number']);
		$this->assertEquals($response, $field['Number']);
	}		

	public function test_checkbox()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */				
		$response = 'Checkbox Choice 2';
		$this->assertEquals(true, in_array($response, $field[6]));
		$this->assertEquals(true, in_array($response, $field['6.Checkbox']));
		$this->assertEquals(true, in_array($response, $field['Checkbox']));
		
		$response = 'Checkbox Choice 3';
		$this->assertEquals(true, in_array($response, $field[6]));
		$this->assertEquals(true, in_array($response, $field['6.Checkbox']));
		$this->assertEquals(true, in_array($response, $field['Checkbox']));

		$this->assertEquals(2, sizeof($field[6]));
	}	

	public function test_radio_button()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */		
		$response = "Radio Second Choice";
		$this->assertEquals($response, $field[7]);
		$this->assertEquals($response, $field['7.Radio Button']);
		$this->assertEquals($response, $field['Radio Button']);

		$response = "Radio Second Choice Name";
		$this->assertEquals($response, $field['7_name']);	
		$this->assertEquals($response, $field['7.Radio Button_name']);
		$this->assertEquals($response, $field['Radio Button_name']);
	}		

	public function test_hidden_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */		
		$response = "hidden field value";
		$this->assertEquals($response, $field[8]);
		$this->assertEquals($response, $field['8.Hidden Field']);
		$this->assertEquals($response, $field['Hidden Field']);
	}

	public function test_name_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */		
		$this->assertEquals('Mr.', $field[11]['prefix']);	
		$this->assertEquals('Jake', $field[11]['first']);	
		$this->assertEquals('Middle', $field[11]['middle']);	
		$this->assertEquals('Jackson', $field[11]['last']);	
		$this->assertEquals('MD', $field[11]['suffix']);	

		$this->assertEquals('Mr.', $field['11.Name']['prefix']);	
		$this->assertEquals('Jake', $field['11.Name']['first']);	
		$this->assertEquals('Middle', $field['11.Name']['middle']);	
		$this->assertEquals('Jackson', $field['11.Name']['last']);	
		$this->assertEquals('MD', $field['11.Name']['suffix']);	

		$this->assertEquals('Mr.', $field['Name']['prefix']);	
		$this->assertEquals('Jake', $field['Name']['first']);	
		$this->assertEquals('Middle', $field['Name']['middle']);	
		$this->assertEquals('Jackson', $field['Name']['last']);	
		$this->assertEquals('MD', $field['Name']['suffix']);					
	}		

	public function test_date_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */	
		$response = "01/01/2015";
		$this->assertEquals($response, $field[12]);
		$this->assertEquals($response, $field['12.Date']);
		$this->assertEquals($response, $field['Date']);				
	}

	public function test_time_field()
	{
		$field = $this->form_data['field'];	
		
		/*
		 * Run our tests... 
		 */	
		$response = "10:30 am";
		/*$this->assertEquals($response, $field[13]);
		$this->assertEquals($response, $field['13.Time']);
		$this->assertEquals($response, $field['Time']);	*/

		/* error with the JSON data... */
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );						
	}	

	public function test_phone_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */	
		$response = "(555) 678-1210";
		$this->assertEquals($response, $field[14]);
		$this->assertEquals($response, $field['14.Phone']);
		$this->assertEquals($response, $field['Phone']);				
	}	

	public function test_address_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */		
		$this->assertEquals('12 Address St', $field[15]['street']);	
		$this->assertEquals('Line 2', $field[15]['street2']);	
		$this->assertEquals('Cityville', $field[15]['city']);	
		$this->assertEquals('Statesman', $field[15]['state']);	
		$this->assertEquals('5000', $field[15]['zip']);	
		$this->assertEquals('Chad', $field[15]['country']);	

		$this->assertEquals('12 Address St', $field['15.Address']['street']);	
		$this->assertEquals('Line 2', $field['15.Address']['street2']);	
		$this->assertEquals('Cityville', $field['15.Address']['city']);	
		$this->assertEquals('Statesman', $field['15.Address']['state']);	
		$this->assertEquals('5000', $field['15.Address']['zip']);	
		$this->assertEquals('Chad', $field['15.Address']['country']);	

		$this->assertEquals('12 Address St', $field['Address']['street']);	
		$this->assertEquals('Line 2', $field['Address']['street2']);	
		$this->assertEquals('Cityville', $field['Address']['city']);	
		$this->assertEquals('Statesman', $field['Address']['state']);	
		$this->assertEquals('5000', $field['Address']['zip']);	
		$this->assertEquals('Chad', $field['Address']['country']);							
	}

	public function test_website_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */	
		$response = "https://gravitypdf.com";
		$this->assertEquals($response, $field[16]);
		$this->assertEquals($response, $field['16.Website']);
		$this->assertEquals($response, $field['Website']);				
	}	

	public function test_email_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */	
		$response = "support@gravitypdf.com";
		$this->assertEquals($response, $field[17]);
		$this->assertEquals($response, $field['17.Email']);
		$this->assertEquals($response, $field['Email']);				
	}	

	public function test_upload_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */	
		$this->assertEquals(1, sizeof($field[18]));
		$this->assertEquals(1, sizeof($field['18.File']));
		$this->assertEquals(1, sizeof($field['18.File_path']));
		$this->assertEquals(1, sizeof($field['18_path']));
		
		$this->assertEquals(2, sizeof($field[19]));
		$this->assertEquals(2, sizeof($field['19.File']));
		$this->assertEquals(2, sizeof($field['19.File_path']));
		$this->assertEquals(2, sizeof($field['19_path']));				

		$this->assertEquals('http://', substr($field[18][0], 0, 7));		
		$this->assertEquals('http://', substr($field['18.File'][0], 0, 7));						
		$this->assertEquals('http://', substr($field[19][0], 0, 7));		
		$this->assertEquals('http://', substr($field[19][1], 0, 7));		
		$this->assertEquals('http://', substr($field['19.File'][0], 0, 7));		
		$this->assertEquals('http://', substr($field['19.File'][1], 0, 7));					

		$this->assertEquals('/', substr($field['18_path'][0], 0, 1));		
		$this->assertEquals('/', substr($field['18.File_path'][0], 0, 1));						
		$this->assertEquals('/', substr($field['19_path'][0], 0, 1));		
		$this->assertEquals('/', substr($field['19_path'][1], 0, 1));		
		$this->assertEquals('/', substr($field['19.File_path'][0], 0, 1));		
		$this->assertEquals('/', substr($field['19.File_path'][1], 0, 1));			
	}	

	public function test_list_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */	
		$response = '<table autosize="1" class="gfield_list" style="border-top: 1px solid #DFDFDF; border-left: 1px solid #DFDFDF; border-spacing: 0; padding: 0; margin: 2px 0 6px; width: 100%"><tr><td>List Item Response 1</td></tr><tr><td>List Item Response 2</td></tr><tr><td>List Item Response 3</td></tr></table>';
		$this->assertEquals($response, $field[20]);
		$this->assertEquals($response, $field['20.Basic List']);
		$this->assertEquals($response, $field['Basic List']);				

		$response = '<table class="gfield_list" autosize="1"><thead><tr><th>Column 1</th><th>Column 2</th><th>Column 3</th></tr></thead><tbody><tr><td>List Response Col 1</td><td>List Response Col 2</td><td>List Response Col 3</td></tr><tr><td>List Response #2 Col 1</td><td>List Response #2 Col 2</td><td>List Response #2 Col 3</td></tr></tbody></table>';
		$this->assertEquals($response, $field[21]);
		$this->assertEquals($response, $field['21.Extended List']);
		$this->assertEquals($response, $field['Extended List']);					
	}
	
	public function test_poll_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */	
		$response = 'Poll Dropdown - First Choice';
		$this->assertEquals($response, $field[22]);
		$this->assertEquals($response, $field['22.Poll Field - Drop Down_name']);			

		$response = 'Poll Radio - Second Choice';
		$this->assertEquals($response, $field[23]);
		$this->assertEquals($response, $field['23.Poll Field - Radio Buttons_name']);						

		$this->assertEquals(true, is_array($field[41][0]));
		$this->assertEquals(true, in_array('Poll Check First Choice', $field[41][0]));
		$this->assertEquals(true, in_array('Poll Check Second Choice', $field[41][0]));
		$this->assertEquals(true, in_array('Poll Check Third Choice', $field[41][0]));

		$this->assertEquals(true, is_array($field['41.Poll Field - Checkboxes'][0]));
		$this->assertEquals(true, in_array('Poll Check First Choice', $field['41.Poll Field - Checkboxes'][0]));
		$this->assertEquals(true, in_array('Poll Check Second Choice', $field['41.Poll Field - Checkboxes'][0]));
		$this->assertEquals(true, in_array('Poll Check Third Choice', $field['41.Poll Field - Checkboxes'][0]));

		$this->assertEquals(true, is_array($field['Poll Field - Checkboxes'][0]));
		$this->assertEquals(true, in_array('Poll Check First Choice', $field['Poll Field - Checkboxes'][0]));
		$this->assertEquals(true, in_array('Poll Check Second Choice', $field['Poll Field - Checkboxes'][0]));
		$this->assertEquals(true, in_array('Poll Check Third Choice', $field['Poll Field - Checkboxes'][0]));		

	}

	public function test_quiz_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 */	
		$response = 'Quiz Dropdown - Second Choice';
		$this->assertEquals($response, $field[24]['text']);
		$this->assertEquals($response, $field['24.Quiz Dropdown_name']['text']);					

		$this->assertEquals(true, array_key_exists('text', $field[24]));
		$this->assertEquals(true, array_key_exists('text', $field['24.Quiz Dropdown_name']));
	}

	public function test_survey_basic_field()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 * Radio button first
		 */	
		$response = 'Survay Radio - First Choice';
		$this->assertEquals($response, $field[46]);
		$this->assertEquals($response, $field['46.Radio Survey Field_name']);

		/*
		 * Run checkbox survey test
		 */
		$this->assertEquals(2, sizeof(array_filter($field[47][0])));
		$this->assertEquals(2, sizeof(array_filter($field['47.Checkbox Survey Field'][0])));
		$this->assertEquals(2, sizeof(array_filter($field['Checkbox Survey Field'][0])));

		$this->assertEquals('Check - First Choice', $field[47][0]['47.1']);
		$this->assertEquals('Check - Second Choice', $field[47][0]['47.2']);
		
		$this->assertEquals('Check - First Choice', $field['47.Checkbox Survey Field'][0]['47.1']);
		$this->assertEquals('Check - Second Choice', $field['47.Checkbox Survey Field'][0]['47.2']);

		$this->assertEquals('Check - First Choice', $field['Checkbox Survey Field'][0]['47.1']);
		$this->assertEquals('Check - Second Choice', $field['Checkbox Survey Field'][0]['47.2']);	

		/*
		 * Run single line survey
		 */	
		$response = 'Survey Field Single Line Response';
		$this->assertEquals($response, $field[48]);
		$this->assertEquals($response, $field['48.Single Line Survey Field']);				
		$this->assertEquals($response, $field['Single Line Survey Field']);

		/*
		 * Run paragraph test 
		 */
		$response = 'Paragraph survey field response...';
		$this->assertEquals($response, $field[49]);
		$this->assertEquals($response, $field['49.Paragraph Survey Field']);			
		$this->assertEquals($response, $field['Paragraph Survey Field']);

		/*
		 * Run Dropdown Test
		 */			
		$response = 'DropDown - Second Choice';
		$this->assertEquals($response, $field[50]);
		$this->assertEquals($response, $field['50.DropDown Survey Field_name']);						
	}

	public function test_post_fields()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 * Post Title		 
		 */			
		$response = 'My Post Title';
		$this->assertEquals($response, $field[28]);
		$this->assertEquals($response, $field['28.Post Title']);				
		$this->assertEquals($response, $field['Post Title']);		

		/*
		 * Post Excerpt
		 */
		$response = 'My Post Excerpt';
		$this->assertEquals($response, $field[29]);
		$this->assertEquals($response, $field['29.Post Excerpt']);				
		$this->assertEquals($response, $field['Post Excerpt']);		

		/*
		 * Post Tags
		 */	
		$response = 'tag1, tag2, tag3';
		$this->assertEquals($response, $field[30]);
		$this->assertEquals($response, $field['30.Post Tags']);				
		$this->assertEquals($response, $field['Post Tags']);

		/*
		 * Post Category
		 */		
		$response = 'Test Category 2';
		$this->assertEquals($response, $field[31]);
		$this->assertEquals($response, $field['31.Post Category']);				
		$this->assertEquals($response, $field['Post Category']);			
		$this->assertEquals($response, $field['31.Post Category_name']);				
		$this->assertEquals($response, $field['31_name']);				

		/*
		 * Post Image
		 */
		$this->assertEquals(5, sizeof($field[32]));
		$this->assertEquals(5, sizeof($field['32.Post Image']));
		$this->assertEquals(5, sizeof($field['Post Image']));

		$title   = 'Post Image Title';
		$caption = 'Post Image caption';
		$desc    = 'Post Image Description';

		$keys = array('32', '32.Post Image', 'Post Image');

		foreach($keys as $key)
		{
			$this->assertEquals('http://', substr($field[$key]['url'], 0, 7));
			$this->assertEquals('/', substr($field[$key]['path'], 0, 1));
			$this->assertEquals($title, $field[$key]['title']);
			$this->assertEquals($caption, $field[$key]['caption']);
			$this->assertEquals($desc, $field[$key]['description']);
		}

		/*
		 * Post Custom Field
		 */
		$response = 'post_custom_field';
		$this->assertEquals($response, $field[33]);
		$this->assertEquals($response, $field['33.Post Custom Field']);				
		$this->assertEquals($response, $field['Post Custom Field']);
	}

	public function test_basic_product_fields()
	{
		$field = $this->form_data['field'];	

		/*
		 * Run our tests... 
		 * Basic Product Drop down 
		 */	
		$response = 'DD - Second Choice ($10.00)';
		$this->assertEquals($response, $field[35]);
		$this->assertEquals($response, $field['35.Product Name - Drop Down']);				
		$this->assertEquals($response, $field['Product Name - Drop Down']);		
		$this->assertEquals($response, $field['35.Product Name - Drop Down_name']);		
		$this->assertEquals($response, $field['Product Name - Drop Down_name']);	
		$this->assertEquals($response, $field['35_name']);	

		/*
		 * Product Radio Button
		 */
		$response = 'Radio - Second Choice ($10.00)';
		$this->assertEquals($response, $field[51]);
		$this->assertEquals($response, $field['51.Product Name - Radio Buttons']);				
		$this->assertEquals($response, $field['Product Name - Radio Buttons']);			
		$this->assertEquals($response, $field['51.Product Name - Radio Buttons_name']);	
		$this->assertEquals($response, $field['51_name']);	
		$this->assertEquals($response, $field['Product Name - Radio Buttons_name']);	

		/*
		 * Product Option Single
		 */	
		$response = 'Option 2 ($30.00)';
		$this->assertEquals($response, $field[37]);
		$this->assertEquals($response, $field['37.Product Options for Basic Product']);				
		$this->assertEquals($response, $field['Product Options for Basic Product']);			
		$this->assertEquals($response, $field['37.Product Options for Basic Product_name']);	
		$this->assertEquals($response, $field['37_name']);				
		$this->assertEquals($response, $field['Product Options for Basic Product_name']);	

		/*
		 * Product Shipping Basic
		 */
		$response = 'Regular ($30.00)';
		$this->assertEquals($response, $field[39]);
		$this->assertEquals($response, $field['39.Shipping']);				
		$this->assertEquals($response, $field['Shipping']);			
		$this->assertEquals($response, $field['39.Shipping_name']);	
		$this->assertEquals($response, $field['39_name']);				
		$this->assertEquals($response, $field['Shipping_name']);			
	}

	public function test_html_block()
	{
		$data = $this->form_data;

		/*
		 * Run our tests... 		 
		 */			
		$response = '<p>This is a HTML block</p>';
		$this->assertEquals($response, trim($data['html'][0]));

		/*
		 * Why does the HTML block use indexes instead of IDs??
		 */
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );				
	}

	public function test_list_field_block()
	{
		$lists = $this->form_data['list'];

		/*
		 * Run our tests... 		 
		 */				
		$this->assertEquals(2, sizeof($lists));
		$this->assertEquals(3, sizeof($lists[20]));
		$this->assertEquals(2, sizeof($lists[21]));
		$this->assertEquals(3, sizeof($lists[21][0]));
		$this->assertEquals(3, sizeof($lists[21][1]));

		/*
		 * Check the basic list content
		 */
		$this->assertEquals('List Item Response 1', $lists[20][0]);
		$this->assertEquals('List Item Response 2', $lists[20][1]);
		$this->assertEquals('List Item Response 3', $lists[20][2]);

		/*
		 * Check the multirow list content
		 */
		$this->assertEquals('List Response Col 1', $lists[21][0]['Column 1']);
		$this->assertEquals('List Response Col 2', $lists[21][0]['Column 2']);
		$this->assertEquals('List Response Col 3', $lists[21][0]['Column 3']);		

		$this->assertEquals('List Response #2 Col 1', $lists[21][1]['Column 1']);
		$this->assertEquals('List Response #2 Col 2', $lists[21][1]['Column 2']);
		$this->assertEquals('List Response #2 Col 3', $lists[21][1]['Column 3']);				
	}

	public function test_signature_blocks()
	{
		$data = $this->form_data;

		$response = '<img src="/tmp/wordpress/wp-content/uploads/gravity_forms/signatures/54bdac4ed24af5.01502579.png" alt="Signature" width="75" height="45" />';

		/*
		 * Standard Signature Array
		 */
		$this->assertEquals($response, $data['signature_details_id'][25]['img']);
		$this->assertEquals('/', substr($data['signature_details_id'][25]['path'], 0, 1));
		$this->assertEquals('http://', substr($data['signature_details_id'][25]['url'], 0, 7));
		$this->assertEquals(75, $data['signature_details_id'][25]['width']);
		$this->assertEquals(45, $data['signature_details_id'][25]['height']);

		/*
		 * Old Signature that doesn't index by ID
		 * Depreciated
		 */
		$this->assertEquals($response, $data['signature_details'][0]['img']);
		$this->assertEquals('/', substr($data['signature_details'][0]['path'], 0, 1));
		$this->assertEquals('http://', substr($data['signature_details'][0]['url'], 0, 7));
		$this->assertEquals(75, $data['signature_details'][0]['width']);
		$this->assertEquals(45, $data['signature_details'][0]['height']);

		/*
		 * Basic Signature
		 * Depreciated
		 */		
		$this->assertEquals($response, $data['signature'][0]);
	}

	public function test_survey_likert_fields()
	{
		$likert = $this->form_data['survey']['likert'];

		/*
		 * Single-row Likert
		 */		
		$this->assertEquals(true, array_key_exists('col', $likert[26]));
		$this->assertEquals(true, array_key_exists('row', $likert[26]));

		$this->assertEquals(5, sizeof($likert[26]['col']));
		$this->assertEquals(5, sizeof($likert[26]['row']));

		$this->assertEquals(true, array_key_exists('Strongly disagree', $likert[26]['row']));		
		$this->assertEquals(true, array_key_exists('Disagree', $likert[26]['row']));		
		$this->assertEquals(true, array_key_exists('Neutral', $likert[26]['row']));		
		$this->assertEquals(true, array_key_exists('Agree', $likert[26]['row']));		
		$this->assertEquals(true, array_key_exists('Strongly agree', $likert[26]['row']));	

		$this->assertEquals('selected', $likert[26]['row']['Strongly disagree']);

		/*
		 * Multi-Row Likert
		 */
		$this->assertEquals(true, array_key_exists('col', $likert[27]));
		$this->assertEquals(true, array_key_exists('rows', $likert[27]));
		$this->assertEquals(false, array_key_exists('row', $likert[27]));

		$this->assertEquals(5, sizeof($likert[27]['col']));
		$this->assertEquals(5, sizeof($likert[27]['rows']));

		$this->assertEquals(true, array_key_exists('First row', $likert[27]['rows']));		
		$this->assertEquals(true, array_key_exists('Second row', $likert[27]['rows']));		
		$this->assertEquals(true, array_key_exists('Third row', $likert[27]['rows']));		
		$this->assertEquals(true, array_key_exists('Fourth row', $likert[27]['rows']));		
		$this->assertEquals(true, array_key_exists('Fifth row', $likert[27]['rows']));	

		$col_names = array('Strongly disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly agree');
		foreach($likert[27]['rows'] as $cols)
		{
			foreach($col_names as $name)
			{
				$this->assertEquals(true, array_key_exists($name, $cols));
			}			
		}

		$this->assertEquals('selected', $likert[27]['rows']['First row']['Neutral']);
		$this->assertEquals('selected', $likert[27]['rows']['Second row']['Disagree']);
		$this->assertEquals('selected', $likert[27]['rows']['Third row']['Agree']);
		$this->assertEquals('selected', $likert[27]['rows']['Fourth row']['Strongly agree']);
		$this->assertEquals('selected', $likert[27]['rows']['Fifth row']['Strongly agree']);
	}

	public function test_survey_rank_fields()
	{
		$rank = $this->form_data['survey']['rank'];

		/*
		 * Test Rank field
		 */	
		$this->assertEquals('Rank Fourth Choce', $rank[44][0]);
		$this->assertEquals('Rank Fifth Choice', $rank[44][1]);
		$this->assertEquals('Rank Second Choice', $rank[44][2]);
		$this->assertEquals('Rank First Choice', $rank[44][3]);
		$this->assertEquals('Rank Third Choice', $rank[44][4]);
    }	

	public function test_survey_rating_fields()
	{
		$rating = $this->form_data['survey']['rating'];

		/*
		 * Test Rating Field
		 */	
		$this->assertEquals('Pretty good', $rating[45][0]);
    }	

	public function test_product_data()
	{
		$products = $this->form_data['products'];

		/*
		 * Run first set of tests
		 */
		$this->assertEquals('Product Basic', $products[34]['name']);
		$this->assertEquals('$30.00', $products[34]['price']);
		$this->assertEquals('30.00', $products[34]['price_unformatted']);
		$this->assertEquals('3', $products[34]['quantity']);
		$this->assertEquals('180', $products[34]['subtotal']);
		$this->assertEquals('$180.00', $products[34]['subtotal_formatted']);

		$this->assertEquals('Product Options for Basic Product', $products[34]['options'][0]['field_label']);
		$this->assertEquals('Option 2', $products[34]['options'][0]['option_name']);
		$this->assertEquals('Product Options for Basic Product: Option 2', $products[34]['options'][0]['option_label']);
		$this->assertEquals('30', $products[34]['options'][0]['price']);
		$this->assertEquals('$30.00', $products[34]['options'][0]['price_formatted']);	

		/*
		 * Run second set of tests
		 */	
		$this->assertEquals('DD - Second Choice', $products[35]['name']);		
		//$this->assertEquals('$10.00', $products[35]['price']);  /* this is currently incorrect */
		//$this->assertEquals('10.00', $products[35]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals('1', $products[35]['quantity']);
		$this->assertEquals('10', $products[35]['subtotal']);
		$this->assertEquals('$10.00', $products[35]['subtotal_formatted']);
		$this->assertEquals(0, sizeof($products[35]['options']));

		/*
		 * Run third set of tests 
		 */
		$this->assertEquals('Radio - Second Choice', $products[51]['name']);		
		//$this->assertEquals('$10.00', $products[51]['price']);  /* this is currently incorrect */
		//$this->assertEquals('10.00', $products[51]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals('1', $products[51]['quantity']);
		$this->assertEquals('10', $products[51]['subtotal']);
		$this->assertEquals('$10.00', $products[51]['subtotal_formatted']);
		$this->assertEquals(0, sizeof($products[51]['options']));		

		/*
		 * Run fourth set of tests 
		 */
		$this->assertEquals('User Defined Price', $products[52]['name']);		
		$this->assertEquals('$30.00', $products[52]['price']);  /* this is currently incorrect */
		$this->assertEquals('30.00', $products[52]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals('1', $products[52]['quantity']);
		$this->assertEquals('30', $products[52]['subtotal']);
		$this->assertEquals('$30.00', $products[52]['subtotal_formatted']);
		$this->assertEquals(0, sizeof($products[52]['options']));		

		/*
		 * Run fifth set of tests 
		 */
		$this->assertEquals('Hidden Price', $products[53]['name']);		
		$this->assertEquals('$50.00', $products[53]['price']);  /* this is currently incorrect */
		$this->assertEquals('50.00', $products[53]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals('6', $products[53]['quantity']);
		$this->assertEquals('300', $products[53]['subtotal']);
		$this->assertEquals('$300.00', $products[53]['subtotal_formatted']);
		$this->assertEquals(0, sizeof($products[53]['options']));

		/*
		 * Run sixth set of tests 
		 */
		$this->assertEquals('Calculation Price', $products[54]['name']);		
		$this->assertEquals('$40.00', $products[54]['price']);  /* this is currently incorrect */
		$this->assertEquals('40.00', $products[54]['price_unformatted']); /* this is currently incorrect */
		$this->assertEquals('5', $products[54]['quantity']);
		$this->assertEquals('300.25', $products[54]['subtotal']);
		$this->assertEquals('$300.25', $products[54]['subtotal_formatted']);

		$this->assertEquals('Option for Calculation Price', $products[54]['options'][0]['field_label']);
		$this->assertEquals('Cal - Option 1', $products[54]['options'][0]['option_name']);
		$this->assertEquals('Option for Calculation Price: Cal - Option 1', $products[54]['options'][0]['option_label']);
		$this->assertEquals('7.95', $products[54]['options'][0]['price']);
		$this->assertEquals('$7.95', $products[54]['options'][0]['price_formatted']);			

		$this->assertEquals('Option for Calculation Price', $products[54]['options'][1]['field_label']);
		$this->assertEquals('Cal - Option 2', $products[54]['options'][1]['option_name']);
		$this->assertEquals('Option for Calculation Price: Cal - Option 2', $products[54]['options'][1]['option_label']);
		$this->assertEquals('12.1', $products[54]['options'][1]['price']);
		$this->assertEquals('$12.10', $products[54]['options'][1]['price_formatted']);			
						
	}

	public function test_product_totals()
	{
		$totals = $this->form_data['products_totals'];

		$this->assertEquals(830.25, $totals['subtotal']);	
		$this->assertEquals(30, $totals['shipping']);	
		$this->assertEquals(860.25, $totals['total']);	
		$this->assertEquals('$30.00', $totals['shipping_formatted']);	
		$this->assertEquals('$830.25', $totals['subtotal_formatted']);	
		$this->assertEquals('$860.25', $totals['total_formatted']);	
	}



}

