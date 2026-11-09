import { call, put, takeLatest } from 'redux-saga/effects';
import {
	watchGetCustomFontList,
	getCustomFontList,
	watchAddFont,
	addFont,
	watchEditFont,
	editFont,
	watchDeleteFont,
	deleteFont,
	watchInstallFont,
	installFont,
	getFontUsage,
	watchGetFontUsage,
} from '../../../../src/assets/js/react/sagas/fontManager';
import {
	GET_CUSTOM_FONT_LIST,
	GET_CUSTOM_FONT_LIST_SUCCESS,
	GET_CUSTOM_FONT_LIST_ERROR,
	GET_FONT_USAGE_SUCCESS,
	GET_FONT_USAGE_ERROR,
	ADD_FONT,
	ADD_FONT_SUCCESS,
	ADD_FONT_ERROR,
	EDIT_FONT,
	EDIT_FONT_SUCCESS,
	EDIT_FONT_ERROR,
	DELETE_FONT,
	DELETE_FONT_SUCCESS,
	DELETE_FONT_ERROR,
	INSTALL_FONT,
	INSTALL_FONT_SUCCESS,
	INSTALL_FONT_ERROR,
} from '../../../../src/assets/js/react/actions/fontManager';
import * as api from '../../../../src/assets/js/react/api/fontManager';

describe('Sagas - fontManager.js', () => {
	describe('Font usage', () => {
		test('loads once the font list has loaded', () => {
			expect(watchGetFontUsage().next().value).toEqual(
				takeLatest(GET_CUSTOM_FONT_LIST_SUCCESS, getFontUsage)
			);
		});

		test('passes on the reasons, or an error', () => {
			const gen = getFontUsage();

			expect(gen.next().value).toEqual(call(api.apiGetFontUsage));
			expect(gen.next({ ok: true, body: { a: ['b'] } }).value).toEqual(
				put({ type: GET_FONT_USAGE_SUCCESS, payload: { a: ['b'] } })
			);

			const failed = getFontUsage();
			failed.next();
			expect(failed.next({ ok: false }).value).toEqual(
				put({
					type: GET_FONT_USAGE_ERROR,
					payload:
						'A problem occurred. Reload the page and try again.',
				})
			);
		});
	});

	describe('Watcher saga - watchGetCustomFontList()', () => {
		const gen = watchGetCustomFontList();

		test('should call GET_CUSTOM_FONT_LIST action and load up the getCustomFontList saga', () => {
			expect(gen.next().value).toEqual(
				takeLatest(GET_CUSTOM_FONT_LIST, getCustomFontList)
			);
		});
	});

	describe('Worker saga - getCustomFontList()', () => {
		const response = {
			ok: true,
			body: [],
		};
		const responseBody = response.body;
		const gen = getCustomFontList();

		test('should check that saga call the API apiGetCustomFontList', () => {
			expect(gen.next().value).toEqual(call(api.apiGetCustomFontList));

			expect(gen.next(response).value).toEqual(
				put({
					type: GET_CUSTOM_FONT_LIST_SUCCESS,
					payload: responseBody,
				})
			);
		});

		test('should check that saga handles correctly the failure of getCustomFontList API call', () => {
			expect(gen.throw().value).toEqual(
				put({
					type: GET_CUSTOM_FONT_LIST_ERROR,
					payload:
						'A problem occurred. Reload the page and try again.',
				})
			);
		});
	});

	describe('Watcher saga - watchAddFont()', () => {
		const gen = watchAddFont();

		test('should call ADD_FONT action and load up the addFont saga', () => {
			expect(gen.next().value).toEqual(takeLatest(ADD_FONT, addFont));
		});
	});

	describe('Worker saga - addFont()', () => {
		const response = {
			ok: true,
			body: {},
		};
		const responseBody = response.body;
		const data = { payload: {} };
		const gen = addFont(data);

		test('should check that saga call the API apiAddFont', () => {
			expect(gen.next().value).toEqual(call(api.apiAddFont, {}));

			expect(gen.next(response).value).toEqual(
				put({
					type: ADD_FONT_SUCCESS,
					payload: {
						font: responseBody,
						msg: '<strong>Your font has been saved.</strong>',
					},
				})
			);
		});

		test('should check that saga handles correctly the failure of apiAddFont API call (500 error)', () => {
			expect(gen.throw({ status: 500 }).value).toEqual(
				put({
					type: ADD_FONT_ERROR,
					payload:
						'A problem occurred. Reload the page and try again.',
				})
			);
		});

		test("should check that saga handles correctly the failure of apiAddFont API call (400 error 'font_validation_error')", () => {
			const gen2 = addFont(data);

			gen2.next();

			expect(
				gen2.throw({
					status: 400,
					body: {
						code: 'font_validation_error',
						message: 'text',
					},
				}).value
			).toEqual(
				put({
					type: ADD_FONT_ERROR,
					payload: {
						fontValidationError:
							'Font file(s) are malformed and cannot be used with Gravity PDF',
						msg: 'text',
					},
				})
			);
		});

		test('should check that saga handles correctly the failure of apiAddFont API call', () => {
			const gen2 = addFont(data);

			gen2.next();

			expect(
				gen2.throw({
					status: 400,
					body: { message: 'text' },
				}).value
			).toEqual(
				put({
					type: ADD_FONT_ERROR,
					payload: 'text',
				})
			);
		});
	});

	describe('Watcher saga - watchEditFont()', () => {
		const gen = watchEditFont();

		test('should call EDIT_FONT action and load up the editFont saga', () => {
			expect(gen.next().value).toEqual(takeLatest(EDIT_FONT, editFont));
		});
	});

	describe('Worker saga - editFont()', () => {
		const response = {
			ok: true,
			body: {},
		};
		const responseBody = response.body;
		const data = { payload: {} };
		const gen = editFont(data);

		test('should check that saga call the API apiEditFont', () => {
			expect(gen.next().value).toEqual(call(api.apiEditFont, {}));

			expect(gen.next(response).value).toEqual(
				put({
					type: EDIT_FONT_SUCCESS,
					payload: {
						font: responseBody,
						msg: '<strong>Your font has been saved.</strong>',
					},
				})
			);
		});

		test("should check that saga handles correctly the failure of apiEditFont API call (500 error and response.code not equal to 'font_file_gone_missing')", () => {
			expect(
				gen.throw({
					status: 500,
					body: { code: '' },
				}).value
			).toEqual(
				put({
					type: EDIT_FONT_ERROR,
					payload:
						'A problem occurred. Reload the page and try again.',
				})
			);
		});

		test("should check that saga handles correctly the failure of apiEditFont API call (400 error 'font_validation_error')", () => {
			const gen2 = editFont(data);

			gen2.next();

			expect(
				gen2.throw({
					status: 400,
					body: {
						code: 'font_validation_error',
						message: 'text',
					},
				}).value
			).toEqual(
				put({
					type: EDIT_FONT_ERROR,
					payload: {
						fontValidationError:
							'Font file(s) are malformed and cannot be used with Gravity PDF',
						msg: 'text',
					},
				})
			);
		});

		test('should check that saga handles correctly the failure of apiEditFont API call (fatal error)', () => {
			const gen2 = editFont(data);

			gen2.next();

			expect(
				gen2.throw({
					status: 400,
					body: { message: '' },
				}).value
			).toEqual(
				put({
					type: EDIT_FONT_ERROR,
					payload:
						'A problem occurred. Reload the page and try again.',
				})
			);
		});

		test('should check that saga handles correctly the failure of apiEditFont API call (response message)', () => {
			const gen2 = editFont(data);

			gen2.next();

			expect(
				gen2.throw({
					status: 400,
					body: { message: 'text' },
				}).value
			).toEqual(
				put({
					type: EDIT_FONT_ERROR,
					payload: 'text',
				})
			);
		});
	});

	describe('Watcher saga - watchDeleteFont()', () => {
		const gen = watchDeleteFont();

		test('should call DELETE_FONT action and load up the deleteFont saga', () => {
			expect(gen.next().value).toEqual(
				takeLatest(DELETE_FONT, deleteFont)
			);
		});
	});

	describe('Worker saga - deleteFont()', () => {
		const response = {
			ok: true,
			body: {},
		};
		const data = { payload: {}, fontType: 'core' };
		const gen = deleteFont(data);

		test('should check that saga call the API apiDeleteFont', () => {
			expect(gen.next().value).toEqual(
				call(api.apiDeleteFont, {}, 'core')
			);

			expect(gen.next(response).value).toEqual(
				put({
					type: DELETE_FONT_SUCCESS,
					payload: {},
				})
			);
		});

		test('should check that saga handles correctly the failure of apiDeleteFont API call', () => {
			expect(gen.throw().value).toEqual(
				put({
					type: DELETE_FONT_ERROR,
					payload:
						'A problem occurred. Reload the page and try again.',
				})
			);
		});

		test("should show the server's reason when the delete is refused", () => {
			const refused = deleteFont({ payload: 'brand' });
			refused.next();

			expect(
				refused.throw({
					status: 409,
					body: { message: 'The font is in use' },
				}).value
			).toEqual(
				put({
					type: DELETE_FONT_ERROR,
					payload: 'The font is in use',
				})
			);
		});
	});

	describe('Installing a font', () => {
		test('runs on each request', () => {
			expect(watchInstallFont().next().value).toEqual(
				takeLatest(INSTALL_FONT, installFont)
			);
		});

		test('passes on the font row', () => {
			const gen = installFont({ payload: 'garuda' });
			const font = { id: 'garuda', type: 'core' };

			expect(gen.next().value).toEqual(
				call(api.apiInstallFont, 'garuda')
			);
			expect(gen.next({ ok: true, body: font }).value).toEqual(
				put({ type: INSTALL_FONT_SUCCESS, payload: font })
			);
		});

		test('shows the server reason, or a general error', () => {
			const fail = (response) => {
				const gen = installFont({ payload: 'garuda' });

				gen.next();

				return gen.next(response).value;
			};

			expect(fail({ ok: false, body: { message: 'No' } })).toEqual(
				put({ type: INSTALL_FONT_ERROR, payload: 'No' })
			);
			expect(fail({ ok: false, body: {} })).toEqual(
				put({ type: INSTALL_FONT_ERROR, payload: GFPDF.addFatalError })
			);
		});
	});
});
