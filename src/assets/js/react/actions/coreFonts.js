import {
  ADD_TO_CONSOLE,
  ADD_TO_RETRY_LIST,
  CLEAR_CONSOLE,
  CLEAR_RETRY_LIST
} from '../actionTypes/coreFonts'

/**
 * Redux Actions - payloads of information that send data from your application to your store
 *
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
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
    type: CLEAR_CONSOLE,
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
export const clearRetryList = () => {
  return {
    type: CLEAR_RETRY_LIST,
  }
}