/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { sprintf } from 'sprintf-js';
/* Redux actions */
import {
	clearAddFontMsg as clearAddFontMsgAction,
	deleteFont as deleteFontAction,
	selectFont as selectFontAction,
	moveSelectedFontToTop as moveSelectedFontAction,
} from '../../actions/fontManager';
/* Components */
import FontListIcon from './FontListIcon';
import Spinner from '../Spinner';
/* Utilities */
import { toggleUpdateFont } from '../../utilities/FontManager/toggleUpdateFont';
import { isCustomFont } from '../../utilities/FontManager/fontManagerReducer';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * FontListItems component
 *
 * @since 6.0
 */
export class FontListItems extends Component {
	/**
	 * PropTypes
	 *
	 * @since 6.0
	 */
	static propTypes = {
		id: PropTypes.string,
		navigate: PropTypes.func.isRequired,
		clearAddFontMsg: PropTypes.func.isRequired,
		msg: PropTypes.object.isRequired,
		deleteFont: PropTypes.func.isRequired,
		selectFont: PropTypes.func.isRequired,
		moveSelectedFontToTop: PropTypes.func.isRequired,
		fontList: PropTypes.arrayOf(
			PropTypes.shape({
				font_name: PropTypes.string.isRequired,
				id: PropTypes.string.isRequired,
				regular: PropTypes.string.isRequired,
				italics: PropTypes.string.isRequired,
				bold: PropTypes.string.isRequired,
				bolditalics: PropTypes.string.isRequired,
				type: PropTypes.string.isRequired,
				can_delete: PropTypes.bool.isRequired,
				auto_install: PropTypes.bool,
			})
		).isRequired,
		searchResult: PropTypes.oneOfType([
			PropTypes.oneOf([null]).isRequired,
			PropTypes.arrayOf(PropTypes.object).isRequired,
		]),
		selectedFont: PropTypes.string.isRequired,
		loading: PropTypes.bool.isRequired,
		fontUsage: PropTypes.objectOf(PropTypes.arrayOf(PropTypes.string)),
	};

	/**
	 * Initialize component state
	 *
	 * @type {{ deleteId: string, moveSelectedFontToTop: boolean }}
	 *
	 * @since 6.0
	 */
	state = {
		deleteId: '',
		moveSelectedFontToTop: true,
	};

	/**
	 * On mount, select the font chosen in the parent font dropdown
	 *
	 * @since 6.0
	 */
	componentDidMount() {
		const { selectedFont } = this.props;

		this.handleSetSelectedFontNameValue();

		/* Move selected font at the top of the list */
		if (selectedFont) {
			this.handleMoveSelectedFontAtTheTopOfTheList(selectedFont);
		}
	}

	/**
	 * If component did update and new props are received we'll check if the methods
	 * handleResetLoadingState() and toggleUpdateFont() will be called
	 *
	 * @param { Object } prevProps
	 *
	 * @since 6.0
	 */
	componentDidUpdate(prevProps) {
		const { navigate, loading, fontList, selectedFont, id } = this.props;
		const updateFontVisible = document.querySelector('.update-font.show');

		/* Reset/Clear deleteId loading state */
		if (prevProps.loading !== loading && !loading) {
			this.handleResetLoadingState();
		}

		/* Remove update font panel after font is successfully deleted */
		if (
			prevProps.loading !== loading &&
			prevProps.fontList !== fontList &&
			updateFontVisible
		) {
			toggleUpdateFont(navigate);
		}

		/* Move selected font at the top of the list */
		if (
			prevProps.selectedFont === '' &&
			selectedFont &&
			!id &&
			this.state.moveSelectedFontToTop
		) {
			this.handleMoveSelectedFontAtTheTopOfTheList(selectedFont);
		}
	}

	/**
	 * Handle the functionality to select and set default font type to be used in PDFs (radio button)
	 *
	 * @since 6.0
	 */
	handleSetSelectedFontNameValue = () => {
		const { fontList, selectFont } = this.props;

		/* A form's PDF settings have the Font field, the global settings the Default Font field */
		const fontManagerSelectBoxValue = (
			document.querySelector('#gfpdf_settings\\[font\\]') ||
			document.querySelector('#gfpdf_settings\\[default_font\\]')
		).value;

		/* Do nothing if selected value doesn't exist on font manager fontList */
		if (
			!this.handleCheckSelectBoxValue(fontList, fontManagerSelectBoxValue)
		) {
			/* Call redux action selectFont() */
			return selectFont('');
		}

		/* Call redux action selectFont() */
		selectFont(fontManagerSelectBoxValue);
	};

	/**
	 * Handle check if font manager default selected font type is listed on custom font list
	 *
	 * @param { Array<Object> } fontList
	 * @param { string }        value
	 *
	 * @return { boolean } condition value
	 *
	 * @since 6.0
	 */
	handleCheckSelectBoxValue = (fontList, value) => {
		const checkIfValueExist =
			fontList && fontList.filter((font) => font.id === value)[0];

		if (!checkIfValueExist) {
			return false;
		}

		return true;
	};

	/**
	 * Reset deleteId state. The state of deleteId is used to match the current delete id request and
	 * trigger loading spinner
	 *
	 * @since 6.0
	 */
	handleResetLoadingState = () => {
		this.setState({ deleteId: '' });
	};

	/**
	 * Move selected font at the very top of the font list
	 *
	 * @param { string } selectedFont
	 *
	 * @since 6.0
	 */
	handleMoveSelectedFontAtTheTopOfTheList = (selectedFont) => {
		this.setState({ moveSelectedFontToTop: false });

		this.props.moveSelectedFontToTop(selectedFont);
	};

	/**
	 * Handle font click to display or hide update font panel
	 *
	 * @param { Object } font
	 *
	 * @since 6.0
	 */
	handleFontClick = (font) => {
		/* Core and bundled fonts can be selected and core fonts deleted, but not edited */
		if (!isCustomFont(font)) {
			return;
		}

		const {
			id,
			navigate,
			clearAddFontMsg,
			msg: { success, error },
		} = this.props;

		/* Remove previous msg */
		if ((success && success.addFont) || (error && error.addFont)) {
			/* Call redux action clearAddFontMsg */
			clearAddFontMsg();
		}

		if (id === font.id) {
			return toggleUpdateFont(navigate);
		}

		toggleUpdateFont(navigate, font.id);
	};

	/**
	 * Listen to an 'enter' keyboard event on font list item
	 *
	 * @param { KeyboardEvent } e
	 * @param { Object }        font
	 *
	 * @since 6.0
	 */
	handleFontClickKeypress = (e, font) => {
		const enter = 'Enter';
		const space = ' ';

		/* Check if a keyboard keypress is 'enter' (13) and call the method handleFontClick() */
		if (e.key === enter || e.key === space) {
			this.handleFontClick(font);
		}
	};

	/**
	 * Handle request of font deletion
	 *
	 * @param { Event }  e
	 * @param { Object } font
	 *
	 * @since 6.0
	 */
	handleDeleteFont = (e, font) => {
		e.stopPropagation();

		if (this.isDeleteLocked(font)) {
			return;
		}

		this.setState({ deleteId: font.id });

		/* Fire a native window alert box to confirm deletion request */
		if (window.confirm(this.getDeleteConfirmation(font))) {
			/* Call redux action deleteFont */
			this.props.deleteFont(font.id, font.type);
		}
	};

	/**
	 * The delete confirmation: a core font comes back on its own unless downloads are off
	 *
	 * @param { Object } font
	 *
	 * @return { string } The message
	 *
	 * @since 6.19.0
	 */
	getDeleteConfirmation = (font) => {
		if (font.type !== 'core') {
			return GFPDF.fontManagerDeleteFontConfirmation;
		}

		// eslint-disable-next-line @wordpress/valid-sprintf
		return sprintf(
			font.auto_install
				? GFPDF.fontManagerCoreConfirm
				: GFPDF.fontManagerCoreConfirmOffline,
			font.font_name
		);
	};

	/**
	 * Why the font is in use
	 *
	 * @param { Object } font
	 *
	 * @return { Array<string> } The reasons
	 *
	 * @since 6.19.0
	 */
	getInUse = (font) => this.props.fontUsage?.[font.id] || [];

	/**
	 * Whether Delete is off: the user can't delete this font, it's in use, or that isn't known yet
	 *
	 * @param { Object } font
	 *
	 * @return { boolean } Whether it's locked
	 *
	 * @since 6.19.0
	 */
	isDeleteLocked = (font) =>
		!font.can_delete ||
		!this.props.fontUsage ||
		this.getInUse(font).length > 0;

	/**
	 * The tag for a font's type
	 *
	 * @param { string } type
	 *
	 * @return { string } The label
	 *
	 * @since 6.19.0
	 */
	getTypeLabel = (type) =>
		({
			custom: GFPDF.fontManagerTypeCustom,
			core: GFPDF.fontManagerTypeCore,
			bundled: GFPDF.fontManagerTypeBundled,
		})[type];

	/**
	 * A row's Delete: a bundled font has none, and a locked one says why when it can
	 *
	 * @param { Object } font
	 * @param { string } tabIndex
	 *
	 * @return { JSX.Element | null } The button
	 *
	 * @since 6.19.0
	 */
	renderDeleteButton = (font, tabIndex) => {
		if (font.type === 'bundled') {
			return null;
		}

		if (this.props.loading && this.state.deleteId === font.id) {
			return <Spinner style="delete-font" />;
		}

		const locked = this.isDeleteLocked(font);

		return (
			<span
				role="button"
				className={
					'dashicons dashicons-trash' + (locked ? ' disabled' : '')
				}
				aria-label={
					GFPDF.fontManagerDeleteFontAriaLabel + ': ' + font.font_name
				}
				aria-disabled={locked}
				title={this.getInUse(font).join(', ') || undefined}
				onClick={(e) => this.handleDeleteFont(e, font)}
				onKeyDown={(e) => this.handleDeleteFontKeypress(e, font)}
				tabIndex={tabIndex}
			/>
		);
	};

	/**
	 * Listen to an 'enter' keyboard event for font deletion
	 *
	 * @param { KeyboardEvent } e
	 * @param { Object }        font
	 *
	 * @since 6.0
	 */
	handleDeleteFontKeypress = (e, font) => {
		const enter = 'Enter';
		const space = ' ';

		/* Check if a keyboard keypress is 'enter' (13) and call the method handleDeleteFont() */
		if (e.key === enter || e.key === space) {
			this.handleDeleteFont(e, font);
		}
	};

	/**
	 * Handle the process of selecting and deselecting of font type/name (radio button)
	 *
	 * @param { Event } e
	 *
	 * @since 6.0
	 */
	handleSelectFont = (e) => {
		this.props.selectFont(e.target.value);

		const installedFonts = document.querySelectorAll('.select-font-name');

		/* Handle font list keyboard accessibility thing */
		installedFonts.forEach((item) => {
			if (item.value === e.target.value) {
				item.checked = true;

				return;
			}

			item.checked = false;
		});
	};

	/**
	 * Listen to an 'enter' or 'space' keyboard event for selecting a font type/name (radio button)
	 *
	 * @param { KeyboardEvent } e
	 *
	 * @since 6.0
	 */
	handleSelectFontKeypress = (e) => {
		const enter = 'Enter';
		const space = ' ';

		/*
		 * Check if a keyboard keypress is 'enter' (13) or 'space' (12) and call the method handleDeleteFont()
		 */
		if (e.key === enter || e.key === space) {
			e.preventDefault();
			e.stopPropagation();

			this.handleSelectFont(e);
		}
	};

	/**
	 * Display the font list items UI
	 *
	 * @since 6.0
	 */
	render() {
		const updateFontVisible = document.querySelector('.update-font.show');
		const { id, fontList, searchResult, selectedFont } = this.props;
		const list = !searchResult ? fontList : searchResult;
		const tabIndex = !updateFontVisible ? '0' : '-1';

		return (
			<>
				<div
					data-test="component-FontListItems"
					className="font-list-items"
					role="listbox"
					aria-label={GFPDF.fontListInstalledFonts}
					aria-live="polite"
				>
					{list &&
						list.map((font) => {
							const inUse = this.getInUse(font);

							return (
								<div
									key={font.id}
									data-test="font-list-item"
									className={
										'font-list-item font-type-' +
										font.type +
										(font.id === id ? ' active' : '')
									}
									onClick={() => this.handleFontClick(font)}
									onKeyDown={(e) =>
										this.handleFontClickKeypress(e, font)
									}
									tabIndex={
										isCustomFont(font)
											? tabIndex
											: undefined
									}
									role="option"
								>
									<span className="font-name">
										<input
											type="radio"
											className="select-font-name"
											name={'select-font-name-' + font.id}
											value={font.id}
											onChange={(e) =>
												this.handleSelectFont(e)
											}
											onClick={(e) => e.stopPropagation()}
											onKeyDown={(e) =>
												this.handleSelectFontKeypress(e)
											}
											checked={font.id === selectedFont}
											aria-label={
												GFPDF.fontManagerSelectFontAriaLabel +
												': ' +
												font.font_name
											}
											tabIndex={tabIndex}
										/>
										{font.font_name}
										<span className="font-type">
											{this.getTypeLabel(font.type)}
										</span>
										{inUse.length > 0 && (
											<span className="font-in-use">
												{inUse.join(', ')}
											</span>
										)}
									</span>

									<FontListIcon font={font.regular} />
									<FontListIcon font={font.italics} />
									<FontListIcon font={font.bold} />
									<FontListIcon font={font.bolditalics} />

									{this.renderDeleteButton(font, tabIndex)}
								</div>
							);
						})}
				</div>

				{list &&
					list.some(
						(font) => font.type === 'core' && !font.can_delete
					) && (
						<p
							data-test="font-list-network-note"
							className="font-list-note"
						>
							{GFPDF.fontManagerNetworkOnly}
						</p>
					)}
			</>
		);
	}
}

/**
 * Map redux state to props
 *
 * @param { Object } state
 * @param { Object } state.fontManager
 *
 * @return {{
 *   loading: boolean,
 *   fontList: Array<Object>,
 *   searchResult: null | Array<Object>,
 *   selectedFont: string,
 *   fontUsage: null | Object,
 *   msg: object,
 * }} mappedState
 *
 * @since 6.0
 */
const mapStateToProps = (state) => ({
	loading: state.fontManager.deleteFontLoading,
	fontList: state.fontManager.fontList,
	searchResult: state.fontManager.searchResult,
	selectedFont: state.fontManager.selectedFont,
	fontUsage: state.fontManager.fontUsage,
	msg: state.fontManager.msg,
});

/**
 * Connect and dispatch redux actions as props
 *
 * @since 6.0
 */
export default connect(mapStateToProps, {
	clearAddFontMsg: clearAddFontMsgAction,
	deleteFont: deleteFontAction,
	selectFont: selectFontAction,
	moveSelectedFontToTop: moveSelectedFontAction,
})(FontListItems);
