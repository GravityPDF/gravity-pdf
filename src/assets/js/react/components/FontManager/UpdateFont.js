/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
/* Components */
import FontVariant from './FontVariant';
import AddUpdateFontFooter from './AddUpdateFontFooter';
import FontPreview from './FontPreview';
import FontStatus from './FontStatus';
import FontUsage from './FontUsage';
/* Utilities */
import { getStyleCount } from '../../utilities/FontManager/fontManagerReducer';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display update font panel UI
 *
 * @param { Object }   props
 * @param { string }   props.id
 * @param { string }   props.label
 * @param { Object }   props.font
 * @param { boolean }  props.show
 * @param { Function } props.onHandleInputChange
 * @param { Function } props.onHandleUpload
 * @param { Function } props.onHandleDeleteFontStyle
 * @param { Function } props.onHandleCancelEditFont
 * @param { Function } props.onHandleSubmit
 * @param { Object }   props.fontStyles
 * @param { boolean }  props.validateLabel
 * @param { boolean }  props.validateRegular
 * @param { boolean }  props.disableUpdateButton
 * @param { Object }   props.msg
 * @param { boolean }  props.loading
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
	font,
	onHandleCancelEditFont,
	onHandleSubmit,
	fontStyles,
	validateLabel,
	validateRegular,
	disableUpdateButton,
	msg,
	loading,
}) => {
	return (
		<div
			data-test="component-UpdateFont"
			className={'update-font' + (font ? ' show' : '')}
		>
			<form
				id="gfpdf-update-font-form"
				name="component-PDF-UpdateFont"
				onSubmit={onHandleSubmit}
			>
				<div className="font-detail-header">
					<h2>{GFPDF.fontManagerUpdateTitle}</h2>
					{id && <FontStatus id={id} />}
				</div>

				{font && (
					<div className="font-eyebrow">
						<span>{getStyleCount(font)}</span>
						<span>{font.size_label}</span>
					</div>
				)}

				<p>{GFPDF.fontManagerUpdateDesc}</p>

				<label
					htmlFor="gfpdf-update-font-name-input"
					aria-label={GFPDF.fontManagerFontNameLabel}
				>
					{GFPDF.fontManagerFontNameLabel}{' '}
					<span className="required">
						{GFPDF.fontManagerRequiredLabel}
					</span>
				</label>

				<input
					type="text"
					id="gfpdf-update-font-name-input"
					className={
						!validateLabel ? 'input-label-validation-error' : ''
					}
					aria-describedby="gfpdf-font-name-desc-update"
					name="label"
					placeholder={GFPDF.fontManagerFontNamePlaceholder}
					value={label}
					maxLength="60"
					onChange={(e) => onHandleInputChange(e, 'updateFont')}
				/>

				<p id="gfpdf-font-name-desc-update" className="font-field-help">
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
				<label id="gfpdf-font-files-label-update">
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
				/>
			</form>

			{font && <FontUsage id={font.id} />}
			{font && <FontPreview font={font} />}

			<AddUpdateFontFooter
				type="update"
				id={id}
				formId="gfpdf-update-font-form"
				disabled={disableUpdateButton}
				onHandleCancelEditFont={onHandleCancelEditFont}
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
UpdateFont.propTypes = {
	id: PropTypes.string,
	label: PropTypes.string.isRequired,
	font: PropTypes.object,
	onHandleInputChange: PropTypes.func.isRequired,
	onHandleUpload: PropTypes.func.isRequired,
	onHandleDeleteFontStyle: PropTypes.func.isRequired,
	onHandleCancelEditFont: PropTypes.func.isRequired,
	onHandleSubmit: PropTypes.func.isRequired,
	validateLabel: PropTypes.bool.isRequired,
	validateRegular: PropTypes.bool.isRequired,
	disableUpdateButton: PropTypes.bool.isRequired,
	fontStyles: PropTypes.object.isRequired,
	msg: PropTypes.object.isRequired,
	loading: PropTypes.bool.isRequired,
};

export default UpdateFont;
