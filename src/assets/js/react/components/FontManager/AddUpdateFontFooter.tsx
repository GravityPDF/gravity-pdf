/* Dependencies */
import { createInterpolateElement, useState } from '@wordpress/element';
import type { KeyboardEvent } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
/* Components */
import Spinner from '../Spinner';
import FontUsageSnippet from './FontUsageSnippet';
/* Store */
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';
/* Types */
import { FontManagerMsg } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	id?: string;
	type: 'add' | 'update';
	disabled?: boolean;
	onHandleCancelEditFont?: () => void;
	onHandleCancelEditFontKeypress?: (e: KeyboardEvent) => void;
	msg: FontManagerMsg;
	loading: boolean;
	tabIndex: number;
}

const AddUpdateFontFooter = ({
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
		(select) => select(fontManagerStore).getSelectedFont(),
		[]
	);

	const [confirmingDelete, setConfirmingDelete] = useState(false);

	const handleSelectFont = (fontId: string) => {
		selectFont(fontId === selectedFont ? '' : fontId);
	};

	const handleSelectFontKeypress = (e: KeyboardEvent, fontId: string) => {
		if (e.key === 'Enter' || e.key === ' ') {
			selectFont(fontId === selectedFont ? '' : fontId);
		}
	};

	const requestDeleteFont = () => setConfirmingDelete(true);

	const requestDeleteFontKeypress = (e: KeyboardEvent) => {
		if (e.key === 'Enter' || e.key === ' ') {
			setConfirmingDelete(true);
		}
	};

	const confirmDeleteFont = () => {
		if (id) {
			deleteFont(id);
		}
		setConfirmingDelete(false);
	};

	const cancelDeleteFont = () => setConfirmingDelete(false);

	const { success, error } = msg;
	const errorFontList = error && error.fontList;
	const successAddFont = success && success.addFont;
	const showSuccessAddFont =
		(successAddFont && errorFontList) || (successAddFont && type === 'add');
	const errorAddFont = error && error.addFont;
	const errorFontValidation = errorAddFont && error?.fontValidationError;
	const selectedBoxStyle =
		id !== '' && id === selectedFont ? ' checked' : ' uncheck';
	const displayInvalidFileErrorMessage = errorAddFont && errorFontValidation;
	const displayGenericErrorMessage = errorAddFont && !errorFontValidation;

	return (
		<footer data-test="component-AddFontFooter" className="footer">
			<div className="buttons-icons-container">
				<div>
					{type === 'update' && (
						<button
							className="button gfpdf-button primary cancel"
							onClick={onHandleCancelEditFont}
							onKeyDown={onHandleCancelEditFontKeypress}
							type="button"
							tabIndex={tabIndex}
							aria-label={__('Cancel', 'gravity-pdf')}
						>
							{__('← Cancel', 'gravity-pdf')}
						</button>
					)}

					<button
						className="button gfpdf-button primary"
						tabIndex={tabIndex}
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
							tabIndex={tabIndex}
							aria-label={__('Select font', 'gravity-pdf')}
						/>
					)}

					{id && (
						<button
							className="dashicons dashicons-trash"
							onClick={requestDeleteFont}
							onKeyDown={requestDeleteFontKeypress}
							type="button"
							tabIndex={tabIndex}
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
					{createInterpolateElement(errorFontValidation as string, {
						strong: <strong />,
					})}
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
						: createInterpolateElement(error?.addFont as string, {
								strong: <strong />,
							})}
				</span>
			)}

			{id && <FontUsageSnippet id={id} />}

			<ConfirmDialog
				isOpen={confirmingDelete}
				onConfirm={confirmDeleteFont}
				onCancel={cancelDeleteFont}
			>
				{__(
					'Are you sure you want to delete this font?',
					'gravity-pdf'
				)}
			</ConfirmDialog>
		</footer>
	);
};

export default AddUpdateFontFooter;
