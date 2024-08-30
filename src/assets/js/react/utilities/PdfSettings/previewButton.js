import { getPdfPreview, getTemplateSchema } from '../../api/preview'
import { getCurrentPdfSettingsForApi, triggerFakeFormSubmit } from './formSettings'
import { viewFile } from '../download'
import { spinner } from '../../../admin/helper/spinner'
import $ from 'jquery'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Setup PDF Preview functionality in PDF settings
 *
 * @since 6.12
 */
export default function () {
  // capture bubbled click event for the Preview buttons on the form
  document.getElementById('gfpdf_pdf_form').addEventListener('click', async function (e) {
    // ignore if wasn't triggered by Preview field
    if (e.target.name !== 'gpdf-preview-pdf-settings') {
      return
    }

    e.preventDefault()
    e.stopImmediatePropagation()

    const $spinner = spinner('gfpdf-spinner-template')
    $(e.target).after($spinner)

    // save JS-powered field data
    triggerFakeFormSubmit('gfpdf_pdf_form')

    // get the current template schema
    const template = document.getElementById('gfpdf_settings[template]').value
    const templateSchema = await getTemplateSchema(form.id, template)
    if (!templateSchema) {
      $spinner.remove()
      window.alert(GFPDF.getPreviewResultError)
      return
    }

    // get the current PDF settings
    const formData = getCurrentPdfSettingsForApi(templateSchema)
    formData.append('form', form.id)

    // generate PDF preview
    const pdfBlob = await getPdfPreview(formData)
    if (!pdfBlob) {
      $spinner.remove()
      window.alert(GFPDF.getPreviewResultError)
      return
    }

    $spinner.remove()

    viewFile(pdfBlob)
  })
}
