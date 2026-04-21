import { screen } from '@testing-library/react';
import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import FontListAlertMessage from '../../../../../src/assets/js/react/components/FontManager/FontListAlertMessage';

describe('FontManager - FontListAlertMessage.js', () => {
	describe('RENDERS COMPONENT', () => {
		test('render <FontListAlertMessage /> component', () => {
			const { container } = renderWithStore(<FontListAlertMessage />);
			expect(
				findByTestAttr(container, 'component-FontListAlertMessage')
			).toBeInTheDocument();
		});

		test('display font list empty message', () => {
			renderWithStore(<FontListAlertMessage empty={true} />);
			expect(screen.getByText('Font list empty.')).toBeInTheDocument();
		});

		test('display search result empty message', () => {
			renderWithStore(<FontListAlertMessage empty={false} />);
			expect(
				screen.getByText('No fonts matching your search found.')
			).toBeInTheDocument();
			expect(screen.getByText('Clear your search query.')).toBeInTheDocument();
		});

		test('display API call request link', () => {
			renderWithStore(
				<FontListAlertMessage empty={false} error="error" />
			);
			expect(
				screen.getByRole('button', { name: 'error' })
			).toBeInTheDocument();
		});
	});
});
