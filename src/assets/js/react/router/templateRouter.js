import React from 'react'

import { render } from 'react-dom'
import { Provider } from 'react-redux'

import { HashRouter as Router, Route, Switch } from 'react-router-dom'

import TemplateList from '../components/TemplateList'
import TemplateSingle from '../components/TemplateSingle'
import Empty from '../components/Empty'

/**
 * React Router v3 Routes with our Redux store integrated
 *
 * Once React Router v4 becomes stable we'll update as required, or if we need to decouple our
 * routes for another module.
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
  <Router>
    <Switch>
      <Route path="/template"
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

      <Route path="/template/:id"
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

      <Route component={Empty}/>
    </Switch>
  </Router>)

/**
 * Setup React Router with our Redux Store
 *
 * @param {Object} store Redux Store
 *
 * @since 4.1
 */
export default function TemplatesRouter (store) {
  render((<Provider store={store}>
    <Routes />
  </Provider>), document.getElementById('gfpdf-overlay'))
}