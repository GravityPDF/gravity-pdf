import {
	interval,
	moved,
} from '../../../../../src/assets/js/react/fontManager/hooks/useInstallPoller';
import {
	isInstalling,
	isRunning,
} from '../../../../../src/assets/js/react/fontManager/utils/install';

describe('Font Manager - the install phase vocabulary', () => {
	test('isInstalling() is true only while a batch is queued or installing', () => {
		expect(isInstalling({ phase: 'queued' })).toBe(true);
		expect(isInstalling({ phase: 'installing' })).toBe(true);
		expect(isInstalling({ phase: null })).toBe(false);
		expect(isInstalling({ phase: 'failed' })).toBe(false);
		expect(isInstalling(null)).toBe(false);
	});

	test('a stuck batch is still installing, so the surfaces keep showing it', () => {
		expect(isInstalling({ phase: 'installing', stuck: true })).toBe(true);
	});

	test('isRunning() drops the stuck batch, so the timer stops', () => {
		expect(isRunning({ phase: 'installing', stuck: true })).toBe(false);
		expect(isRunning({ phase: 'installing' })).toBe(true);
	});
});

describe('Font Manager - hooks/useInstallPoller.js', () => {
	describe('moved()', () => {
		test('sees a phase change', () => {
			expect(
				moved(
					{ a: { phase: 'queued' } },
					{ a: { phase: 'installing' } }
				)
			).toBe(true);
		});

		test('sees a file land', () => {
			expect(
				moved(
					{ a: { phase: 'installing', files_done: 1 } },
					{ a: { phase: 'installing', files_done: 2 } }
				)
			).toBe(true);
		});

		test('sees an entry appear or disappear', () => {
			expect(moved({}, { a: { phase: 'queued' } })).toBe(true);
		});

		test('is false when two reads say the same thing', () => {
			const map = { a: { phase: 'installing', files_done: 3 } };

			expect(
				moved(map, { a: { phase: 'installing', files_done: 3 } })
			).toBe(false);
		});
	});

	describe('interval()', () => {
		test('walks the back-off one step per read that found nothing', () => {
			expect(interval(0)).toBe(2000);
			expect(interval(1)).toBe(5000);
			expect(interval(2)).toBe(10000);
		});

		test('settles into a heartbeat once the back-off runs out', () => {
			expect(interval(3)).toBe(30000);
			expect(interval(99)).toBe(30000);
		});
	});
});
