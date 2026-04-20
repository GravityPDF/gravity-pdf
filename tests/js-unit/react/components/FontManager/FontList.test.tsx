import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import FontList from '../../../../../src/assets/js/react/components/FontManager/FontList';
import type { FontManagerState } from '../../../../../src/assets/js/react/types';

describe('FontManager - FontList.js', () => {
	const defaultProps = {
		activeFontId: '',
		onSelectFont: jest.fn(),
		hasDetailOpen: false,
	};

	/* FontListItems.componentDidMount calls document.querySelector on this element */
	beforeEach(() => {
		document.body.innerHTML = '<select id="gfpdf_settings[font]"></select>';
	});

	afterEach(() => {
		document.body.innerHTML = '';
	});

	const loadingState = {
		fontManager: {
			loading: true,
			fontList: [],
			searchResult: [],
			msg: { error: { fontList: 'error' } },
		} as unknown as FontManagerState,
	};

	describe('RENDERS COMPONENT', () => {
		test('render <FontList /> component', () => {
			const { container } = renderWithStore(
				<FontList {...defaultProps} />,
				loadingState
			);
			expect(
				findByTestAttr(container, 'component-FontList')
			).toBeInTheDocument();
		});

		test('render the font list header row', () => {
			const { container } = renderWithStore(
				<FontList {...defaultProps} />,
				loadingState
			);
			const header = container.querySelector('.font-list-header');
			expect(header).toBeInTheDocument();
			expect(header!.querySelector('.font-name')!.textContent).toBe(
				'Installed Fonts'
			);
		});

		test('render the skeleton placeholder when loading', () => {
			const { container } = renderWithStore(
				<FontList {...defaultProps} />,
				loadingState
			);
			expect(
				container.querySelector('.font-list-items-skeleton')
			).toBeInTheDocument();
			expect(
				container.querySelectorAll(
					'.font-list-items-skeleton .font-list-item'
				)
			).toHaveLength(8);
		});

		test('render <FontListItems /> when not loading', () => {
			const { container } = renderWithStore(
				<FontList {...defaultProps} />,
				{
					fontManager: {
						loading: false,
						fontList: [],
						searchResult: [],
						msg: { error: { fontList: 'error' } },
					} as unknown as FontManagerState,
				}
			);
			expect(
				findByTestAttr(container, 'component-FontListItems')
			).toBeInTheDocument();
		});

		test('render <FontListAlertMessage /> component', () => {
			const { container } = renderWithStore(
				<FontList {...defaultProps} />,
				loadingState
			);
			expect(
				findByTestAttr(container, 'component-FontListAlertMessage')
			).toBeInTheDocument();
		});
	});
});
