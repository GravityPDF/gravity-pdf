import { searchKey } from '../../../../../src/assets/js/react/fontManager/utils/searchKey';

describe('Font Manager - utils/searchKey.js', () => {
	test('is stable however the query object was built', () => {
		expect(searchKey('google', { page: '1', s: 'lato' })).toBe(
			searchKey('google', { s: 'lato', page: '1' })
		);
	});

	test('drops the params that were left blank', () => {
		expect(searchKey('google', { s: '', category: 'serif' })).toBe(
			'google&category=serif'
		);
	});

	test('tells two searches of the same source apart', () => {
		expect(searchKey('google', { page: '1' })).not.toBe(
			searchKey('google', { page: '2' })
		);
	});
});
