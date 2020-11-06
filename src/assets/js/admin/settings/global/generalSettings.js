import $ from 'jquery'
import { setupRequiredFields } from '../pdf/setupRequiredFields'

/**
 * The general settings model method
 * This sets up and processes any of the JS that needs to be applied on the general settings tab
 * @return void
 * @since 4.0
 */
export function generalSettings () {
  setupRequiredFields($('#pdfextended-settings > form'))

  const $table = $('#pdf-general-security')
  const $adminRestrictions = $table.find('input[name="gfpdf_settings[default_restrict_owner]"]')

  /*
   * Add change event to admin restrictions to show/hide dependant fields
   */
  $adminRestrictions.on('change', function () {
    if ($(this).is(':checked')) {
      if ($(this).val() === 'Yes') {
        /* hide user restrictions and logged out user timeout */
        $table.find('tr:nth-child(3)').hide()
      } else {
        /* hide user restrictions and logged out user timeout */
        $table.find('tr:nth-child(3)').show()
      }
    }
  }).trigger('change')

}
