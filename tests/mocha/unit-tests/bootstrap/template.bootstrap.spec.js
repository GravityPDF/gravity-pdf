import cheerio from 'cheerio'
import { createStore } from 'redux'

import { selectTemplate } from '../../../../src/assets/js/actions/templates'

import {
  createTemplateMarkup,
  activeTemplateStoreListener,
  setupReducers
} from '../../../../src/assets/js/bootstrap/templateBootstrap'

describe('createTemplateMarkup()', () => {
  it('Check the appropriate markup is generated in the DOM', () => {
    let $ = cheerio.load('<input id="test-input"><span></span>')
    createTemplateMarkup($('#test-input'))

    expect($('#gpdf-advance-template-selector').length).is.equal(1)
    expect($('#gfpdf-overlay').length).is.equal(1)
  })
})

describe('activeTemplateStoreListener()', () => {
  it('Check the input field gets updated when our Redux store changes', () => {
    let $ = cheerio.load('<input id="test-input">')
    $.prototype.trigger = function () { return this } //cheerio doesn't support trigger so mock it

    /* Setup our redux store and trigger our activeTemplate updater */
    const store = createStore(setupReducers())
    activeTemplateStoreListener(store, $('#test-input'))
    store.dispatch(selectTemplate('zadani'))

    /* Test it all worked */
    expect($('#test-input').val()).is.equal('zadani')

  })
})

