/* Dependencies */
import { useEffect, useMemo, useRef } from '@wordpress/element';
import type { KeyboardEvent as ReactKeyboardEvent } from 'react';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
/* Store */
import { fontManagerStore } from '../../store/fontManagerStore';
/* Hooks */
import { useFontListItems } from '../../utilities/FontManager/useFontListItems';
import { useEditingFont } from '../../utilities/FontManager/useEditingFont';
import { useTypeahead } from '../../utilities/FontManager/useTypeahead';
/* Components */
import FontListItem from './FontListItem';
import DeleteFontDialog from './DeleteFontDialog';
/* Types */
import { FontItem } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	onSelect: (id: string) => void;
}

const LIST_ID = 'gfpdf-fm-listbox';
const itemId = (id: string) => `gfpdf-fm-row-${id}`;

const FontList = ({ onSelect }: Props) => {
	const { editingFont } = useEditingFont();
	const {
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
	} = useFontListItems();
	const initialLoading = useSelect(
		(select) => select(fontManagerStore).getLoading(),
		[]
	);

	const baseList = !searchResult ? fontList : searchResult;

	/* Prepend an unsaved draft to the rendered list so the user can see
	   their in-progress font in the sidebar. */
	const renderedList: Array<{ font: FontItem; displayName: string }> =
		useMemo(() => {
			const out: Array<{ font: FontItem; displayName: string }> = [];
			if (editingFont?.isDraft && !searchResult) {
				const draftFont: FontItem = {
					id: editingFont.id,
					font_name: editingFont.label,
					regular:
						typeof editingFont.fontStyles.regular === 'object'
							? editingFont.fontStyles.regular.name
							: editingFont.fontStyles.regular,
					italics:
						typeof editingFont.fontStyles.italics === 'object'
							? editingFont.fontStyles.italics.name
							: editingFont.fontStyles.italics,
					bold:
						typeof editingFont.fontStyles.bold === 'object'
							? editingFont.fontStyles.bold.name
							: editingFont.fontStyles.bold,
					bolditalics:
						typeof editingFont.fontStyles.bolditalics === 'object'
							? editingFont.fontStyles.bolditalics.name
							: editingFont.fontStyles.bolditalics,
				};
				out.push({ font: draftFont, displayName: editingFont.label });
			}
			for (const f of baseList) {
				const displayName =
					editingFont &&
					!editingFont.isDraft &&
					editingFont.id === f.id
						? editingFont.label
						: f.font_name;
				out.push({ font: f, displayName });
			}
			return out;
		}, [baseList, editingFont, searchResult]);

	const typeaheadItems = useMemo(
		() =>
			renderedList.map(({ font, displayName }) => ({
				id: font.id,
				name: displayName || '',
			})),
		[renderedList]
	);
	const typeahead = useTypeahead(typeaheadItems, editingFont?.id ?? '');

	/* Move the active descendant DOM node into view when it changes */
	const listboxRef = useRef<HTMLDivElement>(null);
	const editingId = editingFont?.id;
	useEffect(() => {
		if (!editingId || !listboxRef.current) {
			return;
		}
		const target = listboxRef.current.querySelector(
			`#${CSS.escape(itemId(editingId))}`
		);
		if (target instanceof HTMLElement) {
			target.scrollIntoView({ block: 'nearest' });
		}
	}, [editingId]);

	const handleKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>) => {
		if (renderedList.length === 0) {
			return;
		}
		const ids = renderedList.map(({ font }) => font.id);
		const currentIdx = editingFont
			? Math.max(0, ids.indexOf(editingFont.id))
			: 0;

		switch (event.key) {
			case 'ArrowDown': {
				event.preventDefault();
				const next = ids[Math.min(ids.length - 1, currentIdx + 1)];
				if (next) {
					onSelect(next);
				}
				return;
			}
			case 'ArrowUp': {
				event.preventDefault();
				const next = ids[Math.max(0, currentIdx - 1)];
				if (next) {
					onSelect(next);
				}
				return;
			}
			case 'Home': {
				event.preventDefault();
				if (ids[0]) {
					onSelect(ids[0]);
				}
				return;
			}
			case 'End': {
				event.preventDefault();
				const last = ids[ids.length - 1];
				if (last) {
					onSelect(last);
				}
				return;
			}
			case 'Enter':
			case ' ': {
				event.preventDefault();
				if (editingFont) {
					onSelect(editingFont.id);
				}
				return;
			}
			default: {
				const matched = typeahead.handleKeyDown(event);
				if (matched) {
					event.preventDefault();
					onSelect(matched);
				}
			}
		}
	};

	const activeDescendantId = editingFont ? itemId(editingFont.id) : undefined;
	const noResults =
		!initialLoading && searchResult !== null && renderedList.length === 0;
	const emptyList =
		!initialLoading &&
		fontList.length === 0 &&
		!editingFont?.isDraft &&
		!searchResult;
	const fontToDelete =
		fontList.find((f) => f.id === pendingDeleteId)?.font_name ?? '';

	return (
		<div
			data-test="component-FontList"
			className="gfpdf-fm-list"
			aria-busy={initialLoading}
		>
			{initialLoading ? (
				<div className="gfpdf-fm-list__loading">
					<Spinner />
				</div>
			) : (
				<div
					ref={listboxRef}
					id={LIST_ID}
					role="listbox"
					aria-label={__('Installed Fonts', 'gravity-pdf')}
					aria-activedescendant={activeDescendantId}
					tabIndex={0}
					onKeyDown={handleKeyDown}
					className="gfpdf-fm-list__items"
				>
					{renderedList.map(({ font, displayName }) => (
						<FontListItem
							key={font.id}
							itemId={itemId(font.id)}
							font={font}
							displayName={displayName}
							isEditing={editingFont?.id === font.id}
							isActive={selectedFont === font.id}
							isDeleting={loading && deleteId === font.id}
							onSelect={() => onSelect(font.id)}
							onRequestDelete={(e) =>
								requestDeleteFont(e, font.id)
							}
							onRequestDeleteKeypress={(e) =>
								requestDeleteFontKeypress(e, font.id)
							}
						/>
					))}
				</div>
			)}

			{noResults && (
				<div className="gfpdf-fm-list__empty">
					{__('No results.', 'gravity-pdf')}
				</div>
			)}
			{emptyList && (
				<div className="gfpdf-fm-list__empty">
					{__('No custom fonts installed yet.', 'gravity-pdf')}
				</div>
			)}

			<DeleteFontDialog
				isOpen={pendingDeleteId !== null}
				fontName={fontToDelete}
				onConfirm={confirmDeleteFont}
				onCancel={cancelDeleteFont}
			/>
		</div>
	);
};

export default FontList;
