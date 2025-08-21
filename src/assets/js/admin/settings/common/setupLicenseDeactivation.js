import $ from 'jquery'
import { ajaxCall } from '../../helper/ajaxCall'
import { spinner } from '../../helper/spinner'

/**
 * Handles individual add-on license key deactivation via AJAX
 * @since 4.2
 */
export function setupLicenseDeactivation () {
  $('.gfpdf-deactivate-license').on('click', function () {
    /* Do AJAX call so user can deactivate license */
    const $container = $(this).parent()

    /* Add spinner */
    const $spinner = spinner('gfpdf-spinner')

    /* Add our spinner */
    $(this).append($spinner)

    /* Set up ajax data */
    const slug = $(this).data('addon-name')

    const data = {
      action: 'gfpdf_deactivate_license',
      addon_name: slug,
      nonce: $(this).data('nonce')
    }

    /* Do ajax call */
    ajaxCall(data, function (response) {
      /* Remove our loading spinner */
      $spinner.remove()

      /* update UI to reflect deactivation */
      postLicenseDeactivation(response.success ?? response.error, slug, $container)

      /* handle any shared licenses that were also deactivated */
      if (response.success && Array.isArray(response?.extra)) {
        response.extra.forEach(item => postLicenseDeactivation(response.success, item, $('#gfpdf-settings-field-wrapper-license_' + item)))
      }
    })

    return false
  })
}

function postLicenseDeactivation (status, slug, $container) {
  /* cleanup inputs */
  $('#gfpdf_settings\\[license_' + slug + '\\]').val('')
  $('#gfpdf_settings\\[license_' + slug + '_message\\]').val('')
  $('#gfpdf_settings\\[license_' + slug + '_status\\]').val('')
  $container.find('button').remove()

  if (status) {
    $container
      .find('#message')
      .removeClass('error')
      .addClass('success')
      .html(status)
  } else {
    /* Show error message */
    $container
      .find('#message')
      .removeClass('success')
      .addClass('error')
      .html(status)
  }
}
