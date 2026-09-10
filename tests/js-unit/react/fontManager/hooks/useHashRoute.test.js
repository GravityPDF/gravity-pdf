import { matchRoute } from '../../../../../src/assets/js/react/fontManager/hooks/useHashRoute';

describe('Font Manager - hooks/useHashRoute.js', () => {
	test('is not the Font Manager when the hash is somebody else’s', () => {
		expect(matchRoute('')).toBeNull();
		expect(matchRoute('/templates/')).toBeNull();
	});

	test('matches the literal routes before the font key', () => {
		expect(matchRoute('/fontmanager/settings').name).toBe('settings');
		expect(matchRoute('/fontmanager/updates').name).toBe('updates');
		expect(matchRoute('/fontmanager/browse').name).toBe('browse');
	});

	test('reads one pair of browse routes for every source', () => {
		expect(matchRoute('/fontmanager/browse/google')).toEqual({
			name: 'browse',
			params: { source: 'google' },
		});

		expect(matchRoute('/fontmanager/browse/packs/cjk-ext-b')).toEqual({
			name: 'entry',
			params: { source: 'packs', entry: 'cjk-ext-b' },
		});
	});

	test('reads a font key, underscores included', () => {
		expect(matchRoute('/fontmanager/foo_bar')).toEqual({
			name: 'font',
			params: { id: 'foo_bar' },
		});
	});

	test('is the home route with or without the trailing slash', () => {
		expect(matchRoute('/fontmanager/').name).toBe('home');
		expect(matchRoute('/fontmanager').name).toBe('home');
	});
});
