import { fireEvent } from '@testing-library/react';
import { renderWithStore } from '../../testUtilsRTL';
import DeleteFontDialog from '../../../../../src/assets/js/react/components/FontManager/DeleteFontDialog';

describe('FontManager - DeleteFontDialog', () => {
	test('renders nothing when isOpen is false', () => {
		const { queryByRole } = renderWithStore(
			<DeleteFontDialog
				isOpen={false}
				fontName="Roboto"
				onConfirm={jest.fn()}
				onCancel={jest.fn()}
			/>
		);
		expect(queryByRole('dialog')).not.toBeInTheDocument();
	});

	test('renders a destructive confirmation dialog when open', () => {
		const { getByRole, getByText } = renderWithStore(
			<DeleteFontDialog
				isOpen
				fontName="Roboto"
				onConfirm={jest.fn()}
				onCancel={jest.fn()}
			/>
		);
		expect(getByRole('dialog')).toBeInTheDocument();
		expect(
			getByText(/this font will be removed from the site/i)
		).toBeInTheDocument();
		expect(
			getByRole('button', { name: /delete font/i })
		).toBeInTheDocument();
	});

	test('confirming dispatches onConfirm', () => {
		const onConfirm = jest.fn();
		const { getByRole } = renderWithStore(
			<DeleteFontDialog
				isOpen
				fontName="Roboto"
				onConfirm={onConfirm}
				onCancel={jest.fn()}
			/>
		);
		fireEvent.click(getByRole('button', { name: /delete font/i }));
		expect(onConfirm).toHaveBeenCalled();
	});
});
