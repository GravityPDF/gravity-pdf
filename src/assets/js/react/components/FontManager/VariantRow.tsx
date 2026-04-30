/* Dependencies */
import { useState } from '@wordpress/element';
import { Button, DropZone, FormFileUpload } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { trash } from '@wordpress/icons';
/* Types */
import { FontVariantStyles } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

export type VariantKey = keyof FontVariantStyles;

export interface VariantDef {
	key: VariantKey;
	label: string;
	required: boolean;
}

interface Props {
	variantDef: VariantDef;
	value: string | File;
	onUpload: (file: File) => void;
	onDelete: () => void;
	onRejected: (message: string) => void;
}

const isTtf = (file: File) => file.name.toLowerCase().endsWith('.ttf');

/**
 * Display the basename of an existing file path or the live name of an
 * unsaved File. Both are server-opaque to the front-end — we only ever
 * surface the basename.
 * @param value
 */
const valueLabel = (value: string | File): string => {
	if (typeof value === 'object') {
		return value.name;
	}
	return value.substring(value.lastIndexOf('/') + 1);
};

const VariantRow = ({
	variantDef,
	value,
	onUpload,
	onDelete,
	onRejected,
}: Props) => {
	const [dragOver, setDragOver] = useState(false);
	const hasFile = !!value;

	const rejectMsg = __('Only .ttf files are supported.', 'gravity-pdf');

	const handleFiles = (files: File[]) => {
		setDragOver(false);
		const ttf = files.find(isTtf);
		if (ttf) {
			onUpload(ttf);
			return;
		}
		if (files.length > 0) {
			onRejected(rejectMsg);
			speak(rejectMsg, 'assertive');
		}
	};

	const className = [
		'gfpdf-fm-variant-row',
		hasFile ? 'is-filled' : 'is-empty',
		dragOver ? 'is-drag-over' : '',
		variantDef.required && !hasFile ? 'is-required-missing' : '',
	]
		.filter(Boolean)
		.join(' ');

	let fileText: string;
	if (dragOver) {
		fileText = __('Drop .ttf to upload', 'gravity-pdf');
	} else if (hasFile) {
		fileText = valueLabel(value);
	} else {
		fileText = __('No .ttf file added', 'gravity-pdf');
	}

	return (
		<div
			data-test="component-VariantRow"
			data-variant-key={variantDef.key}
			className={className}
		>
			<DropZone
				onFilesDrop={handleFiles}
				onDragEnter={() => setDragOver(true)}
				onDragLeave={() => setDragOver(false)}
			/>
			<div className="gfpdf-fm-variant-row__label">
				<span className="gfpdf-fm-variant-row__label-name">
					{variantDef.label}
				</span>
				{variantDef.required && (
					<span className="gfpdf-fm-required">
						{__('(required)', 'gravity-pdf')}
					</span>
				)}
			</div>
			<div
				className={
					hasFile
						? 'gfpdf-fm-variant-row__file'
						: 'gfpdf-fm-variant-row__file is-empty'
				}
			>
				{fileText}
			</div>
			<div className="gfpdf-fm-variant-row__actions">
				<FormFileUpload
					accept=".ttf"
					multiple={false}
					onChange={(event) => {
						const file = event.currentTarget.files?.[0];
						if (!file) {
							return;
						}
						if (!isTtf(file)) {
							onRejected(rejectMsg);
							speak(rejectMsg, 'assertive');
							return;
						}
						onUpload(file);
					}}
					render={({ openFileDialog }) => (
						<Button
							variant="secondary"
							size="small"
							onClick={openFileDialog}
							aria-label={
								hasFile
									? sprintf(
											/* translators: %s: variant label, e.g. "Italic" */
											__(
												'Replace %s font file',
												'gravity-pdf'
											),
											variantDef.label
										)
									: sprintf(
											/* translators: %s: variant label */
											__(
												'Upload %s font file',
												'gravity-pdf'
											),
											variantDef.label
										)
							}
						>
							{hasFile
								? __('Replace', 'gravity-pdf')
								: __('Upload', 'gravity-pdf')}
						</Button>
					)}
				/>
				{hasFile && !variantDef.required && (
					<Button
						variant="secondary"
						size="small"
						icon={trash}
						onClick={onDelete}
						aria-label={sprintf(
							/* translators: %s: variant label, e.g. "Italic" */
							__('Delete %s font file', 'gravity-pdf'),
							variantDef.label
						)}
					/>
				)}
			</div>
		</div>
	);
};

export default VariantRow;
