/* Dependencies */
import type { ChangeEvent, MouseEvent, KeyboardEvent, FormEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
/* Store */
import { fontManagerStore } from '../../store/fontManagerStore';
/* Components */
import FontVariant from './FontVariant';
import AddUpdateFontFooter from './AddUpdateFontFooter';

/**
 * Unified Add/Update font form. Reads its own state from fontManagerStore.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

type Mode = 'add' | 'update';

interface Props {
	mode: Mode;
	onHandleInputChange: (
		e: ChangeEvent<HTMLInputElement>,
		state: 'addFont' | 'updateFont'
	) => void;
	onHandleUpload: (key: string, file: File, state: string) => void;
	onHandleDeleteFontStyle: (
		e: MouseEvent,
		key: string,
		state: string
	) => void;
	onHandleCancelEditFont?: () => void;
	onHandleCancelEditFontKeypress?: (e: KeyboardEvent) => void;
	onHandleSubmit: (e: FormEvent<HTMLFormElement>) => void;
}

const FontForm = ({
	mode,
	onHandleInputChange,
	onHandleUpload,
	onHandleDeleteFontStyle,
	onHandleCancelEditFont,
	onHandleCancelEditFontKeypress,
	onHandleSubmit,
}: Props) => {
	const isUpdate = mode === 'update';
	const state: 'addFont' | 'updateFont' = isUpdate ? 'updateFont' : 'addFont';
	const idSuffix = mode;

	const formState = useSelect(
		(select) =>
			isUpdate
				? select(fontManagerStore).getUpdateFontState()
				: select(fontManagerStore).getAddFontState(),
		[isUpdate]
	);
	const msg = useSelect((select) => select(fontManagerStore).getMsg(), []);
	const loading = useSelect(
		(select) => select(fontManagerStore).getAddFontLoading(),
		[]
	);

	const { id, label, fontStyles, validateLabel, validateRegular } = formState;

	const heading = isUpdate
		? __('Update Font', 'gravity-pdf')
		: __('Add Font', 'gravity-pdf');
	const description = isUpdate
		? __(
				'Once saved, PDFs configured to use this font will have your changes applied automatically for newly-generated documents.',
				'gravity-pdf'
			)
		: __('Install new fonts for use in your PDF documents.', 'gravity-pdf');

	return (
		<div
			data-test={isUpdate ? 'component-UpdateFont' : 'component-AddFont'}
			className={isUpdate ? 'update-font' : 'add-font'}
		>
			<form
				name={isUpdate ? 'component-PDF-UpdateFont' : undefined}
				onSubmit={onHandleSubmit}
			>
				<h2>{heading}</h2>

				<p>{description}</p>

				<label
					htmlFor={`gfpdf-${idSuffix}-font-name-input`}
					aria-label={__('Font Name', 'gravity-pdf')}
				>
					{__('Font Name', 'gravity-pdf')}{' '}
					<span className="required">
						{__('(required)', 'gravity-pdf')}
					</span>
				</label>

				<p id={`gfpdf-font-name-desc-${idSuffix}`}>
					{__(
						'The font name can only contain letters, numbers and spaces.',
						'gravity-pdf'
					)}
				</p>

				<input
					type="text"
					id={`gfpdf-${idSuffix}-font-name-input`}
					className={
						!validateLabel ? 'input-label-validation-error' : ''
					}
					aria-describedby={`gfpdf-font-name-desc-${idSuffix}`}
					name="label"
					value={label}
					maxLength={60}
					onChange={(e) => onHandleInputChange(e, state)}
					tabIndex={0}
				/>

				<div aria-live="polite">
					{!validateLabel && (
						<span className="required" role="alert">
							<em>
								{__(
									'Please choose a name contains letters and/or numbers (and a space if you want it).',
									'gravity-pdf'
								)}
							</em>
						</span>
					)}
				</div>

				{/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
				<label id={`gfpdf-font-files-label-${idSuffix}`}>
					{__('Font Files', 'gravity-pdf')}
				</label>

				<p id={`gfpdf-font-files-description-${idSuffix}`}>
					{__(
						'Select or drag and drop your .ttf font file for the variants below. Only the Regular type is required.',
						'gravity-pdf'
					)}
				</p>

				<FontVariant
					state={state}
					fontStyles={fontStyles}
					validateRegular={validateRegular}
					onHandleUpload={onHandleUpload}
					onHandleDeleteFontStyle={onHandleDeleteFontStyle}
					msg={msg}
					tabIndex={0}
				/>

				{isUpdate ? (
					<AddUpdateFontFooter
						type="update"
						id={id}
						disabled={formState.disableUpdateButton}
						onHandleCancelEditFont={onHandleCancelEditFont}
						onHandleCancelEditFontKeypress={
							onHandleCancelEditFontKeypress
						}
						msg={msg}
						loading={loading}
						tabIndex={0}
					/>
				) : (
					<AddUpdateFontFooter
						type="add"
						msg={msg}
						loading={loading}
						tabIndex={0}
					/>
				)}
			</form>
		</div>
	);
};

export default FontForm;
