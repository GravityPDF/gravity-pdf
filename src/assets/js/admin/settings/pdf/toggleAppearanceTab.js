import $ from 'jquery'

/**
 * Check if the current PDF template selection uses the legacy Enable Advanced Templating option
 * and hide the Appearance tab altogether
 * @since 4.0
 */
export function toggleAppearanceTab () {
  const $appearanceSection = $('#gfpdf-fieldset-gfpdf_form_settings_appearance')
  $('input[name="gfpdf_settings[advanced_template]"]').change(function () {
    if ($(this).val() === 'Yes') {
      $appearanceSection.hide()
    } else {
      $appearanceSection.show()
    }
  })

  $('input[name="gfpdf_settings[advanced_template]"]:checked').trigger('change')
}
