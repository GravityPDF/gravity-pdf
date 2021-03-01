/* Our Redux Action Type Constants */
export const ADD_TO_CONSOLE = 'ADD_TO_CONSOLE'
export const ADD_TO_RETRY_LIST = 'ADD_TO_RETRY_LIST'
export const CLEAR_CONSOLE = 'CLEAR_CONSOLE'
export const CLEAR_BUTTON_CLICKED_AND_RETRY_LIST = 'CLEAR_BUTTON_CLICKED_AND_RETRY_LIST'
export const GET_FILES_FROM_GITHUB = 'GET_FILES_FROM_GITHUB'
export const GET_FILES_FROM_GITHUB_SUCCESS = 'GET_FILES_FROM_GITHUB_SUCCESS'
export const GET_FILES_FROM_GITHUB_FAILED = 'GET_FILES_FROM_GITHUB_FAILED'
export const DOWNLOAD_FONTS_API_CALL = 'DOWNLOAD_FONTS_API_CALL'
export const REQUEST_SENT_COUNTER = 'REQUEST_SENT_COUNTER'
export const CLEAR_REQUEST_REMAINING_DATA = 'CLEAR_REQUEST_REMAINING_DATA'

/**
 * Redux Actions - payloads of information that send data from your application to your store
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Adds a message to our buffer for display to the user
 *
 * @param key
 * @param status
 * @param message
 *
 * @returns {{type, key: *, status: *, message: *}}
 *
 * @since 5.0
 */
export const addToConsole = (key, status, message) => {
  return {
    type: ADD_TO_CONSOLE,
    key,
    status,
    message
  }
}

/**
 * Clears the message buffer
 *
 * @returns {{type}}
 *
 * @since 5.0
 */
export const clearConsole = () => {
  return {
    type: CLEAR_CONSOLE
  }
}

/**
 * Adds a font to our retry list
 *
 * @param name
 * @returns {{type, name: *}}
 *
 * @since 5.0
 */
export const addToRetryList = (name) => {
  return {
    type: ADD_TO_RETRY_LIST,
    name
  }
}

/**
 * Clears our retry list
 *
 * @returns {{type}}
 *
 * @since 5.0
 */
export const clearButtonClickedAndRetryList = () => {
  return {
    type: CLEAR_BUTTON_CLICKED_AND_RETRY_LIST
  }
}

/**
 * Request call to API
 *
 * @returns {{type}}
 *
 * @since 5.2
 */
export const getFilesFromGitHub = () => {
  return {
    type: GET_FILES_FROM_GITHUB
  }
}

/**
 * Get success data from API call
 *
 * @param files
 * @returns {{type, payload: (Array)}}
 *
 * @since 5.2
 */
export const getFilesFromGitHubSuccess = files => {
  return {
    type: GET_FILES_FROM_GITHUB_SUCCESS,
    payload: files
  }
}

/**
 * Get error data from failed API call
 *
 * @param error
 * @returns {{type, payload: (Object)}}
 *
 * @since 5.2
 */
export const getFilesFromGitHubFailed = error => {
  return {
    type: GET_FILES_FROM_GITHUB_FAILED,
    payload: error
  }
}

/**
 * Get success file name from API call
 *
 * @param file
 * @returns {{type, payload: (String)}}
 *
 * @since 5.2
 */
export const downloadFontsApiCall = file => {
  return {
    type: DOWNLOAD_FONTS_API_CALL,
    payload: file
  }
}

/**
 * Request data into our Redux store for getting queue length value
 *
 * @returns {{type}}
 *
 * @since 5.2
 */
export const currentDownload = () => {
  return {
    type: REQUEST_SENT_COUNTER
  }
}

/**
 * Clear/reset store 'requestDownload' state
 *
 * @returns {{type}}
 *
 * @since 5.2
 */
export const clearRequestRemainingData = () => {
  return {
    type: CLEAR_REQUEST_REMAINING_DATA
  }
}
