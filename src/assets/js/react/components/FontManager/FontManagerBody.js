/* Dependencies */
import React, { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import { useSelector, useDispatch } from 'react-redux';
/* Redux actions */
import {
	getCustomFontList as getCustomFontListAction,
	addFont as addFontAction,
	editFont as editFontAction,
	validationError as validationErrorAction,
	deleteVariantError as deleteVariantAction,
	selectFont as selectFontAction,
	clearAddFontMsg as clearAddFontMsgAction,
	clearDropzoneError as clearDropzoneErrorAction,
} from '../../actions/fontManager';
/* Components */
import Alert from '../Alert/Alert';
import SearchBox from './SearchBox';
import FontList from './FontList';
import AddFont from './AddFont';
import UpdateFont from './UpdateFont';
import initialState from './InitialAddUpdateState';
/* Utilities */
import { adjustFontListHeight } from '../../utilities/FontManager/adjustFontListHeight';
import {
	toggleUpdateFont,
	addClass,
} from '../../utilities/FontManager/toggleUpdateFont';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * FontManagerBody component
 *
 * @param {Object} root0
 * @param {*}      root0.id
 * @param {*}      root0.navigate
 * @since 6.0
 */
const FontManagerBody = ({ id, navigate }) => {
	const dispatch = useDispatch();
	const loading = useSelector((s) => s.fontManager.addFontLoading);
	const fontList = useSelector((s) => s.fontManager.fontList);
	const msg = useSelector((s) => s.fontManager.msg);

	const [addFontState, setAddFontState] = useState(initialState);
	const [updateFontState, setUpdateFontState] = useState(initialState);

	/* Track previous props for componentDidUpdate comparisons */
	const prevIdRef = useRef(id);
	const prevFontListRef = useRef(fontList);
	const prevMsgRef = useRef(msg);

	/* componentDidMount: fetch font list, auto-open update panel if id is in URL */
	useEffect(() => {
		dispatch(getCustomFontListAction());

		if (id) {
			addClass(document.querySelector('.update-font'), navigate, id);
		}
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* componentDidUpdate: react to id/fontList/msg changes */
	useEffect(() => {
		const prevId = prevIdRef.current;
		const prevFontList = prevFontListRef.current;
		const prevMsg = prevMsgRef.current;
		prevIdRef.current = id;
		prevFontListRef.current = fontList;
		prevMsgRef.current = msg;

		const handleCheckValidId = (list, fontId) =>
			!!(list && list.filter((f) => f.id === fontId)[0]);

		const handleRequestFontDetails = () => {
			const font = fontList.filter((f) => f.id === id)[0];
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
		if (prevId !== id && id) {
			if (!handleCheckValidId(fontList, id)) {
				return navigate('/fontmanager/');
			}
			handleRequestFontDetails();
		}

		/* If font list updated while an id is active, reload details */
		if (prevFontList !== fontList && fontList && id) {
			if (!handleCheckValidId(fontList, id)) {
				return navigate('/fontmanager/');
			}
			handleRequestFontDetails();
		}

		/* If font was successfully installed, auto-select and show update panel */
		if (prevMsg !== msg && msg.success && !id) {
			if (msg.error && msg.error.fontList) {
				setAddFontState(initialState);
				setUpdateFontState(initialState);
				return;
			}

			/* Auto select new added font and open update panel */
			const newFontIndex = Object.keys(fontList).slice(-1).pop();
			const newFont = fontList[newFontIndex];
			dispatch(selectFontAction(newFont.id));
			toggleUpdateFont(navigate, newFont.id);
		}
	}, [id, fontList, msg, navigate, dispatch]);

	const handleGetCurrentColumnState = (column) =>
		column === 'addFont' ? addFontState : updateFontState;

	const handleDeleteFontStyle = (e, key, state) => {
		e.preventDefault();

		if (msg.error && msg.error.addFont) {
			const forValue = `gfpdf-font-variant-${key}`;
			const dropZone = document.querySelector(`div[for=${forValue}]`);
			if (dropZone) {
				dropZone.classList.remove('error');
			}
			dispatch(clearDropzoneErrorAction(key));
		}

		const currentState = handleGetCurrentColumnState(state);
		const updatedState = {
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

	const handleInputChange = (e, state) => {
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

	const handleUpload = (fontVariant, file, state) => {
		if (
			msg.error &&
			typeof msg.error.addFont === 'object' &&
			msg.error.addFont
		) {
			Object.entries(msg.error.addFont).forEach(([key]) => {
				if (fontVariant === key) {
					dispatch(deleteVariantAction(fontVariant));
				}
			});
		}

		const currentState = handleGetCurrentColumnState(state);
		const updatedState = {
			...currentState,
			fontStyles: {
				...currentState.fontStyles,
				[fontVariant]: !file ? '' : file,
			},
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
		state,
		label,
		regular,
		currentUpdateFontState
	) => {
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

		dispatch(validationErrorAction());
		return false;
	};

	const handleUpdateFontStateAfterChange = (newState, state) => {
		if (state === 'updateFont' && id) {
			const activeFont = fontList.filter((f) => f.id === id)[0];
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

		dispatch(addFontAction({ label, ...fontStyles }));
	};

	const handleEditFont = (fontId) => {
		const { label, fontStyles } = updateFontState;
		const data = {};

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

		Object.keys(fontStyles).forEach((key) => {
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
			dispatch(clearAddFontMsgAction());
			return;
		}

		dispatch(editFontAction({ id: fontId, font: { label, ...data } }));
	};

	const handleCancelEditFont = () => {
		toggleUpdateFont(navigate);
		dispatch(clearAddFontMsgAction());
	};

	const handleCancelEditFontKeypress = (e) => {
		if (e.key === 'Enter' || e.key === ' ') {
			toggleUpdateFont(navigate);
			dispatch(clearAddFontMsgAction());
		}
	};

	const handleSubmit = (e) => {
		e.preventDefault();

		if (id) {
			return handleEditFont(id);
		}

		handleAddFont();
	};

	const updateFontVisible = document.querySelector('.update-font.show');

	return (
		<div
			data-test="component-FontManagerBody"
			id="gfpdf-font-manager-container"
			className="wp-clearfix theme-about"
		>
			<div className="font-list-column container">
				<SearchBox id={id} />

				{msg.error && msg.error.deleteFont && (
					<Alert msg={msg.error.deleteFont} />
				)}

				<FontList id={id} navigate={navigate} />
			</div>

			<div className="add-update-font-column container">
				<AddFont
					onHandleInputChange={handleInputChange}
					onHandleUpload={handleUpload}
					onHandleDeleteFontStyle={handleDeleteFontStyle}
					onHandleSubmit={handleSubmit}
					msg={msg}
					loading={loading}
					tabIndexFontName={!updateFontVisible ? '0' : '-1'}
					tabIndexFontFiles={!updateFontVisible ? '0' : '-1'}
					tabIndexFooterButtons={!updateFontVisible ? '0' : '-1'}
					{...addFontState}
				/>

				<UpdateFont
					onHandleInputChange={handleInputChange}
					onHandleUpload={handleUpload}
					onHandleDeleteFontStyle={handleDeleteFontStyle}
					onHandleCancelEditFont={handleCancelEditFont}
					onHandleCancelEditFontKeypress={
						handleCancelEditFontKeypress
					}
					onHandleSubmit={handleSubmit}
					fontList={fontList}
					msg={msg}
					loading={loading}
					tabIndexFontName={updateFontVisible ? '0' : '-1'}
					tabIndexFontFiles={updateFontVisible ? '0' : '-1'}
					tabIndexFooterButtons={updateFontVisible ? '0' : '-1'}
					{...updateFontState}
				/>
			</div>
		</div>
	);
};

FontManagerBody.propTypes = {
	id: PropTypes.string,
	navigate: PropTypes.func.isRequired,
};

export default FontManagerBody;
