/* Dependencies */
import { lazy, Suspense, createRoot } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
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
	<Suspense fallback={<div />}>
		<CustomHashRouter>
			<Switch>
				<Route
					path="/template"
					element={
						<TemplateList
							ajaxUrl={GFPDF.ajaxUrl}
							ajaxNonce={GFPDF.ajaxNonce}
							templateDetailsText={__('Template Details', 'gravity-pdf')}
							templateHeaderText={__('Installed PDFs', 'gravity-pdf')}
							genericUploadErrorText={__('There was a problem with the upload. Reload the page and try again.', 'gravity-pdf')}
							activateText={__('Select', 'gravity-pdf')}
							addTemplateText={__('Add New Template', 'gravity-pdf')}
							filenameErrorText={__('Upload is not a valid template. Upload a .zip file.', 'gravity-pdf')}
							filesizeErrorText={__('Upload exceeds the 10MB limit.', 'gravity-pdf')}
							installSuccessText={__('Template successfully installed', 'gravity-pdf')}
							installUpdatedText={__('Template successfully updated', 'gravity-pdf')}
							templateSuccessfullyInstalledUpdated={__('PDF Template(s) Successfully Installed / Updated', 'gravity-pdf')}
							templateInstallInstructions={__('If you have a PDF template in .zip format you may install it here. You can also update an existing PDF template (this will override any changes you have made).', 'gravity-pdf')}
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
							activateText={__('Select', 'gravity-pdf')}
							templateDeleteText={__('Delete', 'gravity-pdf')}
							templateConfirmDeleteText={sprintf(
								__("Do you really want to delete this PDF template?%sClick 'Cancel' to go back, 'OK' to confirm the delete.", 'gravity-pdf'),
								'\n\n'
							)}
							templateDeleteErrorText={__('Could not delete template.', 'gravity-pdf')}
							currentTemplateText={__('Current Template', 'gravity-pdf')}
							versionText={__('Version', 'gravity-pdf')}
							groupText={__('Group', 'gravity-pdf')}
							tagsText={__('Tags', 'gravity-pdf')}
							showPreviousTemplateText={__('Show previous template', 'gravity-pdf')}
							showNextTemplateText={__('Show next template', 'gravity-pdf')}
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
