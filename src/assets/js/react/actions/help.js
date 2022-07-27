/* Our Redux Action Type Constants */
export const GET_DATA = 'GET_DATA'
export const RESULT_ERROR = 'RESULT_ERROR'
export const UPDATE_RESULTS = 'UPDATE_RESULTS'
export const DELETE_RESULTS = 'DELETE_RESULTS'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Get the latest help search input query
 *
 * @param data
 *
 * @returns {{type, payload: (string)}}
 *
 * @since 5.2
 */
export const getData = data => {
  return {
    type: GET_DATA,
    payload: data
  }
}

/**
 * Update error handling to the store
 *
 * @param data
 *
 * @returns {{type, payload: (string)}}
 *
 * @since 5.2
 */
export const updateError = data => {
  return {
    type: RESULT_ERROR,
    payload: data
  }
}

/**
 * Save/update the latest help search results to the store
 *
 * @param data
 *
 * @returns {{type, payload: (object)}}
 *
 * @since 5.2
 */
export const updateResult = data => {
  return {
    type: UPDATE_RESULTS,
    payload: data
  }
}

/**
 * Delete the help search results from the store
 *
 * @returns {{type}}
 *
 * @since 5.2
 */
export const deleteResult = () => {
  return {
    type: DELETE_RESULTS
  }
}
