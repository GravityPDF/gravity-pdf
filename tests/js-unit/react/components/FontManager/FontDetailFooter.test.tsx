import { fireEvent } from '@testing-library/react';
import { renderWithStore } from '../../testUtilsRTL';
import FontDetailFooter from '../../../../../src/assets/js/react/components/FontManager/FontDetailFooter';

const baseProps = {
	isDraft: false,
	canDelete: true,
	canSave: true,
	canCancel: true,
	saving: false,
	onSave: jest.fn(),
	onCancel: jest.fn(),
	onRequestDelete: jest.fn(),
};

describe('FontManager - FontDetailFooter', () => {
	test('shows "Add font" button label when isDraft is true', () => {
		const { getByRole } = renderWithStore(
			<FontDetailFooter {...baseProps} isDraft canDelete={false} />
		);
		expect(getByRole('button', { name: /add font/i })).toBeInTheDocument();
	});

	test('shows "Save changes" when isDraft is false', () => {
		const { getByRole } = renderWithStore(
			<FontDetailFooter {...baseProps} />
		);
		expect(
			getByRole('button', { name: /save changes/i })
		).toBeInTheDocument();
	});

	test('disables save when canSave is false', () => {
		const { getByRole } = renderWithStore(
			<FontDetailFooter {...baseProps} canSave={false} />
		);
		expect(getByRole('button', { name: /save changes/i })).toBeDisabled();
	});

	test('clicking Cancel dispatches onCancel', () => {
		const onCancel = jest.fn();
		const { getByRole } = renderWithStore(
			<FontDetailFooter {...baseProps} onCancel={onCancel} />
		);
		fireEvent.click(getByRole('button', { name: /cancel/i }));
		expect(onCancel).toHaveBeenCalled();
	});

	test('does not render delete when canDelete is false', () => {
		const { queryByRole } = renderWithStore(
			<FontDetailFooter {...baseProps} canDelete={false} />
		);
		expect(
			queryByRole('button', { name: /delete font/i })
		).not.toBeInTheDocument();
	});

	test('clicking delete dispatches onRequestDelete', () => {
		const onRequestDelete = jest.fn();
		const { getByRole } = renderWithStore(
			<FontDetailFooter
				{...baseProps}
				onRequestDelete={onRequestDelete}
			/>
		);
		fireEvent.click(getByRole('button', { name: /delete font/i }));
		expect(onRequestDelete).toHaveBeenCalled();
	});
});
