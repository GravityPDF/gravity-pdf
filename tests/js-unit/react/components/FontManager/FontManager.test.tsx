import { renderWithStore } from '../../testUtilsRTL';
import FontManager from '../../../../../src/assets/js/react/components/FontManager/FontManager';

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

/* Modal portals into document.body, so queries must run against `document`, not the render container */
const findInDocument = (val: string) =>
	document.querySelector(`[data-test="${val}"]`);

describe('FontManager - FontManager.js', () => {
	const onSelectFont = jest.fn();
	const onClose = jest.fn();

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

	describe('RENDERS COMPONENT', () => {
		test('render <FontManager /> component', () => {
			renderWithStore(
				<FontManager
					activeFontId=""
					onSelectFont={onSelectFont}
					onClose={onClose}
				/>,
				initialState
			);
			expect(findInDocument('component-FontManager')).toBeInTheDocument();
		});

		test('render <FontManagerBody /> component', () => {
			renderWithStore(
				<FontManager
					activeFontId=""
					onSelectFont={onSelectFont}
					onClose={onClose}
				/>,
				initialState
			);
			expect(
				findInDocument('component-FontManagerBody')
			).toBeInTheDocument();
		});

		test('renders Modal with Font Manager title', () => {
			renderWithStore(
				<FontManager
					activeFontId=""
					onSelectFont={onSelectFont}
					onClose={onClose}
				/>,
				initialState
			);
			expect(document.body.textContent).toContain('Font Manager');
		});
	});
});
