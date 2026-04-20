import React from 'react';
import { render } from '@testing-library/react';
import { Provider } from 'react-redux';
import { MemoryRouter } from 'react-router-dom';
import { configureStore } from '@reduxjs/toolkit';
import createSagaMiddleware from 'redux-saga';
import rootReducer from '../../../src/assets/js/react/reducers/index';

export {
	screen,
	fireEvent,
	waitFor,
	within,
	act,
} from '@testing-library/react';
export { userEvent } from '@testing-library/user-event';

/**
 * Create a test Redux store with optional preloaded state.
 *
 * @param {Object} initialState - Preloaded state slice (partial store state).
 * @return {Object} Redux store.
 */
export function createTestStore(initialState = {}) {
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

/**
 * Render a component wrapped in a Redux Provider with an optional preloaded store state.
 *
 * @param { React.ReactElement } ui            - Component to render.
 * @param {Object}               initialState  - Preloaded Redux state (ignored when store is passed).
 * @param {Object}               renderOptions - Additional RTL render options.
 * @param {Object}               passedStore   - Optional pre-created store (overrides initialState).
 * @return {Object} RTL render result plus the store instance.
 */
export function renderWithStore(
	ui,
	initialState = {},
	renderOptions = {},
	passedStore
) {
	const store =
		passedStore !== undefined ? passedStore : createTestStore(initialState);

	const Wrapper = ({ children }) => (
		<Provider store={store}>{children}</Provider>
	);

	return {
		...render(ui, { wrapper: Wrapper, ...renderOptions }),
		store,
	};
}

/**
 * Render a component wrapped in a Redux Provider and MemoryRouter.
 *
 * @param { React.ReactElement } ui                    - Component to render.
 * @param {Object}               options
 * @param { string }             options.route         - Initial route path.
 * @param {Object}               options.initialState  - Preloaded Redux state.
 * @param {Object}               options.store         - Optional pre-created store (overrides initialState).
 * @param {Object}               options.renderOptions - Additional RTL render options.
 * @return {Object} RTL render result plus the store instance.
 */
export function renderWithRouter(
	ui,
	{
		route = '/',
		initialState = {},
		store: passedStore,
		renderOptions = {},
	} = {}
) {
	const store =
		passedStore !== undefined ? passedStore : createTestStore(initialState);

	const Wrapper = ({ children }) => (
		<Provider store={store}>
			<MemoryRouter initialEntries={[route]}>{children}</MemoryRouter>
		</Provider>
	);

	return {
		...render(ui, { wrapper: Wrapper, ...renderOptions }),
		store,
	};
}

/**
 * Return the first element matching a data-test attribute — compatibility shim
 * for tests migrated from Enzyme's findByTestAttr helper.
 *
 * @param { HTMLElement } container - The RTL render container.
 * @param { string }      val       - Value of the data-test attribute.
 * @return { HTMLElement | null } Matched element or null if not found.
 */
export function findByTestAttr(container, val) {
	return container.querySelector(`[data-test="${val}"]`);
}
