import $ from 'jquery'
import { ajaxCall } from '../../../helper/ajaxCall'

/**
 * Handles the state change of a PDF list item via AJAX
 * @return void
 * @since 4.0
 */
export function setupAJAXListStateListener () {
  /* Add live state listener to change active / inactive value */
  $('#gfpdf_list_form').on('click', '.check-column img', function () {
    let id = String($(this).data('id'))
    let that = this

    if (id.length > 0) {
      let isActive = that.src.indexOf('active1.png') >= 0

      if (isActive) {
        that.src = that.src.replace('active1.png', 'active0.png')
        $(that).attr('title', GFPDF.inactive).attr('alt', GFPDF.inactive)
      } else {
        that.src = that.src.replace('active0.png', 'active1.png')
        $(that).attr('title', GFPDF.active).attr('alt', GFPDF.active)
      }

      /* Set up ajax data */
      let data = {
        'action': 'gfpdf_change_state',
        'nonce': $(this).data('nonce'),
        'fid': $(this).data('fid'),
        'pid': $(this).data('id')
      }

      /* Do ajax call */
      ajaxCall(data, function (response) {
        /* Don't do anything with a successful response */
      })
    }
  })
}
