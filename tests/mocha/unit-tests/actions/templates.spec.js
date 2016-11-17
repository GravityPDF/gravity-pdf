import { SEARCH_TEMPLATES, SELECT_TEMPLATE } from '../../../../src/assets/js/actionTypes/templates'
import { searchTemplates, selectTemplate } from '../../../../src/assets/js/actions/templates'

describe('searchTemplate', () => {
  it('check it returns the correct action', () => {
    let results = searchTemplates('My Text')
    expect(results.text).is.equal('My Text')
    expect(results.type).is.equal(SEARCH_TEMPLATES)
  })
})

describe('selectTemplate', () => {
  it('check it returns the correct action', () => {
    let results = selectTemplate('my-id')
    expect(results.id).is.equal('my-id')
    expect(results.type).is.equal(SELECT_TEMPLATE)
  })
})