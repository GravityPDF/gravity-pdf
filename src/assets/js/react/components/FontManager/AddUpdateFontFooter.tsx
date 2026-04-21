/* Dependencies */
import * as React from '@wordpress/element';
import { createInterpolateElement } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
/* Components */
import Spinner from '../Spinner';
import TemplateTooltip from './TemplateTooltip';
/* Store */
import { FONT_MANAGER_STORE_NAME } from '../../store/fontManagerStore';
/* Types */
import { FontManagerMsg } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	state?: string;
	id?: string;
	type?: string;
	disabled?: boolean;
	onHandleCancelEditFont?: () => void;
	onHandleCancelEditFontKeypress?: (e: React.KeyboardEvent) => void;
	msg: FontManagerMsg;
	loading: boolean;
	tabIndex: string;
}

const AddUpdateFontFooter = ({
	state,
	id,
	type,
	disabled,
	onHandleCancelEditFont,
	onHandleCancelEditFontKeypress,
	msg,
	loading,
	tabIndex,
}: Props) => {
	const { selectFont, deleteFont } = useDispatch(FONT_MANAGER_STORE_NAME);
	const selectedFont = useSelect(
		(select) => select(FONT_MANAGER_STORE_NAME).getSelectedFont(),
		[]
	);

	const tabIndexNum = parseInt(tabIndex, 10);

	const handleSelectFont = (fontId: string) => {
		selectFont(fontId === selectedFont ? '' : fontId);
	};

	const handleSelectFontKeypress = (
		e: React.KeyboardEvent,
		fontId: string
	) => {
		if (e.key === 'Enter' || e.key === ' ') {
			selectFont(fontId === selectedFont ? '' : fontId);
		}
	};

	const handleDeleteFont = (fontId: string) => {
		if (window.confirm(__('Are you sure you want to delete this font?', 'gravity-pdf'))) {
			deleteFont(fontId);
		}
	};

	const handleDeleteFontKeypress = (
		e: React.KeyboardEvent,
		fontId: string
	) => {
		if (e.key === 'Enter' || e.key === ' ') {
			if (window.confirm(__('Are you sure you want to delete this font?', 'gravity-pdf'))) {
				deleteFont(fontId);
			}
		}
	};

	const { success, error } = msg;
	const cancelButton = document.querySelector('.footer button.cancel');
	const errorFontList = error && error.fontList;
	const successAddFont = success && success.addFont;
	const showSuccessAddFont =
		(successAddFont && errorFontList) || (successAddFont && !state);
	const errorAddFont = error && error.addFont;
	const errorFontValidation = errorAddFont && error?.fontValidationError;
	const selectedBoxStyle =
		id !== '' && id === selectedFont ? ' checked' : ' uncheck';
	const displayInvalidFileErrorMessage = errorAddFont && errorFontValidation;
	const displayGenericErrorMessage = errorAddFont && !errorFontValidation;

	return (
		<footer
			data-test="component-AddFontFooter"
			className={'footer' + (cancelButton ? ' cancel' : '')}
		>
			<div className="buttons-icons-container">
				<div>
					{type === 'update' && (
						<button
							className="button gfpdf-button primary cancel"
							onClick={onHandleCancelEditFont}
							onKeyDown={onHandleCancelEditFontKeypress}
							type="button"
							tabIndex={tabIndexNum}
							aria-label={__('Cancel', 'gravity-pdf')}
						>
							{__('← Cancel', 'gravity-pdf')}
						</button>
					)}

					<button
						className="button gfpdf-button primary"
						tabIndex={tabIndexNum}
						disabled={disabled}
						aria-label={
							type === 'update'
								? __('Update font', 'gravity-pdf')
								: __('Add font', 'gravity-pdf')
						}
					>
						{type === 'update'
							? __('Update Font', 'gravity-pdf') + ' →'
							: __('Add Font', 'gravity-pdf') + ' →'}
					</button>

					{loading && <Spinner style="add-update-font" />}
				</div>

				<div className="select-delete-icons-container">
					{id && (
						<button
							className={
								'dashicons dashicons-yes' + selectedBoxStyle
							}
							onClick={() => handleSelectFont(id)}
							onKeyDown={(e) => handleSelectFontKeypress(e, id)}
							type="button"
							tabIndex={tabIndexNum}
							aria-label={__('Select font', 'gravity-pdf')}
						/>
					)}

					{id && (
						<button
							className="dashicons dashicons-trash"
							onClick={() => handleDeleteFont(id)}
							onKeyDown={(e) => handleDeleteFontKeypress(e, id)}
							type="button"
							tabIndex={tabIndexNum}
							aria-label={__('Delete font', 'gravity-pdf')}
						/>
					)}
				</div>
			</div>

			{showSuccessAddFont && (
				<span className="msg success">
					<strong>{success!.addFont!}</strong>
				</span>
			)}

			{displayInvalidFileErrorMessage && (
				<span className="msg error">
					{createInterpolateElement(
						errorFontValidation as string,
						{ strong: <strong /> }
					)}
				</span>
			)}

			{displayGenericErrorMessage && (
				<span className="msg error">
					{typeof error?.addFont === 'object'
						? createInterpolateElement(
								__(
									'<strong>Font file(s) missing from the server.</strong> Please upload the font(s) again and then save.',
									'gravity-pdf'
								),
								{ strong: <strong /> }
							)
						: createInterpolateElement(
								error?.addFont as string,
								{ strong: <strong /> }
							)}
				</span>
			)}

			{id && <TemplateTooltip id={id} />}
		</footer>
	);
};

export default AddUpdateFontFooter;
