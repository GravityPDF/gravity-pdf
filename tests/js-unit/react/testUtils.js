import { createStore, applyMiddleware } from 'redux'
import rootReducer from '../../../src/assets/js/react/reducers/index'
import { middlewares } from '../../../src/assets/js/react/store'

/**
 * Create a testing store with imported reducers, middleware, and initial state.
 * global: rootReducer, middlewares.
 * @param {object} initialState - Initial state for store.
 * @function {Store} - Redux store.
 */
export const storeFactory = (initialState) => {
  const createStoreWithMiddleware = applyMiddleware(...middlewares)(createStore)

  return createStoreWithMiddleware(rootReducer, initialState)
}

/**
 * Return node(s) with the given data-test attribute.
 * @param {ShallowWrapper} wrapper - Enzyme shallow wrapper.
 * @param {string} val - Value of data-test attribute for search.
 * @returns {ShallowWrapper}
 */
export const findByTestAttr = (wrapper, val) => {
  return wrapper.find(`[data-test="${val}"]`)
}
