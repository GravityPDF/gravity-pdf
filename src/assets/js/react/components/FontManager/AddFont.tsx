/* Dependencies */
import React from 'react';
/* Components */
import FontVariant from './FontVariant';
import AddUpdateFontFooter from './AddUpdateFontFooter';
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
	label: string;
	onHandleInputChange: (
		e: React.ChangeEvent<HTMLInputElement>,
		state: 'addFont' | 'updateFont'
	) => void;
	onHandleUpload: (key: string, file: File, state: string) => void;
	onHandleDeleteFontStyle: (
		e: React.MouseEvent,
		key: string,
		state: string
	) => void;
	onHandleSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
	fontStyles: FontStyles;
	validateLabel: boolean;
	validateRegular: boolean;
	msg: FontManagerMsg;
	loading: boolean;
	tabIndexFontName: string;
	tabIndexFontFiles: string;
	tabIndexFooterButtons: string;
}

export const AddFont = ({
	label,
	onHandleInputChange,
	onHandleUpload,
	onHandleDeleteFontStyle,
	onHandleSubmit,
	fontStyles,
	validateLabel,
	validateRegular,
	msg,
	loading,
	tabIndexFontName,
	tabIndexFontFiles,
	tabIndexFooterButtons,
}: Props) => {
	return (
		<div data-test="component-AddFont" className="add-font">
			<form onSubmit={onHandleSubmit}>
				<h2>{GFPDF.fontManagerAddTitle}</h2>

				<p>{GFPDF.fontManagerAddDesc}</p>

				<label
					htmlFor="gfpdf-add-font-name-input"
					aria-label={GFPDF.fontManagerFontNameLabel}
				>
					{GFPDF.fontManagerFontNameLabel}{' '}
					<span className="required">
						{GFPDF.fontManagerRequiredLabel}
					</span>
				</label>

				<p id="gfpdf-font-name-desc-add">
					{GFPDF.fontManagerFontNameDesc}
				</p>

				<input
					type="text"
					id="gfpdf-add-font-name-input"
					className={
						!validateLabel ? 'input-label-validation-error' : ''
					}
					aria-describedby="gfpdf-font-name-desc-add"
					name="label"
					value={label}
					maxLength={60}
					onChange={(e) => onHandleInputChange(e, 'addFont')}
					tabIndex={parseInt(tabIndexFontName, 10)}
				/>

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
					tabIndex={tabIndexFontFiles}
				/>

				<AddUpdateFontFooter
					type="add"
					state="addFont"
					msg={msg}
					loading={loading}
					tabIndex={tabIndexFooterButtons}
				/>
			</form>
		</div>
	);
};

export default AddFont;
