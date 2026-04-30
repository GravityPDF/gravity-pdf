/* Dependencies */
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
/* Store */
import { fontManagerStore } from '../../store/fontManagerStore';
/* Types */
import { EditingFontState, FontItem } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

export interface UseEditingFontResult {
	editingFont: EditingFontState | null;
	savedFont: FontItem | null;
	dirty: boolean;
	regularMissing: boolean;
	hasDestructiveFileChange: boolean;
	nameError: string;
	canSave: boolean;
}

const VARIANT_KEYS: Array<keyof EditingFontState['fontStyles']> = [
	'regular',
	'italics',
	'bold',
	'bolditalics',
];

const valueOf = (v: string | File): string =>
	typeof v === 'object' ? v.name : v;

/**
 * Derive validation, dirty-tracking, and "destructive change" state from the
 * unified editing slice. All inputs come from the store; nothing is mutated.
 */
export function useEditingFont(): UseEditingFontResult {
	const editingFont = useSelect(
		(select) => select(fontManagerStore).getEditingFont(),
		[]
	);
	const fontList = useSelect(
		(select) => select(fontManagerStore).getFontList(),
		[]
	);

	const savedFont = useMemo<FontItem | null>(() => {
		if (!editingFont || editingFont.isDraft) {
			return null;
		}
		return fontList.find((f) => f.id === editingFont.id) ?? null;
	}, [editingFont, fontList]);

	const dirty = useMemo(() => {
		if (!editingFont) {
			return false;
		}
		if (editingFont.isDraft) {
			return (
				editingFont.label.trim() !== '' ||
				VARIANT_KEYS.some((k) => editingFont.fontStyles[k] !== '')
			);
		}
		if (!savedFont) {
			return false;
		}
		if (editingFont.label.trim() !== savedFont.font_name) {
			return true;
		}
		for (const k of VARIANT_KEYS) {
			const editingValue = editingFont.fontStyles[k];
			const savedValue = savedFont[k] || '';
			/* If editing value is a File the user has uploaded a replacement */
			if (typeof editingValue === 'object') {
				return true;
			}
			if (editingValue !== savedValue) {
				return true;
			}
		}
		return false;
	}, [editingFont, savedFont]);

	const regularMissing = useMemo(() => {
		if (!editingFont) {
			return false;
		}
		return !editingFont.fontStyles.regular;
	}, [editingFont]);

	const hasDestructiveFileChange = useMemo(() => {
		if (!editingFont || editingFont.isDraft || !savedFont) {
			return false;
		}
		for (const k of VARIANT_KEYS) {
			const prevPath = savedFont[k] || '';
			const next = editingFont.fontStyles[k];
			const nextPath = valueOf(next);
			/* Replaced (different filename or now a File) or removed (was present, now empty) */
			if (prevPath && (typeof next === 'object' || nextPath === '')) {
				return true;
			}
			if (prevPath && nextPath && prevPath !== nextPath) {
				return true;
			}
		}
		return false;
	}, [editingFont, savedFont]);

	const nameError = useMemo(() => {
		if (!editingFont) {
			return '';
		}
		const trimmed = editingFont.label.trim();
		if (trimmed === '') {
			/* Empty is not surfaced as an error inline — Save is just disabled */
			return '';
		}
		if (!/^[0-9a-zA-Z ]+$/.test(trimmed)) {
			return __(
				'The font name can only contain letters, numbers and spaces.',
				'gravity-pdf'
			);
		}
		const clash = fontList.find(
			(f) =>
				f.id !== editingFont.id &&
				f.font_name.toLowerCase() === trimmed.toLowerCase()
		);
		if (clash) {
			return __('A font with this name already exists.', 'gravity-pdf');
		}
		return '';
	}, [editingFont, fontList]);

	const canSave = useMemo(() => {
		if (!editingFont) {
			return false;
		}
		if (editingFont.label.trim() === '' || nameError) {
			return false;
		}
		if (regularMissing) {
			return false;
		}
		return dirty;
	}, [editingFont, nameError, regularMissing, dirty]);

	return {
		editingFont,
		savedFont,
		dirty,
		regularMissing,
		hasDestructiveFileChange,
		nameError,
		canSave,
	};
}
