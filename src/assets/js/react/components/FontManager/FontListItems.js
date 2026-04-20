/* Dependencies */
import React, { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import { useSelector, useDispatch } from 'react-redux';
/* Redux actions */
import {
	clearAddFontMsg as clearAddFontMsgAction,
	deleteFont as deleteFontAction,
	selectFont as selectFontAction,
	moveSelectedFontToTop as moveSelectedFontToTopAction,
} from '../../actions/fontManager';
/* Components */
import FontListIcon from './FontListIcon';
import Spinner from '../Spinner';
/* Utilities */
import { toggleUpdateFont } from '../../utilities/FontManager/toggleUpdateFont';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * FontListItems component
 *
 * @param {Object} root0
 * @param {*}      root0.id
 * @param {*}      root0.navigate
 * @since 6.0
 */
const FontListItems = ({ id, navigate }) => {
	const dispatch = useDispatch();
	const loading = useSelector((s) => s.fontManager.deleteFontLoading);
	const fontList = useSelector((s) => s.fontManager.fontList);
	const searchResult = useSelector((s) => s.fontManager.searchResult);
	const selectedFont = useSelector((s) => s.fontManager.selectedFont);
	const msg = useSelector((s) => s.fontManager.msg);

	const [disableSelectFontName, setDisableSelectFontName] = useState(false);
	const [deleteId, setDeleteId] = useState('');
	/* One-shot flag: only move selected font to top once */
	const moveSelectedFontToTopRef = useRef(true);

	/* Track previous values to replicate componentDidUpdate prevProps comparisons */
	const prevLoadingRef = useRef(loading);
	const prevFontListRef = useRef(fontList);
	const prevSelectedFontRef = useRef(selectedFont);

	/* componentDidMount: disable select fields + optionally move selected font to top */
	useEffect(() => {
		const tabLocation = window.location.search.substr(
			window.location.search.lastIndexOf('=') + 1
		);

		if (tabLocation === 'tools') {
			setDisableSelectFontName(true);
		} else {
			let selectBoxValue;

			if (tabLocation !== 'PDF' && tabLocation !== 'general') {
				selectBoxValue = document.querySelector(
					'#gfpdf_settings\\[font\\]'
				).value;
			} else {
				selectBoxValue = document.querySelector(
					'#gfpdf_settings\\[default_font\\]'
				).value;
			}

			const fontExists =
				fontList && fontList.filter((f) => f.id === selectBoxValue)[0];
			dispatch(selectFontAction(fontExists ? selectBoxValue : ''));
		}

		if (selectedFont) {
			moveSelectedFontToTopRef.current = false;
			dispatch(moveSelectedFontToTopAction(selectedFont));
		}
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* componentDidUpdate: reset delete loading state + toggle update font panel on deletion */
	useEffect(() => {
		const prevLoading = prevLoadingRef.current;
		const prevFontList = prevFontListRef.current;
		prevLoadingRef.current = loading;
		prevFontListRef.current = fontList;

		if (prevLoading !== loading && !loading) {
			setDeleteId('');
		}

		const updateFontVisible = document.querySelector('.update-font.show');
		if (
			prevLoading !== loading &&
			prevFontList !== fontList &&
			updateFontVisible
		) {
			toggleUpdateFont(navigate);
		}
	}, [loading, fontList, navigate]);

	/* componentDidUpdate: move selected font to top when it changes from empty */
	useEffect(() => {
		const prevSelectedFont = prevSelectedFontRef.current;
		prevSelectedFontRef.current = selectedFont;

		if (
			prevSelectedFont === '' &&
			selectedFont &&
			!id &&
			moveSelectedFontToTopRef.current
		) {
			moveSelectedFontToTopRef.current = false;
			dispatch(moveSelectedFontToTopAction(selectedFont));
		}
	}, [selectedFont, id, dispatch]);

	const handleFontClick = (fontId) => {
		const { success, error } = msg;

		if ((success && success.addFont) || (error && error.addFont)) {
			dispatch(clearAddFontMsgAction());
		}

		if (id === fontId) {
			return toggleUpdateFont(navigate);
		}

		toggleUpdateFont(navigate, fontId);
	};

	const handleFontClickKeypress = (e, fontId) => {
		if (e.key === 'Enter' || e.key === ' ') {
			handleFontClick(fontId);
		}
	};

	const handleDeleteFont = (e, fontId) => {
		e.stopPropagation();
		setDeleteId(fontId);

		if (window.confirm(GFPDF.fontManagerDeleteFontConfirmation)) {
			dispatch(deleteFontAction(fontId));
		}
	};

	const handleDeleteFontKeypress = (e, fontId) => {
		if (e.key === 'Enter' || e.key === ' ') {
			handleDeleteFont(e, fontId);
		}
	};

	const handleSelectFont = (e) => {
		dispatch(selectFontAction(e.target.value));

		const installedFonts = document.querySelectorAll('.select-font-name');
		installedFonts.forEach((item) => {
			item.checked = item.value === e.target.value;
		});
	};

	const handleSelectFontKeypress = (e) => {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			e.stopPropagation();
			handleSelectFont(e);
		}
	};

	const updateFontVisible = document.querySelector('.update-font.show');
	const list = !searchResult ? fontList : searchResult;
	const tabIndex = !updateFontVisible ? '0' : '-1';

	return (
		<div
			data-test="component-FontListItems"
			className="font-list-items"
			role="listbox"
			aria-label={GFPDF.fontListInstalledFonts}
			aria-live="polite"
		>
			{list &&
				list.map((font) => {
					return (
						<div
							key={font.id}
							className={
								'font-list-item' +
								(font.id === id ? ' active' : '')
							}
							onClick={() => handleFontClick(font.id)}
							onKeyDown={(e) =>
								handleFontClickKeypress(e, font.id)
							}
							tabIndex={tabIndex}
							role="option"
						>
							<span className="font-name">
								{!disableSelectFontName && (
									<input
										type="radio"
										className="select-font-name"
										name={'select-font-name-' + font.id}
										value={font.id}
										onChange={(e) => handleSelectFont(e)}
										onClick={(e) => e.stopPropagation()}
										onKeyDown={(e) =>
											handleSelectFontKeypress(e)
										}
										checked={font.id === selectedFont}
										aria-label={
											GFPDF.fontManagerSelectFontAriaLabel +
											': ' +
											font.font_name
										}
										tabIndex={tabIndex}
									/>
								)}
								{font.font_name}
							</span>

							<FontListIcon font={font.regular} />
							<FontListIcon font={font.italics} />
							<FontListIcon font={font.bold} />
							<FontListIcon font={font.bolditalics} />

							{loading && deleteId === font.id ? (
								<Spinner style="delete-font" />
							) : (
								<span
									role="button"
									className="dashicons dashicons-trash"
									onClick={(e) =>
										handleDeleteFont(e, font.id)
									}
									onKeyDown={(e) =>
										handleDeleteFontKeypress(e, font.id)
									}
									tabIndex={tabIndex}
								/>
							)}
						</div>
					);
				})}
		</div>
	);
};

FontListItems.propTypes = {
	id: PropTypes.string,
	navigate: PropTypes.func.isRequired,
};

export default FontListItems;
