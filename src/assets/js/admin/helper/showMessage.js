import $ from 'jquery'

/**
 * Display a message or error to the user with an appropriate timeout
 * @param  String msg     The message to be displayed
 * @param  Integer timeout How long to show the message
 * @param  Boolean error   Whether to show an error (true) or a message (false or undefined)
 * @return void
 * @since 4.0
 */
export function showMessage (msg, timeout, error) {
  timeout = typeof timeout !== 'undefined' ? timeout : 4500
  error = typeof error !== 'undefined' ? error : false

  let $elm = $('<div id="message">').html('<p>' + msg + '</p>')

  if (error === true) {
    $elm.addClass('error')
  } else {
    $elm.addClass('updated')
  }

  $('.wrap > h2').after($elm)

  setTimeout(function () {
    $elm.slideUp()
  }, timeout)
}
