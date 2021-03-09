import React, { lazy, Suspense } from 'react'
import { render } from 'react-dom'
import { Provider } from 'react-redux'
import { HashRouter as Router, Route, Switch } from 'react-router-dom'
import Empty from '../components/Empty'

const TemplateList = lazy(() => import('../components/Template/TemplateList'))
const TemplateSingle = lazy(() => import('../components/Template/TemplateSingle'))

/**
 * React Router v3 Routes with our Redux store integrated
 *
 * Once React Router v4 becomes stable we'll update as required, or if we need to decouple our
 * routes for another module.
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * Contains the React Router Routes for our Advanced Template Selector.
 * We are using hashHistory instead of browserHistory so as not to affect the backend
 *
 * Routes include:
 *
 * /template/ (../components/TemplateList)
 * /template/:id (../components/TemplateSingle)
 * All other routes (../components/Empty)
 *
 * @since 4.1
 */
export const Routes = () => (
  <Suspense fallback={<div>{GFPDF.spinnerAlt}</div>}>
    <Router>
      <Switch>
        <Route
          path='/template'
          exact
          render={(props) => (
            <TemplateList
              {...props}
              ajaxUrl={GFPDF.ajaxUrl}
              ajaxNonce={GFPDF.ajaxNonce}
              templateDetailsText={GFPDF.templateDetails}
              templateHeaderText={GFPDF.installedPdfs}
              genericUploadErrorText={GFPDF.problemWithTheUpload}
              activateText={GFPDF.select}
              addTemplateText={GFPDF.addNewTemplate}
              filenameErrorText={GFPDF.uploadInvalidNotZipFile}
              filesizeErrorText={GFPDF.uploadInvalidExceedsFileSizeLimit}
              installSuccessText={GFPDF.templateSuccessfullyInstalled}
              installUpdatedText={GFPDF.templateSuccessfullyUpdated}
              templateSuccessfullyInstalledUpdated={GFPDF.templateSuccessfullyInstalledUpdated}
              templateInstallInstructions={GFPDF.templateInstallInstructions}
            />
          )}
        />
        <Route
          path='/template/:id'
          render={(props) => (
            <TemplateSingle
              {...props}
              ajaxUrl={GFPDF.ajaxUrl}
              ajaxNonce={GFPDF.ajaxNonce}
              pdfWorkingDirPath={GFPDF.pdfWorkingDir}
              activateText={GFPDF.select}
              templateDeleteText={GFPDF.delete}
              templateConfirmDeleteText={GFPDF.doYouWantToDeleteTemplate}
              templateDeleteErrorText={GFPDF.couldNotDeleteTemplate}
              currentTemplateText={GFPDF.currentTemplate}
              versionText={GFPDF.version}
              groupText={GFPDF.group}
              tagsText={GFPDF.tags}
              showPreviousTemplateText={GFPDF.showPreviousTemplate}
              showNextTemplateText={GFPDF.showNextTemplate}
            />
          )}
        />
        <Route component={Empty} />
      </Switch>
    </Router>
  </Suspense>)

/**
 * Setup React Router with our Redux Store
 *
 * @param {Object} store Redux Store
 *
 * @since 4.1
 */
export default function TemplatesRouter (store) {
  render((
    <Provider store={store}>
      <Routes />
    </Provider>), document.getElementById('gfpdf-overlay'))
}
