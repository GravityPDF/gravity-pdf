import { createSelector } from 'reselect'
import union from 'lodash.union'

const getTemplates = (state) => state.template.list
const getSearch = (state) => state.template.search
const getActiveTemplate = (state) => state.template.activeTemplate

/* Our template search function */
export const searchTemplates = (term, templates) => {
  // Escape the term string for RegExp meta characters
  // Consider spaces as word delimiters and match the whole string
  term = term.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
  term = term.replace(/ /g, ')(?=.*')

  const match = new RegExp('^(?=.*' + term + ').+', 'i')

  const results = templates.filter((template) => {
    const name = template.get('template').replace(/(<([^>]+)>)/ig, '')
    const description = template.get('description').replace(/(<([^>]+)>)/ig, '')
    const author = template.get('author').replace(/(<([^>]+)>)/ig, '')
    const group = template.get('group').replace(/(<([^>]+)>)/ig, '')

    var haystack = union([ name, template.get('id'), group, description, author ])

    return match.test(haystack)
  })

  return results
}

/* Our template sort function */
export const sortTemplates = (templates, activeTemplate) => {
  return templates.sort(function (a, b) {
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

/* Filter results by search, and then sort */
export default createSelector(
  [ getTemplates, getSearch, getActiveTemplate ],
  (templates, search, activeTemplate) => {

    if (search) {
      templates = searchTemplates(search, templates)
    }

    return sortTemplates(templates, activeTemplate)
  }
)