/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { trash } from '@wordpress/icons';
import { useRef, useState } from '@wordpress/element';

/**
 * One face of a custom font: its filename, and the two things you can do to it
 *
 * The row is its own drop target rather than the panel being one, so dropping four files means four
 * deliberate gestures and no guessing at which face a file was meant to fill.
 *
 * @param {Object}   props
 * @param {Object}   props.role     One of `ROLES`
 * @param {?string}  props.filename What is there now
 * @param {Function} props.onFile   Called with a `File`
 * @param {Function} props.onDelete
 *
 * @return {JSX.Element} The row
 *
 * @since 7.0
 */
export default function FontVariantRow({ role, filename, onFile, onDelete }) {
	const input = useRef(null);
	const [over, setOver] = useState(false);

	const stop = (event) => {
		event.preventDefault();
		event.stopPropagation();
	};

	const classes = [
		'variant-row',
		filename ? '' : 'missing',
		over ? 'drag-over' : '',
	]
		.filter(Boolean)
		.join(' ');

	return (
		<div
			className={classes}
			onDragOver={(event) => {
				stop(event);
				setOver(true);
			}}
			onDragEnter={(event) => {
				stop(event);
				setOver(true);
			}}
			onDragLeave={(event) => {
				stop(event);
				setOver(false);
			}}
			onDrop={(event) => {
				stop(event);
				setOver(false);

				const file = event.dataTransfer?.files?.[0];

				if (file) {
					onFile(file);
				}
			}}
		>
			<div className="variant-label">
				<span className="variant-label-name">{role.label}</span>
				{role.required && (
					<span className="variant-required">
						{__('(required)', 'gravity-pdf')}
					</span>
				)}
			</div>

			<div className={`variant-file ${filename ? '' : 'empty'}`}>
				{dropLabel(over, filename)}
			</div>

			<input
				ref={input}
				type="file"
				accept=".ttf"
				className="gfpdf-fm-file-input"
				onChange={(event) => {
					const file = event.target.files?.[0];

					if (file) {
						onFile(file);
					}

					event.target.value = '';
				}}
			/>

			{filename ? (
				<div className="variant-actions">
					<Button
						variant="secondary"
						size="small"
						onClick={() => input.current?.click()}
					>
						{__('Replace', 'gravity-pdf')}
					</Button>

					{!role.required && (
						<Button
							variant="secondary"
							size="small"
							icon={trash}
							label={sprintf(
								/* translators: %s: a face name, e.g. "Bold Italic" */
								__('Delete %s', 'gravity-pdf'),
								role.label
							)}
							onClick={onDelete}
						/>
					)}
				</div>
			) : (
				<Button
					variant="secondary"
					size="small"
					onClick={() => input.current?.click()}
				>
					{__('Upload', 'gravity-pdf')}
				</Button>
			)}
		</div>
	);
}

function dropLabel(over, filename) {
	if (over) {
		return __('Drop .ttf to upload', 'gravity-pdf');
	}

	return filename || __('No .ttf file added', 'gravity-pdf');
}
