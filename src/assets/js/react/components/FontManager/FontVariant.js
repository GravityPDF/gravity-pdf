/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import Dropzone from 'react-dropzone';
import { sprintf } from 'sprintf-js';
/* Components */
import FontVariantLabel from './FontVariantLabel';
import Icon from './Icon';
/* Utilities */
import { getStyleLabel } from '../../utilities/FontManager/fontManagerReducer';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display font variant drop box UI
 *
 * @param { Object }   props
 * @param { Object }   props.state
 * @param { Object }   props.fontStyles
 * @param { boolean }  props.validateRegular
 * @param { Function } props.onHandleUpload
 * @param { Function } props.onHandleDeleteFontStyle
 * @param { Object }   props.msg
 * @param { Object }   props.msg.error
 *
 * @since 6.0
 */
export const FontVariant = ({
	state,
	fontStyles,
	validateRegular,
	onHandleUpload,
	onHandleDeleteFontStyle,
	msg: { error },
}) => (
	<div data-test="component-FontVariant" id="gfpdf-font-files-setting">
		{Object.entries(fontStyles).map(([key, font]) => {
			const id = `gfpdf-font-variant-${key}-${state}`;
			const ariaLabelledby =
				'gfpdf-font-files-label-' +
				(state === 'addFont' ? 'add' : 'update');
			const ariaDescribedby =
				'gfpdf-font-files-description-' +
				(state === 'addFont' ? 'add' : 'update');

			const currentUploadFontName =
				font !== '' && typeof font !== 'object';
			const fontName = currentUploadFontName
				? font.substr(font.lastIndexOf('/') + 1)
				: font.name;
			const fontFileMissing =
				error &&
				typeof error.addFont === 'object' &&
				error.addFont[key];
			const regularFieldValidation =
				key === 'regular' &&
				!validateRegular &&
				fontStyles.regular === '';
			const dropZoneError = fontFileMissing ? ' error' : '';
			const dropZoneRequiredRegular = regularFieldValidation
				? ' required'
				: '';
			const styleLabel = getStyleLabel(key);

			return (
				<Dropzone
					data-test="component-Dropzone"
					key={key}
					accept={{ 'font/ttf': ['.ttf'] }}
					onDrop={(acceptedFiles) =>
						onHandleUpload(key, acceptedFiles[0], state)
					}
					multiple={false}
					noClick
					noKeyboard
				>
					{({ getRootProps, getInputProps, open, isDragActive }) => (
						<div
							data-variant={key}
							className={
								'drop-zone' +
								(font ? ' active' : ' missing') +
								(isDragActive ? ' drag-over' : '') +
								dropZoneError +
								dropZoneRequiredRegular
							}
							{...getRootProps()}
						>
							<input
								data-test="component-FontVariant-input"
								id={id}
								aria-labelledby={ariaLabelledby}
								aria-describedby={ariaDescribedby}
								{...getInputProps()}
							/>

							<FontVariantLabel label={key} />

							<div
								className={
									'gfpdf-font-filename' +
									(fontName ? '' : ' empty')
								}
							>
								{isDragActive && GFPDF.fontManagerDropFile}
								{!isDragActive && regularFieldValidation && (
									<span className="required">
										{
											GFPDF.fontManagerFontFileRequiredRegular
										}
									</span>
								)}
								{!isDragActive &&
									!regularFieldValidation &&
									(fontFileMissing ||
										fontName ||
										GFPDF.fontManagerNoFile)}
							</div>

							<div className="font-variant-actions">
								<button
									type="button"
									className="button"
									aria-label={
										(font
											? GFPDF.fontManagerReplace
											: GFPDF.fontManagerUpload) +
										': ' +
										styleLabel
									}
									onClick={open}
								>
									{font
										? GFPDF.fontManagerReplace
										: GFPDF.fontManagerUpload}
								</button>

								{font && key !== 'regular' && (
									<button
										type="button"
										data-test="component-FontVariant-delete"
										className="button font-variant-delete"
										// %s is found inside fontManagerDeleteStyle
										// eslint-disable-next-line @wordpress/valid-sprintf
										aria-label={sprintf(
											GFPDF.fontManagerDeleteStyle,
											styleLabel
										)}
										onClick={(e) =>
											onHandleDeleteFontStyle(
												e,
												key,
												state
											)
										}
									>
										<Icon name="trash" />
									</button>
								)}
							</div>
						</div>
					)}
				</Dropzone>
			);
		})}
	</div>
);

/**
 * PropTypes
 *
 * @since 6.0
 */
FontVariant.propTypes = {
	state: PropTypes.string.isRequired,
	fontStyles: PropTypes.object.isRequired,
	validateRegular: PropTypes.bool.isRequired,
	onHandleUpload: PropTypes.func.isRequired,
	onHandleDeleteFontStyle: PropTypes.func.isRequired,
	msg: PropTypes.object.isRequired,
};

export default FontVariant;
