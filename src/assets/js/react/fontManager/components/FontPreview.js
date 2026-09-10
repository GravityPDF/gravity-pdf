/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { useFontFace } from '../hooks/useFontFace';
import { ROLES } from '../constants';

const SAMPLE = __('The quick brown fox jumps over the lazy dog', 'gravity-pdf');

/**
 * One line per face, at the size the controls chose
 *
 * A detail page shows all four whatever the row has, because the empty ones are the point: an admin looking at
 * this panel is deciding whether to add a Bold, and a preview that quietly omits it answers the wrong question.
 * A face with no file, and every face while the fonts folder is not web-reachable, renders in the UI font.
 *
 * @param {Object} props
 * @param {string} props.fontKey The key the faces register under
 * @param {Object} props.files   Role → `{ url }`
 * @param {number} props.size    Point size
 * @param {string} props.sample  The typed sample, when there is one
 *
 * @return {JSX.Element} The preview box
 *
 * @since 7.0
 */
export default function FontPreview({ fontKey, files, size, sample }) {
	const { fontFamily } = useFontFace(
		`gfpdf-detail-${fontKey}`,
		ROLES.map((role) => ({
			url: files[role.id]?.url ?? null,
			weight: role.weight,
			style: role.style,
		}))
	);

	const text = sample.trim() || SAMPLE;

	return (
		<div className="preview-box">
			{ROLES.map((role) => (
				<div className="preview-variant" key={role.id}>
					<div className="preview-variant-label">{role.label}</div>
					<div
						className="preview-variant-body"
						style={{
							fontFamily: fontFamily ?? undefined,
							fontWeight: role.weight,
							fontStyle: role.style,
							fontSize: `${size}pt`,
						}}
					>
						{files[role.id] ? (
							text
						) : (
							<span className="preview-variant-missing">
								{__('No file added', 'gravity-pdf')}
							</span>
						)}
					</div>
				</div>
			))}
		</div>
	);
}
