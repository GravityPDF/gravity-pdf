/* Dependencies */
import * as React from '@wordpress/element';
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
	id?: string;
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
	onHandleCancelEditFont: () => void;
	onHandleCancelEditFontKeypress: (e: React.KeyboardEvent) => void;
	onHandleSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
	fontStyles: FontStyles;
	validateLabel: boolean;
	validateRegular: boolean;
	disableUpdateButton: boolean;
	msg: FontManagerMsg;
	loading: boolean;
	tabIndexFontName: string;
	tabIndexFontFiles: string;
	tabIndexFooterButtons: string;
}

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
}: Props) => {
	return (
		<div data-test="component-UpdateFont" className="update-font">
			<form name="component-PDF-UpdateFont" onSubmit={onHandleSubmit}>
				<h2>{GFPDF.fontManagerUpdateTitle}</h2>

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
					maxLength={60}
					onChange={(e) => onHandleInputChange(e, 'updateFont')}
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
					tabIndex={tabIndexFontFiles}
				/>

				<AddUpdateFontFooter
					type="update"
					id={id}
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

export default UpdateFont;
