import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
} from '../actionTypes/templates'

export const searchTemplates = (text) => {
  return {
    type: SEARCH_TEMPLATES,
    text
  }
}

export const selectTemplate = (id) => {
  return {
    type: SELECT_TEMPLATE,
    id
  }
}