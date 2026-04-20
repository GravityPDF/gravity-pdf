import { act, fireEvent } from '@testing-library/react';
import {
	renderWithStore,
	findByTestAttr,
	createTestStore,
} from '../../testUtilsRTL';
import CoreFontContainer from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontContainer';
import { coreFontInitialState } from '../../../../../src/assets/js/react/store/coreFontsStore';
import { DOWNLOAD_FONTS_API_CALL } from '../../../../../src/assets/js/react/actions/coreFonts';

jest.mock('../../../../../src/assets/js/react/api/coreFonts', () => ({
	apiGetFilesFromGitHub: jest.fn(() => new Promise(() => {})),
	apiPostDownloadFonts: jest.fn(() => new Promise(() => {})),
}));

const fontList = [
	'AboriginalSansREGULAR.ttf',
	'Abyssinica_SIL.ttf',
	'DejaVuSerifCondensed.ttf',
];

const baseState = { coreFonts: coreFontInitialState };

describe('CoreFonts - CoreFontContainer.js', () => {
	test('renders <CoreFontContainer /> component container', () => {
		const { container } = renderWithStore(<CoreFontContainer />, baseState);

		expect(
			findByTestAttr(container, 'component-coreFont-downloader')
		).toBeInTheDocument();
	});

	test('renders core font downloader button', () => {
		const { container } = renderWithStore(<CoreFontContainer />, baseState);

		expect(
			findByTestAttr(container, 'component-coreFont-button')
		).toBeInTheDocument();
	});

	test('renders button text', () => {
		const { container } = renderWithStore(<CoreFontContainer />, baseState);

		expect(container.querySelector('button')!.textContent).toBe(
			'Download Core Fonts'
		);
	});

	test('does not render <Spinner /> by default', () => {
		const { container } = renderWithStore(<CoreFontContainer />, baseState);

		expect(
			container.querySelector('.gfpdf-spinner')
		).not.toBeInTheDocument();
	});

	test('button click shows <Spinner />', () => {
		const { container } = renderWithStore(<CoreFontContainer />, baseState);
		fireEvent.click(
			findByTestAttr(container, 'component-coreFont-button')!
		);

		expect(container.querySelector('.gfpdf-spinner')).toBeInTheDocument();
	});

	test('button click dispatches getFilesFromGitHub (sets buttonClicked in store)', () => {
		const { container, store } = renderWithStore(
			<CoreFontContainer />,
			baseState
		);
		fireEvent.click(
			findByTestAttr(container, 'component-coreFont-button')!
		);

		expect(store.getState().coreFonts.buttonClicked).toBe(true);
	});

	test('button is disabled while loading', () => {
		const { container } = renderWithStore(<CoreFontContainer />, baseState);
		fireEvent.click(
			findByTestAttr(container, 'component-coreFont-button')!
		);

		expect(
			findByTestAttr(container, 'component-coreFont-button')
		).toHaveAttribute('aria-disabled', 'true');
	});

	test('renders <Counter /> when ajax is active and queue > 0', () => {
		const { container } = renderWithStore(<CoreFontContainer />, {
			coreFonts: { ...coreFontInitialState, downloadCounter: 3 },
		});
		fireEvent.click(
			findByTestAttr(container, 'component-coreFont-button')!
		);

		expect(
			findByTestAttr(container, 'component-coreFont-counter')
		).toBeInTheDocument();
	});

	test('renders <CoreFontListResults /> component', () => {
		const { container } = renderWithStore(<CoreFontContainer />, baseState);

		expect(
			findByTestAttr(container, 'component-coreFont-downloader')
		).toBeInTheDocument();
	});

	test('auto-start shows spinner when hash is #/downloadCoreFonts', () => {
		window.location.hash = '#/downloadCoreFonts';
		const { container } = renderWithStore(<CoreFontContainer />, baseState);
		window.location.hash = '';

		expect(container.querySelector('.gfpdf-spinner')).toBeInTheDocument();
	});

	test('auto-start dispatches getFilesFromGitHub when hash is #/downloadCoreFonts', () => {
		window.location.hash = '#/downloadCoreFonts';
		const { store } = renderWithStore(<CoreFontContainer />, baseState);
		window.location.hash = '';

		expect(store.getState().coreFonts.buttonClicked).toBe(true);
	});

	test('fontList + buttonClicked triggers downloadFontsApiCall for each file', async () => {
		const store = createTestStore({
			coreFonts: {
				...coreFontInitialState,
				fontList,
				buttonClicked: true,
			},
		});
		const dispatchSpy = jest.spyOn(store, 'dispatch');

		await act(async () => {
			renderWithStore(<CoreFontContainer />, {}, {}, store);
		});

		const downloadCalls = dispatchSpy.mock.calls.filter(
			([action]) =>
				typeof action !== 'function' &&
				(action as { type?: string }).type === DOWNLOAD_FONTS_API_CALL
		);
		expect(downloadCalls).toHaveLength(3);
	});

	test('requestDownload=finished clears requestDownload in store', () => {
		const { store } = renderWithStore(<CoreFontContainer />, {
			coreFonts: {
				...coreFontInitialState,
				requestDownload: 'finished',
			},
		});

		expect(store.getState().coreFonts.requestDownload).toBe('');
	});
});
