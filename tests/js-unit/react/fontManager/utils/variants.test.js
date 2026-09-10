import {
	parseStyle,
	parseStyles,
	variantsFromFiles,
} from '../../../../../src/assets/js/react/fontManager/utils/variants';

describe('Font Manager - utils/variants.js', () => {
	describe('parseStyle()', () => {
		test('reads the publisher ids without asking the server', () => {
			expect(parseStyle('regular')).toMatchObject({
				weight: 400,
				italic: false,
				label: 'Regular',
				group: 'Upright',
			});

			expect(parseStyle('700italic')).toMatchObject({
				weight: 700,
				italic: true,
				label: 'Bold Italic',
				group: 'Italic',
			});
		});

		test('falls back to the number for a weight with no name', () => {
			expect(parseStyle('250').label).toBe('250');
		});
	});

	describe('parseStyles()', () => {
		test('is empty for an entry that offers no choice', () => {
			expect(parseStyles(null)).toEqual([]);
		});

		test('lists uprights before italics, lightest first', () => {
			expect(
				parseStyles('700italic,regular,300,italic,700').map(
					(style) => style.id
				)
			).toEqual(['300', 'regular', '700', 'italic', '700italic']);
		});
	});

	describe('variantsFromFiles()', () => {
		test('reads which style fills each role off the file rows', () => {
			expect(
				variantsFromFiles({
					R: { role: 'R', variant: 'regular' },
					B: { role: 'B', variant: '700' },
					I: { role: 'I', variant: null },
				})
			).toEqual({ R: 'regular', B: '700', I: '' });
		});
	});
});
