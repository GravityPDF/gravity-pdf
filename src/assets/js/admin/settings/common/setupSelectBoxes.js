import $ from 'jquery'

/**
 * Set up 'chosen' select boxes
 * @return void
 * @since 4.0
 */
export function setupSelectBoxes () {
  let $chosen = $('.gfpdf-chosen')
  let chosenSettings = {
    disable_search_threshold: 5,
    width: '100%'
  }

  if ($('body').hasClass('rtl')) {
    $chosen.addClass('chosen-rtl')
    chosenSettings.rtl = true
  }

  $chosen.each(function () {
    $(this).chosen(chosenSettings)
  })
}
