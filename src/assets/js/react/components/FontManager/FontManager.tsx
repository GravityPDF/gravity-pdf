/* Dependencies */
import { useEffect, useRef } from '@wordpress/element';
import type { KeyboardEvent } from 'react';
import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';
/* Components */
import FontManagerBody from './FontManagerBody';
import { associatedFontManagerSelectBox } from '../../utilities/FontManager/associatedFontManagerSelectBox';
import { FontItem } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	activeFontId: string;
	onSelectFont: (id: string) => void;
	onClose: () => void;
}

const FontManager = ({ activeFontId, onSelectFont, onClose }: Props) => {
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

	/* Mirror latest values in refs so unmount cleanup reads current data, not stale closure */
	const fontListRef = useRef<FontItem[]>(fontList);
	fontListRef.current = fontList;
	const selectedFontRef = useRef(selectedFont);
	selectedFontRef.current = selectedFont;

	/* On unmount: sync selected font back to the associated <select> (skipped on tools tab) */
	useEffect(() => {
		return () => {
			const tabLocation = window.location.search.substring(
				window.location.search.lastIndexOf('=') + 1
			);

			if (tabLocation !== 'tools') {
				associatedFontManagerSelectBox(
					fontListRef.current,
					selectedFontRef.current
				);
			}
		};
	}, []);

	/* Escape closes the detail panel (not the modal) when one is open */
	const handleKeyDown = (e: KeyboardEvent) => {
		if (e.key !== 'Escape' || !activeFontId) {
			return;
		}

		e.stopPropagation();
		onSelectFont('');

		if (msg.success?.addFont || msg.error?.addFont) {
			clearAddFontMsg();
		}
	};

	return (
		<Modal
			title={__('Font Manager', 'gravity-pdf')}
			onRequestClose={onClose}
			className="gfpdf-font-manager-modal"
			size="large"
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
					onSelectFont={onSelectFont}
				/>
			</div>
		</Modal>
	);
};

export default FontManager;
