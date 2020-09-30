import $ from 'jquery'
import { setupRequiredFields } from './setupRequiredFields'
import { handleSecurityConditionals } from './handleSecurityConditionals'
import { handlePDFConditionalLogic } from './handlePDFConditionalLogic'
import { handleOwnerRestriction } from './handleOwnerRestriction'
import { toggleFontAppearance } from './toggleFontAppearance'
import { toggleAppearanceTab } from './toggleAppearanceTab'

export function doFormSettingsEditPage () {
  setupRequiredFields($('#gfpdf_pdf_form'))

  /* highlight which fields are required and disable in-browser validation */
  handleSecurityConditionals()
  handlePDFConditionalLogic()
  handleOwnerRestriction()
  toggleFontAppearance($('#gfpdf_settings\\[template\\]').data('template_group'))
  toggleAppearanceTab()

  /* Add better merge tag support */
  $('.gform-settings-field').each(function () {
    $(this)
      .find('.merge-tag-support, .merge-tag-support + span')
      .wrapAll('<div class="gform-settings-input__container gform-settings-input__container--with-merge-tag"></div>')

    $(this)
      .find('.all-merge-tags.textarea')
      .parent()
      .wrapAll('<div class="gform-settings-input__container gform-settings-input__container--with-merge-tag gfpdf-merge-tag-container"></div>')
  })

  /* Hide Template section, if empty */
  const $templateSection = $('#gfpdf-fieldset-gfpdf_form_settings_template')
  if ($templateSection.find('.gform-settings-panel__content').children().length === 0) {
    $templateSection.hide()
  }

  /* Move alert inline */
  $('.gform-settings__wrapper > .alert').detach().prependTo('#tab_pdf')

  /*
   * Workaround for Firefix TinyMCE Editor Bug NS_ERROR_UNEXPECTED (http://www.tinymce.com/develop/bugtracker_view.php?id=3152) when loading wp_editor via AJAX
   * Manual save TinyMCE editors on form submission
   */
  $('#gfpdf_pdf_form').submit(function () {
    try {
      tinyMCE.triggerSave()
    } catch (e) {
      // empty
    }
  })

  /* Add listener on submit functionality */
  $('#gfpdf_pdf_form').submit(function () {
    /* JSONify the conditional logic so we can pass it through the form and use it in PHP (after running json_decode) */
    $('#gfpdf_settings\\[conditionalLogic\\]').val($.toJSON(window.gfpdf_current_pdf.conditionalLogic))
  })
}
