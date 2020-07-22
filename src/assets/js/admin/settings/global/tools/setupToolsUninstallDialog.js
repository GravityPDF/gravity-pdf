import $ from 'jquery'

/**
 * Handles the Uninstall Dialog Box
 * @return void
 * @since 4.0
 */
export function setupToolsUninstallDialog () {
  const $uninstall = $('#gfpdf_settings\\[uninstaller\\]')

  $uninstall.click(function () {
    if (window.confirm(GFPDF.uninstallWarning)) {
      $uninstall.parents('form').submit()
    }

    return false
  })
}
