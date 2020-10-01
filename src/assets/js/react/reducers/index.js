import { combineReducers } from 'redux'
import templateReducer from './templateReducer'
import coreFontsReducer from './coreFontReducer'
import helpReducer from './helpReducer'
import fontManagerReducer from './fontManagerReducer'

/**
 * Combine our Redux reducers for use in a single store
 * If you want to add new top-level keys to our store, this is the place
 *
 * @since 4.1
 */
export function setupReducers () {
  return combineReducers({
    template: templateReducer,
    coreFonts: coreFontsReducer,
    help: helpReducer,
    fontManager: fontManagerReducer
  })
}
