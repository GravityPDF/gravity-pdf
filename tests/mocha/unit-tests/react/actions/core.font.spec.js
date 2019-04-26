import {
  ADD_TO_CONSOLE,
  CLEAR_CONSOLE,
  ADD_TO_RETRY_LIST,
  CLEAR_RETRY_LIST
} from '../../../../../src/assets/js/react/actionTypes/coreFonts'
import {
  addToConsole,
  clearConsole,
  addToRetryList,
  clearRetryList,
} from '../../../../../src/assets/js/react/actions/coreFonts'

describe('addToConsole', () => {
  it('check it returns the correct action', () => {
    let results = addToConsole('key', 'status', 'message')
    expect(results.key).is.equal('key')
    expect(results.status).is.equal('status')
    expect(results.message).is.equal('message')
    expect(results.type).is.equal(ADD_TO_CONSOLE)
  })
})

describe('clearConsole', () => {
  it('check it returns the correct action', () => {
    let results = clearConsole()
    expect(results.type).is.equal(CLEAR_CONSOLE)
  })
})

describe('addToConsole', () => {
  it('check it returns the correct action', () => {
    let results = addToRetryList('name')
    expect(results.name).is.equal('name')
    expect(results.type).is.equal(ADD_TO_RETRY_LIST)
  })
})

describe('clearRetryList', () => {
  it('check it returns the correct action', () => {
    let results = clearRetryList()
    expect(results.type).is.equal(CLEAR_RETRY_LIST)
  })
})