import { toggleUpdateFont } from '../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont';

describe('Utilities/FontManager - toggleUpdateFont.test.js', () => {
	const navigate = jest.fn();

	beforeEach(() => navigate.mockClear());

	test('Opens a font', () => {
		toggleUpdateFont(navigate, 'gotham', '/fontmanager/roboto');

		expect(navigate).toHaveBeenCalledWith('/fontmanager/gotham');
	});

	test('Closes the font when it is already open', () => {
		toggleUpdateFont(navigate, 'roboto', '/fontmanager/roboto');

		expect(navigate).toHaveBeenCalledWith('/fontmanager/');
	});

	test('Closes the font with no font given', () => {
		toggleUpdateFont(navigate);

		expect(navigate).toHaveBeenCalledWith('/fontmanager/');
	});

	test('Avoids pushing the same path', () => {
		toggleUpdateFont(navigate, '', '/fontmanager/');

		expect(navigate).not.toHaveBeenCalled();
	});
});
