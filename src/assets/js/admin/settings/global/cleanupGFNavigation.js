import $ from 'jquery'

/**
 * Our &tab=(.+?) url param causes issues with the default GF navigation
 * @return void
 * @since 4.0
 */
export function cleanupGFNavigation () {
  let $nav = $('#gform_tabs a')

  $nav.each(function () {
    let href = $(this).attr('href')
    let regex = new RegExp('&tab=[^&;]*', 'g')

    $(this).attr('href', href.replace(regex, ''))
  })
}
