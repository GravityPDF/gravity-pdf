import { composeWithDevTools } from 'redux-devtools-extension'
import { createStore, combineReducers, applyMiddleware } from 'redux'
import createSagaMiddleware from 'redux-saga'
import rootSaga from '../sagas'
import templateReducer from '../reducers/templateReducer'
import coreFontsReducer from '../reducers/coreFontReducer'
import helpReducer from '../reducers/helpReducer'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/* Combine our Redux Reducers */
const reducers = setupReducers()
/* Initialize Saga Middleware */
const sagaMiddleware = createSagaMiddleware()
const middlewares = [sagaMiddleware]
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

/**
 * Combine our Redux reducers for use in a single store
 * If you want to add new top-level keys to our store, this is the place
 *
 * @returns {Function}
 *
 * @since 4.1
 */
export function setupReducers () {
  return combineReducers({
    template: templateReducer,
    coreFonts: coreFontsReducer,
    help: helpReducer
  })
}
