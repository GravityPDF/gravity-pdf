import { findByTestAttr, renderWithRouter } from '../../testUtilsRTL';
import FontManager from '../../../../../src/assets/js/react/components/FontManager/FontManager';

jest.mock(
	'../../../../../src/assets/js/react/components/FontManager/FontManagerHeader',
	() =>
		function FontManagerHeader() {
			return <div data-test="component-FontManagerHeader" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/FontManager/FontManagerBody',
	() =>
		function FontManagerBody() {
			return <div data-test="component-FontManagerBody" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/utilities/FontManager/associatedFontManagerSelectBox',
	() => ({
		associatedFontManagerSelectBox: jest.fn(),
	})
);

describe('FontManager - FontManager.js', () => {
	const navigate = jest.fn();
	const params = { id: undefined };

	const initialState = {
		fontManager: {
			loading: false,
			addFontLoading: false,
			deleteFontLoading: false,
			fontList: [],
			searchResult: null,
			selectedFont: '',
			msg: {},
		},
	};

	afterEach(() => {
		jest.restoreAllMocks();
	});

	describe('RUN LIFECYCLE METHODS', () => {
		test('componentDidMount() - adds focus event listener to document', () => {
			const addEventListenerSpy = jest.spyOn(
				document,
				'addEventListener'
			);

			renderWithRouter(
				<FontManager navigate={navigate} params={params} />,
				{ initialState }
			);

			expect(addEventListenerSpy).toHaveBeenCalledWith(
				'focus',
				expect.any(Function),
				true
			);
		});

		test('componentWillUnmount() - removes focus event listener from document', () => {
			const removeEventListenerSpy = jest.spyOn(
				document,
				'removeEventListener'
			);

			const { unmount } = renderWithRouter(
				<FontManager navigate={navigate} params={params} />,
				{ initialState }
			);

			unmount();

			expect(removeEventListenerSpy).toHaveBeenCalledWith(
				'focus',
				expect.any(Function),
				true
			);
		});
	});

	describe('RENDERS COMPONENT', () => {
		test('render <FontManager /> component', () => {
			const { container } = renderWithRouter(
				<FontManager navigate={navigate} params={params} />,
				{ initialState }
			);
			expect(
				findByTestAttr(container, 'component-FontManager')
			).toBeInTheDocument();
		});

		test('render <FontManagerHeader /> component', () => {
			const { container } = renderWithRouter(
				<FontManager navigate={navigate} params={params} />,
				{ initialState }
			);
			expect(
				findByTestAttr(container, 'component-FontManagerHeader')
			).toBeInTheDocument();
		});

		test('render <FontManagerBody /> component', () => {
			const { container } = renderWithRouter(
				<FontManager navigate={navigate} params={params} />,
				{ initialState }
			);
			expect(
				findByTestAttr(container, 'component-FontManagerBody')
			).toBeInTheDocument();
		});
	});
});
