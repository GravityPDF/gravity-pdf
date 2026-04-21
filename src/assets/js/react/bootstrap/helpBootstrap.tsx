/* Dependencies */
import { lazy, Suspense, createRoot } from '@wordpress/element';
import { Provider } from 'react-redux';
/* Redux store */
import { getStore } from '../store';
/* Components */
const HelpContainer = lazy(() => import('../components/Help/HelpContainer'));

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Mount our Help Search Input UI on the DOM
 *
 * @since 5.2
 */
export default function helpBootstrap(): void {
	const store = getStore();

	const container = document.getElementById('gpdf-search');

	const root = createRoot(container!);

	root.render(
		<Suspense fallback={<div>{GFPDF.spinnerAlt}</div>}>
			<Provider store={store}>
				<HelpContainer />
			</Provider>
		</Suspense>
	);
}
