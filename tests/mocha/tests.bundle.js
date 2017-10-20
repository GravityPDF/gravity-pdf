import $ from 'jquery'
import Enzyme from 'enzyme'
import Adapter from 'enzyme-adapter-react-16'

Enzyme.configure({adapter: new Adapter()})

/* React 16 Shim */
window.requestAnimationFrame = function (callback) {
  setTimeout(callback, 0)
}

// setup global defaults that our tests expect is present
window.GFPDF = {
  templateList: [{id: 'zadani'}, {id: 'rubix'}, {id: 'focus-gravity'}],
  activeTemplate: ''
}

// add IE support for remove()
Element.prototype.remove = function () {
  this.parentElement.removeChild(this)
}

NodeList.prototype.remove = HTMLCollection.prototype.remove = function () {
  for (var i = this.length - 1; i >= 0; i--) {
    if (this[i] && this[i].parentElement) {
      this[i].parentElement.removeChild(this[i])
    }
  }
}

// setup global before and after code
beforeEach(function () {
  $('body')
    .append('<div id="karam-test-container">')
})

afterEach(function () {
  $('#karma-test-container').remove()
})

// require all modules ending in "_test" from the
// current directory and all subdirectories
var testsContext = require.context('.', true, /.+\.spec\.jsx?$/)
testsContext.keys().forEach(testsContext)