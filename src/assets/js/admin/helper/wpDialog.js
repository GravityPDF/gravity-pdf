import $ from 'jquery'

/**
 * Generate a WP Dialog box
 * @param  jQuery Object $elm        [description]
 * @param  Object buttonsList [description]
 * @param  Integer boxWidth    [description]
 * @param  Integer boxHeight   [description]
 * @return void
 * @since 4.0
 */
export function wpDialog ($elm, buttonsList, boxWidth, boxHeight) {
  $elm.wpdialog({
    autoOpen: false,
    resizable: false,
    draggable: false,
    width: boxWidth,
    height: boxHeight,
    modal: true,
    dialogClass: 'wp-dialog',
    zIndex: 300000,
    buttons: buttonsList,
    open: function () {
      $(this).siblings('.ui-dialog-buttonpane').find('button:eq(1)').trigger('focus')

      $('.ui-widget-overlay').on('click', function () {
        $elm.wpdialog('close')
      })
    }
  })
}
