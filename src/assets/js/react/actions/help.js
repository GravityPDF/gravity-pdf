import {
  UPDATE_RESULTS,
  DELETE_RESULTS
} from '../actionTypes/help'


export const updateResult = data => {
  return {
    type: UPDATE_RESULTS,
    payload: data
  }
}

export const deleteResult = () => {
  return {
    type: DELETE_RESULTS
  }
}