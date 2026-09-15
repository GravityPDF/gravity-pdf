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

	test('keeps the advice a save came back with under the row it saved', () => {
		let state = reducer(undefined, {
			type: 'SET_WARNINGS',
			key: 'housesans',
			warnings: ['Bold.ttf was uploaded as Italic.'],
		});

		expect(state.warnings.housesans).toHaveLength(1);

		/* Replaced wholesale, so an upload that fixed the slot stops being told about it */
		state = reducer(state, {
			type: 'SET_WARNINGS',
			key: 'housesans',
			warnings: [],
		});

		expect(state.warnings.housesans).toEqual([]);
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
			code: 'font_entry_unknown',
		});

		expect(state.busy).toEqual({ 'packs/emoji': true });
		expect(state.errors).toEqual({
			lato: { message: 'nope', code: 'font_entry_unknown' },
		});
	});

	test('ignores an action it does not know', () => {
		const state = reducer(undefined, {});

		expect(reducer(state, { type: 'SOMETHING_ELSE' })).toBe(state);
	});
});
