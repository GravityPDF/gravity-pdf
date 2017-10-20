import React from 'react'

import { createStore, combineReducers } from 'redux'
import templateReducer from '../reducers/templateReducer'
import coreFontsReducer from '../reducers/coreFontReducer'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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

/* Combine our Redux Reducers */
const reducers = setupReducers()

/* Create our store and enable the Redux dev tools, if they exist */
const store = createStore(reducers, window.devToolsExtension && window.devToolsExtension())

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
  })
}