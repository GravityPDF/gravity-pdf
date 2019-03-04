import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
  ADD_TEMPLATE,
  UPDATE_TEMPLATE_PARAM,
  DELETE_TEMPLATE,
} from '../actionTypes/templates'

/**
 * Our Redux Template Reducer that take the objects returned from our Redux Template Actions
 * and updates the template portion of our store
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
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
 * Setup the initial state of the "template" portion of our Redux store
 *
 * @type {{list: any, activeTemplate: (any), search: string}}
 *
 * @since 4.1
 */
export const initialState = {
  list: GFPDF.templateList,
  activeTemplate: GFPDF.activeTemplate || GFPDF.activeDefaultTemplate,
  search: '',
}

/**
 * The action template reducer which updates our state
 *
 * @param {Object} state The current state of our template store
 * @param {Object} action The Redux action details being triggered
 *
 * @returns {Object} State (whether updated or not)
 *
 * @since 4.1
 */
export default function (state = initialState, action) {

  switch (action.type) {
    /**
     * Update the search key
     *
     * @since 4.1
     */
    case SEARCH_TEMPLATES:
      return {
        ...state,
        search: action.text
      }

    /**
     * Update the activeTemplate key
     *
     * @since 4.1
     */
    case SELECT_TEMPLATE:
      return {
        ...state,
        activeTemplate: action.id
      }

    /**
     * Push a new template into List
     *
     * @since 4.1
     */
    case ADD_TEMPLATE:
      return {
        ...state,
        list: [...state.list, action.template]
      }

    /**
     * Update single parameter in template new value
     *
     * @since 4.1
     */
    case UPDATE_TEMPLATE_PARAM:
      const updatedList = state.list.map(item => {
        if (item.id == action.id) {
          return {...item, [action.name]: action.value}
        }
        return item
      })
      return {
        ...state,
        list: updatedList
      }

    /**
     * Remove template from List
     *
     * @since 4.1
     */
    case DELETE_TEMPLATE:
      const list = state.list.filter(item => item.id !== action.id)
      return {
        ...state,
        list: [...list]
      }
  }

  /* None of these actions fired so return state */
  return state
}
