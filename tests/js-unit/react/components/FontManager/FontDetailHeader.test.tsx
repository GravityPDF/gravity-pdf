import { fireEvent } from '@testing-library/react';
import { renderWithStore } from '../../testUtilsRTL';
import FontDetailHeader from '../../../../../src/assets/js/react/components/FontManager/FontDetailHeader';

const baseProps = {
	mode: 'edit' as const,
	isActive: false,
	canSetActive: true,
	dirty: false,
	onSetActive: jest.fn(),
	onMobileBack: jest.fn(),
};

describe('FontManager - FontDetailHeader', () => {
	test('renders the "Edit font" title for mode="edit"', () => {
		const { getByText } = renderWithStore(
			<FontDetailHeader {...baseProps} />
		);
		expect(getByText('Edit font')).toBeInTheDocument();
	});

	test('renders the "Add font" title for mode="add"', () => {
		const { getByText } = renderWithStore(
			<FontDetailHeader {...baseProps} mode="add" />
		);
		expect(getByText('Add font')).toBeInTheDocument();
	});

	test('shows Set as active button when canSetActive=true and not active', () => {
		const onSetActive = jest.fn();
		const { getByRole } = renderWithStore(
			<FontDetailHeader {...baseProps} onSetActive={onSetActive} />
		);
		const button = getByRole('button', { name: /set as active/i });
		fireEvent.click(button);
		expect(onSetActive).toHaveBeenCalled();
	});

	test('replaces the button with the Active indicator when isActive=true', () => {
		const { queryByRole, getByText } = renderWithStore(
			<FontDetailHeader {...baseProps} isActive />
		);
		expect(queryByRole('button', { name: /set as active/i })).toBeNull();
		expect(getByText('Active font')).toBeInTheDocument();
	});

	test('hides the Set as active button when canSetActive=false (e.g. dirty form or no Regular)', () => {
		const { queryByRole } = renderWithStore(
			<FontDetailHeader {...baseProps} canSetActive={false} />
		);
		expect(queryByRole('button', { name: /set as active/i })).toBeNull();
	});

	test('mobile back button announces the dirty state via aria-label', () => {
		const { getByRole, rerender } = renderWithStore(
			<FontDetailHeader {...baseProps} dirty={false} />
		);
		expect(
			getByRole('button', { name: 'Back to font list' })
		).toBeInTheDocument();
		rerender(<FontDetailHeader {...baseProps} dirty />);
		expect(
			getByRole('button', {
				name: /unsaved changes will be discarded/i,
			})
		).toBeInTheDocument();
	});

	test('clicking the mobile back button fires onMobileBack', () => {
		const onMobileBack = jest.fn();
		const { getByRole } = renderWithStore(
			<FontDetailHeader {...baseProps} onMobileBack={onMobileBack} />
		);
		fireEvent.click(getByRole('button', { name: /back to font list/i }));
		expect(onMobileBack).toHaveBeenCalled();
	});
});
