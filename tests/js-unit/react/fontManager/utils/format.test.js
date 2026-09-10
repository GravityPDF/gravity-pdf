import {
	fileSize,
	filterLabel,
	timeAgo,
} from '../../../../../src/assets/js/react/fontManager/utils/format';

describe('Font Manager - utils/format.js', () => {
	describe('fileSize()', () => {
		test('says nothing at all for a missing size', () => {
			expect(fileSize(0)).toBe('');
			expect(fileSize(null)).toBe('');
		});

		test('scales to the unit a person reads', () => {
			expect(fileSize(512)).toBe('512 B');
			expect(fileSize(2048)).toBe('2 KB');
			expect(fileSize(10595932)).toBe('10.1 MB');
		});
	});

	describe('timeAgo()', () => {
		test('is empty when the catalogue has never synced', () => {
			expect(timeAgo(null)).toBe('');
		});

		test('picks the largest unit that still reads as a number', () => {
			const ago = (seconds) =>
				timeAgo(new Date(Date.now() - seconds * 1000).toISOString());

			expect(ago(10)).toBe('just now');
			expect(ago(120)).toBe('2 minutes ago');
			expect(ago(7200)).toBe('2 hours ago');
			expect(ago(86400 * 3)).toBe('3 days ago');
		});

		test('reads one of a unit in the singular', () => {
			expect(
				timeAgo(new Date(Date.now() - 86400 * 1000).toISOString())
			).toBe('1 day ago');
		});
	});

	describe('filterLabel()', () => {
		test('translates the vocabulary it knows', () => {
			expect(filterLabel('latin-ext')).toBe('Latin Extended');
			expect(filterLabel('sans-serif')).toBe('Sans Serif');
		});

		test('title-cases an id it has never seen', () => {
			expect(filterLabel('old-hungarian')).toBe('Old Hungarian');
		});
	});
});
