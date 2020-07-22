/**
 * Gravity PDF Entries
 * Dependancies: jQuery
 * @since 4.0
 */

(function ($) {
  /**
   * Fires on the Document Ready Event
   */
  $(function () {
    let timer = null
    $('.gfpdf_form_action_has_submenu > a')
      /* Handle keyboard navigation */
      .on('click', function () {
        if ($(this).attr('aria-expanded') === 'false') {
          $(this).parent().addClass('open')
          $(this).attr('aria-expanded', 'true')
        } else {
          $(this).parent().removeClass('open')
          $(this).attr('aria-expanded', 'false')
        }

        return false
      })
      .parent()
      /* Hide submenu after a delay */
      .on('mouseover', function () {
        clearTimeout(timer)

        $(this)
          .addClass('open')
          .find('> a')
          .attr('aria-expanded', 'true')
      })
      .on('mouseout', function () {
        const $submenu = $(this)
        timer = setTimeout(function () {
          $submenu
            .removeClass('open')
            .find('> a')
            .attr('aria-expanded', 'false')
        }, 1000)
      })
  })
})(jQuery)
