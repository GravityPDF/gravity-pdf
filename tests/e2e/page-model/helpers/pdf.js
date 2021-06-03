import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../auth'
import { link } from './field'

class Pdf {
  constructor () {
    this.pdfname = Selector('#gfpdf_settings\\[name\\]')
    this.fileName = Selector('#gfpdf_settings\\[filename\\]')
    this.addPdfButton = Selector('div').find('[class^="button-primary"][value="Add PDF"]')
    this.template = Selector('.alternate')
    this.deletePDF = Selector('.submitdelete')
    this.confirmDelete = Selector('button').withText('Delete')
  }

  async navigateAddPdf (text) {
    await t
      .setNativeDialogHandler(() => true)
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
      .click(link('#tab_pdf', 'Add New'))
      .typeText(this.pdfname, 'Test PDF Template', { paste: true })
      .typeText(this.fileName, 'testpdftemplate', { paste: true })
      .click(this.addPdfButton)
  }

  async navigatePdfSection (text) {
    await t
      .setNativeDialogHandler(() => true)
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
  }

  async navigateDeletePdfEntries (text) {
    await t
      .setNativeDialogHandler(() => true)
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
    let tempalte = await this.template.count
    if (tempalte > 0) {
      for (let i = 0; i < tempalte; i++) {
        await t
          .hover(this.template)
          .click(this.deletePDF)
          .click(this.confirmDelete)
          .wait(2000)
      }
    }
  }
}

export default Pdf
