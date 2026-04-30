/* Dependencies */
import { useState, useEffect, useRef, createRoot } from '@wordpress/element';
import type { MouseEvent } from 'react';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
/* Store */
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../store/fontManagerStore';
/* Components */
import FontManagerModal from '../components/FontManager/FontManagerModal';
import DiscardChangesDialog from '../components/FontManager/DiscardChangesDialog';
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
	const { resetEditingState } = useDispatch(FONT_MANAGER_STORE_NAME);

	/* Mirror latest values in refs so the close-side-effect reads current data */
	const fontListRef = useRef<FontItem[]>(fontList);
	fontListRef.current = fontList;
	const selectedFontRef = useRef(selectedFont);
	selectedFontRef.current = selectedFont;

	/* UI flags from inside the modal — we read these to decide whether Esc
	   should close the outer Modal. The inner FontManagerModal mutates
	   `state` via setState, but we never re-render the bootstrap on those
	   flips because the close-handler reads getState() at event time. */
	const uiStateRef = useRef<FontManagerUiState>({
		dirty: false,
		confirmOpen: false,
	});

	/* "Discard unsaved changes?" dialog state. Lives here so both the outer
	   Modal Esc/close path and the modal's mobile back button can route
	   through requestDiscard() and reuse the same dialog. */
	const [discardDialogOpen, setDiscardDialogOpen] = useState(false);
	const pendingDiscardActionRef = useRef<(() => void) | null>(null);
	const discardDialogOpenRef = useRef(false);
	discardDialogOpenRef.current = discardDialogOpen;

	const uiContextValue = useRef({
		getState: () => uiStateRef.current,
		setState: (next: Partial<FontManagerUiState>) => {
			uiStateRef.current = { ...uiStateRef.current, ...next };
		},
		requestDiscard: (continueAction: () => void) => {
			if (!uiStateRef.current.dirty) {
				continueAction();
				return;
			}
			pendingDiscardActionRef.current = continueAction;
			setDiscardDialogOpen(true);
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
		/* If an inner confirm dialog (delete / replace-files) or our own
		   discard dialog is open, ignore the close request — the open
		   dialog catches Esc itself. */
		if (uiStateRef.current.confirmOpen || discardDialogOpenRef.current) {
			return;
		}
		/* With unsaved changes, surface the discard dialog instead of
		   silently swallowing the close (a11y commitment §3). */
		if (uiStateRef.current.dirty) {
			pendingDiscardActionRef.current = () => setIsOpen(false);
			setDiscardDialogOpen(true);
			return;
		}
		setIsOpen(false);
	};

	const confirmDiscard = () => {
		resetEditingState();
		setDiscardDialogOpen(false);
		const next = pendingDiscardActionRef.current;
		pendingDiscardActionRef.current = null;
		if (next) {
			next();
		}
	};

	const cancelDiscard = () => {
		pendingDiscardActionRef.current = null;
		setDiscardDialogOpen(false);
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

			<DiscardChangesDialog
				isOpen={discardDialogOpen}
				onConfirm={confirmDiscard}
				onCancel={cancelDiscard}
			/>
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
