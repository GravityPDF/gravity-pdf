import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
  ADD_TEMPLATE,
  UPDATE_TEMPLATE,
  UPDATE_TEMPLATE_PARAM,
  DELETE_TEMPLATE,
} from '../actionTypes/templates'

/**
 * Redux Actions - payloads of information that send data from your application to your store
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
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

/**
 * Fires the Advanced Template Search action
 *
 * @param {string} text
 *
 * @returns {{type, text: *}}
 *
 * @since 4.1
 */
export const searchTemplates = (text) => {
  return {
    type: SEARCH_TEMPLATES,
    text
  }
}

/**
 * Fires the Advanced Template select/activate action
 *
 * @param {string} id The template ID
 *
 * @returns {{type, id: *}}
 *
 * @since 4.1
 */
export const selectTemplate = (id) => {
  return {
    type: SELECT_TEMPLATE,
    id
  }
}

/**
 * Fires the Advanced Template add new template action
 *
 * @param {object} template An Immutable Map
 *
 * @returns {{type, template: *}}
 *
 * @since 4.1
 */
export const addTemplate = (template) => {
  return {
    type: ADD_TEMPLATE,
    template
  }
}

/**
 * Fires the Advanced Template update action which overrides the entire template object with a new one
 *
 * @param {object} template An Immutable Map
 *
 * @returns {{type, template: *}}
 *
 * @since 4.1
 */
export const updateTemplate = (template) => {
  return {
    type: UPDATE_TEMPLATE,
    template
  }
}

/**
 * Fires the Advanced Template update action which replaces a template parameter with a new value
 *
 * @param {string} id The template ID
 * @param {string} name The parameter key to update
 * @param {string} value The new value for the parameter
 *
 * @returns {{type, id: *, name: *, value: *}}
 *
 * @since 4.1
 */
export const updateTemplateParam = (id, name, value) => {
  return {
    type: UPDATE_TEMPLATE_PARAM,
    id,
    name,
    value
  }
}

/**
 * Fires the Advanced Template delete action which removes the template from our store
 *
 * @param  {string} id The template ID
 *
 * @returns {{type, id: *}}
 *
 * @since 4.1
 */
export const deleteTemplate = (id) => {
  return {
    type: DELETE_TEMPLATE,
    id
  }
}