import {
	DEFAULT_STATE,
	reducer,
} from '../../../../../src/assets/js/react/fontManager/store/reducer';

describe('Font Manager - store/reducer.js', () => {
	test('starts with nothing resolved, which is not the same as empty', () => {
		expect(reducer(undefined, {})).toEqual(DEFAULT_STATE);
		expect(reducer(undefined, {}).fonts).toBeNull();
	});

	test('replaces the status map wholesale, because the route answers with all of it', () => {
		const first = reducer(undefined, {
			type: 'RECEIVE_STATUSES',
			statuses: { 'packs/emoji': { phase: 'queued' } },
		});
		const second = reducer(first, {
			type: 'RECEIVE_STATUSES',
			statuses: { 'packs/indic': { phase: null } },
		});

		expect(second.statuses).toEqual({ 'packs/indic': { phase: null } });
	});

	test('files each entry and each search page under its own key', () => {
		let state = reducer(undefined, {
			type: 'RECEIVE_ENTRY',
			id: 'google/lato',
			entry: { label: 'Lato' },
		});
		state = reducer(state, {
			type: 'RECEIVE_SEARCH',
			key: 'google&page=1',
			results: { entries: [] },
		});

		expect(state.entries['google/lato'].label).toBe('Lato');
		expect(state.searches['google&page=1'].entries).toEqual([]);
	});

	test('tracks busy and error per key, so two writes do not blur together', () => {
		let state = reducer(undefined, {
			type: 'SET_BUSY',
			key: 'packs/emoji',
			busy: true,
		});
		state = reducer(state, {
			type: 'SET_ERROR',
			key: 'lato',
			message: 'nope',
		});

		expect(state.busy).toEqual({ 'packs/emoji': true });
		expect(state.errors).toEqual({ lato: 'nope' });
	});

	test('ignores an action it does not know', () => {
		const state = reducer(undefined, {});

		expect(reducer(state, { type: 'SOMETHING_ELSE' })).toBe(state);
	});
});
