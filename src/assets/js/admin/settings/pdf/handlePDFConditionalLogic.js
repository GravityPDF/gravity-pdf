import $ from 'jquery'

/**
 * Add GF JS filter to change the conditional logic object type to our PDF
 * @return Object
 * @since 4.0
 */
export function handlePDFConditionalLogic () {
  gform.addFilter('gform_conditional_object', function (obj, objectType) {
    if (objectType === 'gfpdf') {
      obj = window.gfpdf_current_pdf

      /* Manually setup new conditional logic object, with fallback to entry metadata if no available fields present */
      if (!obj.conditionalLogic || obj.conditionalLogic.length === 0) {
        obj.conditionalLogic = new ConditionalLogic()
        obj.conditionalLogic.rules[0].fieldId = GetFirstRuleField()
        if (obj.conditionalLogic.rules[0].fieldId === 0) {
          obj.conditionalLogic.rules[0].fieldId = 'id'
        }
      }
    }
    return obj
  })

  /* Add support for entry meta */
  const entryOptions = window.gfpdf_extra_conditional_logic_options
  gform.addFilter('gform_conditional_logic_fields', function (options, form, selectedFieldId) {
    for (const property in entryOptions) {
      // Entry meta are already added in Notifications and Confirmations conditional logic but not in feeds.
      // Let's just make sure that none of our entry meta options have been previously added.
      if (Object.hasOwn(entryOptions, property) && !options.find(opt => opt.value === entryOptions[property].value)) {
        options.push({
          label: entryOptions[property].label,
          value: entryOptions[property].value
        })
      }
    }
    return options
  })

  gform.addFilter('gform_conditional_logic_operators', function (operators, objectType, fieldId) {
    if (Object.hasOwn(entryOptions, fieldId)) {
      operators = entryOptions[fieldId].operators
    }
    return operators
  })

  gform.addFilter('gform_conditional_logic_values_input', function (str, objectType, ruleIndex, selectedFieldId, selectedValue) {
    if (Object.hasOwn(entryOptions, selectedFieldId)) {
      if (entryOptions[selectedFieldId].choices) {
        const inputName = objectType + '_rule_value_' + ruleIndex
        str = GetRuleValuesDropDown(entryOptions[selectedFieldId].choices, objectType, ruleIndex, selectedValue, inputName)
      }

      if (entryOptions[selectedFieldId].placeholder) {
        str = $(str).attr('placeholder', entryOptions[selectedFieldId].placeholder)[0].outerHTML
      }
    }

    return str
  })

  /* Add change event to conditional logic field */
  $('#gfpdf_conditional_logic').on('change', function () {
    /* Only set up a .conditionalLogic object if it doesn't exist */
    if (typeof window.gfpdf_current_pdf.conditionalLogic === 'undefined' && $(this).prop('checked')) {
      window.gfpdf_current_pdf.conditionalLogic = new ConditionalLogic()
    } else if (!$(this).prop('checked')) {
      window.gfpdf_current_pdf.conditionalLogic = null
    }
    ToggleConditionalLogic(false, 'gfpdf')
  }).trigger('change')
}
