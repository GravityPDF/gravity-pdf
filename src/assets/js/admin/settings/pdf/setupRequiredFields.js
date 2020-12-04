import $ from 'jquery'

/**
 * Enable dynamic required fields on the Gravity Forms PDF Settings page
 * This function will highlight to the user which fields should be processed, and disable in-browser validation
 * @return void
 * @since 4.0
 */
export function setupRequiredFields ($elm) {
  /* prevent in browser validation */
  $elm.attr('novalidate', 'novalidate')

  /* gf compatibility + disable automatic field validation */
  $elm.find('tr input[type="submit"]').on('click', function () {
    $elm.addClass('formSubmitted')
  })

  /* add the required star to make it easier for users */
  $elm.find('tr').each(function () {
    $(this).find(':input[required=""]:first, :input[required]:first').parents('tr').find('th').append('<span class="gfield_required">*</span>')
  })
}
