import $ from 'jquery'
import { ajaxCall } from '../../helper/ajaxCall'
import { spinner } from '../../helper/spinner'
import { loadTinyMCEEditor } from './dynamicTemplateFields/loadTinyMCEEditor'
import { initialiseCommonElements } from './initialiseCommonElements'
import { doMergetags } from './dynamicTemplateFields/doMergetags'
import { toggleFontAppearance } from '../pdf/toggleFontAppearance'

/**
 * PDF Templates can assign their own custom settings which can enhance a template
 * This function setups the required listeners and functionality to allow this behaviour
 * @return return
 * @since 4.0
 */
export function setupDynamicTemplateFields () {
  /* Add change listener to our template */
  $('#gfpdf_settings\\[template\\]').off('change').change(function () {
    /* Add spinner */
    const $spinner = spinner('gfpdf-spinner')

    $(this).next().after($spinner)

    const data = {
      action: 'gfpdf_get_template_fields',
      nonce: GFPDF.ajaxNonce,
      template: $(this).val(),
      type: $(this).attr('id'),
      id: $('#gform_id').val(),
      gform_pdf_id: $('#gform_pdf_id').val()
    }

    ajaxCall(data, function (response) {
      /* Remove our UI loader */
      $spinner.remove()

      /* Reset our legacy Advanced Template option */
      $('input[name="gfpdf_settings[advanced_template]"][value="No"]').prop('checked', true).trigger('change')

      /* Only process if the response is valid */
      if (response.fields) {
        /* Remove any previously loaded editors to prevent conflicts loading an editor with same name */
        $.each(response.editors, function (index, value) {
          const editor = tinyMCE.get(value)
          if (editor !== null) {
            /* Bug Fix for Firefox - http://www.tinymce.com/develop/bugtracker_view.php?id=3152 */
            try {
              tinyMCE.remove(editor)
            } catch (e) {
              // empty
            }
          }
        })

        /* Replace the custom appearance with the AJAX response fields */
        $('#gfpdf-fieldset-gfpdf_form_settings_template')
          .show()
          .find('.gform-settings-panel__content')
          .html(response.fields)

        /* Load our new editors */
        loadTinyMCEEditor(response.editors, response.editor_init)

        /* reinitialise new dom elements */
        initialiseCommonElements.runElements()
        doMergetags()
        gform_initialize_tooltips()
      } else {
        /* Hide our template nav item as there are no fields and clear our the HTML */
        $('#gfpdf-fieldset-gfpdf_form_settings_template')
          .hide()
          .find('.gform-settings-panel__content')
          .html('')
      }

      /* Check if we should hide or show our font fields */
      if (response.template_type) {
        toggleFontAppearance(response.template_type)
      }

      $(document).trigger('gfpdf_template_loaded', [response])
    })
  })
}
