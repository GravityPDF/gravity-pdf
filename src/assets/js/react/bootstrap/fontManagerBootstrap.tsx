/* Dependencies */
import { useState, useEffect, useRef, createRoot } from '@wordpress/element';
import type { KeyboardEvent, MouseEvent } from 'react';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
/* Store */
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../store/fontManagerStore';
/* Components */
import FontManagerBody from '../components/FontManager/FontManagerBody';
/* Utilities */
import { associatedFontManagerSelectBox } from '../utilities/FontManager/associatedFontManagerSelectBox';
/* Types */
import type { FontItem } from '../types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

const FontManagerApp = () => {
	/* Auto-open when navigated here from WP backend via hash URL */
	const [isOpen, setIsOpen] = useState(() =>
		window.location.hash.startsWith('#/fontmanager')
	);
	const [activeFontId, setActiveFontId] = useState('');

	const { clearAddFontMsg } = useDispatch(FONT_MANAGER_STORE_NAME);
	const fontList = useSelect(
		(select) => select(fontManagerStore).getFontList(),
		[]
	);
	const selectedFont = useSelect(
		(select) => select(fontManagerStore).getSelectedFont(),
		[]
	);
	const msg = useSelect((select) => select(fontManagerStore).getMsg(), []);

	/* Mirror latest values in refs so the close-side-effect reads current data */
	const fontListRef = useRef<FontItem[]>(fontList);
	fontListRef.current = fontList;
	const selectedFontRef = useRef(selectedFont);
	selectedFontRef.current = selectedFont;

	/* On Modal close (isOpen true -> false): sync the selected font back to the
	   associated <select>. Skipped on the Tools tab where there is no select. */
	const prevIsOpenRef = useRef(isOpen);
	useEffect(() => {
		const wasOpen = prevIsOpenRef.current;
		prevIsOpenRef.current = isOpen;
		if (!(wasOpen && !isOpen)) {
			return;
		}
		const tabLocation = window.location.search.substring(
			window.location.search.lastIndexOf('=') + 1
		);
		if (tabLocation === 'tools') {
			return;
		}
		associatedFontManagerSelectBox(
			fontListRef.current,
			selectedFontRef.current
		);
	}, [isOpen]);

	const handleCloseModal = () => {
		setIsOpen(false);
		setActiveFontId('');
	};

	/* Escape closes the detail panel (not the modal) when one is open */
	const handleKeyDown = (e: KeyboardEvent) => {
		if (e.key !== 'Escape' || !activeFontId) {
			return;
		}
		e.stopPropagation();
		setActiveFontId('');
		if (msg.success?.addFont || msg.error?.addFont) {
			clearAddFontMsg();
		}
	};

	return (
		<>
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
					shouldCloseOnEsc={!activeFontId}
				>
					{/* eslint-disable-next-line jsx-a11y/no-static-element-interactions */}
					<div
						data-test="component-FontManager"
						className="font-manager"
						onKeyDown={handleKeyDown}
					>
						<FontManagerBody
							activeFontId={activeFontId}
							onSelectFont={setActiveFontId}
						/>
					</div>
				</Modal>
			)}
		</>
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
