/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { sprintf } from 'sprintf-js';
/* Components */
import FontDeleteButton from './FontDeleteButton';
import Spinner from '../Spinner';
import TemplateTooltip from './TemplateTooltip';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display footer of add font panel UI
 *
 * @param state
 * @param id
 * @param formId
 * @param type
 * @param disabled
 * @param onHandleCancelEditFont
 * @param msg
 * @param loading
 *
 * @since 6.0
 */
export class AddUpdateFontFooter extends Component {
	/**
	 * PropTypes
	 *
	 * @since 6.0
	 */
	static propTypes = {
		state: PropTypes.string,
		id: PropTypes.string,
		formId: PropTypes.string,
		type: PropTypes.string,
		disabled: PropTypes.bool,
		onHandleCancelEditFont: PropTypes.func,
		msg: PropTypes.object.isRequired,
		loading: PropTypes.bool.isRequired,
	};

	render() {
		const {
			state,
			type,
			id,
			formId,
			disabled,
			onHandleCancelEditFont,
			msg: { success, error },
			loading,
		} = this.props;
		const errorFontList = error && error.fontList;
		const successAddFont = success && success.addFont;
		const showSuccessAddFont =
			(successAddFont && errorFontList) || (successAddFont && !state);
		const errorAddFont = error && error.addFont && error.addFont;
		const errorFontValidation =
			errorAddFont &&
			error.fontValidationError &&
			error.fontValidationError;
		// eslint can't detect %s on fontFileMissing
		// eslint-disable-next-line @wordpress/valid-sprintf
		const fontFileMissing = sprintf(
			GFPDF.fontFileMissing,
			'<strong>',
			'</strong>'
		);
		/* Display error message for uploading invalid font file */
		const displayInvalidFileErrorMessage =
			errorAddFont && errorFontValidation;
		/* Display generic error messages including missing font file */
		const displayGenericErrorMessage = errorAddFont && !errorFontValidation;

		return (
			<footer data-test="component-AddFontFooter" className="footer">
				{id && <TemplateTooltip id={id} />}

				{showSuccessAddFont && (
					<span
						className="msg success"
						dangerouslySetInnerHTML={{ __html: success.addFont }}
					/>
				)}

				{displayInvalidFileErrorMessage && (
					<span
						className="msg error"
						dangerouslySetInnerHTML={{
							__html: errorFontValidation,
						}}
					/>
				)}

				{displayGenericErrorMessage && (
					<span
						className="msg error"
						dangerouslySetInnerHTML={{
							__html:
								typeof error.addFont === 'object'
									? fontFileMissing
									: error.addFont,
						}}
					/>
				)}

				<div className="buttons-icons-container">
					{id && (
						<FontDeleteButton
							id={id}
							type="custom"
							message={GFPDF.fontManagerDeleteFontConfirmation}
						/>
					)}

					<div className="font-footer-actions">
						{loading && <Spinner style="add-update-font" />}

						<button
							className="button gfpdf-button cancel"
							onClick={onHandleCancelEditFont}
							type="button"
							aria-label={GFPDF.cancel}
						>
							{GFPDF.cancel}
						</button>

						<button
							className="button gfpdf-button primary"
							form={formId}
							disabled={disabled}
							aria-label={
								type === 'update'
									? GFPDF.fontManagerUpdateFontAriaLabel
									: GFPDF.fontManagerAddTitle
							}
						>
							{type === 'update'
								? GFPDF.fontManagerUpdateTitle + ' →'
								: GFPDF.fontManagerAddTitle + ' →'}
						</button>
					</div>
				</div>
			</footer>
		);
	}
}

export default AddUpdateFontFooter;
