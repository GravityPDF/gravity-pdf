import {
  GET_DATA,
  RESULT_ERROR,
  UPDATE_RESULTS,
  DELETE_RESULTS
} from '../actions/help'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Setup the initial state of the "help" portion of our Redux store
 *
 * @type {{loading: boolean, results: array, error: string}}
 *
 * @since 5.2
 */
export const initialState = {
  loading: false,
  results: [],
  error: ''
}

/**
 * The action help reducer which updates our state
 *
 * @param state The current state of our template store
 * @param action The Redux action details being triggered
 *
 * @returns {*} State (whether updated or not)
 *
 * @since 5.2
 */
export default function (state = initialState, action) {
  switch (action.type) {
    /**
     * @since 5.2
     */
    case GET_DATA:
      return {
        ...state,
        loading: true,
        error: ''
      }

    /**
     * @since 5.2
     */
    case RESULT_ERROR:
      return {
        ...state,
        loading: false,
        error: action.payload
      }

    /**
     * @since 5.2
     */
    case UPDATE_RESULTS:
      return {
        ...state,
        loading: false,
        results: action.payload
      }

    /**
     * @since 5.2
     */
    case DELETE_RESULTS:
      return {
        ...state,
        results: []
      }

    /* None of the above actions fired so it will fire 'default' */
    default:
      return state
  }
}
