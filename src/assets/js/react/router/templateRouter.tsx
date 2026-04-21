/* Dependencies */
import { lazy, Suspense, createRoot } from '@wordpress/element';
import { Routes as Switch, Route } from 'react-router-dom';
/* Components */
import Empty from '../components/Empty';
import CustomHashRouter from '../components/CustomHashRouter';
import withRouterHooks from '../utilities/withRouterHooks';
const TemplateList = lazy(() => import('../components/Template/TemplateList'));
const TemplateSingle = lazy(
	() => import('../components/Template/TemplateSingle')
);

/**
 * React Router Routes for our Advanced Template Selector.
 * We are using hashHistory instead of browserHistory so as not to affect the backend.
 *
 * Routes include:
 *
 * /template/ (../components/TemplateList)
 * /template/:id (../components/TemplateSingle)
 * All other routes (../components/Empty)
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

export const Routes = (): JSX.Element => (
	<Suspense fallback={<div>{GFPDF.spinnerAlt}</div>}>
		<CustomHashRouter>
			<Switch>
				<Route
					path="/template"
					element={
						<TemplateList
							ajaxUrl={GFPDF.ajaxUrl}
							ajaxNonce={GFPDF.ajaxNonce}
							templateDetailsText={GFPDF.templateDetails}
							templateHeaderText={GFPDF.installedPdfs}
							genericUploadErrorText={GFPDF.problemWithTheUpload}
							activateText={GFPDF.select}
							addTemplateText={GFPDF.addNewTemplate}
							filenameErrorText={GFPDF.uploadInvalidNotZipFile}
							filesizeErrorText={
								GFPDF.uploadInvalidExceedsFileSizeLimit
							}
							installSuccessText={
								GFPDF.templateSuccessfullyInstalled
							}
							installUpdatedText={
								GFPDF.templateSuccessfullyUpdated
							}
							templateSuccessfullyInstalledUpdated={
								GFPDF.templateSuccessfullyInstalledUpdated
							}
							templateInstallInstructions={
								GFPDF.templateInstallInstructions
							}
						/>
					}
				/>
				<Route
					path="/template/:id"
					element={
						<TemplateSingleWithRouter
							ajaxUrl={GFPDF.ajaxUrl}
							ajaxNonce={GFPDF.ajaxNonce}
							pdfWorkingDirPath={GFPDF.pdfWorkingDir}
							activateText={GFPDF.select}
							templateDeleteText={GFPDF.delete}
							templateConfirmDeleteText={
								GFPDF.doYouWantToDeleteTemplate
							}
							templateDeleteErrorText={
								GFPDF.couldNotDeleteTemplate
							}
							currentTemplateText={GFPDF.currentTemplate}
							versionText={GFPDF.version}
							groupText={GFPDF.group}
							tagsText={GFPDF.tags}
							showPreviousTemplateText={
								GFPDF.showPreviousTemplate
							}
							showNextTemplateText={GFPDF.showNextTemplate}
						/>
					}
				/>
				<Route path="*" element={<Empty />} />
			</Switch>
		</CustomHashRouter>
	</Suspense>
);

const TemplateSingleWithRouter = withRouterHooks(TemplateSingle);

/**
 * Setup React Router for the Template Selector — no Provider needed,
 * components read state directly from the @wordpress/data global registry.
 *
 * @since 4.1
 */
export default function TemplatesRouter(): void {
	const container = document.getElementById('gfpdf-overlay');

	const root = createRoot(container!);

	root.render(<Routes />);
}
