import $ from 'jquery'

/**
 * Check the current browser width and height and set the dialog box size to fit
 * If the size is over 500 pixels (width or height) it will default to 500
 *
 * @param $dialog an object initialised with wpDialog()
 * @param Integer maxWidth The maximum width of the dialog box, if it will fit
 * @param Integer maxHeight The maximum height of the dialog box, if it will fit
 * @return void
 * @since 4.0
 */
export function resizeDialogIfNeeded ($dialog, maxWidth, maxHeight) {
  let windowWidth = $(window).width()
  let windowHeight = $(window).height()

  let dialogWidth = (windowWidth < 500) ? windowWidth - 20 : maxWidth
  let dialogHeight = (windowHeight < 500) ? windowHeight - 50 : maxHeight

  $dialog.wpdialog('option', 'width', dialogWidth)
  $dialog.wpdialog('option', 'height', dialogHeight)
}
