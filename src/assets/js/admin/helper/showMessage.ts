const $ = jQuery;

/**
 * Display a message or error to the user with an appropriate timeout
 *
 * @param {string}    msg     The message to be displayed
 * @param { number }  timeout How long to show the message
 * @param { boolean } error   Whether to show an error (true) or a message (false or undefined)
 *
 * @since 4.0
 */
export function showMessage(
	msg: string,
	timeout?: number,
	error?: boolean
): void {
	const resolvedTimeout = typeof timeout !== 'undefined' ? timeout : 4500;
	const resolvedError = typeof error !== 'undefined' ? error : false;

	const $elm = $('<div id="message">').html('<p>' + msg + '</p>');

	if (resolvedError === true) {
		$elm.addClass('error');
	} else {
		$elm.addClass('updated');
	}

	$('.wrap > h2').after($elm);

	setTimeout(function () {
		$elm.slideUp();
	}, resolvedTimeout);
}
