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
  $('#gfpdf_list_form').on('click', 'a.submitduplicate', function () {
    let id = String($(this).data('id'))
    let that = this

    /* Add our spinner */
    $(this).after(spinner('gfpdf-spinner gfpdf-spinner-small')).parent().parent().attr('style', 'position:static; visibility: visible;')

    if (id.length > 0) {
      /* Set up ajax data */
      let data = {
        'action': 'gfpdf_list_duplicate',
        'nonce': $(this).data('nonce'),
        'fid': $(this).data('fid'),
        'pid': $(this).data('id')
      }

      /* Do ajax call */
      ajaxCall(data, function (response) {
        if (response.msg) {
          /* Remove the spinner */
          $(that).parent().parent().attr('style', '').find('.gfpdf-spinner').remove()

          /* Provide feedback to use */
          showMessage(response.msg)

          /* Clone the row to be duplicated */
          let $row = $(that).parents('tr')
          let $newRow = $row.clone().css('background', '#baffb8')

          /* Update the edit links to point to the new location */
          $newRow.find('.column-name > a, .edit a').each(function () {
            let href = $(this).attr('href')
            href = updateURLParameter(href, 'pid', response.pid)
            $(this).attr('href', href)
          })

          /* Update the name field */
          $newRow.find('.column-name > a').html(response.name)

          /* Find duplicate and delete elements */
          let $duplicate = $newRow.find('.duplicate a')
          let $delete = $newRow.find('.delete a')
          let $state = $newRow.find('.check-column img')
          let $shortcode = $newRow.find('.column-shortcode input')

          /* Update duplicate ID and nonce pointers so the actions are valid */
          $duplicate.data('id', response.pid)
          $duplicate.data('nonce', response.dup_nonce)

          /* Update delete ID and nonce pointers so the actions are valid */
          $delete.data('id', response.pid)
          $delete.data('nonce', response.del_nonce)

          /* update state ID and nonce pointers so the actions are valid */
          $state.data('id', response.pid)
          $state.data('nonce', response.state_nonce)

          /* Update our shortcode ID */
          let shortcodeValue = $shortcode.val()
          shortcodeValue = shortcodeValue.replace(id, response.pid)
          $shortcode.val(shortcodeValue)

          /* Add fix for alternate row background */
          let background = ''
          if ($row.hasClass('alternate')) {
            $newRow.removeClass('alternate')
            background = '#FFF'
          } else {
            $newRow.addClass('alternate')
            background = '#f9f9f9'
          }

          /* Add fix for toggle image */
          let toggleSrc = $state.attr('src')
          $state
            .attr('title', GFPDF.inactive)
            .attr('alt', GFPDF.inactive)
            .attr('src', toggleSrc.replace('active1.png', 'active0.png'))

          /* Add row to node and fade in */
          $newRow.hide().insertAfter($row).fadeIn().animate({ backgroundColor: background })
        }
      })
    }
  })
}
