import { Selector } from 'testcafe'

class PdfTemplateEntries {
  constructor () {
    this.name = Selector('#gfpdf_settings\\[name\\]')
    this.fileName = Selector('#gfpdf_settings\\[filename\\]')
    this.template = Selector('.alternate')
    this.backToTemplateListLink = Selector('a').withText('Back to PDF list.')
    this.templateList = Selector('#the-list')
    this.templateDetail = Selector('.alternate').find('td').nth(0)
    this.toggleSwitch = Selector('.check-column').find('img')
    this.inActiveTemplate = Selector('div').find('[alt^="Inactive"][title="Inactive"]')
    this.activeTemplate = Selector('div').find('[alt^="Active"][title="Active"]')
    this.entryItem = Selector('a').withAttribute('aria-label', 'View this entry')
    this.viewPdfLink = Selector('a').withText('View PDF')
    this.enableConditionalLogic = Selector('#gfpdf_conditional_logic')
    this.editLink = Selector('span').withText('Edit')
    this.updatePdfButton = Selector('div').find('[class^="button-primary"][value="Update PDF"]')
    this.options = Selector('div').find('[class^="name column-name has-row-actions column-primary"]')
    this.duplicateLink = Selector('a').withText('Duplicate')
  }
}

export default PdfTemplateEntries
