import $ from 'jquery';
import { setupClearPdfCache } from '../../../../../src/assets/js/admin/settings/global/setupClearPdfCache';
import { setupToggledFields } from '../../../../../src/assets/js/admin/settings/common/setupToggledFields';
import { ajaxCall } from '../../../../../src/assets/js/admin/helper/ajaxCall';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

jest.mock('../../../../../src/assets/js/admin/helper/ajaxCall');

const toggle = () => $('#gfpdf_settings\\[pdf_cache\\]');
const wrapper = () => $('.gfpdf-clear-pdf-cache');
const button = () => wrapper().find('button');
const notice = () => $('.gfpdf-clear-pdf-cache-notice');
const isShown = () => wrapper().css('display') !== 'none';

/* The PDF Cache toggle as toggle_callback() renders it, with the placeholder in its desc2 */
function render(checked = true) {
	$('body').append(
		`<form>
			<span class="gform-settings-input__container">
				<input type="checkbox" id="gfpdf_settings[pdf_cache]" class="gfpdf-input-toggle" value="Yes" ${
					checked ? 'checked' : ''
				} />
			</span>
			<span class="gfpdf-clear-pdf-cache"></span>
		</form>`
	);

	setupToggledFields();
	setupClearPdfCache();
}

function setToggle(checked) {
	toggle().prop('checked', checked).trigger('change');
}

/* Click, then hand the wired callback whatever the endpoint (or jQuery's error handler) returns */
function clear(respondWith) {
	button().trigger('click');
	ajaxCall.mock.calls[0][1](respondWith);
}

describe('setupClearPdfCache', () => {
	beforeAll(() => {
		$.fx.off = true;

		Object.assign(window.GFPDF, {
			ajaxNonce: 'nonce-123',
			clearPdfCache: 'Clear Cache',
			clearPdfCacheError: 'The PDF cache could not be cleared.',
		});
	});

	afterAll(() => {
		$.fx.off = false;
	});

	afterEach(() => {
		$('body').empty();
	});

	it('adds a non-submitting button beside the toggle', () => {
		render();

		expect(button().length).toBe(1);
		expect(button().attr('type')).toBe('button');
		expect(button().text()).toBe('Clear Cache');
		expect(notice().attr('aria-live')).toBe('polite');
		expect(isShown()).toBe(true);
	});

	it('does nothing for users who cannot edit settings', () => {
		$('body').append(
			'<input type="checkbox" id="gfpdf_settings[pdf_cache]" checked />'
		);
		setupClearPdfCache();

		expect($('button').length).toBe(0);
	});

	it('is shown only while the toggle is on, without saving', () => {
		render(false);
		expect(isShown()).toBe(false);

		setToggle(true);
		expect(isShown()).toBe(true);

		setToggle(false);
		expect(isShown()).toBe(false);
	});

	it('posts once, and disables the button while the request is in flight', () => {
		render();
		button().trigger('click');

		expect(ajaxCall).toHaveBeenCalledTimes(1);
		expect(ajaxCall.mock.calls[0][0]).toEqual({
			action: 'gfpdf_clear_pdf_cache',
			nonce: 'nonce-123',
		});
		expect(button().prop('disabled')).toBe(true);
	});

	it('shows the success message and restores the button', () => {
		render();
		clear({ success: 'PDF cache cleared.' });

		expect(notice().text()).toBe('PDF cache cleared.');
		expect(notice().hasClass('success')).toBe(true);
		expect(button().prop('disabled')).toBe(false);
	});

	it('shows the error message and restores the button on a failed request', () => {
		render();
		/* jQuery's error handler passes the raw jqXHR */
		clear({ readyState: 4, status: 401, statusText: 'Unauthorized' });

		expect(notice().text()).toBe('The PDF cache could not be cleared.');
		expect(notice().hasClass('error')).toBe(true);
		expect(notice().hasClass('success')).toBe(false);
		expect(button().prop('disabled')).toBe(false);
	});
});
