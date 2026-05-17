import {
	toggleUpdateFont,
	removeClass,
	addClass,
} from '../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont';

describe('Utilities/FontManager - toggleUpdateFont.test.js', () => {
	const navigate = jest.fn();
	const pathname = '/fontmanager/roboto';

	test('toggleUpdateFont() - If fontId exist then remove show class', () => {
		// Mock update font panel DOM
		document.body.innerHTML = '<div class="update-font show">' + '</div>';

		toggleUpdateFont(navigate, 'roboto', pathname);

		expect(document.querySelector('div.update-font.show')).toBe(null);
	});

	test('toggleUpdateFont() - If fontId exist then add show class', () => {
		// Mock update font panel DOM
		document.body.innerHTML = '<div class="update-font">' + '</div>';

		toggleUpdateFont(navigate, 'gotham', pathname);

		expect(document.querySelector('div.update-font.show')).toBeTruthy();
	});

	test("toggleUpdateFont() - If fontId doesn't exist then remove show class", () => {
		// Mock update font panel DOM
		document.body.innerHTML = '<div class="update-font">' + '</div>';

		toggleUpdateFont(navigate, '', pathname);

		expect(document.querySelector('div.update-font.show')).toBe(null);
	});

	test('removeClass() - Avoid Warning: Hash history cannot PUSH the same path', () => {
		const mockedElementDOM = {
			classList: {
				remove: jest.fn(),
				add: jest.fn(),
			},
		};

		removeClass(mockedElementDOM, navigate, '/fontmanager/');

		expect(mockedElementDOM.classList.remove).toHaveBeenCalledTimes(1);
		expect(navigate.mock.calls.length).toBe(0);
	});

	test('removeClass() -', () => {
		const mockedElementDOM = { classList: { remove: jest.fn() } };

		removeClass(mockedElementDOM, navigate, pathname);

		expect(mockedElementDOM.classList.remove).toHaveBeenCalledTimes(1);
		expect(navigate.mock.calls.length).toBe(1);
	});

	test('addClass() -', () => {
		const mockedElementDOM = {
			classList: {
				remove: jest.fn(),
				add: jest.fn(),
			},
		};

		addClass(mockedElementDOM, navigate, 'roboto');

		expect(mockedElementDOM.classList.add).toHaveBeenCalledTimes(1);
		expect(navigate.mock.calls.length).toBe(1);
	});
});
