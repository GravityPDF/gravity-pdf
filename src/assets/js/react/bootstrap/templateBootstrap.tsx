/* Dependencies */
import {
	useState,
	useEffect,
	lazy,
	Suspense,
	createRoot,
	createPortal,
} from '@wordpress/element';
import { subscribe, select, dispatch as wpDispatch } from '@wordpress/data';
/* Store */
import { TEMPLATE_STORE_NAME, templateStore } from '../store/templateStore';
/* Components */
import TemplateButton from '../components/Template/TemplateButton';

const TemplateList = lazy(() => import('../components/Template/TemplateList'));
const TemplateSingle = lazy(
	() => import('../components/Template/TemplateSingle')
);

/**
 * Advanced Template Selector Bootstrap
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface AppProps {
	buttonContainer: Element;
}

const TemplateApp = ({ buttonContainer }: AppProps) => {
	const [isOpen, setIsOpen] = useState(false);
	const [activeTemplateId, setActiveTemplateId] = useState('');

	/* Auto-open when navigated here from WP backend via hash URL */
	useEffect(() => {
		if (window.location.hash !== '#/template') {
			return;
		}
		setIsOpen(true);
	}, []);

	const handleOpen = () => {
		setIsOpen(true);
	};

	const handleClose = () => {
		setIsOpen(false);
		setActiveTemplateId('');
	};

	return (
		<>
			{createPortal(
				<TemplateButton onOpen={handleOpen} />,
				buttonContainer
			)}
			{isOpen && (
				<Suspense fallback={<div />}>
					{activeTemplateId ? (
						<TemplateSingle
							activeTemplateId={activeTemplateId}
							onSelectTemplate={setActiveTemplateId}
							onClose={handleClose}
						/>
					) : (
						<TemplateList
							onSelectTemplate={setActiveTemplateId}
							onClose={handleClose}
						/>
					)}
				</Suspense>
			)}
		</>
	);
};

/**
 * Handles the loading of the Fancy Template Selector.
 *
 * @param templateField
 * @since 4.1
 */
export function templateBootstrap(templateField: HTMLSelectElement): void {
	createTemplateMarkup(templateField);

	const buttonContainer = document.getElementById(
		'gpdf-advance-template-selector'
	)!;
	const overlayContainer = document.getElementById('gfpdf-overlay')!;

	createRoot(overlayContainer).render(
		<TemplateApp buttonContainer={buttonContainer} />
	);

	/*
	 * Listen for @wordpress/data store updates and do DOM updates
	 */
	activeTemplateStoreListener(templateField);
	templateChangeStoreListener(templateField);
}

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
