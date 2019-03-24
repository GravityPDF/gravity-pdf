import {
  GET_DATA,
  RESULT_ERROR,
  UPDATE_RESULTS,
  DELETE_RESULTS
} from '../actions/help'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
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
        loading: true
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
