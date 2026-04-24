/* Dependencies */
import { useState, useEffect, useRef } from '@wordpress/element';
import type { KeyboardEvent, MouseEvent, ChangeEvent } from 'react';
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

interface UseFontListItemsArgs {
	activeFontId: string;
	onSelectFont: (id: string) => void;
	hasDetailOpen: boolean;
}

export function useFontListItems({
	activeFontId,
	onSelectFont,
	hasDetailOpen,
}: UseFontListItemsArgs) {
	const { clearAddFontMsg, deleteFont, selectFont, moveSelectedFontToTop } =
		useDispatch(FONT_MANAGER_STORE_NAME);
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
	const msg = useSelect((select) => select(fontManagerStore).getMsg(), []);

	const [disableSelectFontName, setDisableSelectFontName] = useState(false);
	const [deleteId, setDeleteId] = useState('');
	const [pendingDeleteId, setPendingDeleteId] = useState<string | null>(null);
	/* One-shot flag: only move selected font to top once per mount */
	const moveSelectedFontToTopRef = useRef(true);

	/* componentDidMount: disable select fields + optionally move selected font to top */
	useEffect(() => {
		const tabLocation = window.location.search.substr(
			window.location.search.lastIndexOf('=') + 1
		);

		if (tabLocation === 'tools') {
			setDisableSelectFontName(true);
		} else {
			let selectBoxValue: string;

			if (tabLocation !== 'PDF' && tabLocation !== 'general') {
				selectBoxValue = (
					document.querySelector(
						'#gfpdf_settings\\[font\\]'
					) as HTMLInputElement
				).value;
			} else {
				selectBoxValue = (
					document.querySelector(
						'#gfpdf_settings\\[default_font\\]'
					) as HTMLInputElement
				).value;
			}

			const fontExists =
				fontList && fontList.filter((f) => f.id === selectBoxValue)[0];
			selectFont(fontExists ? selectBoxValue : '');
		}

		if (selectedFont) {
			moveSelectedFontToTopRef.current = false;
			moveSelectedFontToTop(selectedFont);
		}
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* Reset deleteId whenever the delete-loading flag flips off */
	useEffect(() => {
		if (!loading) {
			setDeleteId('');
		}
	}, [loading]);

	/* If the detail panel is open and the font list just changed after a delete
	   request completed, close the detail panel — the deleted font is gone. */
	useEffect(() => {
		if (loading || !hasDetailOpen) {
			return;
		}
		onSelectFont('');
		/* Intentional: only fire when fontList changes. Including loading /
		   hasDetailOpen would re-trigger on irrelevant state flips. */
	}, [fontList]); // eslint-disable-line react-hooks/exhaustive-deps

	/* Move selected font to top the first time it becomes non-empty */
	useEffect(() => {
		if (
			!selectedFont ||
			activeFontId ||
			!moveSelectedFontToTopRef.current
		) {
			return;
		}
		moveSelectedFontToTopRef.current = false;
		moveSelectedFontToTop(selectedFont);
	}, [selectedFont, activeFontId, moveSelectedFontToTop]);

	const handleFontClick = (fontId: string) => {
		const { success, error } = msg;

		if ((success && success.addFont) || (error && error.addFont)) {
			clearAddFontMsg();
		}

		if (activeFontId === fontId) {
			onSelectFont('');
			return;
		}

		onSelectFont(fontId);
	};

	const handleFontClickKeypress = (e: KeyboardEvent, fontId: string) => {
		if (e.key === 'Enter' || e.key === ' ') {
			handleFontClick(fontId);
		}
	};

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

	const handleSelectFont = (e: ChangeEvent<HTMLInputElement>) => {
		selectFont(e.target.value);
	};

	const handleSelectFontKeypress = (e: KeyboardEvent<HTMLInputElement>) => {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			e.stopPropagation();
			handleSelectFont(e as unknown as ChangeEvent<HTMLInputElement>);
		}
	};

	return {
		loading,
		fontList,
		searchResult,
		selectedFont,
		disableSelectFontName,
		deleteId,
		pendingDeleteId,
		handleFontClick,
		handleFontClickKeypress,
		requestDeleteFont,
		requestDeleteFontKeypress,
		confirmDeleteFont,
		cancelDeleteFont,
		handleSelectFont,
		handleSelectFontKeypress,
	};
}
