import $ from 'jquery'
import { ajaxCall } from '../../../helper/ajaxCall'

/**
 * Handles the state change of a PDF list item via AJAX
 * @return void
 * @since 4.0
 */
export function setupAJAXListStateListener () {
  /* Add live state listener to change active / inactive value */
  $('#gfpdf_list_form').on('click', '.check-column button', function () {
    const id = String($(this).data('id'))
    const button = $(this)
    const label = button.find('span.gform-status-indicator-status')

    if (id.length > 0) {
      button
        .addClass('gform_status--pending')
        .removeClass('gform-status--active gform-status--inactive')

      /* Set up ajax data */
      const data = {
        action: 'gfpdf_change_state',
        nonce: $(this).data('nonce'),
        fid: $(this).data('fid'),
        pid: $(this).data('id')
      }

      /* Do ajax call */
      ajaxCall(data, function (data) {
        label.html(data.state)

        if (button.data('status') === 'active') {
          button
            .data('status', 'inactive')
            .removeClass('gform_status--pending')
            .addClass('gform-status--inactive')
        } else {
          button
            .data('status', 'active')
            .removeClass('gform_status--pending')
            .addClass('gform-status--active')
        }
      })
    }
  })
}
