import {
  ADD_TO_CONSOLE,
  ADD_TO_RETRY_LIST,
  CLEAR_RETRY_LIST,
  CLEAR_CONSOLE,
  GET_FILES_FROM_GITHUB_SUCCESS,
  GET_FILES_FROM_GITHUB_FAILED,
  REQUEST_SENT_COUNTER,
  CLEAR_REQUEST_REMAINING_DATA,
  RETRY_DOWNLOAD
} from '../actions/coreFonts'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
 */

/**
 * Setup the initial state of the "coreFont" portion of our Redux store
 *
 * @type {{
 *  fontList: Array,
 *  console: Object,
 *  retry: Array,
 *  getFilesFromGitHubFailed: Object,
 *  remainingDownload: Integer,
 *  requestDownload: String,
 *  requestSentCounter: Array,
 *  retry_download: Boolean,
 *  currentDownload: Integer,
 *  retryDownloadLength: null
 * }}
 *
 * @since 5.0
 */
export const initialState = {
  fontList: [],
  console: {},
  retry: [],
  getFilesFromGitHubFailed: {},
  remainingDownload: 0,
  requestDownload: '',
  requestSentCounter: [],
  retry_download: false,
  currentDownload: 0,
  retryDownloadLength: null
}

/**
 * The action coreFont reducer which updates our state
 *
 * @param state The current state of our template store
 * @param action The Redux action details being triggered
 *
 * @returns {*} State (whether updated or note)
 *
 * @since 5.0
 */
export default function (state = initialState, action) {
  switch (action.type) {
    /**
     * @since 5.0
     */
    case ADD_TO_CONSOLE:
      return {
        ...state,
        console: {
          ...state.console,
          [action.key]: {
            status: action.status,
            message: action.message
          }
        }
      }

    /**
     * @since 5.0
     */
    case CLEAR_CONSOLE:
      return {
        ...state,
        console: {}
      }

    /**
     * @since 5.0
     */
    case ADD_TO_RETRY_LIST:
      /* Do not allow the same item in the retry list */
      if (state.retry.includes(action.name)) {
        break
      }

      return {
        ...state,
        retry: [
          ...state.retry,
          action.name
        ]
      }

    /**
     * @since 5.0
     */
    case CLEAR_RETRY_LIST:
      return {
        ...state,
        retry: []
      }

    /**
     * @since 5.2
     */
    case GET_FILES_FROM_GITHUB_SUCCESS: {
      let files = []

      /* Push font names into array */
      action.payload.map((item) => {
        files.push(item.name)
      })

      return {
        ...state,
        fontList: files
      }
    }

    /**
     * @since 5.2
     */
    case GET_FILES_FROM_GITHUB_FAILED:
      return {
        ...state,
        getFilesFromGitHubFailed: action.payload
      }

    /**
     * @since 5.2
     */
    case REQUEST_SENT_COUNTER: {
      /* Show the overall status in the console once all the fonts have been downloaded (or tried to download) */
      const errors = state.retry.length
      const status = errors ? 'error' : 'success'
      const message = errors ? GFPDF.coreFontError.replace('%s', errors) : GFPDF.coreFontSuccess

      /* Fire only if requested a retry download */
      if (state.retry_download === true) {
        state.retryDownloadLength--
        if (state.retryDownloadLength === 0) {
          state.console.completed = {}
          return {
            ...state,
            console: {
              ...state.console,
              ['completed']: {
                status: status,
                message: message
              }
            },
            remainingDownload: state.retryDownloadLength,
            requestDownload: 'finished',
            retry_download: false
          }
        }
        return {
          ...state,
          remainingDownload: state.retryDownloadLength
        }
      }

      /* Fire in first download request */
      state.currentDownload++
      if (state.currentDownload >= state.fontList.length && state.retry_download === false) {
        /* Failed */
        if (state.retry.length > 0) {
          return {
            ...state,
            console: {
              ...state.console,
              ['completed']: {
                status: status,
                message: message
              }
            },
            requestDownload: 'finished',
            currentDownload: 0,
            remainingDownload: 0
          }
        } else {
          /* Success */
          return {
            ...state,
            console: {
              ...state.console,
              ['completed']: {
                status: status,
                message: message
              }
            },
            requestDownload: 'finished',
            currentDownload: 0,
            remainingDownload: 0
          }
        }
      } else {
        let remainingDownloadCounter = state.fontList.length - state.currentDownload
        return {
          ...state,
          remainingDownload: remainingDownloadCounter
        }
      }
    }

    /**
     * @since 5.2
     */
    case CLEAR_REQUEST_REMAINING_DATA:
      return {
        ...state,
        requestDownload: ''
      }

    /**
     * @since 5.2
     */
    case RETRY_DOWNLOAD:
      return {
        ...state,
        retry_download: true,
        retryDownloadLength: action.payload
      }
  }

  /* None of these actions fired so return state */
  return state
}
