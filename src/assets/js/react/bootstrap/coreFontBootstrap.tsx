/* Dependencies */
import { lazy, Suspense, createRoot } from '@wordpress/element';
/* Routes */
const Routes = lazy(() => import('../router/coreFontRouter'));

/**
 * Core Font Downloader Bootstrap
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Mount our Core Font UI on the DOM
 *
 * @since 5.0
 */
export default function coreFontBootstrap(): void {
	const container = document.getElementById(
		'gfpdf-button-wrapper-install_core_fonts'
	);
	const button = container!.getElementsByTagName('button')[0];

	const root = createRoot(container!);

	root.render(
		<Suspense fallback={<div />}>
			<Routes button={button} />
		</Suspense>
	);
}
