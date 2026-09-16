/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { RangeControl, TextControl } from '@wordpress/components';
import { PREVIEW_SIZE } from '../constants';

/**
 * The size slider, and the sample-text field an installed font earns
 *
 * Typing sample text is what switches a preview from the catalogue's subset to the font's own files. Nothing
 * here is saved.
 *
 * @param {Object}   props
 * @param {number}   props.size     Point size
 * @param {Function} props.onSize
 * @param {string}   props.sample   The typed sample, or an empty string
 * @param {Function} props.onSample
 *
 * @return {JSX.Element} The controls
 *
 * @since 7.0
 */
export default function PreviewControls({ size, onSize, sample, onSample }) {
	return (
		<div className="gfpdf-fm-preview-controls">
			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={__('Size', 'gravity-pdf')}
				value={size}
				onChange={(value) => onSize(value ?? PREVIEW_SIZE.initial)}
				min={PREVIEW_SIZE.min}
				max={PREVIEW_SIZE.max}
				step={1}
			/>

			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={__('Sample text', 'gravity-pdf')}
				hideLabelFromVision
				placeholder={__('Sample text', 'gravity-pdf')}
				value={sample}
				onChange={onSample}
			/>
		</div>
	);
}
