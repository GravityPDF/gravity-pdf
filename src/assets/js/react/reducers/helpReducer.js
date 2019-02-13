import {
  UPDATE_RESULTS,
  DELETE_RESULTS
} from '../actionTypes/help'

export const initialState = {
  results: [],
}


export default function(state = initialState, action) {
  switch (action.type) {
    case UPDATE_RESULTS:
      return {
        ...state,
        results: action.payload
      }
    case DELETE_RESULTS:
      return {
        ...state,
        results: []
      }
    default:
      return state
  }
}