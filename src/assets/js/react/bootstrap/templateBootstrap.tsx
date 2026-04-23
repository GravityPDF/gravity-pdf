/* Dependencies */
import { lazy, Suspense, createRoot } from '@wordpress/element';
import { Routes as Switch, Route } from 'react-router-dom';
import { subscribe, select, dispatch as wpDispatch } from '@wordpress/data';
/* Store name */
import { TEMPLATE_STORE_NAME, templateStore } from '../store/templateStore';
/* Routes */
import templateRouter from '../router/templateRouter';
/* Helpers */
import withRouterHooks from '../utilities/withRouterHooks';
/* Components */
import { HashRouter } from 'react-router-dom';
import Empty from '../components/Empty';
const TemplateButton = lazy(
	() => import('../components/Template/TemplateButton')
);

/**
 * Advanced Template Selector Bootstrap
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * Handles the loading of our Fancy Template Selector
 *
 * @param templateField
 * @since 4.1
 */
export function templateBootstrap(templateField: HTMLSelectElement): void {
	/* Create our button container and render our component in it */
	createTemplateMarkup(templateField);

	const container = document.getElementById('gpdf-advance-template-selector');

	const root = createRoot(container!);

	/* Render our React Component in the DOM */
	root.render(
		<Suspense fallback={<div />}>
			<HashRouter>
				<Switch>
					<Route path="/" element={<TemplateButtonWithRouter />} />
					<Route path="*" element={<Empty />} />
				</Switch>
			</HashRouter>
		</Suspense>
	);

	/* Mount our router */
	templateRouter();

	/*
	 * Listen for @wordpress/data store updates and do DOM updates
	 */
	activeTemplateStoreListener(templateField);
	templateChangeStoreListener(templateField);
}

const TemplateButtonWithRouter = withRouterHooks(TemplateButton);

/**
 * Dynamically add the required markup to attach our React components to.
 *
 * @param templateField
 * @since 4.1
 */
export function createTemplateMarkup(templateField: HTMLSelectElement): void {
	const wrapper = document.createElement('div');
	wrapper.id = 'gfpdf-settings-field-wrapper-template-container';
	templateField.parentNode!.insertBefore(wrapper, templateField);
	wrapper.appendChild(templateField);

	const selectorSpan = document.createElement('span');
	selectorSpan.id = 'gpdf-advance-template-selector';
	wrapper.appendChild(selectorSpan);

	const overlayDiv = document.createElement('div');
	overlayDiv.id = 'gfpdf-overlay';
	overlayDiv.className = 'theme-overlay';
	wrapper.appendChild(overlayDiv);
}

/**
 * Listen for updates to the template.activeTemplate data in our @wordpress/data store
 * and update the select box value based on this change. Also, listen for changes
 * to our select box and update the store when needed.
 *
 * @param templateField
 * @since 4.1
 */
export function activeTemplateStoreListener(
	templateField: HTMLSelectElement
): () => void {
	let prevActiveTemplate = select(
		TEMPLATE_STORE_NAME
	).getActiveTemplate() as string;

	/* Watch store for changes */
	const unsubscribeActive = subscribe(() => {
		const activeTemplate = select(
			TEMPLATE_STORE_NAME
		).getActiveTemplate() as string;
		if (activeTemplate !== prevActiveTemplate) {
			prevActiveTemplate = activeTemplate;
			if (templateField.value !== activeTemplate) {
				templateField.value = activeTemplate;
				templateField.dispatchEvent(new Event('change'));
			}
		}
	}, TEMPLATE_STORE_NAME);

	/* Watch DOM for changes */
	templateField.addEventListener('change', () => {
		const activeTemplate = select(
			TEMPLATE_STORE_NAME
		).getActiveTemplate() as string;
		if (templateField.value !== activeTemplate) {
			void wpDispatch(templateStore).selectTemplate(templateField.value);
		}
	});

	return unsubscribeActive;
}

/**
 * PHP builds the Select box DOM for the templates and when we add or delete a template we need to
 * rebuild this. Instead of duplicating the code on both server and client side we do an AJAX call to
 * get the new select box HTML when the template.list length changes and update the DOM accordingly.
 *
 * @param templateField
 * @since 4.1
 */
export function templateChangeStoreListener(
	templateField: HTMLSelectElement
): () => void {
	let prevListLength = (select(TEMPLATE_STORE_NAME).getList() as unknown[])
		.length;
	let prevSelectBoxText = select(
		TEMPLATE_STORE_NAME
	).getUpdateSelectBoxText() as string;

	const unsubscribeChange = subscribe(() => {
		const list = select(TEMPLATE_STORE_NAME).getList() as unknown[];
		const updateSelectBoxText = select(
			TEMPLATE_STORE_NAME
		).getUpdateSelectBoxText() as string;

		if (list.length !== prevListLength) {
			prevListLength = list.length;
			void wpDispatch(templateStore).updateSelectBox();
		}

		if (updateSelectBoxText && updateSelectBoxText !== prevSelectBoxText) {
			prevSelectBoxText = updateSelectBoxText;
			templateField.innerHTML = updateSelectBoxText;
			templateField.value = select(
				TEMPLATE_STORE_NAME
			).getActiveTemplate() as string;
			templateField.dispatchEvent(new CustomEvent('chosen:updated'));
		}
	}, TEMPLATE_STORE_NAME);

	return unsubscribeChange;
}
