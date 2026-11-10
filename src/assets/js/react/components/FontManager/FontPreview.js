/* Dependencies */
import React, { useEffect, useRef, useState } from 'react';
import PropTypes from 'prop-types';
/* Components */
import Spinner from '../Spinner';
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

// Long enough that clicking through the list doesn't start downloads, which aren't cancelled by moving on
const LOAD_DELAY = 200;
// Anything larger waits for a click: of the core fonts, Sun-ExtA, Sun-ExtB and UnBatang
const MAX_AUTO_LOAD_BYTES = 4 * 1024 * 1024;
const ALL_STYLES = 'all';

/**
 * One font's samples, remounted for each font so its load state and chosen style start afresh
 *
 * @param { Object }   props
 * @param { Object }   props.font
 * @param { string }   props.base
 * @param { string }   props.text
 * @param { number }   props.size
 * @param { Function } props.setText
 * @param { Function } props.setSize
 *
 * @return { JSX.Element } FontSamples component
 *
 * @since 6.19.0
 */
const FontSamples = ({ font, base, text, size, setText, setSize }) => {
	const [loaded, setLoaded] = useState(false);
	const [choice, setChoice] = useState('');
	const [loadRequested, setLoadRequested] = useState(false);
	const [readyStyles, setReadyStyles] = useState({});
	const boxRef = useRef(null);

	useEffect(() => {
		let timer;
		const observer = new window.IntersectionObserver((entries) => {
			clearTimeout(timer);

			if (entries.at(-1).isIntersecting) {
				timer = setTimeout(() => setLoaded(true), LOAD_DELAY);
			}
		});

		observer.observe(boxRef.current);

		return () => {
			observer.disconnect();
			clearTimeout(timer);
		};
	}, []);

	const family = 'gfpdf-preview-' + font.id.replace(/[^\w-]/g, '');
	const styles = FONT_STYLES.filter(({ key }) => font[key]);
	const shownStyles =
		choice === ALL_STYLES
			? styles
			: styles
					.filter(({ key }) => key === choice)
					.concat(styles)
					.slice(0, 1);
	const bytes = shownStyles.reduce(
		(total, { key }) => total + (font.file_sizes?.[key] || 0),
		0
	);
	const needsLoadClick = bytes > MAX_AUTO_LOAD_BYTES && !loadRequested;
	const facesRequested = loaded && !needsLoadClick && !!document.fonts;
	const isLoading = (key) => facesRequested && !readyStyles[key];
	const loadingKeys = shownStyles.filter(({ key }) => isLoading(key));

	useEffect(() => {
		let current = true;

		loadingKeys.forEach(({ key, weight, style }) => {
			const settle = (result) => () =>
				current &&
				setReadyStyles((ready) => ({ ...ready, [key]: result }));

			document.fonts
				.load(`${style} ${weight} 16px "${family}"`)
				.then(settle('loaded'), settle('failed'));
		});

		return () => {
			current = false;
		};
	}, [loadingKeys.map(({ key }) => key).join()]); // eslint-disable-line react-hooks/exhaustive-deps

	const faces = styles
		.map(({ key, weight, style }) => {
			const file = encodeURIComponent(reduceFontFileName(font[key]));

			return `@font-face{font-family:"${family}";font-weight:${weight};font-style:${style};font-display:swap;src:url("${base}${file}") format("truetype");}`;
		})
		.join('');

	return (
		<div data-test="component-FontPreview" className="font-section">
			{loaded && !needsLoadClick && <style>{faces}</style>}

			<div className="font-section-head">
				<h3>{GFPDF.fontManagerPreview}</h3>

				<div className="font-preview-controls">
					{styles.length > 1 && (
						<select
							className="font-preview-style"
							data-test="font-preview-style"
							aria-label={GFPDF.fontManagerPreviewStyle}
							value={
								choice === ALL_STYLES
									? ALL_STYLES
									: shownStyles[0].key
							}
							onChange={(e) => setChoice(e.target.value)}
						>
							<option value={ALL_STYLES}>
								{GFPDF.fontManagerPreviewAll}
							</option>
							{FONT_STYLES.map(({ key, label }) => (
								<option
									key={key}
									value={key}
									disabled={!font[key]}
								>
									{GFPDF[label]}
								</option>
							))}
						</select>
					)}

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

			<div ref={boxRef} className="font-preview-box">
				{needsLoadClick ? (
					<div className="font-preview-row">
						<button
							type="button"
							className="button"
							data-test="font-preview-load"
							onClick={() => setLoadRequested(true)}
						>
							{GFPDF.fontManagerPreviewLoad}
						</button>
					</div>
				) : (
					shownStyles.map(({ key, label, weight, style }) => (
						<div key={key} className="font-preview-row">
							<div className="font-preview-label">
								{GFPDF[label]}
							</div>
							<div
								data-test="font-preview-sample"
								dir="auto"
								style={{
									fontFamily: `"${family}", sans-serif`,
									fontWeight: weight,
									fontStyle: style,
									fontSize: `${size}pt`,
								}}
							>
								{text ||
									font.sample ||
									GFPDF.fontManagerPreviewText}
								{isLoading(key) && (
									<span
										data-test="font-preview-loading"
										role="status"
									>
										<Spinner style="delete-font" />
									</span>
								)}
								{readyStyles[key] === 'failed' && (
									<span
										data-test="font-preview-failed"
										className="font-preview-failed"
									>
										{GFPDF.fontManagerPreviewFailed}
									</span>
								)}
							</div>
						</div>
					))
				)}
			</div>
		</div>
	);
};

/**
 * A font's styles in sample text; files load after a moment on screen, and browsers skip faces no text uses
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
	const [size, setSize] = useState(12);
	const base = font.base_url || GFPDF.fontsUrl;

	if (!base) {
		return (
			<div data-test="font-preview-unavailable" className="font-section">
				<h3>{GFPDF.fontManagerPreview}</h3>
				<p>{GFPDF.fontManagerPreviewUnavailable}</p>
			</div>
		);
	}

	return (
		<FontSamples
			key={font.id}
			font={font}
			base={base}
			text={text}
			size={size}
			setText={setText}
			setSize={setSize}
		/>
	);
};

/**
 * PropTypes
 *
 * @since 6.19.0
 */
FontSamples.propTypes = {
	font: PropTypes.object.isRequired,
	base: PropTypes.string.isRequired,
	text: PropTypes.string.isRequired,
	size: PropTypes.number.isRequired,
	setText: PropTypes.func.isRequired,
	setSize: PropTypes.func.isRequired,
};

FontPreview.propTypes = {
	font: PropTypes.object.isRequired,
};

export default FontPreview;
