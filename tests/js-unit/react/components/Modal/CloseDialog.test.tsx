import { fireEvent } from '@testing-library/react';
import {
	renderWithStore,
	findByTestAttr,
	createTestStore,
} from '../../testUtilsRTL';
import { CloseDialog } from '../../../../../src/assets/js/react/components/Modal/CloseDialog';
import * as utilitiesB from '../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont';
import type { FontManagerState } from '../../../../../src/assets/js/react/types';

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
	...jest.requireActual('react-router-dom'),
	useNavigate: () => mockNavigate,
	useLocation: () => ({ pathname: '/fontmanager/' }),
}));

describe('CloseDialog - CloseDialog.js', () => {
	beforeEach(() => {
		mockNavigate.mockClear();
	});

	describe('RUN LIFECYCLE METHODS', () => {
		test('keydown listener is assigned to document on mount', () => {
			const addEventSpy = jest.spyOn(document, 'addEventListener');
			renderWithStore(<CloseDialog />);

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
			const toggleUpdateFont = jest.spyOn(utilitiesB, 'toggleUpdateFont');

			renderWithStore(<CloseDialog id="yes" />, {}, {}, store);
			fireEvent.keyDown(document, { key: 'Escape' });

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'CLEAR_ADD_FONT_MSG' })
			);
			expect(toggleUpdateFont).toHaveBeenCalledTimes(1);

			toggleUpdateFont.mockRestore();
		});

		test('handleKeyPress() - Close modal', () => {
			renderWithStore(<CloseDialog />);
			fireEvent.keyDown(document, { key: 'Escape' });

			expect(mockNavigate).toHaveBeenCalledWith('/');
		});

		test('handleCloseDialog() - trigger router', () => {
			const { container } = renderWithStore(<CloseDialog />);
			fireEvent.click(
				findByTestAttr(container, 'component-CloseDialog')!
			);

			expect(mockNavigate).toHaveBeenCalledWith('/');
		});
	});

	describe('RENDERS COMPONENT', () => {
		test('render <CloseDialog /> component', () => {
			const { container } = renderWithStore(<CloseDialog />);

			expect(
				findByTestAttr(container, 'component-CloseDialog')
			).toBeInTheDocument();
		});

		test('render button accessible label', () => {
			const { container } = renderWithStore(<CloseDialog />);

			expect(
				container.querySelector('button[data-test="component-CloseDialog"]')
			).toHaveAttribute('aria-label', 'Close dialog');
		});

		test('check button click', () => {
			const { container } = renderWithStore(<CloseDialog />);
			fireEvent.click(
				findByTestAttr(container, 'component-CloseDialog')!
			);

			expect(mockNavigate).toHaveBeenCalledTimes(1);
		});
	});
});
