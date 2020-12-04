import $ from 'jquery'

/**
 * Add GF JS filter to change the conditional logic object type to our PDF
 * @return Object
 * @since 4.0
 */
export function handlePDFConditionalLogic () {
  gform.addFilter('gform_conditional_object', function (object, objectType) {
    if (objectType === 'gfpdf') {
      return window.gfpdf_current_pdf
    }
    return object
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
