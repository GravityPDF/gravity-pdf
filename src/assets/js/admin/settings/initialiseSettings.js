import $ from 'jquery'
import { initialiseCommonElements } from './common/initialiseCommonElements'
import { cleanupGFNavigation } from './global/cleanupGFNavigation'
import { runPDFAccessCheck } from './global/runPDFAccessCheck'
import { generalSettings } from './global/generalSettings'
import { toolsSettings } from './global/toolsSettings'
import { doFormSettingsListPage } from './form/doFormSettingsListPage'
import { doFormSettingsEditPage } from './pdf/doFormSettingsEditPage'
import { pages } from './pages'

/**
 * Process the correct settings area (the global PDF settings or individual form PDF settings)
 * Also set up any event listeners needed
 * @return void
 * @since 4.0
 */
class InitialiseSettings {
  init () {
    /* Process any common functions */
    initialiseCommonElements.runElements()

    /* Process the global PDF settings */
    if (pages.isSettings()) {
      this.processSettings()
    }

    /* Process the individual form PDF settings */
    if (pages.isFormSettings()) {
      this.processFormSettings()
    }
  }

  /**
   * Check the current active PDF settings page
   * @return String
   * @since 4.0
   */
  getCurrentSettingsPage () {
    if (pages.isSettings()) {
      return $('.gform-settings-tabs__navigation a.active:first').data('id')
    }
    return ''
  }

  /**
   * Process the global settings page
   * @return void
   * @since 4.0
   */
  processSettings () {
    /* Ensure the Gravity Forms settings navigation (Form Settings / Notifications / Confirmation) has the 'tab' URI stripped from it */
    cleanupGFNavigation()

    /* Run our direct PDF status check */
    runPDFAccessCheck()

    /* Run the appropriate settings page */
    switch (this.getCurrentSettingsPage()) {
      case 'general':
        generalSettings()
        break

      case 'tools':
        toolsSettings.runSetup()
        break
    }
  }

  /**
   * Routing functionality for the individual form settings page
   * @return void
   * @since 4.0
   */
  processFormSettings () {
    /* Process PDF list page */
    if (pages.isFormSettingsList()) {
      doFormSettingsListPage.setupAJAXListListener()
    }

    /* Process single edit page */
    if (pages.isFormSettingsEdit()) {
      doFormSettingsEditPage()
    }
  }
}

export const initialiseSettings = new InitialiseSettings()
