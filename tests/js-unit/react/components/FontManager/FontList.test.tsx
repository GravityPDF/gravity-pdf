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

		test('render <FontListHeader /> component', () => {
			const { container } = renderWithStore(
				<FontList {...defaultProps} />,
				loadingState
			);
			expect(
				findByTestAttr(container, 'component-FontListHeader')
			).toBeInTheDocument();
		});

		test('render <FontListSkeleton /> when loading', () => {
			const { container } = renderWithStore(
				<FontList {...defaultProps} />,
				loadingState
			);
			expect(
				findByTestAttr(container, 'component-FontListSkeleton')
			).toBeInTheDocument();
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
