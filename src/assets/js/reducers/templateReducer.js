import { fromJS } from 'immutable'
import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
} from '../actionTypes/templates'

const templates = fromJS(GFPDF.templateList)

export const initialState = {
  list: templates,
  activeTemplate: GFPDF.activeTemplate || GFPDF.activeDefaultTemplate,
  search: '',
}

export default function (state = initialState, action) {

  switch (action.type) {
    case SEARCH_TEMPLATES:
      return {
        ...state,
        search: action.text
      }

    case SELECT_TEMPLATE:
      return {
        ...state,
        activeTemplate: action.id
      }
  }

  return state;
}