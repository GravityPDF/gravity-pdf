import $ from 'jquery'
import { createStore } from 'redux'
import { selectTemplate } from '../../../../src/assets/js/react/actions/templates'
import {
  createTemplateMarkup,
  activeTemplateStoreListener
} from '../../../../src/assets/js/react/bootstrap/templateBootstrap'
import { setupReducers } from '../../../../src/assets/js/react/store'

describe('templateBootstrap.spec.js', () => {

  beforeEach(function () {
    $('body')
      .append('<div id="karma-test-button"><input id="test-input"><span></span></div>')
  })

  afterEach(function () {
    $('#karma-test-button').remove()
  })

  describe('createTemplateMarkup()', () => {
    it('Check the appropriate markup is generated in the DOM', () => {
      createTemplateMarkup($('#test-input'))

      expect($('#gpdf-advance-template-selector').length).is.equal(1)
      expect($('#gfpdf-overlay').length).is.equal(1)
    })
  })

  describe('activeTemplateStoreListener()', () => {
    it('Check the input field gets updated when our Redux store changes', () => {
      /* Setup our redux store and trigger our activeTemplate updater */
      const store = createStore(setupReducers())
      activeTemplateStoreListener(store, $('#test-input'))
      store.dispatch(selectTemplate('zadani'))

      /* Test it all worked */
      expect($('#test-input').val()).is.equal('zadani')

    })
  })
})
