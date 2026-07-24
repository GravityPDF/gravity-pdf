import $ from 'jquery';
import { setupLicenseDeactivation } from '../../../../../src/assets/js/admin/settings/common/setupLicenseDeactivation';
import { ajaxCall } from '../../../../../src/assets/js/admin/helper/ajaxCall';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.16.0
 */

jest.mock('../../../../../src/assets/js/admin/helper/ajaxCall');
jest.mock('../../../../../src/assets/js/admin/helper/spinner', () => ({
	/* jquery is required lazily: a jest.mock factory can't close over the module-scope `$` import */
	spinner: () => require('jquery')('<span class="gfpdf-spinner" />'),
}));

/* Build the license field wrapper the way license_callback() renders it: message div, key/message/status inputs, and
   the Deactivate button, all inside the #gfpdf-settings-field-wrapper-license_<slug> container. */
function renderLicenseField(slug) {
	return $(
		`<div id="gfpdf-settings-field-wrapper-license_${slug}">
      <div id="message" class="success">existing message</div>
      <input id="gfpdf_settings[license_${slug}]" value="a-real-license-key" />
      <input id="gfpdf_settings[license_${slug}_message]" value="stored message" />
      <input id="gfpdf_settings[license_${slug}_status]" value="active" />
      <button class="gfpdf-deactivate-license" data-addon-name="${slug}" data-nonce="nonce-123">Deactivate License</button>
    </div>`
	);
}

/* Trigger the click, then hand the wired callback whatever the endpoint (or jQuery's error handler) would return. */
function deactivate(respondWith) {
	$('.gfpdf-deactivate-license').first().trigger('click');
	const callback = ajaxCall.mock.calls[0][1];
	callback(respondWith);
}

const fieldValue = (slug, suffix = '') =>
	$(`#gfpdf_settings\\[license_${slug}${suffix}\\]`).val();

describe('setupLicenseDeactivation', () => {
	beforeEach(() => {
		$('body').append(renderLicenseField('sample'));
		setupLicenseDeactivation();
	});

	afterEach(() => {
		$('body').empty();
	});

	describe('on success', () => {
		it('clears the stored key/message/status, removes the button, and shows the success message', () => {
			deactivate({ success: 'License key deactivated.', extra: [] });

			expect(fieldValue('sample')).toBe('');
			expect(fieldValue('sample', '_message')).toBe('');
			expect(fieldValue('sample', '_status')).toBe('');
			expect($('.gfpdf-deactivate-license').length).toBe(0);

			const $message = $(
				'#gfpdf-settings-field-wrapper-license_sample #message'
			);
			expect($message.hasClass('success')).toBe(true);
			expect($message.hasClass('error')).toBe(false);
			expect($message.html()).toBe('License key deactivated.');
		});

		it('also tears down any All Access Pass siblings returned in extra', () => {
			$('body').append(renderLicenseField('sibling'));

			deactivate({
				success: 'Access Pass license key deactivated.',
				extra: ['sibling'],
			});

			expect(fieldValue('sibling')).toBe('');
			expect(
				$('#gfpdf-settings-field-wrapper-license_sibling button').length
			).toBe(0);
		});
	});

	describe('on an application error (HTTP 200 { error })', () => {
		it('still removes the key from the site, but shows the error', () => {
			deactivate({ error: 'An API error occurred.' });

			expect(fieldValue('sample')).toBe('');
			expect($('.gfpdf-deactivate-license').length).toBe(0);

			const $message = $(
				'#gfpdf-settings-field-wrapper-license_sample #message'
			);
			expect($message.hasClass('error')).toBe(true);
			expect($message.hasClass('success')).toBe(false);
			expect($message.html()).toBe('An API error occurred.');
		});
	});

	describe('on a transport/auth failure (jqXHR, not our JSON)', () => {
		it('still removes the key from the site, and shows the generic fallback message', () => {
			/* jQuery's error handler passes the raw jqXHR — neither success nor error is a string */
			deactivate({
				readyState: 4,
				status: 401,
				statusText: 'Unauthorized',
			});

			expect(fieldValue('sample')).toBe('');
			expect($('.gfpdf-deactivate-license').length).toBe(0);

			const $message = $(
				'#gfpdf-settings-field-wrapper-license_sample #message'
			);
			expect($message.hasClass('error')).toBe(true);
			expect($message.hasClass('success')).toBe(false);
			expect($message.html()).toBe(GFPDF.licenseDeactivationError);
		});
	});

	describe('on a repeat click while a request is in flight', () => {
		it('ignores the second click so only one deactivation request is sent', () => {
			$('.gfpdf-deactivate-license').first().trigger('click');
			$('.gfpdf-deactivate-license').first().trigger('click');

			expect(ajaxCall).toHaveBeenCalledTimes(1);
		});
	});
});
