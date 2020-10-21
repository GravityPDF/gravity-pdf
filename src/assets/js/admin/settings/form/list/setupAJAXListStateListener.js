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
    const id = String($(this).data('id'))
    const that = this

    if (id.length > 0) {
      const isActive = that.src.indexOf('active1.svg') >= 0

      if (isActive) {
        that.src = that.src.replace('active1.svg', 'active0.svg')
        $(that).attr('title', GFPDF.inactive).attr('alt', GFPDF.inactive).attr('aria-label', GFPDF.inactive).attr('role','img')
      } else {
        that.src = that.src.replace('active0.svg', 'active1.svg')
        $(that).attr('title', GFPDF.active).attr('alt', GFPDF.active).attr('aria-label', GFPDF.active).attr('role','img')
      }

      /* Set up ajax data */
      const data = {
        action: 'gfpdf_change_state',
        nonce: $(this).data('nonce'),
        fid: $(this).data('fid'),
        pid: $(this).data('id')
      }

      /* Do ajax call */
      ajaxCall(data, function () {
        /* Don't do anything with a successful response */
      })
    }
  })
}
