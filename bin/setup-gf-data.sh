#!/bin/bash

# Include useful environmentals / functions
. "$(dirname "$0")/env.sh"
. "$(dirname "$0")/functions.sh"

# Pre-loading Gravity Forms data
echo -e $(status_message "Pre-load Gravity Forms data")

for i in {1..5}
do
  FID=$(docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI gf form create "Sample $i" --form-json='{"fields":[{"type":"text","id":1,"label":"Text","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","visibility":"visible","inputs":null,"formId":81,"description":"","allowsPrepopulate":false,"inputMask":false,"inputMaskValue":"","inputMaskIsCustom":false,"maxLength":"","inputType":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","cssClass":"","inputName":"","noDuplicates":false,"defaultValue":"","choices":"","conditionalLogic":"","productField":"","enablePasswordInput":"","multipleFiles":false,"maxFiles":"","calculationFormula":"","calculationRounding":"","enableCalculation":"","disableQuantity":false,"displayAllCategories":false,"useRichTextEditor":false,"fields":""},{"type":"name","id":2,"label":"Name","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","visibility":"visible","nameFormat":"advanced","inputs":[{"id":"2.2","label":"Prefix","name":"","choices":[{"text":"Mr.","value":"Mr.","isSelected":false,"price":""},{"text":"Mrs.","value":"Mrs.","isSelected":false,"price":""},{"text":"Miss","value":"Miss","isSelected":false,"price":""},{"text":"Ms.","value":"Ms.","isSelected":false,"price":""},{"text":"Dr.","value":"Dr.","isSelected":false,"price":""},{"text":"Prof.","value":"Prof.","isSelected":false,"price":""},{"text":"Rev.","value":"Rev.","isSelected":false,"price":""}],"isHidden":true,"inputType":"radio"},{"id":"2.3","label":"First","name":""},{"id":"2.4","label":"Middle","name":"","isHidden":true},{"id":"2.6","label":"Last","name":""},{"id":"2.8","label":"Suffix","name":"","isHidden":true}],"formId":81,"description":"","allowsPrepopulate":false,"inputMask":false,"inputMaskValue":"","inputMaskIsCustom":"","maxLength":"","inputType":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","cssClass":"","inputName":"","noDuplicates":false,"defaultValue":"","choices":"","conditionalLogic":"","productField":"","fields":""},{"type":"email","id":3,"label":"Email","adminLabel":"","isRequired":false,"size":"medium","errorMessage":"","visibility":"visible","inputs":null,"formId":81,"description":"","allowsPrepopulate":false,"inputMask":false,"inputMaskValue":"","inputMaskIsCustom":"","maxLength":"","inputType":"","labelPlacement":"","descriptionPlacement":"","subLabelPlacement":"","placeholder":"","cssClass":"","inputName":"","noDuplicates":false,"defaultValue":"","choices":"","conditionalLogic":"","productField":"","emailConfirmEnabled":"","fields":""}]}' --porcelain)
  docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI gf entry create $FID --field_1=value --field_2.3=First --field_2.6=Last --field_3="name@example.com" --quiet

  # Add PDF Configs
  if [[ $i -eq 3  ||  $i -eq 4 ]]; then
    docker-compose $DOCKER_COMPOSE_FILE_OPTIONS run --rm -u 33 $CLI eval "GPDFAPI::add_pdf(${FID}, [ 'name' => 'Sample', 'template' => 'zadani', 'filename' => 'Sample', 'font' => 'dejavusans', 'format' => 'standard', 'security' => 'no' ]);"
  fi
done