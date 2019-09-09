import $ from 'jquery'
import coreFontBootstrap from '../../../../src/assets/js/react/bootstrap/coreFontBootstrap'

describe('coreFontBootstrap.spec.js', () => {

  beforeEach(function () {
    $('body')
      .append('<div id="gfpdf-install-core-fonts"><button class="button gfpdf-button">Test</button> Extra text that gets removed when React element mounted</div>')
  })

  afterEach(function () {
    $('#gfpdf-install-core-fonts').remove()
  })

  describe('coreFontBootstrap()', () => {
    it('Check the appropriate markup is generated in the DOM', () => {
      sinon.spy(coreFontBootstrap)

      expect($('#gfpdf-install-core-fonts').find('button').text()).is.equal('Test')
    })
  })
})
