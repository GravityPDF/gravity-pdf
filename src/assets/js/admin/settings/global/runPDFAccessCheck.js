import $ from 'jquery'
import { ajaxCall } from '../../helper/ajaxCall'
import { spinner } from '../../helper/spinner'

export function runPDFAccessCheck () {
  let $status = $('#gfpdf-direct-pdf-protection-check')

  if ($status.length > 0) {
    /* Do our AJAX call */

    /* Add spinner */
    let $spinner = spinner('gfpdf-spinner')

    /* Add our spinner */
    $status.append($spinner)

    /* Set up ajax data */
    let data = {
      'action': 'gfpdf_has_pdf_protection',
      'nonce': $status.data('nonce'),
    }

    /* Do ajax call */
    ajaxCall(data, function (response) {
      /* Remove our loading spinner */
      $spinner.remove()

      if (response === true) {
        /* enable our protected message */
        $status.find('#gfpdf-direct-pdf-check-protected').show()
      } else {
        /* enable our unprotected message */
        $status.find('#gfpdf-direct-pdf-check-unprotected').show()
      }
    })
  }
}
