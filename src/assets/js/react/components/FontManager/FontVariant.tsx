/* Dependencies */
import * as React from '@wordpress/element';
import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DropZone } from '@wordpress/components';
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

interface ItemProps {
	fontKey: keyof FontStyles;
	font: string | File;
	state: string;
	validateRegular: boolean;
	fontStyles: FontStyles;
	onHandleUpload: (key: string, file: File, state: string) => void;
	onHandleDeleteFontStyle: (
		e: React.MouseEvent,
		key: string,
		state: string
	) => void;
	error: FontManagerMsg['error'];
	tabIndex: string;
}

function FontVariantItem({
	fontKey: key,
	font,
	state,
	validateRegular,
	fontStyles,
	onHandleUpload,
	onHandleDeleteFontStyle,
	error,
	tabIndex,
}: ItemProps) {
	const fileInputRef = useRef<HTMLInputElement>(null);

	const id = `gfpdf-font-variant-${key}-${state}`;
	const ariaLabelledby =
		'gfpdf-font-files-label-' + (state === 'addFont' ? 'add' : 'update');
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
		<div style={{ position: 'relative' }}>
			<DropZone
				onFilesDrop={(files) => {
					const file = files.find((f) => f.name.endsWith('.ttf'));
					if (file) onHandleUpload(key, file, state);
				}}
			/>
			<a
				className={'drop-zone' + dropZoneClassEnhancement}
				tabIndex={parseInt(tabIndex, 10)}
				onClick={(e) => {
					if (font) {
						onHandleDeleteFontStyle(
							e as unknown as React.MouseEvent,
							key,
							state
						);
					} else {
						e.preventDefault();
						fileInputRef.current?.click();
					}
				}}
			>
				{font ? (
					<input
						data-test="component-FontVariant-delete"
						id={id}
						aria-labelledby={ariaLabelledby}
						aria-describedby={ariaDescribedby}
						type="hidden"
					/>
				) : (
					<input
						ref={fileInputRef}
						data-test="component-FontVariant-add"
						id={id}
						aria-labelledby={ariaLabelledby}
						aria-describedby={ariaDescribedby}
						type="file"
						accept=".ttf"
						style={{ display: 'none' }}
						onChange={(e) => {
							const file = e.target.files?.[0];
							if (file) onHandleUpload(key, file, state);
						}}
					/>
				)}

				<span className="gfpdf-font-filename">
					{regularFieldValidation && (
						<span className="required">
							{__('Add a .ttf font file.', 'gravity-pdf')}
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
		</div>
	);
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
		).map(([key, font]) => (
			<FontVariantItem
				key={key}
				fontKey={key}
				font={font}
				state={state}
				validateRegular={validateRegular}
				fontStyles={fontStyles}
				onHandleUpload={onHandleUpload}
				onHandleDeleteFontStyle={onHandleDeleteFontStyle}
				error={error}
				tabIndex={tabIndex}
			/>
		))}
	</div>
);

export default FontVariant;
