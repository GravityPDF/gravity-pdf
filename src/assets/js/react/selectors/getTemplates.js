import { createSelector } from 'reselect'
import versionCompare from '../utilities/versionCompare'

/**
 * Uses the Redux Reselect library to sort, filter and search our templates.
 * It also checks if the PDF templates are compatible with the current version of Gravity PDF
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

/* Assign specific parts of the Redux store to constants (note, we are returning functions) */
const getTemplates = (state) => state.template.list
const getSearch = (state) => state.template.search
const getActiveTemplate = (state) => state.template.activeTemplate

/**
 * Searches our templates for specific terms and returns the results
 * This function is adapted from the Backbone.js filter for themes
 *
 * @param {string} term
 * @param {Object} templates Immutable List of templates
 *
 * @returns {Object} Filtered Immutable list of templates
 *
 * @since 4.1
 */
export const searchTemplates = (term, templates) => {
  /*
   * Escape the term string for RegExp meta characters
   * Consider spaces as word delimiters and match the whole string
   */
  term = term.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
  term = term.replace(/ /g, ')(?=.*')

  const match = new RegExp('^(?=.*' + term + ').+', 'i')

  /* Filter through the templates. Any templates return "true" in out match.test() statement will be included */
  const results = templates.filter((template) => {

    /* Do very basic HTML tag removal from the fields we are interested in */
    const name = template.get('template').replace(/(<([^>]+)>)/ig, '')
    const description = template.get('description').replace(/(<([^>]+)>)/ig, '')
    const author = template.get('author').replace(/(<([^>]+)>)/ig, '')
    const group = template.get('group').replace(/(<([^>]+)>)/ig, '')

    /* Check if our matching term(s) are found in the string */
    return match.test([ name, template.get('id'), group, description, author ].toString())
  })

  return results
}

/**
 * A PDF template sorting function
 *
 * The sort order is as follows:
 *
 * 1. Any new templates get auto-shifted to the back of the list (just installed)
 * 2. The active template gets auto-shifted to the front of the list
 * 3. The templates are then sorted alphabetically by group
 * 4. Then alphabetically by name
 *
 * @param {Object} templates The Immutable list of templates
 * @param {string} activeTemplate The current active PDF template
 *
 * @returns {Object} Sorted Immutable List
 *
 * @since 4.1
 */
export const sortTemplates = (templates, activeTemplate) => {
  /* Sort out template list using our comparator function */
  return templates.sort((a, b) => {

    /* Shift new templates to the bottom (only on install) */
    if (a.get('new', false) === true && a.get('new', false) === true) {
      return 0 //equal
    }

    if (a.get('new', false) === true) {
      return 1
    }

    if (b.get('new', false) === true) {
      return -1
    }

    /* Hoist the active template above the rest */
    if (activeTemplate === a.get('id')) {
      return -1
    }

    if (activeTemplate === b.get('id')) {
      return 1
    }

    /* Order alphabetically by the group name */
    if (a.get('group') < b.get('group')) {
      return -1 //before
    }

    if (a.get('group') > b.get('group')) {
      return 1 //after
    }

    /* Then order alphabetically by the template name */
    if (a.get('template') < b.get('template')) {
      return -1 //before
    }

    if (a.get('template') > b.get('template')) {
      return 1 //after
    }

    return 0 //equal
  })
}

/**
 * Check all PDF templates for compatibility with the current verison of Gravity PDF
 * If they don't pass we'll also dynamically apply error messages
 *
 * @param {Object} templates The Immutable list of templates
 *
 * @returns {Object} The Immutable list of templates
 *
 * @since 4.1
 */
export const addCompatibilityCheck = (templates) => {
  /* Apply this function to all templates */
  return templates.map((template) => {
    /* Get the PDF version and check it against the Gravity PDF version */
    const requiredVersion = template.get('required_pdf_version')
    if (versionCompare(requiredVersion, GFPDF.currentVersion, '>')) {
      /* Not compatible, so let's mark it */
      return template.merge({
        'compatible': false,
        'error': GFPDF.requiresGravityPdfVersion.replace(/%s/g, requiredVersion),
        'long_error': GFPDF.templateNotCompatibleWithGravityPdfVersion.replace(/%s/g, requiredVersion)
      })
    }
    /* If versionCompare() passed we'll mark as true */
    return template.set('compatible', true)
  })
}

/**
 * Create our Reselect selector and apply to our store
 *
 * @since 4.1
 */
export default createSelector(
  [ getTemplates, getSearch, getActiveTemplate ],
  (templates, search, activeTemplate) => {

    templates = addCompatibilityCheck(templates)

    if (search) {
      templates = searchTemplates(search, templates)
    }

    return sortTemplates(templates, activeTemplate)
  }
)