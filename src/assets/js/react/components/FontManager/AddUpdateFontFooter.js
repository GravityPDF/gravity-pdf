/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { useSelector, useDispatch } from 'react-redux';
import { sprintf } from 'sprintf-js';
/* Components */
import Spinner from '../Spinner';
/* Redux actions */
import {
	selectFont as selectFontAction,
	deleteFont as deleteFontAction,
} from '../../actions/fontManager';
import TemplateTooltip from './TemplateTooltip';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display footer of add font panel UI
 *
 * @param {Object} root0
 * @param {*}      root0.state
 * @param {*}      root0.id
 * @param {*}      root0.type
 * @param {*}      root0.disabled
 * @param {*}      root0.onHandleCancelEditFont
 * @param {*}      root0.onHandleCancelEditFontKeypress
 * @param {*}      root0.msg
 * @param {*}      root0.loading
 * @param {*}      root0.tabIndex
 * @since 6.0
 */
const AddUpdateFontFooter = ({
	state,
	id,
	type,
	disabled,
	onHandleCancelEditFont,
	onHandleCancelEditFontKeypress,
	msg,
	loading,
	tabIndex,
}) => {
	const dispatch = useDispatch();
	const selectedFont = useSelector((s) => s.fontManager.selectedFont);

	const handleSelectFont = (fontId) => {
		dispatch(selectFontAction(fontId === selectedFont ? '' : fontId));
	};

	const handleSelectFontKeypress = (e, fontId) => {
		if (e.key === 'Enter' || e.key === ' ') {
			dispatch(selectFontAction(fontId === selectedFont ? '' : fontId));
		}
	};

	const handleDeleteFont = (fontId) => {
		if (window.confirm(GFPDF.fontManagerDeleteFontConfirmation)) {
			dispatch(deleteFontAction(fontId));
		}
	};

	const handleDeleteFontKeypress = (e, fontId) => {
		if (e.key === 'Enter' || e.key === ' ') {
			if (window.confirm(GFPDF.fontManagerDeleteFontConfirmation)) {
				dispatch(deleteFontAction(fontId));
			}
		}
	};

	const { success, error } = msg;
	const cancelButton = document.querySelector('.footer button.cancel');
	const errorFontList = error && error.fontList;
	const successAddFont = success && success.addFont;
	const showSuccessAddFont =
		(successAddFont && errorFontList) || (successAddFont && !state);
	const errorAddFont = error && error.addFont && error.addFont;
	const errorFontValidation =
		errorAddFont && error.fontValidationError && error.fontValidationError;
	const selectedBoxStyle =
		id !== '' && id === selectedFont ? ' checked' : ' uncheck';
	const displayInvalidFileErrorMessage = errorAddFont && errorFontValidation;
	const displayGenericErrorMessage = errorAddFont && !errorFontValidation;

	return (
		<footer
			data-test="component-AddFontFooter"
			className={'footer' + (cancelButton ? ' cancel' : '')}
		>
			<div className="buttons-icons-container">
				<div>
					{type === 'update' && (
						<button
							className="button gfpdf-button primary cancel"
							onClick={onHandleCancelEditFont}
							onKeyDown={onHandleCancelEditFontKeypress}
							type="button"
							tabIndex={tabIndex}
							aria-label={GFPDF.cancel}
						>
							{GFPDF.fontManagerCancelButtonText}
						</button>
					)}

					<button
						className="button gfpdf-button primary"
						tabIndex={tabIndex}
						disabled={disabled}
						aria-label={
							type === 'update'
								? GFPDF.fontManagerUpdateFontAriaLabel
								: GFPDF.fontManagerAddFontAriaLabel
						}
					>
						{type === 'update'
							? GFPDF.fontManagerUpdateTitle + ' →'
							: GFPDF.fontManagerAddTitle + ' →'}
					</button>

					{loading && <Spinner style="add-update-font" />}
				</div>

				<div className="select-delete-icons-container">
					{id && (
						<button
							className={
								'dashicons dashicons-yes' + selectedBoxStyle
							}
							onClick={() => handleSelectFont(id)}
							onKeyDown={(e) => handleSelectFontKeypress(e, id)}
							type="button"
							tabIndex={tabIndex}
							aria-label={GFPDF.fontManagerSelectFontAriaLabel}
						/>
					)}

					{id && (
						<button
							className="dashicons dashicons-trash"
							onClick={() => handleDeleteFont(id)}
							onKeyDown={(e) => handleDeleteFontKeypress(e, id)}
							type="button"
							tabIndex={tabIndex}
							aria-label={GFPDF.fontManagerDeleteFontAriaLabel}
						/>
					)}
				</div>
			</div>

			{showSuccessAddFont && (
				<span
					className="msg success"
					dangerouslySetInnerHTML={{ __html: success.addFont }}
				/>
			)}

			{displayInvalidFileErrorMessage && (
				<span
					className="msg error"
					dangerouslySetInnerHTML={{ __html: errorFontValidation }}
				/>
			)}

			{displayGenericErrorMessage && (
				<span
					className="msg error"
					dangerouslySetInnerHTML={{
						__html:
							typeof error.addFont === 'object'
								? // eslint-disable-next-line @wordpress/valid-sprintf
									sprintf(
										GFPDF.fontFileMissing,
										'<strong>',
										'</strong>'
									)
								: error.addFont,
					}}
				/>
			)}

			{id && <TemplateTooltip id={id} />}
		</footer>
	);
};

AddUpdateFontFooter.propTypes = {
	state: PropTypes.string,
	id: PropTypes.string,
	type: PropTypes.string,
	disabled: PropTypes.bool,
	onHandleCancelEditFont: PropTypes.func,
	onHandleCancelEditFontKeypress: PropTypes.func,
	msg: PropTypes.object.isRequired,
	loading: PropTypes.bool.isRequired,
	tabIndex: PropTypes.string.isRequired,
};

export default AddUpdateFontFooter;
