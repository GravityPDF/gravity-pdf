import { call, put, takeLatest } from 'redux-saga/effects';
import {
	watchGetDownloadedFonts,
	getDownloadedFonts,
	watchDeleteDownloadedFont,
	deleteDownloadedFont,
} from '../../../../src/assets/js/react/sagas/downloadedFonts';
import {
	GET_DOWNLOADED_FONTS,
	GET_DOWNLOADED_FONTS_SUCCESS,
	GET_DOWNLOADED_FONTS_ERROR,
	DELETE_DOWNLOADED_FONT,
	DELETE_DOWNLOADED_FONT_SUCCESS,
	DELETE_DOWNLOADED_FONT_ERROR,
} from '../../../../src/assets/js/react/actions/downloadedFonts';
import * as api from '../../../../src/assets/js/react/api/downloadedFonts';

describe('Sagas - downloadedFonts.js', () => {
	beforeAll(() => {
		window.GFPDF.fontManagerDownloadedDeleted = '%s deleted.';
	});

	test('watchGetDownloadedFonts() takes the latest list request', () => {
		expect(watchGetDownloadedFonts().next().value).toEqual(
			takeLatest(GET_DOWNLOADED_FONTS, getDownloadedFonts)
		);
	});

	test('watchDeleteDownloadedFont() takes the latest delete', () => {
		expect(watchDeleteDownloadedFont().next().value).toEqual(
			takeLatest(DELETE_DOWNLOADED_FONT, deleteDownloadedFont)
		);
	});

	test('getDownloadedFonts() stores the list', () => {
		const body = { fonts: [], can_delete: true };
		const gen = getDownloadedFonts();

		expect(gen.next().value).toEqual(call(api.apiGetDownloadedFonts));
		expect(gen.next({ ok: true, body }).value).toEqual(
			put({ type: GET_DOWNLOADED_FONTS_SUCCESS, payload: body })
		);
	});

	test('getDownloadedFonts() reports a failed request', () => {
		const gen = getDownloadedFonts();
		gen.next();

		expect(gen.next({ ok: false, status: 500 }).value).toEqual(
			put({
				type: GET_DOWNLOADED_FONTS_ERROR,
				payload: GFPDF.addFatalError,
			})
		);
	});

	const deleting = () => {
		const gen = deleteDownloadedFont({
			payload: { id: 'garuda', label: 'Garuda' },
		});

		expect(gen.next().value).toEqual(
			call(api.apiDeleteDownloadedFont, 'garuda')
		);

		return gen;
	};

	test('deleteDownloadedFont() reports the deleted font', () => {
		expect(deleting().next({ ok: true, body: {} }).value).toEqual(
			put({
				type: DELETE_DOWNLOADED_FONT_SUCCESS,
				payload: { id: 'garuda', msg: 'Garuda deleted.' },
			})
		);
	});

	test.each([409, 404])(
		'deleteDownloadedFont() shows why the server refused a %d',
		(status) => {
			expect(
				deleting().next({
					ok: false,
					status,
					body: { message: 'The font is in use' },
				}).value
			).toEqual(
				put({
					type: DELETE_DOWNLOADED_FONT_ERROR,
					payload: 'The font is in use',
				})
			);
		}
	);

	test('deleteDownloadedFont() reports any other failure', () => {
		expect(
			deleting().next({
				ok: false,
				status: 403,
				body: { message: 'Forbidden' },
			}).value
		).toEqual(
			put({
				type: DELETE_DOWNLOADED_FONT_ERROR,
				payload: GFPDF.addFatalError,
			})
		);
	});
});
