/* Dependencies */
import React from 'react';
import Dropzone from 'react-dropzone';
/* Components */
import FontVariantLabel from './FontVariantLabel';
/* Types */
import { FontManagerMsg } from '../../types';
import { FontStyles } from './InitialAddUpdateState';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	state: string;
	fontStyles: FontStyles;
	validateRegular: boolean;
	onHandleUpload: (key: string, file: File, state: string) => void;
	onHandleDeleteFontStyle: (
		e: React.MouseEvent,
		key: string,
		state: string
	) => void;
	msg: FontManagerMsg;
	tabIndex: string;
}

export const FontVariant = ({
	state,
	fontStyles,
	validateRegular,
	onHandleUpload,
	onHandleDeleteFontStyle,
	msg: { error },
	tabIndex,
}: Props) => (
	<div data-test="component-FontVariant" id="gfpdf-font-files-setting">
		{(
			Object.entries(fontStyles) as [keyof FontStyles, string | File][]
		).map(([key, font]) => {
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
				? (font as string).substr((font as string).lastIndexOf('/') + 1)
				: (font as File).name;
			const fontFileMissing =
				error &&
				typeof error.addFont === 'object' &&
				(error.addFont as Record<string, string>)[key];
			const regularFieldValidation =
				key === 'regular' &&
				!validateRegular &&
				fontStyles.regular === '';
			const dropZoneActive = font ? ' active' : '';
			const dropZoneError = fontFileMissing ? ' error' : '';
			const dropZoneRequiredRegular = regularFieldValidation
				? ' required'
				: '';
			const dropZoneClassEnhancement =
				dropZoneActive + dropZoneError + dropZoneRequiredRegular;
			const dropZoneIcon = font ? 'trash' : 'plus';
			const displayRequiredText = font ? 'true' : 'false';

			return (
				<Dropzone
					key={key}
					accept={{ 'font/ttf': ['.ttf'] }}
					onDrop={(acceptedFiles) =>
						onHandleUpload(key, acceptedFiles[0], state)
					}
					multiple={false}
				>
					{({ getRootProps, getInputProps }) => (
						<a
							className={'drop-zone' + dropZoneClassEnhancement}
							{...getRootProps()}
							tabIndex={parseInt(tabIndex, 10)}
						>
							{font ? (
								<input
									data-test="component-FontVariant-delete"
									id={id}
									aria-labelledby={ariaLabelledby}
									aria-describedby={ariaDescribedby}
									{...getInputProps({
										onClick: (e) =>
											onHandleDeleteFontStyle(
												e as unknown as React.MouseEvent,
												key,
												state
											),
									})}
								/>
							) : (
								<input
									data-test="component-FontVariant-add"
									id={id}
									aria-labelledby={ariaLabelledby}
									aria-describedby={ariaDescribedby}
									{...getInputProps()}
								/>
							)}

							<span className="gfpdf-font-filename">
								{regularFieldValidation && (
									<span className="required">
										{
											GFPDF.fontManagerFontFileRequiredRegular
										}
									</span>
								)}
								{!fontFileMissing ? fontName : fontFileMissing}
							</span>

							<span
								className={
									'dashicons dashicons-' + dropZoneIcon
								}
							/>

							<FontVariantLabel
								label={key}
								font={displayRequiredText}
							/>
						</a>
					)}
				</Dropzone>
			);
		})}
	</div>
);

export default FontVariant;
