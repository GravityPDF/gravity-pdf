import coreFontBootstrap from '../../../../src/assets/js/react/bootstrap/coreFontBootstrap'

describe('bootstrap.spec.js', () => {

  beforeEach(function () {
    $('body')
      .append('<div id="gfpdf-install-core-fonts"><button>Test</button> Extra text that gets removed when React element mounted</div>')
  })

  afterEach(function () {
    $('#gfpdf-install-core-fonts').remove()
  })

  describe('coreFontBootstrap()', () => {
    it('Check the appropriate markup is generated in the DOM', () => {
      coreFontBootstrap()
      expect($('#gfpdf-install-core-fonts').find('button').text()).is.equal('Test')
    })
  })

})
