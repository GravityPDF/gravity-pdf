import $ from 'jquery'
import { setupRequiredFields } from './setupRequiredFields'
import { setupPdfTabs } from './setupPdfTabs'
import { handleSecurityConditionals } from './handleSecurityConditionals'
import { handlePDFConditionalLogic } from './handlePDFConditionalLogic'
import { handleOwnerRestriction } from './handleOwnerRestriction'
import { toggleFontAppearance } from './toggleFontAppearance'
import { toggleAppearanceTab } from './toggleAppearanceTab'

export function doFormSettingsEditPage () {
  setupRequiredFields($('#gfpdf_pdf_form'))

  /* highlight which fields are required and disable in-browser validation */
  setupPdfTabs()
  handleSecurityConditionals()
  handlePDFConditionalLogic()
  handleOwnerRestriction()
  toggleFontAppearance($('#gfpdf_settings\\[template\\]').data('template_group'))
  toggleAppearanceTab()

  /*
   * Workaround for Firefix TinyMCE Editor Bug NS_ERROR_UNEXPECTED (http://www.tinymce.com/develop/bugtracker_view.php?id=3152) when loading wp_editor via AJAX
   * Manual save TinyMCE editors on form submission
   */
  $('#gfpdf_pdf_form').submit(function () {
    try {
      tinyMCE.triggerSave()
    } catch (e) {}
  })

  /* Add listener on submit functionality */
  $('#gfpdf_pdf_form').submit(function () {
    /* JSONify the conditional logic so we can pass it through the form and use it in PHP (after running json_decode) */
    $('#gfpdf_settings\\[conditionalLogic\\]').val($.toJSON(window.gfpdf_current_pdf.conditionalLogic))
  })
}
