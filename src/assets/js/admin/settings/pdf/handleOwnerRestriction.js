import $ from 'jquery'

/**
 * Show / Hide the Restrict Owner when `Enable Public Access` is set to "Yes"
 * @since 4.0
 */
export function handleOwnerRestriction () {
  const $table = $('#gfpdf-advanced-pdf-options')
  const $publicAccess = $table.find('input[name="gfpdf_settings[public_access]"]')

  /*
   * Add change event to admin restrictions to show/hide dependant fields
   */
  $publicAccess.change(function () {
    if ($(this).is(':checked')) {
      if ($(this).val() === 'Yes') {
        /* hide user restrictions  */
        $table.find('tr:nth-child(9)').hide()
      } else {
        /* show user restrictions */
        $table.find('tr:nth-child(9)').show()
      }
    }
  }).trigger('change')
}
