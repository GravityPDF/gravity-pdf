import { fireEvent } from '@testing-library/react';
import { renderWithStore } from '../../testUtilsRTL';
import SaveReplaceDialog from '../../../../../src/assets/js/react/components/FontManager/SaveReplaceDialog';

describe('FontManager - SaveReplaceDialog', () => {
	test('does not render when closed', () => {
		const { queryByRole } = renderWithStore(
			<SaveReplaceDialog
				isOpen={false}
				onConfirm={jest.fn()}
				onCancel={jest.fn()}
			/>
		);
		expect(queryByRole('dialog')).not.toBeInTheDocument();
	});

	test('renders the save-replace warning when open', () => {
		const { getByRole, getByText } = renderWithStore(
			<SaveReplaceDialog
				isOpen
				onConfirm={jest.fn()}
				onCancel={jest.fn()}
			/>
		);
		expect(getByRole('dialog')).toBeInTheDocument();
		expect(
			getByText(/this will apply to all PDFs using this font/i)
		).toBeInTheDocument();
	});

	test('clicking Save changes dispatches onConfirm', () => {
		const onConfirm = jest.fn();
		const { getByRole } = renderWithStore(
			<SaveReplaceDialog
				isOpen
				onConfirm={onConfirm}
				onCancel={jest.fn()}
			/>
		);
		fireEvent.click(getByRole('button', { name: /save changes/i }));
		expect(onConfirm).toHaveBeenCalled();
	});
});
