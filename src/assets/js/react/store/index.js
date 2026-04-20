/* Dependencies */
import { configureStore } from '@reduxjs/toolkit';
import createSagaMiddleware from 'redux-saga';
/* Root Saga */
import rootSaga from '../sagas';
/* Root Reducer */
import rootReducer from '../reducers/index';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/* Initialize Saga Middleware */
const sagaMiddleware = createSagaMiddleware();
export const middlewares = [sagaMiddleware];

/* Create our store with RTK configureStore (includes DevTools automatically) */
const store = configureStore({
	reducer: rootReducer,
	middleware: (getDefaultMiddleware) =>
		getDefaultMiddleware({
			thunk: false,
			serializableCheck: false,
		}).concat(sagaMiddleware),
});

/* Run Saga Middleware */
sagaMiddleware.run(rootSaga);

export function getStore() {
	return store;
}
