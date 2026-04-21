import * as React from '@wordpress/element';
import { render, RenderOptions } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';
import { configureStore } from '@reduxjs/toolkit';
import createSagaMiddleware from 'redux-saga';
import rootReducer from '../../../src/assets/js/react/reducers/index';
import type { RootState } from '../../../src/assets/js/react/store/index';

export {
	screen,
	fireEvent,
	waitFor,
	within,
	act,
} from '@testing-library/react';
export { userEvent } from '@testing-library/user-event';

export function createTestStore(
	initialState: Partial<RootState> = {}
): ReturnType<typeof configureStore<ReturnType<typeof rootReducer>>> {
	const sagaMiddleware = createSagaMiddleware();
	return configureStore({
		reducer: rootReducer,
		middleware: (getDefaultMiddleware) =>
			getDefaultMiddleware({
				thunk: false,
				serializableCheck: false,
			}).concat(sagaMiddleware),
		preloadedState: initialState,
	});
}

export function renderWithStore(
	ui: React.ReactElement,
	initialState: Partial<RootState> = {},
	renderOptions: Omit<RenderOptions, 'wrapper'> = {},
	passedStore?: ReturnType<typeof createTestStore>
): ReturnType<typeof render> & { store: ReturnType<typeof createTestStore> } {
	const store =
		passedStore !== undefined ? passedStore : createTestStore(initialState);

	const Wrapper = ({ children }: { children: React.ReactNode }) => (
		<Provider store={store}>{children}</Provider>
	);

	return {
		...render(ui, { wrapper: Wrapper, ...renderOptions }),
		store,
	};
}

export function renderWithRouter(
	ui: React.ReactElement,
	{
		route = '/',
		initialState = {},
		store: passedStore,
		renderOptions = {},
	}: {
		route?: string;
		initialState?: Partial<RootState>;
		store?: ReturnType<typeof createTestStore>;
		renderOptions?: Omit<RenderOptions, 'wrapper'>;
	} = {}
): ReturnType<typeof render> & { store: ReturnType<typeof createTestStore> } {
	const store =
		passedStore !== undefined ? passedStore : createTestStore(initialState);

	const Wrapper = ({ children }: { children: React.ReactNode }) => (
		<Provider store={store}>
			<MemoryRouter initialEntries={[route]}>{children}</MemoryRouter>
		</Provider>
	);

	return {
		...render(ui, { wrapper: Wrapper, ...renderOptions }),
		store,
	};
}

export function findByTestAttr(
	container: HTMLElement,
	val: string
): HTMLElement | null {
	return container.querySelector(`[data-test="${val}"]`);
}
