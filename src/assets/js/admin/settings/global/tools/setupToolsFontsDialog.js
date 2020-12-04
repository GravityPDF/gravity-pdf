import $ from 'jquery'
import { wpDialog } from '../../../helper/wpDialog'
import { resizeDialogIfNeeded } from '../../../helper/resizeDialogIfNeeded'

/**
 * Handles the Fonts Dialog Box
 * @return void
 * @since 4.0
 */
export function setupToolsFontsDialog () {
  let $font = $('#gfpdf_settings\\[manage_fonts\\]')
  /* escape braces */
  let $fontDialog = $('#manage-font-files')

  /* setup fonts dialog */
  wpDialog($fontDialog, [], 500, 500)

  $font.on('click', function () {
    /* Allow responsiveness */
    resizeDialogIfNeeded($fontDialog, 500, 500)

    $fontDialog.wpdialog('open')
    return false
  })

  /* Check if our manage_fonts hash and open the dialog */
  if (window.location.hash === '#manage_fonts') {
    $font.trigger('click')
  }
}
