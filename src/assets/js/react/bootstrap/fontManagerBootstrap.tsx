/* Dependencies */
import { lazy, Suspense, createRoot, createPortal } from '@wordpress/element';
import { HashRouter, Routes, Route, useLocation } from 'react-router';
/* Helpers */
import withRouterHooks from '../utilities/withRouterHooks';
/* Components */
import FontManager from '../components/FontManager/FontManager';
import Empty from '../components/Empty';

const AdvancedButton = lazy(
	() => import('../components/FontManager/AdvancedButton')
);

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

const AdvancedButtonWithRouter = withRouterHooks(AdvancedButton);
const FontManagerWithRouter = withRouterHooks(FontManager);

interface AppProps {
	buttonContainer: Element;
}

const FontManagerApp = ({ buttonContainer }: AppProps) => {
	const { pathname } = useLocation();
	const isOpen = pathname.startsWith('/fontmanager');

	return (
		<>
			{/* Portal renders the button in its sibling DOM node; hidden while modal is open */}
			{!isOpen &&
				createPortal(<AdvancedButtonWithRouter />, buttonContainer)}

			<Routes>
				<Route
					path="/fontmanager/"
					element={<FontManagerWithRouter />}
				/>
				<Route
					path="/fontmanager/:id"
					element={<FontManagerWithRouter />}
				/>
				<Route path="*" element={<Empty />} />
			</Routes>
		</>
	);
};

/**
 * Mount the font manager button and overlay as a single React root.
 *
 * @param defaultFontField
 * @param buttonStyle
 * @since 6.0
 */
export function fontManagerBootstrap(
	defaultFontField: Element,
	buttonStyle?: string
): void {
	/* Prevent button reset styling on tools tab */
	const preventButtonReset = !buttonStyle ? '' : buttonStyle;

	createAdvancedButtonWrapper(defaultFontField, preventButtonReset);

	const buttonContainer = document.querySelector(
		'#gpdf-advance-font-manager-selector' + preventButtonReset
	)!;
	const overlayContainer = document.querySelector('#font-manager-overlay')!;

	createRoot(overlayContainer).render(
		<Suspense fallback={<div />}>
			<HashRouter>
				<FontManagerApp buttonContainer={buttonContainer} />
			</HashRouter>
		</Suspense>
	);
}

/**
 * Create html element wrapper for our font manager advanced button
 *
 * @param defaultFontField
 * @param preventButtonReset
 * @since 6.0
 */
export function createAdvancedButtonWrapper(
	defaultFontField: Element,
	preventButtonReset: string
): void {
	const fontWrapper = document.createElement('span');
	fontWrapper.setAttribute(
		'id',
		'gpdf-advance-font-manager-selector' + preventButtonReset
	);

	const popupWrapper = document.createElement('div');
	popupWrapper.setAttribute('id', 'font-manager-overlay');
	popupWrapper.setAttribute('class', 'theme-overlay');

	if (defaultFontField.nodeName === 'SELECT') {
		const wrapper = document.createElement('div');
		wrapper.setAttribute(
			'id',
			'gfpdf-settings-field-wrapper-font-container'
		);
		wrapper.innerHTML = (defaultFontField as HTMLElement).outerHTML;
		wrapper.appendChild(fontWrapper);
		wrapper.appendChild(popupWrapper);
		(defaultFontField as HTMLElement).outerHTML = wrapper.outerHTML;
	} else {
		defaultFontField.appendChild(fontWrapper);
		defaultFontField.appendChild(popupWrapper);
	}
}
