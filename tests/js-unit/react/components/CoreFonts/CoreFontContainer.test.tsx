import { act, fireEvent } from '@testing-library/react';
import {
	renderWithRouter,
	findByTestAttr,
	createTestStore,
} from '../../testUtilsRTL';
import CoreFontContainer from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontContainer';
import { initialState as coreFontInitialState } from '../../../../../src/assets/js/react/reducers/coreFontReducer';
import { DOWNLOAD_FONTS_API_CALL } from '../../../../../src/assets/js/react/actions/coreFonts';

jest.mock('../../../../../src/assets/js/react/api/coreFonts', () => ({
	apiGetFilesFromGitHub: jest.fn().mockResolvedValue({
		ok: true,
		status: 200,
		text: '[]',
		body: [],
	}),
	apiPostDownloadFonts: jest.fn().mockResolvedValue({
		ok: true,
		status: 200,
		text: '[]',
		body: null,
	}),
}));

const fontList = [
	'AboriginalSansREGULAR.ttf',
	'Abyssinica_SIL.ttf',
	'DejaVuSerifCondensed.ttf',
];

const baseState = { coreFonts: coreFontInitialState };

describe('CoreFonts - CoreFontContainer.js', () => {
	test('renders <CoreFontContainer /> component container', () => {
		const { container } = renderWithRouter(<CoreFontContainer />, {
			initialState: baseState,
		});

		expect(
			findByTestAttr(container, 'component-coreFont-downloader')
		).toBeInTheDocument();
	});

	test('renders core font downloader button', () => {
		const { container } = renderWithRouter(<CoreFontContainer />, {
			initialState: baseState,
		});

		expect(
			findByTestAttr(container, 'component-coreFont-button')
		).toBeInTheDocument();
	});

	test('renders button text', () => {
		const { container } = renderWithRouter(
			<CoreFontContainer buttonText="Download Core Fonts" />,
			{ initialState: baseState }
		);

		expect(container.querySelector('button')!.textContent).toBe(
			'Download Core Fonts'
		);
	});

	test('does not render <Spinner /> by default', () => {
		const { container } = renderWithRouter(<CoreFontContainer />, {
			initialState: baseState,
		});

		expect(
			container.querySelector('.gfpdf-spinner')
		).not.toBeInTheDocument();
	});

	test('button click shows <Spinner />', () => {
		const { container } = renderWithRouter(<CoreFontContainer />, {
			initialState: baseState,
		});
		fireEvent.click(
			findByTestAttr(container, 'component-coreFont-button')!
		);

		expect(container.querySelector('.gfpdf-spinner')).toBeInTheDocument();
	});

	test('button click dispatches getFilesFromGitHub (sets buttonClicked in store)', () => {
		const { container, store } = renderWithRouter(<CoreFontContainer />, {
			initialState: baseState,
		});
		fireEvent.click(
			findByTestAttr(container, 'component-coreFont-button')!
		);

		expect(store.getState().coreFonts.buttonClicked).toBe(true);
	});

	test('button is disabled while loading', () => {
		const { container } = renderWithRouter(<CoreFontContainer />, {
			initialState: baseState,
		});
		fireEvent.click(
			findByTestAttr(container, 'component-coreFont-button')!
		);

		expect(
			findByTestAttr(container, 'component-coreFont-button')
		).toBeDisabled();
	});

	test('renders <Counter /> when ajax is active and queue > 0', () => {
		const { container } = renderWithRouter(
			<CoreFontContainer counterText="Remaining:" />,
			{
				initialState: {
					coreFonts: { ...coreFontInitialState, downloadCounter: 3 },
				},
			}
		);
		fireEvent.click(
			findByTestAttr(container, 'component-coreFont-button')!
		);

		expect(
			findByTestAttr(container, 'component-coreFont-counter')
		).toBeInTheDocument();
	});

	test('renders <CoreFontListResults /> component', () => {
		const { container } = renderWithRouter(<CoreFontContainer />, {
			initialState: baseState,
		});

		expect(
			findByTestAttr(container, 'component-coreFont-downloader')
		).toBeInTheDocument();
	});

	test('/downloadCoreFonts route shows spinner on mount', () => {
		const { container } = renderWithRouter(<CoreFontContainer />, {
			route: '/downloadCoreFonts',
			initialState: baseState,
		});

		expect(container.querySelector('.gfpdf-spinner')).toBeInTheDocument();
	});

	test('/downloadCoreFonts route dispatches getFilesFromGitHub on mount', () => {
		const { store } = renderWithRouter(<CoreFontContainer />, {
			route: '/downloadCoreFonts',
			initialState: baseState,
		});

		expect(store.getState().coreFonts.buttonClicked).toBe(true);
	});

	test('fontList + buttonClicked triggers downloadFontsApiCall for each file', () => {
		jest.useFakeTimers();
		const store = createTestStore({
			coreFonts: {
				...coreFontInitialState,
				fontList,
				buttonClicked: true,
			},
		});
		const dispatchSpy = jest.spyOn(store, 'dispatch');

		renderWithRouter(<CoreFontContainer />, { store });

		act(() => {
			jest.runAllTimers();
		});

		const downloadCalls = dispatchSpy.mock.calls.filter(
			([action]) =>
				typeof action !== 'function' &&
				action.type === DOWNLOAD_FONTS_API_CALL
		);
		expect(downloadCalls).toHaveLength(3);

		jest.useRealTimers();
	});

	test('requestDownload=finished clears requestDownload in store', () => {
		const { store } = renderWithRouter(<CoreFontContainer />, {
			initialState: {
				coreFonts: {
					...coreFontInitialState,
					requestDownload: 'finished',
				},
			},
		});

		expect(store.getState().coreFonts.requestDownload).toBe('');
	});
});
