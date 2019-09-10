import $ from 'jquery'
import { ajaxCall } from '../../helper/ajaxCall'
import { spinner } from '../../helper/spinner'

/**
 * Handles individual add-on license key deactivation via AJAX
 * @since 4.2
 */
export function setupLicenseDeactivation () {
  $('.gfpdf-deactivate-license').click(function () {
    /* Do AJAX call so user can deactivate license */
    let $container = $(this).parent()
    $container.find('.gf_settings_description label').html('')

    /* Add spinner */
    let $spinner = spinner('gfpdf-spinner')

    /* Add our spinner */
    $(this).append($spinner)

    /* Set up ajax data */
    let slug = $(this).data('addon-name')

    let data = {
      'action': 'gfpdf_deactivate_license',
      'addon_name': slug,
      'license': $(this).data('license'),
      'nonce': $(this).data('nonce')
    }

    /* Do ajax call */
    ajaxCall(data, function (response) {
      /* Remove our loading spinner */
      $spinner.remove()

      if (response.success) {
        /* cleanup inputs */
        $('#gfpdf_settings\\[license_' + slug + '\\]').val('')
        $('#gfpdf_settings\\[license_' + slug + '_message\\]').val('')
        $('#gfpdf_settings\\[license_' + slug + '_status\\]').val('')
        $container.find('i').remove()
        $container.find('a').remove()

        $container.find('.gf_settings_description label').html(response.success)
      } else {
        /* Show error message */
        $container.find('.gf_settings_description label').html(response.error)
      }
    })

    return false
  })
}
