import $ from 'jquery'
import helpBootstrap from '../../../../src/assets/js/react/bootstrap/helpBootstrap'

describe('helpBootstrap.spec.js', () => {

  beforeEach(function () {
    $('body')
      .append('<div id="search-knowledgebase"><input type="text" placeholder="  Search the Gravity PDF Knowledgebase..." id="search-help-input" name="searchInput" value=""></div>')
  })

  afterEach(function () {
    $('#search-knowledgebase').remove()
  })

  describe('helpBootstrap()', () => {
    it('Check the appropriate markup is generated in the DOM', () => {
      sinon.spy(helpBootstrap)
      expect($('#search-knowledgebase').find('input#search-help-input')).to.have.lengthOf(1)
    })
  })
})
