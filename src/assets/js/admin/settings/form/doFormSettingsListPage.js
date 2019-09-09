import { setupAJAXListDeleteListener } from './list/setupAJAXListDeleteListener'
import { setupAJAXListDuplicateListener } from './list/setupAJAXListDuplicateListener'
import { setupAJAXListStateListener } from './list/setupAJAXListStateListener'

/**
 * Process the functionality for the PDF form settings 'list' page
 * @return void
 * @since 4.0
 */
class DoFormSettingsListPage {
  setupAJAXListListener () {
    setupAJAXListDeleteListener()
    setupAJAXListDuplicateListener()
    setupAJAXListStateListener()
  }
}

export const doFormSettingsListPage = new DoFormSettingsListPage()
