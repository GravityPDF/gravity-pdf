/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { sprintf } from 'sprintf-js';
/* Components */
import FontVariant from './FontVariant';
import AddUpdateFontFooter from './AddUpdateFontFooter';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display update font panel UI
 *
 * @param { Object }   props
 * @param { string }   props.id
 * @param { string }   props.label
 * @param { Function } props.onHandleInputChange
 * @param { Function } props.onHandleUpload
 * @param { Function } props.onHandleDeleteFontStyle
 * @param { Function } props.onHandleCancelEditFont
 * @param { Function } props.onHandleCancelEditFontKeypress
 * @param { Function } props.onHandleSubmit
 * @param { Object }   props.fontStyles
 * @param { boolean }  props.validateLabel
 * @param { boolean }  props.validateRegular
 * @param { boolean }  props.disableUpdateButton
 * @param { Object }   props.msg
 * @param { boolean }  props.loading
 * @param { string }   props.tabIndexFontName
 * @param { string }   props.tabIndexFontFiles
 * @param { string }   props.tabIndexFooterButtons
 *
 * @return { JSX } UpdateFont Component
 *
 * @since 6.0
 */
export const UpdateFont = ({
	id,
	label,
	onHandleInputChange,
	onHandleUpload,
	onHandleDeleteFontStyle,
	onHandleCancelEditFont,
	onHandleCancelEditFontKeypress,
	onHandleSubmit,
	fontStyles,
	validateLabel,
	validateRegular,
	disableUpdateButton,
	msg,
	loading,
	tabIndexFontName,
	tabIndexFontFiles,
	tabIndexFooterButtons,
}) => {
	// %s is found inside fontManagerFontNameLabel which is not detected by eslint
	// eslint-disable-next-line @wordpress/valid-sprintf
	const fontNameLabel = sprintf(
		GFPDF.fontManagerFontNameLabel,
		"<span class='required'>",
		'</span>'
	);

	return (
		<div data-test="component-UpdateFont" className="update-font">
			<form onSubmit={onHandleSubmit}>
				<h2>{GFPDF.fontManagerUpdateTitle}</h2>

				<p>{GFPDF.fontManagerUpdateDesc}</p>

				<label
					htmlFor="gfpdf-font-name-input"
					dangerouslySetInnerHTML={{ __html: fontNameLabel }}
					aria-label={fontNameLabel}
				/>

				<p id="gfpdf-font-name-desc-update">
					{GFPDF.fontManagerFontNameDesc}
				</p>

				<input
					type="text"
					id="gfpdf-update-font-name-input"
					className={
						!validateLabel ? 'input-label-validation-error' : ''
					}
					aria-describedby="gfpdf-font-name-desc-update"
					name="label"
					value={label}
					maxLength="60"
					onChange={(e) => onHandleInputChange(e, 'updateFont')}
					tabIndex={tabIndexFontName}
				/>

				<div aria-live="polite">
					{!validateLabel && (
						<span className="required" role="alert">
							<em>{GFPDF.fontManagerFontNameValidationError}</em>
						</span>
					)}
				</div>

				<label
					id="gfpdf-font-files-label-update"
					htmlFor="gfpdf-font-files-label-update"
					aria-labelledby="gfpdf-font-files-description-update"
				>
					{GFPDF.fontManagerFontFilesLabel}
				</label>

				<p id="gfpdf-font-files-description-update">
					{GFPDF.fontManagerFontFilesDesc}
				</p>

				<FontVariant
					state="updateFont"
					fontStyles={fontStyles}
					validateRegular={validateRegular}
					onHandleUpload={onHandleUpload}
					onHandleDeleteFontStyle={onHandleDeleteFontStyle}
					msg={msg}
					tabIndex={tabIndexFontFiles}
				/>

				<AddUpdateFontFooter
					id={id}
					label={label}
					disabled={disableUpdateButton}
					onHandleCancelEditFont={onHandleCancelEditFont}
					onHandleCancelEditFontKeypress={
						onHandleCancelEditFontKeypress
					}
					msg={msg}
					loading={loading}
					tabIndex={tabIndexFooterButtons}
				/>
			</form>
		</div>
	);
};

/**
 * PropTypes
 *
 * @since 6.0
 */
UpdateFont.propTypes = {
	id: PropTypes.string,
	label: PropTypes.string.isRequired,
	onHandleInputChange: PropTypes.func.isRequired,
	onHandleUpload: PropTypes.func.isRequired,
	onHandleDeleteFontStyle: PropTypes.func.isRequired,
	onHandleCancelEditFont: PropTypes.func.isRequired,
	onHandleCancelEditFontKeypress: PropTypes.func.isRequired,
	onHandleSubmit: PropTypes.func.isRequired,
	validateLabel: PropTypes.bool.isRequired,
	validateRegular: PropTypes.bool.isRequired,
	disableUpdateButton: PropTypes.bool.isRequired,
	fontStyles: PropTypes.object.isRequired,
	msg: PropTypes.object.isRequired,
	loading: PropTypes.bool.isRequired,
	tabIndexFontName: PropTypes.string.isRequired,
	tabIndexFontFiles: PropTypes.string.isRequired,
	tabIndexFooterButtons: PropTypes.string.isRequired,
};

export default UpdateFont;
