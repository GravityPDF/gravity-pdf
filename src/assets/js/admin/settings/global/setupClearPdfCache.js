import $ from 'jquery';
import { ajaxCall } from '../../helper/ajaxCall';

/**
 * Add the Clear Cache button beside the PDF Cache toggle, and clear the cache over AJAX
 *
 * @since 7.0
 */
export function setupClearPdfCache() {
	const $wrapper = $('.gfpdf-clear-pdf-cache');
	if (!$wrapper.length) {
		return;
	}

	const $button = $(
		'<button type="button" class="button gfpdf-button" />'
	).html(GFPDF.clearPdfCache);
	const $notice = $(
		'<span class="gfpdf-clear-pdf-cache-notice" aria-live="polite" />'
	);

	$wrapper.append($button, $notice);

	/* As the toggle's next sibling, setupToggledFields() shows and hides it from here on */
	if (!$('#gfpdf_settings\\[pdf_cache\\]').prop('checked')) {
		$wrapper.hide();
	}

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
