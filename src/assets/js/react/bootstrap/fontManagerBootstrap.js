/* Dependencies */
import React, { lazy, Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import { Route, Routes } from 'react-router-dom';
/* Routes */
import { fontManagerRouter } from '../router/fontManagerRouter';
/* Redux store */
import { getStore } from '../store';
/* Helpers */
import withRouterHooks from '../utilities/withRouterHooks.js';
/* Components */
import CustomHashRouter from '../components/CustomHashRouter';
import Empty from '../components/Empty';
const AdvancedButton = lazy(
	() => import('../components/FontManager/AdvancedButton')
);

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Mount our font manager advanced button on the DOM
 *
 * @param { HTMLDivElement } defaultFontField
 * @param { string }         buttonStyle
 *
 * @since 6.0
 */
export function fontManagerBootstrap(defaultFontField, buttonStyle) {
	const store = getStore();
	/* Prevent button reset styling on tools tab */
	const preventButtonReset = !buttonStyle ? '' : buttonStyle;

	createAdvancedButtonWrapper(defaultFontField, preventButtonReset);

	const container = document.querySelector(
		'#gpdf-advance-font-manager-selector' + preventButtonReset
	);

	const root = createRoot(container);

	root.render(
		<Suspense fallback={<div>{GFPDF.spinnerAlt}</div>}>
			<CustomHashRouter>
				<Routes>
					<Route
						path="/"
						element={<AdvancedButtonWithRouter store={store} />}
					/>
					<Route path="*" element={<Empty />} />
				</Routes>
			</CustomHashRouter>
		</Suspense>
	);

	fontManagerRouter(store);
}

const AdvancedButtonWithRouter = withRouterHooks(AdvancedButton);

/**
 * Create html element wrapper for our font manager advanced button
 *
 * @param { HTMLDivElement } defaultFontField
 * @param { string }         preventButtonReset
 *
 * @since 6.0
 */
export function createAdvancedButtonWrapper(
	defaultFontField,
	preventButtonReset
) {
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
		wrapper.innerHTML = defaultFontField.outerHTML;
		wrapper.appendChild(fontWrapper);
		wrapper.appendChild(popupWrapper);
		defaultFontField.outerHTML = wrapper.outerHTML;
	} else {
		defaultFontField.appendChild(fontWrapper);
		defaultFontField.appendChild(popupWrapper);
	}
}
