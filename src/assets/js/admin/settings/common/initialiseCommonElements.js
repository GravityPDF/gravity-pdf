import { setupGravityForms } from './setupGravityForms'
import { doUploadListener } from './doUploadListener'
import { doColorPicker } from './doColorPicker'
import { setupCustomPaperSize } from './setupCustomPaperSize'
import { setupToggledFields } from './setupToggledFields'
import { setupDynamicTemplateFields } from './setupDynamicTemplateFields'
import { setupLicenseDeactivation } from './setupLicenseDeactivation'

/**
 * Initialise any common elements
 * @return void
 * @since 4.0
 */
class InitialiseCommonElements {
  runElements () {
    /* Change some Gravity Forms parameters */
    setupGravityForms()

    /* If we have a upload field handle the logic */
    doUploadListener()

    /* If we have a colour picker handle the logic */
    doColorPicker()

    /* Setup custom paper size, if needed */
    setupCustomPaperSize()

    /* Setup toggled fields, if needed */
    setupToggledFields()

    /* Setup our template loader, if needed */
    setupDynamicTemplateFields()

    /* Setup license deactivation, if needed */
    setupLicenseDeactivation()
  }
}

export const initialiseCommonElements = new InitialiseCommonElements()
