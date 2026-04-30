/* Dependencies */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	value: string;
	error: string;
	onChange: (value: string) => void;
}

const HELP_ID = 'gfpdf-fm-name-help';
const ERROR_ID = 'gfpdf-fm-name-error';

const FontNameField = ({ value, error, onChange }: Props) => {
	const helpText = __('Letters, numbers, and spaces only.', 'gravity-pdf');

	return (
		<div
			data-test="component-FontNameField"
			className="gfpdf-fm-name-field"
		>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={
					<>
						{__('Font name', 'gravity-pdf')}{' '}
						<span className="gfpdf-fm-required">
							{__('(required)', 'gravity-pdf')}
						</span>
					</>
				}
				value={value}
				onChange={onChange}
				maxLength={50}
				help={error ? '' : helpText}
				className={
					error
						? 'gfpdf-fm-name-field__input has-error'
						: 'gfpdf-fm-name-field__input'
				}
				aria-describedby={error ? ERROR_ID : HELP_ID}
				aria-invalid={!!error}
				placeholder={__(
					'e.g. Helvetica Neue, Roboto Slab, Source Sans 3',
					'gravity-pdf'
				)}
			/>
			{error && (
				<div
					id={ERROR_ID}
					role="alert"
					className="gfpdf-fm-name-field__error"
				>
					{error}
				</div>
			)}
			{!error && (
				<span id={HELP_ID} className="screen-reader-text">
					{helpText}
				</span>
			)}
		</div>
	);
};

export default FontNameField;
