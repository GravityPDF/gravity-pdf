import $ from 'jquery'

/**
 * An AJAX Wrapper function we can use to ajaxify our plugin
 * @param post Object an object of data to submit to our ajax endpoint. This MUST include an 'nonce' and an 'action'
 * @param responseCallback a callback function
 * @return void
 * @since 4.0
 */
export function ajaxCall (post, responseCallback) {
  const doAjaxcall = $.ajax({
    type: 'post',
    dataType: 'json',
    url: GFPDF.ajaxUrl,
    data: post,
    success: responseCallback,
    error: responseCallback
  })

  return doAjaxcall
}
