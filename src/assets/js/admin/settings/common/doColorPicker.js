import $ from 'jquery'

/**
 * Check if a Gravity PDF color picker field is present and initialise
 * @return void
 * @since 4.0
 */
export function doColorPicker () {
  $('.gfpdf-color-picker').each(function () {
    $(this).wpColorPicker()
  })
}
