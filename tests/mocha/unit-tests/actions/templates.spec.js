import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
  ADD_TEMPLATE,
  UPDATE_TEMPLATE,
  UPDATE_TEMPLATE_PARAM,
  DELETE_TEMPLATE,
} from '../../../../src/assets/js/react/actionTypes/templates'
import {
  searchTemplates,
  selectTemplate,
  addTemplate,
  updateTemplate,
  updateTemplateParam,
  deleteTemplate
} from '../../../../src/assets/js/react/actions/templates'

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

describe('addTemplate', () => {
  it('check it returns the correct action', () => {
    let results = addTemplate('my-id')
    expect(results.template).is.equal('my-id')
    expect(results.type).is.equal(ADD_TEMPLATE)
  })
})

describe('updateTemplate', () => {
  it('check it returns the correct action', () => {
    let results = updateTemplate('my-id')
    expect(results.template).is.equal('my-id')
    expect(results.type).is.equal(UPDATE_TEMPLATE)
  })
})

describe('updateTemplateParam', () => {
  it('check it returns the correct action', () => {
    let results = updateTemplateParam('my-id', 'my-name', 'my-value')
    expect(results.id).is.equal('my-id')
    expect(results.name).is.equal('my-name')
    expect(results.value).is.equal('my-value')
    expect(results.type).is.equal(UPDATE_TEMPLATE_PARAM)
  })
})

describe('deleteTemplate', () => {
  it('check it returns the correct action', () => {
    let results = deleteTemplate('my-id')
    expect(results.id).is.equal('my-id')
    expect(results.type).is.equal(DELETE_TEMPLATE)
  })
})