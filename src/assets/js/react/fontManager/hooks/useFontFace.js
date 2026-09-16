/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Load a preview face, and give it back as a `font-family` value
 *
 * The rule the whole Font Manager rests on is that a swatch never pulls a megabyte of TTF: list surfaces pass
 * the source's small subset preview and observe themselves into view first, detail pages pass the four faces
 * outright. Anything with no URL — an upload whose folder is not web-reachable, every row while the mock layer
 * is in place — resolves to `null`, and the caller renders in the UI font.
 *
 * @param {string}        family          The `font-family` to register the faces under
 * @param {Array<Object>} sources         `{ url, weight, style }` per face
 * @param {Object}        options         Options
 * @param {boolean}       options.observe Wait until the element has scrolled into view
 *
 * @return {Object} `{ ref, fontFamily }`
 *
 * @since 7.0
 */
export function useFontFace(family, sources, { observe = false } = {}) {
	const ref = useRef(null);
	const [loaded, setLoaded] = useState(false);
	const [visible, setVisible] = useState(!observe);

	const key = sources
		.map((face) => `${face.url}|${face.weight}|${face.style}`)
		.join(',');

	useEffect(() => {
		if (!observe || visible || !ref.current) {
			return undefined;
		}

		return watch(ref.current, () => setVisible(true));
	}, [observe, visible]);

	useEffect(() => {
		const usable = sources.filter((face) => face.url);

		if (!visible || usable.length === 0 || !supported()) {
			return undefined;
		}

		const faces = usable.map(
			(face) =>
				new window.FontFace(family, `url(${face.url})`, {
					weight: String(face.weight),
					style: face.style,
					display: 'swap',
				})
		);

		let cancelled = false;

		Promise.all(
			faces.map((face) =>
				face.load().then(() => document.fonts.add(face))
			)
		)
			.then(() => !cancelled && setLoaded(true))
			.catch(() => !cancelled && setLoaded(false));

		return () => {
			cancelled = true;

			faces.forEach((face) => document.fonts.delete(face));
			setLoaded(false);
		};
		/* `key` stands in for `sources`, which is a fresh array on every render */
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [family, key, visible]);

	return { ref, fontFamily: loaded ? `'${family}'` : null };
}

/**
 * One observer for every swatch on the page
 *
 * A browse grid mounts fifty cards and the sidebar one row per installed font; an observer each is fifty
 * observers doing the job of one.
 *
 * @since 7.0
 */
const watchers = new Map();

let observer = null;

function watch(element, onVisible) {
	if (typeof window.IntersectionObserver === 'undefined') {
		onVisible();

		return undefined;
	}

	if (!observer) {
		observer = new window.IntersectionObserver((entries) =>
			entries
				.filter((entry) => entry.isIntersecting)
				.forEach((entry) => watchers.get(entry.target)?.())
		);
	}

	watchers.set(element, onVisible);
	observer.observe(element);

	return () => {
		watchers.delete(element);
		observer.unobserve(element);
	};
}

function supported() {
	return (
		typeof window !== 'undefined' &&
		typeof window.FontFace === 'function' &&
		!!document.fonts
	);
}
