/* Dependencies */
import { useEffect } from '@wordpress/element';
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
/* Types */
import { FontFormState } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

type FontStyles = FontFormState['fontStyles'];

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
		setAddFontState,
		setUpdateFontState,
		resetAddFontState,
		resetUpdateFontState,
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
	const addFontState = useSelect(
		(select) => select(fontManagerStore).getAddFontState(),
		[]
	);
	const updateFontState = useSelect(
		(select) => select(fontManagerStore).getUpdateFontState(),
		[]
	);

	/* componentDidMount: fetch font list */
	useEffect(() => {
		getCustomFontList();
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* Load (or reload) the selected font's details when the id changes or when
	   the font list refreshes while an id is active. */
	useEffect(() => {
		if (!activeFontId) {
			return;
		}

		const font = fontList.find((f) => f.id === activeFontId);
		if (!font) {
			onSelectFont('');
			return;
		}

		resetAddFontState();
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
	}, [activeFontId, fontList, onSelectFont]); // eslint-disable-line react-hooks/exhaustive-deps

	/* Auto-select a newly-installed font once the server confirms success. */
	useEffect(() => {
		if (!msg.success || activeFontId) {
			return;
		}

		if (msg.error && msg.error.fontList) {
			resetAddFontState();
			resetUpdateFontState();
			return;
		}

		const newFont = fontList[fontList.length - 1];
		if (!newFont) {
			return;
		}
		selectFont(newFont.id);
		onSelectFont(newFont.id);
		// Intentionally ignores activeFontId/fontList/etc. deps — fire only on msg
		// changes so list refreshes don't retrigger auto-select.
	}, [msg]); // eslint-disable-line react-hooks/exhaustive-deps

	const getCurrentColumnState = (column: string): FontFormState =>
		column === 'addFont' ? addFontState : updateFontState;

	const applyStateForColumn = (
		column: string,
		nextState: FontFormState
	): void => {
		if (column === 'addFont') {
			setAddFontState(nextState);
			return;
		}
		/* update column: re-compute the disableUpdateButton flag based on the active font */
		if (!activeFontId) {
			setUpdateFontState(nextState);
			return;
		}
		const activeFont = fontList.find((f) => f.id === activeFontId);
		if (!activeFont) {
			setUpdateFontState(nextState);
			return;
		}
		const { label, fontStyles } = nextState;
		const unchanged =
			activeFont.font_name === label &&
			activeFont.regular === fontStyles.regular &&
			activeFont.italics === fontStyles.italics &&
			activeFont.bold === fontStyles.bold &&
			activeFont.bolditalics === fontStyles.bolditalics;
		setUpdateFontState({ ...nextState, disableUpdateButton: unchanged });
	};

	const handleDeleteFontStyle = (
		e: MouseEvent,
		key: string,
		state: string
	) => {
		e.preventDefault();

		if (msg.error && msg.error.addFont) {
			/* clearDropzoneError removes this variant's key from msg.error.addFont
			   in the store, which causes FontVariant to re-render without the
			   `error` class on the next render cycle. */
			clearDropzoneError(key);
		}

		const currentState = getCurrentColumnState(state);
		applyStateForColumn(state, {
			...currentState,
			fontStyles: {
				...currentState.fontStyles,
				[key]: '',
			},
			validateRegular: true,
		});
	};

	const handleInputChange = (
		e: ChangeEvent<HTMLInputElement>,
		state: 'addFont' | 'updateFont'
	) => {
		const currentState = getCurrentColumnState(state);
		applyStateForColumn(state, {
			...currentState,
			label: e.target.value,
		});
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

		const currentState = getCurrentColumnState(state);
		applyStateForColumn(state, {
			...currentState,
			fontStyles: {
				...currentState.fontStyles,
				[fontVariant]: !file ? '' : file,
			} as FontStyles,
		});
	};

	const validateInputFields = (
		state: string,
		label: string,
		regular: string | File,
		currentUpdateFontState?: FontFormState
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

	const handleAddFont = () => {
		const { label, fontStyles } = addFontState;

		if (!validateInputFields('addFont', label, fontStyles.regular)) {
			return;
		}

		addFont({ label, ...fontStyles });
	};

	const handleEditFont = (fontId: string) => {
		const { label, fontStyles } = updateFontState;
		const data: Partial<FontStyles> = {};

		if (
			!validateInputFields(
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

		const currentFont = fontList.find((f) => f.id === fontId);
		if (!currentFont) {
			return;
		}
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
				{detailOpen ? (
					<FontForm
						mode="update"
						onHandleInputChange={handleInputChange}
						onHandleUpload={handleUpload}
						onHandleDeleteFontStyle={handleDeleteFontStyle}
						onHandleCancelEditFont={handleCancelEditFont}
						onHandleCancelEditFontKeypress={
							handleCancelEditFontKeypress
						}
						onHandleSubmit={handleSubmit}
					/>
				) : (
					<FontForm
						mode="add"
						onHandleInputChange={handleInputChange}
						onHandleUpload={handleUpload}
						onHandleDeleteFontStyle={handleDeleteFontStyle}
						onHandleSubmit={handleSubmit}
					/>
				)}
			</div>
		</div>
	);
};

export default FontManagerBody;
