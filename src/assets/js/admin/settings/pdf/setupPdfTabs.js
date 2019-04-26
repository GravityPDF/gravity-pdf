import $ from 'jquery'

/**
 * Handle our AJAX tabs to make it easier to navigate around our settings
 * @return void
 * @since 4.0
 */
export function setupPdfTabs () {
  /* Hide all containers except the first one */
  $('.gfpdf-tab-container').not(':eq(0)').hide()

  /* Add click handler when our nav is selected */
  $('.gfpdf-tab-wrapper a').click(function () {
    /* Reset the active class */
    $(this).parents('ul').find('a').removeClass('current')

    /* Add the new active class */
    $(this).addClass('current').blur()

    /* Hide all containers */
    $('.gfpdf-tab-container').hide()

    /* Show new active container */
    $($(this).attr('href')).show()

    return false
  })
}
