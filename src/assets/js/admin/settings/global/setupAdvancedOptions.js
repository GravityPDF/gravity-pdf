import $ from 'jquery'

/**
 * Controls the Advanced Options hide / show functionality
 * By default these fields are hidden, but are show automatically if an error occurs.
 * @return void
 * @since 4.0
 */
export function setupAdvancedOptions () {
  const $advancedOptionsToggleContainer = $('.gfpdf-advanced-options')
  const $advancedOptionsContainer = $advancedOptionsToggleContainer.prev()
  const $advancedOptions = $advancedOptionsToggleContainer.find('a')

  /*
   * Show / Hide Advanced options
   */
  $advancedOptions.click(function () {
    const click = this

    /* toggle our slider */
    $advancedOptionsContainer.slideToggle(600, function () {
      /* Toggle our link text */
      const text = $(click).text()
      $(click).text(
        text === GFPDF.showAdvancedOptions ? GFPDF.hideAdvancedOptions : GFPDF.showAdvancedOptions
      )
    })

    return false
  })

  if ($('.gfpdf-advanced-options').prev().find('.gfield_error').length) {
    $advancedOptionsContainer.show()
  }
}
