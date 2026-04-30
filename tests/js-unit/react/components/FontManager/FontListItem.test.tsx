import { fireEvent } from '@testing-library/react';
import { renderWithStore } from '../../testUtilsRTL';
import FontListItem from '../../../../../src/assets/js/react/components/FontManager/FontListItem';
import type { FontItem } from '../../../../../src/assets/js/react/types';

const sample: FontItem = {
	id: 'roboto',
	font_name: 'Roboto',
	regular: 'Roboto-Regular.ttf',
	italics: '',
	bold: '',
	bolditalics: '',
};

const baseProps = {
	font: sample,
	displayName: 'Roboto',
	isEditing: false,
	isActive: false,
	isDeleting: false,
	itemId: 'gfpdf-fm-row-roboto',
	onSelect: jest.fn(),
	onRequestDelete: jest.fn(),
	onRequestDeleteKeypress: jest.fn(),
};

describe('FontManager - FontListItem', () => {
	test('renders the font name and 1/4 variants', () => {
		const { getByRole, getByText } = renderWithStore(
			<FontListItem {...baseProps} />
		);
		expect(getByRole('option', { name: /roboto/i })).toBeInTheDocument();
		expect(getByText('1/4 variants')).toBeInTheDocument();
	});

	test('aria-selected reflects isEditing', () => {
		const { getByRole } = renderWithStore(
			<FontListItem {...baseProps} isEditing />
		);
		expect(getByRole('option')).toHaveAttribute('aria-selected', 'true');
	});

	test('active rows announce themselves to AT', () => {
		const { getByRole } = renderWithStore(
			<FontListItem {...baseProps} isActive />
		);
		expect(getByRole('option')).toHaveAttribute(
			'aria-label',
			expect.stringMatching(/active font/i)
		);
	});

	test('clicking the row dispatches onSelect', () => {
		const onSelect = jest.fn();
		const { getByRole } = renderWithStore(
			<FontListItem {...baseProps} onSelect={onSelect} />
		);
		fireEvent.click(getByRole('option'));
		expect(onSelect).toHaveBeenCalled();
	});

	test('clicking the trash icon does NOT bubble to onSelect', () => {
		const onSelect = jest.fn();
		const onRequestDelete = jest.fn();
		const { getByRole } = renderWithStore(
			<FontListItem
				{...baseProps}
				onSelect={onSelect}
				onRequestDelete={onRequestDelete}
			/>
		);
		fireEvent.click(getByRole('button', { name: /delete roboto/i }));
		expect(onRequestDelete).toHaveBeenCalled();
		expect(onSelect).not.toHaveBeenCalled();
	});
});
