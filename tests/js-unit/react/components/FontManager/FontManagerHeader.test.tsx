import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import FontManagerHeader from '../../../../../src/assets/js/react/components/FontManager/FontManagerHeader';

describe('FontManager - FontManagerHeader.js', () => {
	const defaultProps = {
		activeFontId: 'rubix',
		onSelectFont: jest.fn(),
		onClose: jest.fn(),
	};

	describe('RENDERS COMPONENT', () => {
		test('render <FontManagerHeader /> component', () => {
			const { container } = renderWithStore(
				<FontManagerHeader {...defaultProps} />
			);
			expect(
				findByTestAttr(container, 'component-FontManagerHeader')
			).toBeInTheDocument();
		});

		test('render <CloseDialog /> component', () => {
			const { container } = renderWithStore(
				<FontManagerHeader {...defaultProps} />
			);
			expect(
				findByTestAttr(container, 'component-CloseDialog')
			).toBeInTheDocument();
		});
	});
});
