/* Dependencies */
import { useState, useEffect, useRef } from '@wordpress/element';
import type { KeyboardEvent, MouseEvent, ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
/* Store */
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';
/* Types */
import type { FontItem } from '../../types';

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
	/* One-shot flag: only move selected font to top once */
	const moveSelectedFontToTopRef = useRef(true);

	/* Track previous values so we can react to delete-loading completion */
	const prevLoadingRef = useRef(loading);
	const prevFontListRef = useRef<FontItem[]>(fontList);
	const prevSelectedFontRef = useRef(selectedFont);

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

	/* Reset deleteId + close the detail panel when the delete-loading flag flips off */
	useEffect(() => {
		const prevLoading = prevLoadingRef.current;
		const prevFontList = prevFontListRef.current;
		prevLoadingRef.current = loading;
		prevFontListRef.current = fontList;

		if (prevLoading !== loading && !loading) {
			setDeleteId('');
		}

		if (
			prevLoading !== loading &&
			prevFontList !== fontList &&
			hasDetailOpen
		) {
			onSelectFont('');
		}
	}, [loading, fontList, onSelectFont, hasDetailOpen]);

	/* Move selected font to top when it first becomes non-empty */
	useEffect(() => {
		const prevSelectedFont = prevSelectedFontRef.current;
		prevSelectedFontRef.current = selectedFont;

		if (
			prevSelectedFont === '' &&
			selectedFont &&
			!activeFontId &&
			moveSelectedFontToTopRef.current
		) {
			moveSelectedFontToTopRef.current = false;
			moveSelectedFontToTop(selectedFont);
		}
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

	const handleDeleteFont = (e: MouseEvent, fontId: string) => {
		e.stopPropagation();
		setDeleteId(fontId);

		if (
			window.confirm(
				__('Are you sure you want to delete this font?', 'gravity-pdf')
			)
		) {
			deleteFont(fontId);
		}
	};

	const handleDeleteFontKeypress = (e: KeyboardEvent, fontId: string) => {
		if (e.key === 'Enter' || e.key === ' ') {
			handleDeleteFont(e as unknown as MouseEvent, fontId);
		}
	};

	const handleSelectFont = (e: ChangeEvent<HTMLInputElement>) => {
		selectFont(e.target.value);

		const installedFonts =
			document.querySelectorAll<HTMLInputElement>('.select-font-name');
		installedFonts.forEach((item) => {
			item.checked = item.value === e.target.value;
		});
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
		handleFontClick,
		handleFontClickKeypress,
		handleDeleteFont,
		handleDeleteFontKeypress,
		handleSelectFont,
		handleSelectFontKeypress,
	};
}
