import { setupToolsTemplateInstallerDialog } from './tools/setupToolsTemplateInstallerDialog'
import { setupToolsFontsDialog } from './tools/setupToolsFontsDialog'
import { setupToolsUninstallDialog } from './tools/setupToolsUninstallDialog'

/**
 * The tools settings model method
 * This sets up and processes any of the JS that needs to be applied on the tools settings tab
 * @since 4.0
 */
class ToolsSettings {
  runSetup () {
    setupToolsTemplateInstallerDialog()
    setupToolsFontsDialog()
    setupToolsUninstallDialog()
  }
}

export const toolsSettings = new ToolsSettings()
