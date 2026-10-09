import {
	SEARCH_TEMPLATES,
	SELECT_TEMPLATE,
	ADD_TEMPLATE,
	UPDATE_TEMPLATE_PARAM,
	DELETE_TEMPLATE,
	UPDATE_SELECT_BOX_SUCCESS,
	TEMPLATE_PROCESSING_SUCCESS,
	TEMPLATE_PROCESSING_FAILED,
	CLEAR_TEMPLATE_PROCESSING,
	POST_TEMPLATE_UPLOAD_PROCESSING,
	TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
	TEMPLATE_UPLOAD_PROCESSING_FAILED,
	TEMPLATE_UPLOAD_REJECTED,
	DISMISS_TEMPLATE_UPLOAD_SUCCESS,
	CLEAR_FINISHED_TEMPLATE_UPLOADS,
} from '../../../../src/assets/js/react/actions/templates';
import reducer, {
	initialState,
} from '../../../../src/assets/js/react/reducers/templateReducer';

describe('Reducers - templateReducer', () => {
	let newState;

	describe('SEARCH_TEMPLATES', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: SEARCH_TEMPLATES,
				text: 'New Search Item',
			});

			expect(newState.search).toBe('New Search Item');

			newState = reducer(newState, {
				type: SEARCH_TEMPLATES,
				text: 'Another Search Item',
			});

			expect(newState.search).toBe('Another Search Item');
		});
	});

	describe('SELECT_TEMPLATE', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: SELECT_TEMPLATE,
				id: 'template-id',
			});

			expect(newState.activeTemplate).toBe('template-id');

			newState = reducer(newState, {
				type: SELECT_TEMPLATE,
				id: 'new-template-id',
			});

			expect(newState.activeTemplate).toBe('new-template-id');
		});
	});

	describe('ADD_TEMPLATE', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: ADD_TEMPLATE,
				template: { id: 'template-id' },
			});

			expect(newState.list.length).toBe(4);

			newState = reducer(newState, {
				type: ADD_TEMPLATE,
				template: { id: 'template-id1' },
			});

			expect(newState.list.length).toBe(5);
		});
	});

	describe('UPDATE_TEMPLATE_PARAM', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: UPDATE_TEMPLATE_PARAM,
				id: 'zadani',
				name: 'owner',
				value: 'Wilson',
			});

			expect(newState.list[0].owner).toBe('Wilson');

			newState = reducer(initialState, {
				type: UPDATE_TEMPLATE_PARAM,
				id: 'zadani',
				name: 'owner',
				value: 'Billy',
			});

			expect(newState.list[0].owner).toBe('Billy');
		});
	});

	describe('DELETE_TEMPLATE', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: DELETE_TEMPLATE,
				id: 'zadani',
			});

			expect(newState.list.length).toBe(2);

			newState = reducer(newState, {
				type: DELETE_TEMPLATE,
				id: 'rubix',
			});

			expect(newState.list.length).toBe(1);
		});
	});

	describe('UPDATE_SELECT_BOX_SUCCESS', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: UPDATE_SELECT_BOX_SUCCESS,
				payload: 'data',
			});

			expect(newState.updateSelectBoxText).toBe('data');

			newState = reducer(newState, {
				type: UPDATE_SELECT_BOX_SUCCESS,
				payload: 'new-data',
			});

			expect(newState.updateSelectBoxText).toBe('new-data');
		});
	});

	describe('TEMPLATE_PROCESSING_SUCCESS', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: TEMPLATE_PROCESSING_SUCCESS,
				payload: 'data',
			});

			expect(newState.templateProcessing).toBe('data');

			newState = reducer(newState, {
				type: TEMPLATE_PROCESSING_SUCCESS,
				payload: 'new-data',
			});

			expect(newState.templateProcessing).toBe('new-data');
		});
	});

	describe('TEMPLATE_PROCESSING_FAILED', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: TEMPLATE_PROCESSING_FAILED,
				payload: 'data',
			});

			expect(newState.templateProcessing).toBe('data');

			newState = reducer(newState, {
				type: TEMPLATE_PROCESSING_FAILED,
				payload: 'new-data',
			});

			expect(newState.templateProcessing).toBe('new-data');
		});
	});

	describe('CLEAR_TEMPLATE_PROCESSING', () => {
		test('check the correct state gets returned when this action runs', () => {
			newState = reducer(initialState, {
				type: CLEAR_TEMPLATE_PROCESSING,
			});

			expect(newState.templateProcessing).toBe('');

			newState = reducer(newState, { type: CLEAR_TEMPLATE_PROCESSING });

			expect(newState.templateProcessing).toBe('');
		});
	});

	describe('Template uploads', () => {
		const post = (state, id) =>
			reducer(state, {
				type: POST_TEMPLATE_UPLOAD_PROCESSING,
				payload: { file: {}, filename: `${id}.zip`, id },
			});

		const reject = (state, filename) =>
			reducer(state, {
				type: TEMPLATE_UPLOAD_REJECTED,
				payload: [{ filename, message: 'notZip' }],
			});

		const succeed = (state, id, templates = []) =>
			reducer(state, {
				type: TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
				payload: { id, templates },
			});

		test('a drop during an upload joins the batch in flight', () => {
			newState = reject(post(post(initialState, 1), 2), 'a.txt');

			expect(newState.templateUploads).toEqual([
				{ id: 1, filename: '1.zip', status: 'pending' },
				{ id: 2, filename: '2.zip', status: 'pending' },
				{ filename: 'a.txt', message: 'notZip', status: 'failed' },
			]);
		});

		test('records each result against its upload', () => {
			newState = post(post(initialState, 1), 2);
			newState = reducer(newState, {
				type: TEMPLATE_UPLOAD_PROCESSING_FAILED,
				payload: { id: 2, message: 'error' },
			});
			newState = succeed(newState, 1);

			expect(newState.templateUploads).toEqual([
				{ id: 1, filename: '1.zip', status: 'success' },
				{
					id: 2,
					filename: '2.zip',
					status: 'failed',
					message: 'error',
				},
			]);
		});

		test('starts a new batch once every upload has reported back', () => {
			newState = succeed(post(initialState, 1), 1);

			expect(post(newState, 2).templateUploads).toEqual([
				{ id: 2, filename: '2.zip', status: 'pending' },
			]);
			expect(reject(newState, 'a.txt').templateUploads).toEqual([
				{ filename: 'a.txt', message: 'notZip', status: 'failed' },
			]);
		});

		test('adds installed templates to the list and flags existing ones as updated', () => {
			const state = {
				...initialState,
				list: [{ id: 'rubix' }],
			};

			newState = succeed(post(state, 1), 1, [
				{ id: 'cellulose' },
				{ id: 'rubix' },
			]);

			expect(newState.list).toEqual([
				{ id: 'rubix', message: 'updated' },
				{ id: 'cellulose', new: true, message: 'installed' },
			]);
		});

		test('dismissing the success message drops the installed zips', () => {
			newState = reject(succeed(post(initialState, 1), 1), 'a.txt');
			newState = reject(post(newState, 2), 'b.txt');
			newState = succeed(newState, 2);
			newState = reducer(newState, {
				type: DISMISS_TEMPLATE_UPLOAD_SUCCESS,
			});

			expect(newState.templateUploads).toEqual([
				{ filename: 'b.txt', message: 'notZip', status: 'failed' },
			]);
		});

		test('closing the Template Manager keeps only the uploads in flight', () => {
			newState = reject(succeed(post(initialState, 1), 1), 'a.txt');
			newState = post(newState, 2);
			newState = reducer(newState, {
				type: CLEAR_FINISHED_TEMPLATE_UPLOADS,
			});

			expect(newState.templateUploads).toEqual([
				expect.objectContaining({ id: 2, status: 'pending' }),
			]);
		});
	});

	describe('Check state gets returned when no actions match', () => {
		test('Check the state does not change when no action matches', () => {
			const state = reducer(undefined, {
				type: 'none',
				id: 'template-id',
			});

			expect(state).toBe(initialState);
		});
	});
});
