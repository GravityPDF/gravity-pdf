import $ from 'jquery'
import { ajaxCall } from '../../../helper/ajaxCall'
import { spinner } from '../../../helper/spinner'
import { showMessage } from '../../../helper/showMessage'
import { wpDialog } from '../../../helper/wpDialog'
import { resizeDialogIfNeeded } from '../../../helper/resizeDialogIfNeeded'

/**
 * Handles the deletion of a PDF list item via AJAX
 * @return void
 * @since 4.0
 */
export function setupAJAXListDeleteListener () {
  /**
   * Check if the last item was just deleted
   */
  function maybeShowEmptyRow () {
    const $container = $('#gfpdf_list_form tbody')

    if ($container.find('tr').length === 0) {
      const $row = $('<tr>').addClass('no-items')
      const $cell = $('<td>').attr('colspan', '5').addClass('colspanchange')
      const $addNew = $('<a>').attr('href', $('#add-new-pdf').attr('href')).append(GFPDF.letsGoCreateOne + '.')
      $cell.append(GFPDF.thisFormHasNoPdfs).append(' ').append($addNew)
      $row.append($cell)
      $container.append($row)
    }
  }

  /* Set up our delete dialog */
  const $deleteDialog = $('#delete-confirm')

  const deleteButtons = [{
    text: GFPDF.delete,
    click: function () {
      /* handle ajax call */
      $deleteDialog.wpdialog('close')
      const $elm = $($deleteDialog.data('elm'))

      /* Add the spinner */
      $elm.append(spinner('gfpdf-spinner gfpdf-spinner-small')).parent().parent().attr('style', 'position:static; visibility: visible;')

      const data = {
        action: 'gfpdf_list_delete',
        nonce: $elm.data('nonce'),
        fid: $elm.data('fid'),
        pid: $elm.data('id')
      }

      ajaxCall(data, function (response) {
        if (response.msg) {
          /* Remove spinner */
          $elm.parent().parent().attr('style', '').find('.gfpdf-spinner').remove()

          showMessage(response.msg)
          const $row = $elm.parents('tr')
          $row.css('background', '#ffb8b8').fadeOut(400, function () {
            this.remove()
            maybeShowEmptyRow()
          })
        }
        $deleteDialog.data('elm', null)
      })
    }
  }, {
    text: GFPDF.cancel,
    click: function () {
      /* cancel */
      $deleteDialog.wpdialog('close').data('elm', null)
    }
  }]

  /* Add our delete dialog box */
  wpDialog($deleteDialog, deleteButtons, 300, 175)

  /* Add live delete listener */
  $('#gfpdf_list_form').on('click', 'a.submitdelete', function () {
    const id = String($(this).data('id'))
    if (id.length > 0 && !$deleteDialog.data('elm')) {
      /* Allow responsiveness */
      resizeDialogIfNeeded($deleteDialog, 300, 175)

      $deleteDialog.wpdialog('open').data('elm', this)
    }
  })
}
