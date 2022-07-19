import $ from 'jquery'

/**
 * Our &tab=(.+?) url param causes issues with the default GF navigation
 * @return void
 * @since 4.0
 */
export function cleanupGFNavigation () {
  const $nav = $('#gform_tabs a')

  $nav.each(function () {
    const href = $(this).attr('href')
    const regex = new RegExp('&tab=[^&;]*', 'g') // eslint-disable-line

    $(this).attr('href', href.replace(regex, ''))
  })
}
