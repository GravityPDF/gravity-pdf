/* Dependencies */
import { useEffect, useRef } from '@wordpress/element';
import { NavigateFunction } from 'react-router';
import { useSelect } from '@wordpress/data';
import { fontManagerStore } from '../../store/fontManagerStore';
/* Components */
import FontManagerHeader from './FontManagerHeader';
import FontManagerBody from './FontManagerBody';
import { associatedFontManagerSelectBox } from '../../utilities/FontManager/associatedFontManagerSelectBox';
import { FontItem } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	params?: { id?: string };
	navigate: NavigateFunction;
}

const FontManager = ({ params, navigate }: Props) => {
	const fontList = useSelect(
		(select) => select(fontManagerStore).getFontList(),
		[]
	);
	const selectedFont = useSelect(
		(select) => select(fontManagerStore).getSelectedFont(),
		[]
	);
	const containerRef = useRef<HTMLDivElement>(null);

	/* Mirror latest values in refs so unmount cleanup reads current data, not stale closure */
	const fontListRef = useRef<FontItem[]>(fontList);
	fontListRef.current = fontList;
	const selectedFontRef = useRef(selectedFont);
	selectedFontRef.current = selectedFont;

	useEffect(() => {
		const handleFocus = (e: FocusEvent) => {
			if (
				containerRef.current &&
				!containerRef.current.contains(e.target as Node)
			) {
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
			(document.activeElement as HTMLElement).className !==
				'wp-filter-search'
		) {
			containerRef.current?.focus();
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

	const id = params?.id;

	return (
		<div data-test="component-FontManager" ref={containerRef} tabIndex={0}>
			<div className="backdrop theme-backdrop" />
			<div className="container theme-wrap font-manager">
				<FontManagerHeader id={id} />

				<FontManagerBody id={id} navigate={navigate} />
			</div>
		</div>
	);
};

export default FontManager;
