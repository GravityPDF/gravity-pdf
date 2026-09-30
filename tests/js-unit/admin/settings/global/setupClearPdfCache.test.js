import $ from 'jquery';
import { setupClearPdfCache } from '../../../../../src/assets/js/admin/settings/global/setupClearPdfCache';
import { ajaxCall } from '../../../../../src/assets/js/admin/helper/ajaxCall';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

jest.mock('../../../../../src/assets/js/admin/helper/ajaxCall');

const button = () => $('#gfpdf-settings-field-wrapper-clear_pdf_cache button');
const notice = () => $('.gfpdf-clear-pdf-cache-notice');

/* Click, then hand the wired callback whatever the endpoint (or jQuery's error handler) returns */
function clear(respondWith) {
	button().trigger('click');
	ajaxCall.mock.calls[0][1](respondWith);
}

describe('setupClearPdfCache', () => {
	beforeAll(() => {
		Object.assign(window.GFPDF, {
			ajaxNonce: 'nonce-123',
			clearPdfCache: 'Clear PDF Cache',
			clearPdfCacheError: 'The PDF cache could not be cleared.',
		});
	});

	beforeEach(() => {
		$('body').append(
			`<div id="gfpdf-settings-field-wrapper-clear_pdf_cache">
				<label for="gfpdf_settings[clear_pdf_cache]">Clear PDF Cache</label>
				<div class="gform-settings-description">Stop serving every cached PDF.</div>
			</div>`
		);

		setupClearPdfCache();
	});

	afterEach(() => {
		$('body').empty();
	});

	it('adds a non-submitting button the field label points to', () => {
		expect(button().length).toBe(1);
		expect(button().attr('type')).toBe('button');
		expect(button().attr('id')).toBe('gfpdf_settings[clear_pdf_cache]');
		expect(button().text()).toBe('Clear PDF Cache');
		expect(notice().attr('aria-live')).toBe('polite');
	});

	it('does nothing without the field', () => {
		$('body').empty();
		setupClearPdfCache();

		expect($('button').length).toBe(0);
	});

	it('posts once, and disables the button while the request is in flight', () => {
		button().trigger('click');

		expect(ajaxCall).toHaveBeenCalledTimes(1);
		expect(ajaxCall.mock.calls[0][0]).toEqual({
			action: 'gfpdf_clear_pdf_cache',
			nonce: 'nonce-123',
		});
		expect(button().prop('disabled')).toBe(true);
	});

	it('shows the success message and restores the button', () => {
		clear({ success: 'Cached PDFs cleared.' });

		expect(notice().text()).toBe('Cached PDFs cleared.');
		expect(notice().hasClass('success')).toBe(true);
		expect(button().prop('disabled')).toBe(false);
	});

	it('shows the error message and restores the button on a failed request', () => {
		/* jQuery's error handler passes the raw jqXHR */
		clear({ readyState: 4, status: 401, statusText: 'Unauthorized' });

		expect(notice().text()).toBe('The PDF cache could not be cleared.');
		expect(notice().hasClass('error')).toBe(true);
		expect(notice().hasClass('success')).toBe(false);
		expect(button().prop('disabled')).toBe(false);
	});
});
