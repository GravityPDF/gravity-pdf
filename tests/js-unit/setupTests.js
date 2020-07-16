import Enzyme from 'enzyme'
import EnzymeAdapter from 'enzyme-adapter-react-16'

Enzyme.configure({
  adapter: new EnzymeAdapter(),
  disableLifecycleMethods: true
})

// setup global defaults that our tests/legacy code expect is present
window.GFPDF = {
  templateList: [{ id: 'zadani' }, { id: 'rubix' }, { id: 'focus-gravity' }],
  activeTemplate: '',
  coreFontItemPendingMessage: '%s',
  coreFontItemSuccessMessage: '%s',
  coreFontItemErrorMessage: '%s',
  noResultText: "It doesn\'t look like there are any topics related to your issue.",
  coreFontGithubError: 'Could not download Core Font list. Try again.',
  getSearchResultError: 'An error occurred. Please try again',
  userCapabilities: { administrator: true }
}

window.gfpdf_migration_multisite_ids = []
