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
  $elm.find('input[type="submit"]').click(function () {
    $elm.addClass('formSubmitted')
  })

  /* add the required star to make it easier for users */
  const $required = $( '<span class="gfield_required">(required)</span>' )
  $elm.find(':input[required=""], :input[required]').each(function () {
    const $container = $(this).parent()
    if( $container.find('.gform-settings-panel__title a').length ) {
      $container.find('.gform-settings-panel__title a').before('<span class="gfield_required">(required)</span>')
    } else {
      $container.find('.gform-settings-panel__title a').append('<span class="gfield_required">(required)</span>')
    }
  })
}
