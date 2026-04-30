/* Dependencies */
import { useState, useEffect, useRef } from '@wordpress/element';
import type { MouseEvent, KeyboardEvent } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
/* Store */
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Hook for the new sidebar list. Owns delete-confirmation state and the
 * "move selected font to top on first appearance" one-shot effect. The
 * radio-select handlers and tools-tab disable branch from the previous
 * implementation are gone — selection is driven by the detail pane's
 * "Set as active" button now.
 */
export function useFontListItems() {
	const { deleteFont, moveSelectedFontToTop } = useDispatch(
		FONT_MANAGER_STORE_NAME
	);
	const loading = useSelect(
		(select) => select(fontManagerStore).getDeleteFontLoading(),
		[]
	);
	const fontList = useSelect(
		(select) => select(fontManagerStore).getFontList(),
		[]
	);
	const searchResult = useSelect(
		(select) => select(fontManagerStore).getSearchResult(),
		[]
	);
	const selectedFont = useSelect(
		(select) => select(fontManagerStore).getSelectedFont(),
		[]
	);

	const [deleteId, setDeleteId] = useState('');
	const [pendingDeleteId, setPendingDeleteId] = useState<string | null>(null);
	/* One-shot flag: only move selected font to top once per mount */
	const moveSelectedFontToTopRef = useRef(true);

	/* Reset deleteId whenever the delete-loading flag flips off */
	useEffect(() => {
		if (!loading) {
			setDeleteId('');
		}
	}, [loading]);

	/* Move selected font to top the first time it becomes non-empty */
	useEffect(() => {
		if (!selectedFont || !moveSelectedFontToTopRef.current) {
			return;
		}
		moveSelectedFontToTopRef.current = false;
		moveSelectedFontToTop(selectedFont);
	}, [selectedFont, moveSelectedFontToTop]);

	const requestDeleteFont = (e: MouseEvent, fontId: string) => {
		e.stopPropagation();
		setPendingDeleteId(fontId);
	};

	const requestDeleteFontKeypress = (e: KeyboardEvent, fontId: string) => {
		if (e.key === 'Enter' || e.key === ' ') {
			requestDeleteFont(e as unknown as MouseEvent, fontId);
		}
	};

	const confirmDeleteFont = () => {
		if (pendingDeleteId) {
			setDeleteId(pendingDeleteId);
			deleteFont(pendingDeleteId);
		}
		setPendingDeleteId(null);
	};

	const cancelDeleteFont = () => setPendingDeleteId(null);

	return {
		loading,
		fontList,
		searchResult,
		selectedFont,
		deleteId,
		pendingDeleteId,
		requestDeleteFont,
		requestDeleteFontKeypress,
		confirmDeleteFont,
		cancelDeleteFont,
	};
}
