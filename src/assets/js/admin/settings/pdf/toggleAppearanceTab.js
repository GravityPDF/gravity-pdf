import $ from 'jquery'

/**
 * Check if the current PDF template selection uses the legacy Enable Advanced Templating option
 * and hide the Appearance tab altogether
 * @since 4.0
 */
export function toggleAppearanceTab () {
  $('input[name="gfpdf_settings[advanced_template]"]').on('change', function () {
    if ($(this).val() === 'Yes') {
      $('#gfpdf-appearance-nav').hide()
    } else {
      $('#gfpdf-appearance-nav').show()
    }
  })

  $('input[name="gfpdf_settings[advanced_template]"]:checked').trigger('change')
}
