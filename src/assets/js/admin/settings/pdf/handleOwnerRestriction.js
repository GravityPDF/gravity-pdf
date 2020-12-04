import $ from 'jquery'

/**
 * Show / Hide the Restrict Owner when `Enable Public Access` is set to "Yes"
 * @since 4.0
 */
export function handleOwnerRestriction () {
  const $table = $('#gfpdf-fieldset-gfpdf_form_settings_advanced')
  const $publicAccess = $table.find('input[name="gfpdf_settings[public_access]"]')
  const $restrictOwner = $('#gfpdf-settings-field-wrapper-restrict_owner')

  /*
   * Add change event to admin restrictions to show/hide dependant fields
   */
  $publicAccess.on('change', function () {
    if ($(this).is(':checked')) {
      $restrictOwner.hide()
    } else {
      $restrictOwner.show()
    }
  }).trigger('change')
}
