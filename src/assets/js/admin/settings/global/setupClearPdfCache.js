import $ from 'jquery';
import { ajaxCall } from '../../helper/ajaxCall';

/**
 * Add the Clear PDF Cache button to its settings field, and clear the cache over AJAX when it is clicked
 *
 * @since 7.0
 */
export function setupClearPdfCache() {
	const $wrapper = $('#gfpdf-settings-field-wrapper-clear_pdf_cache');
	if (!$wrapper.length) {
		return;
	}

	/* The id matches the field label's `for` */
	const $button = $('<button type="button" class="button gfpdf-button" />')
		.attr('id', 'gfpdf_settings[clear_pdf_cache]')
		.html(GFPDF.clearPdfCache);

	const $notice = $(
		'<div class="gfpdf-clear-pdf-cache-notice" aria-live="polite" />'
	);

	$wrapper.append($button, $notice);

	$button.on('click', () => {
		$button.prop('disabled', true);
		$notice.removeClass('success error').text('');

		ajaxCall(
			{ action: 'gfpdf_clear_pdf_cache', nonce: GFPDF.ajaxNonce },
			(response) => {
				/* A failed request passes jQuery's jqXHR, not our JSON. Both messages are escaped by PHP */
				const success = typeof response?.success === 'string';

				$notice
					.addClass(success ? 'success' : 'error')
					.html(
						success ? response.success : GFPDF.clearPdfCacheError
					);

				$button.prop('disabled', false);
			}
		);
	});
}
