/* Dependencies */
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { Snackbar } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { useDispatch, useSelect } from '@wordpress/data';
/* Store */
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';
/* Hooks */
import { useEditingFont } from '../../utilities/FontManager/useEditingFont';
import { useFontManagerUi } from '../../utilities/FontManager/FontManagerUiContext';
/* Components */
import FontManagerHeader from './FontManagerHeader';
import FontSidebar from './FontSidebar';
import FontDetail from './FontDetail';
import SaveReplaceDialog from './SaveReplaceDialog';
import DeleteFontDialog from './DeleteFontDialog';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 *
 * NOTE: this component must NOT call associatedFontManagerSelectBox() —
 * the store re-runs it after `getCustomFontList` and the bootstrap re-runs
 * it on Modal close. Calling it here would double-fire the parent
 * <select>'s rebuild.
 */

interface Props {
	tabLocation: string;
}

interface ToastEntry {
	id: number;
	message: string;
	status?: 'error' | 'success';
}

const FontManagerModal = ({ tabLocation }: Props) => {
	const {
		getCustomFontList,
		startEditing,
		resetEditingState,
		setEditingState,
		selectFont,
		clearAddFontMsg,
		addFont,
		editFont,
		deleteFont,
	} = useDispatch(FONT_MANAGER_STORE_NAME);

	const fontList = useSelect(
		(select) => select(fontManagerStore).getFontList(),
		[]
	);
	const selectedFont = useSelect(
		(select) => select(fontManagerStore).getSelectedFont(),
		[]
	);
	const msg = useSelect((select) => select(fontManagerStore).getMsg(), []);
	const {
		editingFont,
		savedFont,
		dirty,
		regularMissing,
		hasDestructiveFileChange,
	} = useEditingFont();

	const [mobileView, setMobileView] = useState<'list' | 'detail'>('list');
	const [confirm, setConfirm] = useState<{ type: 'replaceFile' } | null>(
		null
	);
	const [toasts, setToasts] = useState<ToastEntry[]>([]);
	const toastIdRef = useRef(0);
	const pendingDraftSaveRef = useRef<{ label: string } | null>(null);

	const ui = useFontManagerUi();

	/* Bridge dirty + confirm-open to the bootstrap's UI context so the outer
	   <Modal>'s Esc handler can read them when deciding whether to close. */
	useEffect(() => {
		ui.setState({ dirty, confirmOpen: confirm !== null });
	}, [dirty, confirm, ui]);

	/* On mount: fetch font list from REST */
	useEffect(() => {
		getCustomFontList();
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* On mount: read the parent <select>'s current value and use it as the
	   "active" font. Skipped on the Tools tab because there is no parent
	   <select> there. */
	useEffect(() => {
		if (tabLocation === 'tools') {
			return;
		}
		const selector =
			tabLocation === 'PDF' || tabLocation === 'general'
				? '#gfpdf_settings\\[default_font\\]'
				: '#gfpdf_settings\\[font\\]';
		const node = document.querySelector(
			selector
		) as HTMLInputElement | null;
		const value = node?.value ?? '';
		if (!value) {
			return;
		}
		/* Defer: we may not have the fontList yet on first paint. The
		   subsequent useEffect on fontList will validate against the list. */
		selectFont(value);
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* When the font list arrives, ensure selectedFont still references a
	   real font; clear it if not (defensive against stale parent values). */
	useEffect(() => {
		if (!selectedFont) {
			return;
		}
		if (!fontList.some((f) => f.id === selectedFont)) {
			selectFont('');
		}
	}, [fontList, selectedFont, selectFont]);

	/* Auto-select a newly-saved draft once ADD_FONT_SUCCESS fires. */
	const addFontSuccess = msg.success?.addFont;
	useEffect(() => {
		if (!addFontSuccess || !pendingDraftSaveRef.current) {
			return;
		}
		const expectedLabel = pendingDraftSaveRef.current.label;
		pendingDraftSaveRef.current = null;
		const newFont =
			fontList.find((f) => f.font_name === expectedLabel) ??
			fontList[fontList.length - 1];
		if (newFont) {
			startEditing(newFont.id);
		}
	}, [addFontSuccess, fontList, startEditing]);

	const pushToast = useCallback(
		(message: string, status?: 'error' | 'success') => {
			toastIdRef.current += 1;
			const id = toastIdRef.current;
			setToasts((prev) => [...prev, { id, message, status }]);
			setTimeout(() => {
				setToasts((prev) => prev.filter((t) => t.id !== id));
			}, 3500);
		},
		[]
	);

	/* Surface server-side errors as Snackbars and clear them so they don't
	   linger across modal re-opens. */
	useEffect(() => {
		const e = msg.error;
		if (!e) {
			return;
		}
		const messages: string[] = [];
		if (typeof e.addFont === 'string') {
			messages.push(stripTags(e.addFont));
		} else if (e.fontValidationError) {
			messages.push(stripTags(e.fontValidationError));
		}
		if (e.deleteFont) {
			messages.push(stripTags(e.deleteFont));
		}
		if (e.fontList) {
			messages.push(stripTags(e.fontList));
		}
		messages.forEach((m) => pushToast(m, 'error'));
		if (messages.length > 0) {
			clearAddFontMsg();
		}
	}, [msg.error, pushToast, clearAddFontMsg]);

	/* Surface success messages as Snackbars. */
	const successAddFont = msg.success?.addFont;
	useEffect(() => {
		if (successAddFont) {
			pushToast(successAddFont, 'success');
			clearAddFontMsg();
		}
	}, [successAddFont, pushToast, clearAddFontMsg]);

	const onAddFont = useCallback(() => {
		startEditing();
		setMobileView('detail');
	}, [startEditing]);

	const onSelectFromList = useCallback(
		(id: string) => {
			if (editingFont && editingFont.isDraft && editingFont.id !== id) {
				/* Drop the never-saved draft when switching away */
				resetEditingState();
			}
			startEditing(id);
			setMobileView('detail');
		},
		[editingFont, resetEditingState, startEditing]
	);

	const onMobileBack = useCallback(() => {
		setMobileView('list');
	}, []);

	const onSetActive = useCallback(() => {
		if (!editingFont || !savedFont) {
			return;
		}
		if (!savedFont.regular) {
			pushToast(
				__('Upload a Regular variant first.', 'gravity-pdf'),
				'error'
			);
			return;
		}
		selectFont(editingFont.id);
		const announcement = sprintf(
			/* translators: %s: font name */
			__('%s is now the active font', 'gravity-pdf'),
			savedFont.font_name
		);
		pushToast(announcement, 'success');
		speak(announcement);
	}, [editingFont, savedFont, selectFont, pushToast]);

	const commitSave = useCallback(() => {
		if (!editingFont) {
			return;
		}
		const stylesPayload: Record<string, string | File> = {};
		(['regular', 'italics', 'bold', 'bolditalics'] as const).forEach(
			(k) => {
				const v = editingFont.fontStyles[k];
				/* Per existing API contract: only send Files (uploads) and
				   empty strings (deletions); leave server-side paths alone. */
				if (typeof v === 'object' || v === '') {
					stylesPayload[k] = v;
				}
			}
		);

		if (editingFont.isDraft) {
			pendingDraftSaveRef.current = { label: editingFont.label.trim() };
			addFont({
				label: editingFont.label.trim(),
				regular: stylesPayload.regular ?? '',
				italics: stylesPayload.italics ?? '',
				bold: stylesPayload.bold ?? '',
				bolditalics: stylesPayload.bolditalics ?? '',
			});
		} else {
			editFont({
				id: editingFont.id,
				font: {
					label: editingFont.label.trim(),
					...stylesPayload,
				},
			});
		}
		setConfirm(null);
	}, [editingFont, addFont, editFont]);

	const onSave = useCallback(() => {
		if (!editingFont) {
			return;
		}
		if (regularMissing) {
			pushToast(
				__('Upload a Regular variant first.', 'gravity-pdf'),
				'error'
			);
			return;
		}
		if (hasDestructiveFileChange) {
			setConfirm({ type: 'replaceFile' });
			return;
		}
		commitSave();
	}, [
		editingFont,
		regularMissing,
		hasDestructiveFileChange,
		commitSave,
		pushToast,
	]);

	const onCancel = useCallback(() => {
		if (!editingFont) {
			return;
		}
		if (editingFont.isDraft) {
			resetEditingState();
			setMobileView('list');
			return;
		}
		/* Re-hydrate from saved snapshot */
		startEditing(editingFont.id);
	}, [editingFont, resetEditingState, startEditing]);

	const [pendingDelete, setPendingDelete] = useState<string | null>(null);

	const onRequestDelete = useCallback(() => {
		if (!editingFont || editingFont.isDraft) {
			return;
		}
		setPendingDelete(editingFont.id);
	}, [editingFont]);

	const confirmDelete = useCallback(() => {
		if (pendingDelete) {
			deleteFont(pendingDelete);
			if (selectedFont === pendingDelete) {
				selectFont('');
			}
		}
		resetEditingState();
		setMobileView('list');
		setPendingDelete(null);
	}, [
		pendingDelete,
		deleteFont,
		resetEditingState,
		selectedFont,
		selectFont,
	]);

	const cancelDelete = useCallback(() => setPendingDelete(null), []);

	const onRejected = useCallback(
		(message: string) => {
			pushToast(message, 'error');
		},
		[pushToast]
	);

	/* Memoise to avoid Snackbar identity flicker */
	const toastNodes = useMemo(
		() =>
			toasts.map((t) => (
				<Snackbar
					key={t.id}
					/* The library defaults to role="status"/aria-live="polite";
					   we add a parallel speak() above for assertive errors. */
					onRemove={() =>
						setToasts((prev) => prev.filter((x) => x.id !== t.id))
					}
				>
					{t.message}
				</Snackbar>
			)),
		[toasts]
	);

	return (
		<div data-test="component-FontManagerModal" className="gfpdf-fm-modal">
			<FontManagerHeader count={fontList.length} />
			<div className="gfpdf-fm-body" data-mobile-view={mobileView}>
				<FontSidebar
					onSelect={onSelectFromList}
					onAddFont={onAddFont}
				/>
				<FontDetail
					onSave={onSave}
					onCancel={onCancel}
					onRequestDelete={onRequestDelete}
					onSetActive={onSetActive}
					onMobileBack={onMobileBack}
					onRejected={onRejected}
					onAddFont={onAddFont}
				/>
			</div>

			<SaveReplaceDialog
				isOpen={confirm?.type === 'replaceFile'}
				onConfirm={commitSave}
				onCancel={() => setConfirm(null)}
			/>

			{/* Detail-pane delete confirmation (separate from the per-row
			    list delete handled inside FontList). */}
			<div data-test="component-DetailDeleteDialog-wrap">
				<DeleteFontDialog
					isOpen={pendingDelete !== null}
					fontName={savedFont?.font_name ?? ''}
					onConfirm={confirmDelete}
					onCancel={cancelDelete}
				/>
			</div>

			{toastNodes.length > 0 && (
				<div
					className="gfpdf-fm-snackbar-list"
					role="region"
					aria-label={__('Notifications', 'gravity-pdf')}
				>
					{toastNodes}
				</div>
			)}
		</div>
	);
};

/* Strip simple <strong>/<em> tags coming from server-side messages so they
   render plainly inside Snackbar (which doesn't support rich text). */
function stripTags(input: string): string {
	return input.replace(/<\/?[a-z][^>]*>/gi, '');
}

export default FontManagerModal;
