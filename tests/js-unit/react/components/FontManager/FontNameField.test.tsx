import { fireEvent } from '@testing-library/react';
import { renderWithStore } from '../../testUtilsRTL';
import FontNameField from '../../../../../src/assets/js/react/components/FontManager/FontNameField';

describe('FontManager - FontNameField', () => {
	test('renders without an inline error when error is empty', () => {
		const { queryByRole } = renderWithStore(
			<FontNameField value="Roboto" error="" onChange={jest.fn()} />
		);
		expect(queryByRole('alert')).not.toBeInTheDocument();
	});

	test('renders the error text and aria-invalid when error is set', () => {
		const { getByRole, getByLabelText } = renderWithStore(
			<FontNameField
				value="Bad@Name"
				error="Use letters, numbers, and spaces only"
				onChange={jest.fn()}
			/>
		);
		expect(getByRole('alert')).toHaveTextContent(
			/letters, numbers, and spaces only/i
		);
		expect(getByLabelText(/font name/i)).toHaveAttribute(
			'aria-invalid',
			'true'
		);
	});

	test('typing dispatches onChange with the new value', () => {
		const onChange = jest.fn();
		const { getByLabelText } = renderWithStore(
			<FontNameField value="" error="" onChange={onChange} />
		);
		fireEvent.change(getByLabelText(/font name/i), {
			target: { value: 'Roboto' },
		});
		expect(onChange).toHaveBeenCalledWith('Roboto');
	});
});
