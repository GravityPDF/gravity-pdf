/* Dependencies */
import { __ } from '@wordpress/i18n';
import { DropZone, FormFileUpload } from '@wordpress/components';
import type { MouseEvent } from 'react';
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
		e: MouseEvent,
		key: string,
		state: string
	) => void;
	msg: FontManagerMsg;
	tabIndex: number;
}

const isTtf = (file: File) => file.name.toLowerCase().endsWith('.ttf');

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

			const handleFilesDrop = (files: File[]) => {
				const file = files.find(isTtf);
				if (file) {
					onHandleUpload(key, file, state);
				}
			};

			const tileContent = (
				<>
					<DropZone onFilesDrop={handleFilesDrop} />

					<span className="gfpdf-font-filename">
						{regularFieldValidation && (
							<span className="required">
								{__('Add a .ttf font file.', 'gravity-pdf')}
							</span>
						)}
						{!fontFileMissing ? fontName : fontFileMissing}
					</span>

					<span className={'dashicons dashicons-' + dropZoneIcon} />

					<FontVariantLabel label={key} font={displayRequiredText} />
				</>
			);

			if (font) {
				return (
					/* eslint-disable-next-line jsx-a11y/anchor-is-valid */
					<a
						key={key}
						className={'drop-zone' + dropZoneClassEnhancement}
						data-test="component-FontVariant-delete"
						id={id}
						tabIndex={tabIndex}
						role="button"
						aria-labelledby={ariaLabelledby}
						aria-describedby={ariaDescribedby}
						onClick={(e) => onHandleDeleteFontStyle(e, key, state)}
						onKeyDown={(e) => {
							if (e.key === 'Enter' || e.key === ' ') {
								e.preventDefault();
								onHandleDeleteFontStyle(
									e as unknown as MouseEvent,
									key,
									state
								);
							}
						}}
					>
						{tileContent}
					</a>
				);
			}

			return (
				<FormFileUpload
					key={key}
					accept=".ttf"
					multiple={false}
					onChange={(e) => {
						const file = e.currentTarget.files?.[0];
						if (file) {
							onHandleUpload(key, file, state);
						}
					}}
					render={({ openFileDialog }) => (
						/* eslint-disable-next-line jsx-a11y/anchor-is-valid */
						<a
							className={'drop-zone' + dropZoneClassEnhancement}
							data-test="component-FontVariant-add"
							id={id}
							tabIndex={tabIndex}
							role="button"
							aria-labelledby={ariaLabelledby}
							aria-describedby={ariaDescribedby}
							onClick={(e) => {
								e.preventDefault();
								openFileDialog();
							}}
							onKeyDown={(e) => {
								if (e.key === 'Enter' || e.key === ' ') {
									e.preventDefault();
									openFileDialog();
								}
							}}
						>
							{tileContent}
						</a>
					)}
				/>
			);
		})}
	</div>
);

export default FontVariant;
