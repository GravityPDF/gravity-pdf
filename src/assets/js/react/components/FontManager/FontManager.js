/* Dependencies */
import React, { useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
/* Components */
import FontManagerHeader from './FontManagerHeader';
import FontManagerBody from './FontManagerBody';
import { associatedFontManagerSelectBox } from '../../utilities/FontManager/associatedFontManagerSelectBox';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * FontManager component
 *
 * @param {Object} root0
 * @param {*}      root0.params
 * @param {*}      root0.navigate
 * @since 6.0
 */
const FontManager = ({ params, navigate }) => {
	const fontList = useSelector((s) => s.fontManager.fontList);
	const selectedFont = useSelector((s) => s.fontManager.selectedFont);
	const containerRef = useRef(null);

	/* Mirror latest values in refs so unmount cleanup reads current data, not stale closure */
	const fontListRef = useRef(fontList);
	fontListRef.current = fontList;
	const selectedFontRef = useRef(selectedFont);
	selectedFontRef.current = selectedFont;

	useEffect(() => {
		const handleFocus = (e) => {
			if (!containerRef.current.contains(e.target)) {
				e.stopPropagation();
				containerRef.current.focus();
			}
		};

		document.addEventListener('focus', handleFocus, true);

		/* Add focus if not currently applied to search box */
		if (
			// eslint-disable-next-line @wordpress/no-global-active-element
			document.activeElement &&
			// eslint-disable-next-line @wordpress/no-global-active-element
			document.activeElement.className !== 'wp-filter-search'
		) {
			containerRef.current.focus();
		}

		return () => {
			document.removeEventListener('focus', handleFocus, true);

			const tabLocation = window.location.search.substring(
				window.location.search.lastIndexOf('=') + 1
			);

			if (tabLocation !== 'tools') {
				associatedFontManagerSelectBox(
					fontListRef.current,
					selectedFontRef.current
				);
			}
		};
	}, []);

	const { id } = params;

	return (
		<div data-test="component-FontManager" ref={containerRef} tabIndex="0">
			<div className="backdrop theme-backdrop" />
			<div className="container theme-wrap font-manager">
				<FontManagerHeader id={id} />

				<FontManagerBody id={id} navigate={navigate} />
			</div>
		</div>
	);
};

FontManager.propTypes = {
	params: PropTypes.object,
	navigate: PropTypes.func.isRequired,
};

export default FontManager;
