/* Dependencies */
import { useState, useEffect, useRef, createRoot } from '@wordpress/element';
import type { MouseEvent } from 'react';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
/* Store */
import { fontManagerStore } from '../store/fontManagerStore';
/* Components */
import FontManagerModal from '../components/FontManager/FontManagerModal';
/* Utilities */
import { associatedFontManagerSelectBox } from '../utilities/FontManager/associatedFontManagerSelectBox';
import {
	FontManagerUiContext,
	type FontManagerUiState,
} from '../utilities/FontManager/FontManagerUiContext';
/* Types */
import type { FontItem } from '../types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

const tabLocationFromQuery = (): string =>
	window.location.search.substring(
		window.location.search.lastIndexOf('=') + 1
	);

const FontManagerApp = () => {
	/* Auto-open when navigated here from WP backend via hash URL */
	const [isOpen, setIsOpen] = useState(() =>
		window.location.hash.startsWith('#/fontmanager')
	);

	const fontList = useSelect(
		(select) => select(fontManagerStore).getFontList(),
		[]
	);
	const selectedFont = useSelect(
		(select) => select(fontManagerStore).getSelectedFont(),
		[]
	);

	/* Mirror latest values in refs so the close-side-effect reads current data */
	const fontListRef = useRef<FontItem[]>(fontList);
	fontListRef.current = fontList;
	const selectedFontRef = useRef(selectedFont);
	selectedFontRef.current = selectedFont;

	/* UI flags from inside the modal — we read these to decide whether Esc
	   should close the outer Modal. The inner FontManagerModal mutates
	   `state` via setState, but we never re-render the bootstrap on those
	   flips because shouldCloseOnEsc reads getState() at event time. */
	const uiStateRef = useRef<FontManagerUiState>({
		dirty: false,
		confirmOpen: false,
	});
	const uiContextValue = useRef({
		getState: () => uiStateRef.current,
		setState: (next: Partial<FontManagerUiState>) => {
			uiStateRef.current = { ...uiStateRef.current, ...next };
		},
	}).current;

	/* On Modal close (isOpen true -> false): sync the selected font back to
	   the associated <select>. Skipped on the Tools tab where there is no
	   select. */
	const prevIsOpenRef = useRef(isOpen);
	useEffect(() => {
		const wasOpen = prevIsOpenRef.current;
		prevIsOpenRef.current = isOpen;
		if (!(wasOpen && !isOpen)) {
			return;
		}
		if (tabLocationFromQuery() === 'tools') {
			return;
		}
		associatedFontManagerSelectBox(
			fontListRef.current,
			selectedFontRef.current
		);
	}, [isOpen]);

	const handleCloseModal = () => {
		/* If a confirm dialog is open, the inner Modal handles its own Esc.
		   If the form is dirty, ignore the close request — the user must
		   explicitly Cancel/Save first. The Snackbar is enough warning. */
		if (uiStateRef.current.dirty || uiStateRef.current.confirmOpen) {
			return;
		}
		setIsOpen(false);
	};

	const tabLocation = tabLocationFromQuery();

	return (
		<FontManagerUiContext.Provider value={uiContextValue}>
			<Button
				data-test="component-AdvancedButton"
				variant="secondary"
				onClick={(e: MouseEvent<HTMLButtonElement>) => {
					e.stopPropagation();
					setIsOpen(true);
				}}
				aria-label={__('Manage Fonts', 'gravity-pdf')}
				__next40pxDefaultSize={true}
			>
				{__('Manage', 'gravity-pdf')}
			</Button>

			{isOpen && (
				<Modal
					title={__('Font Manager', 'gravity-pdf')}
					onRequestClose={handleCloseModal}
					className="gfpdf-font-manager-modal"
					size="fill"
				>
					<FontManagerModal tabLocation={tabLocation} />
				</Modal>
			)}
		</FontManagerUiContext.Provider>
	);
};

/**
 * Mount the font manager on a single React root adjacent to the field.
 *
 * @param defaultFontField
 * @since 6.0
 */
export function fontManagerBootstrap(defaultFontField: Element): void {
	const mountPoint = createAdvancedButtonWrapper(defaultFontField);
	createRoot(mountPoint).render(<FontManagerApp />);
}

/**
 * Wrap a <select> field in a flex container (for inline select + button
 * layout) and append a <span> mount point for the React root. For non-select
 * anchors (e.g. the Tools tab wrapper), append the mount point directly.
 *
 * @param defaultFontField
 * @since 6.0
 */
export function createAdvancedButtonWrapper(
	defaultFontField: Element
): HTMLSpanElement {
	const mountPoint = document.createElement('span');
	mountPoint.id = 'gpdf-advance-font-manager-selector';

	if (defaultFontField.nodeName === 'SELECT') {
		const wrapper = document.createElement('div');
		wrapper.id = 'gfpdf-settings-field-wrapper-font-container';
		defaultFontField.parentNode!.insertBefore(wrapper, defaultFontField);
		wrapper.appendChild(defaultFontField);
		wrapper.appendChild(mountPoint);
	} else {
		defaultFontField.appendChild(mountPoint);
	}

	return mountPoint;
}
