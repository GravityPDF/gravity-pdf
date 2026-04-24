/* Dependencies */
import { useState, useEffect, useRef } from '@wordpress/element';
import type { MouseEvent, ChangeEvent, KeyboardEvent, FormEvent } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';
/* Components */
import Alert from '../Alert/Alert';
import SearchBox from './SearchBox';
import FontList from './FontList';
import FontForm from './FontForm';
import initialState, {
	AddUpdateFontState,
	FontStyles,
} from './InitialAddUpdateState';
/* Utilities */
import { adjustFontListHeight } from '../../utilities/FontManager/adjustFontListHeight';
/* Types */
import { FontItem, FontManagerMsg } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	activeFontId: string;
	onSelectFont: (id: string) => void;
}

const FontManagerBody = ({ activeFontId, onSelectFont }: Props) => {
	const {
		getCustomFontList,
		addFont,
		editFont,
		validationError,
		deleteVariantError,
		selectFont,
		clearDropzoneError,
		clearAddFontMsg,
	} = useDispatch(FONT_MANAGER_STORE_NAME);
	const loading = useSelect(
		(select) => select(fontManagerStore).getAddFontLoading(),
		[]
	);
	const fontList = useSelect(
		(select) => select(fontManagerStore).getFontList(),
		[]
	);
	const msg = useSelect((select) => select(fontManagerStore).getMsg(), []);

	const [addFontState, setAddFontState] =
		useState<AddUpdateFontState>(initialState);
	const [updateFontState, setUpdateFontState] =
		useState<AddUpdateFontState>(initialState);

	/* Track previous props for componentDidUpdate comparisons */
	const prevIdRef = useRef(activeFontId);
	const prevFontListRef = useRef<FontItem[]>(fontList);
	const prevMsgRef = useRef<FontManagerMsg>(msg);

	/* componentDidMount: fetch font list */
	useEffect(() => {
		getCustomFontList();
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* componentDidUpdate: react to activeFontId/fontList/msg changes */
	useEffect(() => {
		const prevId = prevIdRef.current;
		const prevFontList = prevFontListRef.current;
		const prevMsg = prevMsgRef.current;
		prevIdRef.current = activeFontId;
		prevFontListRef.current = fontList;
		prevMsgRef.current = msg;

		const handleCheckValidId = (list: FontItem[], fontId: string) =>
			!!(list && list.filter((f) => f.id === fontId)[0]);

		const handleRequestFontDetails = () => {
			const font = fontList.filter((f) => f.id === activeFontId)[0];
			setAddFontState(initialState);
			setUpdateFontState({
				id: font.id,
				label: font.font_name,
				fontStyles: {
					regular: font.regular,
					italics: font.italics,
					bold: font.bold,
					bolditalics: font.bolditalics,
				},
				validateLabel: true,
				validateRegular: true,
				disableUpdateButton: true,
			});
			setTimeout(() => adjustFontListHeight(), 100);
		};

		/* If font name is selected, load its details */
		if (prevId !== activeFontId && activeFontId) {
			if (!handleCheckValidId(fontList, activeFontId)) {
				onSelectFont('');
				return;
			}
			handleRequestFontDetails();
		}

		/* If font list updated while an id is active, reload details */
		if (prevFontList !== fontList && fontList && activeFontId) {
			if (!handleCheckValidId(fontList, activeFontId)) {
				onSelectFont('');
				return;
			}
			handleRequestFontDetails();
		}

		/* If font was successfully installed, auto-select and show update panel */
		if (prevMsg !== msg && msg.success && !activeFontId) {
			if (msg.error && msg.error.fontList) {
				setAddFontState(initialState);
				setUpdateFontState(initialState);
				return;
			}

			/* Auto select new added font (opens update panel via activeFontId) */
			const newFont = fontList[fontList.length - 1];
			selectFont(newFont.id);
			onSelectFont(newFont.id);
		}
	}, [activeFontId, fontList, msg, onSelectFont, selectFont]);

	const handleGetCurrentColumnState = (column: string): AddUpdateFontState =>
		column === 'addFont' ? addFontState : updateFontState;

	const handleDeleteFontStyle = (
		e: MouseEvent,
		key: string,
		state: string
	) => {
		e.preventDefault();

		if (msg.error && msg.error.addFont) {
			const forValue = `gfpdf-font-variant-${key}`;
			const dropZone = document.querySelector(`div[for=${forValue}]`);
			if (dropZone) {
				dropZone.classList.remove('error');
			}
			clearDropzoneError(key);
		}

		const currentState = handleGetCurrentColumnState(state);
		const updatedState: AddUpdateFontState = {
			...currentState,
			fontStyles: {
				...currentState.fontStyles,
				[key]: '',
			},
			validateRegular: true,
		};

		if (state === 'addFont') {
			setAddFontState(updatedState);
			handleUpdateFontStateAfterChange(updatedState, state);
		} else {
			setUpdateFontState(updatedState);
			handleUpdateFontStateAfterChange(updatedState, state);
		}
	};

	const handleInputChange = (
		e: ChangeEvent<HTMLInputElement>,
		state: 'addFont' | 'updateFont'
	) => {
		const currentState = handleGetCurrentColumnState(state);
		const updatedState = { ...currentState, label: e.target.value };

		if (state === 'addFont') {
			setAddFontState(updatedState);
			handleUpdateFontStateAfterChange(updatedState, state);
		} else {
			setUpdateFontState(updatedState);
			handleUpdateFontStateAfterChange(updatedState, state);
		}
	};

	const handleUpload = (fontVariant: string, file: File, state: string) => {
		if (
			msg.error &&
			typeof msg.error.addFont === 'object' &&
			msg.error.addFont
		) {
			Object.entries(msg.error.addFont).forEach(([key]) => {
				if (fontVariant === key) {
					deleteVariantError(fontVariant);
				}
			});
		}

		const currentState = handleGetCurrentColumnState(state);
		const updatedState: AddUpdateFontState = {
			...currentState,
			fontStyles: {
				...currentState.fontStyles,
				[fontVariant]: !file ? '' : file,
			} as FontStyles,
		};

		if (state === 'addFont') {
			setAddFontState(updatedState);
			handleUpdateFontStateAfterChange(updatedState, state);
		} else {
			setUpdateFontState(updatedState);
			handleUpdateFontStateAfterChange(updatedState, state);
		}
	};

	const handleValidateInputFields = (
		state: string,
		label: string,
		regular: string | File,
		currentUpdateFontState?: AddUpdateFontState
	): boolean => {
		const defaultState =
			state === 'addFont'
				? addFontState
				: currentUpdateFontState || updateFontState;
		const checkSpecialCharRegex = /^[0-9a-zA-Z ]*$/;
		const labelValid = checkSpecialCharRegex.test(label) && label !== '';
		const regularValid = !!regular;
		const validate = {
			validateLabel: labelValid,
			validateRegular: regularValid,
		};

		if (state === 'addFont') {
			setAddFontState({ ...defaultState, ...validate });
		} else {
			setUpdateFontState({ ...defaultState, ...validate });
		}

		if (labelValid && regularValid) {
			return true;
		}

		validationError();
		return false;
	};

	const handleUpdateFontStateAfterChange = (
		newState: AddUpdateFontState,
		state: string
	) => {
		if (state === 'updateFont' && activeFontId) {
			const activeFont = fontList.filter((f) => f.id === activeFontId)[0];
			if (!activeFont) {
				return;
			}

			const { label, fontStyles } = newState;
			const unchanged =
				activeFont.font_name === label &&
				activeFont.regular === fontStyles.regular &&
				activeFont.italics === fontStyles.italics &&
				activeFont.bold === fontStyles.bold &&
				activeFont.bolditalics === fontStyles.bolditalics;

			setUpdateFontState({
				...newState,
				disableUpdateButton: unchanged,
			});
		}
	};

	const handleAddFont = () => {
		const { label, fontStyles } = addFontState;

		if (!handleValidateInputFields('addFont', label, fontStyles.regular)) {
			return;
		}

		addFont({ label, ...fontStyles });
	};

	const handleEditFont = (fontId: string) => {
		const { label, fontStyles } = updateFontState;
		const data: Partial<FontStyles> = {};

		if (
			!handleValidateInputFields(
				'updateFont',
				label,
				fontStyles.regular,
				updateFontState
			)
		) {
			return;
		}

		(Object.keys(fontStyles) as (keyof FontStyles)[]).forEach((key) => {
			if (typeof fontStyles[key] === 'object' || fontStyles[key] === '') {
				data[key] = fontStyles[key];
			}
		});

		const currentFont = fontList.filter((f) => f.id === fontId)[0];
		const currentFontStyles = {
			regular: currentFont.regular,
			italics: currentFont.italics,
			bold: currentFont.bold,
			bolditalics: currentFont.bolditalics,
		};

		if (
			label === currentFont.font_name &&
			JSON.stringify(fontStyles) === JSON.stringify(currentFontStyles)
		) {
			clearAddFontMsg();
			return;
		}

		editFont({ id: fontId, font: { label, ...data } });
	};

	const handleCancelEditFont = () => {
		onSelectFont('');
		clearAddFontMsg();
	};

	const handleCancelEditFontKeypress = (e: KeyboardEvent) => {
		if (e.key === 'Enter' || e.key === ' ') {
			onSelectFont('');
			clearAddFontMsg();
		}
	};

	const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
		e.preventDefault();

		if (activeFontId) {
			return handleEditFont(activeFontId);
		}

		handleAddFont();
	};

	const detailOpen = Boolean(activeFontId);

	return (
		<div
			data-test="component-FontManagerBody"
			id="gfpdf-font-manager-container"
			className="wp-clearfix theme-about"
		>
			<div className="font-list-column container">
				<SearchBox id={activeFontId} />

				{msg.error && msg.error.deleteFont && (
					<Alert msg={msg.error.deleteFont} />
				)}

				<FontList
					activeFontId={activeFontId}
					onSelectFont={onSelectFont}
					hasDetailOpen={detailOpen}
				/>
			</div>

			<div className="add-update-font-column container">
				<FontForm
					mode="add"
					onHandleInputChange={handleInputChange}
					onHandleUpload={handleUpload}
					onHandleDeleteFontStyle={handleDeleteFontStyle}
					onHandleSubmit={handleSubmit}
					msg={msg}
					loading={loading}
					tabIndexFontName={!detailOpen ? 0 : -1}
					tabIndexFontFiles={!detailOpen ? 0 : -1}
					tabIndexFooterButtons={!detailOpen ? 0 : -1}
					{...addFontState}
				/>

				<FontForm
					mode="update"
					isOpen={detailOpen}
					onHandleInputChange={handleInputChange}
					onHandleUpload={handleUpload}
					onHandleDeleteFontStyle={handleDeleteFontStyle}
					onHandleCancelEditFont={handleCancelEditFont}
					onHandleCancelEditFontKeypress={
						handleCancelEditFontKeypress
					}
					onHandleSubmit={handleSubmit}
					msg={msg}
					loading={loading}
					tabIndexFontName={detailOpen ? 0 : -1}
					tabIndexFontFiles={detailOpen ? 0 : -1}
					tabIndexFooterButtons={detailOpen ? 0 : -1}
					{...updateFontState}
				/>
			</div>
		</div>
	);
};

export default FontManagerBody;
