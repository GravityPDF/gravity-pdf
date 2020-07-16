import $ from 'jquery'
import { wpDialog } from '../../../helper/wpDialog'
import { resizeDialogIfNeeded } from '../../../helper/resizeDialogIfNeeded'

/**
 * Handles the Template Installer Dialog Box
 * @return void
 * @since 4.0
 */
export function setupToolsTemplateInstallerDialog () {
  const $copy = $('#gfpdf_settings\\[setup_templates\\]')
  /* escape braces */
  const $copyDialog = $('#setup-templates-confirm')

  /* Set up copy dialog */
  const copyButtons = [{
    text: GFPDF.continue,
    click: function () {
      /* submit form */
      $copy.unbind().click()
    }
  }, {
    text: GFPDF.cancel,
    click: function () {
      /* cancel */
      $copyDialog.wpdialog('close')
    }
  }]

  if ($copyDialog.length) {
    wpDialog($copyDialog, copyButtons, 500, 350)

    $copy.click(function () {
      /* Allow responsiveness */
      resizeDialogIfNeeded($copyDialog, 500, 350)

      $copyDialog.wpdialog('open')
      return false
    })
  }
}
