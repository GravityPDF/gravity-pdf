import $ from 'jquery'

/**
 * Check if a Gravity PDF color picker field is present and initialise
 * @return void
 * @since 4.0
 */
export function doColorPicker () {
  $('.gfpdf-color-picker').each(function () {
    $(this).wpColorPicker({
      width: 300
    })
    $(this).parents('.wp-picker-container').find('.wp-color-result').addClass('ed_button')
  })
}
