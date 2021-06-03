import { Selector, t } from 'testcafe'
import { link } from '../helpers/field'

class FormSettings {
  constructor () {
    this.advancedLink = Selector('#gfpdf-advanced-nav')
    this.appearanceLink = Selector('#gfpdf-appearance-nav')
    this.conditionalCheckbox = Selector('div').find('[class^="gfpdf_settings_conditional conditional_logic_listener"]')
    this.templateLink = Selector('#gfpdf-custom-appearance-nav')
  }

  async navigateAdvancedLink () {
    await t
      .click(link('#tab_pdf', 'Add New'))
      .click(this.advancedLink)
  }

  async navigateAppearanceLink () {
    await t
      .click(link('#tab_pdf', 'Add New'))
      .click(this.appearanceLink)
  }

  async navigateTemplateLink () {
    await t
      .click(link('#tab_pdf', 'Add New'))
      .click(this.templateLink)
  }
}

export default FormSettings
