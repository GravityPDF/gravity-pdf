import { fireEvent } from '@testing-library/react';
import {
	renderWithStore,
	findByTestAttr,
	createTestStore,
} from '../../testUtilsRTL';
import { CloseDialog } from '../../../../../src/assets/js/react/components/Modal/CloseDialog';
import type { FontManagerState } from '../../../../../src/assets/js/react/types';

describe('CloseDialog - CloseDialog.js', () => {
	describe('RUN LIFECYCLE METHODS', () => {
		test('keydown listener is assigned to document on mount', () => {
			const addEventSpy = jest.spyOn(document, 'addEventListener');
			renderWithStore(<CloseDialog onClose={jest.fn()} />);

			expect(addEventSpy).toHaveBeenCalledWith(
				'keydown',
				expect.any(Function),
				false
			);

			addEventSpy.mockRestore();
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test("handleKeyPress() - Close font manager 'Update Font' panel first", () => {
			document.body.innerHTML = '<div class="update-font show"></div>';

			const initialState = {
				fontManager: {
					msg: { success: { addFont: {} }, error: {} },
				} as unknown as FontManagerState,
			};
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const mockOnClose = jest.fn();
			const mockOnCloseDetail = jest.fn();

			renderWithStore(
				<CloseDialog
					onClose={mockOnClose}
					onCloseDetail={mockOnCloseDetail}
					hasDetailOpen={true}
				/>,
				{},
				{},
				store
			);
			fireEvent.keyDown(document, { key: 'Escape' });

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'CLEAR_ADD_FONT_MSG' })
			);
			expect(mockOnCloseDetail).toHaveBeenCalledTimes(1);
			expect(mockOnClose).not.toHaveBeenCalled();
		});

		test('handleKeyPress() - Close modal', () => {
			const mockOnClose = jest.fn();
			renderWithStore(<CloseDialog onClose={mockOnClose} />);
			fireEvent.keyDown(document, { key: 'Escape' });

			expect(mockOnClose).toHaveBeenCalledTimes(1);
		});

		test('handleCloseDialog() - button click calls onClose', () => {
			const mockOnClose = jest.fn();
			const { container } = renderWithStore(
				<CloseDialog onClose={mockOnClose} />
			);
			fireEvent.click(
				findByTestAttr(container, 'component-CloseDialog')!
			);

			expect(mockOnClose).toHaveBeenCalledTimes(1);
		});
	});

	describe('RENDERS COMPONENT', () => {
		test('render <CloseDialog /> component', () => {
			const { container } = renderWithStore(
				<CloseDialog onClose={jest.fn()} />
			);

			expect(
				findByTestAttr(container, 'component-CloseDialog')
			).toBeInTheDocument();
		});

		test('render button accessible label', () => {
			const { container } = renderWithStore(
				<CloseDialog onClose={jest.fn()} />
			);

			expect(
				container.querySelector(
					'button[data-test="component-CloseDialog"]'
				)
			).toHaveAttribute('aria-label', 'Close dialog');
		});

		test('check button click', () => {
			const mockOnClose = jest.fn();
			const { container } = renderWithStore(
				<CloseDialog onClose={mockOnClose} />
			);
			fireEvent.click(
				findByTestAttr(container, 'component-CloseDialog')!
			);

			expect(mockOnClose).toHaveBeenCalledTimes(1);
		});
	});
});
