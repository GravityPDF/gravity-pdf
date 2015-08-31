<?php

namespace GFPDF\Tests;

use GFPDF\Controller\Controller_PDF;
use GFPDF\Model\Model_PDF;
use GFPDF\View\View_PDF;

use GFAPI;
use GFFormsModel;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use WP_Error;
use WP_Rewrite;

use Exception;

/**
 * Test Gravity PDF Endpoint Functionality
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

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

/**
 * Test the model / view / controller for the PDF Endpoint functionality
 * @since 4.0
 */
class Test_PDF extends WP_UnitTestCase
{

	/**
	 * The Gravity Form IDs assigned to the imported form
	 * @var Integer
	 * @since 4.0
	 */
	public $form_id = array();

	/**
	 * The Gravity Form entries imported
	 * @var Integer
	 * @since 4.0
	 */
	public $entries = array();

	/**
	 * Our Settings Controller
	 * @var Object
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Settings Model
	 * @var Object
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Settings View
	 * @var Object
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup our test classes */
		$this->model = new Model_PDF( $gfpdf->form, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->notices );
		$this->view  = new View_PDF( array() );

		$this->controller = new Controller_PDF( $this->model, $this->view, $gfpdf->form, $gfpdf->log, $gfpdf->misc );
		$this->controller->init();

		/* Set up WP Factory so we can use it */
		$this->factory = new WP_UnitTest_Factory();
	}

	/**
	 * Create our testing data
	 * @since 4.0
	 */
	private function create_form_and_entries() {
		$this->create_forms();
		$this->create_entries();
		
        return $this->setup_form_data();
	}

	/**
	 * Add our forms to be used in the testing process
	 * @since 4.0
	 */
	private function create_forms() {
		$json = <<<EOD
{"title":"ALL FIELDS","description":"This is the form description...","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"type":"text","id":1,"label":"Single Line Text","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the single line text description","cssClass":"exclude","inputType":""},{"type":"textarea","id":2,"label":"Paragraph Text","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the paragraph text description","inputType":""},{"type":"select","id":3,"label":"Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"Option 1","value":"Option 1","isSelected":false,"price":""},{"text":"Option 2","value":"Option 2","isSelected":true,"price":""},{"text":"Option 3","value":"Option 3 Value","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the drop down description","inputType":""},{"type":"multiselect","id":4,"label":"Multi Select Box","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Multi Select Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the multi select box description","inputType":""},{"type":"number","id":5,"label":"Number","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"numberFormat":"decimal_dot","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","inputType":""},{"type":"checkbox","id":6,"label":"Checkbox","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","choices":[{"text":"Checkbox Choice 1","value":"Checkbox Choice 1","isSelected":false,"price":""},{"text":"Checkbox Choice 2 Text","value":"Checkbox Choice 2","isSelected":false,"price":""},{"text":"Checkbox Choice 3 Text","value":"Checkbox Choice 3","isSelected":false,"price":""}],"inputs":[{"id":"6.1","label":"Checkbox Choice 1","name":""},{"id":"6.2","label":"Checkbox Choice 2","name":""},{"id":"6.3","label":"Checkbox Choice 3","name":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Checkbox description"},{"type":"radio","id":7,"label":"Radio Button","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"Radio First Choice","value":"Radio First Choice","isSelected":false,"price":""},{"text":"Radio Second Choice Name","value":"Radio Second Choice","isSelected":false,"price":""},{"text":"Radio Third Choice","value":"Radio Third Choice","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Radio button description","enableOtherChoice":true},{"type":"hidden","id":8,"label":"Hidden Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"hidden field value"},{"type":"html","id":9,"label":"HTML Block","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"displayOnly":true,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"content":"This is a HTML block"},{"type":"section","id":10,"label":"Section Break","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"displayOnly":true,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Section Break Description"},{"type":"name","id":11,"label":"Name","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","nameFormat":"advanced","inputs":[{"id":"11.2","label":"Prefix","name":"","choices":[{"text":"Mr.","value":"Mr.","isSelected":false,"price":""},{"text":"Mrs.","value":"Mrs.","isSelected":false,"price":""},{"text":"Miss","value":"Miss","isSelected":false,"price":""},{"text":"Ms.","value":"Ms.","isSelected":false,"price":""},{"text":"Dr.","value":"Dr.","isSelected":false,"price":""},{"text":"Prof.","value":"Prof.","isSelected":false,"price":""},{"text":"Rev.","value":"Rev.","isSelected":false,"price":""}],"isHidden":false,"inputType":"radio"},{"id":"11.3","label":"First","name":""},{"id":"11.4","label":"Middle","name":"","isHidden":false},{"id":"11.6","label":"Last","name":""},{"id":"11.8","label":"Suffix","name":"","isHidden":false}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Name Description"},{"type":"date","id":12,"label":"Date","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","dateType":"datepicker","calendarIconType":"none","calendarIconUrl":"","allowsPrepopulate":false,"description":"Date Description","dateFormat":"dmy"},{"type":"time","id":13,"label":"Time","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"13.1","label":"HH","name":""},{"id":"13.2","label":"MM","name":""},{"id":"13.3","label":"AM\/PM","name":""}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"timeFormat":"12"},{"type":"phone","id":14,"label":"Phone","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"address","id":15,"label":"Address","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":15.1,"label":"Street Address","name":""},{"id":15.2,"label":"Address Line 2","name":""},{"id":15.3,"label":"City","name":""},{"id":15.4,"label":"State \/ Province","name":""},{"id":15.5,"label":"ZIP \/ Postal Code","name":""},{"id":15.6,"label":"Country","name":""}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"website","id":16,"label":"Website","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"email","id":17,"label":"Email","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"fileupload","id":18,"label":"File","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"fileupload","id":19,"label":"File","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":true,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"inputType":"","defaultValue":"","description":""},{"type":"list","id":20,"label":"Basic List","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"list","id":21,"label":"Extended List","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":[{"text":"Column 1","value":"Column 1","isSelected":false,"price":""},{"text":"Column 2","value":"Column 2","isSelected":false,"price":""},{"text":"Column 3","value":"Column 3","isSelected":false,"price":""}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"enableColumns":true},{"type":"poll","id":22,"label":"Poll Field - Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"poll_field_type":"select","inputType":"select","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Dropdown - First Choice","value":"gpoll22daaa4947","isSelected":false,"price":""},{"text":"Poll Dropdown - Second Choice","value":"gpoll220a301dd5","isSelected":false,"price":""},{"text":"Poll Dropdown - Third Choice","value":"gpoll22175a8601","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"poll","id":23,"label":"Poll Field - Radio Buttons","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"poll_field_type":"radio","inputType":"radio","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Radio - First Choice","value":"gpoll23517d6a14","isSelected":false,"price":""},{"text":"Poll Radio - Second Choice","value":"gpoll23ee2a7382","isSelected":false,"price":""},{"text":"Poll Radio - Third Choice","value":"gpoll232553ed18","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"poll","id":41,"label":"Poll Field - Checkboxes","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"41.1","label":"Poll Check First Choice","name":""},{"id":"41.2","label":"Poll Check Second Choice","name":""},{"id":"41.3","label":"Poll Check Third Choice","name":""}],"poll_field_type":"checkbox","inputType":"checkbox","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Check First Choice","value":"gpoll23517d6a14","isSelected":false,"price":""},{"text":"Poll Check Second Choice","value":"gpoll23ee2a7382","isSelected":false,"price":""},{"text":"Poll Check Third Choice","value":"gpoll232553ed18","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":24,"label":"Quiz Dropdown","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"gquizFieldType":"select","inputType":"select","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":true,"choices":[{"text":"Quiz Dropdown - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"3"},{"text":"Quiz Dropdown - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"2"},{"text":"Quiz Dropdown - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"1"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":42,"label":"Quiz Radio","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"gquizFieldType":"radio","inputType":"radio","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":false,"choices":[{"text":"Quiz Radio - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"},{"text":"Quiz Radio - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"},{"text":"Quiz Radio - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":43,"label":"Quiz Checkbox","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"43.1","label":"Quiz Checkbox - First Choice","name":""},{"id":"43.2","label":"Quiz Checkbox - Second Choice","name":""},{"id":"43.3","label":"Quiz Checkbox - Third Choice","name":""}],"gquizFieldType":"checkbox","inputType":"checkbox","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":false,"choices":[{"text":"Quiz Checkbox - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"},{"text":"Quiz Checkbox - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"},{"text":"Quiz Checkbox - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"id":25,"label":"Signature","adminLabel":"","type":"signature","isRequired":false,"size":"medium","errorMessage":"","formId":58,"pageNumber":1,"choices":"","inputs":"","conditionalLogic":"","displayOnly":""},{"id":26,"label":"Likert Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Strongly disagree","value":"glikertcol2636762f85","isSelected":false,"score":1},{"text":"Disagree","value":"glikertcol26a40c345c","isSelected":false,"score":2},{"text":"Neutral","value":"glikertcol26114a03dd","isSelected":false,"score":3},{"text":"Agree","value":"glikertcol26d3452ed6","isSelected":false,"score":4},{"text":"Strongly agree","value":"glikertcol2648e6c579","isSelected":false,"score":5}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrowd6ee998c"},{"text":"Second row","value":"glikertrow3ba9477d"},{"text":"Third row","value":"glikertrowbfdd8b2d"},{"text":"Fourth row","value":"glikertrowb042f1a8"},{"text":"Fifth row","value":"glikertrow4770db3e"}],"inputType":"likert","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"id":27,"label":"Likert Survey Field Extended","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"27.1","label":"First row","name":"glikertrowb9cfdef9"},{"id":"27.2","label":"Second row","name":"glikertrow5b6e1800"},{"id":"27.3","label":"Third row","name":"glikertrowa5b5f578"},{"id":"27.4","label":"Fourth row","name":"glikertrow27a6d5ef"},{"id":"27.5","label":"Fifth row","name":"glikertrowd17a261b"}],"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":true,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Strongly disagree","value":"glikertcol27573469fe","isSelected":false,"score":1},{"text":"Disagree","value":"glikertcol27114a23c1","isSelected":false,"score":2},{"text":"Neutral","value":"glikertcol27c274ea49","isSelected":false,"score":3},{"text":"Agree","value":"glikertcol27cb066f8a","isSelected":false,"score":4},{"text":"Strongly agree","value":"glikertcol275bb3ab84","isSelected":false,"score":5}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrowb9cfdef9"},{"text":"Second row","value":"glikertrow5b6e1800"},{"text":"Third row","value":"glikertrowa5b5f578"},{"text":"Fourth row","value":"glikertrow27a6d5ef"},{"text":"Fifth row","value":"glikertrowd17a261b"}],"inputType":"likert","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"id":44,"label":"Rank Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Rank First Choice","value":"grank44ef548e7b","isSelected":false,"price":""},{"text":"Rank Second Choice","value":"grank440c3f3227","isSelected":false,"price":""},{"text":"Rank Third Choice","value":"grank444f7bdfe0","isSelected":false,"price":""},{"text":"Rank Fourth Choce","value":"grank44902be0db","isSelected":false,"price":""},{"text":"Rank Fifth Choice","value":"grank447f27daf1","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"rank","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":""},{"id":45,"label":"Rating Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Terrible","value":"grating45ed195d17","isSelected":false,"price":""},{"text":"Not so great","value":"grating4594b2edee","isSelected":false,"price":""},{"text":"Neutral","value":"grating4506fdbb76","isSelected":false,"price":""},{"text":"Pretty good","value":"grating45c8a62ee6","isSelected":false,"price":""},{"text":"Excellent","value":"grating4581a9f8d2","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"rating","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":""},{"type":"survey","id":46,"label":"Radio Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Survay Radio - First Choice","value":"gsurvey46baa416f0","isSelected":false,"price":""},{"text":"Survay Radio - Second Choice","value":"gsurvey4603c12a75","isSelected":false,"price":""},{"text":"Survay Radio - Third Choice","value":"gsurvey4641726850","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"radio","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":""},{"type":"survey","id":47,"label":"Checkbox Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"47.1","label":"Check - First Choice","name":""},{"id":"47.2","label":"Check - Second Choice","name":""},{"id":"47.3","label":"Check - Third Choice","name":""}],"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Check - First Choice","value":"gsurvey47526e8c41","isSelected":false,"price":""},{"text":"Check - Second Choice","value":"gsurvey47b70bdcfd","isSelected":false,"price":""},{"text":"Check - Third Choice","value":"gsurvey47faae3091","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"checkbox","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":""},{"type":"survey","id":48,"label":"Single Line Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":null,"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"text","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":""},{"type":"survey","id":49,"label":"Paragraph Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":null,"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"textarea","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":""},{"type":"survey","id":50,"label":"DropDown Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"DropDown - First Choice","value":"gsurvey50e71aa478","isSelected":false,"price":""},{"text":"DropDown - Second Choice","value":"gsurvey50792465b4","isSelected":false,"price":""},{"text":"DropDown - Third Choice","value":"gsurvey50d4b6b7b1","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"select","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":""},{"type":"post_title","id":28,"label":"Post Title","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_excerpt","id":29,"label":"Post Excerpt","adminLabel":"","isRequired":false,"size":"small","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_tags","id":30,"label":"Post Tags","adminLabel":"","isRequired":false,"size":"large","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_category","id":31,"label":"Post Category","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[],"displayAllCategories":true,"inputType":"select","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"post_image","id":32,"label":"Post Image","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"allowedExtensions":"jpg, jpeg, png, gif","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"displayTitle":true,"displayCaption":true,"displayDescription":true},{"type":"post_custom_field","id":33,"label":"Post Custom Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"text","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"postCustomFieldName":"Payer first name","customFieldTemplate":"","customFieldTemplateEnabled":false},{"type":"product","id":34,"label":"Product Basic","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":34.1,"label":"Name","name":""},{"id":34.2,"label":"Price","name":""},{"id":34.3,"label":"Quantity","name":""}],"inputType":"singleproduct","enablePrice":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$30.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"product","id":35,"label":"Product Name - Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"select","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"DD - First Choice","value":"DD - First Choice","isSelected":false,"price":"$5.00"},{"text":"DD - Second Choice","value":"DD - Second Choice","isSelected":false,"price":"$10.00"},{"text":"DD - Third Choice","value":"DD - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":51,"label":"Product Name - Radio Buttons","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"radio","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":52,"label":"User Defined Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"price","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":53,"label":"Hidden Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":53.1,"label":"Name","name":""},{"id":53.2,"label":"Price","name":""},{"id":53.3,"label":"Quantity","name":""}],"inputType":"hiddenproduct","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$50.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":54,"label":"Calculation Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":54.1,"label":"Name","name":""},{"id":54.2,"label":"Price","name":""},{"id":54.3,"label":"Quantity","name":""}],"inputType":"calculation","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"20 + 20","calculationRounding":"","enableCalculation":true,"basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quantity","id":36,"label":"Quantity Field for Hidden Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"number","productField":"53","numberFormat":"decimal_dot","formId":58,"pageNumber":1,"choices":"","inputs":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"option","id":37,"label":"Product Options for Basic Product","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"select","choices":[{"text":"Option 1","value":"Option 1","isSelected":false,"price":"$20.00"},{"text":"Option 2","value":"Option 2","isSelected":false,"price":"$30.00"},{"text":"Option 3","value":"Option 3","isSelected":false,"price":"$40.00"}],"enablePrice":true,"productField":34,"formId":58,"pageNumber":1,"inputs":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"option","id":38,"label":"Option for Calculation Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"checkbox","choices":[{"text":"Cal - Option 1","value":"Cal - Option 1","isSelected":false,"price":"$7.95"},{"text":"Cal - Option 2","value":"Cal - Option 2","isSelected":false,"price":"$12.10"},{"text":"Cal - Option 3","value":"Cal - Option 3","isSelected":false,"price":"$9.05"}],"enablePrice":true,"productField":"54","formId":58,"pageNumber":1,"inputs":[{"id":"38.1","label":"Cal - Option 1","name":""},{"id":"38.2","label":"Cal - Option 2","name":""},{"id":"38.3","label":"Cal - Option 3","name":""}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"shipping","id":39,"label":"Shipping","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"select","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"Express","value":"Express","isSelected":false,"price":"$20.00"},{"text":"Regular","value":"Regular","isSelected":false,"price":"$30.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"total","id":40,"label":"Total","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false}],"id":58,"subLabelPlacement":"below","cssClass":"","enableHoneypot":"","enableAnimation":"","save":{"enabled":"","button":{"type":"link","text":"Save and Continue Later"}},"limitEntries":"","limitEntriesCount":"","limitEntriesPeriod":"","limitEntriesMessage":"","scheduleForm":"","scheduleStart":"","scheduleStartHour":"","scheduleStartMinute":"","scheduleStartAmpm":"","scheduleEnd":"","scheduleEndHour":"","scheduleEndMinute":"","scheduleEndAmpm":"","schedulePendingMessage":"","scheduleMessage":"","requireLogin":"","requireLoginMessage":"","useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"postAuthor":"3","postCategory":"26","postFormat":"0","postStatus":"draft","notifications":{"54bca349732b8":{"id":"54bca349732b8","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}","isActive":false}},"confirmations":{"54bca34973cdd":{"id":"54bca34973cdd","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"gravityformsquiz":{"shuffleFields":"0","instantFeedback":"0","grades":[{"text":"A","value":90},{"text":"B","value":80},{"text":"C","value":70},{"text":"D","value":60},{"text":"E","value":0}],"grading":"letter","passPercent":"50","passfailDisplayConfirmation":"1","passConfirmationMessage":"<strong>Quiz Results:<\/strong> You Passed!\\n<strong>Score:<\/strong> {quiz_score}\\n<strong>Percentage:<\/strong> {quiz_percent}%","passConfirmationDisableAutoformat":"0","failConfirmationMessage":"<strong>Quiz Results:<\/strong> You Failed!\\n<strong>Score:<\/strong> {quiz_score}\\n<strong>Percentage:<\/strong> {quiz_percent}%","failConfirmationDisableAutoformat":"0","letterDisplayConfirmation":"1","letterConfirmationMessage":"<strong>Quiz Grade:<\/strong> {quiz_grade}\\n<strong>Score:<\/strong> {quiz_score}\\n<strong>Percentage:<\/strong> {quiz_percent}%","letterConfirmationDisableAutoformat":"0"},"is_active":"1","date_created":"2015-01-19 06:25:13","is_trash":"0"}
EOD;

		$forms[0] = json_decode( trim( $json ), true );

		foreach ( $forms as $id => $form ) {
			$results = GFAPI::add_form( $form );
			$this->form_id[$id] = $results;
		}
	}

	/**
	 * Add the entries to be used in the testing process
	 * @since 4.0
	 */
	private function create_entries() {
		$entries = json_decode( '[{"form_id":"58","date_created":"2015-01-20 01:15:58","is_starred":0,"is_read":1,"ip":"124.183.82.7","source_url":"http:\/\/example.org\/?gf_page=preview&id=58","post_id":"364","currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko\/20100101 Firefox\/35.0","status":"active","1":"My Single Line Response","2":"My paragraph text response over...\r\n\r\nMultiple lines.","3":"Option 3 Value","4":"Second Choice,Third Choice","5":"50032145","6.2":"Checkbox Choice 2","6.3":"Checkbox Choice 3","7":"Radio Second Choice","8":"hidden field value","11.2":"Mr.","11.3":"Jake","11.4":"Middle","11.6":"Jackson","11.8":"MD","12":"2015-01-01","13":"10:30 am","14":"(555) 678-1210","15.1":"12 Address St","15.2":"Line 2","15.3":"Cityville","15.4":"Statesman","15.5":"5000","15.6":"Chad","16":"https:\/\/gravitypdf.com","17":"support@gravitypdf.com","18":"http:\/\/example.org\/wp-content\/uploads\/gravity_forms\/58-8f4de538fff188d8557c12d830a38810\/2015\/01\/gravityforms-export-2015-01-14.xml","19":"[\"http:\\\/\\\/example.org\\\/wp-content\\\/uploads\\\/gravity_forms\\\/58-8f4de538fff188d8557c12d830a38810\\\/2015\\\/01\\\/CPC-JAKE.docx\",\"http:\\\/\\\/example.org\\\/wp-content\\\/uploads\\\/gravity_forms\\\/58-8f4de538fff188d8557c12d830a38810\\\/2015\\\/01\\\/Tent-Cards.pdf\"]","20":"a:3:{i:0;s:20:\"List Item Response 1\";i:1;s:20:\"List Item Response 2\";i:2;s:20:\"List Item Response 3\";}","21":"a:2:{i:0;a:3:{s:8:\"Column 1\";s:19:\"List Response Col 1\";s:8:\"Column 2\";s:19:\"List Response Col 2\";s:8:\"Column 3\";s:19:\"List Response Col 3\";}i:1;a:3:{s:8:\"Column 1\";s:22:\"List Response #2 Col 1\";s:8:\"Column 2\";s:22:\"List Response #2 Col 2\";s:8:\"Column 3\";s:22:\"List Response #2 Col 3\";}}","22":"gpoll22daaa4947","23":"gpoll23ee2a7382","41.1":"gpoll23517d6a14","41.2":"gpoll23ee2a7382","41.3":"gpoll232553ed18","24":"gquiz240836e68a","42":"gquiz24c91cc7af","43.2":"gquiz240836e68a","25":"54bdac4ed24af5.01502579.png","26":"glikertcol2636762f85","27.1":"glikertrowb9cfdef9:glikertcol27c274ea49","27.2":"glikertrow5b6e1800:glikertcol27114a23c1","27.3":"glikertrowa5b5f578:glikertcol27cb066f8a","27.4":"glikertrow27a6d5ef:glikertcol275bb3ab84","27.5":"glikertrowd17a261b:glikertcol275bb3ab84","44":"grank44902be0db,grank447f27daf1,grank440c3f3227,grank44ef548e7b,grank444f7bdfe0","45":"grating45c8a62ee6","46":"gsurvey46baa416f0","47.1":"gsurvey47526e8c41","47.2":"gsurvey47b70bdcfd","48":"Survey Field Single Line Response","49":"Paragraph survey field response...","50":"gsurvey50792465b4","28":"My Post Title","29":"My Post Excerpt","30":"tag1, tag2, tag3","31":"Test Category 2:30","32":"http:\/\/example.org\/wp-content\/uploads\/gravity_forms\/58-8f4de538fff188d8557c12d830a38810\/2015\/01\/one1-500x381.jpg|:|Post Image Title|:|Post Image caption|:|Post Image Description","33":"post_custom_field","34.1":"Product Basic","34.2":"$30.00","34.3":"3","35":"DD - Second Choice|10","51":"Radio - Second Choice|10","52":"$30.00","53.1":"Hidden Price","53.2":"$50.00","36":"6","37":"Option 2|30","38.1":"Cal - Option 1|7.95","38.2":"Cal - Option 2|12.1","39":"Regular|30","54.1":"Calculation Price","54.2":"$40.00","54.3":"5","40":"860.25","6.1":"","9":"","10":"","43.1":"","43.3":"","47.3":"","53.3":"","38.3":"","gquiz_score":"2","gquiz_percent":"40","gquiz_grade":"E","gquiz_is_pass":"0"},{"id":"955","form_id":"58","date_created":"2015-01-23 07:59:48","is_starred":0,"is_read":0,"ip":"124.183.82.7","source_url":"http:\/\/example.org\/?gf_page=preview&id=58","post_id":"367","currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko\/20100101 Firefox\/35.0","status":"active","3":"Option 2","8":"hidden field value","22":"gpoll22daaa4947","23":"gpoll23ee2a7382","24":"gquiz24a1a78951","26":"glikertcol26a40c345c","27.1":"glikertrowb9cfdef9:glikertcol27cb066f8a","27.2":"glikertrow5b6e1800:glikertcol27c274ea49","27.3":"glikertrowa5b5f578:glikertcol275bb3ab84","27.4":"glikertrow27a6d5ef:glikertcol27114a23c1","27.5":"glikertrowd17a261b:glikertcol27573469fe","31":"Uncategorized:1","34.1":"Product Basic","34.2":"$30.00","35":"DD - First Choice|5","37":"Option 1|20","39":"Express|20","40":"25","41.2":"gpoll23ee2a7382","41.3":"gpoll232553ed18","42":"gquiz24c91cc7af","43.2":"gquiz240836e68a","44":"grank44ef548e7b,grank440c3f3227,grank447f27daf1,grank444f7bdfe0,grank44902be0db","45":"grating4581a9f8d2","46":"gsurvey46baa416f0","47.2":"gsurvey47b70bdcfd","47.3":"gsurvey47faae3091","48":"Survey Field Single Line Response 123","49":"Paragraph Survey Field...","50":"gsurvey50792465b4","53.1":"Hidden Price","53.2":"$50.00","54.1":"Calculation Price","54.2":"$40.00","1":"","2":"","4":"","5":"","6.1":"","6.2":"","6.3":"","7":"","9":"","10":"","11.2":"","11.3":"","11.4":"","11.6":"","11.8":"","12":"","13":"","14":"","15.1":"","15.2":"","15.3":"","15.4":"","15.5":"","15.6":"","16":"","17":"","18":"","19":"","20":"","21":"","41.1":"","43.1":"","43.3":"","25":"","47.1":"","28":"","29":"","30":"","32":"","33":"","34.3":"","51":"","52":"","53.3":"","54.3":"","36":"","38.1":"","38.2":"","38.3":"","gquiz_score":"1","gquiz_percent":"20","gquiz_grade":"E","gquiz_is_pass":"0"},{"id":"956","form_id":"58","date_created":"2015-01-23 08:00:31","is_starred":0,"is_read":0,"ip":"124.183.82.7","source_url":"http:\/\/example.org\/?gf_page=preview&id=58","post_id":"368","currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko\/20100101 Firefox\/35.0","status":"active","3":"Option 2","8":"hidden field value","22":"gpoll220a301dd5","23":"gpoll23517d6a14","24":"gquiz24a1a78951","26":"glikertcol2648e6c579","27.1":"glikertrowb9cfdef9:glikertcol27573469fe","27.2":"glikertrow5b6e1800:glikertcol27573469fe","27.3":"glikertrowa5b5f578:glikertcol27573469fe","27.4":"glikertrow27a6d5ef:glikertcol27114a23c1","27.5":"glikertrowd17a261b:glikertcol27114a23c1","31":"Uncategorized:1","34.1":"Product Basic","34.2":"$30.00","35":"DD - First Choice|5","37":"Option 1|20","39":"Express|20","40":"25","41.1":"gpoll23517d6a14","41.2":"gpoll23ee2a7382","42":"gquiz24c91cc7af","43.2":"gquiz240836e68a","44":"grank440c3f3227,grank44ef548e7b,grank44902be0db,grank444f7bdfe0,grank447f27daf1","45":"grating4594b2edee","46":"gsurvey4641726850","47.2":"gsurvey47b70bdcfd","47.3":"gsurvey47faae3091","48":"Single Line","49":"Paragraph Survey Field","50":"gsurvey50d4b6b7b1","53.1":"Hidden Price","53.2":"$50.00","54.1":"Calculation Price","54.2":"$40.00","1":"","2":"","4":"","5":"","6.1":"","6.2":"","6.3":"","7":"","9":"","10":"","11.2":"","11.3":"","11.4":"","11.6":"","11.8":"","12":"","13":"","14":"","15.1":"","15.2":"","15.3":"","15.4":"","15.5":"","15.6":"","16":"","17":"","18":"","19":"","20":"","21":"","41.3":"","43.1":"","43.3":"","25":"","47.1":"","28":"","29":"","30":"","32":"","33":"","34.3":"","51":"","52":"","53.3":"","54.3":"","36":"","38.1":"","38.2":"","38.3":"","gquiz_score":"1","gquiz_percent":"20","gquiz_grade":"E","gquiz_is_pass":"0"},{"id":"957","form_id":"58","date_created":"2015-01-23 08:01:00","is_starred":0,"is_read":0,"ip":"124.183.82.7","source_url":"http:\/\/example.org\/?gf_page=preview&id=58","post_id":"369","currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko\/20100101 Firefox\/35.0","status":"active","3":"Option 2","8":"hidden field value","22":"gpoll22daaa4947","23":"gpoll23ee2a7382","24":"gquiz24c91cc7af","26":"glikertcol26a40c345c","27.1":"glikertrowb9cfdef9:glikertcol27c274ea49","27.2":"glikertrow5b6e1800:glikertcol27c274ea49","27.3":"glikertrowa5b5f578:glikertcol27c274ea49","27.4":"glikertrow27a6d5ef:glikertcol27114a23c1","27.5":"glikertrowd17a261b:glikertcol27114a23c1","31":"Uncategorized:1","34.1":"Product Basic","34.2":"$30.00","35":"DD - First Choice|5","37":"Option 1|20","39":"Express|20","40":"25","41.1":"gpoll23517d6a14","41.2":"gpoll23ee2a7382","41.3":"gpoll232553ed18","42":"gquiz24c91cc7af","43.1":"gquiz24c91cc7af","43.2":"gquiz240836e68a","43.3":"gquiz24a1a78951","44":"grank44ef548e7b,grank440c3f3227,grank444f7bdfe0,grank44902be0db,grank447f27daf1","45":"grating45c8a62ee6","46":"gsurvey4641726850","47.1":"gsurvey47526e8c41","47.2":"gsurvey47b70bdcfd","47.3":"gsurvey47faae3091","50":"gsurvey50e71aa478","53.1":"Hidden Price","53.2":"$50.00","54.1":"Calculation Price","54.2":"$40.00","1":"","2":"","4":"","5":"","6.1":"","6.2":"","6.3":"","7":"","9":"","10":"","11.2":"","11.3":"","11.4":"","11.6":"","11.8":"","12":"","13":"","14":"","15.1":"","15.2":"","15.3":"","15.4":"","15.5":"","15.6":"","16":"","17":"","18":"","19":"","20":"","21":"","25":"","48":"","49":"","28":"","29":"","30":"","32":"","33":"","34.3":"","51":"","52":"","53.3":"","54.3":"","36":"","38.1":"","38.2":"","38.3":"","gquiz_score":"3","gquiz_percent":"60","gquiz_grade":"D","gquiz_is_pass":"1"},{"id":"958","form_id":"58","date_created":"2015-01-23 08:02:20","is_starred":0,"is_read":0,"ip":"124.183.82.7","source_url":"http:\/\/example.org\/?gf_page=preview&id=58","post_id":"370","currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko\/20100101 Firefox\/35.0","status":"active","3":"Option 2","8":"hidden field value","23":"gpoll232553ed18","24":"gquiz24c91cc7af","31":"Uncategorized:1","34.1":"Product Basic","34.2":"$30.00","35":"DD - First Choice|5","37":"Option 1|20","39":"Express|20","40":"25","42":"gquiz240836e68a","43.2":"gquiz240836e68a","43.3":"gquiz24a1a78951","44":"grank44ef548e7b,grank440c3f3227,grank444f7bdfe0,grank44902be0db,grank447f27daf1","50":"gsurvey50e71aa478","53.1":"Hidden Price","53.2":"$50.00","54.1":"Calculation Price","54.2":"$40.00","1":"","2":"","4":"","5":"","6.1":"","6.2":"","6.3":"","7":"","9":"","10":"","11.2":"","11.3":"","11.4":"","11.6":"","11.8":"","12":"","13":"","14":"","15.1":"","15.2":"","15.3":"","15.4":"","15.5":"","15.6":"","16":"","17":"","18":"","19":"","20":"","21":"","22":"","41.1":"","41.2":"","41.3":"","43.1":"","25":"","26":"","27.1":"","27.2":"","27.3":"","27.4":"","27.5":"","45":"","46":"","47.1":"","47.2":"","47.3":"","48":"","49":"","28":"","29":"","30":"","32":"","33":"","34.3":"","51":"","52":"","53.3":"","54.3":"","36":"","38.1":"","38.2":"","38.3":"","gquiz_score":"5","gquiz_percent":"100","gquiz_grade":"A","gquiz_is_pass":"1"},{"id":"959","form_id":"58","date_created":"2015-01-23 08:02:24","is_starred":0,"is_read":0,"ip":"124.183.82.7","source_url":"http:\/\/example.org\/?gf_page=preview&id=58","post_id":"371","currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko\/20100101 Firefox\/35.0","status":"active","3":"Option 2","8":"hidden field value","23":"gpoll232553ed18","24":"gquiz24c91cc7af","31":"Uncategorized:1","34.1":"Product Basic","34.2":"$30.00","35":"DD - First Choice|5","37":"Option 1|20","39":"Express|20","40":"25","42":"gquiz240836e68a","43.2":"gquiz240836e68a","43.3":"gquiz24a1a78951","44":"grank44ef548e7b,grank440c3f3227,grank444f7bdfe0,grank44902be0db,grank447f27daf1","50":"gsurvey50e71aa478","53.1":"Hidden Price","53.2":"$50.00","54.1":"Calculation Price","54.2":"$40.00","1":"","2":"","4":"","5":"","6.1":"","6.2":"","6.3":"","7":"","9":"","10":"","11.2":"","11.3":"","11.4":"","11.6":"","11.8":"","12":"","13":"","14":"","15.1":"","15.2":"","15.3":"","15.4":"","15.5":"","15.6":"","16":"","17":"","18":"","19":"","20":"","21":"","22":"","41.1":"","41.2":"","41.3":"","43.1":"","25":"","26":"","27.1":"","27.2":"","27.3":"","27.4":"","27.5":"","45":"","46":"","47.1":"","47.2":"","47.3":"","48":"","49":"","28":"","29":"","30":"","32":"","33":"","34.3":"","51":"","52":"","53.3":"","54.3":"","36":"","38.1":"","38.2":"","38.3":"","gquiz_score":"5","gquiz_percent":"100","gquiz_grade":"A","gquiz_is_pass":"1"}]', true );
		$results = GFAPI::add_entries( $entries, $this->form_id[0] );
		$this->entries = $results;
	}

    /**
     * Our software takes the $form and $entry details and converts it to the $form_data array
     * Set this up so all our functions have access
     * @since 4.0
     */
    private function setup_form_data() {
        return array(
            'form'  => GFFormsModel::get_form_meta($this->form_id[0]),
            'entry' => GFFormsModel::get_lead($this->entries[0]),
        );
    }

	/**
	 * Check if all the correct actions are applied
	 * @since 4.0
	 * @group pdf
	 */
	public function test_actions() {
		$this->assertSame( 10, has_action( 'parse_request', array( $this->controller, 'process_legacy_pdf_endpoint' ) ) );
		$this->assertSame( 10, has_action( 'parse_request', array( $this->controller, 'process_pdf_endpoint' ) ) );

		$this->assertSame( 10, has_action( 'gform_entries_first_column_actions', array( $this->model, 'view_pdf_entry_list' ) ) );
		$this->assertSame( 10, has_action( 'gform_entry_info', array( $this->model, 'view_pdf_entry_detail' ) ) );
		$this->assertSame( 10, has_action( 'gform_after_submission', array( $this->model, 'maybe_save_pdf' ) ) );
		$this->assertSame( 10, has_action( 'gfpdf_cleanup_tmp_dir', array( $this->model, 'cleanup_tmp_dir' ) ) );
	}

	/**
	 * Check if all the correct filters are applied
	 * @since 4.0
	 * @group pdf
	 */
	public function test_filters() {
		global $gfpdf;

		$this->assertSame( 10, has_filter( 'gfpdf_pdf_middleware', array( $this->model, 'middle_active' ) ) );
		$this->assertSame( 10, has_filter( 'gfpdf_pdf_middleware', array( $this->model, 'middle_conditional' ) ) );
		$this->assertSame( 20, has_filter( 'gfpdf_pdf_middleware', array( $this->model, 'middle_logged_out_restriction' ) ) );
		$this->assertSame( 30, has_filter( 'gfpdf_pdf_middleware', array( $this->model, 'middle_logged_out_timeout' ) ) );
		$this->assertSame( 40, has_filter( 'gfpdf_pdf_middleware', array( $this->model, 'middle_auth_logged_out_user' ) ) );
		$this->assertSame( 50, has_filter( 'gfpdf_pdf_middleware', array( $this->model, 'middle_user_capability' ) ) );

		$this->assertSame( 9999, has_filter( 'gform_notification', array( $this->model, 'notifications' ), 9999 ) );

		$this->assertSame( 10, has_filter( 'mpdf_tmp_path', array( $this->model, 'mpdf_tmp_path' ) ) );
		$this->assertSame( 10, has_filter( 'mpdf_fontdata_path', array( $this->model, 'mpdf_tmp_path' ) ) );
		$this->assertSame( 10, has_filter( 'mpdf_current_font_path', array( $this->model, 'set_current_pdf_font' ) ) );
		$this->assertSame( 10, has_filter( 'mpdf_font_data', array( $this->model, 'register_custom_font_data_with_mPDF' ) ) );

		$this->assertSame( 10, has_filter( 'gfpdf_pdf_html_output', array( $gfpdf->misc, 'do_mergetags' ) ) );
		$this->assertSame( 10, has_filter( 'gfpdf_pdf_html_output', 'do_shortcode' ) );

		/* Backwards compatiblity */
		$this->assertSame( 1, has_filter( 'gfpdfe_pre_load_template', array( 'PDFRender', 'prepare_ids' ) ) );
	}

	/**
	 * Test if our active PDF middleware works correctly
	 * @since 4.0
	 * @group pdf
	 */
	public function test_middle_active() {

		/* Check if error correctly triggered */
		$settings['active'] = false;
		$this->assertTrue( is_wp_error( $this->model->middle_active( '', '', $settings ) ) );

		/* Check if setting passes */
		$settings['active'] = true;
		$this->assertTrue( $this->model->middle_active( true, '', $settings ) );
	}

	/**
	 * Test if our conditional logic middleware works correctly
	 * @since 4.0
	 * @group pdf
	 */
	public function test_middle_conditional() {

		/* Setup some test data */
        $results          = $this->create_form_and_entries();
        $entry            = $results['entry'];
        $entry['form_id'] = $results['form']['id'];

		/* Create a passing condition */
		$settings['conditionalLogic'] = array(
			'actionType' => 'show',
			'logicType'  => 'all',
			'rules'      => array(
                array(
    				'fieldId'  => '1',
    				'operator' => 'is',
    				'value'    => 'My Single Line Response',
			    ),
            ),
		);

		$this->assertTrue( $this->model->middle_conditional( true, $entry, $settings ) );

		/* Create a failing condition */
		$settings['conditionalLogic']['rules']['value'] = 'test';

		$this->assertTrue( is_wp_error( $this->model->middle_conditional( true, $entry, $settings ) ) );
	}

	/**
	 * Check if correct GF entry owner is determined
	 * @since 4.0
	 * @group pdf
	 */
	public function test_is_current_pdf_owner() {
		/* set up a user to test its privilages */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		/* Set up a blank entry array */
		$entry = array(
			'created_by' => '',
			'ip'         => '',
		);

		$this->assertFalse( $this->model->is_current_pdf_owner( $entry ) );

		/* assign our user ID */
		$entry['created_by'] = $user_id;

		$this->assertTrue( $this->model->is_current_pdf_owner( $entry ) );
		$this->assertTrue( $this->model->is_current_pdf_owner( $entry, 'logged_in' ) );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry, 'logged_out' ) );

		/* logout and retest */
		wp_set_current_user( 0 );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry ) );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry, 'logged_in' ) );

		/* Set the IPs */
		$entry['ip'] = '197.64.12.40';
		$_SERVER['HTTP_CLIENT_IP'] = $entry['ip'];

		$this->assertTrue( $this->model->is_current_pdf_owner( $entry ) );
		$this->assertTrue( $this->model->is_current_pdf_owner( $entry, 'logged_out' ) );
		$this->assertFalse( $this->model->is_current_pdf_owner( $entry, 'logged_in' ) );
	}

	/**
	 * Check if our logged out restrictions are being applied correctly
	 * @since 4.0
	 * @group pdf
	 */
	public function test_middle_logged_out_restrictions() {
		global $gfpdf;

		/* Disable test and check results */
		$gfpdf->options->update_option( 'limit_to_admin', 'No' );

		$this->assertTrue( $this->model->middle_logged_out_restriction( true, '', '' ) );
		$this->assertTrue( is_wp_error( $this->model->middle_logged_out_restriction( new WP_Error( '' ), '', '' ) ) );

		/* Enable our tests */
		$gfpdf->options->update_option( 'limit_to_admin', 'Yes' );

		/* test if we are redirecting */
		try {
			wp_set_current_user( 0 );
			$this->model->middle_logged_out_restriction( true, '', '' );
		} catch (Exception $e) {
			$this->assertEquals( 'Redirecting', $e->getMessage() );
		}

		/* Test if logged in users are ignored */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_logged_out_restriction( true, '', '' ) );
	}

	/**
	 * Check if our logged out timeout restrictions are being applied correctly
	 * @since 4.0
	 * @group pdf
	 */
	public function test_middle_logged_out_timeout() {
		global $gfpdf;

		/* Set up our testing data */
		$entry = array(
			'date_created' => date( 'Y-m-d H:i:s', strtotime( '-32 minutes' ) ),
			'ip'           => '197.64.12.40',
		);

		$_SERVER['HTTP_CLIENT_IP'] = $entry['ip'];

		/* Test we get a timeout error */
		$results = $this->model->middle_logged_out_timeout( true, $entry, '' );
		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'timeout_expired', $results->get_error_code() );

		/* Test we get a auth redirect */
		$entry['created_by'] = 5;

		try {
			$this->model->middle_logged_out_timeout( true, $entry, '' );
		} catch (Exception $e) {
			$this->assertEquals( 'Redirecting', $e->getMessage() );
		}

		/* Update timeout settings and check again */
		$gfpdf->options->update_option( 'logged_out_timeout', '33' );
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, '' ) );

		/* Check if the test should be skipped */
		$_SERVER['HTTP_CLIENT_IP'] = '12.123.123.124';
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, '' ) );
		$this->assertTrue( is_wp_error( $this->model->middle_logged_out_timeout( new WP_Error(), $entry, '' ) ) );

		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_logged_out_timeout( true, $entry, '' ) );
	}

	/**
	 * Check if our logged out user has access to our PDF
	 * @since 4.0
	 * @group pdf
	 */
	public function test_middle_auth_logged_out_user() {

		 /* Set up our testing data */
		$entry = array(
			'ip'           => '197.64.12.40',
		);

		/* Check for WP Error */
		$this->assertTrue( is_wp_error( $this->model->middle_auth_logged_out_user( true, $entry, '' ) ) );

		/* Check for redirect */
		$entry['created_by'] = 5;

		try {
			$this->model->middle_auth_logged_out_user( true, $entry, '' );
		} catch (Exception $e) {
			$this->assertEquals( 'Redirecting', $e->getMessage() );
		}

		/* Test that the middleware is skipped */
		$_SERVER['HTTP_CLIENT_IP'] = $entry['ip'];
		$this->assertTrue( $this->model->middle_auth_logged_out_user( true, $entry, '' ) );

		unset($_SERVER['HTTP_CLIENT_IP']);
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );
		$this->assertTrue( $this->model->middle_auth_logged_out_user( true, $entry, '' ) );
	}

	/**
	 * Check if our logged in user has access to our PDF
	 * @since 4.0
	 * @group pdf
	 */
	public function test_middle_middle_user_capability() {
		global $current_user;

		/* Check for WP Error */
		$this->assertTrue( is_wp_error( $this->model->middle_user_capability( new WP_Error(), '', '' ) ) );

		/* create subscriber and test access */
		$user_id = $this->factory->user->create();
		$this->assertInternalType( 'integer', $user_id );
		wp_set_current_user( $user_id );

		/* get the results */
		$results = $this->model->middle_user_capability( true, '', '' );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'access_denied', $results->get_error_code() );

		/* Elevate user to administrator */
		$user = wp_get_current_user();
		$user->remove_role( 'subscriber' );
		$user->add_role( 'administrator' );

		$this->assertTrue( $this->model->middle_user_capability( true, '', '' ) );

		/* Remove elevated user privilages and set the default capability 'gravityforms_view_entries' */
		$user->remove_role( 'administrator' );
		$user->add_role( 'subscriber' );

		/* Double check they have been removed */
		$results = $this->model->middle_user_capability( true, '', '' );

		$this->assertTrue( is_wp_error( $results ) );
		$this->assertEquals( 'access_denied', $results->get_error_code() );

		/* Add default capability and test */
		$user->add_cap( 'gravityforms_view_entries' );
		$this->assertTrue( $this->model->middle_user_capability( true, '', '' ) );
	}
}
