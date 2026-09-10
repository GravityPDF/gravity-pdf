/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { PREVIEW_SIZE } from '../constants';
import FontPreview from './FontPreview';
import PreviewControls from './PreviewControls';

/**
 * The Preview heading, its controls and the four faces
 *
 * Size and sample text are this section's own business — every detail pane shows the same block and none of
 * them reads what the controls are set to, so the state belongs here rather than in three parents.
 *
 * @param {Object} props
 * @param {string} props.fontKey The key the faces register under
 * @param {Object} props.files   Role → file row
 *
 * @return {JSX.Element} The section
 *
 * @since 7.0
 */
export default function PreviewSection({ fontKey, files }) {
	const [size, setSize] = useState(PREVIEW_SIZE.initial);
	const [sample, setSample] = useState('');

	return (
		<div className="section">
			<div className="section-heading-row">
				<h3 className="section-title">
					{__('Preview', 'gravity-pdf')}
				</h3>
				<PreviewControls
					size={size}
					onSize={setSize}
					sample={sample}
					onSample={setSample}
				/>
			</div>

			<FontPreview
				fontKey={fontKey}
				files={files ?? {}}
				size={size}
				sample={sample}
			/>
		</div>
	);
}
