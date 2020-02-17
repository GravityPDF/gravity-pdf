/* Our Redux Action Type Constants */
export const GET_DATA = 'GET_DATA'
export const RESULT_ERROR = 'RESULT_ERROR'
export const UPDATE_RESULTS = 'UPDATE_RESULTS'
export const DELETE_RESULTS = 'DELETE_RESULTS'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2020, Blue Liquid Designs

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
