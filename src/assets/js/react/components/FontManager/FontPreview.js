/* Dependencies */
import React, {
	createContext,
	useCallback,
	useContext,
	useEffect,
	useMemo,
	useRef,
	useState,
} from 'react';
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
// Faces stay parsed while the Font Manager is open; this holds the three large core fonts together
const MAX_CACHED_BYTES = 64 * 1024 * 1024;

const PreviewCache = createContext(null);

/**
 * Remembers each font's faces, the styles that loaded and a click to load it, while it's mounted
 *
 * Entries are kept in the order last viewed, and the oldest are dropped once their files pass the budget
 *
 * @param { Object }      props
 * @param { JSX.Element } props.children
 *
 * @return { JSX.Element } FontPreviewCache component
 *
 * @since 6.19.0
 */
export const FontPreviewCache = ({ children }) => {
	const [fonts, setFonts] = useState(() => new Map());

	const update = useCallback(
		(id, change, viewed = false) =>
			setFonts((all) => {
				const entry = change(all.get(id));

				if (entry === all.get(id) && (!viewed || !entry)) {
					return all;
				}

				const next = new Map(all);

				if (viewed) {
					next.delete(id);
				}

				if (entry) {
					next.set(id, entry);
				} else {
					next.delete(id);
				}

				let total = 0;
				next.forEach((font) => (total += font.bytes || 0));

				// The last viewed is the font on screen, so it's never dropped
				[...next.keys()].slice(0, -1).forEach((key) => {
					if (total > MAX_CACHED_BYTES && next.get(key).bytes) {
						total -= next.get(key).bytes;
						next.delete(key);
					}
				});

				return next;
			}),
		[]
	);

	const value = useMemo(() => ({ fonts, update }), [fonts, update]);

	// One sheet per font in a fixed order, so adding a font doesn't make the browser re-read the others
	const sheets = useMemo(
		() =>
			[...fonts]
				.filter(([, font]) => font.shown)
				.sort(([a], [b]) => (a < b ? -1 : 1))
				.map(([id, font]) => <style key={id}>{font.faces}</style>),
		[fonts]
	);

	return (
		<PreviewCache.Provider value={value}>
			{sheets}
			{children}
		</PreviewCache.Provider>
	);
};

/**
 * One font's samples, remounted for each font so its chosen style starts afresh; what loaded is kept in the cache
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
	const { fonts, update } = useContext(PreviewCache);
	const [loaded, setLoaded] = useState(false);
	const [choice, setChoice] = useState('');
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
	// Keys the cache: the sizes are in it as a saved custom font can keep its name but change its files
	const faces = useMemo(
		() =>
			`/*${JSON.stringify(font.file_sizes || {})}*/` +
			styles
				.map(({ key, weight, style }) => {
					const file = encodeURIComponent(
						reduceFontFileName(font[key])
					);

					return `@font-face{font-family:"${family}";font-weight:${weight};font-style:${style};font-display:swap;src:url("${base}${file}") format("truetype");}`;
				})
				.join(''),
		[font, base] // eslint-disable-line react-hooks/exhaustive-deps
	);
	const own = (current) => (current?.faces === faces ? current : undefined);
	const entry = own(fonts.get(font.id));

	// Arriving drops faces of the font's old files, and leaving drops a font with a failed face so it's retried
	useEffect(() => {
		update(font.id, own, true);

		return () =>
			update(font.id, (current) =>
				current && !Object.values(current.styles).includes('failed')
					? current
					: undefined
			);
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

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
	const needsLoadClick = bytes > MAX_AUTO_LOAD_BYTES && !entry?.requested;
	const showFaces = (loaded || !!entry?.shown) && !needsLoadClick;
	const facesShown = showFaces && !!entry?.shown && !!document.fonts;
	const isLoading = (key) => facesShown && !entry.styles[key];
	const loadingKeys = shownStyles.filter(({ key }) => isLoading(key));

	useEffect(() => {
		if (showFaces && !entry?.shown) {
			update(font.id, (current) => ({
				styles: {},
				...own(current),
				faces,
				shown: true,
				bytes: Object.values(font.file_sizes || {}).reduce(
					(total, fileBytes) => total + fileBytes,
					0
				),
			}));
		}
	}, [showFaces, faces]); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect(() => {
		let current = true;

		loadingKeys.forEach(({ key, weight, style }) => {
			const settle = (result) => () =>
				current &&
				update(font.id, (cachedFont) =>
					own(cachedFont)
						? {
								...cachedFont,
								styles: { ...cachedFont.styles, [key]: result },
							}
						: cachedFont
				);

			document.fonts
				.load(`${style} ${weight} 16px "${family}"`)
				.then(settle('loaded'), settle('failed'));
		});

		return () => {
			current = false;
		};
	}, [loadingKeys.map(({ key }) => key).join()]); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<div data-test="component-FontPreview" className="font-section">
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
							onClick={() =>
								update(font.id, (current) => ({
									styles: {},
									...own(current),
									faces,
									requested: true,
								}))
							}
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
								{entry?.styles[key] === 'failed' && (
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

FontPreviewCache.propTypes = {
	children: PropTypes.node.isRequired,
};

FontPreview.propTypes = {
	font: PropTypes.object.isRequired,
};

export default FontPreview;
