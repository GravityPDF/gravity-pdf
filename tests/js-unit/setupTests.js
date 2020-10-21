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
  userCapabilities: { administrator: true },
  // Font manager component
  fontListInstalledFonts: 'Installed Fonts',
  fontListRegular: 'Regular',
  fontListItalics: 'Italics',
  fontListBold: 'Bold',
  fontListBoldItalics: 'Bold Italics',
  fontManagerAddTitle: 'Add Font',
  fontManagerUpdateTitle: 'Update Font',
  fontListRegularRequired: 'Regular',
  searchResultEmpty: 'No fonts matching your search found.',
  fontListEmpty: 'Font list empty.',
  fontManagerFontFilesLabel: 'Font Files',
  fontManagerTemplateTooltipLabel: 'View template usage',
  addUpdateFontSuccess: 'Your font has been saved.',
  addFatalError: 'A problem occurred. Reload the page and try again.',
  fontFileInvalid: 'Font file(s) are malformed and cannot be used with Gravity PDF',
  manage: 'Advanced',
}

window.gfpdf_migration_multisite_ids = []
