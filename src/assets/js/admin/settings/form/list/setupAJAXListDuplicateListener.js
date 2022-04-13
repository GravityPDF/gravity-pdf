import $ from 'jquery'
import { updateURLParameter } from '../../common/updateURLParameter'
import { ajaxCall } from '../../../helper/ajaxCall'
import { spinner } from '../../../helper/spinner'
import { showMessage } from '../../../helper/showMessage'

/**
 * Handles the duplicate of a PDF list item via AJAX and fixes up all the nonce actions
 * @return void
 * @since 4.0
 */
export function setupAJAXListDuplicateListener () {
  /* Add live duplicate listener */
  $('#gfpdf_list_form').on('click', 'a.submitduplicate', function (e) {
    e.preventDefault()

    const id = String($(this).data('id'))
    const that = this

    /* Add our spinner */
    $(this).after(spinner('gfpdf-spinner gfpdf-spinner-small')).parent().parent().attr('style', 'position:static; visibility: visible;')

    if (id.length > 0) {
      /* Set up ajax data */
      const data = {
        action: 'gfpdf_list_duplicate',
        nonce: $(this).data('nonce'),
        fid: $(this).data('fid'),
        pid: $(this).data('id')
      }

      /* Do ajax call */
      ajaxCall(data, function (response) {
        if (response.msg) {
          /* Remove the spinner */
          $(that).parent().parent().attr('style', '').find('.gfpdf-spinner').remove()

          /* Provide feedback to use */
          showMessage(response.msg)

          /* Clone the row to be duplicated */
          const $row = $(that).parents('tr')
          const $newRow = $row.clone()

          /* Update the edit links to point to the new location */
          $newRow.find('.column-name > a, .edit a').each(function () {
            let href = $(this).attr('href')
            href = updateURLParameter(href, 'pid', response.pid)
            $(this).attr('href', href)
          })

          /* Update the name field */
          $newRow.find('.column-name > a').html(response.name)

          /* Find duplicate and delete elements */
          const $duplicate = $newRow.find('.duplicate a')
          const $delete = $newRow.find('.delete a')
          const $state = $newRow.find('.check-column button')
          const $shortcode = $newRow.find('.column-shortcode')

          /* Update duplicate ID and nonce pointers so the actions are valid */
          $duplicate.data('id', response.pid)
          $duplicate.data('nonce', response.dup_nonce)

          /* Update delete ID and nonce pointers so the actions are valid */
          $delete.data('id', response.pid)
          $delete.data('nonce', response.del_nonce)

          /* update state ID and nonce pointers so the actions are valid */
          $state.data('id', response.pid)
          $state.data('nonce', response.state_nonce)

          /* Set button data-status to inactive by default */
          $state[0].setAttribute('data-status', 'inactive')

          /* Update our shortcode ID */
          let shortcodeValue = $shortcode.find('button').attr('data-clipboard-text')
          shortcodeValue = shortcodeValue.replace(id, response.pid)
          $shortcode.find('button').attr('data-clipboard-text', shortcodeValue)
          $shortcode.find('input')
            .attr('id', response.pid)
            .attr('value', shortcodeValue)

          $state.removeClass('gform-status--active')
            .addClass('gform-status--inactive')
            .find('.gform-status-indicator-status')
            .html(response.status)

          /* Add row to node and fade in */
          $newRow.hide().insertAfter($row).fadeIn()
        }
      })
    }
  })
}
