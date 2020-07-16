import $ from 'jquery'

/**
 * Handles our DOM security conditional logic based on the user selection
 * @return void
 * @since 4.0
 */
export function handleSecurityConditionals () {
  /* Get the appropriate elements for use */
  const $secTable = $('#pdf-general-advanced')
  const $pdfSecurity = $secTable.find('input[name="gfpdf_settings[security]"]')
  const $format = $secTable.find('input[name="gfpdf_settings[format]"]')

  /* Add change event to admin restrictions to show/hide dependant fields */
  $pdfSecurity.change(function () {
    if ($(this).is(':checked')) {
      /* Get the format dependancy */
      const format = $format.filter(':checked').val()

      if ($(this).val() === GFPDF.no || format !== GFPDF.standard) {
        /* hide security password / privileges */
        $secTable.find('tr:nth-child(3),tr:nth-child(4),tr:nth-child(5):not(.gfpdf-hidden)').hide()
      } else {
        /* show security password / privileges */
        $secTable.find('tr:nth-child(3),tr:nth-child(4),tr:nth-child(5):not(.gfpdf-hidden)').show()
      }

      if (format !== GFPDF.standard) {
        $secTable.find('tr:nth-child(2)').hide()
      } else {
        $secTable.find('tr:nth-child(2)').show()
      }
    }
  }).trigger('change')

  /* The format field effects the security field. When it changes it triggers the security field as changed */
  $format.change(function () {
    if ($(this).is(':checked')) {
      $pdfSecurity.trigger('change')
    }
  }).trigger('change')
}
