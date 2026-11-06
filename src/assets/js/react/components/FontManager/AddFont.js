/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
/* Components */
import FontVariant from './FontVariant';
import AddUpdateFontFooter from './AddUpdateFontFooter';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display add font panel UI
 *
 * @param { Object }   props
 * @param { string }   props.label
 * @param { Function } props.onHandleInputChange
 * @param { Function } props.onHandleUpload
 * @param { Function } props.onHandleDeleteFontStyle
 * @param { Function } props.onHandleSubmit
 * @param { Object }   props.fontStyles
 * @param { boolean }  props.validateLabel
 * @param { boolean }  props.validateRegular
 * @param { Object }   props.msg
 * @param { boolean }  props.loading
 *
 * @return {JSX.Element} AddFont component
 *
 * @since 6.0
 */
export const AddFont = ({
	label,
	onHandleInputChange,
	onHandleUpload,
	onHandleDeleteFontStyle,
	onHandleSubmit,
	onHandleCancelAddFont,
	fontStyles,
	validateLabel,
	validateRegular,
	msg,
	loading,
}) => {
	return (
		<div data-test="component-AddFont" className="add-font">
			<form id="gfpdf-add-font-form" onSubmit={onHandleSubmit}>
				<h2>{GFPDF.fontManagerAddTitle}</h2>

				<label
					htmlFor="gfpdf-add-font-name-input"
					aria-label={GFPDF.fontManagerFontNameLabel}
				>
					{GFPDF.fontManagerFontNameLabel}{' '}
					<span className="required">
						{GFPDF.fontManagerRequiredLabel}
					</span>
				</label>

				<input
					type="text"
					id="gfpdf-add-font-name-input"
					className={
						!validateLabel ? 'input-label-validation-error' : ''
					}
					aria-describedby="gfpdf-font-name-desc-add"
					name="label"
					placeholder={GFPDF.fontManagerFontNamePlaceholder}
					value={label}
					maxLength="60"
					onChange={(e) => onHandleInputChange(e, 'addFont')}
				/>

				<p id="gfpdf-font-name-desc-add" className="font-field-help">
					{GFPDF.fontManagerFontNameDesc}
				</p>

				<div aria-live="polite">
					{!validateLabel && (
						<span className="required" role="alert">
							<em>{GFPDF.fontManagerFontNameValidationError}</em>
						</span>
					)}
				</div>
				{/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
				<label id="gfpdf-font-files-label-add">
					{GFPDF.fontManagerFontFilesLabel}
				</label>

				<p id="gfpdf-font-files-description-add">
					{GFPDF.fontManagerFontFilesDesc}
				</p>

				<FontVariant
					state="addFont"
					fontStyles={fontStyles}
					validateRegular={validateRegular}
					onHandleUpload={onHandleUpload}
					onHandleDeleteFontStyle={onHandleDeleteFontStyle}
					msg={msg}
				/>
			</form>

			<AddUpdateFontFooter
				type="add"
				state="addFont"
				formId="gfpdf-add-font-form"
				onHandleCancelEditFont={onHandleCancelAddFont}
				msg={msg}
				loading={loading}
			/>
		</div>
	);
};

/**
 * PropTypes
 *
 * @since 6.0
 */
AddFont.propTypes = {
	label: PropTypes.string.isRequired,
	onHandleInputChange: PropTypes.func.isRequired,
	onHandleUpload: PropTypes.func.isRequired,
	onHandleDeleteFontStyle: PropTypes.func.isRequired,
	onHandleSubmit: PropTypes.func.isRequired,
	onHandleCancelAddFont: PropTypes.func,
	validateLabel: PropTypes.bool.isRequired,
	validateRegular: PropTypes.bool.isRequired,
	fontStyles: PropTypes.object.isRequired,
	msg: PropTypes.object.isRequired,
	loading: PropTypes.bool.isRequired,
};

export default AddFont;
