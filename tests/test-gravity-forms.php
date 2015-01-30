<?php

/**
 * PDF Extended integrates directly with Gravity Forms
 * Breakages can occur if the plugin starts returning results which
 * differ from what we expected.
 *
 * This class will test all the functions we use in the software to
 * ensure we maintain compatibility.
 */
class Test_GravityForms extends WP_UnitTestCase
{
    private $form_id = array();
    private $entries = array();

    public function setUp()
    {

        /*
         * For some reasons this wasn't automatically set up so
         * we are running it here.
         */
        $this->factory = new WP_UnitTest_Factory();

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
        $wpdb->query('SET autocommit = 0;');
        $wpdb->query('START TRANSACTION;');
    }

    public function tearDown()
    {
        parent::tearDown();

        /*
         * Uninstall Gravity Forms
         */
        RGFormsModel::drop_tables();
    }

    private function create_form_and_entries()
    {
        $this->create_forms();
        $this->create_entries();
    }

    private function create_forms()
    {
        $form = json_decode('{"title":"Simple Form Testing","description":"","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"id":1,"label":"Name","adminLabel":"","type":"name","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":1.3,"label":"First","name":""},{"id":1.6,"label":"Last","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":2,"label":"Address","adminLabel":"","type":"address","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":2.1,"label":"Street Address","name":""},{"id":2.2,"label":"Address Line 2","name":""},{"id":2.3,"label":"City","name":""},{"id":2.4,"label":"State \/ Province","name":""},{"id":2.5,"label":"ZIP \/ Postal Code","name":""},{"id":2.6,"label":"Country","name":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":3,"label":"Email","adminLabel":"","type":"email","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":4,"label":"Phone","adminLabel":"","type":"phone","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":5,"label":"Untitled","adminLabel":"","type":"select","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":6,"label":"Untitled","adminLabel":"","type":"multiselect","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":47,"pageNumber":1,"descriptionPlacement":"below"},{"id":7,"label":"Untitled","adminLabel":"","type":"textarea","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":47,"pageNumber":1,"descriptionPlacement":"below"}],"id":47,"useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"notifications":{"5414ff2b70018":{"id":"5414ff2b70018","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}"},"5414ff5a5a28a":{"isActive":true,"id":"5414ff5a5a28a","name":"User Notification","event":"form_submission","to":"3","toType":"field","bcc":"","subject":"Email Notification","message":"User Notification","from":"{admin_email}","fromName":"","replyTo":"","routing":null,"conditionalLogic":null,"disableAutoformat":""}},"confirmations":{"5414ff2b752f0":{"id":"5414ff2b752f0","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"is_active":"1","date_created":"2014-09-14 02:36:27","is_trash":"0"}', true);

        $results = GFAPI::add_form($form);
        $this->form_id[0] = $results;

        $json = <<<EOT
{"title":"ALL FIELDS","description":"This is the form description...","labelPlacement":"top_label","descriptionPlacement":"below","button":{"type":"text","text":"Submit","imageUrl":""},"fields":[{"type":"text","id":1,"label":"Single Line Text","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the single line text description","cssClass":"exclude","inputType":""},{"type":"textarea","id":2,"label":"Paragraph Text","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the paragraph text description","inputType":""},{"type":"select","id":3,"label":"Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"Option 1","value":"Option 1","isSelected":false,"price":""},{"text":"Option 2","value":"Option 2","isSelected":true,"price":""},{"text":"Option 3","value":"Option 3 Value","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the drop down description","inputType":""},{"type":"multiselect","id":4,"label":"Multi Select Box","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"First Choice","value":"First Choice","isSelected":false,"price":""},{"text":"Multi Select Second Choice","value":"Second Choice","isSelected":false,"price":""},{"text":"Multi Select Third Choice","value":"Third Choice","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"This is the multi select box description","inputType":""},{"type":"number","id":5,"label":"Number","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"numberFormat":"decimal_dot","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","inputType":""},{"type":"checkbox","id":6,"label":"Checkbox","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","choices":[{"text":"Checkbox Choice 1","value":"Checkbox Choice 1","isSelected":false,"price":""},{"text":"Checkbox Choice 2","value":"Checkbox Choice 2","isSelected":false,"price":""},{"text":"Checkbox Choice 3","value":"Checkbox Choice 3","isSelected":false,"price":""}],"inputs":[{"id":"6.1","label":"Checkbox Choice 1","name":""},{"id":"6.2","label":"Checkbox Choice 2","name":""},{"id":"6.3","label":"Checkbox Choice 3","name":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Checkbox description"},{"type":"radio","id":7,"label":"Radio Button","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[{"text":"Radio First Choice","value":"Radio First Choice","isSelected":false,"price":""},{"text":"Radio Second Choice Name","value":"Radio Second Choice","isSelected":false,"price":""},{"text":"Radio Third Choice","value":"Radio Third Choice","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Radio button description","enableOtherChoice":true},{"type":"hidden","id":8,"label":"Hidden Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"hidden field value"},{"type":"html","id":9,"label":"HTML Block","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"displayOnly":true,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"content":"This is a HTML block"},{"type":"section","id":10,"label":"Section Break","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"displayOnly":true,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Section Break Description"},{"type":"name","id":11,"label":"Name","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","nameFormat":"advanced","inputs":[{"id":"11.2","label":"Prefix","name":"","choices":[{"text":"Mr.","value":"Mr.","isSelected":false,"price":""},{"text":"Mrs.","value":"Mrs.","isSelected":false,"price":""},{"text":"Miss","value":"Miss","isSelected":false,"price":""},{"text":"Ms.","value":"Ms.","isSelected":false,"price":""},{"text":"Dr.","value":"Dr.","isSelected":false,"price":""},{"text":"Prof.","value":"Prof.","isSelected":false,"price":""},{"text":"Rev.","value":"Rev.","isSelected":false,"price":""}],"isHidden":false,"inputType":"radio"},{"id":"11.3","label":"First","name":""},{"id":"11.4","label":"Middle","name":"","isHidden":false},{"id":"11.6","label":"Last","name":""},{"id":"11.8","label":"Suffix","name":"","isHidden":false}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"description":"Name Description"},{"type":"date","id":12,"label":"Date","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","dateType":"datepicker","calendarIconType":"none","calendarIconUrl":"","allowsPrepopulate":false,"description":"Date Description","dateFormat":"dmy"},{"type":"time","id":13,"label":"Time","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"13.1","label":"HH","name":""},{"id":"13.2","label":"MM","name":""},{"id":"13.3","label":"AM\/PM","name":""}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"timeFormat":"12"},{"type":"phone","id":14,"label":"Phone","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"phoneFormat":"standard","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"address","id":15,"label":"Address","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":15.1,"label":"Street Address","name":""},{"id":15.2,"label":"Address Line 2","name":""},{"id":15.3,"label":"City","name":""},{"id":15.4,"label":"State \/ Province","name":""},{"id":15.5,"label":"ZIP \/ Postal Code","name":""},{"id":15.6,"label":"Country","name":""}],"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"website","id":16,"label":"Website","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"email","id":17,"label":"Email","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"fileupload","id":18,"label":"File","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"fileupload","id":19,"label":"File","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":true,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"inputType":"","defaultValue":"","description":""},{"type":"list","id":20,"label":"Basic List","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"list","id":21,"label":"Extended List","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":[{"text":"Column 1","value":"Column 1","isSelected":false,"price":""},{"text":"Column 2","value":"Column 2","isSelected":false,"price":""},{"text":"Column 3","value":"Column 3","isSelected":false,"price":""}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"enableColumns":true},{"type":"poll","id":22,"label":"Poll Field - Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"poll_field_type":"select","inputType":"select","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Dropdown - First Choice","value":"gpoll22daaa4947","isSelected":false,"price":""},{"text":"Poll Dropdown - Second Choice","value":"gpoll220a301dd5","isSelected":false,"price":""},{"text":"Poll Dropdown - Third Choice","value":"gpoll22175a8601","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"poll","id":23,"label":"Poll Field - Radio Buttons","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"poll_field_type":"radio","inputType":"radio","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Radio - First Choice","value":"gpoll23517d6a14","isSelected":false,"price":""},{"text":"Poll Radio - Second Choice","value":"gpoll23ee2a7382","isSelected":false,"price":""},{"text":"Poll Radio - Third Choice","value":"gpoll232553ed18","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"poll","id":41,"label":"Poll Field - Checkboxes","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"41.1","label":"Poll Check First Choice","name":""},{"id":"41.2","label":"Poll Check Second Choice","name":""},{"id":"41.3","label":"Poll Check Third Choice","name":""}],"poll_field_type":"checkbox","inputType":"checkbox","enableChoiceValue":true,"enablePrice":false,"enableRandomizeChoices":false,"choices":[{"text":"Poll Check First Choice","value":"gpoll23517d6a14","isSelected":false,"price":""},{"text":"Poll Check Second Choice","value":"gpoll23ee2a7382","isSelected":false,"price":""},{"text":"Poll Check Third Choice","value":"gpoll232553ed18","isSelected":false,"price":""}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":24,"label":"Quiz Dropdown","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"gquizFieldType":"select","inputType":"select","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":true,"choices":[{"text":"Quiz Dropdown - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"3"},{"text":"Quiz Dropdown - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"2"},{"text":"Quiz Dropdown - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"1"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":42,"label":"Quiz Radio","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"gquizFieldType":"radio","inputType":"radio","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":false,"choices":[{"text":"Quiz Radio - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"},{"text":"Quiz Radio - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"},{"text":"Quiz Radio - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quiz","id":43,"label":"Quiz Checkbox","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"43.1","label":"Quiz Checkbox - First Choice","name":""},{"id":"43.2","label":"Quiz Checkbox - Second Choice","name":""},{"id":"43.3","label":"Quiz Checkbox - Third Choice","name":""}],"gquizFieldType":"checkbox","inputType":"checkbox","enableChoiceValue":true,"enablePrice":false,"gquizEnableRandomizeQuizChoices":false,"gquizShowAnswerExplanation":false,"gquizAnswerExplanation":"","gquizWeightedScoreEnabled":false,"choices":[{"text":"Quiz Checkbox - First Choice","value":"gquiz24c91cc7af","isSelected":false,"price":"","gquizIsCorrect":false,"gquizWeight":"0"},{"text":"Quiz Checkbox - Second Choice","value":"gquiz240836e68a","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"},{"text":"Quiz Checkbox - Third Choice","value":"gquiz24a1a78951","isSelected":false,"price":"","gquizIsCorrect":true,"gquizWeight":"0"}],"formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"id":25,"label":"Signature","adminLabel":"","type":"signature","isRequired":false,"size":"medium","errorMessage":"","formId":58,"pageNumber":1,"choices":"","inputs":"","conditionalLogic":"","displayOnly":""},{"id":26,"label":"Likert Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Strongly disagree","value":"glikertcol2636762f85","isSelected":false,"score":1},{"text":"Disagree","value":"glikertcol26a40c345c","isSelected":false,"score":2},{"text":"Neutral","value":"glikertcol26114a03dd","isSelected":false,"score":3},{"text":"Agree","value":"glikertcol26d3452ed6","isSelected":false,"score":4},{"text":"Strongly agree","value":"glikertcol2648e6c579","isSelected":false,"score":5}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrowd6ee998c"},{"text":"Second row","value":"glikertrow3ba9477d"},{"text":"Third row","value":"glikertrowbfdd8b2d"},{"text":"Fourth row","value":"glikertrowb042f1a8"},{"text":"Fifth row","value":"glikertrow4770db3e"}],"inputType":"likert","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"id":27,"label":"Likert Survey Field Extended","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"27.1","label":"First row","name":"glikertrowb9cfdef9"},{"id":"27.2","label":"Second row","name":"glikertrow5b6e1800"},{"id":"27.3","label":"Third row","name":"glikertrowa5b5f578"},{"id":"27.4","label":"Fourth row","name":"glikertrow27a6d5ef"},{"id":"27.5","label":"Fifth row","name":"glikertrowd17a261b"}],"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":true,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Strongly disagree","value":"glikertcol27573469fe","isSelected":false,"score":1},{"text":"Disagree","value":"glikertcol27114a23c1","isSelected":false,"score":2},{"text":"Neutral","value":"glikertcol27c274ea49","isSelected":false,"score":3},{"text":"Agree","value":"glikertcol27cb066f8a","isSelected":false,"score":4},{"text":"Strongly agree","value":"glikertcol275bb3ab84","isSelected":false,"score":5}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrowb9cfdef9"},{"text":"Second row","value":"glikertrow5b6e1800"},{"text":"Third row","value":"glikertrowa5b5f578"},{"text":"Fourth row","value":"glikertrow27a6d5ef"},{"text":"Fifth row","value":"glikertrowd17a261b"}],"inputType":"likert","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"id":44,"label":"Rank Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Rank First Choice","value":"grank44ef548e7b","isSelected":false,"price":""},{"text":"Rank Second Choice","value":"grank440c3f3227","isSelected":false,"price":""},{"text":"Rank Third Choice","value":"grank444f7bdfe0","isSelected":false,"price":""},{"text":"Rank Fourth Choce","value":"grank44902be0db","isSelected":false,"price":""},{"text":"Rank Fifth Choice","value":"grank447f27daf1","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"rank","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","formId":58,"pageNumber":1,"conditionalLogic":""},{"id":45,"label":"Rating Survey Field","adminLabel":"","type":"survey","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Terrible","value":"grating45ed195d17","isSelected":false,"price":""},{"text":"Not so great","value":"grating4594b2edee","isSelected":false,"price":""},{"text":"Neutral","value":"grating4506fdbb76","isSelected":false,"price":""},{"text":"Pretty good","value":"grating45c8a62ee6","isSelected":false,"price":""},{"text":"Excellent","value":"grating4581a9f8d2","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"rating","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":46,"label":"Radio Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Survay Radio - First Choice","value":"gsurvey46baa416f0","isSelected":false,"price":""},{"text":"Survay Radio - Second Choice","value":"gsurvey4603c12a75","isSelected":false,"price":""},{"text":"Survay Radio - Third Choice","value":"gsurvey4641726850","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"radio","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":47,"label":"Checkbox Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":"47.1","label":"Check - First Choice","name":""},{"id":"47.2","label":"Check - Second Choice","name":""},{"id":"47.3","label":"Check - Third Choice","name":""}],"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"Check - First Choice","value":"gsurvey47526e8c41","isSelected":false,"price":""},{"text":"Check - Second Choice","value":"gsurvey47b70bdcfd","isSelected":false,"price":""},{"text":"Check - Third Choice","value":"gsurvey47faae3091","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"checkbox","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":48,"label":"Single Line Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":null,"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"text","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":49,"label":"Paragraph Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":null,"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"textarea","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"survey","id":50,"label":"DropDown Survey Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"enableChoiceValue":true,"enablePrice":false,"gsurveyLikertEnableMultipleRows":false,"gsurveyLikertEnableScoring":false,"choices":[{"text":"DropDown - First Choice","value":"gsurvey50e71aa478","isSelected":false,"price":""},{"text":"DropDown - Second Choice","value":"gsurvey50792465b4","isSelected":false,"price":""},{"text":"DropDown - Third Choice","value":"gsurvey50d4b6b7b1","isSelected":false,"price":""}],"gsurveyLikertRows":[{"text":"First row","value":"glikertrow2471ee53"},{"text":"Second row","value":"glikertrow5ccf6d45"},{"text":"Third row","value":"glikertrow4ec600ab"},{"text":"Fourth row","value":"glikertrowa520c792"},{"text":"Fifth row","value":"glikertrow04dbdbf7"}],"inputType":"select","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":"","reversed":true,"customFieldTemplate":"","customFieldTemplateEnabled":false,"formId":58,"pageNumber":1,"conditionalLogic":""},{"type":"post_title","id":28,"label":"Post Title","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_excerpt","id":29,"label":"Post Excerpt","adminLabel":"","isRequired":false,"size":"small","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_tags","id":30,"label":"Post Tags","adminLabel":"","isRequired":false,"size":"large","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":""},{"type":"post_category","id":31,"label":"Post Category","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"choices":[],"displayAllCategories":true,"inputType":"select","formId":58,"pageNumber":1,"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"post_image","id":32,"label":"Post Image","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"allowedExtensions":"jpg, jpeg, png, gif","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"displayTitle":true,"displayCaption":true,"displayDescription":true},{"type":"post_custom_field","id":33,"label":"Post Custom Field","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"text","formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"postCustomFieldName":"Payer first name","customFieldTemplate":"","customFieldTemplateEnabled":false},{"type":"product","id":34,"label":"Product Basic","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":34.1,"label":"Name","name":""},{"id":34.2,"label":"Price","name":""},{"id":34.3,"label":"Quantity","name":""}],"inputType":"singleproduct","enablePrice":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$30.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"product","id":35,"label":"Product Name - Drop Down","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"select","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"DD - First Choice","value":"DD - First Choice","isSelected":false,"price":"$5.00"},{"text":"DD - Second Choice","value":"DD - Second Choice","isSelected":false,"price":"$10.00"},{"text":"DD - Third Choice","value":"DD - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":51,"label":"Product Name - Radio Buttons","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"radio","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":52,"label":"User Defined Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"price","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":53,"label":"Hidden Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":53.1,"label":"Name","name":""},{"id":53.2,"label":"Price","name":""},{"id":53.3,"label":"Quantity","name":""}],"inputType":"hiddenproduct","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$50.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"product","id":54,"label":"Calculation Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":[{"id":54.1,"label":"Name","name":""},{"id":54.2,"label":"Price","name":""},{"id":54.3,"label":"Quantity","name":""}],"inputType":"calculation","enablePrice":null,"formId":58,"pageNumber":1,"choices":[{"text":"Radio - First Choice","value":"Radio - First Choice","isSelected":false,"price":"$5.00"},{"text":"Radio - Second Choice","value":"Radio - Second Choice","isSelected":false,"price":"$10.00"},{"text":"Radio - Third Choice","value":"Radio - Third Choice","isSelected":false,"price":"$15.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"20 + 20","calculationRounding":"","enableCalculation":true,"basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"quantity","id":36,"label":"Quantity Field for Hidden Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"number","productField":"53","numberFormat":"decimal_dot","formId":58,"pageNumber":1,"choices":"","inputs":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"option","id":37,"label":"Product Options for Basic Product","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"select","choices":[{"text":"Option 1","value":"Option 1","isSelected":false,"price":"$20.00"},{"text":"Option 2","value":"Option 2","isSelected":false,"price":"$30.00"},{"text":"Option 3","value":"Option 3","isSelected":false,"price":"$40.00"}],"enablePrice":true,"productField":34,"formId":58,"pageNumber":1,"inputs":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false},{"type":"option","id":38,"label":"Option for Calculation Price","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputType":"checkbox","choices":[{"text":"Cal - Option 1","value":"Cal - Option 1","isSelected":false,"price":"$7.95"},{"text":"Cal - Option 2","value":"Cal - Option 2","isSelected":false,"price":"$12.10"},{"text":"Cal - Option 3","value":"Cal - Option 3","isSelected":false,"price":"$9.05"}],"enablePrice":true,"productField":"54","formId":58,"pageNumber":1,"inputs":[{"id":"38.1","label":"Cal - Option 1","name":""},{"id":"38.2","label":"Cal - Option 2","name":""},{"id":"38.3","label":"Cal - Option 3","name":""}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"shipping","id":39,"label":"Shipping","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"inputType":"select","enablePrice":true,"formId":58,"pageNumber":1,"choices":[{"text":"Express","value":"Express","isSelected":false,"price":"$20.00"},{"text":"Regular","value":"Regular","isSelected":false,"price":"$30.00"}],"conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","basePrice":"$0.00","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false,"defaultValue":"","description":""},{"type":"total","id":40,"label":"Total","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","inputs":null,"formId":58,"pageNumber":1,"choices":"","conditionalLogic":"","displayOnly":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"inputMask":false,"inputMaskValue":"","allowsPrepopulate":false}],"id":58,"subLabelPlacement":"below","cssClass":"","enableHoneypot":"","enableAnimation":"","save":{"enabled":"","button":{"type":"link","text":"Save and Continue Later"}},"limitEntries":"","limitEntriesCount":"","limitEntriesPeriod":"","limitEntriesMessage":"","scheduleForm":"","scheduleStart":"","scheduleStartHour":"","scheduleStartMinute":"","scheduleStartAmpm":"","scheduleEnd":"","scheduleEndHour":"","scheduleEndMinute":"","scheduleEndAmpm":"","schedulePendingMessage":"","scheduleMessage":"","requireLogin":"","requireLoginMessage":"","useCurrentUserAsAuthor":true,"postContentTemplateEnabled":false,"postTitleTemplateEnabled":false,"postTitleTemplate":"","postContentTemplate":"","lastPageButton":null,"pagination":null,"firstPageCssClass":null,"postAuthor":"3","postCategory":"26","postFormat":"0","postStatus":"draft","notifications":{"54bca349732b8":{"id":"54bca349732b8","to":"{admin_email}","name":"Admin Notification","event":"form_submission","toType":"email","subject":"New submission from {form_title}","message":"{all_fields}"}},"confirmations":{"54bca34973cdd":{"id":"54bca34973cdd","name":"Default Confirmation","isDefault":true,"type":"message","message":"Thanks for contacting us! We will get in touch with you shortly.","url":"","pageId":"","queryString":""}},"is_active":"1","date_created":"2015-01-19 06:25:13","is_trash":"0"}
EOT;

        $form = json_decode($json, true);

        $results = GFAPI::add_form($form);
        $this->form_id[1] = $results;
    }

    private function create_entries()
    {
        $entries = json_decode('[{"id":"453","form_id":"47","date_created":"2014-09-14 02:47:14","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"My","1.6":"Name","5":"First Choice","2.1":"","2.2":"","2.3":"","2.4":"","2.5":"","2.6":"","3":"","4":"","6":"","7":""},{"id":"452","form_id":"47","date_created":"2014-09-14 02:47:06","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"First","1.6":"Last","2.1":"12 Alister St","2.3":"Ali","2.4":"State","2.5":"2678","2.6":"Barbados","3":"my@test.com","4":"(345)445-4566","5":"Second Choice","6":"First Choice,Second Choice,Third Choice","2.2":"","7":""},{"id":"451","form_id":"47","date_created":"2014-09-14 02:46:35","is_starred":0,"is_read":0,"ip":"144.131.91.23","source_url":"http:\/\/clients.blueliquiddesigns.com.au\/gfpdf3\/gf1_7\/wordpress\/?gf_page=preview&id=47","post_id":null,"currency":"USD","payment_status":null,"payment_date":null,"transaction_id":null,"payment_amount":null,"payment_method":null,"is_fulfilled":null,"created_by":"1","transaction_type":null,"user_agent":"Mozilla\/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko\/20100101 Firefox\/32.0","status":"active","1.3":"Jake","1.6":"Jackson","2.1":"123 Fake St","2.2":"Line 2","2.3":"City","2.4":"State","2.5":"2441","2.6":"Albania","3":"test@test.com","4":"(123)123-1234","5":"Third Choice","6":"Second Choice,Third Choice","7":"This is paragraph test!"}]', true);

        $results = GFAPI::add_entries($entries, $this->form_id[0]);
        $this->entries = $results;
    }

    /**
     * [test_form_and_entry description]
     * @group gravityforms
     */
    public function test_form_and_entry()
    {
        /* test the form was correctly added to the database */
        foreach ($this->form_id as $results) {
            $this->assertInternalType('int', $results);
        }

        $this->assertEquals(true, is_array($this->entries));
    }

    /**
     * [test_core_classes description]
     * @group gravityforms
     */
    public function test_core_classes()
    {
        $this->assertTrue(true, class_exists('GFCommon'));
        $this->assertTrue(true, class_exists('GFFormsModel'));
        $this->assertTrue(true, class_exists('GFEntryDetail'));
        $this->assertTrue(true, class_exists('GFFormDisplay'));
    }

    /**
     * Check that GFAPI::get_form(); outputs correctly
     * @group gravityforms
     */
    public function test_get_forms()
    {
        $form = RGFormsModel::get_form_meta($this->form_id[0]);

        /*
         * Check the basics
         * Title is there, field number is correct
         */
        $this->assertEquals('Simple Form Testing', $form['title']);
        $this->assertEquals(true, is_array($form['fields']));
        $this->assertEquals(7, sizeof($form['fields']));
        $this->assertEquals(1, $form['is_active']);

        /*
         * Run through each field type and ensure the correct data is present
         */
        foreach ($form['fields'] as $field) {
            switch ($field['type']) {
                case 'name':
                    $this->assertEquals($field['inputs'][0]['id'], $field['id'].'.3');
                    $this->assertEquals($field['inputs'][1]['id'], $field['id'].'.6');
                break;

                case 'address':
                    $this->assertEquals($field['inputs'][0]['id'], $field['id'].'.1');
                    $this->assertEquals($field['inputs'][1]['id'], $field['id'].'.2');
                    $this->assertEquals($field['inputs'][2]['id'], $field['id'].'.3');
                    $this->assertEquals($field['inputs'][3]['id'], $field['id'].'.4');
                    $this->assertEquals($field['inputs'][4]['id'], $field['id'].'.5');
                    $this->assertEquals($field['inputs'][5]['id'], $field['id'].'.6');
                break;

                case 'email':
                    $this->assertEquals(3, $field['id']);
                break;

                case 'phone':
                    $this->assertEquals(4, $field['id']);
                    $this->assertEquals('standard', $field['phoneFormat']);
                break;

                case 'select':
                case 'multiselect':
                    $this->assertEquals(3, sizeof($field['choices']));
                break;

                case 'textarea':
                    $this->assertEquals(7, $field['id']);
                break;
            }
        }

        /*
         * Run through the notifications
         */
        $this->assertEquals(2, sizeof($form['notifications']));

        $form['notifications'] = array_values($form['notifications']);

        $this->assertEquals('Admin Notification', $form['notifications'][0]['name']);
        $this->assertEquals('User Notification', $form['notifications'][1]['name']);
    }

    /**
     * Test that GFAPI::get_entry() outputs correctly
     * @group gravityforms
     */
    public function test_get_entry()
    {
        $entry = RGFormsModel::get_lead($this->entries[0]);

        $valid_entries = array(
            'id', 'form_id', 'date_created', 'is_starred', 'is_read', 'ip', 'source_url', 'post_id', 'currency', 'payment_status', 'payment_date', 'transaction_id', 'payment_amount', 'payment_method', 'is_fulfilled', 'created_by', 'transaction_type', 'user_agent', 'status',
        );

        foreach ($valid_entries as $v) {
            $this->assertEquals(array_key_exists($v, $entry), true);
        }

        $this->assertEquals('My', $entry['1.3']);
        $this->assertEquals('Name', $entry['1.6']);
        $this->assertEquals('First Choice', $entry[5]);

        $entry = RGFormsModel::get_lead($this->entries[1]);

        $this->assertEquals('First', $entry['1.3']);
        $this->assertEquals('Last', $entry['1.6']);
        $this->assertEquals('12 Alister St', $entry['2.1']);
        $this->assertEquals('Ali', $entry['2.3']);
        $this->assertEquals('State', $entry['2.4']);
        $this->assertEquals('2678', $entry['2.5']);
        $this->assertEquals('Barbados', $entry['2.6']);
        $this->assertEquals('my@test.com', $entry['3']);
        $this->assertEquals('(345)445-4566', $entry['4']);
        $this->assertEquals('Second Choice', $entry['5']);
        $this->assertEquals('First Choice,Second Choice,Third Choice', $entry['6']);

        $entry = RGFormsModel::get_lead($this->entries[2]);

        $this->assertEquals('Jake', $entry['1.3']);
        $this->assertEquals('Jackson', $entry['1.6']);
        $this->assertEquals('123 Fake St', $entry['2.1']);
        $this->assertEquals('Line 2', $entry['2.2']);
        $this->assertEquals('City', $entry['2.3']);
        $this->assertEquals('State', $entry['2.4']);
        $this->assertEquals('2441', $entry['2.5']);
        $this->assertEquals('Albania', $entry['2.6']);
        $this->assertEquals('test@test.com', $entry['3']);
        $this->assertEquals('(123)123-1234', $entry['4']);
        $this->assertEquals('Third Choice', $entry['5']);
        $this->assertEquals('Second Choice,Third Choice', $entry['6']);
        $this->assertEquals('This is paragraph test!', $entry['7']);
    }

    /**
     * Test GF replace variables function (merge tags)
     * i.e GFCommon::replace_variables
     *
     * @group gravityforms
     * @dataProvider provider_mergetag_test
     */
    public function test_replace_variables($mergetag, $value)
    {
    	$this->assertEquals($value, PDF_Common::do_mergetags($mergetag, $this->form_id[0], $this->entries[2]));
    }

    /**
     * 
     */
    public function provider_mergetag_test()
    {
    	return array(
    		array('{:1.3}', 'Jake'),
    		array('{:1.6}', 'Jackson'),
    		array('{:5}', 'Third Choice'),
    		array('{:7}', 'This is paragraph test!'),
            array('{date_dmy}', date('d/m/Y')),
            array('{date_mdy}', date('m/d/Y')),
    		array('{form_title}', 'Simple Form Testing'),
    	);
    }

    /**
     * Test Gravity Form user privlages
     * i.e GFCommon::current_user_can_any("gravityforms_view_entries")
     *
     * @group gravityforms
     */
    public function test_gf_privs()
    {
        /* create user using WP Unit Factory functions */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);

        /*
         * Set up our users and test the privilages
         */
        wp_set_current_user($user_id);
        $this->assertFalse(GFCommon::current_user_can_any('gravityforms_view_entries'));

        /* Create second user we'll use to test out the privilage */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);

        /*
         * Add the user capability
         */
        $user = new WP_User($user_id);
        $user->add_cap('gravityforms_view_entries');

        wp_set_current_user($user_id);

        $this->assertTrue(GFCommon::current_user_can_any('gravityforms_view_entries'));

        /* Create third user we'll use to test out the privilage */
        $user_id = $this->factory->user->create();
        $this->assertInternalType('integer', $user_id);

        /*
         * Add the user capability
         */
        $user = new WP_User($user_id);
        $user->add_cap('gform_full_access');

        wp_set_current_user($user_id);

        $this->assertTrue(GFCommon::current_user_can_any('gravityforms_view_entries'));
    }

    /**
     * Test that the correct IP is returned by the function
     * @param String $ip  The test IP address
     * @param String $var The $_SERVER array key
     *
     * @group gravityforms
     * @dataProvider provider_ip_testing
     */
    public function run_ip_test($ip, $var)
    {
        $_SERVER[$var] = $ip;
        $this->assertEquals($ip, GFFormsModel::get_ip());
        unset($_SERVER[$var]);
    }

    /**
     * The data provider for the run_ip_test() function
     */
    public function provider_ip_testing()
    {
        return array(
            array('5.120.2.1', 'HTTP_CLIENT_IP'),
            array('6.10.3.9', 'HTTP_X_FORWARDED_FOR'),
            array('7.60.126.3', 'REMOTE_ADDR'),
            array('240.24.12.44,5.120.2.1', 'HTTP_CLIENT_IP'),
            array('10.17.54.234,6.10.3.9', 'HTTP_X_FORWARDED_FOR'),
            array('7.60.126.3,65.4.69.129', 'REMOTE_ADDR'),
        );
    }

    /**
     * Test that GFCommon::$version will produce
     * the expected result.
     *
     * @group gravityforms
     */
    public function test_gf_version()
    {
        $version = GFCommon::$version;

        /* which the version number is a string before we try to match it */
        $this->assertEquals(true, is_string($version));

        /* do a final test to match the version number according to a set standard */
        $this->assertRegExp('/^(?:(\d+)\.)?(?:(\d+)\.)?(\*|\d+)$/', $version);
    }
}
