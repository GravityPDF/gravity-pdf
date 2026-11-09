/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
/* Redux actions */
import {
	clearAddFontMsg as clearAddFontMsgAction,
	selectFont as selectFontAction,
} from '../../actions/fontManager';
/* Components */
import Icon from './Icon';
/* Utilities */
import { toggleUpdateFont } from '../../utilities/FontManager/toggleUpdateFont';
import { getStyleCount } from '../../utilities/FontManager/fontManagerReducer';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/* The fixed order of the list's groups, under the selected font */
const FONT_TYPES = [
	{ type: 'custom', label: GFPDF.fontManagerTypeCustom },
	{ type: 'bundled', label: GFPDF.fontManagerTypeBundled },
	{ type: 'core', label: GFPDF.fontManagerTypeCore },
	{ type: 'available', label: GFPDF.fontManagerTypeAvailable },
];

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
		selectFont: PropTypes.func.isRequired,
		fontList: PropTypes.arrayOf(
			PropTypes.shape({
				font_name: PropTypes.string.isRequired,
				id: PropTypes.string.isRequired,
				regular: PropTypes.string.isRequired,
				italics: PropTypes.string.isRequired,
				bold: PropTypes.string.isRequired,
				bolditalics: PropTypes.string.isRequired,
				type: PropTypes.string.isRequired,
			})
		).isRequired,
		searchResult: PropTypes.oneOfType([
			PropTypes.oneOf([null]).isRequired,
			PropTypes.arrayOf(PropTypes.object).isRequired,
		]),
		selectedFont: PropTypes.string.isRequired,
		fontUsage: PropTypes.object,
	};

	/**
	 * On mount, select the font chosen in the parent font dropdown
	 *
	 * @since 6.0
	 */
	componentDidMount() {
		this.handleSetSelectedFontNameValue();
	}

	/**
	 * Select the font in the dropdown if it's listed
	 *
	 * @since 6.0
	 */
	handleSetSelectedFontNameValue = () => {
		const { fontList, selectFont } = this.props;

		/* A form's PDF settings have the Font field, the global settings the Default Font field */
		const value = (
			document.querySelector('#gfpdf_settings\\[font\\]') ||
			document.querySelector('#gfpdf_settings\\[default_font\\]')
		).value;

		selectFont(fontList?.some((font) => font.id === value) ? value : '');
	};

	/**
	 * Show a font's detail, or close it if it's showing
	 *
	 * @param { Object } font
	 *
	 * @since 6.0
	 */
	handleFontClick = (font) => {
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

		toggleUpdateFont(navigate, id === font.id ? '' : font.id);
	};

	/**
	 * Display one font's row
	 *
	 * @param { Object } font
	 *
	 * @return { JSX.Element } The row
	 *
	 * @since 6.19.0
	 */
	renderFont = (font) => {
		const { id, selectedFont, fontUsage } = this.props;

		return (
			<button
				type="button"
				key={font.id}
				data-test="font-list-item"
				data-font={font.id}
				className={
					'font-list-item font-type-' +
					font.type +
					(font.id === id ? ' active' : '') +
					(font.id === selectedFont ? ' chosen' : '')
				}
				aria-current={font.id === id ? 'true' : undefined}
				onClick={() => this.handleFontClick(font)}
			>
				<span className="font-swatch" aria-hidden="true">
					Aa
				</span>

				<span className="font-meta">
					<span className="font-name-row">
						<span className="font-name">{font.font_name}</span>
						{font.id === selectedFont && (
							<span className="font-active-tag">
								{GFPDF.active}
							</span>
						)}
					</span>
					<span className="font-sub">
						{getStyleCount(font)}
						{fontUsage?.[font.id] && ' · ' + GFPDF.fontListInUse}
					</span>
				</span>

				{font.type === 'bundled' && (
					<span
						className="font-lock"
						title={GFPDF.fontManagerBundledWith}
					>
						<Icon name="lock" />
					</span>
				)}
			</button>
		);
	};

	/**
	 * Display the font list items UI, grouped by type
	 *
	 * @since 6.0
	 */
	render() {
		const { fontList, searchResult, selectedFont } = this.props;
		const list = (!searchResult ? fontList : searchResult) || [];
		const selected = list.find((font) => font.id === selectedFont);

		return (
			<div
				data-test="component-FontListItems"
				className="font-list-items"
			>
				{selected && (
					<div
						data-test="font-list-selected"
						className="font-list-selected"
						role="group"
						aria-label={GFPDF.active}
					>
						{this.renderFont(selected)}
					</div>
				)}

				{FONT_TYPES.map(({ type, label }) => {
					const fonts = list.filter(
						(font) => font.type === type && font !== selected
					);

					return (
						fonts.length > 0 && (
							<div
								key={type}
								data-test={'font-list-group-' + type}
								className="font-list-group"
								role="group"
								aria-label={label}
							>
								<div className="font-list-group-title">
									<span className="font-list-group-name">
										{label}
									</span>
									<span className="count">
										{fonts.length}
									</span>
								</div>

								{fonts.map((font) => this.renderFont(font))}
							</div>
						)
					);
				})}
			</div>
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
	selectFont: selectFontAction,
})(FontListItems);
