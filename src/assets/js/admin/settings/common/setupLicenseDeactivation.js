import $ from 'jquery'
import { ajaxCall } from '../../helper/ajaxCall'
import { spinner } from '../../helper/spinner'

/**
 * Handles individual add-on license key deactivation via AJAX
 * @since 4.2
 */
export function setupLicenseDeactivation () {
  $('.gfpdf-deactivate-license').on('click', function () {
    const $button = $(this)

    /* Ignore repeat clicks while a deactivation request is already in flight */
    if ($button.prop('disabled')) {
      return false
    }
    $button.prop('disabled', true)

    /* Do AJAX call so user can deactivate license */
    const $container = $button.parent()

    /* Add spinner */
    const $spinner = spinner('gfpdf-spinner')

    /* Add our spinner */
    $button.append($spinner)

    /* Set up ajax data */
    const slug = $button.data('addon-name')

    const data = {
      action: 'gfpdf_deactivate_license',
      addon_name: slug,
      nonce: $button.data('nonce')
    }

    /* Do ajax call */
    ajaxCall(data, function (response) {
      /* Remove our loading spinner */
      $spinner.remove()

      /* Our endpoint always returns a `success` or `error` string. A transport/auth failure (eg. a 500, or the 401
         from handle_ajax_authentication) instead hits jQuery's error handler with the raw jqXHR, so neither is set. */
      const successMessage = typeof response?.success === 'string' ? response.success : null
      if (successMessage === null) {
        /* Deactivation didn't complete — re-enable the button and surface the error so the user can retry */
        $button.prop('disabled', false)
        const error = typeof response?.error === 'string' ? response.error : GFPDF.licenseDeactivationError
        showLicenseMessage($container, error, false)
        return
      }

      /* update UI to reflect deactivation */
      postLicenseDeactivation(slug, $container, successMessage)

      /* handle any shared licenses that were also deactivated */
      if (Array.isArray(response.extra)) {
        response.extra.forEach(item => postLicenseDeactivation(item, $('#gfpdf-settings-field-wrapper-license_' + item), successMessage))
      }
    })

    return false
  })
}

function postLicenseDeactivation (slug, $container, message) {
  /* cleanup inputs */
  $('#gfpdf_settings\\[license_' + slug + '\\]').val('')
  $('#gfpdf_settings\\[license_' + slug + '_message\\]').val('')
  $('#gfpdf_settings\\[license_' + slug + '_status\\]').val('')
  $container.find('button').remove()

  showLicenseMessage($container, message, true)
}

function showLicenseMessage ($container, message, success) {
  $container
    .find('#message')
    .toggleClass('success', success)
    .toggleClass('error', !success)
    .html(message)
}
