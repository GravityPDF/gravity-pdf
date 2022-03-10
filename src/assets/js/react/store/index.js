/* Dependencies */
import { composeWithDevTools } from 'redux-devtools-extension'
import { createStore, applyMiddleware } from 'redux'
import createSagaMiddleware from 'redux-saga'
/* Root Saga */
import rootSaga from '../sagas'
/* Root Reducer */
import rootReducer from '../reducers/index'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/* Combine our Redux Reducers */
const reducers = rootReducer
/* Initialize Saga Middleware */
const sagaMiddleware = createSagaMiddleware()
export const middlewares = [sagaMiddleware]
const middlewareEnhancer = applyMiddleware(...middlewares)
const enhancers = [middlewareEnhancer]
/* Initialize Redux dev tools */
const composedEnhancers = composeWithDevTools(...enhancers)
/* Create our store and enable composedEnhancers */
const store = createStore(
  reducers,
  composedEnhancers
)

/* Run Saga Middleware */
sagaMiddleware.run(rootSaga)

export function getStore () {
  return store
}
