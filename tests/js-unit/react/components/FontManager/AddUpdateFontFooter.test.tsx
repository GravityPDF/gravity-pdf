import { fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import AddUpdateFontFooter from '../../../../../src/assets/js/react/components/FontManager/AddUpdateFontFooter';

describe('FontManager - AddFontFooter.js', () => {
	const initialState = {
		fontManager: {
			loading: false,
			addFontLoading: false,
			deleteFontLoading: false,
			fontList: [],
			searchResult: null,
			selectedFont: 'roboto',
			msg: {},
		},
	};

	const defaultMsg = {
		success: { addFont: 'success' },
		error: { addFont: 'error' },
	};

	const defaultProps = {
		msg: defaultMsg,
		loading: true,
		tabIndex: '148',
		type: 'add' as const,
	};

	describe('RENDERS COMPONENT', () => {
		test('render <AddFontFooter /> component', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-AddFontFooter')
			).toBeInTheDocument();
		});

		test('render cancel button', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter
					{...defaultProps}
					id="active"
					type="update"
				/>,
				initialState
			);
			expect(
				container.querySelector('button.cancel')
			).toBeInTheDocument();
		});

		test('render add font button', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} />,
				initialState
			);
			expect(
				container.querySelector('button.gfpdf-button')!.textContent
			).toBe('Add Font →');
		});

		test('render update font button', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter
					{...defaultProps}
					id="active"
					type="update"
				/>,
				initialState
			);
			expect(
				container.querySelector('button.gfpdf-button:not(.cancel)')!
					.textContent
			).toBe('Update Font →');
		});

		test('render update panel select font checkbox', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				initialState
			);
			expect(
				container.querySelector('button.dashicons-yes')
			).toBeInTheDocument();
		});

		test('render update panel delete icon', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				initialState
			);
			expect(
				container.querySelector('button.dashicons-trash')
			).toBeInTheDocument();
		});

		test('render loading spinner', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} loading={true} />,
				initialState
			);
			expect(
				container.querySelector('.gfpdf-spinner')
			).toBeInTheDocument();
		});

		test('render success message', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} />,
				initialState
			);
			expect(container.querySelector('span.success')).toBeInTheDocument();
		});

		test('render error message', () => {
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} />,
				initialState
			);
			expect(container.querySelector('span.error')).toBeInTheDocument();
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('handleSelectFont() - dispatches selectFont action on click', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				{},
				{},
				store
			);

			fireEvent.click(container.querySelector('button.dashicons-yes')!);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'SELECT_FONT' })
			);
		});

		test('handleSelectFontKeypress() - dispatches selectFont on Enter key', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				{},
				{},
				store
			);

			fireEvent.keyDown(
				container.querySelector('button.dashicons-yes')!,
				{
					key: 'Enter',
				}
			);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'SELECT_FONT' })
			);
		});

		test('handleSelectFontKeypress() - does not dispatch selectFont on other keys', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				{},
				{},
				store
			);

			fireEvent.keyDown(
				container.querySelector('button.dashicons-yes')!,
				{
					key: 'Tab',
				}
			);

			expect(dispatchSpy).not.toHaveBeenCalledWith(
				expect.objectContaining({ type: 'SELECT_FONT' })
			);
		});

		test('handleDeleteFont() - dispatches deleteFont when confirmed', () => {
			global.confirm = () => true;
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				{},
				{},
				store
			);

			fireEvent.click(container.querySelector('button.dashicons-trash')!);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'DELETE_FONT' })
			);
		});

		test('handleDeleteFont() - does not dispatch deleteFont when not confirmed', () => {
			global.confirm = () => false;
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				{},
				{},
				store
			);

			fireEvent.click(container.querySelector('button.dashicons-trash')!);

			expect(dispatchSpy).not.toHaveBeenCalledWith(
				expect.objectContaining({ type: 'DELETE_FONT' })
			);
		});

		test('handleDeleteFontKeypress() - dispatches deleteFont on Enter when confirmed', () => {
			global.confirm = () => true;
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				{},
				{},
				store
			);

			fireEvent.keyDown(
				container.querySelector('button.dashicons-trash')!,
				{
					key: 'Enter',
				}
			);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'DELETE_FONT' })
			);
		});

		test('handleDeleteFontKeypress() - does not dispatch deleteFont on non-Enter key', () => {
			global.confirm = () => false;
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<AddUpdateFontFooter {...defaultProps} id="roboto" />,
				{},
				{},
				store
			);

			fireEvent.keyDown(
				container.querySelector('button.dashicons-trash')!,
				{
					key: 'Tab',
				}
			);

			expect(dispatchSpy).not.toHaveBeenCalledWith(
				expect.objectContaining({ type: 'DELETE_FONT' })
			);
		});
	});
});
