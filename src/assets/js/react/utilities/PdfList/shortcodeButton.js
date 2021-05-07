import $ from 'jquery'

export default function shortcodeButton () {
  /* Fallback when clipboard not available, or clipboard error */
  if (!ClipboardJS.isSupported()) {
    fallback('.btn-shortcode')

    return
  }

  const clipboard = new ClipboardJS('.btn-shortcode')
  clipboard.on('success', function (e) {
    const gpdf = new GPDFShortcodeButton(e.trigger)
    gpdf.buttonActive()
  })

  clipboard.on('error', function (e) {
    fallback(e.trigger)
    $(e.trigger).trigger('click')
  })

  function fallback (selector) {
    $(selector).on('click', function () {
      $(this).toggleClass('toggle')
      if ($(this).hasClass('toggle')) {
        $(this).next().find('input').focus()
      }
    })

    $(this).next().find('input').on('click focus', function () {
      $(this).select()
    })
  }

  class GPDFShortcodeButton {
    constructor (element) {
      this.element = jQuery(element)
      this.defaultText = this.element.text()
    }

    buttonDefault () {
      this.element.removeClass('btn-success')
      this.element.text(this.defaultText)
    }

    buttonActive () {
      this.element.addClass('btn-success')
      this.element.text(this.element.data('selectedText'))
      setTimeout(() => this.buttonDefault(), 3000)
    }
  }
}
