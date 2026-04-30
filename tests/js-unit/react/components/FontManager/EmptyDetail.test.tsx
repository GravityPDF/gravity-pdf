import { fireEvent } from '@testing-library/react';
import { renderWithStore, findByTestAttr } from '../../testUtilsRTL';
import EmptyDetail from '../../../../../src/assets/js/react/components/FontManager/EmptyDetail';

describe('FontManager - EmptyDetail', () => {
	test('renders the placeholder content', () => {
		const { container } = renderWithStore(
			<EmptyDetail onAddFont={jest.fn()} />
		);
		expect(
			findByTestAttr(container, 'component-EmptyDetail')
		).toBeInTheDocument();
	});

	test('clicking the Add new font button calls onAddFont', () => {
		const onAddFont = jest.fn();
		const { getByRole } = renderWithStore(
			<EmptyDetail onAddFont={onAddFont} />
		);
		fireEvent.click(getByRole('button', { name: /add new font/i }));
		expect(onAddFont).toHaveBeenCalled();
	});
});
