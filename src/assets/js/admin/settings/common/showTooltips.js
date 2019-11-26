import $ from 'jquery'

/**
 * Because we are using the WordPress Settings API Gravity Forms tooltip support was lacking
 * This method fixes that issue
 * @return void
 * @since 4.0
 */
export function showTooltips () {
  /**
   * Create the tooltip HTML
   * @param  String html The tooltip message
   * @return String
   * @since 4.0
   */
  function getTooltip (html) {
    const $a = $('<a>')
    const $i = $('<i class="fa fa-question-circle">')

    $a.append($i)
    $a.addClass('gf_tooltip tooltip')
    $a.click(function () {
      return false
    })

    $a.attr('title', html)

    return $a
  }

  if (typeof gform_initialize_tooltips !== 'function') { // eslint-disable-line
    return
  }

  $('.gf_hidden_tooltip').each(function () {
    $(this)
      .parent()
      .siblings('th:first')
      .append(' ')
      .append(
        getTooltip($(this).html())
      )

    $(this).remove()
  })

  gform_initialize_tooltips()
}
