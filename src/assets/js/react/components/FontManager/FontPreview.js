/* Dependencies */
import React, { useState } from 'react';
import PropTypes from 'prop-types';
/* Utilities */
import {
	FONT_STYLES,
	reduceFontFileName,
} from '../../utilities/FontManager/fontManagerReducer';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * A font's styles in sample text, with its files loaded only once this is shown
 *
 * @param { Object } props
 * @param { Object } props.font
 *
 * @return { JSX.Element } FontPreview component
 *
 * @since 6.19.0
 */
const FontPreview = ({ font }) => {
	const [text, setText] = useState('');
	const [size, setSize] = useState(10);

	const family = 'gfpdf-preview-' + font.id.replace(/[^\w-]/g, '');
	const base = font.base_url || GFPDF.fontsUrl;
	const styles = FONT_STYLES.filter(({ key }) => font[key]);

	const faces = styles
		.map(({ key, weight, style }) => {
			const file = encodeURIComponent(reduceFontFileName(font[key]));

			return `@font-face{font-family:"${family}";font-weight:${weight};font-style:${style};src:url("${base}${file}") format("truetype");}`;
		})
		.join('');

	return (
		<div data-test="component-FontPreview" className="font-section">
			<style>{faces}</style>

			<div className="font-section-head">
				<h3>{GFPDF.fontManagerPreview}</h3>

				<div className="font-preview-controls">
					<input
						type="text"
						className="font-preview-text"
						placeholder={GFPDF.fontManagerSampleText}
						aria-label={GFPDF.fontManagerSampleText}
						value={text}
						onChange={(e) => setText(e.target.value)}
					/>

					<div className="font-preview-size">
						<label htmlFor="gfpdf-font-preview-size">
							{GFPDF.fontListSize}
						</label>
						<input
							id="gfpdf-font-preview-size"
							type="range"
							min="8"
							max="36"
							value={size}
							onChange={(e) => setSize(Number(e.target.value))}
						/>
						<span>
							{size} {GFPDF.fontManagerPreviewUnit}
						</span>
					</div>
				</div>
			</div>

			<div className="font-preview-box">
				{styles.map(({ key, label, weight, style }) => (
					<div key={key} className="font-preview-row">
						<div className="font-preview-label">{GFPDF[label]}</div>
						<div
							data-test="font-preview-sample"
							style={{
								fontFamily: `"${family}"`,
								fontWeight: weight,
								fontStyle: style,
								fontSize: `${size}pt`,
							}}
						>
							{text || GFPDF.fontManagerPreviewText}
						</div>
					</div>
				))}
			</div>
		</div>
	);
};

/**
 * PropTypes
 *
 * @since 6.19.0
 */
FontPreview.propTypes = {
	font: PropTypes.object.isRequired,
};

export default FontPreview;
