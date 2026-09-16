import {
	deriveKey,
	validateName,
} from '../../../../../src/assets/js/react/fontManager/utils/fontKey';

describe('Font Manager - utils/fontKey.js', () => {
	describe('deriveKey()', () => {
		test('strips everything a template could not write', () => {
			expect(deriveKey('Lato Light')).toBe('latolight');
			expect(deriveKey('Source Sans 3')).toBe('sourcesans3');
		});

		test('suffixes a key that would collide with a route word', () => {
			expect(deriveKey('Settings')).toBe('settingsfont');
			expect(deriveKey('updates')).toBe('updatesfont');
		});
	});

	describe('validateName()', () => {
		test('requires a name', () => {
			expect(validateName('   ')).toBe('Font name is required');
		});

		test('refuses characters the key rule would drop', () => {
			expect(validateName('Lato-Light')).toBe(
				'Use letters, numbers, and spaces only'
			);
		});

		test('refuses a name whose key is already taken', () => {
			expect(validateName('Lato Light', ['latolight'])).toBe(
				'A font with this name already exists'
			);
		});

		test('accepts a name whose key is free', () => {
			expect(validateName('Lato Light', ['lato'])).toBe('');
		});
	});
});
