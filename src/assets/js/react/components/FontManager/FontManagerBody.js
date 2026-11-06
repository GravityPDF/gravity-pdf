/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
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
import FontDetail from './FontDetail';
import FontList from './FontList';
import AddFont from './AddFont';
import UpdateFont from './UpdateFont';
import initialState from './InitialAddUpdateState';
import { isCustomFont } from '../../utilities/FontManager/fontManagerReducer';
import { toggleUpdateFont } from '../../utilities/FontManager/toggleUpdateFont';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * FontManagerBody component
 *
 * @since 6.0
 */
export class FontManagerBody extends Component {
	/**
	 * PropTypes
	 *
	 * @since 6.0
	 */
	static propTypes = {
		getCustomFontList: PropTypes.func.isRequired,
		id: PropTypes.string,
		loading: PropTypes.bool.isRequired,
		fontList: PropTypes.arrayOf(PropTypes.object).isRequired,
		msg: PropTypes.object.isRequired,
		clearDropzoneError: PropTypes.func.isRequired,
		clearAddFontMsg: PropTypes.func.isRequired,
		editFont: PropTypes.func.isRequired,
		validationError: PropTypes.func.isRequired,
		deleteVariantError: PropTypes.func.isRequired,
		selectFont: PropTypes.func.isRequired,
		addFont: PropTypes.func.isRequired,
		navigate: PropTypes.func.isRequired,
		pathname: PropTypes.string,
	};

	/**
	 * Initialize component state
	 *
	 * @type {{
	 * addFont: {
	 *  fontStyles: { italics: string, bold: string, bolditalics: string, regular: string },
	 *  disableUpdateButton: boolean,
	 *  id: string,
	 *  label: string,
	 *  validateLabel: boolean,
	 *  validateRegular: boolean
	 * },
	 * updateFont: {
	 *  fontStyles: { italics: string, bold: string, bolditalics: string, regular: string },
	 *  disableUpdateButton: boolean,
	 *  id: string,
	 *  label: string,
	 *  validateLabel: boolean,
	 *  validateRegular: boolean
	 * }}}
	 *
	 * @since 6.0
	 */
	state = {
		addFont: initialState,
		updateFont: initialState,
	};

	/**
	 * On mount, Request custom font list by calling redux action getCustomFontList()
	 *
	 * @since 6.0
	 */
	componentDidMount() {
		this.props.getCustomFontList();
	}

	/**
	 * If component did update and new props are received,
	 * fires appropriate action based on redux store data
	 *
	 * @param { Object } prevProps
	 *
	 * @since 6.0
	 */
	componentDidUpdate(prevProps) {
		const { id, fontList, msg, navigate } = this.props;

		if (prevProps.id !== id) {
			this.handleMoveFocus(prevProps.id);
		}

		/* If font name is selected call the method handleRequestFontDetails() */
		if (prevProps.id !== id && id) {
			/* Perform check if the accessed ID is valid, if not prevent fatal error event */
			if (!this.handleCheckValidId(fontList, id)) {
				return navigate('/fontmanager/');
			}

			this.handleRequestFontDetails();
		}

		/* If font list did update, call the method handleRequestFontDetails() */
		if (prevProps.fontList !== fontList && fontList && id) {
			/* Perform check if the accessed ID is valid, if not prevent fatal error event */
			if (!this.handleCheckValidId(fontList, id)) {
				return navigate('/fontmanager/');
			}

			this.handleRequestFontDetails();
		}

		/* If font is successfully installed, auto select the new added font and slide update font panel */
		if (prevProps.msg !== msg && msg.success?.addFont && !id) {
			/* Check if there's a response message error for fontList */
			if (msg.error && msg.error.fontList) {
				return this.handleSetDefaultState();
			}

			/* Auto select new added font after a successful submission */
			this.handleAutoSelectNewAddedFont(navigate, fontList);
		}
	}

	/**
	 * Handle check if the current accessed ID is valid/active or not
	 *
	 * @param { Array<Object> } fontList
	 * @param { string }        id
	 * @since 6.0
	 */
	handleCheckValidId = (fontList, id) => {
		return fontList.some((font) => font.id === id);
	};

	/**
	 * Follow the panel the route just showed: into a font being edited, into the add form when asked to, or back to the
	 * row of the font that closed
	 *
	 * @param { string } previousId
	 *
	 * @since 6.19.0
	 */
	handleMoveFocus = (previousId) => {
		const { id, fontList } = this.props;
		const current = fontList.find((font) => font.id === id);

		if (current && isCustomFont(current)) {
			document.getElementById('gfpdf-update-font-name-input')?.focus();
		} else if (!id && this.focusAddForm) {
			document.getElementById('gfpdf-add-font-name-input')?.focus();
		} else if (!id) {
			document.querySelector(`[data-font="${previousId}"]`)?.focus();
		}

		this.focusAddForm = false;
	};

	/**
	 * Map current font details from our redux store to component state (updateFont)
	 *
	 * @since 6.0
	 */
	handleRequestFontDetails = () => {
		const { fontList, id } = this.props;
		const font = fontList.filter((f) => f.id === id)[0];

		/* Only a custom font can be edited */
		if (!isCustomFont(font)) {
			return;
		}

		this.setState({
			addFont: initialState,
			updateFont: {
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
			},
		});
	};

	/**
	 * Set component state back to its default state
	 *
	 * @since 6.0
	 */
	handleSetDefaultState = () => {
		this.setState({
			addFont: initialState,
			updateFont: initialState,
		});
	};

	/**
	 * Auto select new added font and slide update font panel
	 *
	 * @param { Function }      navigate
	 * @param { Array<Object> } fontList
	 *
	 * @since 6.0
	 */
	handleAutoSelectNewAddedFont = (navigate, fontList) => {
		const newFontIndex = Object.keys(fontList).slice(-1).pop();
		const newFont = fontList[newFontIndex];

		this.props.selectFont(newFont.id);
		toggleUpdateFont(navigate, newFont.id);
	};

	/**
	 * Return current active state (addFont or updateFont)
	 *
	 * @param { string } column
	 *
	 * @return { Object } state
	 *
	 * @since 6.0
	 */
	handleGetCurrentColumnState = (column) => {
		return column === 'addFont'
			? this.state.addFont
			: this.state.updateFont;
	};

	/**
	 * Handle deletion process of a font variant (font files drop box)
	 *
	 * @param { Event }  e
	 * @param { string } key
	 * @param { string } state
	 * @since 6.0
	 */
	handleDeleteFontStyle = (e, key, state) => {
		e.preventDefault();

		const {
			msg: { error },
			clearDropzoneError,
		} = this.props;

		/* Remove addFont error */
		if (error && error.addFont) {
			/* Call redux action clearDropzoneError() */
			clearDropzoneError(key);
		}

		this.handleGetCurrentColumnState(state).fontStyles[key] = '';
		this.setState({
			[state]: {
				...this.handleGetCurrentColumnState(state),
				validateRegular: true,
			},
		});
		this.forceUpdate();
		this.handleUpdateFontState();
	};

	/**
	 * Listen to font name input box field change
	 *
	 * @param { Event }  e
	 * @param { string } state
	 * @since 6.0
	 */
	handleInputChange = (e, state) => {
		const { addFont, updateFont } = this.state;
		const defaultState = state === 'addFont' ? addFont : updateFont;

		this.setState(
			{
				[state]: {
					...defaultState,
					label: e.target.value,
				},
			},
			() => this.handleUpdateFontState()
		);
	};

	/**
	 * Handle process for uploading font variant
	 *
	 * @param { string } fontVariant
	 * @param { file }   file
	 * @param { string } state
	 *
	 * @since 6.0
	 */
	handleUpload = (fontVariant, file, state) => {
		const {
			msg: { error },
		} = this.props;
		const fontFileMissing =
			error && typeof error.addFont === 'object' && error.addFont;

		/* If error exist delete it first to enable dropping */
		if (fontFileMissing) {
			Object.entries(fontFileMissing).map(([key]) => {
				if (fontVariant === key) {
					/* Call redux action deleteVariantError() */
					return this.props.deleteVariantError(fontVariant);
				}

				return false;
			});
		}

		/* Safeguard file */
		const checkFile = !file ? '' : file;

		this.setState(
			{
				[state]: {
					...this.handleGetCurrentColumnState(state),
					fontStyles: {
						...this.handleGetCurrentColumnState(state).fontStyles,
						[fontVariant]: checkFile,
					},
				},
			},
			() => this.handleUpdateFontState()
		);
	};

	/**
	 * Check validation for font name input box field and font files drop box field
	 *
	 * @param { string } state
	 * @param { string } label
	 * @param { string } regular
	 *
	 * @return { boolean } a flag used for conditional checks
	 *
	 * @since 6.0
	 */
	handleValidateInputFields = (state, label, regular) => {
		const defaultState =
			state === 'addFont' ? this.state.addFont : this.state.updateFont;
		const validate = {
			validateLabel: true,
			validateRegular: true,
		};
		let labelField = false;
		let regularField = false;

		/* Regex will allow only a-z, A-Z, and 0-9 */
		const checkSpecialCharRegex = /^[0-9a-zA-Z ]*$/;

		if (!checkSpecialCharRegex.test(label) || label === '') {
			labelField = false;
			validate.validateLabel = false;
		}

		if (checkSpecialCharRegex.test(label) && label !== '') {
			labelField = true;
			validate.validateLabel = true;
		}

		if (!regular) {
			regularField = false;
			validate.validateRegular = false;
		}

		if (regular) {
			regularField = true;
			validate.validateRegular = true;
		}

		if (labelField && regularField) {
			this.setState({ [state]: { ...defaultState, ...validate } });
			return true;
		}

		this.setState({ [state]: { ...defaultState, ...validate } });
		/* Call redux action validationError() */
		this.props.validationError();
		return false;
	};

	/**
	 * Check the update font panel state and disable or enable the update button based on
	 * new field change
	 *
	 * @since 6.0
	 */
	handleUpdateFontState = () => {
		const { fontList, id } = this.props;

		if (id) {
			const { label, fontStyles } = this.state.updateFont;
			const activeFont = fontList.filter((font) => font.id === id)[0];

			if (
				activeFont.font_name === label &&
				activeFont.regular === fontStyles.regular &&
				activeFont.italics === fontStyles.italics &&
				activeFont.bold === fontStyles.bold &&
				activeFont.bolditalics === fontStyles.bolditalics
			) {
				return this.setState({
					updateFont: {
						...this.state.updateFont,
						disableUpdateButton: true,
					},
				});
			}

			this.setState({
				updateFont: {
					...this.state.updateFont,
					disableUpdateButton: false,
				},
			});
		}
	};

	/**
	 * Handle our add font process and call our addFont redux action
	 *
	 * @since 6.0
	 */
	handleAddFont = () => {
		const { label, fontStyles } = this.state.addFont;

		/* Check if all fields are valid */
		if (
			!this.handleValidateInputFields(
				'addFont',
				label,
				fontStyles.regular
			)
		) {
			return;
		}

		/* Call redux action addFont() */
		this.props.addFont({ label, ...fontStyles });
	};

	/**
	 * Handle our edit font process and call our editFont redux action
	 *
	 * @param { string } id
	 *
	 * @since 6.0
	 */
	handleEditFont = (id) => {
		const { label, fontStyles } = this.state.updateFont;
		const { fontList, editFont, clearAddFontMsg } = this.props;
		const data = {};

		/* Check if all fields are valid */
		if (
			!this.handleValidateInputFields(
				'updateFont',
				label,
				fontStyles.regular
			)
		) {
			return;
		}

		/* Construct the data to be submitted */
		Object.keys(fontStyles).forEach((key) => {
			if (typeof fontStyles[key] === 'object' || fontStyles[key] === '') {
				data[key] = fontStyles[key];
			}
		});

		const currentFont = fontList.filter((font) => font.id === id)[0];
		const currentFontStyles = {
			regular: currentFont.regular,
			italics: currentFont.italics,
			bold: currentFont.bold,
			bolditalics: currentFont.bolditalics,
		};

		/* Check if there's no changes in current font data */
		if (
			label === currentFont.font_name &&
			JSON.stringify(fontStyles) === JSON.stringify(currentFontStyles)
		) {
			/* Call redux action clearAddFontMsg() */
			return clearAddFontMsg();
		}

		/* Call redux action editFont() */
		editFont({
			id,
			font: { label, ...data },
		});
	};

	/**
	 * Show the add font form, ready to type
	 *
	 * @since 6.19.0
	 */
	handleAddNewFont = () => {
		const { id, navigate, clearAddFontMsg } = this.props;

		clearAddFontMsg();

		if (!id) {
			document.getElementById('gfpdf-add-font-name-input')?.focus();

			return;
		}

		this.focusAddForm = true;
		toggleUpdateFont(navigate);
	};

	/**
	 * Empty the add font form
	 *
	 * @since 6.19.0
	 */
	handleCancelAddFont = () => {
		this.props.clearAddFontMsg();
		this.setState({ addFont: initialState });
	};

	/**
	 * Close the update font panel
	 *
	 * @since 6.0
	 */
	handleCancelEditFont = () => {
		const { navigate, clearAddFontMsg } = this.props;

		toggleUpdateFont(navigate);
		/* Call redux action clearAddFontMsg() */
		clearAddFontMsg();
	};

	/**
	 * Listen to form submit event and distinguish if it's add or edit request
	 *
	 * @param { Event } e
	 *
	 * @since 6.0
	 */
	handleSubmit = (e) => {
		e.preventDefault();
		const { id } = this.props;

		if (id) {
			return this.handleEditFont(id);
		}

		this.handleAddFont();
	};

	/**
	 * Display the font manager body UI
	 *
	 * @since 6.0
	 */
	render() {
		const { id, fontList, msg, loading, navigate } = this.props;
		const current = id && fontList.find((font) => font.id === id);
		const editing = current && isCustomFont(current);

		return (
			<div
				data-test="component-FontManagerBody"
				id="gfpdf-font-manager-container"
				className="wp-clearfix theme-about"
			>
				<div className="font-list-column container">
					<div className="font-sidebar-top">
						<SearchBox id={id} />

						<button
							type="button"
							data-test="font-add-new"
							className="button font-add-new"
							onClick={this.handleAddNewFont}
						>
							{GFPDF.fontManagerAddNew}
						</button>

						{msg.error && msg.error.deleteFont && (
							<Alert msg={msg.error.deleteFont} />
						)}

						<div aria-live="polite">
							{msg.success?.deleteFont && (
								<div
									data-test="font-deleted"
									className="notice notice-success gf-notice"
								>
									<p>{msg.success.deleteFont}</p>
								</div>
							)}
						</div>
					</div>

					<FontList id={id} navigate={navigate} />
				</div>

				<div className="add-update-font-column container">
					{current && !isCustomFont(current) && (
						<FontDetail font={current} />
					)}

					<AddFont
						onHandleInputChange={this.handleInputChange}
						onHandleUpload={this.handleUpload}
						onHandleDeleteFontStyle={this.handleDeleteFontStyle}
						onHandleSubmit={this.handleSubmit}
						onHandleCancelAddFont={this.handleCancelAddFont}
						msg={msg}
						loading={loading}
						{...this.state.addFont}
					/>

					<UpdateFont
						onHandleInputChange={this.handleInputChange}
						onHandleUpload={this.handleUpload}
						onHandleDeleteFontStyle={this.handleDeleteFontStyle}
						onHandleCancelEditFont={this.handleCancelEditFont}
						onHandleSubmit={this.handleSubmit}
						font={editing ? current : undefined}
						show={!!editing}
						msg={msg}
						loading={loading}
						{...this.state.updateFont}
					/>
				</div>
			</div>
		);
	}
}

/**
 * Map redux state to props
 *
 * @param { Object }        state
 * @param { Object }        state.fontManager
 * @param { boolean }       state.fontManager.addFontLoading
 * @param { Array<Object> } state.fontManager.fontList
 * @param { Object }        state.fontManager.msg
 *
 * @return {{ loading: boolean, fontList: Array<Object>, msg: Object }} mapped State
 *
 * @since 6.0
 */
const mapStateToProps = (state) => ({
	loading: state.fontManager.addFontLoading,
	fontList: state.fontManager.fontList,
	msg: state.fontManager.msg,
});

/**
 * Connect and dispatch redux actions as props
 *
 * @since 6.0
 */
export default connect(mapStateToProps, {
	getCustomFontList: getCustomFontListAction,
	addFont: addFontAction,
	editFont: editFontAction,
	validationError: validationErrorAction,
	deleteVariantError: deleteVariantAction,
	selectFont: selectFontAction,
	clearAddFontMsg: clearAddFontMsgAction,
	clearDropzoneError: clearDropzoneErrorAction,
})(FontManagerBody);
